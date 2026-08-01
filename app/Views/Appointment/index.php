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

<!-- Enhanced Services Section -->
<section id="Services" class="Services py-5" data-aos="fade-up" style="background: linear-gradient(180deg, #f8faff 0%, #ffffff 100%);">
    <div class="container">
        <!-- Section Header with decorative elements -->
        <div class="text-center mb-5" data-aos="fade-up">
            <div class="d-flex align-items-center justify-content-center mb-3">
                <div style="height: 3px; width: 60px; background: linear-gradient(90deg, transparent, #0148ca);"></div>
                <span class="mx-3 text-primary fw-bold text-uppercase small tracking-wide">Services</span>
                <div style="height: 3px; width: 60px; background: linear-gradient(90deg, #0148ca, transparent);"></div>
            </div>
            <h2 class="display-4 fw-bold" style="color: #0a2b4e;">Our Diagnostic <span style="color: #0148ca;">Services</span></h2>
            <p class="lead text-muted" style="max-width: 600px; margin: 0 auto;">Comprehensive laboratory &amp; imaging services — no hidden costs, just quality care.</p>
        </div>

        <div class="row g-4">
            <!-- Laboratory Services - Enhanced -->
            <div class="col-lg-6" data-aos="fade-up" data-aos-delay="100">
                <div class="card h-100 border-0 shadow-lg rounded-4 overflow-hidden" style="transition: transform 0.3s ease, box-shadow 0.3s ease;">
                    <div class="card-header bg-gradient-primary p-4" style="background: linear-gradient(135deg, #0148ca 0%, #0a2b4e 100%); border: none;">
                        <div class="d-flex align-items-center">
                            <div class="bg-white bg-opacity-20 p-3 rounded-3 me-3">
                                <i class="bi bi-micoscope fs-2 text-white"></i>
                            </div>
                            <div>
                                <h3 class="card-title h3 mb-0 fw-bold text-white">Clinical Laboratory</h3>
                                <p class="text-white-50 small mb-0"><i class="bi bi-droplet me-1"></i> Chemistry · Hematology · Serology</p>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-4" style="background: #ffffff;">
                        <!-- Clinical Chemistry -->
                        <div class="mb-3">
                            <div class="d-flex align-items-center mb-2">
                                <div class="bg-primary bg-opacity-10 p-2 rounded-2 me-2">
                                    <i class="bi bi-flask text-primary"></i>
                                </div>
                                <h5 class="mb-0 fw-bold" style="color: #0a2b4e;">Clinical Chemistry</h5>
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                <span class="badge bg-light text-dark px-3 py-2 rounded-pill border border-primary border-opacity-10">Glucose (RBS/FBS)</span>
                                <span class="badge bg-light text-dark px-3 py-2 rounded-pill border border-primary border-opacity-10">HbA1c</span>
                                <span class="badge bg-light text-dark px-3 py-2 rounded-pill border border-primary border-opacity-10">Cholesterol</span>
                                <span class="badge bg-light text-dark px-3 py-2 rounded-pill border border-primary border-opacity-10">Triglycerides</span>
                                <span class="badge bg-light text-dark px-3 py-2 rounded-pill border border-primary border-opacity-10">HDL / LDL</span>
                            </div>
                        </div>

                        <!-- Kidney & Liver -->
                        <div class="mb-3">
                            <div class="d-flex align-items-center mb-2">
                                <div class="bg-success bg-opacity-10 p-2 rounded-2 me-2">
                                    <i class="bi bi-cpu text-success"></i>
                                </div>
                                <h5 class="mb-0 fw-bold" style="color: #0a2b4e;">Kidney &amp; Liver</h5>
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                <span class="badge bg-light text-dark px-3 py-2 rounded-pill border border-success border-opacity-10">Creatinine</span>
                                <span class="badge bg-light text-dark px-3 py-2 rounded-pill border border-success border-opacity-10">Blood Uric Acid</span>
                                <span class="badge bg-light text-dark px-3 py-2 rounded-pill border border-success border-opacity-10">BUN</span>
                                <span class="badge bg-light text-dark px-3 py-2 rounded-pill border border-success border-opacity-10">SGPT/ALT</span>
                                <span class="badge bg-light text-dark px-3 py-2 rounded-pill border border-success border-opacity-10">SGOT/AST</span>
                            </div>
                        </div>

                        <!-- Electrolytes -->
                        <div class="mb-3">
                            <div class="d-flex align-items-center mb-2">
                                <div class="bg-info bg-opacity-10 p-2 rounded-2 me-2">
                                    <i class="bi bi-droplet-half text-info"></i>
                                </div>
                                <h5 class="mb-0 fw-bold" style="color: #0a2b4e;">Electrolytes</h5>
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                <span class="badge bg-light text-dark px-3 py-2 rounded-pill border border-info border-opacity-10">Sodium (Na)</span>
                                <span class="badge bg-light text-dark px-3 py-2 rounded-pill border border-info border-opacity-10">Potassium (K)</span>
                                <span class="badge bg-light text-dark px-3 py-2 rounded-pill border border-info border-opacity-10">Chloride (Cl)</span>
                                <span class="badge bg-light text-dark px-3 py-2 rounded-pill border border-info border-opacity-10">Calcium (Ca)</span>
                            </div>
                        </div>

                        <!-- Clinical Microscopy -->
                        <div class="mb-3">
                            <div class="d-flex align-items-center mb-2">
                                <div class="bg-warning bg-opacity-10 p-2 rounded-2 me-2">
                                    <i class="bi bi-eye text-warning"></i>
                                </div>
                                <h5 class="mb-0 fw-bold" style="color: #0a2b4e;">Clinical Microscopy</h5>
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                <span class="badge bg-light text-dark px-3 py-2 rounded-pill border border-warning border-opacity-10">Urinalysis</span>
                                <span class="badge bg-light text-dark px-3 py-2 rounded-pill border border-warning border-opacity-10">Fecalysis</span>
                                <span class="badge bg-light text-dark px-3 py-2 rounded-pill border border-warning border-opacity-10">Semenanalysis</span>
                            </div>
                        </div>

                        <!-- Hematology -->
                        <div class="mb-3">
                            <div class="d-flex align-items-center mb-2">
                                <div class="bg-danger bg-opacity-10 p-2 rounded-2 me-2">
                                    <i class="bi bi-droplet text-danger"></i>
                                </div>
                                <h5 class="mb-0 fw-bold" style="color: #0a2b4e;">Hematology</h5>
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                <span class="badge bg-light text-dark px-3 py-2 rounded-pill border border-danger border-opacity-10">Complete Blood Count (CBC)</span>
                                <span class="badge bg-light text-dark px-3 py-2 rounded-pill border border-danger border-opacity-10">Platelet Count</span>
                                <span class="badge bg-light text-dark px-3 py-2 rounded-pill border border-danger border-opacity-10">ESR</span>
                                <span class="badge bg-light text-dark px-3 py-2 rounded-pill border border-danger border-opacity-10">Hemoglobin (Hgb)</span>
                                <span class="badge bg-light text-dark px-3 py-2 rounded-pill border border-danger border-opacity-10">Hematocrit (Hct)</span>
                                <span class="badge bg-light text-dark px-3 py-2 rounded-pill border border-danger border-opacity-10">Blood Typing</span>
                            </div>
                        </div>

                        <!-- Serology & Thyroid -->
                        <div>
                            <div class="d-flex align-items-center mb-2">
                                <div class="bg-purple bg-opacity-10 p-2 rounded-2 me-2" style="background: rgba(128, 0, 128, 0.1);">
                                    <i class="bi bi-shield-plus" style="color: #800080;"></i>
                                </div>
                                <h5 class="mb-0 fw-bold" style="color: #0a2b4e;">Serology &amp; Thyroid</h5>
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                <span class="badge bg-light text-dark px-3 py-2 rounded-pill" style="border: 1px solid #80008033;">T3 / T4 / FT3 / FT4</span>
                                <span class="badge bg-light text-dark px-3 py-2 rounded-pill" style="border: 1px solid #80008033;">TSH</span>
                                <span class="badge bg-light text-dark px-3 py-2 rounded-pill" style="border: 1px solid #80008033;">HBsAg (Hepatitis B)</span>
                                <span class="badge bg-light text-dark px-3 py-2 rounded-pill" style="border: 1px solid #80008033;">HCV (Pregnancy)</span>
                                <span class="badge bg-light text-dark px-3 py-2 rounded-pill" style="border: 1px solid #80008033;">HIV (IgG/IgM)</span>
                                <span class="badge bg-light text-dark px-3 py-2 rounded-pill" style="border: 1px solid #80008033;">Salmonella typhi IgG/IgM</span>
                                <span class="badge bg-light text-dark px-3 py-2 rounded-pill" style="border: 1px solid #80008033;">Syphilis (T. pallidum)</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- X-Ray Services - Enhanced -->
            <div class="col-lg-6" data-aos="fade-up" data-aos-delay="200">
                <div class="card h-100 border-0 shadow-lg rounded-4 overflow-hidden" style="transition: transform 0.3s ease, box-shadow 0.3s ease;">
                    <div class="card-header p-4" style="background: linear-gradient(135deg, #0a2b4e 0%, #1b4a7a 100%); border: none;">
                        <div class="d-flex align-items-center">
                            <div class="bg-white bg-opacity-20 p-3 rounded-3 me-3">
                                <i class="bi bi-x-ray fs-2 text-white"></i>
                            </div>
                            <div>
                                <h3 class="card-title h3 mb-0 fw-bold text-white">X-Ray &amp; Imaging</h3>
                                <p class="text-white-50 small mb-0"><i class="bi bi-grid-3x3-gap-fill me-1"></i> Full skeletal &amp; chest series</p>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-4" style="background: #ffffff;">
                        <!-- Chest -->
                        <div class="mb-3">
                            <div class="d-flex align-items-center mb-2">
                                <div class="bg-primary bg-opacity-10 p-2 rounded-2 me-2">
                                    <i class="bi bi-lungs text-primary"></i>
                                </div>
                                <h5 class="mb-0 fw-bold" style="color: #0a2b4e;">Chest</h5>
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                <span class="badge bg-light text-dark px-3 py-2 rounded-pill border border-primary border-opacity-10">Chest A/P</span>
                                <span class="badge bg-light text-dark px-3 py-2 rounded-pill border border-primary border-opacity-10">Chest APL</span>
                                <span class="badge bg-light text-dark px-3 py-2 rounded-pill border border-primary border-opacity-10">Thoracic Bony Cage</span>
                            </div>
                        </div>

                        <!-- Upper Extremities -->
                        <div class="mb-3">
                            <div class="d-flex align-items-center mb-2">
                                <div class="bg-success bg-opacity-10 p-2 rounded-2 me-2">
                                    <i class="bi bi-hand-index-thumb text-success"></i>
                                </div>
                                <h5 class="mb-0 fw-bold" style="color: #0a2b4e;">Upper Extremities</h5>
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                <span class="badge bg-light text-dark px-3 py-2 rounded-pill border border-success border-opacity-10">Shoulder AP / APL</span>
                                <span class="badge bg-light text-dark px-3 py-2 rounded-pill border border-success border-opacity-10">Humerus AP / APL</span>
                                <span class="badge bg-light text-dark px-3 py-2 rounded-pill border border-success border-opacity-10">Arm AP Only</span>
                                <span class="badge bg-light text-dark px-3 py-2 rounded-pill border border-success border-opacity-10">Elbow AP / APL</span>
                                <span class="badge bg-light text-dark px-3 py-2 rounded-pill border border-success border-opacity-10">Forearm AP / APL</span>
                                <span class="badge bg-light text-dark px-3 py-2 rounded-pill border border-success border-opacity-10">Radius/Ulna AP</span>
                                <span class="badge bg-light text-dark px-3 py-2 rounded-pill border border-success border-opacity-10">Wrist AP / APL</span>
                                <span class="badge bg-light text-dark px-3 py-2 rounded-pill border border-success border-opacity-10">Hand AP / Oblique</span>
                                <span class="badge bg-light text-dark px-3 py-2 rounded-pill border border-success border-opacity-10">Finger</span>
                            </div>
                        </div>

                        <!-- Skull & Face -->
                        <div class="mb-3">
                            <div class="d-flex align-items-center mb-2">
                                <div class="bg-warning bg-opacity-10 p-2 rounded-2 me-2">
                                    <i class="bi bi-person-bounding-box text-warning"></i>
                                </div>
                                <h5 class="mb-0 fw-bold" style="color: #0a2b4e;">Skull &amp; Face</h5>
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                <span class="badge bg-light text-dark px-3 py-2 rounded-pill border border-warning border-opacity-10">Skull AP / APL</span>
                                <span class="badge bg-light text-dark px-3 py-2 rounded-pill border border-warning border-opacity-10">Skull Towner</span>
                                <span class="badge bg-light text-dark px-3 py-2 rounded-pill border border-warning border-opacity-10">Orbit AP / APL</span>
                                <span class="badge bg-light text-dark px-3 py-2 rounded-pill border border-warning border-opacity-10">PNS / Water / Caldwell</span>
                                <span class="badge bg-light text-dark px-3 py-2 rounded-pill border border-warning border-opacity-10">Nasal AP / APL</span>
                                <span class="badge bg-light text-dark px-3 py-2 rounded-pill border border-warning border-opacity-10">Neck / Cervical APL</span>
                            </div>
                        </div>

                        <!-- Spine & Pelvis -->
                        <div class="mb-3">
                            <div class="d-flex align-items-center mb-2">
                                <div class="bg-danger bg-opacity-10 p-2 rounded-2 me-2">
                                    <i class="bi bi-body-text text-danger"></i>
                                </div>
                                <h5 class="mb-0 fw-bold" style="color: #0a2b4e;">Spine &amp; Pelvis</h5>
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                <span class="badge bg-light text-dark px-3 py-2 rounded-pill border border-danger border-opacity-10">Cervical APL</span>
                                <span class="badge bg-light text-dark px-3 py-2 rounded-pill border border-danger border-opacity-10">Thoracic Vert AP / APL</span>
                                <span class="badge bg-light text-dark px-3 py-2 rounded-pill border border-danger border-opacity-10">Lumbosacral AP / APL</span>
                                <span class="badge bg-light text-dark px-3 py-2 rounded-pill border border-danger border-opacity-10">Thoracolumbar AP / APL</span>
                                <span class="badge bg-light text-dark px-3 py-2 rounded-pill border border-danger border-opacity-10">Pelvis AP / APL</span>
                                <span class="badge bg-light text-dark px-3 py-2 rounded-pill border border-danger border-opacity-10">Frog Leg View</span>
                                <span class="badge bg-light text-dark px-3 py-2 rounded-pill border border-danger border-opacity-10">Whole Spine APL</span>
                            </div>
                        </div>

                        <!-- Lower Extremities & Abdomen -->
                        <div>
                            <div class="d-flex align-items-center mb-2">
                                <div class="bg-info bg-opacity-10 p-2 rounded-2 me-2">
                                    <i class="bi bi-person-walking text-info"></i>
                                </div>
                                <h5 class="mb-0 fw-bold" style="color: #0a2b4e;">Lower Extremities &amp; Abdomen</h5>
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                <span class="badge bg-light text-dark px-3 py-2 rounded-pill border border-info border-opacity-10">Leg APL / Knee APL</span>
                                <span class="badge bg-light text-dark px-3 py-2 rounded-pill border border-info border-opacity-10">Foot APL</span>
                                <span class="badge bg-light text-dark px-3 py-2 rounded-pill border border-info border-opacity-10">Ankle APL</span>
                                <span class="badge bg-light text-dark px-3 py-2 rounded-pill border border-info border-opacity-10">One Foot AP</span>
                                <span class="badge bg-light text-dark px-3 py-2 rounded-pill border border-info border-opacity-10">Abdomen Plain / Upright</span>
                                <span class="badge bg-light text-dark px-3 py-2 rounded-pill border border-info border-opacity-10">Abdomen APL/Upright</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


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