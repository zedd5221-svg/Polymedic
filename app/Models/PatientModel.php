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
        $prefix = 'PAT-' . $year . '-';
        $maxAttempts = 50;
        $attempt = 0;
        
        // Get the highest number for this year using MAX on numeric suffix
        $db = \Config\Database::connect();
        $query = $db->query("
            SELECT MAX(CAST(SUBSTRING(patient_code, 8) AS UNSIGNED)) as max_num
            FROM patients
            WHERE patient_code LIKE '{$prefix}%'
        ");
        $row = $query->getRow();
        $maxNum = $row->max_num ?? 0;
        
        $nextNumber = $maxNum + 1;
        
        // Generate a unique code with attempts
        $code = '';
        do {
            $numberStr = str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
            $code = $prefix . $numberStr;
            $nextNumber++;
            $attempt++;
            
            // Check if this code already exists
            $exists = $this->where('patient_code', $code)->first();
            
            if ($attempt > $maxAttempts) {
                // Fallback: use timestamp + random
                $code = $prefix . date('Hi') . rand(10, 99);
                $exists = $this->where('patient_code', $code)->first();
                if (!$exists) {
                    break;
                }
                // Ultimate fallback: microtime
                $code = $prefix . substr(microtime(true) * 10000, -4);
                break;
            }
        } while ($exists);
        
        return $code;
    }
    
    /**
     * Find existing patient or create from appointment data (Online Booking)
     * FIXED: Properly creates patient records from online appointments
     */
    public function findOrCreateFromAppointment($appointment)
    {
        $fullName = trim($appointment['full_name'] ?? '');
        $age = $appointment['age'] ?? null;
        $gender = $appointment['gender'] ?? '';
        $email = trim($appointment['email'] ?? '');
        $phone = trim($appointment['phone'] ?? '');
        
        if (empty($fullName)) {
            log_message('error', 'findOrCreateFromAppointment: Empty name');
            return null;
        }
        
        // ---- STEP 1: Try to find existing by email ----
        $patient = null;
        if (!empty($email)) {
            $patient = $this->where('email', $email)->first();
        }
        
        // ---- STEP 2: Try by phone ----
        if (!$patient && !empty($phone)) {
            $patient = $this->where('phone', $phone)->first();
        }
        
        // ---- STEP 3: Try by name + age + gender ----
        if (!$patient) {
            $patient = $this->where('full_name', $fullName)
                           ->where('age', $age)
                           ->where('gender', $gender)
                           ->first();
        }
        
        // ---- STEP 4: Try by name + age only (fallback) ----
        if (!$patient) {
            $patient = $this->where('full_name', $fullName)
                           ->where('age', $age)
                           ->first();
        }
        
        // ---- STEP 5: If patient exists, update and return ----
        if ($patient) {
            $update = [];
            if (empty($patient['email']) && !empty($email)) {
                $update['email'] = $email;
            }
            if (empty($patient['phone']) && !empty($phone)) {
                $update['phone'] = $phone;
            }
            if (empty($patient['gender']) && !empty($gender)) {
                $update['gender'] = $gender;
            }
            if (($patient['source'] ?? '') !== 'online') {
                $update['source'] = 'online';
            }
            if (!empty($update)) {
                $this->update($patient['id'], $update);
                log_message('info', 'Updated existing patient from appointment: ' . $patient['id']);
            }
            return $patient;
        }
        
        // ---- STEP 6: Create new patient (Online source) ----
        $maxAttempts = 5;
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $newCode = $this->generatePatientCode();
            $insertData = [
                'patient_code' => $newCode,
                'full_name'    => $fullName,
                'email'        => !empty($email) ? $email : null,
                'phone'        => !empty($phone) ? $phone : null,
                'age'          => $age,
                'gender'       => $gender,
                'source'       => 'online',
                'address'      => null,
                'created_at'   => date('Y-m-d H:i:s'),
                'updated_at'   => date('Y-m-d H:i:s')
            ];
            
            try {
                $this->insert($insertData);
                $id = $this->getInsertID();
                if ($id) {
                    $patient = $this->find($id);
                    log_message('info', "Patient created from appointment: $fullName (Code: $newCode, ID: $id)");
                    return $patient;
                }
            } catch (\Exception $e) {
                if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                    log_message('warning', "Duplicate code $newCode, retry $attempt/$maxAttempts");
                    continue;
                }
                log_message('error', 'Insert exception in findOrCreateFromAppointment: ' . $e->getMessage());
                break;
            }
        }
        
        log_message('error', "Failed to create patient from appointment after $maxAttempts attempts");
        return null;
    }
    
    /**
     * Find existing patient or create from walk-in request data
     * This is used by diagnostic_requests (Receptionist)
     */
    public function findOrCreateFromWalkin($data)
    {
        $fullName = trim($data['full_name'] ?? '');
        $age = $data['age'] ?? null;
        $gender = $data['gender'] ?? '';
        $email = trim($data['email'] ?? '');
        $phone = trim($data['phone'] ?? '');
        
        if (empty($fullName)) {
            log_message('error', 'findOrCreateFromWalkin: Empty name');
            return null;
        }
        
        // ---- STEP 1: Try to find existing by email ----
        $patient = null;
        if (!empty($email)) {
            $patient = $this->where('email', $email)->first();
        }
        
        // ---- STEP 2: Try by phone ----
        if (!$patient && !empty($phone)) {
            $patient = $this->where('phone', $phone)->first();
        }
        
        // ---- STEP 3: Try by name + age + gender ----
        if (!$patient) {
            $patient = $this->where('full_name', $fullName)
                           ->where('age', $age)
                           ->where('gender', $gender)
                           ->first();
        }
        
        // ---- STEP 4: Try by name + age only (fallback) ----
        if (!$patient) {
            $patient = $this->where('full_name', $fullName)
                           ->where('age', $age)
                           ->first();
        }
        
        // ---- STEP 5: If patient exists, update source to walk-in ----
        if ($patient) {
            $update = [];
            if (empty($patient['email']) && !empty($email)) {
                $update['email'] = $email;
            }
            if (empty($patient['phone']) && !empty($phone)) {
                $update['phone'] = $phone;
            }
            if (empty($patient['gender']) && !empty($gender)) {
                $update['gender'] = $gender;
            }
            if (($patient['source'] ?? '') !== 'walk-in') {
                $update['source'] = 'walk-in';
            }
            if (!empty($update)) {
                $this->update($patient['id'], $update);
                log_message('info', 'Updated existing patient from walk-in: ' . $patient['id']);
            }
            return $patient;
        }
        
        // ---- STEP 6: Create new walk-in patient ----
        $maxAttempts = 5;
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $newCode = $this->generatePatientCode();
            $insertData = [
                'patient_code' => $newCode,
                'full_name'    => $fullName,
                'email'        => !empty($email) ? $email : null,
                'phone'        => !empty($phone) ? $phone : null,
                'age'          => $age,
                'gender'       => $gender,
                'source'       => 'walk-in',
                'address'      => null,
                'created_at'   => date('Y-m-d H:i:s'),
                'updated_at'   => date('Y-m-d H:i:s')
            ];
            
            try {
                $this->insert($insertData);
                $id = $this->getInsertID();
                if ($id) {
                    $patient = $this->find($id);
                    log_message('info', "Patient created from walk-in: $fullName (Code: $newCode, ID: $id)");
                    return $patient;
                }
            } catch (\Exception $e) {
                if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                    log_message('warning', "Duplicate code $newCode, retry $attempt/$maxAttempts");
                    continue;
                }
                log_message('error', 'Insert exception in findOrCreateFromWalkin: ' . $e->getMessage());
                break;
            }
        }
        
        log_message('error', "Failed to create walk-in patient after $maxAttempts attempts");
        return null;
    }
    
    /**
     * Sync walk-in patients from lab_requests and xray_examinations
     * This prevents duplicate patients when syncing
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
                        $patient = $this->findOrCreateFromWalkin([
                            'full_name' => $request['patient_name'],
                            'age' => $request['age'],
                            'gender' => $request['gender'],
                            'email' => $request['email'] ?? '',
                            'phone' => $request['phone'] ?? ''
                        ]);
                        if ($patient) {
                            $count++;
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
                        $patient = $this->findOrCreateFromWalkin([
                            'full_name' => $request['patient_name'],
                            'age' => $request['age'],
                            'gender' => $request['gender'],
                            'email' => $request['email'] ?? '',
                            'phone' => $request['phone'] ?? ''
                        ]);
                        if ($patient) {
                            $count++;
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
    
    /**
     * Get patient by patient code
     */
    public function getByPatientCode($patientCode)
    {
        return $this->where('patient_code', $patientCode)->first();
    }
    
    /**
     * Get patients by source (online, walk-in, referral)
     */
    public function getBySource($source)
    {
        return $this->where('source', $source)->findAll();
    }
    
    /**
     * Get patient by email
     */
    public function getByEmail($email)
    {
        return $this->where('email', $email)->first();
    }
    
    /**
     * Get patient by phone
     */
    public function getByPhone($phone)
    {
        return $this->where('phone', $phone)->first();
    }
    
    /**
     * Search patients by keyword
     */
    public function search($keyword)
    {
        return $this->like('full_name', $keyword)
                    ->orLike('patient_code', $keyword)
                    ->orLike('email', $keyword)
                    ->orLike('phone', $keyword)
                    ->findAll();
    }
    
    /**
     * Get all patients with pagination
     */
    public function getPatients($limit = null, $offset = 0, $source = null)
    {
        if ($source) {
            $this->where('source', $source);
        }
        if ($limit) {
            $this->limit($limit, $offset);
        }
        return $this->orderBy('created_at', 'DESC')->findAll();
    }
    
    /**
     * Get patients by gender
     */
    public function getByGender($gender)
    {
        return $this->where('gender', $gender)->findAll();
    }
    
    /**
     * Get patients by age range
     */
    public function getByAgeRange($minAge, $maxAge)
    {
        return $this->where('age >=', $minAge)
                    ->where('age <=', $maxAge)
                    ->findAll();
    }
    
    /**
     * Count total patients
     */
    public function countPatients($source = null)
    {
        if ($source) {
            return $this->where('source', $source)->countAllResults();
        }
        return $this->countAll();
    }
    
    /**
     * Get recent patients
     */
    public function getRecentPatients($limit = 10, $source = null)
    {
        if ($source) {
            $this->where('source', $source);
        }
        return $this->orderBy('created_at', 'DESC')
                    ->limit($limit)
                    ->findAll();
    }
    
    /**
     * Update patient information
     */
    public function updatePatient($id, $data)
    {
        return $this->update($id, $data);
    }
    
    /**
     * Delete patient (hard delete)
     */
    public function deletePatient($id)
    {
        return $this->delete($id);
    }
    
    /**
     * Get patients with their appointment count
     */
    public function getPatientsWithAppointmentCount()
    {
        $db = \Config\Database::connect();
        
        $query = $db->table('patients p')
                    ->select('p.*, COUNT(a.id) as appointment_count')
                    ->join('appointments a', 'a.full_name = p.full_name AND a.age = p.age', 'left')
                    ->groupBy('p.id')
                    ->orderBy('p.created_at', 'DESC')
                    ->get();
        
        return $query->getResultArray();
    }
}