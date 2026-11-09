<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/*
 * Refund approval workflow.
 *
 * A receptionist's refund is a request until an administrator confirms
 * it; an administrator's own refund goes through at once. While a
 * request is pending the payment stays "paid" (no money has gone back).
 *
 *   refund_status        NULL | pending | approved | rejected
 *   refund_reason        why the customer wants the money back (printed on the receipt)
 *   refund_requested_*   who asked for it, and when
 *   refund_reviewed_*    who approved / rejected it, and when
 */
class AddRefundApprovalToPayments extends Migration
{
    private const FIELDS = [
        'refund_status'          => ['type' => 'VARCHAR', 'constraint' => 20,  'null' => true, 'after' => 'payment_status'],
        'refund_reason'          => ['type' => 'TEXT',                         'null' => true, 'after' => 'refund_status'],
        'refund_requested_by'    => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true, 'after' => 'refund_reason'],
        'refund_requested_by_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'after' => 'refund_requested_by'],
        'refund_requested_at'    => ['type' => 'DATETIME',                     'null' => true, 'after' => 'refund_requested_by_id'],
        'refund_reviewed_by'     => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true, 'after' => 'refund_requested_at'],
        'refund_reviewed_by_id'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'after' => 'refund_reviewed_by'],
        'refund_reviewed_at'     => ['type' => 'DATETIME',                     'null' => true, 'after' => 'refund_reviewed_by_id'],
    ];

    public function up()
    {
        $add = [];
        foreach (self::FIELDS as $name => $def) {
            if (! $this->db->fieldExists($name, 'payments')) {
                $add[$name] = $def;
            }
        }
        if ($add !== []) {
            $this->forge->addColumn('payments', $add);
        }

        if (! $this->indexExists('idx_payments_refund_status')) {
            $this->db->query('ALTER TABLE `payments` ADD INDEX `idx_payments_refund_status` (`refund_status`)');
        }

        // Refunds made before this workflow: treat them as approved, and
        // carry the old void reason over as the refund reason.
        $this->db->query("
            UPDATE payments
            SET refund_status       = 'approved',
                refund_reason       = COALESCE(refund_reason, void_reason),
                refund_reviewed_by  = COALESCE(refund_reviewed_by, voided_by),
                refund_reviewed_at  = COALESCE(refund_reviewed_at, voided_at)
            WHERE payment_status = 'refunded' AND refund_status IS NULL
        ");
    }

    public function down()
    {
        foreach (array_keys(self::FIELDS) as $name) {
            if ($this->db->fieldExists($name, 'payments')) {
                $this->forge->dropColumn('payments', $name);
            }
        }
    }

    private function indexExists(string $name): bool
    {
        return (bool) $this->db->query('SHOW INDEX FROM `payments` WHERE Key_name = ?', [$name])->getRow();
    }
}
