<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Override bookings skip the slot capacity check, so each one records why
 * it was made and who made it.
 */
class AddOverrideAuditToAppointments extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('appointments')) {
            return;
        }

        $fields = [];
        if (!$this->db->fieldExists('override_reason', 'appointments')) {
            $fields['override_reason'] = [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'is_override',
            ];
        }
        if (!$this->db->fieldExists('override_by', 'appointments')) {
            $fields['override_by'] = [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
                'after'      => 'override_reason',
            ];
        }
        if ($fields !== []) {
            $this->forge->addColumn('appointments', $fields);
        }
    }

    public function down()
    {
        foreach (['override_by', 'override_reason'] as $f) {
            if ($this->db->tableExists('appointments') && $this->db->fieldExists($f, 'appointments')) {
                $this->forge->dropColumn('appointments', $f);
            }
        }
    }
}
