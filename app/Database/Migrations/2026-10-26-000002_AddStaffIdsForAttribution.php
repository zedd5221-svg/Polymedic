<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/*
 * Credit work to staff by user id, not only by the name text.
 *
 * The name columns (received_by, created_by, med_tech_name,
 * radiologist_name) stay as the printed label. The new *_id columns are
 * what My Profile counts on, so a renamed user keeps their history.
 * Existing rows are backfilled where the stored name matches exactly
 * one user.
 */
class AddStaffIdsForAttribution extends Migration
{
    private const COLUMNS = [
        // table => [new id column, existing name column, after]
        ['invoices',            'created_by_id',  'created_by'],
        ['payments',            'received_by_id', 'received_by'],
        ['diagnostic_requests', 'med_tech_id',    'med_tech_name'],
        ['diagnostic_requests', 'radiologist_id', 'radiologist_name'],
    ];

    public function up()
    {
        foreach (self::COLUMNS as [$table, $idCol, $nameCol]) {
            if (! $this->db->fieldExists($idCol, $table)) {
                $this->forge->addColumn($table, [
                    $idCol => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'after' => $nameCol],
                ]);
                $this->db->query("ALTER TABLE `{$table}` ADD INDEX `idx_{$table}_{$idCol}` (`{$idCol}`)");
            }

            // Backfill only unambiguous names (exactly one user has it).
            $this->db->query("
                UPDATE `{$table}` t
                JOIN (
                    SELECT full_name, MIN(id) AS id
                    FROM users
                    GROUP BY full_name
                    HAVING COUNT(*) = 1
                ) u ON u.full_name = t.`{$nameCol}`
                SET t.`{$idCol}` = u.id
                WHERE t.`{$idCol}` IS NULL
            ");
        }
    }

    public function down()
    {
        foreach (self::COLUMNS as [$table, $idCol]) {
            if ($this->db->fieldExists($idCol, $table)) {
                $this->forge->dropColumn($table, $idCol);
            }
        }
    }
}
