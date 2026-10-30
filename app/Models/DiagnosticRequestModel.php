<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * DiagnosticRequestModel
 * ======================
 *
 * Unified model for the merged `diagnostic_requests` table.
 *
 * The table was created by merging lab_requests and xray_examinations.
 * This model replaces both LabRequestModel and XrayExaminationModel at
 * every call site in the four controllers (Admin, Receptionist, MedTech,
 * Radiologist) and in LabResultModel's callers.
 *
 * ──────────────────────────────────────────────────────────────────────
 * Row shape — the model returns BOTH the merged and the legacy names
 * ──────────────────────────────────────────────────────────────────────
 *
 * The merged table stores:
 *     services           (was lab_services / exam_type)
 *     request_date       (was request_date / exam_date)
 *     type               'lab' | 'xray'
 *
 * To avoid touching dozens of views and controllers on day one, every
 * returned row is decorated with the legacy aliases too:
 *
 *     lab_services       = services  (present only on type = 'lab')
 *     exam_type          = services  (present only on type = 'xray')
 *     exam_date          = request_date  (present only on type = 'xray')
 *
 * A caller that reads $row['lab_services'] and a caller that reads
 * $row['services'] will both work. This lets the codebase be cleaned
 * up lazily, on your own schedule, without the migration being held
 * hostage to a big-bang rename.
 *
 * ──────────────────────────────────────────────────────────────────────
 * Method surface — the union of both old models
 * ──────────────────────────────────────────────────────────────────────
 *
 *   From LabRequestModel:
 *     getRequests($status, $limit)
 *     getPendingRequests()
 *     getCounts()   // legacy keys: pending, in_progress, draft,
 *                   //               completed, released, total
 *
 *   From XrayExaminationModel:
 *     getExaminations($status, $limit)
 *     getPendingExaminations()
 *     getWeeklyVolumeData()
 *     determineModality($examType)
 *
 *   From both:
 *     getByAppointment($appointmentId)
 *     updateStatus($id, $status)
 *     saveFindings($id, $findings, $remarksOrInterpretation)
 *     createFromAppointment($appointment)
 */
class DiagnosticRequestModel extends Model
{
    protected $table      = 'diagnostic_requests';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'reference_number', 'type', 'appointment_id',
        'patient_name', 'patient_code', 'age', 'gender',
        'email', 'phone',
        'services', 'request_date', 'doctor_name', 'priority', 'status',
        'assigned_to_id', 'assigned_to_name', 'assigned_at',
        'med_tech_name', 'med_tech_id', 'remarks',
        'radiologist_name', 'radiologist_id', 'interpretation',
        'image_path', 'image_paths',
        'findings', 'released_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // ----------------------------------------------------------------
    // Status constants — union of both old models
    // ----------------------------------------------------------------

    const STATUS_PENDING     = 'pending';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_DRAFT       = 'draft';        // lab only
    const STATUS_COMPLETED   = 'completed';
    const STATUS_RELEASED    = 'released';
    const STATUS_CANCELLED   = 'cancelled';    // xray only

    // Kept for code that still references the old name.
    const STATUS_PROCESSING  = 'in_progress';

    // ----------------------------------------------------------------
    // Type constants
    // ----------------------------------------------------------------

    const TYPE_LAB  = 'lab';
    const TYPE_XRAY = 'xray';

    // ----------------------------------------------------------------
    // Department view of a request
    // ----------------------------------------------------------------
    // The stored status follows the front desk: 'pending' = not started
    // (unpaid), 'in_progress' = started and paid. Inside the lab / x-ray
    // room a paid request nobody has worked on yet is still "pending";
    // it becomes "in progress" when a med tech / radiologist first saves
    // on it, which is also when their name and id are stamped on the row.

    /** Staff columns that record who worked a request, per type. */
    const STAFF_COLUMNS = [
        self::TYPE_LAB  => ['id' => 'med_tech_id',    'name' => 'med_tech_name'],
        self::TYPE_XRAY => ['id' => 'radiologist_id', 'name' => 'radiologist_name'],
    ];

    /**
     * Laboratory sections and the test-name keywords that place a test in
     * one (matched as whole words, case-insensitive, first section wins).
     */
    const LAB_SECTIONS = [
        'Hematology'   => ['complete blood count', 'cbc', 'platelet count', 'platelet', 'esr', 'hemoglobin', 'hgb',
                           'hematocrit', 'hct', 'blood typing', 'rbc', 'wbc', 'mcv', 'mch', 'mchc', 'rdw',
                           'neutrophils', 'lymphocytes', 'monocytes', 'eosinophils', 'basophils'],
        'Chemistry'    => ['glucose', 'rbs', 'fbs', 'hba1c', 'cholesterol', 'triglycerides', 'hdl', 'ldl', 'vldl',
                           'creatinine', 'blood uric acid', 'uric acid', 'blood urea nitrogen', 'bun', 'sgpt', 'alt',
                           'sgot', 'ast', 'sodium', 'potassium', 'chloride', 'calcium', 'electrolytes', 'albumin',
                           'bilirubin', 'alkaline phosphatase', 'protein', 'bicarbonate', 'magnesium', 'phosphorus',
                           'lipid profile', 'lipid'],
        'Urinalysis'   => ['urinalysis', 'urine', 'specific gravity', 'ketones', 'urobilinogen', 'nitrite', 'leukocyte esterase'],
        'Microscopy'   => ['fecalysis', 'fecal', 'semenanalysis', 'semen'],
        'Serology'     => ['hbsag', 'hepatitis', 'anti-hbs', 'anti-hcv', 'hcv', 'hiv', 'anti-hiv', 'salmonella', 'typhi',
                           'syphilis', 't. pallidum', 'vdrl', 'rpr', 'dengue', 'igg', 'igm', 'rheumatoid factor', 'aso',
                           'antibody', 'antigen'],
        'Thyroid'      => ['thyroid', 'thyroid panel', 'thyroid function', 't3 uptake', 'free t3', 'ft3', 'free t4', 'ft4',
                           'tsh', 't3', 't4'],
        'Microbiology' => ['culture', 'sensitivity', 'gram stain', 'blood culture', 'urine culture', 'sputum culture',
                           'stool culture', 'afb', 'fungal culture', 'bacterial culture'],
        'Coagulation'  => ['coagulation', 'prothrombin time', 'pt', 'partial thromboplastin time', 'aptt', 'inr',
                           'bleeding time', 'clotting time'],
    ];

    /** Section a lab test belongs to (LAB_SECTIONS), or 'Other'. */
    public static function labSection(string $test): string
    {
        static $patterns = null;
        if ($patterns === null) {
            foreach (self::LAB_SECTIONS as $section => $keywords) {
                $alts = implode('|', array_map(fn($k) => preg_quote($k, '/'), $keywords));
                // Whole words only, so "pt" / "alt" / "ast" don't match inside other names.
                $patterns[$section] = '/(?<![a-z0-9])(?:' . $alts . ')(?![a-z0-9])/i';
            }
        }

        foreach ($patterns as $section => $pattern) {
            if (preg_match($pattern, $test)) return $section;
        }
        return 'Other';
    }

    /** Statuses a department works with (unpaid 'pending' rows excluded). */
    const DEPARTMENT_STATUSES = [
        self::STATUS_IN_PROGRESS, self::STATUS_DRAFT, self::STATUS_COMPLETED, self::STATUS_RELEASED,
    ];

    /** Paid, but not yet touched by the department. */
    public static function isUntouched(array $row): bool
    {
        $cols = self::STAFF_COLUMNS[$row['type'] ?? ''] ?? null;

        return $cols !== null
            && ($row['status'] ?? '') === self::STATUS_IN_PROGRESS
            && empty($row[$cols['id']])
            && trim((string) ($row[$cols['name']] ?? '')) === '';
    }

    /** Status as the department sees it: untouched in_progress => 'pending'. */
    public static function departmentStatus(array $row): string
    {
        return self::isUntouched($row) ? self::STATUS_PENDING : (string) ($row['status'] ?? '');
    }

    /** SQL condition for "paid but untouched" rows of one type. */
    public static function untouchedSql(string $type): string
    {
        $cols = self::STAFF_COLUMNS[$type];
        return "(status = '" . self::STATUS_IN_PROGRESS . "' AND {$cols['id']} IS NULL AND COALESCE({$cols['name']}, '') = '')";
    }

    /**
     * Department counts in one query, keyed the way the department views
     * read them: pending = paid but untouched, in_progress/processing =
     * being worked on, plus draft / completed / released and total.
     */
    public function getDepartmentCounts(string $type): array
    {
        $untouched = self::untouchedSql($type);

        $row = $this->db->table($this->table)
            ->select("COALESCE(SUM({$untouched}), 0) AS pending,
                      COALESCE(SUM(status = 'in_progress' AND NOT {$untouched}), 0) AS in_progress,
                      COALESCE(SUM(status = 'draft'), 0) AS draft,
                      COALESCE(SUM(status = 'completed'), 0) AS completed,
                      COALESCE(SUM(status = 'released'), 0) AS released", false)
            ->where('type', $type)
            ->whereIn('status', self::DEPARTMENT_STATUSES)
            ->get()->getRowArray();

        $c = array_map('intval', $row ?: []);

        // A draft is work in progress that has been saved but not finished,
        // so it counts as both "In progress" and "Draft". in_progress_only
        // is the exclusive figure for charts whose slices must add up.
        $c['in_progress_only'] = $c['in_progress'];
        $c['in_progress']     += $c['draft'];
        $c['processing']       = $c['in_progress'];   // legacy alias
        $c['cancelled']        = 0;
        $c['total']            = $c['pending'] + $c['in_progress_only'] + $c['draft'] + $c['completed'] + $c['released'];

        return $c;
    }

    // ----------------------------------------------------------------
    // Assignment — who the front desk sent the request to
    // ----------------------------------------------------------------
    // Start assigns a request to exactly one med tech / radiologist. A
    // patient is handled by one user at a time: while any of their
    // requests (lab or x-ray) is active, no other one can be started.

    /** Statuses in which a request is with the user handling it. */
    const ACTIVE_STATUSES = [self::STATUS_IN_PROGRESS, self::STATUS_DRAFT];

    /** Statuses Start may move to in_progress. */
    const STARTABLE_STATUSES = [self::STATUS_PENDING, self::STATUS_CANCELLED];

    /** Roles that may be assigned each request type. */
    const ASSIGNABLE_ROLES = [
        self::TYPE_LAB  => ['med_tech', 'technologist'],
        self::TYPE_XRAY => ['radiologist'],
    ];

    /** Who has a request: the assignee, else (older rows) whoever worked it. */
    const HANDLER_ID_SQL = "COALESCE(assigned_to_id, CASE type WHEN 'xray' THEN radiologist_id ELSE med_tech_id END)";

    /** Name of who has the request: the assignee, else whoever worked it. */
    public static function handlerName(array $row): string
    {
        $name = trim((string) ($row['assigned_to_name'] ?? ''));
        $cols = self::STAFF_COLUMNS[$row['type'] ?? ''] ?? null;
        if ($name === '' && $cols !== null) {
            $name = trim((string) ($row[$cols['name']] ?? ''));
        }
        return $name;
    }

    /** Assignee's name when the request is assigned to someone other than $userId. */
    public static function assignedToOther(array $row, ?int $userId): ?string
    {
        $assigned = (int) ($row['assigned_to_id'] ?? 0);
        if ($assigned <= 0 || $assigned === (int) $userId) {
            return null;
        }
        return trim((string) ($row['assigned_to_name'] ?? '')) ?: 'another staff member';
    }

    /**
     * Keys that identify a request's patient: name + age + sex, and the
     * patient code when there is one. Two rows sharing any key are the
     * same patient.
     */
    public static function patientKeys(array $row): array
    {
        $keys = ['n:' . self::patientNameKey($row)];
        $code = trim((string) ($row['patient_code'] ?? ''));
        if ($code !== '' && strtoupper($code) !== 'N/A') {
            $keys[] = 'c:' . strtolower($code);
        }
        return $keys;
    }

    /** MySQL GET_LOCK name that serialises Start for one patient. */
    public static function patientLockName(array $row): string
    {
        return 'dx_patient_' . md5(self::patientNameKey($row));
    }

    private static function patientNameKey(array $row): string
    {
        return mb_strtolower(trim((string) ($row['patient_name'] ?? ''))) . '|'
            . trim((string) ($row['age'] ?? '')) . '|'
            . mb_strtolower(trim((string) ($row['gender'] ?? '')));
    }

    /** Another active request (any type) for the same patient, if any. */
    public function activeRequestForPatient(array $row): ?array
    {
        $builder = $this->db->table($this->table)
            ->whereIn('status', self::ACTIVE_STATUSES)
            ->where('id !=', (int) $row['id'])
            ->groupStart()
                ->groupStart()
                    ->where('patient_name', (string) $row['patient_name'])
                    ->where('age', $row['age'] ?? null)
                    ->where('gender', $row['gender'] ?? null)
                ->groupEnd();

        $code = trim((string) ($row['patient_code'] ?? ''));
        if ($code !== '' && strtoupper($code) !== 'N/A') {
            $builder->orWhere('patient_code', $code);
        }

        return $builder->groupEnd()->orderBy('updated_at', 'DESC')->get()->getRowArray() ?: null;
    }

    /**
     * Active requests per user id — the workload shown in the Start
     * modal — in one grouped query.
     *
     * @param int[] $userIds
     * @return array<int, int>
     */
    public function activeWorkload(array $userIds): array
    {
        if ($userIds === []) {
            return [];
        }

        $rows = $this->db->table($this->table)
            ->select(self::HANDLER_ID_SQL . ' AS uid, COUNT(*) AS n', false)
            ->whereIn('status', self::ACTIVE_STATUSES)
            ->where(self::HANDLER_ID_SQL . ' IN (' . implode(',', array_map('intval', $userIds)) . ')', null, false)
            ->groupBy('uid')
            ->get()->getResultArray();

        return array_map('intval', array_column($rows, 'n', 'uid'));
    }

    /**
     * Where a request stands, for the people monitoring it from the front
     * desk. Uses the same rules as the lab / x-ray worklists:
     *
     *   pending      not started at the front desk (unpaid)
     *   queued       paid and sent, no med tech / radiologist has started
     *   in_progress  being processed / read (a draft counts here too)
     *   draft        lab results saved as a draft (also "in progress")
     *   completed    finished, waiting for the department to release it
     *   released     result released, ready to print
     *   cancelled
     *
     * Returns the stage, the tags it is listed/counted under, the badges
     * to show, who is handling it, and short / long notes.
     */
    public static function monitorStage(array $row): array
    {
        $type   = ($row['type'] ?? '') === self::TYPE_XRAY ? self::TYPE_XRAY : self::TYPE_LAB;
        $isXray = $type === self::TYPE_XRAY;
        $status = (string) ($row['db_status'] ?? $row['status'] ?? self::STATUS_PENDING);
        $cols     = self::STAFF_COLUMNS[$type];
        $assignee = trim((string) ($row['assigned_to_name'] ?? ''));
        $staff    = trim((string) ($row[$cols['name']] ?? '')) ?: $assignee;
        $role     = $isXray ? 'radiologist' : 'med tech';
        $dept   = $isXray ? 'X-ray' : 'lab';

        $stage = $status;
        if ($status === self::STATUS_IN_PROGRESS && self::isUntouched(['type' => $type, 'status' => $status] + $row)) {
            $stage = 'queued';
        }

        $labels = [
            'pending'     => ['Pending', 'pending'],
            'queued'      => ['Waiting for ' . $dept, 'queued'],
            'in_progress' => [$isXray ? 'In reading' : 'In progress', 'progress'],
            'draft'       => ['Draft', 'draft'],
            'completed'   => ['Completed', 'completed'],
            'released'    => ['Released', 'released'],
            'cancelled'   => ['Cancelled', 'cancelled'],
        ];

        $tags   = $stage === self::STATUS_DRAFT ? [self::STATUS_IN_PROGRESS, self::STATUS_DRAFT] : [$stage];
        $badges = [];
        foreach ($tags as $t) {
            [$label, $tone] = $labels[$t] ?? [ucfirst(str_replace('_', ' ', $t)), 'pending'];
            $badges[] = ['key' => $t, 'label' => $label, 'tone' => $tone];
        }

        $by = $staff !== '' ? $staff : 'the ' . $role;
        [$short, $note] = match ($stage) {
            'pending'     => ['Not started', 'Not started yet. Start it to record the payment and send it to the ' . $dept . '.'],
            'queued'      => $assignee !== ''
                ? ['Assigned · ' . $assignee, 'Paid and assigned to ' . $assignee . ', who has not started on it yet.']
                : ['Waiting for ' . $role, 'Paid and sent. No ' . $role . ' has started on it yet.'],
            'in_progress' => [($isXray ? 'Reading: ' : 'Processing: ') . $by, ($isXray ? 'Being read by ' : 'Being processed by ') . $by . '.'],
            'draft'       => ['Draft · ' . $by, 'Results saved as a draft by ' . $by . '. Still in progress.'],
            'completed'   => ['Awaiting release · ' . $by, 'Completed by ' . $by . '. Waiting for the ' . $role . ' to release it.'],
            'released'    => ['Released', 'Released' . ($staff !== '' ? ' by ' . $staff : '') . '. Ready to print.'],
            'cancelled'   => ['Cancelled', 'This request was cancelled.'],
            default       => [ucfirst($stage), ''],
        };

        return [
            'stage'  => $stage,
            'tags'   => $tags,
            'badges' => $badges,
            'staff'    => $staff,
            'assignee' => $assignee,
            'short'  => $short,
            'note'   => $note,
        ];
    }

    /**
     * Every status a row should be listed under in the department views.
     * A draft is listed under both "draft" and "in_progress".
     */
    public static function statusTags(string $status): array
    {
        return $status === self::STATUS_DRAFT
            ? [self::STATUS_IN_PROGRESS, self::STATUS_DRAFT]
            : [$status];
    }

    /** Replace each row's status with the department view (keeps the stored one in db_status). */
    public static function withDepartmentStatus(array $rows): array
    {
        foreach ($rows as &$row) {
            $row['db_status'] = $row['status'] ?? null;
            $row['status']    = self::departmentStatus($row);
        }
        unset($row);
        return $rows;
    }

    // ================================================================
    // ROW DECORATION
    // ================================================================
    // Every read path funnels through here. Centralising the alias
    // logic means it can never drift between methods.
    // ================================================================

    /**
     * Decorate a single row with the legacy column aliases.
     * Safe to call twice on the same row.
     */
    protected function decorateRow(?array $row): ?array
    {
        if (!is_array($row)) {
            return $row;
        }

        $type     = (string) ($row['type'] ?? '');
        $services = $row['services']     ?? null;
        $date     = $row['request_date'] ?? null;

        // Merged names — always present.
        $row['services']     = $services;
        $row['request_date'] = $date;

        // Legacy lab name.
        if (!isset($row['lab_services'])) {
            $row['lab_services'] = $type === self::TYPE_LAB ? $services : null;
        }

        // Legacy xray names.
        if (!isset($row['exam_type'])) {
            $row['exam_type'] = $type === self::TYPE_XRAY ? $services : null;
        }
        if (!isset($row['exam_date'])) {
            $row['exam_date'] = $type === self::TYPE_XRAY ? $date : null;
        }

        // Convenience: expose the "old table" name so anything that
        // branches on which table a row came from can still do so.
        if (!isset($row['legacy_table'])) {
            $row['legacy_table'] = $type === self::TYPE_XRAY
                ? 'xray_examinations'
                : 'lab_requests';
        }

        return $row;
    }

    /**
     * Decorate every row in a result set.
     */
    protected function decorateAll(?array $rows): array
    {
        if (!is_array($rows)) {
            return [];
        }
        return array_map([$this, 'decorateRow'], $rows);
    }

    // ================================================================
    // FIND OVERRIDES — ensure every read path decorates
    // ================================================================

    public function find($id = null)
    {
        return $this->decorateRow(parent::find($id));
    }

    public function findAll(?int $limit = null, int $offset = 0)
    {
        return $this->decorateAll(parent::findAll($limit, $offset));
    }

    public function first()
    {
        return $this->decorateRow(parent::first());
    }

    // ================================================================
    // LIST QUERIES — replacing LabRequestModel::getRequests()
    //                and XrayExaminationModel::getExaminations()
    // ================================================================

    /**
     * Legacy lab-style list.
     *
     * If $status is provided, only rows with that status are returned.
     * If $type is not null, results are restricted to one diagnostic
     * type ('lab' or 'xray'). When omitted, both types are returned.
     */
    public function getRequests($status = null, $limit = null, ?string $type = null)
    {
        $this->resetQuery();

        if ($type !== null) {
            $this->where('type', $type);
        }
        if ($status !== null && $status !== '') {
            $this->where('status', $status);
        }
        if ($limit !== null) {
            $this->limit((int) $limit);
        }

        $rows = $this->orderBy('request_date', 'DESC')
                     ->orderBy('created_at', 'DESC')
                     ->findAll();

        return $this->decorateAll($rows);
    }

    /**
     * Legacy xray-style list.
     *
     * Symmetric to getRequests(). Both names are exposed so the old
     * call sites keep working without edits.
     */
    public function getExaminations($status = null, $limit = null, ?string $type = self::TYPE_XRAY)
    {
        return $this->getRequests($status, $limit, $type);
    }

    /**
     * Pending or in-progress rows — lab semantics.
     */
    public function getPendingRequests()
    {
        $this->resetQuery();

        $rows = $this->groupStart()
                        ->where('status', self::STATUS_PENDING)
                        ->orWhere('status', self::STATUS_IN_PROGRESS)
                     ->groupEnd()
                     ->where('type', self::TYPE_LAB)
                     ->orderBy('request_date', 'ASC')
                     ->orderBy('created_at', 'ASC')
                     ->findAll();

        return $this->decorateAll($rows);
    }

    /**
     * Pending or in-progress rows — xray semantics.
     * Old name preserved; delegates to the same query logic.
     */
    public function getPendingExaminations()
    {
        $this->resetQuery();

        $rows = $this->groupStart()
                        ->where('status', self::STATUS_PENDING)
                        ->orWhere('status', self::STATUS_IN_PROGRESS)
                     ->groupEnd()
                     ->where('type', self::TYPE_XRAY)
                     ->orderBy('request_date', 'ASC')
                     ->orderBy('created_at', 'ASC')
                     ->findAll();

        return $this->decorateAll($rows);
    }

    /**
     * Find the first row created from a given appointment.
     * Optionally filter by type so that an appointment with both
     * lab and xray services can look up each independently.
     */
    public function getByAppointment($appointmentId, ?string $type = null)
    {
        $this->resetQuery()->where('appointment_id', (int) $appointmentId);

        if ($type !== null) {
            $this->where('type', $type);
        }

        return $this->decorateRow($this->first());
    }

    // ================================================================
    // STATUS + FINDINGS
    // ================================================================

    /**
     * Update status. When status becomes 'released', stamp released_at.
     * Matches both old models' behaviour.
     */
    public function updateStatus($id, $status)
    {
        $data = ['status' => $status];

        if ($status === self::STATUS_RELEASED) {
            $data['released_at'] = date('Y-m-d H:i:s');
        }

        return $this->update((int) $id, $data);
    }

    /**
     * Save findings.
     *
     * Lab call sites pass (findings, remarks).
     * Xray call sites pass (findings, interpretation).
     *
     * The third parameter is stored into whichever column matches the
     * row's own type, so a single method serves both call sites.
     * The row is loaded first to look up its type.
     */
    public function saveFindings($id, $findings, $remarksOrInterpretation = null)
    {
        $row = parent::find((int) $id);
        if (!$row) {
            return false;
        }

        $update = ['findings' => $findings];

        if ($row['type'] === self::TYPE_XRAY) {
            $update['interpretation'] = $remarksOrInterpretation;
        } else {
            $update['remarks'] = $remarksOrInterpretation;
        }

        // Mark completed when findings are saved — matches the old
        // MedTech behaviour for lab rows and is a safe default for
        // xray rows whose own controller writes status separately.
        if (($row['status'] ?? '') !== self::STATUS_RELEASED) {
            $update['status'] = self::STATUS_COMPLETED;
        }

        return $this->update((int) $id, $update);
    }

    // ================================================================
    // COUNTS
    // ================================================================

    /**
     * Counts for dashboard cards.
     *
     * The returned array carries the union of the keys the two old
     * models produced, so no caller needs to change:
     *   total, pending, in_progress, processing, draft,
     *   completed, released, cancelled
     *
     * Optional $type narrows the count to one diagnostic type.
     */
    public function getCounts(?string $type = null): array
    {
        // One grouped query instead of one COUNT per status.
        $builder = $this->db->table($this->table)->select('status, COUNT(*) AS n');
        if ($type !== null) {
            $builder->where('type', $type);
        }
        $rows   = $builder->groupBy('status')->get()->getResultArray();
        $counts = array_map('intval', array_column($rows, 'n', 'status'));

        $inProgress = $counts[self::STATUS_IN_PROGRESS] ?? 0;

        return [
            'total'       => array_sum($counts),
            'pending'     => $counts[self::STATUS_PENDING] ?? 0,
            'in_progress' => $inProgress,
            'processing'  => $inProgress,   // legacy alias
            'draft'       => $counts[self::STATUS_DRAFT] ?? 0,
            'completed'   => $counts[self::STATUS_COMPLETED] ?? 0,
            'released'    => $counts[self::STATUS_RELEASED] ?? 0,
            'cancelled'   => $counts[self::STATUS_CANCELLED] ?? 0,
        ];
    }


    protected function countAllForType(?string $type): int
    {
        $this->resetQuery();
        if ($type !== null) {
            $this->where('type', $type);
        }
        return (int) $this->countAllResults();
    }

    // ================================================================
    // CREATE FROM APPOINTMENT
    // ================================================================

    /**
     * Create the diagnostic row that corresponds to an approved
     * appointment.
     *
     * The old code had two methods — LabRequestModel::createFromAppointment()
     * and XrayExaminationModel::createFromAppointment() — that differed
     * only in which column they wrote the services list to. This model
     * unifies them: callers pass the type, and the method writes to
     * the merged `services` column.
     *
     * The $appointment array is the row from the appointments table.
     * The caller decides which JSON column to read from
     * (lab_services or xray_services).
     */
    public function createFromAppointment(array $appointment, string $type = self::TYPE_LAB)
    {
        if (!in_array($type, [self::TYPE_LAB, self::TYPE_XRAY], true)) {
            return null;
        }

        $servicesColumn = $type === self::TYPE_XRAY
            ? 'xray_services'
            : 'lab_services';

        $services = json_decode($appointment[$servicesColumn] ?? '[]', true) ?? [];
        $services = array_values(array_filter(array_map('trim', $services), 'strlen'));

        if (empty($services)) {
            return null;
        }

        // Idempotent — if a row already exists for this appointment
        // of this type, return it rather than creating a duplicate.
        $existing = $this->resetQuery()
                         ->where('appointment_id', (int) $appointment['id'])
                         ->where('type', $type)
                         ->first();

        if ($existing) {
            return $this->decorateRow($existing);
        }

        $today = date('Y-m-d');

        $insert = [
            'reference_number' => $this->generateReference($type),
            'type'             => $type,
            'appointment_id'   => (int) $appointment['id'],
            'patient_name'     => (string) ($appointment['full_name'] ?? 'Unknown'),
            'patient_code'     => null,
            'age'              => $appointment['age']    ?? null,
            'gender'           => $appointment['gender'] ?? null,
            'email'            => $appointment['email']  ?? null,
            'phone'            => $appointment['phone']  ?? null,
            'services'         => implode(', ', $services),
            'request_date'     => $appointment['appointment_date'] ?? $today,
            'doctor_name'      => null,
            'priority'         => $type === self::TYPE_XRAY ? 'Routine' : 'routine',
            'status'           => self::STATUS_PENDING,
        ];

        $this->insert($insert);
        return $this->decorateRow(parent::find($this->getInsertID()));
    }

    /**
     * Generate a unique reference number.
     * Format: LAB-YY-NNNN or XR-YY-NNNN.
     */
    protected function generateReference(string $type): string
    {
        $prefix = $type === self::TYPE_XRAY ? 'XR' : 'LAB';
        $year   = date('y');

        for ($attempt = 0; $attempt < 20; $attempt++) {
            $random = strtoupper(bin2hex(random_bytes(3)));
            $ref    = $prefix . '-' . $year . '-' . $random;

            $exists = $this->resetQuery()
                           ->where('reference_number', $ref)
                           ->countAllResults();

            if (!$exists) {
                return $ref;
            }
        }

        // Extreme fallback — should never trigger.
        return $prefix . '-' . $year . '-' . substr((string) microtime(true), -6);
    }

    // ================================================================
    // WEEKLY VOLUME — for the radiologist reports page
    // ================================================================

    /**
     * This week's (Mon–Sun) x-ray studies per day, stacked by modality,
     * for the radiologist dashboard. Counts the studies the department
     * has (paid: in progress, completed, released), like the rest of the
     * dashboard. No studies => empty datasets, so the card shows its
     * empty state instead of a row of zero bars.
     */
    public function getWeeklyVolumeData()
    {
        $weekStart = date('Y-m-d', strtotime('monday this week'));
        $weekEnd   = date('Y-m-d', strtotime('sunday this week'));

        $rows = $this->resetQuery()
                     ->where('type', self::TYPE_XRAY)
                     ->whereIn('status', self::DEPARTMENT_STATUSES)
                     ->where('request_date >=', $weekStart)
                     ->where('request_date <=', $weekEnd)
                     ->orderBy('request_date', 'ASC')
                     ->findAll();

        $days       = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        $modalities = [];

        foreach ($rows as $row) {
            $dayIndex = (int) date('N', strtotime($row['request_date'])) - 1;
            $modality = $this->determineModality($row['services'] ?? '');

            if (!isset($modalities[$modality])) {
                $modalities[$modality] = array_fill(0, 7, 0);
            }
            $modalities[$modality][$dayIndex]++;
        }

        $colors = [
            'X-Ray'       => '#1D4ED8',
            'CT'          => '#0E7490',
            'MRI'         => '#7c3aed',
            'Ultrasound'  => '#16a34a',
            'Mammography' => '#f59e0b',
            'Other'       => '#94A3B8',
        ];

        // Stable stacking / legend order, whatever order the rows came in.
        $datasets = [];
        foreach (array_keys($colors) as $modality) {
            if (!isset($modalities[$modality])) {
                continue;
            }
            $datasets[] = [
                'label'           => $modality,
                'data'            => $modalities[$modality],
                'backgroundColor' => $colors[$modality],
                'borderRadius'    => 4,
                'barPercentage'   => 0.6,
            ];
        }

        return ['labels' => $days, 'datasets' => $datasets];
    }

    /**
     * Classify an exam-type string into one of the known modalities.
     * Keywords match whole words only, so "ct" doesn't fire inside
     * "Fracture". Anything else is a plain radiograph — every study in
     * this department's catalogue (Finger, Wrist AP / APL, ...) is one.
     */
    public function determineModality($examType): string
    {
        $s = strtolower(trim((string) $examType));
        if ($s === '') {
            return 'Other';
        }

        $has = static fn (string $words) => preg_match('/(?<![a-z0-9])(?:' . $words . ')(?![a-z0-9])/', $s) === 1;

        if ($has('ct|ct scan|cat scan|computed tomography')) { return 'CT'; }
        if ($has('mri|magnetic resonance'))                   { return 'MRI'; }
        if ($has('ultrasound|ultrasonography|sono\w*|uts'))   { return 'Ultrasound'; }
        if ($has('mammo\w*'))                                 { return 'Mammography'; }

        return 'X-Ray';
    }

    // ================================================================
    // SOURCE DERIVATION — shared with both controllers
    // ================================================================

    /**
     * Derive the 'Online' vs 'Walk-in' badge for a row.
     *
     * The same rule the two controllers were already implementing
     * inline: a real appointment_id means Online, anything else
     * (0 or NULL) means Walk-in. Centralised here so the rule lives
     * in one place.
     */
    public static function sourceFor(array $row): string
    {
        return !empty($row['appointment_id']) ? 'Online' : 'Walk-in';
    }

    // ================================================================
    // RESET HELPER
    // ================================================================
    // The query builder accumulates where()/orderBy() calls across
    // repeated calls on the same model instance. Since several of
    // these methods are called more than once per request (e.g.
    // getCounts() calls countStatus several times), each one must
    // start from a clean builder. This helper does that.
    // ================================================================

    protected function resetQuery(): self
    {
        $this->builder = null;
        return $this;
    }
}