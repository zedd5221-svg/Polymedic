<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateClinicSettings extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('clinic_settings')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'setting_key' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                ],
                'setting_value' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);

            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey('setting_key', 'uniq_setting_key');
            $this->forge->createTable('clinic_settings', true);
        }

        // Seed defaults only when the key is not already present.
        $defaults = [
            'clinic_open_time'   => '08:00',
            'clinic_close_time'  => '17:00',
            'slot_interval'      => '60',
            'lunch_start'        => '12:00',
            'lunch_end'          => '13:00',
            'closed_weekdays'    => json_encode([0]), // 0=Sunday
            'holidays'           => json_encode([]),
            'booking_horizon'    => '90',
        ];

        $builder = $this->db->table('clinic_settings');
        foreach ($defaults as $key => $value) {
            $exists = $builder->where('setting_key', $key)->countAllResults();
            if ($exists === 0) {
                $this->db->table('clinic_settings')->insert([
                    'setting_key'   => $key,
                    'setting_value' => $value,
                    'created_at'    => date('Y-m-d H:i:s'),
                    'updated_at'    => date('Y-m-d H:i:s'),
                ]);
            }
        }
    }

    public function down()
    {
        if ($this->db->tableExists('clinic_settings')) {
            $this->forge->dropTable('clinic_settings', true);
        }
    }
}