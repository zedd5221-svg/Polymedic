<?php

namespace App\Models;

use CodeIgniter\Model;

class AppointmentModel extends Model
{
    protected $table = 'appointments';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'reference_number',
        'appointment_date',
        'appointment_time',
        'full_name',
        'age',
        'gender',
        'email',
        'phone',
        'service_type',
        'lab_services',
        'xray_services',
        'other_requests',
        'status',
        'arrival_time'
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    
    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_LATE = 'late';
    
    public function getAppointmentsByStatus($status = null)
    {
        if ($status) {
            return $this->where('status', $status)->findAll();
        }
        return $this->findAll();
    }
    
    public function getTodaysAppointments()
    {
        return $this->where('appointment_date', date('Y-m-d'))
                    ->orderBy('appointment_time', 'ASC')
                    ->findAll();
    }
    
    public function updateStatus($id, $status)
    {
        return $this->update($id, ['status' => $status]);
    }
    
    public function checkLateAppointments()
    {
        $appointments = $this->where('status', self::STATUS_PENDING)
                            ->where('appointment_date', date('Y-m-d'))
                            ->findAll();
        
        $now = time();
        $updated = 0;
        
        foreach ($appointments as $appt) {
            $apptDateTime = strtotime($appt['appointment_date'] . ' ' . $appt['appointment_time']);
            
            // If 1 hour has passed since appointment time
            if (($now - $apptDateTime) >= 3600) {
                $this->update($appt['id'], ['status' => self::STATUS_LATE]);
                $updated++;
            }
        }
        
        return $updated;
    }
}