<?php

namespace App\Models;

use CodeIgniter\Model;

class AvailabilityModel extends Model
{
    protected $table          = 'availability_slots';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useTimestamps  = true;
    protected $createdField   = 'created_at';
    protected $updatedField   = 'updated_at';

    protected $allowedFields = [
        'slot_date',
        'slot_time',
        'department',
        'capacity',
        'is_blocked',
        'blocked_reason',
    ];

    /** Appointment statuses that DO count against capacity. */
    private const ACTIVE_STATUSES = ['pending', 'approved', 'completed', 'late'];

    private ?SettingsModel $settings = null;

    public function __construct()
    {
        parent::__construct();
        $this->settings = new SettingsModel();
    }

    /* ============================================================
       CALENDAR PRIMITIVES
       ============================================================ */

    public function isWorkingDay(string $date): bool
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return false;
        }

        if (in_array($date, $this->settings->holidays(), true)) {
            return false;
        }

        $dow = (int) date('w', strtotime($date));
        if (in_array($dow, $this->settings->closedWeekdays(), true)) {
            return false;
        }

        return true;
    }

    public function isWithinHorizon(string $date): bool
    {
        $today    = date('Y-m-d');
        $lastDay  = date('Y-m-d', strtotime('+' . $this->settings->bookingHorizon() . ' days'));

        return $date >= $today && $date <= $lastDay;
    }

    /**
     * Every clock time the clinic is open for the configured interval,
     * lunch break excluded.
     *
     * @return string[] e.g. ['08:00','09:00',...,'11:00','13:00',...,'16:00']
     */
    public function generateSlotTimes(): array
    {
        $open   = $this->settings->openTime();
        $close  = $this->settings->closeTime();
        $step   = $this->settings->slotInterval();
        $lStart = $this->settings->lunchStart();
        $lEnd   = $this->settings->lunchEnd();

        $times   = [];
        $cursor  = strtotime($open);
        $endTs   = strtotime($close);
        $lStartTs = strtotime($lStart);
        $lEndTs   = strtotime($lEnd);

        while ($cursor < $endTs) {
            // Lunch is skipped entirely.
            if (!($cursor >= $lStartTs && $cursor < $lEndTs)) {
                $times[] = date('H:i', $cursor);
            }
            $cursor += $step * 60;
        }

        return $times;
    }

    /* ============================================================
       CAPACITY / BOOKING
       ============================================================ */

    /**
     * Departments a booking of this service type consumes.
     *
     * @return string[] e.g. ['lab'], ['xray'], or ['lab','xray']
     */
    public function departmentsFor(string $serviceType): array
    {
        $type = strtolower(trim($serviceType));
        if ($type === 'both')   { return ['lab', 'xray']; }
        if ($type === 'xray')   { return ['xray']; }
        return ['lab']; // default = laboratory
    }

    /**
     * Count the appointments actually occupying a slot for a department.
     * A 'both' booking consumes one lab slot AND one xray slot, so it
     * is counted for both departments.
     */
    public function countActiveBookings(string $date, string $time, string $department): int
    {
        $db = db_connect();

        if ($department === 'lab') {
            $rows = $db->table('appointments')
                ->select('service_type')
                ->where('appointment_date', $date)
                ->where('appointment_time', $time)
                ->whereIn('status', self::ACTIVE_STATUSES)
                ->whereIn('service_type', ['laboratory', 'lab', 'both'])
                ->get()->getResultArray();
        } elseif ($department === 'xray') {
            $rows = $db->table('appointments')
                ->select('service_type')
                ->where('appointment_date', $date)
                ->where('appointment_time', $time)
                ->whereIn('status', self::ACTIVE_STATUSES)
                ->whereIn('service_type', ['xray', 'both'])
                ->get()->getResultArray();
        } else {
            return 0;
        }

        return count($rows);
    }

    /**
     * Slot rows for one date + department, joined with live capacity.
     *
     * @return array<int,array{
     *     time:string, label:string, capacity:int, booked:int,
     *     remaining:int, blocked:bool, reason:?string, available:bool
     * }>
     */
    public function slotsFor(string $date, string $department): array
    {
        $times    = $this->generateSlotTimes();
        $override = $this->slotOverridesFor($date, $department);
        $result   = [];

        foreach ($times as $time) {
            $overrideRow = $override[$time] ?? null;
            $capacity    = $overrideRow ? (int) $overrideRow['capacity'] : 1;
            $blocked     = $overrideRow ? ((int) $overrideRow['is_blocked'] === 1) : false;
            $reason      = $overrideRow['blocked_reason'] ?? null;

            $booked = $this->countActiveBookings($date, $time, $department);

            $available = !$blocked && $booked < $capacity;

            $result[] = [
                'time'      => $time,
                'label'     => date('g:i A', strtotime($time)),
                'capacity'  => $capacity,
                'booked'    => $booked,
                'remaining' => max(0, $capacity - $booked),
                'blocked'   => $blocked,
                'reason'    => $reason,
                'available' => $available,
            ];
        }

        return $result;
    }

    /**
     * The slot rows already written to availability_slots for this
     * date+department, keyed by time.
     */
    private function slotOverridesFor(string $date, string $department): array
    {
        $rows = $this->where('slot_date', $date)
                     ->where('department', $department)
                     ->findAll();

        $out = [];
        foreach ($rows as $row) {
            $out[$row['slot_time']] = $row;
        }
        return $out;
    }

    /**
     * Is a specific slot bookable? Used as the server-side re-check.
     */
    public function isBookable(string $date, string $time, string $serviceType): bool
    {
        if (!$this->isWorkingDay($date)) {
            return false;
        }
        if (!$this->isWithinHorizon($date)) {
            return false;
        }

        $depts = $this->departmentsFor($serviceType);
        foreach ($depts as $dept) {
            $row = $this->slotOverride($date, $time, $dept);
            $capacity = $row ? (int) $row['capacity'] : 1;
            $blocked  = $row ? ((int) $row['is_blocked'] === 1) : false;

            if ($blocked) {
                return false;
            }
            if ($this->countActiveBookings($date, $time, $dept) >= $capacity) {
                return false;
            }
        }

        return true;
    }

    private function slotOverride(string $date, string $time, string $department): ?array
    {
        return $this->where('slot_date', $date)
                    ->where('slot_time', $time)
                    ->where('department', $department)
                    ->first();
    }

    /* ============================================================
       ADMIN — BLOCK/UNBLOCK
       ============================================================ */

    public function blockSlot(string $date, string $time, string $department, ?string $reason = null): bool
    {
        $row = $this->slotOverride($date, $time, $department);

        if ($row) {
            return (bool) $this->update($row['id'], [
                'is_blocked'     => 1,
                'blocked_reason' => $reason,
            ]);
        }

        return (bool) $this->insert([
            'slot_date'      => $date,
            'slot_time'      => $time,
            'department'     => $department,
            'capacity'       => 1,
            'is_blocked'     => 1,
            'blocked_reason' => $reason,
        ]);
    }

    public function unblockSlot(string $date, string $time, string $department): bool
    {
        $row = $this->slotOverride($date, $time, $department);
        if (!$row) {
            return true;
        }
        return (bool) $this->update($row['id'], [
            'is_blocked'     => 0,
            'blocked_reason' => null,
        ]);
    }

    /* ============================================================
       PREVIEW (Admin page)
       ============================================================ */

    /**
     * A compact 14-day preview for the admin UI.
     *
     * @return array<int,array{date:string,label:string,working:bool,holiday:bool,lab_total:int,xray_total:int,lab_booked:int,xray_booked:int}>
     */
    public function previewDays(int $days = 14): array
    {
        $out     = [];
        $holiday = $this->settings->holidays();
        $times   = $this->generateSlotTimes();
        $slotN   = count($times);

        for ($i = 0; $i < $days; $i++) {
            $date = date('Y-m-d', strtotime('+' . $i . ' days'));
            $working = $this->isWorkingDay($date);

            $labBooked = 0;
            $xrayBooked = 0;
            if ($working) {
                foreach ($times as $t) {
                    $labBooked  += $this->countActiveBookings($date, $t, 'lab');
                    $xrayBooked += $this->countActiveBookings($date, $t, 'xray');
                }
            }

            $out[] = [
                'date'        => $date,
                'label'       => date('D, M j', strtotime($date)),
                'working'     => $working,
                'holiday'     => in_array($date, $holiday, true),
                'lab_total'   => $working ? $slotN : 0,
                'xray_total'  => $working ? $slotN : 0,
                'lab_booked'  => $labBooked,
                'xray_booked' => $xrayBooked,
            ];
        }

        return $out;
    }
}