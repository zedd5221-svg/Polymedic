<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/*
 * Same-role patient handoffs.
 *
 * A med tech (or radiologist) asks a colleague of the same role to take
 * over one or more of their active requests. Nothing moves until the
 * colleague accepts.
 *
 * patient_transfer_items.pending_request_id holds the request id while
 * the transfer is pending and NULL afterwards. Its unique index is what
 * stops one request from sitting in two pending transfers at once.
 *
 * notifications.user_id lets a notification go to one user instead of
 * every user of a role (NULL = the whole role, as before).
 */
class CreatePatientTransfers extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('patient_transfers')) {
            $this->forge->addField([
                'id'             => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'type'           => ['type' => 'ENUM', 'constraint' => ['lab', 'xray']],
                'from_user_id'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'from_user_name' => ['type' => 'VARCHAR', 'constraint' => 100],
                'to_user_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'to_user_name'   => ['type' => 'VARCHAR', 'constraint' => 100],
                'reason'         => ['type' => 'TEXT'],
                'status'         => ['type' => 'ENUM', 'constraint' => ['pending', 'accepted', 'declined', 'cancelled'], 'default' => 'pending'],
                'responded_at'   => ['type' => 'DATETIME', 'null' => true],
                'created_at'     => ['type' => 'DATETIME', 'null' => true],
                'updated_at'     => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey(['to_user_id', 'status']);
            $this->forge->addKey(['from_user_id', 'status']);
            $this->forge->createTable('patient_transfers', true, ['ENGINE' => 'InnoDB']);
        }

        if (! $this->db->tableExists('patient_transfer_items')) {
            $this->forge->addField([
                'id'                 => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'transfer_id'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'request_id'         => ['type' => 'INT', 'constraint' => 11],
                'pending_request_id' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
                'patient_name'       => ['type' => 'VARCHAR', 'constraint' => 100],
                'reference_number'   => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
                // moved / skipped once the transfer is accepted
                'outcome'            => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey('transfer_id');
            $this->forge->addKey('request_id');
            $this->forge->addUniqueKey('pending_request_id');
            $this->forge->createTable('patient_transfer_items', true, ['ENGINE' => 'InnoDB']);
        }

        if (! $this->db->fieldExists('user_id', 'notifications')) {
            $this->forge->addColumn('notifications', [
                'user_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'after' => 'user_role'],
            ]);
            $this->db->query('ALTER TABLE `notifications` ADD INDEX `idx_notifications_user_id` (`user_id`)');
        }
    }

    public function down()
    {
        $this->forge->dropTable('patient_transfer_items', true);
        $this->forge->dropTable('patient_transfers', true);
        if ($this->db->fieldExists('user_id', 'notifications')) {
            $this->forge->dropColumn('notifications', 'user_id');
        }
    }
}
