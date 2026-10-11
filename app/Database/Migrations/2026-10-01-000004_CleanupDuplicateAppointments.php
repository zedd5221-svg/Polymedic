<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CleanupDuplicateAppointments extends Migration
{
    /**
     * Keeps the OLDEST appointment per (date, time, service_type) that is
     * NOT cancelled, and cancels any later duplicates. This makes the
     * existing data conform to the new "1 patient per slot per department"
     * rule without deleting history.
     */
    public function up()
    {
        if (!$this->db->tableExists('appointments')) {
            return;
        }

        $rows = $this->db->table('appointments')
            ->select('id, appointment_date, appointment_time, service_type, status, created_at')
            ->whereNotIn('status', ['cancelled'])
            ->orderBy('appointment_date', 'ASC')
            ->orderBy('appointment_time', 'ASC')
            ->orderBy('created_at', 'ASC')
            ->get()
            ->getResultArray();

        $seen   = [];
        $toKill = [];

        foreach ($rows as $row) {
            $key = $row['appointment_date'] . '|' . $row['appointment_time'] . '|' . $row['service_type'];
            if (isset($seen[$key])) {
                $toKill[] = (int) $row['id'];
            } else {
                $seen[$key] = (int) $row['id'];
            }
        }

        if (!empty($toKill)) {
            $this->db->table('appointments')
                ->whereIn('id', $toKill)
                ->update([
                    'status'     => 'cancelled',
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

            log_message('info', 'CleanupDuplicateAppointments cancelled ' . count($toKill) . ' duplicate appointment(s).');
        }
    }

    public function down()
    {
        // Intentionally non-reversible — the original state is unknown.
    }
}