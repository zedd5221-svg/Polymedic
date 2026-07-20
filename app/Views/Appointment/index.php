<?= $this->extend('layouts/AppointmentLayout') ?>

<?= $this->section('AppointmentContent') ?>

      <nav class="navbar navbar-expand-lg bg-light border-bottom">
            <div class="container">
                <div class="navbar-logo-wrapper">
                    <div class="navbar-logo-placeholder">
                        <img src="<?= base_url('assets/images/logo.png') ?>" alt="Logo" class="navbar-logo">
                    </div>
                    <div class="navbar-brand-wrapper">
                        <a class="navbar-brand" href="#">PolyMedic</a>
                        <p class="navbar-text">Diagnostic & Laboratory Center</p>
                    </div>
                </div>
            </div>
      </nav>        
<?= $this->endSection() ?>