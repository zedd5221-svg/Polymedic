<?php

namespace App\Models;

use CodeIgniter\Model;

class SettingsModel extends Model
{
    protected $table            = 'clinic_settings';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';

    protected $allowedFields = [
        'setting_key',
        'setting_value',
    ];

    /** In-request cache so we don't hammer the DB. */
    private static $cache = null;

    /**
     * Load every setting once per request.
     */
    public function all(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $rows = $this->findAll();
        $out  = [];
        foreach ($rows as $row) {
            $out[$row['setting_key']] = $row['setting_value'];
        }

        self::$cache = $out;
        return $out;
    }

    public function get(string $key, $default = null)
    {
        $all = $this->all();
        return array_key_exists($key, $all) ? $all[$key] : $default;
    }

    /**
     * NOTE: named saveSetting(), not set() — CI4's base Model already
     * declares set($key, $value = null, $escape = null), and overriding
     * it with an incompatible signature triggers a fatal ErrorException
     * at class load.
     */
    public function saveSetting(string $key, $value): bool
    {
        $exists = $this->where('setting_key', $key)->first();

        if ($exists) {
            $this->update($exists['id'], ['setting_value' => (string) $value]);
        } else {
            $this->insert([
                'setting_key'   => $key,
                'setting_value' => (string) $value,
            ]);
        }

        self::$cache = null; // invalidate
        return true;
    }

    public function saveMany(array $pairs): bool
    {
        foreach ($pairs as $key => $value) {
            $this->saveSetting((string) $key, $value);
        }
        return true;
    }

    /* -------- typed helpers -------- */

    public function openTime(): string  { return (string) ($this->get('clinic_open_time',  '08:00')); }
    public function closeTime(): string { return (string) ($this->get('clinic_close_time', '17:00')); }
    public function lunchStart(): string { return (string) ($this->get('lunch_start', '12:00')); }
    public function lunchEnd(): string   { return (string) ($this->get('lunch_end',   '13:00')); }

    public function slotInterval(): int
    {
        $allowed = [15, 20, 30, 45, 60, 90, 120];
        $v = (int) $this->get('slot_interval', 60);
        return in_array($v, $allowed, true) ? $v : 60;
    }

    public function bookingHorizon(): int
    {
        $v = (int) $this->get('booking_horizon', 90);
        return $v > 0 ? $v : 90;
    }

    /** Weekday numbers (0=Sun ... 6=Sat) the clinic is closed on. */
    public function closedWeekdays(): array
    {
        $raw = $this->get('closed_weekdays', '[]');
        $decoded = is_string($raw) ? json_decode($raw, true) : $raw;
        if (!is_array($decoded)) {
            return [];
        }
        return array_values(array_unique(array_map('intval', $decoded)));
    }

    /** List of YYYY-MM-DD holiday strings. */
    public function holidays(): array
    {
        $raw = $this->get('holidays', '[]');
        $decoded = is_string($raw) ? json_decode($raw, true) : $raw;
        if (!is_array($decoded)) {
            return [];
        }
        $clean = [];
        foreach ($decoded as $d) {
            $d = trim((string) $d);
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) {
                $clean[] = $d;
            }
        }
        return array_values(array_unique($clean));
    }
}