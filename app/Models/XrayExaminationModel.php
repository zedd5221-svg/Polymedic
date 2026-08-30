<?php

namespace App\Models;

use CodeIgniter\Model;

class XrayExaminationModel extends Model
{
    protected $table = 'xray_examinations';
    protected $primaryKey = 'id';
    
    protected $allowedFields = [
        'appointment_id', 'patient_name', 'patient_id', 'age', 'gender',
        'email', 'phone', 'exam_type', 'exam_date', 'doctor_name', 
        'radiologist_name', 'priority', 'status', 'image_path', 
        'findings', 'interpretation', 'released_at'
    ];
    
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    
    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_RELEASED = 'released';
    
    /**
     * Get all examinations with optional status filter
     */
    public function getExaminations($status = null, $limit = null)
    {
        if ($status) {
            $this->where('status', $status);
        }
        if ($limit) {
            $this->limit($limit);
        }
        return $this->orderBy('exam_date', 'DESC')
                    ->orderBy('created_at', 'DESC')
                    ->findAll();
    }
    
    /**
     * Get pending examinations (for radiologist dashboard)
     */
    public function getPendingExaminations()
    {
        return $this->where('status', self::STATUS_PENDING)
                    ->orWhere('status', self::STATUS_PROCESSING)
                    ->orderBy('exam_date', 'ASC')
                    ->orderBy('created_at', 'ASC')
                    ->findAll();
    }
    
    /**
     * Get examinations by appointment ID
     */
    public function getByAppointment($appointmentId)
    {
        return $this->where('appointment_id', $appointmentId)->first();
    }
    
    /**
     * Format exam type from JSON array to readable string
     */
    private function formatExamType($xrayServices)
    {
        if (empty($xrayServices)) {
            return 'X-Ray Examination';
        }
        
        // If it's already a string, return it
        if (is_string($xrayServices) && !empty($xrayServices)) {
            // Check if it's a JSON array
            if (strpos($xrayServices, '[') === 0) {
                $decoded = json_decode($xrayServices, true);
                if (is_array($decoded)) {
                    return $this->formatExamTypeArray($decoded);
                }
            }
            return $xrayServices;
        }
        
        // If it's an array
        if (is_array($xrayServices)) {
            return $this->formatExamTypeArray($xrayServices);
        }
        
        return 'X-Ray Examination';
    }
    
    /**
     * Format array of services to readable string
     */
    private function formatExamTypeArray($services)
    {
        if (empty($services)) {
            return 'X-Ray Examination';
        }
        
        // Clean each service - remove JSON escape characters
        $cleaned = array_map(function($service) {
            // Remove JSON escape slashes
            $service = str_replace('\/', '/', $service);
            $service = str_replace('\\/', '/', $service);
            // Remove any other JSON artifacts
            $service = stripslashes($service);
            return trim($service);
        }, $services);
        
        return implode(', ', $cleaned);
    }
    
    /**
     * Create examination from appointment data
     */
    public function createFromAppointment($appointment)
    {
        // Decode xray services
        $xrayServices = json_decode($appointment['xray_services'], true) ?? [];
        
        // Format exam type
        $examType = $this->formatExamType($xrayServices);
        
        // Check if already exists
        $existing = $this->where('appointment_id', $appointment['id'])->first();
        if ($existing) {
            return $existing;
        }
        
        $data = [
            'appointment_id' => $appointment['id'],
            'patient_name' => $appointment['full_name'],
            'age' => $appointment['age'],
            'gender' => $appointment['gender'],
            'email' => $appointment['email'] ?? null,
            'phone' => $appointment['phone'] ?? null,
            'exam_type' => $examType,
            'exam_date' => $appointment['appointment_date'],
            'priority' => 'Routine',
            'status' => self::STATUS_PENDING
        ];
        
        $this->insert($data);
        return $this->where('appointment_id', $appointment['id'])->first();
    }
    
    /**
     * Update examination status
     */
    public function updateStatus($id, $status)
    {
        $data = ['status' => $status];
        if ($status === self::STATUS_RELEASED) {
            $data['released_at'] = date('Y-m-d H:i:s');
        }
        return $this->update($id, $data);
    }
    
    /**
     * Save findings and interpretation
     */
    public function saveFindings($id, $findings, $interpretation)
    {
        return $this->update($id, [
            'findings' => $findings,
            'interpretation' => $interpretation,
            'status' => self::STATUS_COMPLETED
        ]);
    }
    
    /**
     * Get counts for dashboard
     */
    public function getCounts()
    {
        return [
            'pending' => $this->where('status', self::STATUS_PENDING)->countAllResults(),
            'processing' => $this->where('status', self::STATUS_PROCESSING)->countAllResults(),
            'completed' => $this->where('status', self::STATUS_COMPLETED)->countAllResults(),
            'released' => $this->where('status', self::STATUS_RELEASED)->countAllResults(),
            'total' => $this->countAll()
        ];
    }

    /**
     * Get weekly volume data for the last 7 days
     * Returns data grouped by day and service type
     */
    public function getWeeklyVolumeData()
    {
        $db = \Config\Database::connect();
        
        // Get the start of the week (Monday)
        $weekStart = date('Y-m-d', strtotime('monday this week'));
        $weekEnd = date('Y-m-d', strtotime('sunday this week'));
        
        // Get all examinations for this week with their exam_type
        $examinations = $this
            ->where('DATE(exam_date) >=', $weekStart)
            ->where('DATE(exam_date) <=', $weekEnd)
            ->orderBy('exam_date', 'ASC')
            ->findAll();
        
        // Initialize data structure for 7 days (Mon-Sun)
        $days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        $modalities = [];
        
        // Process each examination
        foreach ($examinations as $exam) {
            // Get the day of week (0=Mon, 6=Sun)
            $dayIndex = date('N', strtotime($exam['exam_date'])) - 1;
            $dayName = $days[$dayIndex];
            
            // Determine the modality/service type from exam_type
            $modality = $this->determineModality($exam['exam_type']);
            
            // Initialize modality if not exists
            if (!isset($modalities[$modality])) {
                $modalities[$modality] = array_fill(0, 7, 0);
            }
            
            // Increment the count for this day
            $modalities[$modality][$dayIndex]++;
        }
        
        // Format for Chart.js
        $datasets = [];
        $colors = [
            'X-Ray' => '#1D4ED8',
            'CT' => '#0E7490',
            'MRI' => '#7c3aed',
            'Ultrasound' => '#16a34a',
            'Mammography' => '#f59e0b',
            'Other' => '#94A3B8'
        ];
        
        foreach ($modalities as $modality => $data) {
            $datasets[] = [
                'label' => $modality,
                'data' => $data,
                'backgroundColor' => $colors[$modality] ?? '#94A3B8',
                'borderRadius' => 4,
                'barPercentage' => 0.6
            ];
        }
        
        // If no data, return empty datasets with sample structure
        if (empty($datasets)) {
            $datasets = [
                [
                    'label' => 'X-Ray',
                    'data' => [0, 0, 0, 0, 0, 0, 0],
                    'backgroundColor' => '#1D4ED8',
                    'borderRadius' => 4,
                    'barPercentage' => 0.6
                ]
            ];
        }
        
        return [
            'labels' => $days,
            'datasets' => $datasets
        ];
    }
    
    /**
     * Determine modality from exam_type string
     */
    public function determineModality($examType)
    {
        if (empty($examType)) {
            return 'Other';
        }
        
        $examTypeLower = strtolower($examType);
        
        // Check for specific modalities
        if (strpos($examTypeLower, 'ct') !== false || strpos($examTypeLower, 'cat scan') !== false) {
            return 'CT';
        }
        if (strpos($examTypeLower, 'mri') !== false) {
            return 'MRI';
        }
        if (strpos($examTypeLower, 'ultrasound') !== false || strpos($examTypeLower, 'us') !== false || strpos($examTypeLower, 'sono') !== false) {
            return 'Ultrasound';
        }
        if (strpos($examTypeLower, 'mammo') !== false) {
            return 'Mammography';
        }
        if (strpos($examTypeLower, 'x-ray') !== false || strpos($examTypeLower, 'xray') !== false || strpos($examTypeLower, 'chest') !== false) {
            return 'X-Ray';
        }
        
        // Try to find from common service names
        $commonServices = [
            'chest' => 'X-Ray',
            'abdomen' => 'X-Ray',
            'spine' => 'X-Ray',
            'extremity' => 'X-Ray',
            'skull' => 'X-Ray',
            'bone' => 'X-Ray',
            'joint' => 'X-Ray'
        ];
        
        foreach ($commonServices as $keyword => $modality) {
            if (strpos($examTypeLower, $keyword) !== false) {
                return $modality;
            }
        }
        
        return 'Other';
    }
}