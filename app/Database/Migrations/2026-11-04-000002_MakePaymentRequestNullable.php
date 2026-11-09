<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * An invoice created on the Billing page does not have to belong to a
 * diagnostic request. Its payments then have no request either, but
 * payments.request_id was NOT NULL with a foreign key to
 * diagnostic_requests, so every such payment was rejected
 * ("Payment could not be recorded"). NULL now means "no request";
 * the foreign key still checks every real id.
 */
class MakePaymentRequestNullable extends Migration
{
    public function up()
    {
        $this->forge->modifyColumn('payments', [
            'request_id'   => ['name' => 'request_id', 'type' => 'INT', 'constraint' => 11, 'null' => true],
            'request_type' => ['name' => 'request_type', 'type' => 'ENUM', 'constraint' => ['lab', 'xray'], 'null' => true],
        ]);
    }

    public function down()
    {
        // Rows without a request cannot satisfy the old NOT NULL foreign key, so down() refuses them.
        $orphans = $this->db->table('payments')->where('request_id IS NULL')->countAllResults();
        if ($orphans > 0) {
            throw new \RuntimeException($orphans . ' payment(s) have no diagnostic request; they would break the NOT NULL foreign key.');
        }

        $this->forge->modifyColumn('payments', [
            'request_id'   => ['name' => 'request_id', 'type' => 'INT', 'constraint' => 11, 'null' => false],
            'request_type' => ['name' => 'request_type', 'type' => 'ENUM', 'constraint' => ['lab', 'xray'], 'null' => false],
        ]);
    }
}
