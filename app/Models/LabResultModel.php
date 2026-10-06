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
     * Get results by lab request ID, in a stable order.
     */
    public function getByRequest($labRequestId)
    {
        return $this->where('lab_request_id', $labRequestId)
                    ->orderBy('id', 'ASC')
                    ->findAll();
    }

    /**
     * ============================================================
     * SAVE RESULTS (IDEMPOTENT UPSERT)
     * ============================================================
     * Rows in the payload that match an existing test_name update
     * in place. New test_name values insert. Existing rows that are
     * not in the payload are kept unless $replaceAll is true.
     *
     * Empty payloads never delete anything.
     */
    public function saveResults($labRequestId, $results, $replaceAll = false)
    {
        $labRequestId = (int) $labRequestId;
        if ($labRequestId <= 0) {
            return false;
        }

        $clean = [];
        foreach ((array) $results as $row) {
            $name = trim((string) ($row['test_name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $clean[$name] = [
                'lab_request_id'  => $labRequestId,
                'test_name'       => $name,
                'result'          => (string) ($row['result'] ?? ''),
                'unit'            => (string) ($row['unit'] ?? ''),
                'reference_range' => (string) ($row['reference_range'] ?? ''),
                'flag'            => (string) ($row['flag'] ?? 'normal'),
            ];
        }

        if (empty($clean)) {
            return true;
        }

        $existing = [];
        foreach ($this->where('lab_request_id', $labRequestId)->findAll() as $row) {
            $existing[(string) $row['test_name']] = $row;
        }

        $db = \Config\Database::connect();
        $db->transStart();

        foreach ($clean as $name => $payload) {
            if (isset($existing[$name])) {
                $this->update($existing[$name]['id'], [
                    'result'          => $payload['result'],
                    'unit'            => $payload['unit'],
                    'reference_range' => $payload['reference_range'],
                    'flag'            => $payload['flag'],
                ]);
                unset($existing[$name]);
            } else {
                $this->insert($payload);
            }
        }

        if ($replaceAll) {
            foreach ($existing as $stale) {
                $this->delete($stale['id']);
            }
        }

        $db->transComplete();

        return $db->transStatus();
    }

    /**
     * ============================================================
     * PER-SERVICE TEST DEFINITIONS
     * ============================================================
     * Keys are lowercased, trimmed service names as they appear in
     * lab_requests.lab_services. Each value is the list of rows that
     * should appear on the result form when that service is ordered.
     *
     * Add new entries here when the services table grows.
     */
    public function getServiceTests(): array
    {
        return [

            /* ---------- Chemistry ---------- */
            'blood urea nitrogen' => [
                ['test_name' => 'Blood Urea Nitrogen (BUN)', 'unit' => 'mg/dL', 'reference_range' => '7-20'],
            ],
            'blood urea nitrogen (bun)' => [
                ['test_name' => 'Blood Urea Nitrogen (BUN)', 'unit' => 'mg/dL', 'reference_range' => '7-20'],
            ],
            'bun' => [
                ['test_name' => 'Blood Urea Nitrogen (BUN)', 'unit' => 'mg/dL', 'reference_range' => '7-20'],
            ],
            'creatinine' => [
                ['test_name' => 'Creatinine', 'unit' => 'mg/dL', 'reference_range' => '0.6-1.2'],
            ],
            'blood uric acid' => [
                ['test_name' => 'Blood Uric Acid', 'unit' => 'mg/dL', 'reference_range' => '3.4-7.0'],
            ],
            'uric acid' => [
                ['test_name' => 'Blood Uric Acid', 'unit' => 'mg/dL', 'reference_range' => '3.4-7.0'],
            ],
            'glucose' => [
                ['test_name' => 'Glucose (FBS)', 'unit' => 'mg/dL', 'reference_range' => '70-100'],
            ],
            'glucose (fbs)' => [
                ['test_name' => 'Glucose (FBS)', 'unit' => 'mg/dL', 'reference_range' => '70-100'],
            ],
            'fbs' => [
                ['test_name' => 'Glucose (FBS)', 'unit' => 'mg/dL', 'reference_range' => '70-100'],
            ],
            'calcium' => [
                ['test_name' => 'Calcium', 'unit' => 'mg/dL', 'reference_range' => '8.5-10.5'],
            ],
            'calcium (ca)' => [
                ['test_name' => 'Calcium', 'unit' => 'mg/dL', 'reference_range' => '8.5-10.5'],
            ],
            'sodium' => [
                ['test_name' => 'Sodium (Na)', 'unit' => 'mmol/L', 'reference_range' => '136-145'],
            ],
            'sodium (na)' => [
                ['test_name' => 'Sodium (Na)', 'unit' => 'mmol/L', 'reference_range' => '136-145'],
            ],
            'potassium' => [
                ['test_name' => 'Potassium (K)', 'unit' => 'mmol/L', 'reference_range' => '3.5-5.0'],
            ],
            'potassium (k)' => [
                ['test_name' => 'Potassium (K)', 'unit' => 'mmol/L', 'reference_range' => '3.5-5.0'],
            ],
            'chloride' => [
                ['test_name' => 'Chloride (Cl)', 'unit' => 'mmol/L', 'reference_range' => '98-106'],
            ],
            'cholesterol' => [
                ['test_name' => 'Total Cholesterol', 'unit' => 'mg/dL', 'reference_range' => '<200'],
            ],
            'total cholesterol' => [
                ['test_name' => 'Total Cholesterol', 'unit' => 'mg/dL', 'reference_range' => '<200'],
            ],
            'triglycerides' => [
                ['test_name' => 'Triglycerides', 'unit' => 'mg/dL', 'reference_range' => '<150'],
            ],
            'hdl' => [
                ['test_name' => 'HDL Cholesterol', 'unit' => 'mg/dL', 'reference_range' => '>40'],
            ],
            'hdl cholesterol' => [
                ['test_name' => 'HDL Cholesterol', 'unit' => 'mg/dL', 'reference_range' => '>40'],
            ],
            'ldl' => [
                ['test_name' => 'LDL Cholesterol', 'unit' => 'mg/dL', 'reference_range' => '<100'],
            ],
            'ldl cholesterol' => [
                ['test_name' => 'LDL Cholesterol', 'unit' => 'mg/dL', 'reference_range' => '<100'],
            ],

            /* ---------- Diabetes ---------- */
            'hba1c' => [
                ['test_name' => 'HbA1c', 'unit' => '%', 'reference_range' => '4.0-5.6'],
            ],
            'hemoglobin a1c' => [
                ['test_name' => 'HbA1c', 'unit' => '%', 'reference_range' => '4.0-5.6'],
            ],
            'glycated hemoglobin' => [
                ['test_name' => 'HbA1c', 'unit' => '%', 'reference_range' => '4.0-5.6'],
            ],

            /* ---------- Hematology ---------- */
            'cbc' => [
                ['test_name' => 'Hemoglobin (Hgb)',       'unit' => 'g/dL', 'reference_range' => '12.0-16.0'],
                ['test_name' => 'Hematocrit (Hct)',       'unit' => '%',    'reference_range' => '36-48'],
                ['test_name' => 'Red Blood Cell (RBC)',   'unit' => 'M/uL', 'reference_range' => '4.0-5.4'],
                ['test_name' => 'White Blood Cell (WBC)', 'unit' => 'K/uL', 'reference_range' => '4.5-11.0'],
                ['test_name' => 'Platelet Count',         'unit' => 'K/uL', 'reference_range' => '150-450'],
                ['test_name' => 'Neutrophils',            'unit' => '%',    'reference_range' => '40-70'],
                ['test_name' => 'Lymphocytes',            'unit' => '%',    'reference_range' => '20-45'],
                ['test_name' => 'Monocytes',              'unit' => '%',    'reference_range' => '2-10'],
                ['test_name' => 'Eosinophils',            'unit' => '%',    'reference_range' => '1-6'],
                ['test_name' => 'Basophils',              'unit' => '%',    'reference_range' => '0-2'],
            ],
            'complete blood count' => [
                ['test_name' => 'Hemoglobin (Hgb)',       'unit' => 'g/dL', 'reference_range' => '12.0-16.0'],
                ['test_name' => 'Hematocrit (Hct)',       'unit' => '%',    'reference_range' => '36-48'],
                ['test_name' => 'Red Blood Cell (RBC)',   'unit' => 'M/uL', 'reference_range' => '4.0-5.4'],
                ['test_name' => 'White Blood Cell (WBC)', 'unit' => 'K/uL', 'reference_range' => '4.5-11.0'],
                ['test_name' => 'Platelet Count',         'unit' => 'K/uL', 'reference_range' => '150-450'],
                ['test_name' => 'Neutrophils',            'unit' => '%',    'reference_range' => '40-70'],
                ['test_name' => 'Lymphocytes',            'unit' => '%',    'reference_range' => '20-45'],
                ['test_name' => 'Monocytes',              'unit' => '%',    'reference_range' => '2-10'],
                ['test_name' => 'Eosinophils',            'unit' => '%',    'reference_range' => '1-6'],
                ['test_name' => 'Basophils',              'unit' => '%',    'reference_range' => '0-2'],
            ],
            'blood typing' => [
                ['test_name' => 'ABO Blood Type', 'unit' => '', 'reference_range' => ''],
                ['test_name' => 'Rh Factor',      'unit' => '', 'reference_range' => ''],
            ],
            'blood type' => [
                ['test_name' => 'ABO Blood Type', 'unit' => '', 'reference_range' => ''],
                ['test_name' => 'Rh Factor',      'unit' => '', 'reference_range' => ''],
            ],

            /* ---------- Urinalysis ---------- */
            'urinalysis' => [
                ['test_name' => 'Color',            'unit' => '',     'reference_range' => 'Yellow'],
                ['test_name' => 'Appearance',       'unit' => '',     'reference_range' => 'Clear'],
                ['test_name' => 'pH',               'unit' => '',     'reference_range' => '5.0-7.0'],
                ['test_name' => 'Specific Gravity', 'unit' => '',     'reference_range' => '1.005-1.030'],
                ['test_name' => 'Protein',          'unit' => '',     'reference_range' => 'Negative'],
                ['test_name' => 'Glucose',          'unit' => '',     'reference_range' => 'Negative'],
                ['test_name' => 'Ketones',          'unit' => '',     'reference_range' => 'Negative'],
                ['test_name' => 'RBC',              'unit' => '/HPF', 'reference_range' => '0-3'],
                ['test_name' => 'WBC',              'unit' => '/HPF', 'reference_range' => '0-5'],
                ['test_name' => 'Epithelial Cells', 'unit' => '',     'reference_range' => 'Few'],
                ['test_name' => 'Bacteria',         'unit' => '',     'reference_range' => 'None'],
            ],

            /* ---------- Serology ---------- */
            'syphilis' => [
                ['test_name' => 'Syphilis (T. pallidum)', 'unit' => '', 'reference_range' => 'Non-reactive'],
            ],
            'syphilis (t. pallidum)' => [
                ['test_name' => 'Syphilis (T. pallidum)', 'unit' => '', 'reference_range' => 'Non-reactive'],
            ],
            't. pallidum' => [
                ['test_name' => 'Syphilis (T. pallidum)', 'unit' => '', 'reference_range' => 'Non-reactive'],
            ],
            'vdrl' => [
                ['test_name' => 'VDRL', 'unit' => '', 'reference_range' => 'Non-reactive'],
            ],
            'hiv' => [
                ['test_name' => 'HIV 1/2 Antibody', 'unit' => '', 'reference_range' => 'Non-reactive'],
            ],
            'hbsag' => [
                ['test_name' => 'HBsAg', 'unit' => '', 'reference_range' => 'Non-reactive'],
            ],
            'hepatitis b' => [
                ['test_name' => 'HBsAg', 'unit' => '', 'reference_range' => 'Non-reactive'],
            ],

            /* ---------- Thyroid ---------- */
            't3' => [
                ['test_name' => 'T3', 'unit' => 'ng/dL', 'reference_range' => '80-200'],
            ],
            't4' => [
                ['test_name' => 'T4', 'unit' => 'ug/dL', 'reference_range' => '4.5-12.5'],
            ],
            'ft3' => [
                ['test_name' => 'FT3', 'unit' => 'pg/mL', 'reference_range' => '2.3-4.2'],
            ],
            'ft4' => [
                ['test_name' => 'FT4', 'unit' => 'ng/dL', 'reference_range' => '0.8-1.8'],
            ],
            'tsh' => [
                ['test_name' => 'TSH', 'unit' => 'uIU/mL', 'reference_range' => '0.4-4.0'],
            ],
            'thyroid panel' => [
                ['test_name' => 'T3',  'unit' => 'ng/dL',  'reference_range' => '80-200'],
                ['test_name' => 'T4',  'unit' => 'ug/dL',  'reference_range' => '4.5-12.5'],
                ['test_name' => 'TSH', 'unit' => 'uIU/mL', 'reference_range' => '0.4-4.0'],
            ],
        ];
    }

    /**
     * ============================================================
     * BUILD TEMPLATE FOR A REQUEST
     * ============================================================
     * Splits lab_services on commas, looks up each service in the
     * map, merges the results, and collapses duplicate test names.
     */
    public function buildTemplateForRequest($labServicesString): array
    {
        $map = $this->getServiceTests();

        $services = array_filter(
            array_map('trim', explode(',', (string) $labServicesString)),
            'strlen'
        );

        $rows = [];
        $seen = [];

        foreach ($services as $service) {
            $key = strtolower($service);

            $definitions = $map[$key] ?? null;

            if ($definitions === null) {
                foreach ($map as $mapKey => $mapDef) {
                    if (strpos($key, $mapKey) !== false || strpos($mapKey, $key) !== false) {
                        $definitions = $mapDef;
                        break;
                    }
                }
            }

            if (!is_array($definitions)) {
                continue;
            }

            foreach ($definitions as $row) {
                $testName = (string) ($row['test_name'] ?? '');
                if ($testName === '' || isset($seen[$testName])) {
                    continue;
                }
                $seen[$testName] = true;
                $rows[] = [
                    'test_name'       => $testName,
                    'result'          => '',
                    'unit'            => (string) ($row['unit'] ?? ''),
                    'reference_range' => (string) ($row['reference_range'] ?? ''),
                    'flag'            => 'normal',
                ];
            }
        }

        return $rows;
    }

    /**
     * Merge the request's template with any already-saved results.
     * Saved results take precedence so the MedTech never loses
     * entered values when the page is re-rendered.
     */
    public function mergeTemplateWithSaved($labServicesString, $savedResults): array
    {
        $template = $this->buildTemplateForRequest($labServicesString);

        $byName = [];
        foreach ($savedResults as $row) {
            $byName[(string) $row['test_name']] = $row;
        }

        $merged = [];
        $usedNames = [];

        foreach ($template as $row) {
            $name = $row['test_name'];
            $usedNames[$name] = true;
            $merged[] = isset($byName[$name]) ? array_merge($row, $byName[$name]) : $row;
        }

        foreach ($savedResults as $row) {
            $name = (string) $row['test_name'];
            if (!isset($usedNames[$name])) {
                $merged[] = $row;
            }
        }

        return $merged;
    }

    /**
     * Legacy alias kept so nothing that calls getTestTemplates()
     * fatals.
     */
    public function getTestTemplates($category = null)
    {
        $map = $this->getServiceTests();
        if ($category && isset($map[strtolower($category)])) {
            return $map[strtolower($category)];
        }
        return $map;
    }
}