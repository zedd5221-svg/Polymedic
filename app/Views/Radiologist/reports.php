<?= $this->extend('Layouts/RadiologistLayout') ?>

<?= $this->section('pageTitle') ?>Reports<?= $this->endSection() ?>

<?= $this->section('radiologistContent') ?>
<?php include APPPATH . 'Views/Reports/_department_page.php'; ?>
<?= $this->endSection() ?>
