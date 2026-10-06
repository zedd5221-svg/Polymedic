<?php

namespace App\Models;

use CodeIgniter\Model;

class LabRequestModel extends Model
{
    protected $table = 'lab_requests';
    protected $primaryKey = 'id';
    
    protected $allowedFields = [
        'appointment_id', 'patient_name', 'patient_id', 'age', 'gender',
        'email', 'phone', 'lab_services', 'request_date', 'doctor_name', 
        'med_tech_name', 'status', 'findings', 'remarks', 'released_at'
    ];
    
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    
    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_DRAFT = 'draft';
    const STATUS_COMPLETED = 'completed';
    const STATUS_RELEASED = 'released';
    
    /**
     * Get all lab requests with optional status filter
     */
    public function getRequests($status = null, $limit = null)
    {
        if ($status) {
            $this->where('status', $status);
        }
        if ($limit) {
            $this->limit($limit);
        }
        return $this->orderBy('request_date', 'DESC')
                    ->orderBy('created_at', 'DESC')
                    ->findAll();
    }
    
    /**
     * Get pending lab requests
     */
    public function getPendingRequests()
    {
        return $this->where('status', self::STATUS_PENDING)
                    ->orWhere('status', self::STATUS_IN_PROGRESS)
                    ->orderBy('request_date', 'ASC')
                    ->orderBy('created_at', 'ASC')
                    ->findAll();
    }
    
    /**
     * Get request by appointment ID
     */
    public function getByAppointment($appointmentId)
    {
        return $this->where('appointment_id', $appointmentId)->first();
    }
    
    /**
     * Create lab request from appointment
     */
    public function createFromAppointment($appointment)
    {
        // Decode lab services
        $labServices = json_decode($appointment['lab_services'], true) ?? [];
        
        if (empty($labServices)) {
            return null;
        }
        
        // Check if already exists
        $existing = $this->where('appointment_id', $appointment['id'])->first();
        if ($existing) {
            return $existing;
        }
        
        // Format lab services
        $services = array_map(function($service) {
            $service = str_replace('\/', '/', $service);
            $service = str_replace('\\/', '/', $service);
            $service = stripslashes($service);
            return trim($service);
        }, $labServices);
        
        $data = [
            'appointment_id' => $appointment['id'],
            'patient_name' => $appointment['full_name'],
            'age' => $appointment['age'],
            'gender' => $appointment['gender'],
            'email' => $appointment['email'] ?? null,
            'phone' => $appointment['phone'] ?? null,
            'lab_services' => implode(', ', $services),
            'request_date' => $appointment['appointment_date'],
            'status' => self::STATUS_PENDING
        ];
        
        $this->insert($data);
        return $this->where('appointment_id', $appointment['id'])->first();
    }
    
    /**
     * Update request status
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
     * Save findings and remarks
     */
    public function saveFindings($id, $findings, $remarks)
    {
        return $this->update($id, [
            'findings' => $findings,
            'remarks' => $remarks,
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
            'in_progress' => $this->where('status', self::STATUS_IN_PROGRESS)->countAllResults(),
            'draft' => $this->where('status', self::STATUS_DRAFT)->countAllResults(),
            'completed' => $this->where('status', self::STATUS_COMPLETED)->countAllResults(),
            'released' => $this->where('status', self::STATUS_RELEASED)->countAllResults(),
            'total' => $this->countAll()
        ];
    }
}