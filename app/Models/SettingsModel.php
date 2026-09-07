<?php

namespace App\Models;

use CodeIgniter\Model;
use Config\Database;

class SettingsModel extends Model
{
    protected $table            = 'settings';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'setting_key',
        'setting_value',
        'updated_at'
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function __construct()
    {
        parent::__construct();
        $this->ensureTableExists();
    }

    /**
     * Ensure the settings table exists automatically.
     */
    public function ensureTableExists()
    {
        try {
            $db = Database::connect();
            if (!$db->tableExists('settings')) {
                $forge = Database::forge();
                $forge->addField([
                    'id' => [
                        'type'           => 'INT',
                        'constraint'     => 11,
                        'unsigned'       => true,
                        'auto_increment' => true,
                    ],
                    'setting_key' => [
                        'type'       => 'VARCHAR',
                        'constraint' => 100,
                        'unique'     => true,
                    ],
                    'setting_value' => [
                        'type' => 'TEXT',
                        'null' => true,
                    ],
                    'created_at' => [
                        'type' => 'DATETIME',
                        'null' => true,
                    ],
                    'updated_at' => [
                        'type' => 'DATETIME',
                        'null' => true,
                    ],
                ]);
                $forge->addKey('id', true);
                $forge->createTable('settings', true);

                // Insert default system settings
                $defaults = [
                    'ip_restriction_enabled' => '0',
                    'allowed_ips'            => '127.0.0.1, ::1',
                    'print_header_title'     => 'PolyMedic',
                    'print_header_subtitle'  => 'Diagnostic & Laboratory Center',
                    'print_contact_info'     => 'Gov. Gutierrez Ave, Cotabato City 9600 | Tel: (064) 123-4567',
                    'print_accent_color'     => '#0148ca',
                    'print_signature_title'  => 'Radiologist / Attending Physician',
                    'print_footer_note'      => 'This is a computer-generated medical report. PolyMedic Diagnostic Center.',
                    'print_layout_style'     => 'modern',
                ];

                foreach ($defaults as $key => $val) {
                    $db->table('settings')->insert([
                        'setting_key'   => $key,
                        'setting_value' => $val,
                        'created_at'    => date('Y-m-d H:i:s'),
                        'updated_at'    => date('Y-m-d H:i:s'),
                    ]);
                }
            }
        } catch (\Exception $e) {
            log_message('error', 'Settings table setup error: ' . $e->getMessage());
        }
    }

    /**
     * Get a setting by key with fallback default.
     */
    public function getSetting($key, $default = null)
    {
        $setting = $this->where('setting_key', $key)->first();
        if ($setting) {
            return $setting['setting_value'];
        }
        return $default;
    }

    /**
     * Set/update a setting by key.
     */
    public function setSetting($key, $value)
    {
        $existing = $this->where('setting_key', $key)->first();
        if ($existing) {
            return $this->update($existing['id'], [
                'setting_value' => $value,
                'updated_at'    => date('Y-m-d H:i:s')
            ]);
        } else {
            return $this->insert([
                'setting_key'   => $key,
                'setting_value' => $value,
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s')
            ]);
        }
    }

    /**
     * Get all settings as associative key-value array.
     */
    public function getAllSettings(): array
    {
        $rows = $this->findAll();
        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        return $settings;
    }

    /**
     * Check if a given IP address is allowed based on IP restriction settings.
     */
    public function isIpAllowed(string $clientIp): bool
    {
        $enabled = $this->getSetting('ip_restriction_enabled', '0');
        if ($enabled !== '1') {
            return true; // Restriction disabled -> allow all
        }

        $allowedStr = $this->getSetting('allowed_ips', '');
        if (empty(trim($allowedStr))) {
            return true;
        }

        $allowedList = array_map('trim', explode(',', $allowedStr));
        $clientIp = trim($clientIp);

        foreach ($allowedList as $allowed) {
            if (empty($allowed)) continue;
            
            // Exact match
            if ($clientIp === $allowed) {
                return true;
            }
            
            // Wildcard matching (e.g. 192.168.1.*)
            if (strpos($allowed, '*') !== false) {
                $pattern = '/^' . str_replace('\*', '.*', preg_quote($allowed, '/')) . '$/';
                if (preg_match($pattern, $clientIp)) {
                    return true;
                }
            }

            // CIDR matching (e.g. 192.168.1.0/24)
            if (strpos($allowed, '/') !== false) {
                if ($this->ipInCidr($clientIp, $allowed)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if IPv4 address is within CIDR subnet range.
     */
    private function ipInCidr(string $ip, string $cidr): bool
    {
        list($subnet, $bits) = explode('/', $cidr);
        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);

        if ($ipLong === false || $subnetLong === false) {
            return false;
        }

        $mask = -1 << (32 - (int)$bits);
        $subnetLong &= $mask;

        return ($ipLong & $mask) == $subnetLong;
    }
}
