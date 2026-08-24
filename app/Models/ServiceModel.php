<?php

namespace App\Models;

use CodeIgniter\Model;

class ServiceModel extends Model
{
    protected $table = 'services';
    protected $primaryKey = 'id';
    
    protected $allowedFields = [
        'service_code', 'service_name', 'category', 
        'description', 'charge', 'is_active'
    ];
    
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    
    /**
     * Get all active services
     */
    public function getActiveServices()
    {
        return $this->where('is_active', 1)
                    ->orderBy('category', 'ASC')
                    ->orderBy('service_name', 'ASC')
                    ->findAll();
    }
    
    /**
     * Get services by category
     */
    public function getServicesByCategory($category)
    {
        return $this->where('is_active', 1)
                    ->where('category', $category)
                    ->orderBy('service_name', 'ASC')
                    ->findAll();
    }
    
    /**
     * Get laboratory services
     */
    public function getLaboratoryServices()
    {
        return $this->getServicesByCategory('laboratory');
    }
    
    /**
     * Get X-Ray services
     */
    public function getXrayServices()
    {
        return $this->getServicesByCategory('xray');
    }
    
    /**
     * Get service by code
     */
    public function getByCode($code)
    {
        return $this->where('service_code', $code)->first();
    }
    
    /**
     * Get service charges for multiple IDs
     */
    public function getCharges($serviceIds)
    {
        if (empty($serviceIds)) {
            return [];
        }
        
        return $this->select('id, service_name, charge, category')
                    ->whereIn('id', $serviceIds)
                    ->findAll();
    }
    
    /**
     * Get total count by category
     */
    public function getCountByCategory()
    {
        return [
            'laboratory' => $this->where('category', 'laboratory')->countAllResults(),
            'xray' => $this->where('category', 'xray')->countAllResults(),
            'other' => $this->where('category', 'other')->countAllResults(),
            'total' => $this->countAll()
        ];
    }
}