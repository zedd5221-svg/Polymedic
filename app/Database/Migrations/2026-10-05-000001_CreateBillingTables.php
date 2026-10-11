<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * CreateBillingTables
 * ===================
 *
 * Adds the invoice layer that sits on top of the existing `payments`
 * table. Nothing is dropped or renamed. All additions to `payments`
 * are guarded with fieldExists(), so re-running this migration (or
 * running it against a partially-updated database) is safe.
 *
 * After the schema is in place, the migration backfills one invoice
 * per existing payments row, decoding the JSON in `payments.services`
 * into invoice_items. That makes every historic payment visible in
 * the new Billing UI immediately.
 *
 * down() reverses everything: it drops the two new tables and removes
 * the four columns added to `payments`.
 */
class CreateBillingTables extends Migration
{
    public function up()
    {
        // ---------------------------------------------------------
        // 1. invoices
        // ---------------------------------------------------------
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'invoice_no' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => false,
            ],
            'patient_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'patient_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => false,
            ],
            'appointment_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'request_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'request_type' => [
                'type'       => 'ENUM',
                'constraint' => ['lab', 'xray'],
                'null'       => true,
            ],
            'subtotal' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
                'null'       => false,
                'default'    => 0.00,
            ],
            'discount_type' => [
                'type'       => 'ENUM',
                'constraint' => ['none', 'percent', 'fixed'],
                'null'       => false,
                'default'    => 'none',
            ],
            'discount_value' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
                'null'       => false,
                'default'    => 0.00,
            ],
            'discount_reason' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
            'tax' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
                'null'       => false,
                'default'    => 0.00,
            ],
            'total' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
                'null'       => false,
                'default'    => 0.00,
            ],
            'amount_paid' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
                'null'       => false,
                'default'    => 0.00,
            ],
            'balance' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
                'null'       => false,
                'default'    => 0.00,
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['draft', 'unpaid', 'partial', 'paid', 'void', 'refunded'],
                'null'       => false,
                'default'    => 'unpaid',
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_by' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'voided_by' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'voided_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'void_reason' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('invoice_no');
        $this->forge->addKey('patient_code');
        $this->forge->addKey('request_id');
        $this->forge->addKey('request_type');
        $this->forge->addKey('status');
        $this->forge->addKey('created_at');
        $this->forge->addKey(['request_id', 'request_type'], false, false, 'idx_request');

        $this->forge->createTable('invoices', true);

        // ---------------------------------------------------------
        // 2. invoice_items
        // ---------------------------------------------------------
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'invoice_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => false,
            ],
            'service_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => false,
            ],
            'category' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => true,
            ],
            'qty' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'null'       => false,
                'default'    => 1.00,
            ],
            'unit_price' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
                'null'       => false,
                'default'    => 0.00,
            ],
            'line_total' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
                'null'       => false,
                'default'    => 0.00,
            ],
            'notes' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('invoice_id');

        $this->forge->createTable('invoice_items', true);

        // ---------------------------------------------------------
        // 3. additive columns on payments (guarded)
        // ---------------------------------------------------------
        $this->addPaymentsColumnIfMissing('invoice_id', [
            'type'       => 'INT',
            'constraint' => 11,
            'null'       => true,
        ], 'id');

        $this->addPaymentsColumnIfMissing('reference_no', [
            'type'       => 'VARCHAR',
            'constraint' => 100,
            'null'       => true,
        ], 'payment_method');

        $this->addPaymentsColumnIfMissing('voided_by', [
            'type'       => 'VARCHAR',
            'constraint' => 100,
            'null'       => true,
        ], 'received_by');

        $this->addPaymentsColumnIfMissing('voided_at', [
            'type' => 'DATETIME',
            'null' => true,
        ], 'received_by');

        $this->addPaymentsColumnIfMissing('void_reason', [
            'type'       => 'VARCHAR',
            'constraint' => 255,
            'null'       => true,
        ], 'received_by');

        // Index on the new FK-style column, if it doesn't already exist.
        $this->addPaymentsIndexIfMissing('invoice_id', 'idx_payments_invoice');

        // ---------------------------------------------------------
        // 4. backfill — one invoice per existing payments row
        // ---------------------------------------------------------
        $this->backfillInvoicesFromPayments();
    }

    public function down()
    {
        // Drop the two tables created above.
        $this->forge->dropTable('invoice_items', true);
        $this->forge->dropTable('invoices', true);

        // Remove the columns we added to payments, if they exist.
        foreach (['invoice_id', 'reference_no', 'voided_by', 'voided_at', 'void_reason'] as $col) {
            if ($this->db->fieldExists($col, 'payments')) {
                $this->forge->dropColumn('payments', $col);
            }
        }

        // Drop the index if it survived the column drops.
        try {
            $this->db->query('DROP INDEX idx_payments_invoice ON payments');
        } catch (\Throwable $e) {
            // ignore — index already gone
        }
    }

    // =============================================================
    // HELPERS
    // =============================================================

    private function addPaymentsColumnIfMissing(string $name, array $definition, ?string $after = null): void
    {
        if ($this->db->fieldExists($name, 'payments')) {
            return;
        }

        $sql = 'ALTER TABLE `payments` ADD COLUMN `' . $name . '` ';

        switch (strtoupper($definition['type'])) {
            case 'INT':
                $sql .= 'INT(' . ($definition['constraint'] ?? 11) . ') ';
                break;
            case 'VARCHAR':
                $sql .= 'VARCHAR(' . ($definition['constraint'] ?? 255) . ') ';
                break;
            case 'DATETIME':
                $sql .= 'DATETIME ';
                break;
            case 'TEXT':
                $sql .= 'TEXT ';
                break;
            case 'DECIMAL':
                $sql .= 'DECIMAL(' . ($definition['constraint'] ?? '12,2') . ') ';
                break;
            default:
                $sql .= 'VARCHAR(255) ';
                break;
        }

        $sql .= !empty($definition['null']) ? 'NULL ' : 'NOT NULL ';

        if (array_key_exists('default', $definition)) {
            $def = $definition['default'];
            $sql .= is_numeric($def) ? 'DEFAULT ' . $def . ' ' : "DEFAULT '" . $def . "' ";
        }

        if ($after !== null) {
            $sql .= 'AFTER `' . $after . '`';
        }

        $this->db->query($sql);
    }

    private function addPaymentsIndexIfMissing(string $column, string $indexName): void
    {
        try {
            $row = $this->db->query(
                "SHOW INDEX FROM `payments` WHERE Key_name = ?",
                [$indexName]
            )->getRow();

            if ($row) {
                return;
            }

            $this->db->query(
                'ALTER TABLE `payments` ADD INDEX `' . $indexName . '` (`' . $column . '`)'
            );
        } catch (\Throwable $e) {
            log_message('warning', 'Billing migration — index add skipped: ' . $e->getMessage());
        }
    }

    /**
     * For every existing payments row, create a matching invoice and
     * one invoice_item per service in the JSON `services` column.
     * Idempotent: skips payments that already have invoice_id set.
     */
    private function backfillInvoicesFromPayments(): void
    {
        $rows = $this->db
            ->table('payments')
            ->select('id, request_id, request_type, patient_name, patient_code, appointment_id,
                      services, total_amount, amount_paid, change_amount, payment_method,
                      payment_status, payment_date, reference_number, received_by, notes,
                      invoice_id')
            ->get()
            ->getResultArray();

        if (empty($rows)) {
            return;
        }

        // Annual sequence cursor, so we don't hit the DB for every row.
        $year    = (int) date('Y');
        $nextSeq = $this->nextInvoiceSeq($year);

        foreach ($rows as $p) {
            // Skip if already linked.
            if (!empty($p['invoice_id'])) {
                continue;
            }

            // Respect the enum on payments: only 'paid' is treated as
            // settled. 'pending'/'refunded'/'failed' rows are imported
            // with a matching status on the invoice.
            $payStatus  = (string) ($p['payment_status'] ?? 'paid');
            $total      = (float) $p['total_amount'];
            $paid       = (float) $p['amount_paid'];

            $invoiceStatus = match ($payStatus) {
                'paid'     => ($paid >= $total ? 'paid' : 'partial'),
                'refunded' => 'refunded',
                'failed'   => 'void',
                'pending'  => 'unpaid',
                default    => 'unpaid',
            };

            $invoiceNo = sprintf('INV-%d-%06d', $year, $nextSeq++);

            $invoiceId = $this->db->table('invoices')->insert([
                'invoice_no'      => $invoiceNo,
                'patient_code'    => $p['patient_code'] ?? null,
                'patient_name'    => (string) ($p['patient_name'] ?? 'Unknown'),
                'appointment_id'  => $p['appointment_id'] ?? null,
                'request_id'      => $p['request_id'] ?? null,
                'request_type'    => in_array($p['request_type'] ?? '', ['lab', 'xray'], true)
                                        ? $p['request_type']
                                        : null,
                'subtotal'        => number_format($total, 2, '.', ''),
                'discount_type'   => 'none',
                'discount_value'  => '0.00',
                'discount_reason' => null,
                'tax'             => '0.00',
                'total'           => number_format($total, 2, '.', ''),
                'amount_paid'     => number_format($paid, 2, '.', ''),
                'balance'         => number_format(max(0, $total - $paid), 2, '.', ''),
                'status'          => $invoiceStatus,
                'notes'           => $p['notes'] ?? null,
                'created_by'      => $p['received_by'] ?? null,
                'voided_by'       => null,
                'voided_at'       => null,
                'void_reason'     => null,
                'created_at'      => $p['payment_date'] ?? date('Y-m-d H:i:s'),
                'updated_at'      => $p['payment_date'] ?? date('Y-m-d H:i:s'),
            ])
            ? $this->db->insertID()
            : 0;

            if (!$invoiceId) {
                log_message('error', 'Billing migration — could not create invoice for payment #' . $p['id']);
                continue;
            }

            // Decode services JSON. Accepts both shapes the app has used:
            //   [{"name":"...","charge":123.45}, ...]   and   ["...", ...]
            $items = json_decode((string) ($p['services'] ?? '[]'), true);
            if (!is_array($items)) {
                $items = [];
            }

            $computedSubtotal = 0.0;
            $lineRows         = [];

            foreach ($items as $item) {
                if (is_array($item)) {
                    $name  = trim((string) ($item['name'] ?? $item['service_name'] ?? ''));
                    $price = isset($item['price'])
                        ? (float) $item['price']
                        : (isset($item['charge'])
                            ? (float) $item['charge']
                            : (isset($item['amount']) ? (float) $item['amount'] : 0.0));
                } else {
                    $name  = trim((string) $item);
                    $price = 0.0;
                }

                if ($name === '') {
                    continue;
                }

                $lineRows[] = [
                    'invoice_id'   => $invoiceId,
                    'service_name' => $name,
                    'category'     => null,
                    'qty'          => '1.00',
                    'unit_price'   => number_format($price, 2, '.', ''),
                    'line_total'   => number_format($price, 2, '.', ''),
                    'notes'        => null,
                    'created_at'   => $p['payment_date'] ?? date('Y-m-d H:i:s'),
                    'updated_at'   => $p['payment_date'] ?? date('Y-m-d H:i:s'),
                ];

                $computedSubtotal += $price;
            }

            // If the JSON produced no items, add a single line for the
            // recorded total, so the invoice isn't blank.
            if (empty($lineRows) && $total > 0) {
                $lineRows[] = [
                    'invoice_id'   => $invoiceId,
                    'service_name' => 'Services (legacy)',
                    'category'     => null,
                    'qty'          => '1.00',
                    'unit_price'   => number_format($total, 2, '.', ''),
                    'line_total'   => number_format($total, 2, '.', ''),
                    'notes'        => 'Imported from a pre-billing payment',
                    'created_at'   => $p['payment_date'] ?? date('Y-m-d H:i:s'),
                    'updated_at'   => $p['payment_date'] ?? date('Y-m-d H:i:s'),
                ];
                $computedSubtotal = $total;
            }

            if (!empty($lineRows)) {
                $this->db->table('invoice_items')->insertBatch($lineRows);
            }

            // Link the payment back.
            $this->db->table('payments')
                ->where('id', (int) $p['id'])
                ->update([
                    'invoice_id'   => $invoiceId,
                    'reference_no' => $p['reference_number'] ?? null,
                ]);
        }
    }

    /**
     * Returns the next numeric sequence for the given year, based on
     * the highest existing INV-YYYY-##### in the invoices table.
     */
    private function nextInvoiceSeq(int $year): int
    {
        $prefix = 'INV-' . $year . '-';

        $row = $this->db->query(
            "SELECT MAX(CAST(SUBSTRING(invoice_no, ?) AS UNSIGNED)) AS max_seq
             FROM invoices
             WHERE invoice_no LIKE ?",
            [strlen($prefix) + 1, $prefix . '%']
        )->getRow();

        return ((int) ($row->max_seq ?? 0)) + 1;
    }
}