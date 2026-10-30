<?= $this->extend('Layouts/MedTechLayout') ?>

<?= $this->section('pageTitle') ?>Reports<?= $this->endSection() ?>

<?= $this->section('medtechContent') ?>
<?php include APPPATH . 'Views/Reports/_department_page.php'; ?>
<?= $this->endSection() ?>
