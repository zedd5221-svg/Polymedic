<?php

namespace App\Models;

use CodeIgniter\Model;

class PaymentModel extends Model
{
    protected $table = 'payments';
    protected $primaryKey = 'id';
    
    protected $allowedFields = [
        'request_id',
        'request_type',
        'patient_name',
        'patient_code',
        'appointment_id',
        'services',
        'total_amount',
        'amount_paid',
        'change_amount',
        'payment_method',
        'payment_status',
        'payment_date',
        'reference_number',
        'received_by',
        'notes'
    ];
    
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    
    // Payment status constants
    const STATUS_PAID = 'paid';
    const STATUS_PENDING = 'pending';
    const STATUS_REFUNDED = 'refunded';
    const STATUS_FAILED = 'failed';
    
    // Payment method constants
    const METHOD_CASH = 'cash';
    const METHOD_CARD = 'card';
    const METHOD_GCASH = 'gcash';
    const METHOD_PAYMAYA = 'paymaya';
    const METHOD_INSURANCE = 'insurance';
    
    /**
     * Generate unique reference number
     */
    public function generateReference()
    {
        $prefix = 'PAY-';
        $year = date('y');
        $month = date('m');
        $day = date('d');
        $random = strtoupper(substr(uniqid(), -4));
        
        return $prefix . $year . $month . $day . '-' . $random;
    }
    
    /**
     * Record a new payment
     */
    public function recordPayment($data)
    {
        // Calculate change if cash payment
        $changeAmount = 0;
        if (isset($data['amount_paid']) && isset($data['total_amount'])) {
            $changeAmount = $data['amount_paid'] - $data['total_amount'];
        }
        
        $paymentData = [
            'request_id' => $data['request_id'],
            'request_type' => $data['request_type'],
            'patient_name' => $data['patient_name'],
            'patient_code' => $data['patient_code'] ?? null,
            'appointment_id' => $data['appointment_id'] ?? null,
            'services' => $data['services'] ?? null,
            'total_amount' => $data['total_amount'],
            'amount_paid' => $data['amount_paid'] ?? $data['total_amount'],
            'change_amount' => $changeAmount > 0 ? $changeAmount : 0,
            'payment_method' => $data['payment_method'] ?? self::METHOD_CASH,
            'payment_status' => self::STATUS_PAID,
            'payment_date' => date('Y-m-d H:i:s'),
            'reference_number' => $this->generateReference(),
            'received_by' => $data['received_by'] ?? null,
            'notes' => $data['notes'] ?? null
        ];
        
        $this->insert($paymentData);
        return $this->getInsertID();
    }
    
    /**
     * Get payment by request ID
     */
    public function getByRequest($requestId, $requestType)
    {
        return $this->where('request_id', $requestId)
                    ->where('request_type', $requestType)
                    ->first();
    }
    
    /**
     * Get payments by patient
     */
    public function getByPatient($patientName)
    {
        return $this->where('patient_name', $patientName)
                    ->orderBy('payment_date', 'DESC')
                    ->findAll();
    }
    
    /**
     * Get payments by date range
     */
    public function getByDateRange($startDate, $endDate)
    {
        return $this->where('payment_date >=', $startDate . ' 00:00:00')
                    ->where('payment_date <=', $endDate . ' 23:59:59')
                    ->orderBy('payment_date', 'DESC')
                    ->findAll();
    }
    
    /**
     * Get today's collections
     */
    public function getTodayCollections()
    {
        $today = date('Y-m-d');
        return $this->where('DATE(payment_date)', $today)
                    ->where('payment_status', self::STATUS_PAID)
                    ->findAll();
    }
    
    /**
     * Get total collections for today
     */
    public function getTodayTotal()
    {
        $today = date('Y-m-d');
        $result = $this->select('SUM(total_amount) as total')
                       ->where('DATE(payment_date)', $today)
                       ->where('payment_status', self::STATUS_PAID)
                       ->first();
        return $result['total'] ?? 0;
    }
    
    /**
     * Get monthly collections
     */
    public function getMonthlyCollections($year, $month)
    {
        return $this->where('YEAR(payment_date)', $year)
                    ->where('MONTH(payment_date)', $month)
                    ->where('payment_status', self::STATUS_PAID)
                    ->orderBy('payment_date', 'DESC')
                    ->findAll();
    }
    
    /**
     * Get payment counts by status
     */
    public function getCounts()
    {
        return [
            'total' => $this->countAll(),
            'paid' => $this->where('payment_status', self::STATUS_PAID)->countAllResults(),
            'pending' => $this->where('payment_status', self::STATUS_PENDING)->countAllResults(),
            'refunded' => $this->where('payment_status', self::STATUS_REFUNDED)->countAllResults(),
            'failed' => $this->where('payment_status', self::STATUS_FAILED)->countAllResults()
        ];
    }
}