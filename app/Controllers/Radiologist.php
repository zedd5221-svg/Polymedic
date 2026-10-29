<?php

namespace App\Controllers;

use App\Models\AppointmentModel;
use App\Models\DiagnosticRequestModel;
use App\Models\NotificationModel;
use App\Models\PaymentModel;
use App\Models\ServiceModel;
use App\Models\UserModel;

class Radiologist extends BaseController
{
    private function checkAuth()
    {
        if (!session()->get('is_logged_in')) {
            return redirect()->to(base_url('login'));
        }

        $role = session()->get('role');
        if ($role !== 'radiologist') {
            return redirect()->to((new NotificationModel())->dashboardFor($role));
        }
        return null;
    }

    /** An X-ray request by id; lab rows are never reachable from here. */
    private function findXray($id): ?array
    {
        return (new DiagnosticRequestModel())
            ->where('id', (int) $id)
            ->where('type', DiagnosticRequestModel::TYPE_XRAY)
            ->first();
    }

    private function departmentStatuses(): array
    {
        return [
            DiagnosticRequestModel::STATUS_IN_PROGRESS,
            DiagnosticRequestModel::STATUS_COMPLETED,
            DiagnosticRequestModel::STATUS_RELEASED,
        ];
    }

    /**
     * X-ray worklist counts from one grouped query.
     * pending = paid but not opened-and-saved by a radiologist yet,
     * processing = in reading (a radiologist has saved a draft).
     */
    private function departmentCounts(): array
    {
        return (new DiagnosticRequestModel())->getDepartmentCounts(DiagnosticRequestModel::TYPE_XRAY);
    }

    /**
     * ------------------------------------------------------------------
     * Payment precondition helper.
     * Mirrors the same guard used by MedTech.
     * ------------------------------------------------------------------
     */
    private function requestIsPaid(int $id, string $type = DiagnosticRequestModel::TYPE_XRAY): bool
    {
        return !empty((new PaymentModel())->getByRequest($id, $type));
    }

    private function getAvailableXrayServices()
    {
        $serviceModel = new ServiceModel();

        $services = $serviceModel->getXrayServices();

        $categoryMap = [
            'Chest'             => [
                'chest', 'thoracic bony', 'thoracic bony cage',
            ],
            'Skull & Face'      => [
                'skull', 'towner', 'orbit', 'nasap',
                'paranasal', 'pns', 'neck', 'cervical',
            ],
            'Spine & Pelvis'    => [
                'thoracic vert', 'thoracolumbar', 'lumbosacral',
                'lumbar', 'whole spine', 'pelvis', 'frog leg',
            ],
            'Upper Extremities' => [
                'shoulder', 'humerus', 'arm', 'elbow',
                'forearm', 'radius', 'wrist',
                'hand', 'finger', 'metacarpal',
            ],
            'Lower Extremities' => [
                'leg', 'knee', 'foot', 'ankle',
            ],
            'Abdomen'           => [
                'abdomen',
            ],
        ];

        $categoryCounts = [];
        foreach ($categoryMap as $cat => $_) {
            $categoryCounts[$cat] = 0;
        }
        $categoryCounts['Other'] = 0;

        foreach ($services as $service) {
            $name    = strtolower((string) ($service['service_name'] ?? ''));
            $matched = false;

            foreach ($categoryMap as $cat => $keywords) {
                foreach ($keywords as $kw) {
                    if (strpos($name, $kw) !== false) {
                        $categoryCounts[$cat]++;
                        $matched = true;
                        break 2;
                    }
                }
            }

            if (!$matched) {
                $categoryCounts['Other']++;
            }
        }

        $categoryCounts = array_filter($categoryCounts);

        return [
            'services'       => $services,
            'count'          => count($services),
            'categoryCounts' => $categoryCounts,
        ];
    }

    public function dashboard()
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $model        = new DiagnosticRequestModel();
        $paymentModel = new PaymentModel();
        $type         = DiagnosticRequestModel::TYPE_XRAY;

        $departmentStatuses = $this->departmentStatuses();

        $data = $this->departmentCounts();

        $model->resetQuery();
        $data['recent_examinations'] = $model
            ->where('type', $type)
            ->whereIn('status', $departmentStatuses)
            ->orderBy('created_at', 'DESC')
            ->limit(10)
            ->findAll();
        $data['recent_examinations'] = DiagnosticRequestModel::withDepartmentStatus($data['recent_examinations']);

        $model->resetQuery();
        $data['pending_examinations'] = $model
            ->where('type', $type)
            ->where('status', DiagnosticRequestModel::STATUS_IN_PROGRESS)
            ->orderBy('created_at', 'ASC')
            ->limit(5)
            ->findAll();
        $data['pending_examinations'] = DiagnosticRequestModel::withDepartmentStatus($data['pending_examinations']);

        $data['today_revenue']   = $paymentModel->getXrayRevenueToday();
        $data['monthly_revenue'] = $paymentModel->getXrayRevenueThisMonth();

        $data['servicesCatalog'] = $this->getAvailableXrayServices();

        $data['kpiTrends'] = $this->getKpiTrends(14, (int) $data['total']);

        return view('Radiologist/dashboard', $data);
    }

    private function getKpiTrends(int $days, int $totalStudies): array
    {
        $db    = db_connect();
        $start = date('Y-m-d', strtotime('-' . ($days - 1) . ' days'));
        $end   = date('Y-m-d');
        $type  = DiagnosticRequestModel::TYPE_XRAY;

        $dates = [];
        for ($i = 0; $i < $days; $i++) {
            $dates[] = date('Y-m-d', strtotime($start . ' +' . $i . ' days'));
        }

        $series = function (string $dateExpr, string $valueExpr, array $where = []) use ($db, $dates, $start, $end, $type) {
            $b = $db->table('diagnostic_requests')
                ->select("$dateExpr AS d, $valueExpr AS v", false)
                ->where('type', $type)
                ->where("$dateExpr >=", $start)
                ->where("$dateExpr <=", $end);
            foreach ($where as $col => $val) {
                // a null value means $col is a raw SQL condition
                $val === null ? $b->where($col, null, false) : $b->where($col, $val);
            }
            $rows = $b->groupBy('d')->get()->getResultArray();

            $map = [];
            foreach ($rows as $r) {
                $map[$r['d']] = (float) $r['v'];
            }
            return array_map(fn($d) => $map[$d] ?? 0, $dates);
        };

        $newTotal = $series('DATE(created_at)', 'COUNT(*)');
        $total    = [];
        $running  = $totalStudies - array_sum($newTotal);
        foreach ($newTotal as $n) {
            $running += $n;
            $total[]  = $running;
        }

        return [
            'dates'       => $dates,
            'pending'     => $series('DATE(created_at)', 'COUNT(*)', [DiagnosticRequestModel::untouchedSql($type) => null]),
            'processing'  => $series('DATE(created_at)', 'COUNT(*)', ['status' => 'in_progress', 'NOT ' . DiagnosticRequestModel::untouchedSql($type) => null]),
            'completed'   => $series('DATE(updated_at)', 'COUNT(*)', ['status' => 'completed']),
            'released'    => $series('DATE(released_at)', 'COUNT(*)', ['status' => 'released']),
            'total'       => $total,
            'revenue_today' => $this->getXrayRevenueSeries($dates, false),
            'revenue_month' => $this->getXrayRevenueSeries($dates, true),
        ];
    }

    private function getXrayRevenueSeries(array $dates, bool $cumulative): array
    {
        if (empty($dates)) { return []; }

        $db    = db_connect();
        $start = $dates[0];
        $end   = end($dates);

        $rows = $db->table('payments')
            ->select('DATE(payment_date) AS d, SUM(amount_paid) AS v', false)
            ->where('payment_status', 'paid')
            ->where('request_type', DiagnosticRequestModel::TYPE_XRAY)
            ->where('DATE(payment_date) >=', $start)
            ->where('DATE(payment_date) <=', $end)
            ->groupBy('d')
            ->get()
            ->getResultArray();

        $map = [];
        foreach ($rows as $r) {
            $map[$r['d']] = (float) $r['v'];
        }

        $series = array_map(fn($d) => $map[$d] ?? 0, $dates);

        if (!$cumulative) {
            return $series;
        }

        $monthStart = date('Y-m-01');
        $running    = 0;
        $out        = [];
        foreach ($dates as $i => $d) {
            if ($d < $monthStart) {
                $out[] = 0;
                continue;
            }
            $running += $series[$i];
            $out[]    = $running;
        }
        return $out;
    }

    public function examinations()
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $model = new DiagnosticRequestModel();
        $type  = DiagnosticRequestModel::TYPE_XRAY;

        $departmentStatuses = $this->departmentStatuses();

        $data['examinations'] = $model
            ->where('type', $type)
            ->whereIn('status', $departmentStatuses)
            ->orderBy('created_at', 'DESC')
            ->findAll();

        $data['examinations'] = DiagnosticRequestModel::withDepartmentStatus($data['examinations']);
        $data['counts']       = $this->departmentCounts();

        return view('Radiologist/examinations', $data);
    }

    public function viewExamination($id)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $model = new DiagnosticRequestModel();
        $data['examination'] = $this->findXray($id);
        if ($data['examination']) {
            [$data['examination']] = DiagnosticRequestModel::withDepartmentStatus([$data['examination']]);
        }

        if (!$data['examination']) {
            return redirect()->to(base_url('radiologist/examinations'))
                            ->with('error', 'Examination not found');
        }

        // Pass the payment state to the view so it can disable the
        // report form when the study has not been paid yet.
        $data['isPaid'] = $this->requestIsPaid((int) $id, DiagnosticRequestModel::TYPE_XRAY);

        return view('Radiologist/view_examination', $data);
    }

    public function saveDraft($id)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $findings       = $this->request->getPost('findings');
        $interpretation = $this->request->getPost('interpretation');

        try {
            $model       = new DiagnosticRequestModel();
            $examination = $this->findXray($id);

            if (!$examination) {
                return redirect()->to(base_url('radiologist/examinations'))
                                ->with('error', 'Examination not found');
            }

            // Payment precondition.
            if (!$this->requestIsPaid((int) $id, DiagnosticRequestModel::TYPE_XRAY)) {
                return redirect()->to(base_url('radiologist/examinations'))
                                ->with('error', 'This study has not been paid yet. '
                                    . 'The receptionist must click Start before the report can be written.');
            }

            if ($examination['status'] === 'released') {
                return redirect()->to(base_url('radiologist/examination/view/' . $id))
                                ->with('error', 'This report has already been released and cannot be edited.');
            }

            // Preserve an already-advanced status. Only a `pending`
            // request is moved to `in_progress` by saving a draft.
            $status = $examination['status'] === DiagnosticRequestModel::STATUS_PENDING
                ? DiagnosticRequestModel::STATUS_IN_PROGRESS
                : $examination['status'];

            $update = [
                'findings'       => $findings,
                'interpretation' => $interpretation,
                'status'         => $status,
            ];

            if (empty($examination['radiologist_name'])) {
                $update['radiologist_name'] = PaymentModel::currentCashierName();
                $update['radiologist_id']   = PaymentModel::currentStaffId();
            }

            $model->update($id, $update);

            return redirect()->to(base_url('radiologist/examination/view/' . $id))
                            ->with('success', 'Draft saved. Complete the report when both sections are filled.');

        } catch (\Exception $e) {
            log_message('error', 'Save draft error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error saving draft');
        }
    }

    public function saveFindings($id)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $findings       = trim((string) $this->request->getPost('findings'));
        $interpretation = trim((string) $this->request->getPost('interpretation'));

        try {
            $model       = new DiagnosticRequestModel();
            $examination = $this->findXray($id);

            if (!$examination) {
                return redirect()->to(base_url('radiologist/examinations'))
                                ->with('error', 'Examination not found');
            }

            // Payment precondition.
            if (!$this->requestIsPaid((int) $id, DiagnosticRequestModel::TYPE_XRAY)) {
                return redirect()->to(base_url('radiologist/examinations'))
                                ->with('error', 'This study has not been paid yet. '
                                    . 'The receptionist must click Start before the report can be saved.');
            }

            if ($examination['status'] === 'released') {
                return redirect()->to(base_url('radiologist/examination/view/' . $id))
                                ->with('error', 'This report has already been released and cannot be edited.');
            }

            $missing = [];
            if ($findings === '')       { $missing[] = 'Findings'; }
            if ($interpretation === '') { $missing[] = 'Impression'; }

            if (!empty($missing)) {
                return redirect()->to(base_url('radiologist/examination/view/' . $id))
                                ->with('error', 'Cannot complete this report: ' . implode(' and ', $missing) . ' must not be empty. Use Save draft to keep working.');
            }

            $update = [
                'findings'       => $findings,
                'interpretation' => $interpretation,
                'status'         => 'completed',
            ];

            if (empty($examination['radiologist_name'])) {
                $update['radiologist_name'] = PaymentModel::currentCashierName();
                $update['radiologist_id']   = PaymentModel::currentStaffId();
            }

            $model->update($id, $update);

            return redirect()->to(base_url('radiologist/examination/view/' . $id))
                            ->with('success', 'Report completed. You can now release it to the receptionist.');

        } catch (\Exception $e) {
            log_message('error', 'Save findings error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error saving report');
        }
    }

    /* ============================================================
       RADIOLOGY REPORT
       ============================================================
       Period workload / turnaround report for the x-ray room.
       - Studies received: paid x-ray requests whose exam date
         (request_date) falls in the window.
       - Reports released: released_at in the window.
       - Turnaround (TAT): first payment -> release, the time the
         study actually spent with the department.
       - Backlog: every unreleased study, as of now.
       GET: range (today|yesterday|week|month|last_month|custom),
            from, to, radiologist, export=csv
       ============================================================ */

    /** TAT targets in hours, by priority. */
    private const TAT_TARGETS = ['stat' => 2, 'urgent' => 4, 'routine' => 24];

    public function reports()
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        [$range, $from, $to] = $this->reportRange();
        $reader = trim((string) ($this->request->getGet('radiologist') ?? ''));

        $days     = (int) ((strtotime($to) - strtotime($from)) / 86400) + 1;
        $prevTo   = date('Y-m-d', strtotime($from . ' -1 day'));
        $prevFrom = date('Y-m-d', strtotime($prevTo . ' -' . ($days - 1) . ' days'));

        $studies  = $this->reportStudies($from, $to, $reader);
        $released = $this->releasedStudies($from, $to, $reader);

        if ($this->request->getGet('export') === 'csv') {
            return $this->reportCsv($studies, $from, $to);
        }

        $prevReleased = $this->releasedStudies($prevFrom, $prevTo, $reader);

        $readers = array_column(
            db_connect()->table('diagnostic_requests')
                ->select('radiologist_name')->distinct()
                ->where('type', DiagnosticRequestModel::TYPE_XRAY)
                ->where('radiologist_name IS NOT NULL')->where('radiologist_name <>', '')
                ->orderBy('radiologist_name')->get()->getResultArray(),
            'radiologist_name'
        );

        $paymentModel = new PaymentModel();

        return view('Radiologist/reports', [
            'range'       => $range,
            'from'        => $from,
            'to'          => $to,
            'prevFrom'    => $prevFrom,
            'prevTo'      => $prevTo,
            'reader'      => $reader,
            'readers'     => $readers,
            'studies'     => $studies,
            'released'    => $released,
            'summary'     => $this->summariseStudies($studies, $released),
            'prev'        => $this->summariseStudies($this->reportStudies($prevFrom, $prevTo, $reader), $prevReleased),
            'revenue'     => $paymentModel->getXrayRevenueBetween($from, $to),
            'prevRevenue' => $paymentModel->getXrayRevenueBetween($prevFrom, $prevTo),
            'daily'       => $this->dailyVolume($studies, $released, $from, $to),
            'backlog'     => $this->backlog($reader),
            'targets'     => self::TAT_TARGETS,
            'clinic'      => config('Clinic')->toArray(),
            'preparedBy'  => PaymentModel::currentCashierName(),
        ]);
    }

    /** Resolve the report window from the GET params. Returns [range, from, to]. */
    private function reportRange(): array
    {
        $range = (string) ($this->request->getGet('range') ?? 'month');
        $today = date('Y-m-d');

        switch ($range) {
            case 'today':
                return [$range, $today, $today];
            case 'yesterday':
                $d = date('Y-m-d', strtotime('-1 day'));
                return [$range, $d, $d];
            case 'week':
                return [$range, date('Y-m-d', strtotime('monday this week')), $today];
            case 'last_month':
                return [$range, date('Y-m-01', strtotime('first day of last month')), date('Y-m-t', strtotime('last day of last month'))];
            case 'custom':
                $from = (string) $this->request->getGet('from');
                $to   = (string) $this->request->getGet('to');
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) $from = $today;
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to))   $to   = $today;
                if ($from > $to) [$from, $to] = [$to, $from];
                if ((strtotime($to) - strtotime($from)) / 86400 > 366) {
                    $from = date('Y-m-d', strtotime($to . ' -366 days'));
                }
                return [$range, $from, $to];
            default:
                return ['month', date('Y-m-01'), $today];
        }
    }

    /** Paid x-ray studies with an exam date in the window, decorated. */
    private function reportStudies(string $from, string $to, string $reader = ''): array
    {
        $builder = (new DiagnosticRequestModel())
            ->where('type', DiagnosticRequestModel::TYPE_XRAY)
            ->whereIn('status', DiagnosticRequestModel::DEPARTMENT_STATUSES)
            ->where('request_date >=', $from)
            ->where('request_date <=', $to);

        if ($reader !== '') {
            $builder->where('radiologist_name', $reader);
        }

        return $this->decorateStudies($builder->orderBy('request_date', 'DESC')->orderBy('id', 'DESC')->findAll());
    }

    /** Studies released in the window (whatever their exam date), decorated. */
    private function releasedStudies(string $from, string $to, string $reader = ''): array
    {
        $builder = (new DiagnosticRequestModel())
            ->where('type', DiagnosticRequestModel::TYPE_XRAY)
            ->where('status', DiagnosticRequestModel::STATUS_RELEASED)
            ->where('released_at >=', $from . ' 00:00:00')
            ->where('released_at <=', $to . ' 23:59:59');

        if ($reader !== '') {
            $builder->where('radiologist_name', $reader);
        }

        return $this->decorateStudies($builder->orderBy('released_at', 'DESC')->findAll());
    }

    /**
     * Adds dept_status, exams[], image_count, paid_at, tat_hours,
     * priority_key and within_target to each row.
     */
    private function decorateStudies(array $rows): array
    {
        if ($rows === []) return [];

        $rows = DiagnosticRequestModel::withDepartmentStatus($rows);

        // When the study reached the department = its first payment.
        $paidAt = [];
        foreach (db_connect()->table('payments')
            ->select('request_id, MIN(payment_date) AS paid_at')
            ->where('request_type', DiagnosticRequestModel::TYPE_XRAY)
            ->whereIn('payment_status', [PaymentModel::STATUS_PAID, PaymentModel::STATUS_REFUNDED])
            ->whereIn('request_id', array_column($rows, 'id'))
            ->groupBy('request_id')->get()->getResultArray() as $p) {
            $paidAt[(int) $p['request_id']] = $p['paid_at'];
        }

        foreach ($rows as &$r) {
            $r['exams'] = array_values(array_filter(array_map('trim', explode(',', (string) ($r['services'] ?? '')))));

            $images = json_decode((string) ($r['image_paths'] ?? ''), true);
            $r['image_count'] = is_array($images) ? count($images) : (empty($r['image_path']) ? 0 : 1);

            $r['paid_at'] = $paidAt[(int) $r['id']] ?? $r['created_at'];

            $p = strtolower(trim((string) ($r['priority'] ?? '')));
            $r['priority_key'] = isset(self::TAT_TARGETS[$p]) ? $p : 'routine';

            $r['tat_hours']     = null;
            $r['within_target'] = null;
            if (!empty($r['released_at']) && !empty($r['paid_at'])) {
                $r['tat_hours']     = max(0, (strtotime($r['released_at']) - strtotime($r['paid_at'])) / 3600);
                $r['within_target'] = $r['tat_hours'] <= self::TAT_TARGETS[$r['priority_key']];
            }
        }
        unset($r);

        return $rows;
    }

    /** Totals and breakdowns for a window. */
    private function summariseStudies(array $studies, array $released): array
    {
        $s = [
            'received' => count($studies), 'released' => count($released),
            'exposures' => 0, 'patients' => 0, 'stat' => 0,
            'status' => ['pending' => 0, 'in_progress' => 0, 'draft' => 0, 'completed' => 0, 'released' => 0],
            'tat_median' => null, 'tat_avg' => null, 'within_target' => null,
            'tat_buckets' => ['< 2 h' => 0, '2–8 h' => 0, '8–24 h' => 0, '1–3 days' => 0, '> 3 days' => 0],
            'exams' => [], 'readers' => [], 'release_rate' => null,
        ];

        $patients = [];
        foreach ($studies as $r) {
            $st = $r['status'];
            if (isset($s['status'][$st])) $s['status'][$st]++;
            if ($r['priority_key'] !== 'routine') $s['stat']++;
            $s['exposures'] += count($r['exams']);
            foreach ($r['exams'] as $e) {
                $s['exams'][$e] = ($s['exams'][$e] ?? 0) + 1;
            }
            $key = trim((string) ($r['patient_code'] ?? '')) ?: strtolower(trim((string) $r['patient_name']));
            if ($key !== '') $patients[$key] = true;
        }
        $s['patients'] = count($patients);
        if ($s['received'] > 0) {
            $s['release_rate'] = $s['status']['released'] / $s['received'] * 100;
        }
        arsort($s['exams']);

        $tats = [];
        $hits = 0;
        foreach ($released as $r) {
            $who = trim((string) ($r['radiologist_name'] ?? '')) ?: 'Unassigned';
            $s['readers'][$who]['count'] = ($s['readers'][$who]['count'] ?? 0) + 1;
            $s['readers'][$who]['exams'] = ($s['readers'][$who]['exams'] ?? 0) + count($r['exams']);
            if ($r['tat_hours'] === null) continue;

            $h = $r['tat_hours'];
            $tats[] = $h;
            $s['readers'][$who]['tat'][] = $h;
            if ($r['within_target']) $hits++;

            if ($h < 2)       $s['tat_buckets']['< 2 h']++;
            elseif ($h < 8)   $s['tat_buckets']['2–8 h']++;
            elseif ($h < 24)  $s['tat_buckets']['8–24 h']++;
            elseif ($h < 72)  $s['tat_buckets']['1–3 days']++;
            else              $s['tat_buckets']['> 3 days']++;
        }

        if ($tats) {
            sort($tats);
            $n = count($tats);
            $s['tat_median']    = $n % 2 ? $tats[intdiv($n, 2)] : ($tats[$n / 2 - 1] + $tats[$n / 2]) / 2;
            $s['tat_avg']       = array_sum($tats) / $n;
            $s['within_target'] = $hits / $n * 100;
        }

        foreach ($s['readers'] as &$rd) {
            $t = $rd['tat'] ?? [];
            $rd['tat_avg'] = $t ? array_sum($t) / count($t) : null;
            unset($rd['tat']);
        }
        unset($rd);
        uasort($s['readers'], fn($a, $b) => $b['count'] <=> $a['count']);

        return $s;
    }

    /** Received vs released per day (or per hour for a single day). */
    private function dailyVolume(array $studies, array $released, string $from, string $to): array
    {
        $hourly  = $from === $to;
        $buckets = [];

        if ($hourly) {
            for ($h = 7; $h <= 19; $h++) {
                $buckets[sprintf('%02d', $h)] = ['label' => date('g A', mktime($h, 0, 0)), 'received' => 0, 'released' => 0];
            }
        } else {
            for ($d = $from; $d <= $to; $d = date('Y-m-d', strtotime($d . ' +1 day'))) {
                $buckets[$d] = ['label' => date('M j', strtotime($d)), 'received' => 0, 'released' => 0];
            }
        }

        $add = function (string $when, string $field) use (&$buckets, $hourly) {
            $ts  = strtotime($when);
            $key = $hourly ? date('H', $ts) : date('Y-m-d', $ts);
            if (!isset($buckets[$key])) {
                if (!$hourly) return; // released row outside the window's days
                $buckets[$key] = ['label' => date('g A', $ts), 'received' => 0, 'released' => 0];
            }
            $buckets[$key][$field]++;
        };

        // A single day has one exam date, so spread it by the time paid.
        foreach ($studies as $r)  $add($hourly ? (string) $r['paid_at'] : (string) $r['request_date'], 'received');
        foreach ($released as $r) $add((string) $r['released_at'], 'released');
        ksort($buckets);

        return ['hourly' => $hourly, 'buckets' => array_values($buckets)];
    }

    /** Every unreleased study as of now, oldest first, with its age in hours. */
    private function backlog(string $reader = ''): array
    {
        $builder = (new DiagnosticRequestModel())
            ->where('type', DiagnosticRequestModel::TYPE_XRAY)
            ->whereIn('status', [
                DiagnosticRequestModel::STATUS_IN_PROGRESS,
                DiagnosticRequestModel::STATUS_DRAFT,
                DiagnosticRequestModel::STATUS_COMPLETED,
            ]);

        if ($reader !== '') {
            // Untouched studies have no reader yet but are everyone's queue.
            $builder->groupStart()
                    ->where('radiologist_name', $reader)
                    ->orGroupStart()
                        ->where('status', DiagnosticRequestModel::STATUS_IN_PROGRESS)
                        ->where('radiologist_id', null)
                        ->groupStart()->where('radiologist_name', null)->orWhere('radiologist_name', '')->groupEnd()
                    ->groupEnd()
                    ->groupEnd();
        }

        $rows  = $this->decorateStudies($builder->orderBy('created_at', 'ASC')->findAll());
        $aging = ['< 24 h' => 0, '1–3 days' => 0, '> 3 days' => 0];
        $over  = 0;

        foreach ($rows as &$r) {
            $r['age_hours'] = max(0, (time() - strtotime((string) $r['paid_at'])) / 3600);
            $r['overdue']   = $r['age_hours'] > self::TAT_TARGETS[$r['priority_key']];
            if ($r['overdue']) $over++;
            if ($r['age_hours'] < 24)     $aging['< 24 h']++;
            elseif ($r['age_hours'] < 72) $aging['1–3 days']++;
            else                          $aging['> 3 days']++;
        }
        unset($r);
        usort($rows, fn($a, $b) => $b['age_hours'] <=> $a['age_hours']);

        return ['rows' => $rows, 'aging' => $aging, 'overdue' => $over];
    }

    /** Study log for the window as a CSV download. */
    private function reportCsv(array $studies, string $from, string $to)
    {
        $fh = fopen('php://temp', 'r+');
        fwrite($fh, "\xEF\xBB\xBF"); // BOM so Excel reads ñ correctly
        fputcsv($fh, ['Exam Date', 'Reference', 'Patient Code', 'Patient', 'Age', 'Sex', 'Examinations', 'Exposures',
                      'Priority', 'Status', 'Radiologist', 'Images', 'Received (paid)', 'Released', 'TAT (hours)', 'Within Target']);

        foreach ($studies as $r) {
            fputcsv($fh, [
                $r['request_date'],
                $r['reference_number'],
                $r['patient_code'] ?? '',
                $r['patient_name'],
                $r['age'] ?? '',
                $r['gender'] ?? '',
                implode('; ', $r['exams']),
                count($r['exams']),
                ucfirst($r['priority_key']),
                ucwords(str_replace('_', ' ', $r['status'])),
                $r['radiologist_name'] ?? '',
                $r['image_count'],
                $r['paid_at'] ?? '',
                $r['released_at'] ?? '',
                $r['tat_hours'] === null ? '' : number_format($r['tat_hours'], 1, '.', ''),
                $r['within_target'] === null ? '' : ($r['within_target'] ? 'Yes' : 'No'),
            ]);
        }

        rewind($fh);
        $csv = stream_get_contents($fh);
        fclose($fh);

        $name = 'radiology-report_' . $from . ($from !== $to ? '_to_' . $to : '') . '.csv';

        return $this->response
            ->setHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $name . '"')
            ->setBody($csv);
    }

    public function printResult($id)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $model = new DiagnosticRequestModel();
        $examination = $this->findXray($id);

        if (!$examination) {
            return redirect()->to(base_url('radiologist/examinations'))
                            ->with('error', 'Examination not found');
        }

        $data['examination'] = $examination;

        $radiologistName = trim((string) ($examination['radiologist_name'] ?? ''));

        if ($radiologistName === '') {
            $radiologistName = trim((string) (session()->get('full_name') ?? ''));
        }

        $data['radiologist_license'] = '';

        if ($radiologistName !== '') {
            $userModel = new UserModel();
            $radiologistUser = $userModel
                ->where('full_name', $radiologistName)
                ->where('role', 'radiologist')
                ->first();

            if ($radiologistUser && !empty($radiologistUser['prc_license'])) {
                $data['radiologist_license'] = trim((string) $radiologistUser['prc_license']);
            }
        }

        return view('Radiologist/print_result', $data);
    }

    /**
     * Save the Findings / Impression text that came along with an image
     * upload or removal, as a draft. Same rules as saveDraft(): the status
     * is never moved backwards and the radiologist is credited on first save.
     * Does nothing if the form did not send the text or it is unchanged.
     */
    private function keepTypedReport(array $examination): void
    {
        $findings       = $this->request->getPost('findings');
        $interpretation = $this->request->getPost('interpretation');

        if ($findings === null && $interpretation === null) {
            return;
        }

        $update = [];
        if ($findings !== null && (string) $findings !== (string) ($examination['findings'] ?? '')) {
            $update['findings'] = (string) $findings;
        }
        if ($interpretation !== null && (string) $interpretation !== (string) ($examination['interpretation'] ?? '')) {
            $update['interpretation'] = (string) $interpretation;
        }
        if ($update === []) {
            return;
        }

        if (($examination['status'] ?? '') === DiagnosticRequestModel::STATUS_PENDING) {
            $update['status'] = DiagnosticRequestModel::STATUS_IN_PROGRESS;
        }
        if (empty($examination['radiologist_name'])) {
            $update['radiologist_name'] = PaymentModel::currentCashierName();
            $update['radiologist_id']   = PaymentModel::currentStaffId();
        }

        (new DiagnosticRequestModel())->update((int) $examination['id'], $update);
    }

    public function uploadImage($id)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $model       = new DiagnosticRequestModel();
        $examination = $this->findXray($id);

        if (!$examination) {
            return redirect()->back()->with('error', 'Examination not found.');
        }

        // Payment precondition — a study that hasn't been paid cannot
        // have images uploaded against it.
        if (!$this->requestIsPaid((int) $id, DiagnosticRequestModel::TYPE_XRAY)) {
            return redirect()->back()->with('error', 'This study has not been paid yet.');
        }

        if (($examination['status'] ?? '') === 'released') {
            return redirect()->back()->with('error', 'This report has already been released. Images can no longer be changed.');
        }

        // Uploading / removing an image reloads the page; keep whatever
        // was typed in Findings / Impression instead of losing it.
        $this->keepTypedReport($examination);

        $files = $this->request->getFileMultiple('xray_images');

        if (empty($files) || !is_array($files)) {
            return redirect()->back()->with('error', 'No images selected.');
        }

        $existing = [];
        if (!empty($examination['image_paths'])) {
            $decoded = json_decode($examination['image_paths'], true);
            if (is_array($decoded)) {
                $existing = array_values(array_filter($decoded, 'is_string'));
            }
        } elseif (!empty($examination['image_path'])) {
            $existing = [$examination['image_path']];
        }

        $remainingSlots = 5 - count($existing);
        if ($remainingSlots <= 0) {
            return redirect()->back()->with('error', 'This study already has the maximum of 5 images. Remove one before uploading another.');
        }

        $allowedMime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $allowedExt  = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        $uploadDir = FCPATH . 'uploads/xray';

        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
            log_message('error', 'Upload directory could not be created: ' . $uploadDir);
            return redirect()->back()->with('error', 'Server could not prepare the upload directory.');
        }

        $added   = [];
        $skipped = 0;

        foreach ($files as $file) {
            if (count($added) >= $remainingSlots) {
                $skipped++;
                continue;
            }

            if (!$file || !$file->isValid() || $file->hasMoved()) {
                $skipped++;
                continue;
            }

            $mime = $file->getMimeType();
            $ext  = strtolower($file->getExtension());

            if (!in_array($mime, $allowedMime, true) || !in_array($ext, $allowedExt, true)) {
                $skipped++;
                continue;
            }

            $newName = 'xray_' . $id . '_' . bin2hex(random_bytes(6)) . '.' . $ext;

            try {
                $file->move($uploadDir, $newName);
            } catch (\Exception $e) {
                log_message('error', 'Move failed for examination ' . $id . ': ' . $e->getMessage());
                $skipped++;
                continue;
            }

            $added[] = 'uploads/xray/' . $newName;
        }

        if (empty($added)) {
            return redirect()->back()->with('error', 'No valid image was uploaded. Accepted formats: JPEG, PNG, GIF, WEBP.');
        }

        $allPaths = array_merge($existing, $added);

        $model->update($id, [
            'image_path'  => $allPaths[0],
            'image_paths' => json_encode(array_values($allPaths)),
        ]);

        $count = count($added);
        $msg   = $count === 1
            ? 'Image uploaded successfully.'
            : $count . ' images uploaded successfully.';

        if ($skipped > 0) {
            $msg .= ' ' . $skipped . ' file' . ($skipped === 1 ? ' was' : 's were') . ' skipped (invalid type or over the 5-image limit).';
        }

        return redirect()->back()->with('success', $msg);
    }

    public function removeImage($id)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $path = (string) $this->request->getPost('image_path');
        if ($path === '') {
            return redirect()->back()->with('error', 'No image specified.');
        }

        $model       = new DiagnosticRequestModel();
        $examination = $this->findXray($id);

        if (!$examination) {
            return redirect()->back()->with('error', 'Examination not found.');
        }

        if (!$this->requestIsPaid((int) $id, DiagnosticRequestModel::TYPE_XRAY)) {
            return redirect()->back()->with('error', 'This study has not been paid yet.');
        }

        if (($examination['status'] ?? '') === 'released') {
            return redirect()->back()->with('error', 'This report has already been released. Images can no longer be changed.');
        }

        // Uploading / removing an image reloads the page; keep whatever
        // was typed in Findings / Impression instead of losing it.
        $this->keepTypedReport($examination);

        $paths = [];
        if (!empty($examination['image_paths'])) {
            $decoded = json_decode($examination['image_paths'], true);
            if (is_array($decoded)) {
                $paths = array_values(array_filter($decoded, 'is_string'));
            }
        } elseif (!empty($examination['image_path'])) {
            $paths = [$examination['image_path']];
        }

        // Only ever delete a file this examination actually references.
        if (!in_array($path, $paths, true)) {
            return redirect()->back()->with('error', 'That image does not belong to this examination.');
        }

        $paths = array_values(array_filter($paths, static fn ($p) => $p !== $path));

        $normalized = str_replace('\\', '/', $path);
        if (str_starts_with($normalized, 'uploads/xray/') && !str_contains($normalized, '..')) {
            $full = FCPATH . $normalized;
            if (is_file($full)) {
                @unlink($full);
            }
        }

        $model->update($id, [
            'image_path'  => $paths[0] ?? null,
            'image_paths' => empty($paths) ? null : json_encode($paths),
        ]);

        return redirect()->back()->with('success', 'Image removed.');
    }

    public function releaseResult($id)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        try {
            $model       = new DiagnosticRequestModel();
            $examination = $this->findXray($id);

            if (!$examination) {
                return redirect()->to(base_url('radiologist/examinations'))
                                ->with('error', 'Examination not found');
            }

            // Payment precondition.
            if (!$this->requestIsPaid((int) $id, DiagnosticRequestModel::TYPE_XRAY)) {
                return redirect()->to(base_url('radiologist/examinations'))
                                ->with('error', 'This study has not been paid yet.');
            }

            if ($examination['status'] === 'released') {
                return redirect()->to(base_url('radiologist/examination/view/' . $id))
                                ->with('error', 'This result has already been released.');
            }

            $missing = [];
            if (trim((string) ($examination['findings'] ?? '')) === '')       { $missing[] = 'Findings'; }
            if (trim((string) ($examination['interpretation'] ?? '')) === '') { $missing[] = 'Impression'; }

            if (!empty($missing)) {
                return redirect()->to(base_url('radiologist/examination/view/' . $id))
                                ->with('error', 'Cannot release: ' . implode(' and ', $missing) . ' must be filled first.');
            }

            $update = [
                'status'      => 'released',
                'released_at' => date('Y-m-d H:i:s'),
            ];

            if (empty($examination['radiologist_name'])) {
                $update['radiologist_name'] = PaymentModel::currentCashierName();
                $update['radiologist_id']   = PaymentModel::currentStaffId();
            }

            $model->update($id, $update);

            return redirect()->to(base_url('radiologist/examinations'))
                            ->with('success', 'Result released. The receptionist can now print it.');

        } catch (\Exception $e) {
            log_message('error', 'Release result error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error releasing result');
        }
    }
}