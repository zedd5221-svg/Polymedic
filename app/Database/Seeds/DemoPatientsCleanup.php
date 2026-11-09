<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Removes exactly the rows and X-ray files DemoPatientsSeeder added, using
 * its manifest. Rows created later by the app (e.g. notifications) that point
 * at seeded requests are not touched.
 *
 *   php spark db:seed DemoPatientsCleanup
 */
class DemoPatientsCleanup extends Seeder
{
    public function run()
    {
        $file = DemoPatientsSeeder::MANIFEST;
        if (!is_file($file)) {
            echo 'Nothing to clean: no manifest at ' . $file . PHP_EOL;
            return;
        }
        $m = json_decode((string) file_get_contents($file), true) ?: [];

        // Children first.
        $order = ['lab_results', 'invoice_items', 'payments', 'invoices', 'diagnostic_requests', 'appointments', 'patients'];

        $this->db->transException(true)->transStart();
        foreach ($order as $table) {
            foreach (array_chunk($m[$table] ?? [], 500) as $ids) {
                $this->db->table($table)->whereIn('id', $ids)->delete();
            }
            echo str_pad($table, 22) . count($m[$table] ?? []) . PHP_EOL;
        }
        $this->db->transComplete();

        foreach ($m['files'] ?? [] as $rel) {
            $path = FCPATH . $rel;
            if (str_starts_with($rel, 'uploads/xray/') && is_file($path)) {
                unlink($path);
            }
        }
        echo str_pad('files', 22) . count($m['files'] ?? []) . PHP_EOL;

        unlink($file);
    }
}
