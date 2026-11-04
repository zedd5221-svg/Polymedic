<?= $this->extend('Layouts/AdminLayout') ?>

<?= $this->section('pageTitle') ?><?= ($viewMode ?? 'appointment') === 'patient' ? 'Patient Details' : 'Appointment Details' ?><?= $this->endSection() ?>

<?= $this->section('adminContent') ?>
<?= view('partials/appointment_detail', [
    'role'          => 'admin',
    'appointment'   => $appointment ?? [],
    'viewMode'      => $viewMode ?? 'appointment',
    'lab_services'  => $lab_services ?? [],
    'xray_services' => $xray_services ?? [],
    'patient'       => $patient ?? null,
]) ?>
<?= $this->endSection() ?>
