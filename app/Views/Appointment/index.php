<?= $this->extend('layouts/AppointmentLayout') ?>

<?= $this->section('AppointmentContent') ?>

<nav class="navbar navbar-expand-lg bg-light border-bottom">
    <div class="container">
        <div class="navbar-logo-wrapper">
            <div class="navbar-logo-placeholder">
               <img src="/polymedic/public/assets/images/logo4.png" alt="Logo" class="navbar-logo">
            </div>
            <div class="navbar-brand-wrapper">
                <a class="navbar-brand" href="<?= base_url() ?>">PolyMedic</a>
                <p class="navbar-text">Diagnostic & Laboratory Center</p>
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
                          <a class="btn btn-success btn-lg px-4" href="/polymedic/public/index.php/appointment/book" style="background: #04ccab; border-color: #04ccab;">Book Now</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>
</section>

<!-- Services Section -->
<section id="Services" class="Services py-5" data-aos="fade-up" style="background: #f8faff;">
    <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
            <div class="d-flex align-items-center justify-content-center mb-3">
                <div style="height: 2px; width: 50px; background: #0148ca;"></div>
                <span class="mx-3 text-primary fw-bold text-uppercase small" style="letter-spacing: 2px;">Services</span>
                <div style="height: 2px; width: 50px; background: #0148ca;"></div>
            </div>
            <h2 class="display-4 fw-bold" style="color: #0a2b4e;">Our Diagnostic <span style="color: #0148ca;">Services</span></h2>
            <p class="text-muted" style="max-width: 600px; margin: 0 auto;">Comprehensive laboratory &amp; imaging services — no hidden costs, just quality care.</p>
        </div>

        <div class="row g-4">
            <!-- Laboratory Services -->
            <div class="col-lg-6" data-aos="fade-up" data-aos-delay="100">
                <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="card-header p-4" style="background: #0a2b4e; border: none;">
                        <div class="d-flex align-items-center">
                            <div class="bg-white bg-opacity-10 p-2 rounded-3 me-3">
                                <i class="bi bi-micoscope fs-3 text-white"></i>
                            </div>
                            <div>
                                <h3 class="card-title h4 mb-0 fw-bold text-white">CLINICAL LABORATORY</h3>
                                <p class="text-white-50 small mb-0">CHEMISTRY · HEMATOLOGY · SEROLOGY</p>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="border-bottom pb-2 mb-2">
                                    <span class="fw-bold text-primary"><i class="bi bi-flask me-2"></i>CLINICAL CHEMISTRY</span>
                                </div>
                                <div class="row g-1">
                                    <div class="col-6"><span class="text-dark">• GLUCOSE (RBS/FBS)</span></div>
                                    <div class="col-6"><span class="text-dark">• HbA1c</span></div>
                                    <div class="col-6"><span class="text-dark">• CHOLESTEROL</span></div>
                                    <div class="col-6"><span class="text-dark">• TRIGLYCERIDES</span></div>
                                    <div class="col-6"><span class="text-dark">• HDL / LDL</span></div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="border-bottom pb-2 mb-2">
                                    <span class="fw-bold text-success"><i class="bi bi-cpu me-2"></i>KIDNEY FUNCTION TESTS</span>
                                </div>
                                <div class="row g-1">
                                    <div class="col-6"><span class="text-dark">• CREATININE</span></div>
                                    <div class="col-6"><span class="text-dark">• BLOOD URIC ACID</span></div>
                                    <div class="col-6"><span class="text-dark">• BLOOD UREA NITROGEN</span></div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="border-bottom pb-2 mb-2">
                                    <span class="fw-bold" style="color: #ff6b00;"><i class="bi bi-heart-pulse me-2"></i>LIVER FUNCTION TESTS</span>
                                </div>
                                <div class="row g-1">
                                    <div class="col-6"><span class="text-dark">• SGPT/ALT</span></div>
                                    <div class="col-6"><span class="text-dark">• SGOT/AST</span></div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="border-bottom pb-2 mb-2">
                                    <span class="fw-bold text-info"><i class="bi bi-droplet-half me-2"></i>ELECTROLYTES</span>
                                </div>
                                <div class="row g-1">
                                    <div class="col-6"><span class="text-dark">• SODIUM (Na)</span></div>
                                    <div class="col-6"><span class="text-dark">• POTASSIUM (K)</span></div>
                                    <div class="col-6"><span class="text-dark">• CHLORIDE (Cl)</span></div>
                                    <div class="col-6"><span class="text-dark">• CALCIUM (Ca)</span></div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="border-bottom pb-2 mb-2">
                                    <span class="fw-bold text-warning"><i class="bi bi-eye me-2"></i>CLINICAL MICROSCOPY</span>
                                </div>
                                <div class="row g-1">
                                    <div class="col-6"><span class="text-dark">• URINALYSIS</span></div>
                                    <div class="col-6"><span class="text-dark">• FECALYSIS</span></div>
                                    <div class="col-6"><span class="text-dark">• SEMENANALYSIS</span></div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="border-bottom pb-2 mb-2">
                                    <span class="fw-bold text-danger"><i class="bi bi-droplet me-2"></i>HEMATOLOGY</span>
                                </div>
                                <div class="row g-1">
                                    <div class="col-6"><span class="text-dark">• COMPLETE BLOOD COUNT (CBC)</span></div>
                                    <div class="col-6"><span class="text-dark">• PLATELET COUNT</span></div>
                                    <div class="col-6"><span class="text-dark">• ESR</span></div>
                                    <div class="col-6"><span class="text-dark">• HEMOGLOBIN (Hgb)</span></div>
                                    <div class="col-6"><span class="text-dark">• HEMATOCRIT (Hct)</span></div>
                                    <div class="col-6"><span class="text-dark">• BLOOD TYPING</span></div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="border-bottom pb-2 mb-2">
                                    <span class="fw-bold" style="color: #800080;"><i class="bi bi-shield-plus me-2"></i>SEROLOGY</span>
                                </div>
                                <div class="row g-1">
                                    <div class="col-12"><span class="fw-bold text-dark" style="font-size: 0.85rem;">THYROID PANEL TESTS</span></div>
                                    <div class="col-6"><span class="text-dark">• T3</span></div>
                                    <div class="col-6"><span class="text-dark">• T4</span></div>
                                    <div class="col-6"><span class="text-dark">• FT3</span></div>
                                    <div class="col-6"><span class="text-dark">• FT4</span></div>
                                    <div class="col-6"><span class="text-dark">• TSH</span></div>
                                    <div class="col-12 mt-2"><span class="fw-bold text-dark" style="font-size: 0.85rem;">RAPID CARD TESTS</span></div>
                                    <div class="col-6"><span class="text-dark">• HBsAg (HEPATITIS B)</span></div>
                                    <div class="col-6"><span class="text-dark">• HCV (PREGNANCY TEST)</span></div>
                                    <div class="col-6"><span class="text-dark">• HIV (IgG/IgM)</span></div>
                                    <div class="col-6"><span class="text-dark">• SALMONELLA TYPHI IgG/IgM</span></div>
                                    <div class="col-6"><span class="text-dark">• SYPHILIS (T. PALLIDUM)</span></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- X-Ray Services -->
            <div class="col-lg-6" data-aos="fade-up" data-aos-delay="200">
                <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="card-header p-4" style="background: #1b4a7a; border: none;">
                        <div class="d-flex align-items-center">
                            <div class="bg-white bg-opacity-10 p-2 rounded-3 me-3">
                                <i class="bi bi-x-ray fs-3 text-white"></i>
                            </div>
                            <div>
                                <h3 class="card-title h4 mb-0 fw-bold text-white">X-RAY &amp; IMAGING</h3>
                                <p class="text-white-50 small mb-0">FULL SKELETAL &amp; CHEST SERIES</p>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="border-bottom pb-2 mb-2">
                                    <span class="fw-bold text-primary"><i class="bi bi-lungs me-2"></i>CHEST</span>
                                </div>
                                <div class="row g-1">
                                    <div class="col-6"><span class="text-dark">• CHEST A/P</span></div>
                                    <div class="col-6"><span class="text-dark">• CHEST APL</span></div>
                                    <div class="col-6"><span class="text-dark">• THORACIC BONY CAGE/TBC</span></div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="border-bottom pb-2 mb-2">
                                    <span class="fw-bold text-success"><i class="bi bi-hand-index-thumb me-2"></i>UPPER EXTREMITIES</span>
                                </div>
                                <div class="row g-1">
                                    <div class="col-6"><span class="text-dark">• SHOULDER AP / APL</span></div>
                                    <div class="col-6"><span class="text-dark">• HUMERUS AP / APL</span></div>
                                    <div class="col-6"><span class="text-dark">• ARM AP ONLY</span></div>
                                    <div class="col-6"><span class="text-dark">• ELBOW AP / APL</span></div>
                                    <div class="col-6"><span class="text-dark">• FOREARM AP / APL</span></div>
                                    <div class="col-6"><span class="text-dark">• RADIUS/ULNA AP</span></div>
                                    <div class="col-6"><span class="text-dark">• WRIST AP / APL</span></div>
                                    <div class="col-6"><span class="text-dark">• HAND AP/METACARPAL ONLY</span></div>
                                    <div class="col-6"><span class="text-dark">• HAND AP/OBLIQUE/HAND APD</span></div>
                                    <div class="col-6"><span class="text-dark">• FINGER</span></div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="border-bottom pb-2 mb-2">
                                    <span class="fw-bold text-warning"><i class="bi bi-person-bounding-box me-2"></i>SKULL &amp; FACE</span>
                                </div>
                                <div class="row g-1">
                                    <div class="col-6"><span class="text-dark">• SKULL AP / APL</span></div>
                                    <div class="col-6"><span class="text-dark">• SKULL APL/TOWNER</span></div>
                                    <div class="col-6"><span class="text-dark">• ORBIT AP / APL</span></div>
                                    <div class="col-6"><span class="text-dark">• NASAP AP / APL</span></div>
                                    <div class="col-6"><span class="text-dark">• PARANASAL WATER ONLY</span></div>
                                    <div class="col-6"><span class="text-dark">• PARANASAL (WATER LATERAL CARDWELL)/PNS</span></div>
                                    <div class="col-6"><span class="text-dark">• NECK/CERVICAL APL</span></div>
                                    <div class="col-6"><span class="text-dark">• NECK/CERVICAL APL/OBLIQUE</span></div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="border-bottom pb-2 mb-2">
                                    <span class="fw-bold text-danger"><i class="bi bi-body-text me-2"></i>SPINE &amp; PELVIS</span>
                                </div>
                                <div class="row g-1">
                                    <div class="col-6"><span class="text-dark">• CERVICAL APL</span></div>
                                    <div class="col-6"><span class="text-dark">• THORACIC VERT AP / APL</span></div>
                                    <div class="col-6"><span class="text-dark">• THORACOLUMBAR AP / APL</span></div>
                                    <div class="col-6"><span class="text-dark">• THORACOLUMBAR SPINE APL/THIGH</span></div>
                                    <div class="col-6"><span class="text-dark">• LUMBOSACRAL AP / APL</span></div>
                                    <div class="col-6"><span class="text-dark">• LUMBOSACRAL APL/LUMBAR VERTEBRAE APL</span></div>
                                    <div class="col-6"><span class="text-dark">• PELVIS AP / APL</span></div>
                                    <div class="col-6"><span class="text-dark">• FROG LEG/VIEW</span></div>
                                    <div class="col-6"><span class="text-dark">• WHOLE SPINE APL</span></div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="border-bottom pb-2 mb-2">
                                    <span class="fw-bold text-info"><i class="bi bi-person-walking me-2"></i>LOWER EXTREMITIES &amp; ABDOMEN</span>
                                </div>
                                <div class="row g-1">
                                    <div class="col-6"><span class="text-dark">• LEG APL/KNEE</span></div>
                                    <div class="col-6"><span class="text-dark">• KNEE APL</span></div>
                                    <div class="col-6"><span class="text-dark">• ONE FOOT AP</span></div>
                                    <div class="col-6"><span class="text-dark">• FOOT APL</span></div>
                                    <div class="col-6"><span class="text-dark">• ANKLE APL</span></div>
                                    <div class="col-6"><span class="text-dark">• ABDOMEN PLAIN</span></div>
                                    <div class="col-6"><span class="text-dark">• ABDOMEN APL/UPRIGHT</span></div>
                                    <div class="col-6"><span class="text-dark">• ABDOMEN UPRIGHT ONLY</span></div>
                                </div>
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
                <div class="p-5">
                      <img class="img-fluid rounded-circle" src="/polymedic/public/assets/images/Appointment2.png" alt="Appointment" />
                 </div>
            </div>
            <div class="col-lg-6 order-lg-1" data-aos="fade-up" data-aos-delay="100">
                <div class="p-5">
                    <h2 class="display-4">How to book an appointment</h2>
                    <p>Booking an appointment with us is easy and convenient. Follow these simple steps to schedule your visit:</p>
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


<footer class="py-2 bg-dark text-white">
    <div class="container px-5">
        <p class="text-center mb-0">&copy; 2026 PolyMedic. All rights reserved.</p>
    </div>
</footer>

<?= $this->endSection() ?>