<?php

namespace App\Models;

use CodeIgniter\Model;

class PatientModel extends Model
{
    protected $table = 'patients';
    protected $primaryKey = 'id';
    
    protected $allowedFields = [
        'patient_code',
        'full_name',
        'email',
        'phone',
        'age',
        'gender',
        'address',
        'source',
        'created_at',
        'updated_at'
    ];
    
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    
    /**
     * Generate a unique patient code
     * Format: PAT-YY-XXXX
     * FIXED: Properly checks for existing codes with retry logic
     */
    public function generatePatientCode()
    {
        $year = date('y');
        $maxAttempts = 50;
        $attempt = 0;
        
        // Get the highest number for this year using a more reliable query
        $builder = $this->db->table($this->table);
        $builder->select('patient_code');
        $builder->like('patient_code', 'PAT-' . $year . '-', 'after');
        $builder->orderBy('id', 'DESC');
        $builder->limit(1);
        $result = $builder->get()->getRowArray();
        
        $nextNumber = 1;
        if ($result && isset($result['patient_code'])) {
            $lastCode = $result['patient_code'];
            if (preg_match('/PAT-' . $year . '-(\d{4})/', $lastCode, $matches)) {
                $nextNumber = intval($matches[1]) + 1;
            }
        }
        
        // Generate a unique code with attempts
        $code = '';
        do {
            $numberStr = str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
            $code = 'PAT-' . $year . '-' . $numberStr;
            $nextNumber++;
            $attempt++;
            
            // Check if this code already exists
            $exists = $this->where('patient_code', $code)->first();
            
            if ($attempt > $maxAttempts) {
                // Fallback: use timestamp + random
                $code = 'PAT-' . $year . '-' . date('Hi') . rand(10, 99);
                // Check one more time
                $exists = $this->where('patient_code', $code)->first();
                if (!$exists) {
                    break;
                }
                // Ultimate fallback: microtime
                $code = 'PAT-' . $year . '-' . substr(microtime(true) * 10000, -4);
                break;
            }
        } while ($exists);
        
        return $code;
    }
    
    /**
     * Find existing patient or create from appointment data
     */
    public function findOrCreateFromAppointment($appointment)
    {
        // Try to find by email first
        if (!empty($appointment['email'])) {
            $existing = $this->where('email', $appointment['email'])->first();
            if ($existing) {
                return $this->updatePatientSource($existing);
            }
        }
        
        // Try by phone
        if (!empty($appointment['phone'])) {
            $existing = $this->where('phone', $appointment['phone'])->first();
            if ($existing) {
                return $this->updatePatientSource($existing);
            }
        }
        
        // Try by name + age + gender
        $existing = $this->where('full_name', $appointment['full_name'])
                         ->where('age', $appointment['age'])
                         ->where('gender', $appointment['gender'])
                         ->first();
        if ($existing) {
            return $this->updatePatientSource($existing);
        }
        
        // Create new patient
        return $this->createFromAppointment($appointment);
    }
    
    /**
     * Update patient source to include walk-in if they came in person
     */
    private function updatePatientSource($patient)
    {
        if (($patient['source'] ?? '') !== 'walk-in') {
            $this->update($patient['id'], ['source' => 'walk-in']);
            $patient['source'] = 'walk-in';
        }
        return $patient;
    }
    
    /**
     * Create patient from appointment data
     */
    public function createFromAppointment($appointment)
    {
        $patientCode = $this->generatePatientCode();
        
        $data = [
            'patient_code' => $patientCode,
            'full_name' => $appointment['full_name'],
            'email' => $appointment['email'] ?? null,
            'phone' => $appointment['phone'] ?? null,
            'age' => $appointment['age'],
            'gender' => $appointment['gender'],
            'source' => 'online'
        ];
        
        try {
            $this->insert($data);
            return $this->find($this->getInsertID());
        } catch (\Exception $e) {
            log_message('error', 'Create from appointment error: ' . $e->getMessage());
            // Try to find existing patient with same name/age/gender
            $existing = $this->where('full_name', $appointment['full_name'])
                             ->where('age', $appointment['age'])
                             ->where('gender', $appointment['gender'])
                             ->first();
            if ($existing) {
                return $this->updatePatientSource($existing);
            }
            return null;
        }
    }
    
    /**
     * Find existing patient or create from request data
     */
    public function findOrCreateFromRequest($data)
    {
        // Try by email
        if (!empty($data['email'])) {
            $existing = $this->where('email', $data['email'])->first();
            if ($existing) {
                return $this->updatePatientSource($existing);
            }
        }
        
        // Try by phone
        if (!empty($data['phone'])) {
            $existing = $this->where('phone', $data['phone'])->first();
            if ($existing) {
                return $this->updatePatientSource($existing);
            }
        }
        
        // Try by name + age + gender
        $existing = $this->where('full_name', $data['patient_name'])
                         ->where('age', $data['age'])
                         ->where('gender', $data['gender'])
                         ->first();
        if ($existing) {
            return $this->updatePatientSource($existing);
        }
        
        // Create new patient
        return $this->createFromRequestData($data);
    }
    
    /**
     * Create patient from diagnostic request data
     */
    public function createFromRequestData($data)
    {
        $patientCode = $this->generatePatientCode();
        
        $patientData = [
            'patient_code' => $patientCode,
            'full_name' => $data['patient_name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'age' => $data['age'],
            'gender' => $data['gender'],
            'source' => 'walk-in'
        ];
        
        try {
            $this->insert($patientData);
            $id = $this->getInsertID();
            if ($id) {
                return $this->find($id);
            }
        } catch (\Exception $e) {
            log_message('error', 'Create from request error: ' . $e->getMessage());
            // Try to find existing patient with same name/age/gender
            $existing = $this->where('full_name', $data['patient_name'])
                             ->where('age', $data['age'])
                             ->where('gender', $data['gender'])
                             ->first();
            if ($existing) {
                return $this->updatePatientSource($existing);
            }
            
            // Ultimate fallback: try with a different code
            $fallbackCode = 'PAT-' . date('y') . '-' . date('Hi') . rand(10, 99);
            $patientData['patient_code'] = $fallbackCode;
            try {
                $this->insert($patientData);
                $id = $this->getInsertID();
                if ($id) {
                    return $this->find($id);
                }
            } catch (\Exception $e2) {
                log_message('error', 'Fallback patient creation error: ' . $e2->getMessage());
            }
        }
        
        return null;
    }
    
    /**
     * Get patient by patient code
     */
    public function getByPatientCode($patientCode)
    {
        return $this->where('patient_code', $patientCode)->first();
    }
    
    /**
     * Get patients by source
     */
    public function getBySource($source)
    {
        return $this->where('source', $source)->findAll();
    }
    
    /**
     * Sync walk-in patients from lab_requests and xray_examinations
     * This prevents duplicate patients
     */
    public function syncWalkInPatients()
    {
        $db = \Config\Database::connect();
        $count = 0;
        $errors = [];
        
        // Get all lab requests with walk-in patients (appointment_id = 0)
        try {
            $labRequests = $db->table('lab_requests')
                              ->where('appointment_id', 0)
                              ->get()
                              ->getResultArray();
            
            foreach ($labRequests as $request) {
                if (!empty($request['patient_name'])) {
                    $existing = $this->where('full_name', $request['patient_name'])
                                     ->where('age', $request['age'])
                                     ->where('gender', $request['gender'])
                                     ->first();
                    
                    if (!$existing) {
                        $patientCode = $this->generatePatientCode();
                        try {
                            $this->insert([
                                'patient_code' => $patientCode,
                                'full_name' => $request['patient_name'],
                                'email' => $request['email'] ?? null,
                                'phone' => $request['phone'] ?? null,
                                'age' => $request['age'],
                                'gender' => $request['gender'],
                                'source' => 'walk-in'
                            ]);
                            $count++;
                        } catch (\Exception $e) {
                            $errors[] = 'Lab sync error: ' . $e->getMessage();
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $errors[] = 'Lab sync error: ' . $e->getMessage();
        }
        
        // Get all x-ray examinations with walk-in patients (appointment_id = 0)
        try {
            $xrayRequests = $db->table('xray_examinations')
                               ->where('appointment_id', 0)
                               ->get()
                               ->getResultArray();
            
            foreach ($xrayRequests as $request) {
                if (!empty($request['patient_name'])) {
                    $existing = $this->where('full_name', $request['patient_name'])
                                     ->where('age', $request['age'])
                                     ->where('gender', $request['gender'])
                                     ->first();
                    
                    if (!$existing) {
                        $patientCode = $this->generatePatientCode();
                        try {
                            $this->insert([
                                'patient_code' => $patientCode,
                                'full_name' => $request['patient_name'],
                                'email' => $request['email'] ?? null,
                                'phone' => $request['phone'] ?? null,
                                'age' => $request['age'],
                                'gender' => $request['gender'],
                                'source' => 'walk-in'
                            ]);
                            $count++;
                        } catch (\Exception $e) {
                            $errors[] = 'X-Ray sync error: ' . $e->getMessage();
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $errors[] = 'X-Ray sync error: ' . $e->getMessage();
        }
        
        if (!empty($errors)) {
            log_message('error', 'Sync errors: ' . json_encode($errors));
        }
        
        return $count;
    }
}