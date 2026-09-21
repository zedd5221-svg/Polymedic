<?php

namespace App\Controllers;

use App\Models\AppointmentModel;
use App\Models\DiagnosticRequestModel;
use App\Models\LabResultModel;
use App\Models\NotificationModel;
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
        if ($role !== 'med_tech' && $role !== 'technologist' && $role !== 'admin') {
            if ($role === 'receptionist') {
                return redirect()->to(base_url('receptionist/dashboard'));
            } elseif ($role === 'admin') {
                return redirect()->to(base_url('admin/dashboard'));
            } elseif ($role === 'radiologist') {
                return redirect()->to(base_url('radiologist/dashboard'));
            }
            return redirect()->to(base_url('login'));
        }
        return null;
    }

    /**
     * Statuses the MedTech is allowed to see in their worklist.
     *
     * 'pending' is deliberately excluded. A walk-in request created
     * by the receptionist stays in the front-desk queue until they
     * press Start, which flips the status to 'in_progress'. Only at
     * that point does the request appear in the lab.
     */
    private function departmentStatuses(): array
    {
        return [
            DiagnosticRequestModel::STATUS_IN_PROGRESS,
            DiagnosticRequestModel::STATUS_DRAFT,
            DiagnosticRequestModel::STATUS_COMPLETED,
            DiagnosticRequestModel::STATUS_RELEASED,
        ];
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

        $data['counts'] = $labRequestModel->getCounts($type);

        $perPage = 5;
        $page    = $this->request->getGet('page') ?? 1;
        $offset  = ($page - 1) * $perPage;

        $departmentStatuses = $this->departmentStatuses();

        $labRequestModel->resetQuery();
        $data['allRequests'] = $labRequestModel
            ->where('type', $type)
            ->whereIn('status', $departmentStatuses)
            ->orderBy('created_at', 'DESC')
            ->limit($perPage, $offset)
            ->findAll();

        $labRequestModel->resetQuery();
        $data['totalRequests'] = $labRequestModel
            ->where('type', $type)
            ->whereIn('status', $departmentStatuses)
            ->countAllResults();

        $data['currentPage'] = (int) $page;
        $data['perPage']     = $perPage;
        $data['totalPages']  = ceil($data['totalRequests'] / $perPage);

        // "Pending" on the MedTech side means sent to the lab but not
        // started here yet. That is in_progress and draft, not the raw
        // pending state.
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

        $labRequestModel->resetQuery();
        $data['recentRequests'] = $labRequestModel
            ->where('type', $type)
            ->whereIn('status', $departmentStatuses)
            ->orderBy('created_at', 'DESC')
            ->limit(10)
            ->findAll();

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

        return view('MedTech/dashboard', $data);
    }

    private function getHourlyIntake($labRequestModel)
    {
        $today  = date('Y-m-d');
        $hours  = [];
        $counts = [];
        $type   = DiagnosticRequestModel::TYPE_LAB;

        for ($h = 0; $h <= 23; $h++) {
            $hourStr = str_pad($h, 2, '0', STR_PAD_LEFT) . ':00';
            $hours[] = $hourStr;

            $startTime = $today . ' ' . str_pad($h, 2, '0', STR_PAD_LEFT) . ':00:00';
            $endTime   = $today . ' ' . str_pad($h, 2, '0', STR_PAD_LEFT) . ':59:59';

            $labRequestModel->resetQuery();
            $count = $labRequestModel
                ->where('type', $type)
                ->where('DATE(created_at)', $today)
                ->where('TIME(created_at) >=', date('H:i:s', strtotime($startTime)))
                ->where('TIME(created_at) <=', date('H:i:s', strtotime($endTime)))
                ->countAllResults();

            $counts[] = $count;
        }

        return [
            'labels' => $hours,
            'data'   => $counts,
        ];
    }

    /**
     * Daily specimen intake for the current calendar month.
     *
     * Returns one entry per day of the month: the day-of-month as a
     * label and the request count for that day as the value. Days
     * with no requests return 0, so the bar chart always shows the
     * full month rather than just the days with activity.
     */
    private function getDailyIntakeMonth($labRequestModel)
    {
        $type  = DiagnosticRequestModel::TYPE_LAB;
        $year  = (int) date('Y');
        $month = (int) date('n');
        $days  = (int) date('t');

        $labels = [];
        $counts = [];

        for ($day = 1; $day <= $days; $day++) {
            $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
            $labels[] = (string) $day;

            $labRequestModel->resetQuery();
            $counts[] = $labRequestModel
                ->where('type', $type)
                ->where('DATE(created_at)', $date)
                ->countAllResults();
        }

        return [
            'labels' => $labels,
            'data'   => $counts,
        ];
    }

    /**
     * Count of active services in the catalogue.
     *
     * Used by the dashboard's "Available services" card so the page
     * reflects what the lab actually offers, not a hard-coded number.
     */
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

    /**
     * Today's volume broken down by laboratory section.
     *
     * Keywords are matched against the request's lab_services string,
     * in the order the sections appear here. Longer / more specific
     * keywords must come before shorter ones inside each section, and
     * sections are ordered so a service lands in its true home.
     *
     * Every service in the `services` table appears below, so nothing
     * falls into "Other" unless it is genuinely unrecognised.
     */
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

        // If the user selected a department-facing status, honour it.
        // Otherwise list everything the lab has been sent, which is
        // all statuses except 'pending' and 'cancelled'.
        if ($status !== null && $status !== '' && in_array($status, $departmentStatuses, true)) {
            $labRequestModel->resetQuery();
            $data['requests'] = $labRequestModel
                ->where('type', $type)
                ->where('status', $status)
                ->orderBy('request_date', 'DESC')
                ->orderBy('created_at', 'DESC')
                ->findAll();
        } else {
            $labRequestModel->resetQuery();
            $data['requests'] = $labRequestModel
                ->where('type', $type)
                ->whereIn('status', $departmentStatuses)
                ->orderBy('request_date', 'DESC')
                ->orderBy('created_at', 'DESC')
                ->findAll();
        }

        $data['currentStatus'] = $status;
        $data['counts']        = $labRequestModel->getCounts($type);

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

        $request = $labRequestModel->find($id);
        if (!$request) {
            return redirect()->to(base_url('medtech/requests'))
                            ->with('error', 'Laboratory request not found');
        }

        $savedResults = $labResultModel->getByRequest($id);

        $rows = $labResultModel->mergeTemplateWithSaved(
            $request['lab_services'] ?? '',
            $savedResults
        );

        $data['request']     = $request;
        $data['appointment'] = $appointmentModel->find($request['appointment_id']);
        $data['results']     = $rows;
        $data['statusClass'] = [
            'pending'     => 'warning',
            'in_progress' => 'primary',
            'draft'       => 'secondary',
            'completed'   => 'success',
            'released'    => 'info',
        ];

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

        $request = $labRequestModel->find($id);
        if (!$request) {
            return redirect()->to(base_url('medtech/requests'))
                            ->with('error', 'Laboratory request not found');
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
        $labRequestModel->update($id, ['status' => DiagnosticRequestModel::STATUS_IN_PROGRESS]);

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
        $request         = $labRequestModel->find($id);

        if (!$request) {
            return redirect()->to(base_url('medtech/requests'))
                            ->with('error', 'Laboratory request not found');
        }

        $findings = $this->request->getPost('findings');
        $remarks  = $this->request->getPost('remarks');
        $action   = $this->request->getPost('action') ?? 'save';

        $status = DiagnosticRequestModel::STATUS_DRAFT;
        if ($action === 'complete') {
            $status = DiagnosticRequestModel::STATUS_COMPLETED;
        }

        $labRequestModel->update($id, [
            'findings' => $findings,
            'remarks'  => $remarks,
            'status'   => $status,
        ]);

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

        $request = $labRequestModel->find($id);
        if (!$request) {
            return redirect()->to(base_url('medtech/requests'))
                            ->with('error', 'Laboratory request not found');
        }

        $results = $labResultModel->getByRequest($id);
        if (empty($results)) {
            return redirect()->back()->with('error', 'Please enter test results first');
        }

        $labRequestModel->updateStatus($id, DiagnosticRequestModel::STATUS_RELEASED);

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

        $request = $labRequestModel->find($id);
        if (!$request) {
            return redirect()->to(base_url('medtech/requests'))
                            ->with('error', 'Laboratory request not found');
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
        }

        $update['status'] = $status;
        $labRequestModel->update($id, $update);

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

        $labRequestModel  = new DiagnosticRequestModel();
        $labResultModel   = new LabResultModel();
        $appointmentModel = new AppointmentModel();
        $userModel        = new UserModel();

        $data['request'] = $labRequestModel->find($id);
        if (!$data['request']) {
            return redirect()->to(base_url('medtech/requests'))
                            ->with('error', 'Laboratory request not found');
        }

        $data['appointment'] = $appointmentModel->find($data['request']['appointment_id']);
        $data['results']     = $labResultModel->getByRequest($id);

        $currentUserId = (int) session()->get('user_id');

        $data['technologistUser'] = $currentUserId > 0
            ? $userModel->find($currentUserId)
            : null;

        $data['pathologistUser'] = null;

        return view('MedTech/print_result', $data);
    }

    // =============================================
    // NOTIFICATIONS
    // =============================================
    public function notifications()
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $notificationModel = new NotificationModel();
        $notifications     = $notificationModel
            ->orderBy('created_at', 'DESC')
            ->findAll();

        $data['notifications'] = $notifications;
        $data['unread_count']  = $notificationModel->getUnreadCount();
        $data['total_count']   = count($notifications);

        return view('MedTech/notifications', $data);
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

            $counts    = $labRequestModel->getCounts($type);
            $data['counts'] = [
                'pending'     => $counts['pending']     ?? 0,
                'in_progress' => $counts['in_progress'] ?? 0,
                'draft'       => $counts['draft']       ?? 0,
                'completed'   => $counts['completed']   ?? 0,
                'released'    => $counts['released']    ?? 0,
                'total'       => $counts['total']       ?? 0,
            ];

            $totalCount = $data['counts']['pending'] + $data['counts']['in_progress']
                        + $data['counts']['draft']   + $data['counts']['completed']
                        + $data['counts']['released'];
            $data['totalCount'] = $totalCount > 0 ? $totalCount : 1;

            // Same rule as the requests page — reports cover only rows
            // that have actually reached the department.
            $labRequestModel->resetQuery();
            $data['requests'] = $labRequestModel
                ->where('type', $type)
                ->whereIn('status', $this->departmentStatuses())
                ->orderBy('request_date', 'DESC')
                ->orderBy('created_at', 'DESC')
                ->findAll();

            $labRequestModel->resetQuery();
            $monthlyStats = $labRequestModel
                ->where('type', $type)
                ->select('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as total, status')
                ->groupBy('month, status')
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
                    $status = $stat['status'] ?? 'pending';
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
                    $serviceDetails = json_decode($report['service_details'] ?? '[]', true);
                    $testNames      = [];

                    if (is_array($serviceDetails) && !empty($serviceDetails)) {
                        foreach ($serviceDetails as $service) {
                            if (is_string($service)) {
                                $testNames[] = $service;
                            } elseif (is_array($service) && isset($service['name'])) {
                                $testNames[] = $service['name'];
                            } elseif (is_array($service) && isset($service['test_name'])) {
                                $testNames[] = $service['test_name'];
                            }
                        }
                    }

                    $testDisplay = !empty($testNames)
                        ? implode(', ', array_slice($testNames, 0, 3))
                          . (count($testNames) > 3 ? ' +' . (count($testNames) - 3) . ' more' : '')
                        : 'Lab Test';

                    $formattedReports[] = [
                        'id'           => $report['id'] ?? 0,
                        'patient_name' => $report['patient_name'] ?? 'Unknown',
                        'age'          => $report['age'] ?? 'N/A',
                        'test_name'    => $testDisplay,
                        'status'       => $report['status'] ?? 'pending',
                        'requested_at' => $report['created_at'] ?? date('Y-m-d H:i:s'),
                        'released_at'  => $report['released_at'] ?? null,
                        'technologist' => session()->get('full_name') ?? 'John Doe',
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