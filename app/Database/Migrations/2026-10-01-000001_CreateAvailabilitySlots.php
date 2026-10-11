<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAvailabilitySlots extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('availability_slots')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'slot_date' => [
                'type' => 'DATE',
            ],
            'slot_time' => [
                'type'       => 'VARCHAR',
                'constraint' => 10,
            ],
            'department' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
            ],
            'capacity' => [
                'type'       => 'TINYINT',
                'constraint' => 3,
                'unsigned'   => true,
                'default'    => 1,
            ],
            'is_blocked' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'blocked_reason' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['slot_date', 'slot_time', 'department'], 'uniq_slot');
        $this->forge->addKey(['slot_date', 'department'], false, false, 'idx_date_dept');
        $this->forge->createTable('availability_slots', true);
    }

    public function down()
    {
        if ($this->db->tableExists('availability_slots')) {
            $this->forge->dropTable('availability_slots', true);
        }
    }
}