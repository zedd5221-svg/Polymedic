<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/*
 * Who the front desk sent a diagnostic request to.
 *
 * Start now assigns the request to one med tech / radiologist. The
 * assignment is kept apart from med_tech_id / radiologist_id, which
 * record who actually worked the request and are what marks it as
 * "touched" in the department views.
 */
class AddAssignmentToDiagnosticRequests extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('assigned_to_id', 'diagnostic_requests')) {
            $this->forge->addColumn('diagnostic_requests', [
                'assigned_to_id'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'after' => 'status'],
                'assigned_to_name' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true, 'after' => 'assigned_to_id'],
                'assigned_at'      => ['type' => 'DATETIME', 'null' => true, 'after' => 'assigned_to_name'],
            ]);
            $this->db->query('ALTER TABLE `diagnostic_requests` ADD INDEX `idx_diagnostic_requests_assigned_to_id` (`assigned_to_id`)');
        }
    }

    public function down()
    {
        foreach (['assigned_at', 'assigned_to_name', 'assigned_to_id'] as $column) {
            if ($this->db->fieldExists($column, 'diagnostic_requests')) {
                $this->forge->dropColumn('diagnostic_requests', $column);
            }
        }
    }
}
