<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Appointments no longer have a "completed" status: approval is the last
 * step of an appointment, and the work itself is tracked on the diagnostic
 * requests (draft / completed / released). The "Mark completed" action was
 * removed for both the receptionist and the admin, so existing completed
 * appointments are folded back into "approved".
 *
 * The 54 rows converted on 2026-11-09 were backed up first to
 * writable/backups/appointments_completed_*_2026-11-09.*
 */
class RemoveCompletedAppointmentStatus extends Migration
{
    /** IDs that were 'completed' when this migration was written; used by down(). */
    private const CONVERTED_IDS = [
        423, 424, 426, 427, 429, 430, 432, 434, 435, 436, 438, 440, 441, 442, 444, 445, 446, 448,
        449, 450, 451, 452, 453, 454, 455, 456, 457, 459, 460, 461, 462, 463, 465, 466, 467, 468,
        469, 470, 471, 472, 473, 475, 477, 478, 480, 481, 484, 485, 487, 488, 489, 490, 491, 495,
    ];

    public function up()
    {
        $this->db->table('appointments')
                 ->where('status', 'completed')
                 ->update(['status' => 'approved']);
    }

    public function down()
    {
        $this->db->table('appointments')
                 ->whereIn('id', self::CONVERTED_IDS)
                 ->where('status', 'approved')
                 ->update(['status' => 'completed']);
    }
}
