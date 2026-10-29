<?php

namespace App\Controllers\Concerns;

use App\Models\AppointmentModel;
use App\Models\DiagnosticRequestModel;
use App\Models\LabResultModel;
use App\Models\NotificationModel;
use App\Models\PatientModel;
use App\Models\PaymentModel;
use App\Models\ServiceModel;
use App\Models\UserModel;

/*
 * Front-desk workflow shared by the Admin and Receptionist controllers.
 *
 * Both roles approve appointments, start (and charge) diagnostic
 * requests, print slips/results and list patients. These used to be
 * two hand-maintained copies that had drifted apart; keep the single
 * implementation here.
 */
trait HandlesDiagnosticRequests
{
    /* ---------------------------------------------------------
       APPROVAL -> DIAGNOSTIC REQUESTS
       --------------------------------------------------------- */

    /**
     * Create the lab / x-ray request rows for an approved appointment.
     * Idempotent: an existing row of the same type is left alone.
     *
     * @return string[] Human-readable notes for the flash message
     */
    protected function createRequestsFromAppointment(array $appointment, ?string $patientCode): array
    {
        $model = new DiagnosticRequestModel();
        $notes = [];

        $departments = [
            DiagnosticRequestModel::TYPE_LAB  => ['column' => 'lab_services',  'label' => 'Lab',   'prefix' => 'LAB', 'priority' => 'routine'],
            DiagnosticRequestModel::TYPE_XRAY => ['column' => 'xray_services', 'label' => 'X-Ray', 'prefix' => 'XR',  'priority' => 'Routine'],
        ];

        foreach ($departments as $type => $cfg) {
            $services = $this->cleanServiceList($appointment[$cfg['column']] ?? null);
            if ($services === []) {
                continue;
            }

            $existing = $model->where('appointment_id', (int) $appointment['id'])
                              ->where('type', $type)
                              ->first();
            if ($existing) {
                $notes[] = $cfg['label'] . ' request already exists.';
                continue;
            }

            $model->insert([
                'reference_number' => $cfg['prefix'] . '-' . date('y') . '-' . strtoupper(bin2hex(random_bytes(3))),
                'type'             => $type,
                'appointment_id'   => (int) $appointment['id'],
                'patient_name'     => $appointment['full_name'],
                'patient_code'     => $patientCode,
                'age'              => $appointment['age'],
                'gender'           => $appointment['gender'],
                'email'            => $appointment['email'] ?? null,
                'phone'            => $appointment['phone'] ?? null,
                'services'         => implode(', ', $services),
                'request_date'     => $appointment['appointment_date'] ?: date('Y-m-d'),
                'priority'         => $cfg['priority'],
                'status'           => DiagnosticRequestModel::STATUS_PENDING,
            ]);

            // The department links open /request/view/{id}, so the
            // reference must be the new request id, not the appointment id.
            NotificationModel::dispatch(
                $type,
                'New ' . $cfg['label'] . ' Request (Online Booking)',
                'New ' . strtolower($cfg['label']) . ' request for ' . $appointment['full_name'],
                (int) $model->getInsertID(),
                null,
                true,
                $appointment['gender'] ?? null
            );

            $notes[] = $cfg['label'] . ' request created.';
        }

        return $notes;
    }

    /** Decode a JSON service list from the appointments table into clean names. */
    protected function cleanServiceList($raw): array
    {
        $list = is_array($raw) ? $raw : (json_decode((string) $raw, true) ?? []);
        if (! is_array($list)) {
            return [];
        }

        $out = [];
        foreach ($list as $service) {
            $service = trim(stripslashes(str_replace('\/', '/', (string) $service)));
            if ($service !== '') {
                $out[] = $service;
            }
        }
        return array_values(array_unique($out));
    }

    /* ---------------------------------------------------------
       STATUS CHANGES (the "Start" button records the payment)
       --------------------------------------------------------- */

    /**
     * Apply a status change requested from the diagnostic-requests page.
     * Returns the JSON payload for the AJAX caller.
     */
    protected function changeDiagnosticStatus(int $id, string $type, string $status): array
    {
        $type   = strtolower($type);
        $status = strtolower($status);

        if (! in_array($type, [DiagnosticRequestModel::TYPE_LAB, DiagnosticRequestModel::TYPE_XRAY], true)) {
            return ['success' => false, 'message' => 'Unknown request type.'];
        }
        if (! in_array($status, ['pending', 'in_progress', 'completed', 'released', 'cancelled'], true)) {
            return ['success' => false, 'message' => 'Unknown status.'];
        }

        $model = new DiagnosticRequestModel();
        $row   = $model->where('id', $id)->where('type', $type)->first();

        if (! $row) {
            return ['success' => false, 'message' => 'Request not found.'];
        }

        if ($status === DiagnosticRequestModel::STATUS_IN_PROGRESS) {
            return $this->startProcessing($row, $type);
        }

        $update = ['status' => $status];
        if ($status === DiagnosticRequestModel::STATUS_RELEASED) {
            $update['released_at'] = date('Y-m-d H:i:s');
        }
        $model->update($id, $update);

        return ['success' => true, 'message' => 'Status updated.', 'payment_recorded' => false];
    }

    /**
     * Move a request to in_progress, recording its payment first.
     * The payment is the gate MedTech/Radiologist check before any
     * result can be entered, so the status never advances without it.
     */
    private function startProcessing(array $row, string $type): array
    {
        $id           = (int) $row['id'];
        $model        = new DiagnosticRequestModel();
        $paymentModel = new PaymentModel();
        $current      = (string) ($row['status'] ?? '');

        // Never drag a request that is already further along back to in_progress.
        if (! in_array($current, [DiagnosticRequestModel::STATUS_PENDING, DiagnosticRequestModel::STATUS_CANCELLED, DiagnosticRequestModel::STATUS_IN_PROGRESS], true)) {
            return ['success' => false, 'message' => 'This request is already ' . str_replace('_', ' ', $current) . '.'];
        }

        $existing = $paymentModel->getByRequest($id, $type);
        if ($existing) {
            $model->update($id, ['status' => DiagnosticRequestModel::STATUS_IN_PROGRESS]);
            return [
                'success'          => true,
                'message'          => 'Status updated. Payment already recorded.',
                'payment_recorded' => true,
                'payment_id'       => $existing['id'],
                'amount'           => (float) $existing['total_amount'],
            ];
        }

        [$servicesList, $totalAmount] = $this->priceServices((string) ($row['services'] ?? ''), $id, $type);
        $patientCode = $this->resolvePatientCode($row);

        $paymentId = $paymentModel->recordPayment([
            'request_id'     => $id,
            'request_type'   => $type,
            'patient_name'   => (string) $row['patient_name'],
            'patient_code'   => $patientCode,
            'appointment_id' => $row['appointment_id'] ?? null,
            'services'       => json_encode($servicesList),
            'total_amount'   => $totalAmount,
            'amount_paid'    => $totalAmount,
            'payment_method' => PaymentModel::METHOD_CASH,
            'notes'          => 'Payment recorded when starting processing',
        ]);

        if (! $paymentId) {
            log_message('error', "Failed to record payment for diagnostic request #{$id} ({$type}). Status left at {$current}.");
            return [
                'success' => false,
                'message' => 'Could not record the payment. The status was not changed. Please try again or record the payment manually.',
            ];
        }

        $model->update($id, ['status' => DiagnosticRequestModel::STATUS_IN_PROGRESS]);

        return [
            'success'          => true,
            'message'          => 'Status updated to in progress. Payment recorded: ₱' . number_format($totalAmount, 2),
            'payment_recorded' => true,
            'payment_id'       => $paymentId,
            'amount'           => $totalAmount,
            'patient_code'     => $patientCode,
        ];
    }

    /** @return array{0: array<int,array{name:string,charge:float}>, 1: float} */
    private function priceServices(string $services, int $id, string $type): array
    {
        $catalogue = [];
        foreach ((new ServiceModel())->select('service_name, charge')->where('is_active', 1)->findAll() as $svc) {
            $key = strtolower(trim((string) $svc['service_name']));
            if ($key !== '') {
                $catalogue[$key] = ['name' => (string) $svc['service_name'], 'charge' => (float) $svc['charge']];
            }
        }

        $list  = [];
        $total = 0.0;
        foreach (array_filter(array_map('trim', explode(',', $services)), 'strlen') as $name) {
            $hit = $catalogue[strtolower($name)] ?? null;
            if ($hit === null) {
                log_message('warning', "Diagnostic payment: service \"{$name}\" on request #{$id} ({$type}) has no active price in the services table.");
            }
            $list[]  = ['name' => $hit['name'] ?? $name, 'charge' => $hit['charge'] ?? 0.0];
            $total  += $hit['charge'] ?? 0.0;
        }

        if ($list === []) {
            $list  = [['name' => 'Consultation Fee', 'charge' => 500.00]];
            $total = 500.00;
        }

        return [$list, $total];
    }

    private function resolvePatientCode(array $row): string
    {
        if (! empty($row['patient_code'])) {
            return (string) $row['patient_code'];
        }

        $patients = new PatientModel();
        $patient  = $patients->where('full_name', $row['patient_name'])
                             ->where('age', $row['age'])
                             ->where('gender', $row['gender'])
                             ->first();

        if (! $patient && ! empty($row['email'])) {
            $patient = $patients->where('email', $row['email'])->first();
        }
        if (! $patient) {
            $patient = $patients->where('full_name', $row['patient_name'])->first();
        }

        return $patient['patient_code'] ?? 'N/A';
    }

    /* ---------------------------------------------------------
       PRINTING
       --------------------------------------------------------- */

    /**
     * Released requests print as the department's final report;
     * anything else prints as a request slip.
     */
    protected function renderPrintable(array $row, string $type)
    {
        $row['type'] = $type;

        // "Back" on the printout returns to this role's own request list,
        // not the lab / radiology pages admins and receptionists can't open.
        $back = [
            'backUrl'   => base_url((session()->get('role') === 'admin' ? 'admin' : 'receptionist') . '/diagnostic-requests'),
            'backLabel' => 'Back to diagnostic requests',
        ];

        if (($row['status'] ?? '') !== DiagnosticRequestModel::STATUS_RELEASED) {
            return view('Receptionist/print_request', [
                'request' => $row,
                'type'    => $type,
                'clinic'  => config('Clinic')->toArray(),
            ] + $back);
        }

        return $type === DiagnosticRequestModel::TYPE_XRAY
            ? $this->printXrayResult($row, $back)
            : $this->printLabResult($row, $back);
    }

    private function printXrayResult(array $examination, array $back = [])
    {
        $examination['exam_type'] = $examination['exam_type'] ?? $examination['services'] ?? 'X-Ray Examination';
        $examination['exam_date'] = $examination['exam_date'] ?? $examination['request_date'] ?? ($examination['created_at'] ?? date('Y-m-d'));

        $license = '';
        $name    = trim((string) ($examination['radiologist_name'] ?? ''));
        if ($name !== '') {
            $user    = (new UserModel())->where('full_name', $name)->where('role', 'radiologist')->first();
            $license = trim((string) ($user['prc_license'] ?? ''));
        }

        return view('Radiologist/print_result', [
            'examination'         => $examination,
            'radiologist_license' => $license,
            'appointment'         => ! empty($examination['appointment_id']) ? (new AppointmentModel())->find($examination['appointment_id']) : null,
        ] + $back);
    }

    private function printLabResult(array $request, array $back = [])
    {
        $results = (new LabResultModel())->getByRequest((int) $request['id']);

        return view('MedTech/print_result', [
            'request'          => $request,
            'results'          => $results,
            'appointment'      => ! empty($request['appointment_id']) ? (new AppointmentModel())->find($request['appointment_id']) : null,
            'technologistUser' => $this->resolveLabTechnologist($request),
            'pathologistUser'  => null,
        ] + $back);
    }

    /**
     * The Medical Technologist who signs a lab report: the one recorded
     * on the request (med_tech_name), else the first active med tech.
     * Never the admin or receptionist doing the reprint.
     */
    private function resolveLabTechnologist(array $request): ?array
    {
        $users = new UserModel();
        $name  = trim((string) ($request['med_tech_name'] ?? ''));

        if ($name !== '') {
            $user = $users->where('full_name', $name)->whereIn('role', ['med_tech', 'technologist'])->first();
            if ($user) {
                return $user;
            }
        }

        return $users->whereIn('role', ['med_tech', 'technologist'])
                     ->where('status', 'active')
                     ->orderBy('id', 'ASC')
                     ->first() ?: null;
    }

    /* ---------------------------------------------------------
       PATIENT LIST
       --------------------------------------------------------- */

    /**
     * Every patient known to the system: the patients table first, then
     * anyone who only appears on appointments or diagnostic requests.
     * De-duplicated on name + age + gender, newest visit first.
     */
    protected function collectPatients(): array
    {
        $db       = db_connect();
        $patients = [];
        $seen     = [];
        $idByName = []; // "name|age" => patients.id, replaces a lookup per row

        $add = static function (array $row, string $source) use (&$patients, &$seen, &$idByName): void {
            $name = strtolower(trim((string) ($row['full_name'] ?? '')));
            $key  = $name . '|' . ($row['age'] ?? '') . '|' . ($row['gender'] ?? '');
            if ($name === '' || isset($seen[$key])) {
                return;
            }
            $seen[$key] = true;

            $patients[] = [
                'id'           => (int) ($row['id'] ?? ($idByName[$name . '|' . ($row['age'] ?? '')] ?? $idByName[$name . '|'] ?? 0)),
                'patient_code' => $row['patient_code'] ?? 'N/A',
                'full_name'    => $row['full_name'],
                'email'        => $row['email'] ?? '',
                'phone'        => $row['phone'] ?? '',
                'age'          => $row['age'] ?? '',
                'gender'       => $row['gender'] ?? '',
                'source'       => $source !== '' ? $source : ucfirst((string) ($row['source'] ?? 'Unknown')),
                'last_visit'   => $row['last_visit'] ?? null,
                'created_at'   => $row['created_at'] ?? null,
                'status'       => 'active',
            ];
        };

        try {
            $rows = $db->table('patients')
                ->select('id, patient_code, full_name, email, phone, age, gender, source, created_at, created_at AS last_visit')
                ->orderBy('created_at', 'DESC')
                ->get()->getResultArray();

            foreach ($rows as $row) {
                $name = strtolower(trim((string) $row['full_name']));
                $idByName[$name . '|' . $row['age']] ??= (int) $row['id'];
                $idByName[$name . '|']               ??= (int) $row['id'];
            }
            foreach ($rows as $row) {
                $add($row, '');
            }
        } catch (\Throwable $e) {
            log_message('error', 'collectPatients (patients): ' . $e->getMessage());
        }

        try {
            $rows = $db->table('appointments')
                ->select('full_name, MAX(email) AS email, MAX(phone) AS phone, age, gender, MAX(appointment_date) AS last_visit, MIN(created_at) AS created_at')
                ->whereIn('status', ['approved', 'completed'])
                ->groupBy(['full_name', 'age', 'gender'])
                ->get()->getResultArray();
            foreach ($rows as $row) {
                $add($row, 'Online');
            }
        } catch (\Throwable $e) {
            log_message('error', 'collectPatients (appointments): ' . $e->getMessage());
        }

        try {
            $rows = $db->table('diagnostic_requests')
                ->select('patient_name AS full_name, age, gender, type, MAX(request_date) AS last_visit, MIN(created_at) AS created_at')
                ->whereIn('status', ['pending', 'in_progress', 'draft', 'completed', 'released'])
                ->groupBy(['patient_name', 'age', 'gender', 'type'])
                ->get()->getResultArray();
            foreach ($rows as $row) {
                $add($row, $row['type'] === DiagnosticRequestModel::TYPE_XRAY ? 'Walk-in (X-Ray)' : 'Walk-in (Lab)');
            }
        } catch (\Throwable $e) {
            log_message('error', 'collectPatients (diagnostic_requests): ' . $e->getMessage());
        }

        usort($patients, static fn ($a, $b) => strtotime($b['last_visit'] ?? '0') <=> strtotime($a['last_visit'] ?? '0'));

        return $patients;
    }

    /* ---------------------------------------------------------
       PAYMENTS / APPOINTMENT HELPERS
       --------------------------------------------------------- */

    /** Sum of money actually collected (amount_paid) between two dates. */
    protected function collectedBetween(string $from, string $to): float
    {
        $row = db_connect()->table('payments')
            ->selectSum('amount_paid', 'total')
            ->where('payment_status', PaymentModel::STATUS_PAID)
            ->where('payment_date >=', $from . ' 00:00:00')
            ->where('payment_date <=', $to . ' 23:59:59')
            ->get()->getRow();

        return (float) ($row->total ?? 0);
    }

    /** Add each payment's patient gender (for the avatar) in one query. */
    protected function attachPatientGender(array &$payments): void
    {
        $codes = [];
        foreach ($payments as $p) {
            $code = trim((string) ($p['patient_code'] ?? ''));
            if ($code !== '' && strtoupper($code) !== 'N/A') {
                $codes[$code] = true;
            }
        }

        $genderByCode = [];
        if ($codes !== []) {
            $rows = (new PatientModel())
                ->select('patient_code, gender')
                ->whereIn('patient_code', array_keys($codes))
                ->findAll();
            $genderByCode = array_column($rows, 'gender', 'patient_code');
        }

        foreach ($payments as &$p) {
            $p['gender'] = $genderByCode[trim((string) ($p['patient_code'] ?? ''))] ?? null;
        }
        unset($p);
    }

    /**
     * Diagnostic-request tab counts from each row's monitoring stage
     * (row['monitor']['tags']). A draft is counted under both
     * "in_progress" and "draft"; "total" counts every row once.
     */
    protected function monitoringCounts(array $requests): array
    {
        $counts = array_fill_keys(['pending', 'queued', 'in_progress', 'draft', 'completed', 'released', 'cancelled'], 0);
        foreach ($requests as $r) {
            foreach ($r['monitor']['tags'] ?? [$r['status'] ?? 'pending'] as $tag) {
                if (isset($counts[$tag])) {
                    $counts[$tag]++;
                }
            }
        }
        $counts['total']      = count($requests);
        $counts['processing'] = $counts['in_progress'];   // legacy key
        return $counts;
    }

    /** Appointment totals per status in one grouped query. */
    protected function appointmentStatusCounts(): array
    {
        $rows   = db_connect()->table('appointments')->select('status, COUNT(*) AS n')->groupBy('status')->get()->getResultArray();
        $counts = array_map('intval', array_column($rows, 'n', 'status'));

        return [
            'total'     => array_sum($counts),
            'pending'   => $counts['pending']   ?? 0,
            'approved'  => $counts['approved']  ?? 0,
            'completed' => $counts['completed'] ?? 0,
            'cancelled' => $counts['cancelled'] ?? 0,
            'late'      => $counts['late']      ?? 0,
        ];
    }

    /* ---------------------------------------------------------
       PATIENT DETAIL PAGE
       --------------------------------------------------------- */

    /**
     * Data for the patient detail page, shaped like an appointment so
     * the existing appointment_view template can render it.
     */
    protected function patientViewData(array $patient): array
    {
        $source = strtolower((string) ($patient['source'] ?? ''));

        $appointment = [
            'id'               => $patient['id'] ?? null,
            'reference_number' => $patient['patient_code'] ?? '—',
            'full_name'        => $patient['full_name'] ?? '',
            'gender'           => $patient['gender'] ?? '',
            'age'              => $patient['age'] ?? '',
            'email'            => $patient['email'] ?? '',
            'phone'            => $patient['phone'] ?? '',
            'appointment_date' => $patient['created_at'] ?? null,
            'appointment_time' => null,
            'arrival_time'     => null,
            'created_at'       => $patient['created_at'] ?? null,
            'updated_at'       => $patient['updated_at'] ?? null,
            'status'           => 'approved',
            'service_type'     => strpos($source, 'walk') !== false ? 'Walk-in' : 'Online',
            'other_requests'   => '',
        ];

        $lab  = [];
        $xray = [];
        try {
            $rows = (new DiagnosticRequestModel())
                ->select('type, services')
                ->where('patient_name', $appointment['full_name'])
                ->orderBy('created_at', 'DESC')
                ->findAll(20);

            foreach ($rows as $row) {
                $services = array_filter(array_map('trim', explode(',', (string) ($row['services'] ?? ''))));
                if (($row['type'] ?? '') === DiagnosticRequestModel::TYPE_XRAY) {
                    $xray = array_merge($xray, $services);
                } else {
                    $lab = array_merge($lab, $services);
                }
            }
        } catch (\Throwable $e) {
            log_message('error', 'patientViewData history: ' . $e->getMessage());
        }

        return [
            'appointment'   => $appointment,
            'lab_services'  => array_values(array_unique($lab)),
            'xray_services' => array_values(array_unique($xray)),
            'viewMode'      => 'patient',
        ];
    }
}
