<?php

namespace App\Models;

use CodeIgniter\Model;

class LabResultModel extends Model
{
    protected $table = 'lab_results';
    protected $primaryKey = 'id';
    
    protected $allowedFields = [
        'lab_request_id', 'test_name', 'result', 'unit', 
        'reference_range', 'flag'
    ];
    
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    
    /**
     * Get results by lab request ID
     */
    public function getByRequest($labRequestId)
    {
        return $this->where('lab_request_id', $labRequestId)
                    ->findAll();
    }
    
    /**
     * Save multiple results
     */
    public function saveResults($labRequestId, $results)
    {
        // Delete existing results
        $this->where('lab_request_id', $labRequestId)->delete();
        
        // Insert new results
        $data = [];
        foreach ($results as $result) {
            if (!empty($result['test_name'])) {
                $data[] = [
                    'lab_request_id' => $labRequestId,
                    'test_name' => $result['test_name'],
                    'result' => $result['result'] ?? '',
                    'unit' => $result['unit'] ?? '',
                    'reference_range' => $result['reference_range'] ?? '',
                    'flag' => $result['flag'] ?? 'normal'
                ];
            }
        }
        
        if (!empty($data)) {
            return $this->insertBatch($data);
        }
        
        return true;
    }
    
    /**
     * Get test templates by category
     */
    public function getTestTemplates($category = null)
    {
        $templates = [
            'cbc' => [
                ['test_name' => 'Hemoglobin (Hgb)', 'unit' => 'g/dL', 'reference_range' => '12.0-16.0'],
                ['test_name' => 'Hematocrit (Hct)', 'unit' => '%', 'reference_range' => '36-48'],
                ['test_name' => 'Red Blood Cell (RBC)', 'unit' => 'M/uL', 'reference_range' => '4.0-5.4'],
                ['test_name' => 'White Blood Cell (WBC)', 'unit' => 'K/uL', 'reference_range' => '4.5-11.0'],
                ['test_name' => 'Platelet Count', 'unit' => 'K/uL', 'reference_range' => '150-450'],
                ['test_name' => 'Neutrophils', 'unit' => '%', 'reference_range' => '40-70'],
                ['test_name' => 'Lymphocytes', 'unit' => '%', 'reference_range' => '20-45'],
                ['test_name' => 'Monocytes', 'unit' => '%', 'reference_range' => '2-10'],
                ['test_name' => 'Eosinophils', 'unit' => '%', 'reference_range' => '1-6'],
                ['test_name' => 'Basophils', 'unit' => '%', 'reference_range' => '0-2']
            ],
            'urinalysis' => [
                ['test_name' => 'Color', 'unit' => '', 'reference_range' => 'Yellow'],
                ['test_name' => 'Appearance', 'unit' => '', 'reference_range' => 'Clear'],
                ['test_name' => 'pH', 'unit' => '', 'reference_range' => '5.0-7.0'],
                ['test_name' => 'Specific Gravity', 'unit' => '', 'reference_range' => '1.005-1.030'],
                ['test_name' => 'Protein', 'unit' => '', 'reference_range' => 'Negative'],
                ['test_name' => 'Glucose', 'unit' => '', 'reference_range' => 'Negative'],
                ['test_name' => 'Ketones', 'unit' => '', 'reference_range' => 'Negative'],
                ['test_name' => 'RBC', 'unit' => '/HPF', 'reference_range' => '0-3'],
                ['test_name' => 'WBC', 'unit' => '/HPF', 'reference_range' => '0-5'],
                ['test_name' => 'Epithelial Cells', 'unit' => '', 'reference_range' => 'Few'],
                ['test_name' => 'Bacteria', 'unit' => '', 'reference_range' => 'None']
            ],
            'blood_chemistry' => [
                ['test_name' => 'Glucose (FBS)', 'unit' => 'mg/dL', 'reference_range' => '70-100'],
                ['test_name' => 'Creatinine', 'unit' => 'mg/dL', 'reference_range' => '0.6-1.2'],
                ['test_name' => 'BUN', 'unit' => 'mg/dL', 'reference_range' => '7-20'],
                ['test_name' => 'Sodium (Na)', 'unit' => 'mmol/L', 'reference_range' => '136-145'],
                ['test_name' => 'Potassium (K)', 'unit' => 'mmol/L', 'reference_range' => '3.5-5.0'],
                ['test_name' => 'Chloride (Cl)', 'unit' => 'mmol/L', 'reference_range' => '98-106'],
                ['test_name' => 'Calcium (Ca)', 'unit' => 'mg/dL', 'reference_range' => '8.5-10.5']
            ],
            'lipid_profile' => [
                ['test_name' => 'Total Cholesterol', 'unit' => 'mg/dL', 'reference_range' => '<200'],
                ['test_name' => 'Triglycerides', 'unit' => 'mg/dL', 'reference_range' => '<150'],
                ['test_name' => 'HDL Cholesterol', 'unit' => 'mg/dL', 'reference_range' => '>40'],
                ['test_name' => 'LDL Cholesterol', 'unit' => 'mg/dL', 'reference_range' => '<100'],
                ['test_name' => 'VLDL', 'unit' => 'mg/dL', 'reference_range' => '<30']
            ],
            'thyroid' => [
                ['test_name' => 'T3', 'unit' => 'ng/dL', 'reference_range' => '80-200'],
                ['test_name' => 'T4', 'unit' => 'ug/dL', 'reference_range' => '4.5-12.5'],
                ['test_name' => 'FT3', 'unit' => 'pg/mL', 'reference_range' => '2.3-4.2'],
                ['test_name' => 'FT4', 'unit' => 'ng/dL', 'reference_range' => '0.8-1.8'],
                ['test_name' => 'TSH', 'unit' => 'uIU/mL', 'reference_range' => '0.4-4.0']
            ]
        ];
        
        if ($category && isset($templates[$category])) {
            return $templates[$category];
        }
        
        return $templates;
    }
}