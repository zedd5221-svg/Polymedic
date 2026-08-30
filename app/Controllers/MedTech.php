<?php

namespace App\Controllers;

use App\Models\AppointmentModel;
use App\Models\LabRequestModel;
use App\Models\LabResultModel;
use App\Models\NotificationModel;

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

    // =============================================
    // DASHBOARD
    // =============================================
    public function dashboard()
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $labRequestModel = new LabRequestModel();
        $labResultModel = new LabResultModel();
        
        // ===== 1. GET COUNTS =====
        $data['counts'] = $labRequestModel->getCounts();
        
        // ===== 2. PAGINATION FOR SPECIMEN QUEUE =====
        $perPage = 5;
        $page = $this->request->getGet('page') ?? 1;
        $offset = ($page - 1) * $perPage;
        
        $data['allRequests'] = $labRequestModel
            ->orderBy('created_at', 'DESC')
            ->limit($perPage, $offset)
            ->findAll();
        
        $data['totalRequests'] = $labRequestModel->countAll();
        $data['currentPage'] = (int)$page;
        $data['perPage'] = $perPage;
        $data['totalPages'] = ceil($data['totalRequests'] / $perPage);
        $data['pendingRequests'] = $labRequestModel->getPendingRequests();
        $data['recentRequests'] = $labRequestModel->getRequests(null, 10);
        $data['todayCompleted'] = $labRequestModel
            ->where('DATE(updated_at)', date('Y-m-d'))
            ->where('status', LabRequestModel::STATUS_COMPLETED)
            ->countAllResults();
        
        // ===== 3. SPECIMEN INTAKE DATA (Hourly for today - 24 HOURS) =====
        $data['intakeData'] = $this->getHourlyIntake($labRequestModel);
        
        // ===== 4. SECTION BREAKDOWN DATA =====
        $data['sectionData'] = $this->getSectionBreakdown($labRequestModel);
        
        // ===== 5. TURNAROUND TIME DATA =====
        $data['turnaroundData'] = $this->getTurnaroundTimes($labRequestModel, $labResultModel);
        
        return view('MedTech/dashboard', $data);
    }
    
    /**
     * Get hourly specimen intake for today - 24 HOURS (00:00 to 23:00)
     */
    private function getHourlyIntake($labRequestModel)
    {
        $today = date('Y-m-d');
        $hours = [];
        $counts = [];
        
        // Generate hours from 00:00 to 23:00 (24 hours)
        for ($h = 0; $h <= 23; $h++) {
            $hourStr = str_pad($h, 2, '0', STR_PAD_LEFT) . ':00';
            $hours[] = $hourStr;
            
            // Count requests for this hour
            $startTime = $today . ' ' . str_pad($h, 2, '0', STR_PAD_LEFT) . ':00:00';
            $endTime = $today . ' ' . str_pad($h, 2, '0', STR_PAD_LEFT) . ':59:59';
            
            $count = $labRequestModel
                ->where('DATE(created_at)', $today)
                ->where('TIME(created_at) >=', date('H:i:s', strtotime($startTime)))
                ->where('TIME(created_at) <=', date('H:i:s', strtotime($endTime)))
                ->countAllResults();
            
            $counts[] = $count;
        }
        
        return [
            'labels' => $hours,
            'data' => $counts
        ];
    }
    
    /**
     * Get section breakdown of tests
     */
    private function getSectionBreakdown($labRequestModel)
    {
        $today = date('Y-m-d');
        
        // Get all requests for today
        $requests = $labRequestModel
            ->where('DATE(created_at)', $today)
            ->findAll();
        
        // Define section mapping based on keywords in lab_services
        $sectionMap = [
            'Hematology' => [
                'cbc', 'hemoglobin', 'hematocrit', 'rbc', 'wbc', 'platelet', 
                'complete blood', 'blood count', 'blood typing', 'complete blood count',
                'hb', 'hct', 'mcv', 'mch', 'mchc', 'rdw', 'neutrophils', 'lymphocytes',
                'monocytes', 'eosinophils', 'basophils', 'blood typing'
            ],
            'Chemistry' => [
                'glucose', 'creatinine', 'bun', 'sodium', 'potassium', 'chloride', 
                'calcium', 'chemistry', 'blood chemistry', 'blood uric acid', 'uric acid', 
                'hba1c', 'triglycerides', 'cholesterol', 'lipid', 'lipid profile',
                'total cholesterol', 'hdl', 'ldl', 'vldl', 'albumin', 'bilirubin',
                'alkaline phosphatase', 'alt', 'ast', 'sgpt', 'sgot', 'protein',
                'electrolytes', 'bicarbonate', 'magnesium', 'phosphorus', 'blood urea nitrogen'
            ],
            'Urinalysis' => [
                'urinalysis', 'urine', 'ua', 'specific gravity', 'ph',
                'protein urine', 'glucose urine', 'ketones', 'urobilinogen',
                'bilirubin urine', 'nitrite', 'leukocyte esterase'
            ],
            'Microbiology' => [
                'culture', 'sensitivity', 'gram stain', 'microbiology',
                'blood culture', 'urine culture', 'sputum culture', 'stool culture',
                'afb', 'gram', 'fungal culture', 'bacterial culture'
            ],
            'Serology' => [
                'serology', 'antibody', 'antigen', 'hiv', 'hepatitis', 'syphilis', 
                't. pallidum', 'vdrl', 'rpr', 'anti-hcv', 'hbsag', 'anti-hbs',
                'anti-hiv', 'dengue', 'igg', 'igm', 'rheumatoid factor', 'aso'
            ],
            'Coagulation' => [
                'coagulation', 'pt', 'aptt', 'inr', 'bleeding time',
                'clotting time', 'prothrombin time', 'partial thromboplastin time'
            ],
            'Thyroid' => [
                'thyroid', 't3', 't4', 'ft3', 'ft4', 'tsh',
                'thyroid panel', 'thyroid function', 't3 uptake', 'free t4'
            ]
        ];
        
        $sectionCounts = [];
        foreach ($sectionMap as $section => $keywords) {
            $sectionCounts[$section] = 0;
        }
        $sectionCounts['Other'] = 0;
        
        foreach ($requests as $request) {
            $labServices = strtolower($request['lab_services'] ?? '');
            $matched = false;
            
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
        
        // Remove sections with 0 count and sort by count descending
        $sectionCounts = array_filter($sectionCounts);
        arsort($sectionCounts);
        
        return [
            'labels' => array_keys($sectionCounts),
            'data' => array_values($sectionCounts)
        ];
    }
    
    /**
     * Get turnaround times for different test types
     */
    private function getTurnaroundTimes($labRequestModel, $labResultModel)
    {
        // Define test types and their target times (in minutes)
        $testTypes = [
            'cbc' => ['label' => 'CBC', 'target' => 60],
            'urinalysis' => ['label' => 'Urinalysis', 'target' => 60],
            'blood_chemistry' => ['label' => 'Blood Chemistry', 'target' => 120],
            'lipid_profile' => ['label' => 'Lipid Profile', 'target' => 120],
            'serology' => ['label' => 'Serology', 'target' => 120],
            'thyroid' => ['label' => 'Thyroid', 'target' => 120],
            'microbiology' => ['label' => 'Microbiology', 'target' => 180]
        ];
        
        $result = [];
        
        // Get all released requests with valid timestamps
        $releasedRequests = $labRequestModel
            ->where('status', LabRequestModel::STATUS_RELEASED)
            ->where('released_at IS NOT NULL')
            ->findAll();
        
        foreach ($testTypes as $key => $testType) {
            $totalMinutes = 0;
            $count = 0;
            
            foreach ($releasedRequests as $request) {
                $labServices = strtolower($request['lab_services'] ?? '');
                $isMatch = false;
                
                // Check if this request contains the test type
                $searchTerms = explode(' ', $testType['label']);
                foreach ($searchTerms as $term) {
                    if (strlen($term) > 2 && strpos($labServices, strtolower($term)) !== false) {
                        $isMatch = true;
                        break;
                    }
                }
                
                // Also check for alternative names
                $alternatives = [
                    'CBC' => ['cbc', 'complete blood count', 'complete blood'],
                    'Urinalysis' => ['urinalysis', 'urine', 'ua'],
                    'Blood Chemistry' => ['blood chemistry', 'chemistry', 'glucose', 'creatinine', 'bun', 'blood urea nitrogen'],
                    'Lipid Profile' => ['lipid profile', 'lipid', 'cholesterol', 'triglycerides'],
                    'Serology' => ['serology', 'syphilis', 'hiv', 'hepatitis'],
                    'Thyroid' => ['thyroid', 't3', 't4', 'tsh'],
                    'Microbiology' => ['microbiology', 'culture', 'sensitivity']
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
                        $created = strtotime($request['created_at']);
                        $released = strtotime($request['released_at']);
                        
                        // Only count if released > created (valid turnaround)
                        if ($released > $created) {
                            $minutes = round(($released - $created) / 60);
                            $totalMinutes += $minutes;
                            $count++;
                        }
                    }
                }
            }
            
            $avgMinutes = $count > 0 ? round($totalMinutes / $count) : 0;
            $target = $testType['target'];
            
            // Determine color based on avg vs target
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
                'name' => $testType['label'],
                'avg' => $avgMinutes,
                'target' => $target,
                'color' => $color
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

        $labRequestModel = new LabRequestModel();
        $status = $this->request->getGet('status');
        
        $data['requests'] = $labRequestModel->getRequests($status);
        $data['currentStatus'] = $status;
        $data['counts'] = $labRequestModel->getCounts();
        
        return view('MedTech/requests', $data);
    }

    // =============================================
    // VIEW LAB REQUEST
    // =============================================
    public function viewRequest($id)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $labRequestModel = new LabRequestModel();
        $labResultModel = new LabResultModel();
        $appointmentModel = new AppointmentModel();
        
        $data['request'] = $labRequestModel->find($id);
        
        if (!$data['request']) {
            return redirect()->to(base_url('medtech/requests'))
                            ->with('error', 'Laboratory request not found');
        }
        
        $data['appointment'] = $appointmentModel->find($data['request']['appointment_id']);
        $data['results'] = $labResultModel->getByRequest($id);
        $data['templates'] = $labResultModel->getTestTemplates();
        
        $data['statusClass'] = [
            'pending' => 'warning',
            'in_progress' => 'primary',
            'draft' => 'secondary',
            'completed' => 'success',
            'released' => 'info'
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
        $templates = $labResultModel->getTestTemplates();
        
        $data = $templates[$template] ?? [];
        
        return $this->response->setJSON($data);
    }

    // =============================================
    // SAVE RESULTS
    // =============================================
    public function saveResults($id)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $labRequestModel = new LabRequestModel();
        $labResultModel = new LabResultModel();
        
        $request = $labRequestModel->find($id);
        
        if (!$request) {
            return redirect()->to(base_url('medtech/requests'))
                            ->with('error', 'Laboratory request not found');
        }
        
        $testNames = $this->request->getPost('test_name') ?? [];
        $results = $this->request->getPost('result') ?? [];
        $units = $this->request->getPost('unit') ?? [];
        $ranges = $this->request->getPost('reference_range') ?? [];
        $flags = $this->request->getPost('flag') ?? [];
        
        $resultData = [];
        foreach ($testNames as $index => $testName) {
            if (!empty($testName)) {
                $resultData[] = [
                    'test_name' => $testName,
                    'result' => $results[$index] ?? '',
                    'unit' => $units[$index] ?? '',
                    'reference_range' => $ranges[$index] ?? '',
                    'flag' => $flags[$index] ?? 'normal'
                ];
            }
        }
        
        $labResultModel->saveResults($id, $resultData);
        $labRequestModel->update($id, ['status' => LabRequestModel::STATUS_IN_PROGRESS]);
        
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

        $labRequestModel = new LabRequestModel();
        $request = $labRequestModel->find($id);
        
        if (!$request) {
            return redirect()->to(base_url('medtech/requests'))
                            ->with('error', 'Laboratory request not found');
        }
        
        $findings = $this->request->getPost('findings');
        $remarks = $this->request->getPost('remarks');
        $action = $this->request->getPost('action') ?? 'save';
        
        $status = LabRequestModel::STATUS_DRAFT;
        if ($action === 'complete') {
            $status = LabRequestModel::STATUS_COMPLETED;
        }
        
        $labRequestModel->update($id, [
            'findings' => $findings,
            'remarks' => $remarks,
            'status' => $status
        ]);
        
        $message = $action === 'complete' ? 'Laboratory findings completed!' : 'Findings saved as draft!';
        
        return redirect()->to(base_url('medtech/request/view/' . $id))
                        ->with('success', $message);
    }

    // =============================================
    // RELEASE RESULT
    // =============================================
    public function releaseResult($id)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $labRequestModel = new LabRequestModel();
        $labResultModel = new LabResultModel();
        
        $request = $labRequestModel->find($id);
        
        if (!$request) {
            return redirect()->to(base_url('medtech/requests'))
                            ->with('error', 'Laboratory request not found');
        }
        
        $results = $labResultModel->getByRequest($id);
        if (empty($results)) {
            return redirect()->back()->with('error', 'Please enter test results first');
        }
        
        $labRequestModel->updateStatus($id, LabRequestModel::STATUS_RELEASED);
        
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
    // RELEASE DIRECTLY (WITH VALIDATION)
    // =============================================
    public function releaseDirectly($id)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $labRequestModel = new LabRequestModel();
        $labResultModel = new LabResultModel();
        
        $request = $labRequestModel->find($id);
        
        if (!$request) {
            return redirect()->to(base_url('medtech/requests'))
                            ->with('error', 'Laboratory request not found');
        }
        
        $testNames = $this->request->getPost('test_name') ?? [];
        $results = $this->request->getPost('result') ?? [];
        $units = $this->request->getPost('unit') ?? [];
        $ranges = $this->request->getPost('reference_range') ?? [];
        $flags = $this->request->getPost('flag') ?? [];
        $findings = $this->request->getPost('findings');
        $remarks = $this->request->getPost('remarks');
        
        $errors = [];
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
            $errors[] = "Please enter at least one test result";
        }
        
        if (empty($findings)) {
            $errors[] = "Findings / Interpretation is required";
        }
        
        if (!empty($errors)) {
            return redirect()->back()
                            ->withInput()
                            ->with('validation_errors', $errors)
                            ->with('error', 'Please fill in all required fields before releasing');
        }
        
        $resultData = [];
        foreach ($testNames as $index => $testName) {
            if (!empty($testName)) {
                $resultData[] = [
                    'test_name' => $testName,
                    'result' => $results[$index] ?? '',
                    'unit' => $units[$index] ?? '',
                    'reference_range' => $ranges[$index] ?? '',
                    'flag' => $flags[$index] ?? 'normal'
                ];
            }
        }
        
        $labResultModel->saveResults($id, $resultData);
        $labRequestModel->update($id, [
            'findings' => $findings,
            'remarks' => $remarks,
            'status' => LabRequestModel::STATUS_RELEASED,
            'released_at' => date('Y-m-d H:i:s')
        ]);
        
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
    // PRINT RESULT (PDF)
    // =============================================
    public function printResult($id)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $labRequestModel = new LabRequestModel();
        $labResultModel = new LabResultModel();
        $appointmentModel = new AppointmentModel();
        
        $data['request'] = $labRequestModel->find($id);
        
        if (!$data['request']) {
            return redirect()->to(base_url('medtech/requests'))
                            ->with('error', 'Laboratory request not found');
        }
        
        $data['appointment'] = $appointmentModel->find($data['request']['appointment_id']);
        $data['results'] = $labResultModel->getByRequest($id);
        
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
        $notifications = $notificationModel
            ->orderBy('created_at', 'DESC')
            ->findAll();
        
        $data['notifications'] = $notifications;
        $data['unread_count'] = $notificationModel->getUnreadCount();
        $data['total_count'] = count($notifications);
        
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
            $labRequestModel = new LabRequestModel();
            $appointmentModel = new AppointmentModel();
            
            $counts = $labRequestModel->getCounts();
            $data['counts'] = [
                'pending' => $counts['pending'] ?? 0,
                'in_progress' => $counts['in_progress'] ?? 0,
                'draft' => $counts['draft'] ?? 0,
                'completed' => $counts['completed'] ?? 0,
                'released' => $counts['released'] ?? 0,
                'total' => $counts['total'] ?? 0
            ];
            
            $totalCount = $data['counts']['pending'] + $data['counts']['in_progress'] + $data['counts']['draft'] + $data['counts']['completed'] + $data['counts']['released'];
            $data['totalCount'] = $totalCount > 0 ? $totalCount : 1;
            $data['requests'] = $labRequestModel->getRequests() ?? [];
            
            $monthlyStats = $labRequestModel
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
                            'pending' => 0,
                            'in_progress' => 0,
                            'draft' => 0,
                            'completed' => 0,
                            'released' => 0,
                            'total' => 0
                        ];
                    }
                    $status = $stat['status'] ?? 'pending';
                    $count = (int)($stat['total'] ?? 0);
                    
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
            
            $recentReports = $labRequestModel
                ->orderBy('created_at', 'DESC')
                ->limit(10)
                ->findAll();
            
            $formattedReports = [];
            if (!empty($recentReports)) {
                foreach ($recentReports as $report) {
                    $serviceDetails = json_decode($report['service_details'] ?? '[]', true);
                    $testNames = [];
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
                    $testDisplay = !empty($testNames) ? implode(', ', array_slice($testNames, 0, 3)) . (count($testNames) > 3 ? ' +' . (count($testNames) - 3) . ' more' : '') : 'Lab Test';
                    
                    $formattedReports[] = [
                        'id' => $report['id'] ?? 0,
                        'patient_name' => $report['patient_name'] ?? 'Unknown',
                        'age' => $report['age'] ?? 'N/A',
                        'test_name' => $testDisplay,
                        'status' => $report['status'] ?? 'pending',
                        'requested_at' => $report['created_at'] ?? date('Y-m-d H:i:s'),
                        'released_at' => $report['released_at'] ?? null,
                        'technologist' => session()->get('full_name') ?? 'John Doe'
                    ];
                }
            }
            $data['recentReports'] = $formattedReports;
            
            $statusLabels = ['Pending', 'In Progress', 'Draft', 'Completed', 'Released'];
            $statusData = [
                $data['counts']['pending'],
                $data['counts']['in_progress'],
                $data['counts']['draft'],
                $data['counts']['completed'],
                $data['counts']['released']
            ];
            $statusColors = ['#C2410C', '#1D4ED8', '#6c757d', '#15803D', '#0d9488'];
            
            $data['statusLabels'] = json_encode($statusLabels);
            $data['statusData'] = json_encode($statusData);
            $data['statusColors'] = json_encode($statusColors);
            
            return view('MedTech/reports', $data);
            
        } catch (\Exception $e) {
            log_message('error', 'MedTech reports error: ' . $e->getMessage());
            log_message('error', 'File: ' . $e->getFile() . ' Line: ' . $e->getLine());
            
            $data = [
                'counts' => ['pending' => 0, 'in_progress' => 0, 'draft' => 0, 'completed' => 0, 'released' => 0, 'total' => 0],
                'totalCount' => 1,
                'requests' => [],
                'monthlyStats' => [],
                'totalLabAppointments' => 0,
                'recentReports' => [],
                'statusLabels' => json_encode(['Pending', 'In Progress', 'Draft', 'Completed', 'Released']),
                'statusData' => json_encode([0, 0, 0, 0, 0]),
                'statusColors' => json_encode(['#C2410C', '#1D4ED8', '#6c757d', '#15803D', '#0d9488'])
            ];
            
            return view('MedTech/reports', $data);
        }
    }
}