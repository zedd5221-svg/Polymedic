<?php

namespace App\Controllers;

use App\Models\AppointmentModel;
use App\Models\DiagnosticRequestModel;
use App\Models\LabResultModel;
use App\Models\NotificationModel;
use App\Models\PaymentModel;
use App\Models\ServiceModel;
use App\Models\UserModel;

class MedTech extends BaseController
{
    private function checkAuth()
    {
        if (!session()->get('is_logged_in')) {
            return redirect()->to(base_url('login'));
        }

        $role = session()->get('role');
        if ($role !== 'med_tech' && $role !== 'technologist') {
            return redirect()->to((new NotificationModel())->dashboardFor($role));
        }
        return null;
    }

    /** A laboratory request by id; X-ray rows are never reachable from here. */
    private function findLab($id): ?array
    {
        return (new DiagnosticRequestModel())
            ->where('id', (int) $id)
            ->where('type', DiagnosticRequestModel::TYPE_LAB)
            ->first();
    }

    /** Who processed the request: set once, by the first med tech to save it. */
    private function stampTechnologist(array $request, array $update): array
    {
        if (trim((string) ($request['med_tech_name'] ?? '')) === '') {
            $update['med_tech_name'] = PaymentModel::currentCashierName();
            $update['med_tech_id']   = PaymentModel::currentStaffId();
        }
        return $update;
    }

    private function departmentStatuses(): array
    {
        return [
            DiagnosticRequestModel::STATUS_IN_PROGRESS,
            DiagnosticRequestModel::STATUS_DRAFT,
            DiagnosticRequestModel::STATUS_COMPLETED,
            DiagnosticRequestModel::STATUS_RELEASED,
        ];
    }

    /**
     * ------------------------------------------------------------------
     * Payment precondition helper.
     *
     * The receptionist's "Start" button is the only entry point that
     * writes a payments row. Every department-side writer must check
     * this first, otherwise the diagnostic request can advance past
     * `pending` without any money being recorded — exactly the bug
     * that created the historical orphan rows.
     * ------------------------------------------------------------------
     */
    private function requestIsPaid(int $id, string $type = DiagnosticRequestModel::TYPE_LAB): bool
    {
        return !empty((new PaymentModel())->getByRequest($id, $type));
    }

    // =============================================
    // DASHBOARD
    // =============================================
    public function dashboard()
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $labRequestModel = new DiagnosticRequestModel();
        $labResultModel  = new LabResultModel();
        $type            = DiagnosticRequestModel::TYPE_LAB;

        // pending = paid but no med tech has saved anything on it yet
        $data['counts'] = $labRequestModel->getDepartmentCounts($type);

        $perPage = 5;
        $page    = max(1, (int) ($this->request->getGet('page') ?? 1));
        $offset  = ($page - 1) * $perPage;

        $departmentStatuses = $this->departmentStatuses();

        $labRequestModel->resetQuery();
        $data['allRequests'] = $labRequestModel
            ->where('type', $type)
            ->whereIn('status', $departmentStatuses)
            ->orderBy('created_at', 'DESC')
            ->limit($perPage, $offset)
            ->findAll();
        $data['allRequests'] = DiagnosticRequestModel::withDepartmentStatus($data['allRequests']);

        $labRequestModel->resetQuery();
        $data['totalRequests'] = $labRequestModel
            ->where('type', $type)
            ->whereIn('status', $departmentStatuses)
            ->countAllResults();

        $data['currentPage'] = (int) $page;
        $data['perPage']     = $perPage;
        $data['totalPages']  = ceil($data['totalRequests'] / $perPage);

        $labRequestModel->resetQuery();
        $data['pendingRequests'] = $labRequestModel
            ->where('type', $type)
            ->whereIn('status', [
                DiagnosticRequestModel::STATUS_IN_PROGRESS,
                DiagnosticRequestModel::STATUS_DRAFT,
            ])
            ->orderBy('request_date', 'ASC')
            ->orderBy('created_at', 'ASC')
            ->findAll();
        $data['pendingRequests'] = DiagnosticRequestModel::withDepartmentStatus($data['pendingRequests']);

        $labRequestModel->resetQuery();
        $data['recentRequests'] = $labRequestModel
            ->where('type', $type)
            ->whereIn('status', $departmentStatuses)
            ->orderBy('created_at', 'DESC')
            ->limit(10)
            ->findAll();
        $data['recentRequests'] = DiagnosticRequestModel::withDepartmentStatus($data['recentRequests']);

        $labRequestModel->resetQuery();
        $data['todayCompleted'] = $labRequestModel
            ->where('type', $type)
            ->where('DATE(updated_at)', date('Y-m-d'))
            ->where('status', DiagnosticRequestModel::STATUS_COMPLETED)
            ->countAllResults();

        $data['intakeData']      = $this->getHourlyIntake($labRequestModel);
        $data['intakeMonthData'] = $this->getDailyIntakeMonth($labRequestModel);
        $data['sectionData']     = $this->getSectionBreakdown($labRequestModel);
        $data['turnaroundData']  = $this->getTurnaroundTimes($labRequestModel, $labResultModel);
        $data['serviceCatalog']  = $this->getAvailableServices();

        $data['kpiTrends'] = $this->getKpiTrends(14, (int) ($data['counts']['total'] ?? 0));

        return view('MedTech/dashboard', $data);
    }

    private function getKpiTrends(int $days, int $totalRequests): array
    {
        $db    = db_connect();
        $start = date('Y-m-d', strtotime('-' . ($days - 1) . ' days'));
        $end   = date('Y-m-d');
        $type  = DiagnosticRequestModel::TYPE_LAB;

        $dates = [];
        for ($i = 0; $i < $days; $i++) {
            $dates[] = date('Y-m-d', strtotime($start . ' +' . $i . ' days'));
        }

        $series = function (string $dateExpr, string $valueExpr, array $where = [], array $whereIn = []) use ($db, $dates, $start, $end, $type) {
            $b = $db->table('diagnostic_requests')
                ->select("$dateExpr AS d, $valueExpr AS v", false)
                ->where('type', $type)
                ->where("$dateExpr >=", $start)
                ->where("$dateExpr <=", $end);
            foreach ($where as $col => $val) {
                // a null value means $col is a raw SQL condition
                $val === null ? $b->where($col, null, false) : $b->where($col, $val);
            }
            foreach ($whereIn as $col => $vals) {
                $b->whereIn($col, $vals);
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
        $running  = $totalRequests - array_sum($newTotal);
        foreach ($newTotal as $n) {
            $running += $n;
            $total[]  = $running;
        }

        $pending     = $series('DATE(created_at)', 'COUNT(*)', [DiagnosticRequestModel::untouchedSql($type) => null]);
        $inProgress  = $series('DATE(created_at)', 'COUNT(*)', ['status' => 'in_progress', 'NOT ' . DiagnosticRequestModel::untouchedSql($type) => null]);
        $draft       = $series('DATE(created_at)', 'COUNT(*)', ['status' => 'draft']);
        $completed   = $series('DATE(updated_at)', 'COUNT(*)', ['status' => 'completed']);
        $released    = $series('DATE(released_at)', 'COUNT(*)', ['status' => 'released']);

        $inQueue = [];
        for ($i = 0; $i < $days; $i++) {
            $inQueue[] = ($pending[$i] ?? 0) + ($inProgress[$i] ?? 0) + ($draft[$i] ?? 0);
            $inProgress[$i] = ($inProgress[$i] ?? 0) + ($draft[$i] ?? 0);   // drafts are in progress too
        }

        return [
            'dates'       => $dates,
            'total'       => $total,
            'pending'     => $pending,
            'in_progress' => $inProgress,
            'draft'       => $draft,
            'completed'   => $completed,
            'released'    => $released,
            'in_queue'    => $inQueue,
        ];
    }

    /** Today's lab intake per hour (00:00..23:00), one query. */
    private function getHourlyIntake($labRequestModel)
    {
        $today = date('Y-m-d');

        $rows = db_connect()->table('diagnostic_requests')
            ->select('HOUR(created_at) AS h, COUNT(*) AS n', false)
            ->where('type', DiagnosticRequestModel::TYPE_LAB)
            ->where('created_at >=', $today . ' 00:00:00')
            ->where('created_at <=', $today . ' 23:59:59')
            ->groupBy('h')
            ->get()->getResultArray();
        $byHour = array_column($rows, 'n', 'h');

        $hours  = [];
        $counts = [];
        for ($h = 0; $h <= 23; $h++) {
            $hours[]  = str_pad((string) $h, 2, '0', STR_PAD_LEFT) . ':00';
            $counts[] = (int) ($byHour[$h] ?? 0);
        }

        return [
            'labels' => $hours,
            'data'   => $counts,
        ];
    }

    /** This month's lab intake per day, one query. */
    private function getDailyIntakeMonth($labRequestModel)
    {
        $days = (int) date('t');

        $rows = db_connect()->table('diagnostic_requests')
            ->select('DAY(created_at) AS d, COUNT(*) AS n', false)
            ->where('type', DiagnosticRequestModel::TYPE_LAB)
            ->where('created_at >=', date('Y-m-01 00:00:00'))
            ->where('created_at <=', date('Y-m-t 23:59:59'))
            ->groupBy('d')
            ->get()->getResultArray();
        $byDay = array_column($rows, 'n', 'd');

        $labels = [];
        $counts = [];
        for ($day = 1; $day <= $days; $day++) {
            $labels[] = (string) $day;
            $counts[] = (int) ($byDay[$day] ?? 0);
        }

        return [
            'labels' => $labels,
            'data'   => $counts,
        ];
    }

    private function getAvailableServices()
    {
        $serviceModel = new ServiceModel();

        $labServices  = $serviceModel->getLaboratoryServices();
        $xrayServices = $serviceModel->getXrayServices();
        $counts       = $serviceModel->getCountByCategory();

        return [
            'lab'        => $labServices,
            'xray'       => $xrayServices,
            'labCount'   => $counts['laboratory'],
            'xrayCount'  => $counts['xray'],
            'otherCount' => $counts['other'],
            'total'      => $counts['total'],
        ];
    }

    private function getSectionBreakdown($labRequestModel)
    {
        $today = date('Y-m-d');
        $type  = DiagnosticRequestModel::TYPE_LAB;

        $labRequestModel->resetQuery();
        $requests = $labRequestModel
            ->where('type', $type)
            ->where('DATE(created_at)', $today)
            ->findAll();

        $sectionMap = [
            'Hematology' => [
                'complete blood count', 'cbc',
                'platelet count', 'platelet',
                'esr',
                'hemoglobin', 'hgb',
                'hematocrit', 'hct',
                'blood typing',
                'rbc', 'wbc',
                'mcv', 'mch', 'mchc', 'rdw',
                'neutrophils', 'lymphocytes', 'monocytes',
                'eosinophils', 'basophils',
            ],
            'Chemistry' => [
                'glucose', 'rbs', 'fbs',
                'hba1c',
                'cholesterol', 'triglycerides',
                'hdl', 'ldl', 'vldl',
                'creatinine', 'blood uric acid', 'uric acid',
                'blood urea nitrogen', 'bun',
                'sgpt', 'alt', 'sgot', 'ast',
                'sodium', 'potassium', 'chloride', 'calcium',
                'electrolytes', 'albumin', 'bilirubin',
                'alkaline phosphatase', 'protein',
                'bicarbonate', 'magnesium', 'phosphorus',
                'lipid profile', 'lipid',
            ],
            'Urinalysis' => [
                'urinalysis', 'urine',
                'specific gravity', 'ketones', 'urobilinogen',
                'nitrite', 'leukocyte esterase',
            ],
            'Microscopy' => [
                'fecalysis', 'fecal',
                'semenanalysis', 'semen',
            ],
            'Serology' => [
                'hbsag', 'hepatitis', 'anti-hbs',
                'anti-hcv', 'hcv',
                'hiv', 'anti-hiv',
                'salmonella', 'typhi',
                'syphilis', 't. pallidum',
                'vdrl', 'rpr',
                'dengue', 'igg', 'igm',
                'rheumatoid factor', 'aso',
                'antibody', 'antigen',
            ],
            'Thyroid' => [
                'thyroid', 'thyroid panel', 'thyroid function',
                't3 uptake', 'free t3', 'ft3',
                'free t4', 'ft4',
                'tsh', 't3', 't4',
            ],
            'Microbiology' => [
                'culture', 'sensitivity', 'gram stain',
                'blood culture', 'urine culture',
                'sputum culture', 'stool culture',
                'afb', 'fungal culture', 'bacterial culture',
            ],
            'Coagulation' => [
                'coagulation',
                'prothrombin time', 'pt',
                'partial thromboplastin time', 'aptt',
                'inr', 'bleeding time', 'clotting time',
            ],
        ];

        $sectionCounts = [];
        foreach ($sectionMap as $section => $keywords) {
            $sectionCounts[$section] = 0;
        }
        $sectionCounts['Other'] = 0;

        foreach ($requests as $request) {
            $labServices = strtolower($request['lab_services'] ?? '');
            $matched     = false;

            foreach ($sectionMap as $section => $keywords) {
                foreach ($keywords as $keyword) {
                    if (strpos($labServices, $keyword) !== false) {
                        $sectionCounts[$section]++;
                        $matched = true;
                        break 2;
                    }
                }
            }

            if (!$matched) {
                $sectionCounts['Other']++;
            }
        }

        $sectionCounts = array_filter($sectionCounts);
        arsort($sectionCounts);

        return [
            'labels' => array_keys($sectionCounts),
            'data'   => array_values($sectionCounts),
        ];
    }

    private function getTurnaroundTimes($labRequestModel, $labResultModel)
    {
        $testTypes = [
            'cbc'             => ['label' => 'CBC',             'target' => 60],
            'urinalysis'      => ['label' => 'Urinalysis',      'target' => 60],
            'blood_chemistry' => ['label' => 'Blood Chemistry', 'target' => 120],
            'lipid_profile'   => ['label' => 'Lipid Profile',   'target' => 120],
            'serology'        => ['label' => 'Serology',        'target' => 120],
            'thyroid'         => ['label' => 'Thyroid',         'target' => 120],
            'microbiology'    => ['label' => 'Microbiology',    'target' => 180],
        ];

        $result = [];
        $type   = DiagnosticRequestModel::TYPE_LAB;

        $labRequestModel->resetQuery();
        $releasedRequests = $labRequestModel
            ->where('type', $type)
            ->where('status', DiagnosticRequestModel::STATUS_RELEASED)
            ->where('released_at IS NOT NULL')
            ->findAll();

        foreach ($testTypes as $key => $testType) {
            $totalMinutes = 0;
            $count        = 0;

            foreach ($releasedRequests as $request) {
                $labServices = strtolower($request['lab_services'] ?? '');
                $isMatch     = false;

                $searchTerms = explode(' ', $testType['label']);
                foreach ($searchTerms as $term) {
                    if (strlen($term) > 2 && strpos($labServices, strtolower($term)) !== false) {
                        $isMatch = true;
                        break;
                    }
                }

                $alternatives = [
                    'CBC'             => ['cbc', 'complete blood count', 'complete blood'],
                    'Urinalysis'      => ['urinalysis', 'urine', 'ua'],
                    'Blood Chemistry' => ['blood chemistry', 'chemistry', 'glucose', 'creatinine', 'bun', 'blood urea nitrogen'],
                    'Lipid Profile'   => ['lipid profile', 'lipid', 'cholesterol', 'triglycerides'],
                    'Serology'        => ['serology', 'syphilis', 'hiv', 'hepatitis'],
                    'Thyroid'         => ['thyroid', 't3', 't4', 'tsh'],
                    'Microbiology'    => ['microbiology', 'culture', 'sensitivity'],
                ];

                if (!$isMatch && isset($alternatives[$testType['label']])) {
                    foreach ($alternatives[$testType['label']] as $alt) {
                        if (strpos($labServices, $alt) !== false) {
                            $isMatch = true;
                            break;
                        }
                    }
                }

                if ($isMatch) {
                    if (!empty($request['created_at']) && !empty($request['released_at'])) {
                        $created  = strtotime($request['created_at']);
                        $released = strtotime($request['released_at']);

                        if ($released > $created) {
                            $minutes = round(($released - $created) / 60);
                            $totalMinutes += $minutes;
                            $count++;
                        }
                    }
                }
            }

            $avgMinutes = $count > 0 ? round($totalMinutes / $count) : 0;
            $target     = $testType['target'];

            if ($avgMinutes === 0) {
                $color = 'gray';
            } elseif ($avgMinutes <= $target) {
                $color = 'green';
            } elseif ($avgMinutes <= $target * 1.3) {
                $color = 'orange';
            } else {
                $color = 'red';
            }

            $result[] = [
                'name'   => $testType['label'],
                'avg'    => $avgMinutes,
                'target' => $target,
                'color'  => $color,
            ];
        }

        return $result;
    }

    // =============================================
    // LABORATORY REQUESTS LIST
    // =============================================
    public function requests()
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $labRequestModel = new DiagnosticRequestModel();
        $status          = $this->request->getGet('status');
        $type            = DiagnosticRequestModel::TYPE_LAB;

        $departmentStatuses = $this->departmentStatuses();

        $labRequestModel->resetQuery();
        $data['requests'] = DiagnosticRequestModel::withDepartmentStatus(
            $labRequestModel
                ->where('type', $type)
                ->whereIn('status', $departmentStatuses)
                ->orderBy('request_date', 'DESC')
                ->orderBy('created_at', 'DESC')
                ->findAll()
        );

        // ?status= filters on the department view (pending = untouched)
        $allowed = array_merge(['pending'], $departmentStatuses);
        if ($status !== null && $status !== '' && in_array($status, $allowed, true)) {
            $data['requests'] = array_values(array_filter(
                $data['requests'],
                static fn ($r) => in_array($status, DiagnosticRequestModel::statusTags($r['status']), true)
            ));
        }
        $data['currentStatus'] = $status;
        $data['counts']        = $labRequestModel->getDepartmentCounts($type);

        return view('MedTech/requests', $data);
    }

    // =============================================
    // VIEW LAB REQUEST
    // =============================================
    public function viewRequest($id)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $labRequestModel  = new DiagnosticRequestModel();
        $labResultModel   = new LabResultModel();
        $appointmentModel = new AppointmentModel();

        $request = $this->findLab($id);
        if (!$request) {
            return redirect()->to(base_url('medtech/requests'))
                            ->with('error', 'Laboratory request not found');
        }

        $savedResults = $labResultModel->getByRequest($id);

        $rows = $labResultModel->mergeTemplateWithSaved(
            $request['lab_services'] ?? '',
            $savedResults
        );

        [$data['request']]   = DiagnosticRequestModel::withDepartmentStatus([$request]);
        // find(null) would return every appointment, so walk-ins get null.
        $data['appointment'] = !empty($request['appointment_id']) ? $appointmentModel->find($request['appointment_id']) : null;
        $data['results']     = $rows;
        $data['statusClass'] = [
            'pending'     => 'warning',
            'in_progress' => 'primary',
            'draft'       => 'secondary',
            'completed'   => 'success',
            'released'    => 'info',
        ];

        // Pass a flag so the view can disable inputs when the request
        // is not yet paid. This is the same invariant the controller
        // enforces, surfaced at the UI level so the tech never wastes
        // time typing results they cannot save.
        $data['isPaid'] = $this->requestIsPaid((int) $id, DiagnosticRequestModel::TYPE_LAB);

        return view('MedTech/view_request', $data);
    }

    // =============================================
    // GET TEST TEMPLATE (AJAX)
    // =============================================
    public function getTemplate($template)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $labResultModel = new LabResultModel();
        $templates      = $labResultModel->getTestTemplates();

        $data = $templates[$template] ?? [];

        return $this->response->setJSON($data);
    }

    // =============================================
    // SAVE RESULTS (draft / in_progress)
    // =============================================
    public function saveResults($id)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $labRequestModel = new DiagnosticRequestModel();
        $labResultModel  = new LabResultModel();

        $request = $this->findLab($id);
        if (!$request) {
            return redirect()->to(base_url('medtech/requests'))
                            ->with('error', 'Laboratory request not found');
        }

        // Payment precondition: a request that has not been paid must
        // never leave `pending`. The receptionist's Start button is the
        // only writer of payments rows.
        if (!$this->requestIsPaid((int) $id, DiagnosticRequestModel::TYPE_LAB)) {
            return redirect()->to(base_url('medtech/requests'))
                            ->with('error', 'This request has not been paid yet. '
                                . 'The receptionist must click Start before results can be entered.');
        }

        $testNames = $this->request->getPost('test_name') ?? [];
        $results   = $this->request->getPost('result') ?? [];
        $units     = $this->request->getPost('unit') ?? [];
        $ranges    = $this->request->getPost('reference_range') ?? [];
        $flags     = $this->request->getPost('flag') ?? [];

        $resultData = [];
        foreach ($testNames as $index => $testName) {
            if (!empty($testName)) {
                $resultData[] = [
                    'test_name'       => $testName,
                    'result'          => $results[$index] ?? '',
                    'unit'            => $units[$index] ?? '',
                    'reference_range' => $ranges[$index] ?? '',
                    'flag'            => $flags[$index] ?? 'normal',
                ];
            }
        }

        $labResultModel->saveResults($id, $resultData, false);

        // Only advance a `pending` request to `in_progress`. Never
        // overwrite a later status (completed, released, draft).
        $update = $request['status'] === DiagnosticRequestModel::STATUS_PENDING
            ? ['status' => DiagnosticRequestModel::STATUS_IN_PROGRESS]
            : [];
        $update = $this->stampTechnologist($request, $update);
        if ($update !== []) {
            $labRequestModel->update($id, $update);
        }

        return redirect()->to(base_url('medtech/request/view/' . $id))
                        ->with('success', 'Results saved successfully!');
    }

    // =============================================
    // SAVE FINDINGS & REMARKS
    // =============================================
    public function saveFindings($id)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $labRequestModel = new DiagnosticRequestModel();
        $request         = $this->findLab($id);

        if (!$request) {
            return redirect()->to(base_url('medtech/requests'))
                            ->with('error', 'Laboratory request not found');
        }

        // Payment precondition.
        if (!$this->requestIsPaid((int) $id, DiagnosticRequestModel::TYPE_LAB)) {
            return redirect()->to(base_url('medtech/requests'))
                            ->with('error', 'This request has not been paid yet. '
                                . 'The receptionist must click Start before findings can be saved.');
        }

        $findings = $this->request->getPost('findings');
        $remarks  = $this->request->getPost('remarks');
        $action   = $this->request->getPost('action') ?? 'save';

        $status = DiagnosticRequestModel::STATUS_DRAFT;
        if ($action === 'complete') {
            $status = DiagnosticRequestModel::STATUS_COMPLETED;
        }

        $labRequestModel->update($id, $this->stampTechnologist($request, [
            'findings' => $findings,
            'remarks'  => $remarks,
            'status'   => $status,
        ]));

        $message = $action === 'complete'
            ? 'Laboratory findings completed!'
            : 'Findings saved as draft!';

        return redirect()->to(base_url('medtech/request/view/' . $id))
                        ->with('success', $message);
    }

    // =============================================
    // RELEASE RESULT (simple path)
    // =============================================
    public function releaseResult($id)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $labRequestModel = new DiagnosticRequestModel();
        $labResultModel  = new LabResultModel();

        $request = $this->findLab($id);
        if (!$request) {
            return redirect()->to(base_url('medtech/requests'))
                            ->with('error', 'Laboratory request not found');
        }

        // Payment precondition.
        if (!$this->requestIsPaid((int) $id, DiagnosticRequestModel::TYPE_LAB)) {
            return redirect()->to(base_url('medtech/requests'))
                            ->with('error', 'This request has not been paid yet.');
        }

        $results = $labResultModel->getByRequest($id);
        if (empty($results)) {
            return redirect()->back()->with('error', 'Please enter test results first');
        }

        $labRequestModel->update($id, $this->stampTechnologist($request, [
            'status'      => DiagnosticRequestModel::STATUS_RELEASED,
            'released_at' => date('Y-m-d H:i:s'),
        ]));

        NotificationModel::notify(
            'lab',
            'Laboratory Result Released',
            'Laboratory result released for patient ' . $request['patient_name'],
            $id,
            'medtech/request/view/' . $id
        );

        return redirect()->to(base_url('medtech/request/view/' . $id))
                        ->with('success', 'Result released successfully!');
    }

    // =============================================
    // RELEASE DIRECTLY (the main form handler)
    // =============================================
    public function releaseDirectly($id)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $labRequestModel = new DiagnosticRequestModel();
        $labResultModel  = new LabResultModel();

        $request = $this->findLab($id);
        if (!$request) {
            return redirect()->to(base_url('medtech/requests'))
                            ->with('error', 'Laboratory request not found');
        }

        // Payment precondition.
        if (!$this->requestIsPaid((int) $id, DiagnosticRequestModel::TYPE_LAB)) {
            return redirect()->to(base_url('medtech/requests'))
                            ->with('error', 'This request has not been paid yet. '
                                . 'The receptionist must click Start before results can be released.');
        }

        $action = (string) ($this->request->getPost('submit_action') ?? 'save');
        if (!in_array($action, ['save', 'complete', 'release'], true)) {
            $action = 'save';
        }

        $testNames = $this->request->getPost('test_name') ?? [];
        $results   = $this->request->getPost('result') ?? [];
        $units     = $this->request->getPost('unit') ?? [];
        $ranges    = $this->request->getPost('reference_range') ?? [];
        $flags     = $this->request->getPost('flag') ?? [];
        $findings  = $this->request->getPost('findings');
        $remarks   = $this->request->getPost('remarks');

        $resultData = [];
        foreach ($testNames as $index => $testName) {
            if (!empty($testName)) {
                $resultData[] = [
                    'test_name'       => $testName,
                    'result'          => $results[$index] ?? '',
                    'unit'            => $units[$index] ?? '',
                    'reference_range' => $ranges[$index] ?? '',
                    'flag'            => $flags[$index] ?? 'normal',
                ];
            }
        }

        if ($action === 'release') {
            $errors  = [];
            $hasData = false;

            foreach ($testNames as $index => $testName) {
                if (!empty($testName)) {
                    $hasData = true;
                    if (empty($results[$index]) && $results[$index] !== '0') {
                        $errors[] = "Result for '{$testName}' is required";
                    }
                    if (empty($units[$index])) {
                        $errors[] = "Unit for '{$testName}' is required";
                    }
                    if (empty($ranges[$index])) {
                        $errors[] = "Reference range for '{$testName}' is required";
                    }
                }
            }

            if (!$hasData) {
                $errors[] = 'Please enter at least one test result';
            }
            if (empty($findings)) {
                $errors[] = 'Findings / Interpretation is required';
            }

            if (!empty($errors)) {
                return redirect()->back()
                                ->withInput()
                                ->with('validation_errors', $errors)
                                ->with('error', 'Please fill in all required fields before releasing');
            }
        }

        $replaceAll = ($action === 'release');
        $labResultModel->saveResults($id, $resultData, $replaceAll);

        $status = DiagnosticRequestModel::STATUS_DRAFT;
        $update = [
            'findings' => $findings,
            'remarks'  => $remarks,
        ];

        if ($action === 'complete') {
            $status = DiagnosticRequestModel::STATUS_COMPLETED;
        } elseif ($action === 'release') {
            $status = DiagnosticRequestModel::STATUS_RELEASED;
            $update['released_at'] = date('Y-m-d H:i:s');
        } else {
            // "save" — preserve the current status if it's already
            // something more advanced than draft.
            if (in_array($request['status'], [
                DiagnosticRequestModel::STATUS_COMPLETED,
                DiagnosticRequestModel::STATUS_RELEASED,
            ], true)) {
                $status = $request['status'];
            } elseif ($request['status'] === DiagnosticRequestModel::STATUS_IN_PROGRESS) {
                $status = DiagnosticRequestModel::STATUS_DRAFT;
            }
        }

        $update['status'] = $status;
        $labRequestModel->update($id, $this->stampTechnologist($request, $update));

        if ($action === 'release') {
            NotificationModel::notify(
                'lab',
                'Laboratory Result Released',
                'Laboratory result released for patient ' . $request['patient_name'],
                $id,
                'medtech/request/view/' . $id
            );

            return redirect()->to(base_url('medtech/request/view/' . $id))
                            ->with('success', 'Result released successfully!');
        }

        if ($action === 'complete') {
            return redirect()->to(base_url('medtech/request/view/' . $id))
                            ->with('success', 'Laboratory findings completed!');
        }

        return redirect()->to(base_url('medtech/request/view/' . $id))
                        ->with('success', 'Saved as draft.');
    }

    // =============================================
    // PRINT RESULT
    // =============================================
    public function printResult($id)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $data['request'] = $this->findLab($id);
        if (!$data['request']) {
            return redirect()->to(base_url('medtech/requests'))
                            ->with('error', 'Laboratory request not found');
        }

        $userModel = new UserModel();

        $data['appointment'] = !empty($data['request']['appointment_id'])
            ? (new AppointmentModel())->find($data['request']['appointment_id'])
            : null;
        $data['results']     = (new LabResultModel())->getByRequest($id);

        // Sign with the technologist who processed the request; fall back
        // to the one printing it for requests saved before this was tracked.
        $signer = null;
        $name   = trim((string) ($data['request']['med_tech_name'] ?? ''));
        if ($name !== '') {
            $signer = $userModel->where('full_name', $name)->whereIn('role', ['med_tech', 'technologist'])->first();
        }
        $data['technologistUser'] = $signer ?: $userModel->find((int) session()->get('user_id'));
        $data['pathologistUser']  = null;

        return view('MedTech/print_result', $data);
    }

    // =============================================
    // REPORTS
    // =============================================
    public function reports()
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        try {
            $labRequestModel  = new DiagnosticRequestModel();
            $appointmentModel = new AppointmentModel();
            $type             = DiagnosticRequestModel::TYPE_LAB;

            $counts    = $labRequestModel->getDepartmentCounts($type);
            $data['counts'] = [
                'pending'     => $counts['pending']     ?? 0,
                'in_progress' => $counts['in_progress'] ?? 0,
                'draft'       => $counts['draft']       ?? 0,
                'completed'   => $counts['completed']   ?? 0,
                'released'    => $counts['released']    ?? 0,
                'total'       => $counts['total']       ?? 0,
            ];

            $data['counts']['in_progress_only'] = $counts['in_progress_only'] ?? 0;

            $totalCount = $data['counts']['total'];
            $data['totalCount'] = $totalCount > 0 ? $totalCount : 1;

            $labRequestModel->resetQuery();
            $data['requests'] = $labRequestModel
                ->where('type', $type)
                ->whereIn('status', $this->departmentStatuses())
                ->orderBy('request_date', 'DESC')
                ->orderBy('created_at', 'DESC')
                ->findAll();
            $data['requests'] = DiagnosticRequestModel::withDepartmentStatus($data['requests']);

            $labRequestModel->resetQuery();
            $monthlyStats = $labRequestModel
                ->where('type', $type)
                ->select("DATE_FORMAT(created_at, '%Y-%m') AS month, COUNT(*) AS total,
                          CASE WHEN " . DiagnosticRequestModel::untouchedSql($type) . " THEN 'pending' ELSE status END AS dstatus", false)
                ->whereIn('status', $this->departmentStatuses())
                ->groupBy(['month', 'dstatus'])
                ->orderBy('month', 'DESC')
                ->findAll();

            $monthlyData = [];
            if (!empty($monthlyStats)) {
                foreach ($monthlyStats as $stat) {
                    $month = $stat['month'] ?? date('Y-m');
                    if (!isset($monthlyData[$month])) {
                        $monthlyData[$month] = [
                            'pending'     => 0,
                            'in_progress' => 0,
                            'draft'       => 0,
                            'completed'   => 0,
                            'released'    => 0,
                            'total'       => 0,
                        ];
                    }
                    $status = $stat['dstatus'] ?? 'pending';
                    $count  = (int) ($stat['total'] ?? 0);

                    if (isset($monthlyData[$month][$status])) {
                        $monthlyData[$month][$status] = $count;
                        $monthlyData[$month]['total'] += $count;
                    }
                }
                krsort($monthlyData);
            }
            $data['monthlyStats'] = $monthlyData;

            $data['totalLabAppointments'] = $appointmentModel
                ->where('lab_services IS NOT NULL')
                ->where('lab_services !=', '[]')
                ->where('lab_services !=', 'null')
                ->countAllResults();

            $labRequestModel->resetQuery();
            $recentReports = $labRequestModel
                ->where('type', $type)
                ->whereIn('status', $this->departmentStatuses())
                ->orderBy('created_at', 'DESC')
                ->limit(10)
                ->findAll();

            $formattedReports = [];
            if (!empty($recentReports)) {
                foreach ($recentReports as $report) {
                    $testNames = array_values(array_filter(array_map('trim', explode(',', (string) ($report['services'] ?? '')))));

                    $testDisplay = !empty($testNames)
                        ? implode(', ', array_slice($testNames, 0, 3))
                          . (count($testNames) > 3 ? ' +' . (count($testNames) - 3) . ' more' : '')
                        : 'Lab Test';

                    $formattedReports[] = [
                        'id'           => $report['id'] ?? 0,
                        'patient_name' => $report['patient_name'] ?? 'Unknown',
                        'age'          => $report['age'] ?? 'N/A',
                        'test_name'    => $testDisplay,
                        'status'       => DiagnosticRequestModel::departmentStatus($report),
                        'requested_at' => $report['created_at'] ?? date('Y-m-d H:i:s'),
                        'released_at'  => $report['released_at'] ?? null,
                        'technologist' => ($report['med_tech_name'] ?? '') ?: (session()->get('full_name') ?? ''),
                    ];
                }
            }
            $data['recentReports'] = $formattedReports;

            $statusLabels = ['Pending', 'In Progress', 'Draft', 'Completed', 'Released'];
            $statusData   = [
                $data['counts']['pending'],
                $data['counts']['in_progress'],
                $data['counts']['draft'],
                $data['counts']['completed'],
                $data['counts']['released'],
            ];
            $statusColors = ['#C2410C', '#1D4ED8', '#6c757d', '#15803D', '#0d9488'];

            $data['statusLabels'] = json_encode($statusLabels);
            $data['statusData']   = json_encode($statusData);
            $data['statusColors'] = json_encode($statusColors);

            return view('MedTech/reports', $data);

        } catch (\Exception $e) {
            log_message('error', 'MedTech reports error: ' . $e->getMessage());
            log_message('error', 'File: ' . $e->getFile() . ' Line: ' . $e->getLine());

            $data = [
                'counts'               => ['pending' => 0, 'in_progress' => 0, 'draft' => 0, 'completed' => 0, 'released' => 0, 'total' => 0],
                'totalCount'           => 1,
                'requests'             => [],
                'monthlyStats'         => [],
                'totalLabAppointments' => 0,
                'recentReports'        => [],
                'statusLabels'         => json_encode(['Pending', 'In Progress', 'Draft', 'Completed', 'Released']),
                'statusData'           => json_encode([0, 0, 0, 0, 0]),
                'statusColors'         => json_encode(['#C2410C', '#1D4ED8', '#6c757d', '#15803D', '#0d9488']),
            ];

            return view('MedTech/reports', $data);
        }
    }
}