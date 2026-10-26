<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/*
 * Personal profile fields for staff accounts.
 *
 * Every column is nullable so existing users keep working untouched;
 * each person fills in their own details from the My Profile page.
 */
class AddProfileFieldsToUsers extends Migration
{
    public function up()
    {
        $this->forge->addColumn('users', [
            'contact_number' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
                'after'      => 'prc_license',
            ],
            'gender' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
                'after'      => 'contact_number',
            ],
            'birthdate' => [
                'type'  => 'DATE',
                'null'  => true,
                'after' => 'gender',
            ],
            'address' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'birthdate',
            ],
            'bio' => [
                'type'  => 'TEXT',
                'null'  => true,
                'after' => 'address',
            ],
            'avatar' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'bio',
            ],
            'last_login_at' => [
                'type'  => 'DATETIME',
                'null'  => true,
                'after' => 'status',
            ],
            'password_changed_at' => [
                'type'  => 'DATETIME',
                'null'  => true,
                'after' => 'last_login_at',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('users', [
            'contact_number', 'gender', 'birthdate', 'address', 'bio',
            'avatar', 'last_login_at', 'password_changed_at',
        ]);
    }
}
