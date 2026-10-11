<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddIsOverrideToAppointments extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('appointments')) {
            return;
        }

        if (!$this->db->fieldExists('is_override', 'appointments')) {
            $this->forge->addColumn('appointments', [
                'is_override' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 0,
                    'after'      => 'status',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->tableExists('appointments')
            && $this->db->fieldExists('is_override', 'appointments')) {
            $this->forge->dropColumn('appointments', 'is_override');
        }
    }
}