<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Online bookings always carry an email and phone, but an override booking
 * made at the front desk may not (a walk-in with no email). The override
 * saves a missing value as NULL, which NOT NULL columns rejected with a
 * database error, so the booking could not be made at all.
 */
class MakeAppointmentContactNullable extends Migration
{
    public function up()
    {
        $this->forge->modifyColumn('appointments', [
            'email' => ['name' => 'email', 'type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'phone' => ['name' => 'phone', 'type' => 'VARCHAR', 'constraint' => 20,  'null' => true],
        ]);
    }

    public function down()
    {
        $this->db->query("UPDATE appointments SET email = '' WHERE email IS NULL");
        $this->db->query("UPDATE appointments SET phone = '' WHERE phone IS NULL");

        $this->forge->modifyColumn('appointments', [
            'email' => ['name' => 'email', 'type' => 'VARCHAR', 'constraint' => 100, 'null' => false],
            'phone' => ['name' => 'phone', 'type' => 'VARCHAR', 'constraint' => 20,  'null' => false],
        ]);
    }
}
