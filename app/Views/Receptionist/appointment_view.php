<?= $this->extend('Layouts/ReceptionistLayout') ?>

<?= $this->section('pageTitle') ?><?= ($viewMode ?? 'appointment') === 'patient' ? 'Patient Details' : 'Appointment Details' ?><?= $this->endSection() ?>

<?= $this->section('receptionistContent') ?>
<?= view('partials/appointment_detail', [
    'role'          => 'receptionist',
    'appointment'   => $appointment ?? [],
    'viewMode'      => $viewMode ?? 'appointment',
    'lab_services'  => $lab_services ?? [],
    'xray_services' => $xray_services ?? [],
    'patient'       => $patient ?? null,
]) ?>
<?= $this->endSection() ?>
