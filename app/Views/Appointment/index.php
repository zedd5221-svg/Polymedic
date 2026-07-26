<?= $this->extend('layouts/AppointmentLayout') ?>

<?= $this->section('AppointmentContent') ?>
<!-- Appointment Page Content -->
<nav class="navbar navbar-expand-lg bg-light border-bottom">
    <div class="container">
        <div class="navbar-logo-wrapper">
            <div class="navbar-logo-placeholder">
                <img src="assets/images/logo.png" alt="Logo" class="navbar-logo">
            </div>
            <div class="navbar-brand-wrapper">
                <a class="navbar-brand" href="#">PolyMedic</a>
                <p class="navbar-text">Diagnostic & Laboratory Center</p>
            </div>
        </div>
    </div>
</nav>

<!-- Hero Section -->
<section class="Hero">
    <header class="hero-header py-5">
        <div class="container px-5">
            <div class="row gx-5 justify-content-center">
                <div class="col-lg-6">
                    <div class="text-center my-5">
                        <h1 class="display-5 fw-bolder text-white mb-2">
                            Have an appointment with us
                        </h1>
                        <p class="lead text-white-50 mb-4">
                            Book your appointment online and get the best medical care.
                        </p>
                        <div class="d-grid gap-3 d-sm-flex justify-content-sm-center">
                            <a class="btn btn-outline-light btn-lg px-4" href="#Tutorial">How it works</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>
</section>

<!-- Tutorial Section -->
<section id="Tutorial" class="Tutorial">
    <div class="container px-5 my-5">
        <div class="row gx-5 align-items-center">
            <div class="col-lg-6 order-lg-2">
                <div class="p-5"><img class="img-fluid rounded-circle" src="assets/images/Appointment.png" alt="..." />
                </div>

            </div>

            <div class="col-lg-6 order-lg-1">
                <div class="p-5">
                    <h2 class="display-4">How to book an appointment</h2>
                    <p>Booking an appointment with us is easy and convenient. Follow these simple steps to schedule your
                        visit:</p>
                    <ol>
                        
                        <li>Visit our website and navigate to the appointment booking page.</li>
                        <li>Select the date that works best for you.</li>
                        <li>Fill in your personal information and any specific requirements.</li>
                        <li>Review your appointment details and confirm your booking.</li>
                       
                    </ol>
                </div>
            </div>
        </div>
    </div>


</section>
 <div class="d-flex justify-content-center align-items-center">
    <button class="btn btn-primary submit-btn">Book Now</button>
</div>
<footer class="py-2 bg-dark text-white">
    <div class="container px-5">
        <p class="text-center mb-0">&copy; 2026 PolyMedic. All rights reserved.</p>
    </div>
</footer>

<?= $this->endSection() ?>