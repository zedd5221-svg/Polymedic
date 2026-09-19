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
        'med_tech_name', 'remarks',
        'radiologist_name', 'interpretation',
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
        $typeFilter = $type !== null ? ['type' => $type] : [];

        $countStatus = function (string $status) use ($typeFilter) {
            $this->resetQuery();
            foreach ($typeFilter as $k => $v) {
                $this->where($k, $v);
            }
            return (int) $this->where('status', $status)->countAllResults();
        };

        $inProgress = $countStatus(self::STATUS_IN_PROGRESS);

        return [
            'total'       => $this->countAllForType($type),
            'pending'     => $countStatus(self::STATUS_PENDING),
            'in_progress' => $inProgress,
            'processing'  => $inProgress,   // legacy alias
            'draft'       => $countStatus(self::STATUS_DRAFT),
            'completed'   => $countStatus(self::STATUS_COMPLETED),
            'released'    => $countStatus(self::STATUS_RELEASED),
            'cancelled'   => $countStatus(self::STATUS_CANCELLED),
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
     * Weekly volume by modality. Replaces the equivalent method on
     * the old XrayExaminationModel.
     */
    public function getWeeklyVolumeData()
    {
        $weekStart = date('Y-m-d', strtotime('monday this week'));
        $weekEnd   = date('Y-m-d', strtotime('sunday this week'));

        $rows = $this->resetQuery()
                     ->where('type', self::TYPE_XRAY)
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

        $datasets = [];
        foreach ($modalities as $modality => $data) {
            $datasets[] = [
                'label'           => $modality,
                'data'            => $data,
                'backgroundColor' => $colors[$modality] ?? '#94A3B8',
                'borderRadius'    => 4,
                'barPercentage'   => 0.6,
            ];
        }

        if (empty($datasets)) {
            $datasets[] = [
                'label'           => 'X-Ray',
                'data'            => [0, 0, 0, 0, 0, 0, 0],
                'backgroundColor' => '#1D4ED8',
                'borderRadius'    => 4,
                'barPercentage'   => 0.6,
            ];
        }

        return ['labels' => $days, 'datasets' => $datasets];
    }

    /**
     * Classify an exam-type string into one of the known modalities.
     * Same logic as the old XrayExaminationModel::determineModality().
     */
    public function determineModality($examType): string
    {
        if (empty($examType)) {
            return 'Other';
        }

        $s = strtolower((string) $examType);

        if (str_contains($s, 'ct') || str_contains($s, 'cat scan')) { return 'CT'; }
        if (str_contains($s, 'mri'))                                  { return 'MRI'; }
        if (str_contains($s, 'ultrasound')
            || str_contains($s, 'sono'))                              { return 'Ultrasound'; }
        if (str_contains($s, 'mammo'))                                { return 'Mammography'; }
        if (str_contains($s, 'x-ray')
            || str_contains($s, 'xray')
            || str_contains($s, 'chest'))                             { return 'X-Ray'; }

        foreach (['chest','abdomen','spine','extremity','skull','bone','joint'] as $kw) {
            if (str_contains($s, $kw)) { return 'X-Ray'; }
        }

        return 'Other';
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