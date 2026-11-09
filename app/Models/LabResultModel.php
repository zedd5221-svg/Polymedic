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

            /* ---------- Catalogue names (services table) ---------- */
            'glucose (rbs/fbs)' => [
                ['test_name' => 'Glucose (FBS)', 'unit' => 'mg/dL', 'reference_range' => '70-100'],
            ],
            'hdl / ldl' => [
                ['test_name' => 'HDL Cholesterol', 'unit' => 'mg/dL', 'reference_range' => '>40'],
                ['test_name' => 'LDL Cholesterol', 'unit' => 'mg/dL', 'reference_range' => '<100'],
            ],
            'sgpt/alt' => [
                ['test_name' => 'ALT (SGPT)', 'unit' => 'U/L', 'reference_range' => '7-56'],
            ],
            'sgot/ast' => [
                ['test_name' => 'AST (SGOT)', 'unit' => 'U/L', 'reference_range' => '10-40'],
            ],
            'complete blood count (cbc)' => 'cbc',
            'platelet count' => [
                ['test_name' => 'Platelet Count', 'unit' => 'K/uL', 'reference_range' => '150-450'],
            ],
            'esr' => [
                ['test_name' => 'ESR', 'unit' => 'mm/hr', 'reference_range' => '0-20'],
            ],
            'hemoglobin (hgb)' => [
                ['test_name' => 'Hemoglobin (Hgb)', 'unit' => 'g/dL', 'reference_range' => '12.0-16.0'],
            ],
            'hematocrit (hct)' => [
                ['test_name' => 'Hematocrit (Hct)', 'unit' => '%', 'reference_range' => '36-48'],
            ],
            'fecalysis' => [
                ['test_name' => 'Stool Color',          'unit' => '',     'reference_range' => 'Brown'],
                ['test_name' => 'Stool Consistency',    'unit' => '',     'reference_range' => 'Formed'],
                ['test_name' => 'Occult Blood',         'unit' => '',     'reference_range' => 'Negative'],
                ['test_name' => 'Pus Cells (Fecal)',    'unit' => '/HPF', 'reference_range' => '0-2'],
                ['test_name' => 'RBC (Fecal)',          'unit' => '/HPF', 'reference_range' => '0-2'],
                ['test_name' => 'Fat Globules',         'unit' => '',     'reference_range' => 'None'],
                ['test_name' => 'Ova / Parasites',      'unit' => '',     'reference_range' => 'None seen'],
            ],
            'semenanalysis' => [
                ['test_name' => 'Volume',                'unit' => 'mL',        'reference_range' => '1.5-5.0'],
                ['test_name' => 'Semen pH',              'unit' => '',          'reference_range' => '7.2-8.0'],
                ['test_name' => 'Liquefaction Time',     'unit' => 'min',       'reference_range' => '<60'],
                ['test_name' => 'Sperm Concentration',   'unit' => 'million/mL', 'reference_range' => '>=15'],
                ['test_name' => 'Total Motility',        'unit' => '%',         'reference_range' => '>=40'],
                ['test_name' => 'Progressive Motility',  'unit' => '%',         'reference_range' => '>=32'],
                ['test_name' => 'Normal Morphology',     'unit' => '%',         'reference_range' => '>=4'],
                ['test_name' => 'Viability',             'unit' => '%',         'reference_range' => '>=58'],
            ],
            'hcv (pregnancy test)' => [
                ['test_name' => 'Pregnancy Test (hCG)', 'unit' => '', 'reference_range' => 'Negative'],
            ],
            'salmonella typhi igg/igm' => [
                ['test_name' => 'Salmonella typhi IgG', 'unit' => '', 'reference_range' => 'Negative'],
                ['test_name' => 'Salmonella typhi IgM', 'unit' => '', 'reference_range' => 'Negative'],
            ],
            'hbsag (hepatitis b)' => 'hbsag',
            'hiv (igg/igm)' => 'hiv',
        ];
    }

    /**
     * Lab section each service belongs to, shown next to the service on the
     * result form so the technologist sees what kind of test was ordered.
     */
    public function sectionFor(string $service): string
    {
        $key = strtolower(trim($service));
        $sections = [
            'Hematology'          => ['cbc', 'complete blood count', 'platelet', 'esr', 'hemoglobin (', 'hematocrit', 'blood typ'],
            'Clinical Microscopy' => ['urinalysis', 'fecalysis', 'semen'],
            'Thyroid'             => ['t3', 't4', 'ft3', 'ft4', 'tsh', 'thyroid'],
            'Serology'            => ['hiv', 'hbsag', 'hepatitis', 'hcv', 'pregnan', 'salmonella', 'typhi', 'syphilis', 'pallidum', 'vdrl'],
            'Chemistry'           => ['glucose', 'fbs', 'hba1c', 'a1c', 'cholesterol', 'triglycer', 'hdl', 'ldl', 'creatinine',
                                      'uric', 'urea', 'bun', 'sgpt', 'sgot', 'alt', 'ast', 'sodium', 'potassium', 'chloride', 'calcium'],
        ];
        foreach ($sections as $section => $words) {
            foreach ($words as $w) {
                // short codes (t3, alt…) must match a whole word, longer words may match inside
                $hit = strlen($w) <= 4
                    ? preg_match('/(^|[^a-z0-9])' . preg_quote($w, '/') . '($|[^a-z0-9])/', $key)
                    : strpos($key, $w) !== false;
                if ($hit) {
                    return $section;
                }
            }
        }
        return 'Laboratory';
    }

    /**
     * Tests for one ordered service: exact name first, then a loose match;
     * an entry may point at another key ('hiv (igg/igm)' => 'hiv').
     */
    private function definitionsFor(string $service, array $map): ?array
    {
        $key  = strtolower(trim($service));
        $defs = $map[$key] ?? null;

        if ($defs === null) {
            // loose match, preferring the longest known name inside the ordered one
            $best = null;
            foreach ($map as $mapKey => $mapDef) {
                $mapKey = (string) $mapKey;
                // short codes (t3, esr, hdl…) only count as whole words
                $inside = strlen($mapKey) <= 4
                    ? preg_match('/(^|[^a-z0-9])' . preg_quote($mapKey, '/') . '($|[^a-z0-9])/', $key) === 1
                    : strpos($key, $mapKey) !== false;
                if ($inside || (strlen($key) > 4 && strpos($mapKey, $key) !== false)) {
                    if ($best === null || strlen($mapKey) > strlen($best)) {
                        $best = $mapKey;
                    }
                }
            }
            $defs = $best !== null ? $map[$best] : null;
        }

        if (is_string($defs)) {
            $defs = $map[$defs] ?? null;
        }
        return is_array($defs) ? $defs : null;
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
            // A service with no known tests still gets one row under its own name,
            // so every ordered service shows up on the form.
            $definitions = $this->definitionsFor($service, $map)
                ?? [['test_name' => $service, 'unit' => '', 'reference_range' => '']];
            $section = $this->sectionFor($service);

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
                    'service_name'    => $service,
                    'section'         => $section,
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
     * Saved results for a printed report, each tagged with the ordered
     * service it belongs to (service_name / section), so the report can
     * group and title them. No blank template rows are added.
     */
    public function getForPrint(int $labRequestId, string $labServicesString): array
    {
        $byTest = [];
        foreach ($this->buildTemplateForRequest($labServicesString) as $t) {
            $byTest[$t['test_name']] = ['service_name' => $t['service_name'], 'section' => $t['section']];
        }

        $rows = [];
        foreach ($this->getByRequest($labRequestId) as $row) {
            $rows[] = $row + ($byTest[(string) $row['test_name']] ?? []);
        }

        // keep the order the services were ordered in; hand-added tests last
        $svcOrder = array_flip(array_values(array_unique(array_column($byTest, 'service_name'))));
        $pos = [];
        foreach ($rows as $i => $r) {
            $pos[$i] = isset($r['service_name']) ? ($svcOrder[$r['service_name']] ?? 999) : 999;
        }
        uksort($rows, static fn ($a, $b) => [$pos[$a], $a] <=> [$pos[$b], $b]);

        return array_values($rows);
    }

    /**
     * Legacy alias kept so nothing that calls getTestTemplates()
     * fatals.
     */
    public function getTestTemplates($category = null)
    {
        $map = $this->getServiceTests();
        // resolve aliases ('hiv (igg/igm)' => 'hiv') so callers always get rows
        foreach ($map as $k => $v) {
            if (is_string($v)) {
                $map[$k] = $map[$v] ?? [];
            }
        }
        if ($category && isset($map[strtolower($category)])) {
            return $map[strtolower($category)];
        }
        return $map;
    }
}