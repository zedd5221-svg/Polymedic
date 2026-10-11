<?php

namespace App\Controllers;

use App\Models\AppointmentModel;
use App\Models\ServiceModel;
use App\Models\LabRequestModel;
use App\Models\XrayExaminationModel;
use App\Models\NotificationModel;
use App\Models\PatientModel;
use App\Models\SettingsModel;
use App\Models\AvailabilityModel;

class Appointment extends BaseController
{
    public function index(): string
    {
        return view('Appointment/index');
    }

    public function book(): string
    {
        $serviceModel = new ServiceModel();
        $data['labServices']  = $serviceModel->getServicesByCategory('laboratory');
        $data['xrayServices'] = $serviceModel->getServicesByCategory('xray');

        // NEW: expose scheduling settings so the view can render its
        // own min/max hint without hardcoding 90 days.
        $settingsModel      = new SettingsModel();
        $data['scheduling'] = [
            'open'        => $settingsModel->openTime(),
            'close'       => $settingsModel->closeTime(),
            'interval'    => $settingsModel->slotInterval(),
            'lunch_start' => $settingsModel->lunchStart(),
            'lunch_end'   => $settingsModel->lunchEnd(),
            'horizon'     => $settingsModel->bookingHorizon(),
            'closed_days' => $settingsModel->closedWeekdays(),
            'holidays'    => $settingsModel->holidays(),
        ];

        return view('Appointment/book', $data);
    }

    public function submit()
    {
        $rules = [
            'appointment_date' => 'required|valid_date',
            'appointment_time' => 'required',
            'email'            => 'required|valid_email',
            'phone'            => 'required|min_length[10]',
            'full_name'        => 'required|min_length[2]',
            'age'              => 'required|numeric|greater_than[0]',
            'gender'           => 'required',
            'other_requests'   => 'permit_empty',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $labServices  = $this->request->getPost('lab_services') ?? [];
        $xrayServices = $this->request->getPost('xray_services') ?? [];
        $serviceType  = $this->request->getPost('service_type') ?? 'laboratory';

        $date = (string) $this->request->getPost('appointment_date');
        $time = (string) $this->request->getPost('appointment_time');

        $availability = new AvailabilityModel();

        // Cheap pre-check outside the transaction.
        if (!$availability->isBookable($date, $time, $serviceType)) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Sorry, that time slot just became unavailable. Please choose another.');
        }

        $db = db_connect();
        $db->transStart();

        try {
            // Row-level lock per (date,time) so two simultaneous submits
            // cannot both pass the count check.
            $db->query(
                'SELECT id FROM appointments
                 WHERE appointment_date = ? AND appointment_time = ?
                 FOR UPDATE',
                [$date, $time]
            );

            // Re-check INSIDE the lock.
            if (!$availability->isBookable($date, $time, $serviceType)) {
                $db->transRollback();
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Sorry, that time slot just became unavailable. Please choose another.');
            }

            $reference = 'APPT-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));

            $appointmentData = [
                'reference_number' => $reference,
                'appointment_date' => $date,
                'appointment_time' => $time,
                'full_name'        => $this->request->getPost('full_name'),
                'age'              => $this->request->getPost('age'),
                'gender'           => $this->request->getPost('gender'),
                'email'            => $this->request->getPost('email'),
                'phone'            => $this->request->getPost('phone'),
                'service_type'     => $serviceType,
                'lab_services'     => !empty($labServices) ? json_encode($labServices) : null,
                'xray_services'    => !empty($xrayServices) ? json_encode($xrayServices) : null,
                'other_requests'   => $this->request->getPost('other_requests'),
                'status'           => 'pending',
                'is_override'      => 0,
            ];

            $appointmentModel = new AppointmentModel();
            $appointmentId    = $appointmentModel->insert($appointmentData);

            if (!$appointmentId) {
                $db->transRollback();
                log_message('error', 'Failed to save appointment for: ' . $appointmentData['full_name']);
                return redirect()->back()
                    ->with('error', 'Failed to save appointment. Please try again.')
                    ->withInput();
            }

            $patientModel = new PatientModel();
            $patient      = $patientModel->findOrCreateFromAppointment($appointmentData);

            if ($patient) {
                log_message('info', 'Patient created/found from online booking: '
                    . $appointmentData['full_name'] . ' (Code: ' . $patient['patient_code'] . ')');
            } else {
                log_message('error', 'Failed to create/find patient from online booking: ' . $appointmentData['full_name']);
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                return redirect()->back()
                    ->with('error', 'Failed to save appointment. Please try again.')
                    ->withInput();
            }

        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'Appointment submit error: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to save appointment. Please try again.')
                ->withInput();
        }

        // ============================================================
        // SINGLE dispatch call - NotificationModel::AUDIENCE already
        // fans out to both 'admin' and 'receptionist' with the correct
        // can_open flag per role.
        // ============================================================
        NotificationModel::dispatch(
            'appointment',
            'New Appointment Request: ' . $reference,
            'New appointment request from ' . $appointmentData['full_name']
                . ' for ' . date('M d, Y', strtotime($appointmentData['appointment_date'])),
            $appointmentId,
            null,
            true,
            $appointmentData['gender']
        );

        session()->set('appointment_data', $appointmentData);

        return redirect()->to(base_url('appointment/success/' . $reference))
                         ->with('message', 'Appointment booked successfully!');
    }

    public function success($reference = null)
    {
        $model = new AppointmentModel();
        $data['appointment'] = $model->where('reference_number', $reference)->first();
        $data['reference']   = $reference;
        return view('Appointment/success', $data);
    }

    public function getServicePrices()
    {
        $serviceModel = new ServiceModel();
        $services     = $serviceModel->getActiveServices();

        $prices = [];
        foreach ($services as $service) {
            $prices[$service['service_name']] = (float) $service['charge'];
        }

        return $this->response->setJSON($prices);
    }

    /**
     * AJAX: slots for a date + service type.
     *
     * GET /appointment/slots?date=YYYY-MM-DD&type=laboratory|xray|both
     */
    public function slots()
    {
        $date = (string) $this->request->getGet('date');
        $type = (string) ($this->request->getGet('type') ?? 'laboratory');

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON(['ok' => false, 'error' => 'Invalid date.']);
        }

        $availability = new AvailabilityModel();

        if (!$availability->isWithinHorizon($date)) {
            return $this->response->setJSON([
                'ok'      => false,
                'error'   => 'That date is outside the booking window.',
                'working' => false,
                'slots'   => [],
            ]);
        }

        if (!$availability->isWorkingDay($date)) {
            return $this->response->setJSON([
                'ok'      => true,
                'working' => false,
                'error'   => 'The clinic is closed on that date.',
                'slots'   => [],
            ]);
        }

        $depts = $availability->departmentsFor($type);

        // Merge the lab/xray timelines for 'both' so the picker only
        // offers times where BOTH departments have a free slot.
        $perDept = [];
        foreach ($depts as $d) {
            $perDept[$d] = $availability->slotsFor($date, $d);
        }

        $merged = [];
        foreach ($perDept[$depts[0]] as $i => $slot) {
            $available = $slot['available'];
            foreach ($depts as $d) {
                if (!$perDept[$d][$i]['available']) {
                    $available = false;
                    break;
                }
            }

            $bookedTotal = 0;
            foreach ($depts as $d) {
                $bookedTotal += $perDept[$d][$i]['booked'];
            }

            $merged[] = [
                'time'      => $slot['time'],
                'label'     => $slot['label'],
                'available' => $available,
                'blocked'   => $slot['blocked'],
                'reason'    => $slot['reason'],
                'booked'    => $bookedTotal,
            ];
        }

        return $this->response->setJSON([
            'ok'      => true,
            'working' => true,
            'date'    => $date,
            'type'    => $type,
            'slots'   => $merged,
        ]);
    }
}