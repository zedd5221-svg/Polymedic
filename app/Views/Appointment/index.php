<?= $this->extend('layouts/AppointmentLayout') ?>

<?= $this->section('AppointmentContent') ?>
<!-- Appointment Page Content -->
<nav class="navbar navbar-expand-lg bg-light border-bottom">
    <div class="container">
        <div class="navbar-logo-wrapper">
            <div class="navbar-logo-placeholder">
                <img src="assets/images/logo2.png" alt="Logo" class="navbar-logo">
            </div>
            <div class="navbar-brand-wrapper">
                <a class="navbar-brand" href="#">COTABATO POLYMEDIC</a>
                <p class="navbar-text">& Diagnostic Center, inc</p>
            </div>
        </div>
    </div>
</nav>

<!-- Hero Section -->
<section class="Hero" data-aos="fade-up">
    <header class="hero-header py-5">
        <div class="container px-5">
            <div class="row gx-5 justify-content-center">
                <div class="col-lg-6">
                    <div class="text-center my-5">
                        <h1 class="display-5 fw-bolder text-white mb-2" data-aos="fade-up" data-aos-delay="50">
                            Have an appointment with us
                        </h1>
                        <p class="lead text-white-50 mb-4" data-aos="fade-up" data-aos-delay="100">
                            Book your appointment online and get the best medical care.
                            <a href="https://www.google.com/maps/place/Cotabato+Polymedic+and+Diagnostic+Center/@7.1978084,124.241251,17z/data=!3m1!4b1!4m6!3m5!1s0x32563a20c200452b:0x2ec643833068de46!8m2!3d7.1978031!4d124.2438313!16s%2Fg%2F1tltjwz7?entry=ttu&g_ep=EgoyMDI2MDcyNy4wIKXMDSoASAFQAw%3D%3D" target="_blank" class="mb-4 text-white-50">Governor Gutierez Ave, Cotabato City 9600</a>
                        </p>
                        <div class="d-grid gap-3 d-sm-flex justify-content-sm-center" data-aos="fade-up" data-aos-delay="150">
                            <a class="btn btn-outline-light btn-lg px-4" href="#Tutorial">How it works</a>
                            <a class="btn btn-light btn-lg px-4" href="#Services">Services</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>
</section>


<!-- Services Section -->
 <!-- Betad nengka e ngini mapya papegkeran lun like ngini offer na hospital antu -->
<section id="Services" class="Services" data-aos="fade-up">

 <!-- Betad nengka e ngini mapya papegkeran lun like ngini offer na hospital antu -->
   <!-- Betad nengka e ngini mapya papegkeran lun like ngini offer na hospital antu -->
</section>




<!-- Tutorial Section -->
<section id="Tutorial" class="Tutorial" data-aos="fade-up">
    <div class="container px-5 my-5">
        <div class="row gx-5 align-items-center">
            <div class="col-lg-6 order-lg-2" data-aos="fade-up">
                <div class="p-5"><img class="img-fluid rounded-circle" src="assets/images/Appointment.png" alt="..." />
                </div>

            </div>

            <div class="col-lg-6 order-lg-1" data-aos="fade-up" data-aos-delay="100">
                <div class="p-5">
                    <h2 class="display-4">How to book an appointment</h2>
                    <p>Booking an appointment with us is easy and convenient. Follow these simple steps to schedule your
                        visit:</p>
                    <ol data-aos="fade-up" data-aos-delay="150">
                        
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