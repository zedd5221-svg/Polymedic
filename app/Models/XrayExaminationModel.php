<?php

namespace App\Models;

use CodeIgniter\Model;

class XrayExaminationModel extends Model
{
    protected $table = 'xray_examinations';
    protected $primaryKey = 'id';
    
    protected $allowedFields = [
        'appointment_id', 'patient_name', 'patient_id', 'age', 'gender',
        'exam_type', 'exam_date', 'doctor_name', 'radiologist_name',
        'priority', 'status', 'image_path', 'findings', 'interpretation',
        'released_at'
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
}