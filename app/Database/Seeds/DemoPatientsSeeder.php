<?php

namespace App\Database\Seeds;

use App\Models\LabResultModel;
use CodeIgniter\Database\Seeder;
use RuntimeException;

/**
 * Test data: 100 patients (walk-in + online) with visit history spread over
 * the past ~6 months, following the real workflow:
 *
 *   booking/walk-in -> pending request -> Start (assign + payment + invoice)
 *   -> picked up -> draft -> completed -> released
 *
 * Older visits are released with realistic lab values and X-ray reports;
 * recent and today's visits are spread across every in-between status.
 *
 *   php spark db:seed DemoPatientsSeeder
 *   php spark db:seed DemoPatientsCleanup   (removes exactly what was added)
 *
 * Every inserted id is written to writable/seed/demo_patients_manifest.json.
 * Sample X-ray films are read from writable/seed/xray_samples/*.jpg.
 */
class DemoPatientsSeeder extends Seeder
{
    public const MANIFEST    = WRITEPATH . 'seed/demo_patients_manifest.json';
    public const SAMPLES_DIR = WRITEPATH . 'seed/xray_samples/';

    private const PATIENTS = 100;
    private const SEED     = 20261105;

    private const RECEPTION = ['id' => 5, 'name' => 'Receptionist User'];
    private const MED_TECHS = [['id' => 14, 'name' => 'Raffsan T. Sacron'], ['id' => 53, 'name' => 'Ryumen Sukuna']];
    private const RADIOLOGISTS = [['id' => 10, 'name' => 'Sacron Rampak'], ['id' => 54, 'name' => 'Gojo Saturo']];

    private array $manifest = [
        'patients' => [], 'appointments' => [], 'diagnostic_requests' => [], 'lab_results' => [],
        'payments' => [], 'invoices' => [], 'invoice_items' => [], 'files' => [],
    ];
    private array $prices = [];
    private array $tests;
    private int $invoiceSeq;
    private array $usedRefs = [];
    private array $pendingFiles = [];
    private string $today;

    public function run()
    {
        if (is_file(self::MANIFEST)) {
            throw new RuntimeException('Demo patients are already seeded. Run DemoPatientsCleanup first.');
        }
        $samples = glob(self::SAMPLES_DIR . '*.jpg') ?: [];
        sort($samples);
        if (count($samples) < 4) {
            throw new RuntimeException('Put the 4 sample X-ray films (1.jpg..4.jpg) in ' . self::SAMPLES_DIR);
        }

        mt_srand(self::SEED);
        $this->today = date('Y-m-d');
        $this->tests = (new LabResultModel())->getServiceTests();
        foreach ($this->db->table('services')->where('is_active', 1)->get()->getResultArray() as $s) {
            $this->prices[$s['service_name']] = ['charge' => (float) $s['charge'], 'category' => $s['category']];
        }
        foreach (['appointments', 'diagnostic_requests', 'payments'] as $t) {
            foreach ($this->db->table($t)->select('reference_number')->get()->getResultArray() as $r) {
                $this->usedRefs[$r['reference_number']] = true;
            }
        }
        $row = $this->db->query("SELECT MAX(CAST(SUBSTRING(invoice_no, 10) AS UNSIGNED)) AS m FROM invoices WHERE invoice_no LIKE ?", ['INV-' . date('Y') . '-%'])->getRow();
        $this->invoiceSeq = (int) ($row->m ?? 0);

        $patients = $this->buildPatients();

        $this->db->transException(true)->transStart();
        $codeRow = $this->db->query("SELECT MAX(CAST(SUBSTRING(patient_code, 8) AS UNSIGNED)) AS m FROM patients WHERE patient_code LIKE ?", ['PAT-' . date('y') . '-%'])->getRow();
        $codeSeq = (int) ($codeRow->m ?? 0);

        foreach ($patients as $p) {
            $p['code'] = sprintf('PAT-%s-%04d', date('y'), ++$codeSeq);
            $this->db->table('patients')->insert([
                'patient_code' => $p['code'],
                'full_name'    => $p['name'],
                'email'        => $p['email'],
                'phone'        => $p['phone'],
                'age'          => $p['age'],
                'gender'       => $p['gender'],
                'source'       => $p['source'],
                'address'      => $p['address'],
                'created_at'   => $p['visits'][0]['at'],
                'updated_at'   => $p['visits'][0]['at'],
            ]);
            $this->manifest['patients'][] = (int) $this->db->insertID();

            foreach ($p['visits'] as $visit) {
                $this->seedVisit($p, $visit, $samples);
            }
        }
        $this->db->transComplete();

        // Files only after the rows are committed.
        if (!is_dir(FCPATH . 'uploads/xray')) {
            mkdir(FCPATH . 'uploads/xray', 0775, true);
        }
        foreach ($this->pendingFiles as [$src, $rel]) {
            copy($src, FCPATH . $rel);
            $this->manifest['files'][] = $rel;
        }

        if (!is_dir(dirname(self::MANIFEST))) {
            mkdir(dirname(self::MANIFEST), 0775, true);
        }
        file_put_contents(self::MANIFEST, json_encode($this->manifest, JSON_PRETTY_PRINT));

        foreach ($this->manifest as $table => $ids) {
            echo str_pad($table, 22) . count($ids) . PHP_EOL;
        }
    }

    // =================================================================
    // PEOPLE + VISIT PLAN
    // =================================================================

    private function buildPatients(): array
    {
        $male = ['Juan', 'Jose', 'Mark', 'John Paul', 'Christian', 'Rafael', 'Miguel', 'Carlo', 'Paolo', 'Angelo', 'Ramon', 'Ernesto', 'Rodrigo', 'Benjamin', 'Gabriel', 'Nathaniel', 'Jerome', 'Kenneth', 'Ronaldo', 'Danilo', 'Eduardo', 'Francis', 'Lorenzo', 'Joshua', 'Emmanuel', 'Arnel', 'Rolando', 'Vicente', 'Alfredo', 'Reynaldo', 'Jericho', 'Aldrin', 'Noel', 'Marvin', 'Dominic', 'Teodoro', 'Isidro', 'Renato', 'Joel', 'Allan'];
        $female = ['Maria', 'Ana', 'Kristine', 'Angelica', 'Jasmine', 'Patricia', 'Camille', 'Andrea', 'Nicole', 'Bea', 'Rosario', 'Luzviminda', 'Teresita', 'Corazon', 'Erlinda', 'Marites', 'Jocelyn', 'Liza', 'Sheila', 'Rowena', 'Grace', 'Joy', 'Princess', 'Hannah', 'Sofia', 'Isabel', 'Clarissa', 'Lovely', 'Mylene', 'Charmaine', 'Rhea', 'Divina', 'Imelda', 'Leonora', 'Ma. Cristina', 'Janelle', 'Trisha', 'Abigail', 'Danica', 'Shiela Mae'];
        $last = ['Santos', 'Reyes', 'Cruz', 'Bautista', 'Ocampo', 'Garcia', 'Mendoza', 'Torres', 'Castillo', 'Flores', 'Villanueva', 'Ramos', 'Aquino', 'Salazar', 'Mercado', 'Dela Cruz', 'Gonzales', 'Lopez', 'Rivera', 'Domingo', 'Soriano', 'Valdez', 'Panganiban', 'Magbanua', 'Evangelista', 'De Guzman', 'Javier', 'Marquez', 'Corpuz', 'Dizon', 'Tolentino', 'Sarmiento', 'Pangilinan', 'Macaraeg', 'Lacson', 'Sison', 'Gatchalian', 'Abad', 'Buenaventura', 'Cabrera', 'Dimaculangan', 'Esguerra', 'Fajardo', 'Ilagan', 'Lagman', 'Manalastas', 'Nepomuceno', 'Quiambao', 'Robles', 'Umali', 'Yap', 'Tan', 'Lim', 'Sy', 'Ampatuan', 'Mangudadatu', 'Sinsuat', 'Tomawis', 'Pagayon', 'Labrador'];
        $places = ['Brgy. Poblacion', 'Brgy. San Isidro', 'Brgy. Santo Niño', 'Brgy. Mabini', 'Brgy. Rizal', 'Brgy. Bagumbayan', 'Brgy. Kalawag', 'Brgy. Lagao', 'Brgy. Dadiangas', 'Brgy. Tambler', 'Brgy. Calumpang', 'Brgy. Labangal'];
        $cities = ['General Santos City', 'Koronadal City', 'Tacurong City', 'Isulan, Sultan Kudarat', 'Polomolok, South Cotabato', 'Tupi, South Cotabato', 'Cotabato City', 'Kidapawan City'];
        $mail = ['gmail.com', 'gmail.com', 'gmail.com', 'yahoo.com', 'outlook.com'];

        $existing = array_flip(array_column($this->db->table('patients')->select('full_name')->get()->getResultArray(), 'full_name'));
        $people = [];
        $taken = [];
        while (count($people) < self::PATIENTS) {
            $gender = mt_rand(0, 1) ? 'Male' : 'Female';
            $first  = $this->pick($gender === 'Male' ? $male : $female);
            $middle = $this->pick($last);
            $sur    = $this->pick($last);
            if ($middle === $sur) {
                continue;
            }
            $name = $first . ' ' . $middle . ' ' . $sur;
            if (isset($taken[$name]) || isset($existing[$name])) {
                continue;
            }
            $taken[$name] = true;

            $ageBand = mt_rand(1, 100);
            $age = $ageBand <= 8 ? mt_rand(2, 12) : ($ageBand <= 20 ? mt_rand(13, 22) : ($ageBand <= 70 ? mt_rand(23, 50) : ($ageBand <= 90 ? mt_rand(51, 68) : mt_rand(69, 86))));
            $online = mt_rand(1, 100) <= 45;
            $slug = strtolower(preg_replace('/[^a-z]+/i', '.', $first . '.' . $sur));
            $email = ($online || mt_rand(1, 100) <= 35) ? trim($slug, '.') . mt_rand(1, 99) . '@' . $this->pick($mail) : null;

            $people[] = [
                'name'    => $name,
                'gender'  => $gender,
                'age'     => $age,
                'source'  => $online ? 'online' : 'walk-in',
                'email'   => $email,
                'phone'   => '09' . mt_rand(15, 99) . str_pad((string) mt_rand(0, 9999999), 7, '0', STR_PAD_LEFT),
                'address' => mt_rand(1, 100) <= 80 ? 'Purok ' . mt_rand(1, 9) . ', ' . $this->pick($places) . ', ' . $this->pick($cities) : null,
                // A stable health profile keeps repeat visits consistent.
                'profile' => [
                    'diabetic'     => $age > 35 && mt_rand(1, 100) <= 18,
                    'dyslipidemia' => $age > 30 && mt_rand(1, 100) <= 25,
                    'anemic'       => $gender === 'Female' && mt_rand(1, 100) <= 15,
                    'uti'          => mt_rand(1, 100) <= 10,
                    'renal'        => $age > 55 && mt_rand(1, 100) <= 15,
                    'ptb'          => $age > 18 && mt_rand(1, 100) <= 7,
                ],
            ];
        }

        // Visit plan: first visit within the last ~180 days, weighted to recent;
        // about a third of patients come back once or twice (history).
        // ~28% are walk-ins/bookings of the last few days (the live work queue);
        // the rest have history, and a fifth of those are back this week.
        $bookingsToday = 5;
        foreach ($people as &$p) {
            if (mt_rand(1, 100) <= 28) {
                $dates = [$this->recentDay()];
            } else {
                $daysAgo = 7 + (int) floor(173 * (mt_rand(0, 1000) / 1000) ** 1.4);
                $dates = [$daysAgo];
                if (mt_rand(1, 100) <= 33 && $daysAgo > 30) {
                    $next = 7 + (int) floor(($daysAgo - 7) * mt_rand(20, 70) / 100);
                    $dates[] = $next;
                    if (mt_rand(1, 100) <= 35 && $next > 20) {
                        $dates[] = 7 + mt_rand(0, (int) floor(($next - 7) / 2));
                    }
                }
                if (mt_rand(1, 100) <= 20) {
                    $dates[] = $this->recentDay();
                }
            }
            $p['visits'] = [];
            foreach ($dates as $i => $d) {
                $p['visits'][] = $this->planVisit($p, $d, $i > 0);
            }
            // Some online patients have an upcoming booking awaiting approval.
            // The first few are today so the receptionist has bookings to Approve / Cancel.
            if ($p['source'] === 'online' && mt_rand(1, 100) <= 20) {
                $p['visits'][] = $this->planVisit($p, $bookingsToday-- > 0 ? 0 : -mt_rand(1, 6), true, 'booking');
            }
        }
        unset($p);

        usort($people, fn($a, $b) => strcmp($a['visits'][0]['at'], $b['visits'][0]['at']));
        return $people;
    }

    /** 0 = today. Weighted so today's queue is the busiest. */
    private function recentDay(): int
    {
        return (int) $this->weighted(['0' => 50, '1' => 20, '2' => 15, '3' => 15]);
    }

    /** $daysAgo < 0 is a future date (an upcoming booking). */
    private function planVisit(array $p, int $daysAgo, bool $returning, ?string $forced = null): array
    {
        $date = date('Y-m-d', strtotime(($daysAgo >= 0 ? '-' : '+') . abs($daysAgo) . ' days', strtotime($this->today)));
        if (date('w', strtotime($date)) === '0') {                 // closed on Sundays
            $date = date('Y-m-d', strtotime($date . ($daysAgo > 0 ? ' -1 day' : ' +1 day')));
        }
        $minutes = mt_rand(16, 33) * 30;                              // 08:00 - 16:30 slots
        $at = $date . ' ' . sprintf('%02d:%02d:00', intdiv($minutes, 60), $minutes % 60);
        if ($date === $this->today && strtotime($at) > time() - 3600) {
            $at = $date . ' ' . date('H:i:s', strtotime('-' . mt_rand(90, 300) . ' minutes'));
            $at = max($at, $date . ' 08:00:00');
        }

        $kind = mt_rand(1, 100);
        $types = $kind <= 55 ? ['lab'] : ($kind <= 80 ? ['xray'] : ['lab', 'xray']);
        if ($returning && $p['profile']['diabetic']) {
            $types = in_array('lab', $types, true) ? $types : array_merge(['lab'], $types);
        }

        return [
            'date'     => $date,
            'at'       => $at,
            'daysAgo'  => $daysAgo,
            'types'    => $types,
            'lab'      => in_array('lab', $types, true) ? $this->pickLabServices($p, $returning) : [],
            'xray'     => in_array('xray', $types, true) ? $this->pickXrayServices($p) : [],
            'stage'    => $forced ?? $this->pickStage($daysAgo),
            'stat'     => mt_rand(1, 100) <= 8,
        ];
    }

    private function pickStage(int $daysAgo): string
    {
        $r = mt_rand(1, 100);
        if ($daysAgo >= 4) {
            return $r <= 93 ? 'released' : ($r <= 97 ? 'completed' : 'cancelled');
        }
        if ($daysAgo >= 1) {
            return $r <= 45 ? 'released' : ($r <= 65 ? 'completed' : ($r <= 77 ? 'draft' : ($r <= 89 ? 'processing' : 'started')));
        }
        return $r <= 30 ? 'pending' : ($r <= 52 ? 'started' : ($r <= 68 ? 'processing' : ($r <= 80 ? 'draft' : ($r <= 92 ? 'completed' : 'released'))));
    }

    private function pickLabServices(array $p, bool $returning): array
    {
        $common = ['Complete Blood Count (CBC)' => 30, 'Urinalysis' => 22, 'Glucose (RBS/FBS)' => 16, 'Cholesterol' => 10, 'Creatinine' => 8, 'Blood Uric Acid' => 7, 'Fecalysis' => 8, 'Blood Typing' => 6, 'HbA1c' => 5, 'HDL / LDL' => 6, 'Triglycerides' => 6, 'SGPT/ALT' => 5, 'SGOT/AST' => 4, 'Blood Urea Nitrogen' => 5, 'Hemoglobin (Hgb)' => 3, 'Platelet Count' => 4, 'ESR' => 3, 'Sodium (Na)' => 3, 'Potassium (K)' => 3, 'HBsAg (Hepatitis B)' => 4, 'TSH' => 3, 'FT4' => 2, 'Syphilis (T. pallidum)' => 2, 'HIV (IgG/IgM)' => 2, 'Salmonella typhi IgG/IgM' => 3, 'Calcium (Ca)' => 2];
        if ($p['gender'] === 'Female' && $p['age'] >= 16 && $p['age'] <= 45) {
            $common['HCV (Pregnancy Test)'] = 6;
        }
        if ($p['gender'] === 'Male' && $p['age'] >= 20 && $p['age'] <= 55) {
            $common['Semenanalysis'] = 2;
        }
        $n = mt_rand(1, 100) <= 50 ? 1 : (mt_rand(1, 100) <= 70 ? 2 : 3);
        $picked = [];
        if ($p['profile']['diabetic'] && ($returning || mt_rand(0, 1))) {
            $picked['Glucose (RBS/FBS)'] = true;
            if (mt_rand(0, 1)) { $picked['HbA1c'] = true; }
        }
        if ($p['profile']['dyslipidemia'] && mt_rand(0, 1)) {
            $picked['Cholesterol'] = true;
        }
        while (count($picked) < $n) {
            $picked[$this->weighted($common)] = true;
        }
        return array_keys($picked);
    }

    private function pickXrayServices(array $p): array
    {
        $list = ['Chest A/P' => 22, 'Chest APL' => 20, 'Thoracic Bony Cage/TBC' => 3, 'Hand AP/Metacarpal Only' => 5, 'Wrist AP / APL' => 5, 'Finger' => 3, 'Forearm AP / APL' => 4, 'Cervical APL' => 5, 'Neck/Cervical APL' => 3, 'Lumbosacral AP / APL' => 6, 'Knee APL' => 6, 'Ankle APL' => 5, 'Foot APL' => 4, 'Shoulder AP / APL' => 4, 'Elbow AP / APL' => 3, 'Paranasal Water Only' => 4, 'Skull AP / APL' => 2, 'Abdomen Plain' => 3, 'Pelvis AP / APL' => 3];
        $first = $this->weighted($list);
        $out = [$first];
        if (mt_rand(1, 100) <= 12) {
            $second = $this->weighted($list);
            if ($second !== $first) {
                $out[] = $second;
            }
        }
        return $out;
    }

    // =================================================================
    // ONE VISIT -> appointment / requests / payment / results
    // =================================================================

    private function seedVisit(array $p, array $v, array $samples): void
    {
        $appointmentId = null;
        $online = $p['source'] === 'online';

        if ($online) {
            $status = match ($v['stage']) {
                'booking', 'pending' => $v['stage'] === 'booking' ? 'pending' : 'approved',
                'cancelled' => 'cancelled',
                'released'  => $v['daysAgo'] > 0 ? 'completed' : 'approved',
                default     => 'approved',
            };
            $booked = date('Y-m-d H:i:s', strtotime($v['at']) - mt_rand(1, 6) * 86400 - mt_rand(0, 36000));
            if ($v['stage'] === 'booking') {
                $booked = date('Y-m-d H:i:s', strtotime('-' . mt_rand(1, 48) . ' hours'));
            }
            $arrived = in_array($status, ['approved', 'completed'], true) ? $v['at'] : null;
            $this->db->table('appointments')->insert([
                'reference_number' => $this->ref('APPT-' . date('Ymd', strtotime($booked)) . '-', 8),
                'appointment_date' => $v['date'],
                'appointment_time' => substr(explode(' ', $v['at'])[1], 0, 5),
                'full_name'        => $p['name'],
                'age'              => $p['age'],
                'gender'           => $p['gender'],
                'email'            => $p['email'],
                'phone'            => $p['phone'],
                'service_type'     => count($v['types']) === 2 ? 'both' : ($v['types'][0] === 'lab' ? 'laboratory' : 'xray'),
                'lab_services'     => $v['lab'] ? json_encode($v['lab']) : null,
                'xray_services'    => $v['xray'] ? json_encode($v['xray']) : null,
                'status'           => $status,
                'arrival_time'     => $arrived,
                'created_at'       => $booked,
                'updated_at'       => $arrived ?? $booked,
            ]);
            $appointmentId = (int) $this->db->insertID();
            $this->manifest['appointments'][] = $appointmentId;

            if (in_array($v['stage'], ['booking', 'cancelled'], true)) {
                return;      // no requests until approved
            }
        } elseif ($v['stage'] === 'cancelled') {
            $v['stage'] = 'released';
        }

        $doctors = ['Dr. Ma. Theresa Lacson', 'Dr. Ramon Esguerra', 'Dr. Felipe Tan', 'Dr. Angela Sinsuat', 'Dr. Jose Rizal Umali', 'Dr. Carmela Fajardo', 'Dr. Benedicto Abad'];
        foreach ($v['types'] as $type) {
            $services = $type === 'lab' ? $v['lab'] : $v['xray'];
            $this->seedRequest($p, $v, $type, $services, $appointmentId, $online ? null : (mt_rand(1, 100) <= 70 ? $this->pick($doctors) : null), $samples);
        }
    }

    private function seedRequest(array $p, array $v, string $type, array $services, ?int $appointmentId, ?string $doctor, array $samples): void
    {
        $isXray = $type === 'xray';
        $stage  = $v['stage'];
        $t      = strtotime($v['at']);
        $now    = time();
        $clamp  = static fn(int $ts) => min($ts, $now - 60);

        $created  = $clamp($t);
        $assigned = $clamp($created + mt_rand(5, 35) * 60);
        $picked   = $clamp($assigned + mt_rand(5, 45) * 60);
        $done     = $clamp($picked + ($isXray ? mt_rand(20, 150) : mt_rand(30, 240)) * 60);
        $released = $done + mt_rand(10, 20 * 60) * 60;
        $hour = (int) date('G', $released);
        if ($hour < 8 || $hour >= 18) {          // releases happen during clinic hours
            $released = strtotime(date('Y-m-d', $released + ($hour >= 18 ? 86400 : 0)) . sprintf(' %02d:%02d:00', mt_rand(8, 10), mt_rand(0, 59)));
        }
        $released = $clamp($released);

        $staff = $isXray ? $this->pick(self::RADIOLOGISTS) : $this->pick(self::MED_TECHS);
        $order = ['pending' => 0, 'started' => 1, 'processing' => 2, 'draft' => 3, 'completed' => 4, 'released' => 5];
        $level = $order[$stage];

        $row = [
            'reference_number' => $this->ref(($isXray ? 'XR' : 'LAB') . '-' . date('y') . '-', 6),
            'type'             => $type,
            'appointment_id'   => $appointmentId,
            'patient_name'     => $p['name'],
            'patient_code'     => $p['code'],
            'age'              => $p['age'],
            'gender'           => $p['gender'],
            'email'            => $p['email'],
            'phone'            => $p['phone'],
            'services'         => implode(', ', $services),
            'request_date'     => $v['date'],
            'doctor_name'      => $doctor,
            'priority'         => $isXray ? ($v['stat'] ? 'STAT' : 'Routine') : ($v['stat'] ? 'stat' : 'routine'),
            'status'           => match (true) {
                $level === 0 => 'pending',
                $level <= 2  => 'in_progress',
                $level === 3 => 'draft',
                $level === 4 => 'completed',
                default      => 'released',
            },
            'created_at'       => date('Y-m-d H:i:s', $created),
        ];
        $last = $created;
        if ($level >= 1) {
            $row += ['assigned_to_id' => $staff['id'], 'assigned_to_name' => $staff['name'], 'assigned_at' => date('Y-m-d H:i:s', $assigned)];
            $last = $assigned;
        }
        if ($level >= 2) {
            $row += $isXray
                ? ['radiologist_id' => $staff['id'], 'radiologist_name' => $staff['name']]
                : ['med_tech_id' => $staff['id'], 'med_tech_name' => $staff['name']];
            $last = $picked;
        }
        if ($level >= 3) {
            $last = $done;
        }
        if ($level >= 5) {
            $row['released_at'] = date('Y-m-d H:i:s', $released);
            $last = $released;
        }
        $row['updated_at'] = date('Y-m-d H:i:s', $last);

        $this->db->table('diagnostic_requests')->insert($row);
        $requestId = (int) $this->db->insertID();
        $this->manifest['diagnostic_requests'][] = $requestId;

        if ($level >= 1) {
            $this->seedPayment($p, $type, $requestId, $appointmentId, $services, $assigned);
        }

        if ($isXray && $level >= 3) {
            [$findings, $impression] = $this->xrayReport($p, $services, $level === 3);
            $paths = [];
            foreach ($this->xrayFilms($services, $samples) as $src) {
                $rel = 'uploads/xray/xray_' . $requestId . '_' . bin2hex(random_bytes(6)) . '.jpg';
                $this->pendingFiles[] = [$src, $rel];
                $paths[] = $rel;
            }
            $this->db->table('diagnostic_requests')->where('id', $requestId)->update([
                'findings'       => $findings,
                'interpretation' => $impression,
                'image_path'     => $paths[0],
                'image_paths'    => json_encode($paths),
            ]);
        }

        if (!$isXray && $level >= 3) {
            $abnormal = $this->seedLabResults($p, $requestId, $services, $level === 3, date('Y-m-d H:i:s', $done));
            if ($level >= 4) {
                $this->db->table('diagnostic_requests')->where('id', $requestId)->update([
                    'findings' => $abnormal === []
                        ? 'All results are within the reference ranges.'
                        : 'Results outside the reference range: ' . implode(', ', $abnormal) . '. Kindly correlate clinically.',
                    'remarks'  => $this->pick(['Specimen adequate.', 'Specimen received in good condition.', 'Fasting specimen (10 hrs).', 'Non-fasting specimen.', '']),
                ]);
            }
        }
    }

    private function seedPayment(array $p, string $type, int $requestId, ?int $appointmentId, array $services, int $paidTs): void
    {
        $lines = [];
        $total = 0.0;
        foreach ($services as $s) {
            $charge = $this->prices[$s]['charge'] ?? 0.0;
            $lines[] = ['name' => $s, 'charge' => $charge];
            $total += $charge;
        }
        $at = date('Y-m-d H:i:s', $paidTs);
        $method = $this->weighted(['cash' => 70, 'gcash' => 22, 'card' => 6, 'paymaya' => 2]);
        $ref = $this->ref('PAY-' . date('ymd', $paidTs) . '-', 4);

        $this->db->table('payments')->insert([
            'request_id'       => $requestId,
            'request_type'     => $type,
            'patient_name'     => $p['name'],
            'patient_code'     => $p['code'],
            'appointment_id'   => $appointmentId,
            'services'         => json_encode($lines),
            'total_amount'     => $total,
            'amount_paid'      => $total,
            'change_amount'    => 0,
            'payment_method'   => $method,
            'reference_no'     => $method === 'cash' ? $ref : (string) mt_rand(1000000, 9999999) . mt_rand(100000, 999999),
            'payment_status'   => 'paid',
            'payment_date'     => $at,
            'reference_number' => $ref,
            'received_by'      => self::RECEPTION['name'],
            'received_by_id'   => self::RECEPTION['id'],
            'notes'            => 'Payment recorded when starting processing',
            'created_at'       => $at,
            'updated_at'       => $at,
        ]);
        $paymentId = (int) $this->db->insertID();
        $this->manifest['payments'][] = $paymentId;

        $this->db->table('invoices')->insert([
            'invoice_no'     => sprintf('INV-%s-%06d', date('Y'), ++$this->invoiceSeq),
            'patient_code'   => $p['code'],
            'patient_name'   => $p['name'],
            'appointment_id' => $appointmentId,
            'request_id'     => $requestId,
            'request_type'   => $type,
            'subtotal'       => $total,
            'total'          => $total,
            'amount_paid'    => $total,
            'balance'        => 0,
            'status'         => 'paid',
            'created_by'     => self::RECEPTION['name'],
            'created_by_id'  => self::RECEPTION['id'],
            'created_at'     => $at,
            'updated_at'     => $at,
        ]);
        $invoiceId = (int) $this->db->insertID();
        $this->manifest['invoices'][] = $invoiceId;
        $this->db->table('payments')->where('id', $paymentId)->update(['invoice_id' => $invoiceId]);

        foreach ($lines as $l) {
            $this->db->table('invoice_items')->insert([
                'invoice_id'   => $invoiceId,
                'service_name' => $l['name'],
                'category'     => $this->prices[$l['name']]['category'] ?? ($type === 'xray' ? 'xray' : 'laboratory'),
                'qty'          => 1,
                'unit_price'   => $l['charge'],
                'line_total'   => $l['charge'],
                'created_at'   => $at,
                'updated_at'   => $at,
            ]);
            $this->manifest['invoice_items'][] = (int) $this->db->insertID();
        }
    }

    // =================================================================
    // LAB RESULTS
    // =================================================================

    /** @return string[] names of out-of-range tests */
    private function seedLabResults(array $p, int $requestId, array $services, bool $partial, string $at): array
    {
        $abnormal = [];
        $rows = [];
        foreach ($services as $service) {
            $tests = $this->tests[strtolower($service)] ?? [];
            if (is_string($tests)) {
                $tests = $this->tests[$tests] ?? [];
            }
            foreach ($tests as $test) {
                [$value, $flag] = $this->labValue($p, $test);
                if ($flag !== 'normal') {
                    $abnormal[] = $test['test_name'] . ' (' . $value . ($test['unit'] !== '' ? ' ' . $test['unit'] : '') . ')';
                }
                $rows[] = [
                    'lab_request_id'  => $requestId,
                    'test_name'       => $test['test_name'],
                    'result'          => $value,
                    'unit'            => $test['unit'],
                    'reference_range' => $test['reference_range'],
                    'flag'            => $flag,
                    'created_at'      => $at,
                    'updated_at'      => $at,
                ];
            }
        }
        if ($partial) {                       // a draft has only part of the panel saved
            $rows = array_slice($rows, 0, max(1, intdiv(count($rows), 2)));
        }
        foreach ($rows as $r) {
            $this->db->table('lab_results')->insert($r);
            $this->manifest['lab_results'][] = (int) $this->db->insertID();
        }
        return $abnormal;
    }

    /** @return array{0: string, 1: string} [result, flag] */
    private function labValue(array $p, array $test): array
    {
        $name  = $test['test_name'];
        $range = $test['reference_range'];
        $prof  = $p['profile'];

        // Qualitative tests
        $qual = [
            'ABO Blood Type' => fn() => [$this->weighted(['O' => 45, 'A' => 25, 'B' => 25, 'AB' => 5]), 'normal'],
            'Rh Factor'      => fn() => [mt_rand(1, 100) <= 99 ? 'Positive' : 'Negative', 'normal'],
            'Color'          => fn() => $prof['uti'] && mt_rand(0, 1) ? ['Dark Yellow', 'high'] : [$this->pick(['Yellow', 'Light Yellow', 'Straw']), 'normal'],
            'Appearance'     => fn() => $prof['uti'] ? ['Turbid', 'high'] : [$this->pick(['Clear', 'Clear', 'Slightly Hazy']), 'normal'],
            'Protein'        => fn() => $prof['renal'] || $prof['diabetic'] && mt_rand(0, 2) === 0 ? ['+1', 'high'] : (mt_rand(1, 100) <= 6 ? ['Trace', 'high'] : ['Negative', 'normal']),
            'Glucose'        => fn() => $prof['diabetic'] && mt_rand(0, 1) ? ['+2', 'high'] : ['Negative', 'normal'],
            'Ketones'        => fn() => ['Negative', 'normal'],
            'Epithelial Cells' => fn() => [$this->pick(['Few', 'Few', 'Rare', 'Moderate']), 'normal'],
            'Bacteria'       => fn() => $prof['uti'] ? ['Many', 'high'] : [$this->pick(['None', 'None', 'Few']), 'normal'],
            'Stool Color'    => fn() => [$this->pick(['Brown', 'Brown', 'Yellowish Brown']), 'normal'],
            'Stool Consistency' => fn() => mt_rand(1, 100) <= 15 ? ['Soft', 'normal'] : (mt_rand(1, 100) <= 8 ? ['Watery', 'high'] : ['Formed', 'normal']),
            'Occult Blood'   => fn() => mt_rand(1, 100) <= 5 ? ['Positive', 'high'] : ['Negative', 'normal'],
            'Fat Globules'   => fn() => ['None', 'normal'],
            'Ova / Parasites' => fn() => mt_rand(1, 100) <= 12 ? [$this->pick(['Ascaris lumbricoides ova seen', 'Trichuris trichiura ova seen', 'Giardia lamblia cysts seen']), 'high'] : ['None seen', 'normal'],
            'Pregnancy Test (hCG)' => fn() => mt_rand(1, 100) <= 10 ? ['Positive', 'high'] : ['Negative', 'normal'],
            'Salmonella typhi IgG' => fn() => mt_rand(1, 100) <= 10 ? ['Positive', 'high'] : ['Negative', 'normal'],
            'Salmonella typhi IgM' => fn() => mt_rand(1, 100) <= 7 ? ['Positive', 'high'] : ['Negative', 'normal'],
        ];
        if (isset($qual[$name])) {
            return $qual[$name]();
        }
        if (in_array($range, ['Non-reactive'], true)) {
            return mt_rand(1, 100) <= 2 ? ['Reactive', 'critical'] : ['Non-reactive', 'normal'];
        }

        // Numeric tests: centre, tilt by profile, then format to the range's precision.
        if (!preg_match('/^(<|>|>=|<=)?\s*([\d.]+)(?:-([\d.]+))?$/', $range, $m)) {
            return ['—', 'normal'];
        }
        $op = $m[1];
        $lo = (float) $m[2];
        $hi = isset($m[3]) && $m[3] !== '' ? (float) $m[3] : null;
        $dec = max(strlen(substr(strrchr($m[2], '.') ?: '', 1)), $hi !== null ? strlen(substr(strrchr($m[3], '.') ?: '', 1)) : 0);

        $tilt = 0;   // -1 low, +1 high, 0 random
        $n = strtolower($name);
        if ($prof['diabetic'] && (str_contains($n, 'glucose') || $n === 'hba1c')) { $tilt = 1; }
        if ($prof['dyslipidemia'] && (str_contains($n, 'cholesterol') && !str_contains($n, 'hdl') || $n === 'triglycerides')) { $tilt = 1; }
        if ($prof['dyslipidemia'] && str_contains($n, 'hdl')) { $tilt = -1; }
        if ($prof['anemic'] && (str_contains($n, 'hemoglobin') || str_contains($n, 'hematocrit') || str_contains($n, 'red blood'))) { $tilt = -1; }
        if ($prof['renal'] && (in_array($n, ['creatinine', 'blood urea nitrogen (bun)', 'blood uric acid'], true))) { $tilt = 1; }
        if ($prof['uti'] && $n === 'wbc') { $tilt = 1; }
        if ($tilt === 0 && mt_rand(1, 100) <= 12) {
            $tilt = mt_rand(0, 1) ? 1 : -1;
        }

        if ($hi !== null) {
            $span = $hi - $lo;
            $v = match ($tilt) {
                1  => $hi + $span * mt_rand(5, 45) / 100,
                -1 => max(0, $lo - $span * mt_rand(5, 30) / 100),
                default => $lo + $span * mt_rand(10, 90) / 100,
            };
            if ($n === 'hba1c' && $tilt === 1) { $v = mt_rand(62, 98) / 10; }
            if ($n === 'glucose (fbs)' && $tilt === 1) { $v = mt_rand(126, 240); }
            $flag = $v > $hi ? 'high' : ($v < $lo ? 'low' : 'normal');
        } elseif ($op === '<' || $op === '<=') {
            $v = $tilt === 1 ? $lo * mt_rand(103, 140) / 100 : $lo * mt_rand(55, 95) / 100;
            $flag = $v >= $lo ? 'high' : 'normal';
        } else {   // > or >=
            $v = $tilt === -1 ? $lo * mt_rand(65, 95) / 100 : $lo * mt_rand(105, 170) / 100;
            $flag = $v < $lo ? 'low' : 'normal';
        }
        if (($n === 'glucose (fbs)' && $v >= 250) || ($n === 'potassium (k)' && ($v >= 6 || $v < 3))) {
            $flag = 'critical';
        }
        return [number_format($v, $dec, '.', ''), $flag];
    }

    // =================================================================
    // X-RAY REPORTS
    // =================================================================

    /** @return array{0: string, 1: string} [findings, impression] */
    private function xrayReport(array $p, array $services, bool $draft): array
    {
        $findings = [];
        $impressions = [];
        foreach ($services as $s) {
            $k = strtolower($s);
            $old = $p['age'] >= 55;
            if (str_contains($k, 'chest') || str_contains($k, 'tbc')) {
                if ($p['profile']['ptb']) {
                    $f = 'Fibroreticular and nodular densities are seen in the right upper lung field. The rest of the lungs are clear. Heart is not enlarged. Diaphragm and costophrenic sulci are intact.';
                    $i = 'Pulmonary tuberculosis, right upper lobe, activity undetermined. Suggest sputum studies.';
                } elseif (mt_rand(1, 100) <= 12) {
                    $f = 'Hazy densities are noted in the right lower lung field. Heart is not enlarged. Hemidiaphragms and costophrenic sulci are intact.';
                    $i = 'Pneumonia, right lower lobe.';
                } elseif ($old && mt_rand(1, 100) <= 40) {
                    $f = 'Lungs are clear. The heart is enlarged with a cardiothoracic ratio of 0.58. The aorta is tortuous with calcific plaques at the knob. Diaphragm and sulci are intact.';
                    $i = 'Cardiomegaly. Atheromatous aorta.';
                } else {
                    $f = 'Both lung fields are clear. No focal consolidation, pleural effusion or pneumothorax. Heart is normal in size. Mediastinum and hila are unremarkable. Bony thorax is intact.';
                    $i = 'Normal chest findings.';
                }
            } elseif (preg_match('/hand|wrist|finger|forearm|radius|elbow|arm|shoulder|humerus/', $k)) {
                $part = strtolower(trim(preg_replace('/\s*(ap|apl|\/.*|only).*$/i', '', $s)));
                if (mt_rand(1, 100) <= 22) {
                    $f = "A non-displaced transverse fracture line is seen in the {$part}. Alignment is maintained. No dislocation. Surrounding soft tissue swelling is noted.";
                    $i = "Non-displaced fracture, {$part}.";
                } else {
                    $f = "Bones of the {$part} are intact with normal alignment. Joint spaces are preserved. No fracture, dislocation or bony lesion. Soft tissues are unremarkable.";
                    $i = "Negative study of the {$part}.";
                }
            } elseif (preg_match('/cervical|neck|lumbo|lumbar|thoracic|spine/', $k)) {
                $seg = preg_match('/cervical|neck/', $k) ? 'cervical' : (str_contains($k, 'thoracic') ? 'thoracic' : 'lumbosacral');
                if ($old || mt_rand(1, 100) <= 25) {
                    $f = "Marginal osteophytes are seen along the anterior borders of the {$seg} vertebral bodies. Intervertebral disc spaces are mildly narrowed. Alignment is maintained. No fracture or listhesis.";
                    $i = 'Spondylosis, ' . $seg . ' spine.';
                } else {
                    $f = "Straightening of the normal {$seg} curvature is noted. Vertebral bodies and disc spaces are preserved. Pedicles are intact. No fracture or subluxation.";
                    $i = 'Straightening of the ' . $seg . ' curvature, may be due to muscle spasm.';
                }
            } elseif (preg_match('/knee|ankle|foot|leg|pelvis|hip|frog/', $k)) {
                $part = strtolower(trim(preg_replace('/\s*(ap|apl|\/.*|only).*$/i', '', $s)));
                if ($old && mt_rand(1, 100) <= 60) {
                    $f = "Narrowing of the joint space with subchondral sclerosis and marginal osteophytes is seen in the {$part}. No fracture or dislocation.";
                    $i = "Degenerative osteoarthritis, {$part}.";
                } else {
                    $f = "No fracture, dislocation or bony destruction in the {$part}. Joint spaces are preserved. Soft tissues are unremarkable.";
                    $i = "Normal {$part} radiographs.";
                }
            } elseif (preg_match('/paranasal|pns|skull|nasap|orbit/', $k)) {
                if (mt_rand(1, 100) <= 45) {
                    $f = 'Mucosal thickening with haziness is seen in both maxillary sinuses. Frontal and ethmoid sinuses are clear. Nasal septum is midline.';
                    $i = 'Bilateral maxillary sinusitis.';
                } else {
                    $f = 'Paranasal sinuses are well aerated. Nasal septum is midline. Calvarium is intact with no fracture line.';
                    $i = 'Normal study.';
                }
            } else {
                $f = 'Bowel gas pattern is nonspecific. No dilated bowel loops or air-fluid levels. No radio-opaque densities. Psoas shadows and properitoneal fat lines are preserved.';
                $i = 'Nonspecific bowel gas pattern.';
            }
            $findings[] = (count($services) > 1 ? strtoupper($s) . ': ' : '') . $f;
            $impressions[] = $i;
        }
        if ($draft) {
            return [implode("\n\n", $findings), ''];
        }
        return [implode("\n\n", $findings), implode("\n", array_unique($impressions))];
    }

    /** Sample films: 1-2 chest, 3 hand, 4 cervical; anything else gets a sample too. */
    private function xrayFilms(array $services, array $samples): array
    {
        $k = strtolower(implode(' ', $services));
        if (preg_match('/chest|tbc|thoracic/', $k)) {
            return mt_rand(0, 1) ? [$samples[1], $samples[0]] : [$samples[1]];
        }
        if (preg_match('/hand|wrist|finger|forearm|radius|arm|elbow/', $k)) {
            return [$samples[2]];
        }
        if (preg_match('/cervical|neck|skull|nasap|orbit|paranasal/', $k)) {
            return [$samples[3]];
        }
        return [$samples[mt_rand(0, 3)]];
    }

    // =================================================================
    // helpers
    // =================================================================

    private function pick(array $items)
    {
        return $items[array_rand($items)];
    }

    private function weighted(array $weights): string
    {
        $r = mt_rand(1, array_sum($weights));
        foreach ($weights as $key => $w) {
            if (($r -= $w) <= 0) {
                return (string) $key;
            }
        }
        return (string) array_key_first($weights);
    }

    private function ref(string $prefix, int $hexLen): string
    {
        do {
            $ref = $prefix . strtoupper(substr(bin2hex(random_bytes(8)), 0, $hexLen));
        } while (isset($this->usedRefs[$ref]));
        $this->usedRefs[$ref] = true;
        return $ref;
    }
}
