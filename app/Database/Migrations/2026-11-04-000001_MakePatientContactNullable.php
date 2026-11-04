<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Walk-in patients often have no email or phone, and every walk-in form
 * saves a missing value as NULL. With NOT NULL columns that insert failed,
 * so the receptionist saw "Could not assign a patient code".
 */
class MakePatientContactNullable extends Migration
{
    public function up()
    {
        $this->forge->modifyColumn('patients', [
            'email' => ['name' => 'email', 'type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'phone' => ['name' => 'phone', 'type' => 'VARCHAR', 'constraint' => 20,  'null' => true],
        ]);
    }

    public function down()
    {
        $this->db->query("UPDATE patients SET email = '' WHERE email IS NULL");
        $this->db->query("UPDATE patients SET phone = '' WHERE phone IS NULL");

        $this->forge->modifyColumn('patients', [
            'email' => ['name' => 'email', 'type' => 'VARCHAR', 'constraint' => 100, 'null' => false],
            'phone' => ['name' => 'phone', 'type' => 'VARCHAR', 'constraint' => 20,  'null' => false],
        ]);
    }
}
