<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#ffffff">
    <title>PolyMedic Diagnostic Center</title>

    <!-- base_url() keeps the path correct on nested routes such as /appointment/book -->
    <link href="<?= base_url('assets/css/AppointmentStyle.css') ?>" rel="stylesheet">

    <link href="<?= base_url('assets/vendor/bootstrap/css/bootstrap.min.css') ?>" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('assets/vendor/bootstrap-icons/bootstrap-icons.min.css') ?>">

    <link href="<?= base_url('assets/vendor/fonts/fonts.css') ?>" rel="stylesheet">
</head>
<body>
    <?= $this->renderSection('AppointmentContent') ?>

    <script src="<?= base_url('assets/vendor/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
</body>
</html>
