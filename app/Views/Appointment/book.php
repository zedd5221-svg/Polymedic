<?= $this->extend('layouts/AppointmentLayout') ?>

<?= $this->section('AppointmentContent') ?>

<!-- Load booking CSS -->
<link href="http://localhost/polymedic/public/assets/css/booking.css" rel="stylesheet">


<!-- ====== BOOKING PAGE ====== -->
<section class="booking-page">
    <div class="container">
        
        <!-- ===== STEP PROGRESS ===== -->
        <div class="step-progress-wrapper">
            <div class="step-progress">
                <div class="progress-line" id="progressLine"></div>
                
                <!-- Step 1 -->
                <div class="step-item" data-step="1">
                    <div class="step-circle active" id="stepCircle1">
                        <span class="step-number">1</span>
                        <span class="step-icon"><i class="bi bi-calendar3"></i></span>
                    </div>
                    <span class="step-label active" id="stepLabel1">Date &amp; Time</span>
                </div>
                
                <!-- Step 2 -->
                <div class="step-item" data-step="2">
                    <div class="step-circle" id="stepCircle2">
                        <span class="step-number">2</span>
                        <span class="step-icon"><i class="bi bi-person"></i></span>
                    </div>
                    <span class="step-label" id="stepLabel2">Your Details</span>
                </div>
                
                <!-- Step 3 -->
                <div class="step-item" data-step="3">
                    <div class="step-circle" id="stepCircle3">
                        <span class="step-number">3</span>
                        <span class="step-icon"><i class="bi bi-clipboard2-pulse"></i></span>
                    </div>
                    <span class="step-label" id="stepLabel3">Services</span>
                </div>
                
                <!-- Step 4 -->
                <div class="step-item" data-step="4">
                    <div class="step-circle" id="stepCircle4">
                        <span class="step-number">4</span>
                        <span class="step-icon"><i class="bi bi-credit-card"></i></span>
                    </div>
                    <span class="step-label" id="stepLabel4">Payment</span>
                </div>
                
                <!-- Step 5 -->
                <div class="step-item" data-step="5">
                    <div class="step-circle" id="stepCircle5">
                        <span class="step-number">5</span>
                        <span class="step-icon"><i class="bi bi-check2-circle"></i></span>
                    </div>
                    <span class="step-label" id="stepLabel5">Confirm</span>
                </div>
            </div>
        </div>
        
        <!-- ===== MAIN CARD ===== -->
        <div class="booking-card">
            <div class="card-header-custom">
                <h2>
                    <i class="bi bi-heart-pulse-fill"></i>
                    Book Your Appointment
                </h2>
                <p>Complete the steps below to schedule your visit with us</p>
            </div>
            
            <div class="card-body-custom">
                
                <!-- Display validation errors -->
                <?php if (session()->getFlashdata('errors')): ?>
                    <div class="alert alert-danger alert-dismissible fade show rounded-3" role="alert">
                        <strong><i class="bi bi-exclamation-triangle-fill me-2"></i>Please fix the following:</strong>
                        <ul class="mb-0 mt-2">
                            <?php foreach (session()->getFlashdata('errors') as $error): ?>
                                <li><?= esc($error) ?></li>
                            <?php endforeach ?>
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif ?>
                
                <form action="http://localhost/polymedic/public/index.php/appointment/submit" method="POST" id="bookingForm">
                    <?= csrf_field() ?>
                    
                    <!-- ===== STEP 1: Date & Time ===== -->
                    <div class="form-step active" data-step="1">
                        <div class="step-title">
                            <span class="badge bg-primary rounded-pill">1</span>
                            Select Date &amp; Time
                        </div>
                        <p class="step-subtitle">Choose your preferred appointment schedule.</p>
                        
                        <div class="row g-4">
                            <div class="col-md-7">
                                <label class="form-label-custom">
                                    <i class="bi bi-calendar-event me-1"></i> Appointment Date
                                </label>
                                <input type="date" class="form-control form-control-custom" name="appointment_date" id="appointment_date" min="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label-custom">
                                    <i class="bi bi-clock me-1"></i> Preferred Time
                                </label>
                                <select class="form-select form-select-custom" name="appointment_time" id="appointment_time" required>
                                    <option value="">Select time</option>
                                    <option value="08:00">🕐 8:00 AM</option>
                                    <option value="09:00">🕐 9:00 AM</option>
                                    <option value="10:00">🕐 10:00 AM</option>
                                    <option value="11:00">🕐 11:00 AM</option>
                                    <option value="13:00">🕐 1:00 PM</option>
                                    <option value="14:00">🕐 2:00 PM</option>
                                    <option value="15:00">🕐 3:00 PM</option>
                                    <option value="16:00">🕐 4:00 PM</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Step 1: Home button only (no back needed) -->
                        <div class="btn-group-custom">
                            <a href="/polymedic/public/" class="btn-home">
                                <i class="bi bi-house me-2"></i>Home
                            </a>
                            <button type="button" class="btn btn-primary-custom" id="step1Next">
                                Next Step <i class="bi bi-arrow-right"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- ===== STEP 2: Personal Details ===== -->
                    <div class="form-step" data-step="2">
                        <div class="step-title">
                            <span class="badge bg-primary rounded-pill">2</span>
                            Your Information
                        </div>
                        <p class="step-subtitle">Tell us about yourself so we can prepare for your visit.</p>
                        
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="form-label-custom">
                                    <i class="bi bi-person me-1"></i> Full Name
                                </label>
                                <input type="text" class="form-control form-control-custom" name="full_name" id="full_name" placeholder="Dr. Juan Dela Cruz" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label-custom">
                                    <i class="bi bi-cake2 me-1"></i> Age
                                </label>
                                <input type="number" class="form-control form-control-custom" name="age" id="age" placeholder="25" min="0" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label-custom">
                                    <i class="bi bi-gender-ambiguous me-1"></i> Gender
                                </label>
                                <select class="form-select form-select-custom" name="gender" id="gender" required>
                                    <option value="">Select</option>
                                    <option value="Male">👨 Male</option>
                                    <option value="Female">👩 Female</option>
                                    <option value="Other">👤 Other</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-custom">
                                    <i class="bi bi-envelope me-1"></i> Email Address
                                </label>
                                <input type="email" class="form-control form-control-custom" name="email" id="email" placeholder="your@email.com" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-custom">
                                    <i class="bi bi-phone me-1"></i> Phone Number
                                </label>
                                <input type="tel" class="form-control form-control-custom" name="phone" id="phone" placeholder="0912 345 6789" required>
                            </div>
                        </div>
                        
                        <div class="btn-group-custom">
                            <a href="/polymedic/public/" class="btn-home">
                                <i class="bi bi-house me-2"></i>Home
                            </a>
                            <button type="button" class="btn btn-outline-custom" id="step2Prev">
                                <i class="bi bi-arrow-left"></i> Back
                            </button>
                            <button type="button" class="btn btn-primary-custom" id="step2Next">
                                Next Step <i class="bi bi-arrow-right"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- ===== STEP 3: Services ===== -->
                    <div class="form-step" data-step="3">
                        <div class="step-title">
                            <span class="badge bg-primary rounded-pill">3</span>
                            Select Services
                        </div>
                        <p class="step-subtitle">Choose the type of service and specific tests you need.</p>
                        
                        <!-- Service Type Selection -->
                        <label class="form-label-custom">
                            <i class="bi bi-tags me-1"></i> Service Type
                        </label>
                        <div class="service-type-grid">
                            <!-- Laboratory -->
                            <div class="service-type-card active" data-type="laboratory" id="serviceLab">
                                <span class="check-mark"><i class="bi bi-check"></i></span>
                                <span class="icon">
                                    <img src="/polymedic/public/assets/images/lab-icon.png" alt="Laboratory" style="width: 55px; height: 55px; object-fit: contain;">
                                </span>
                                <div class="title">Laboratory</div>
                                <div class="desc">Blood tests, urinalysis, etc.</div>
                            </div>
                            
                            <!-- X-Ray -->
                            <div class="service-type-card" data-type="xray" id="serviceXray">
                                <span class="check-mark"><i class="bi bi-check"></i></span>
                                <span class="icon">
                                    <img src="/polymedic/public/assets/images/xray-icon.png" alt="X-Ray" style="width: 55px; height: 55px; object-fit: contain;">
                                </span>
                                <div class="title">X-Ray &amp; Imaging</div>
                                <div class="desc">Chest, skeletal, etc.</div>
                            </div>
                            
                            <!-- Both -->
                            <div class="service-type-card" data-type="both" id="serviceBoth">
                                <span class="check-mark"><i class="bi bi-check"></i></span>
                                <span class="icon">
                                    <img src="/polymedic/public/assets/images/both-icon.png" alt="Both" style="width: 55px; height: 55px; object-fit: contain;">
                                </span>
                                <div class="title">Both</div>
                                <div class="desc">Laboratory &amp; X-Ray</div>
                            </div>
                        </div>
                        
                        <!-- Laboratory Services -->
<div id="labServicesContainer">
    <div class="service-category-title">
        <i class="bi bi-droplet" style="color: #0148ca;"></i> Laboratory Tests
    </div>
    <div class="service-grid">
        <!-- CLINICAL CHEMISTRY -->
        <div style="grid-column: 1 / -1; font-weight: 700; color: #0148ca; font-size: 0.85rem; margin-top: 0.25rem; border-bottom: 1px solid #eef2f7; padding-bottom: 0.25rem;">
            <i class="bi bi-flask me-2"></i>CLINICAL CHEMISTRY
        </div>
        <div class="service-check">
            <input type="checkbox" name="lab_services[]" value="Glucose (RBS/FBS)" id="labGlucose">
            <label for="labGlucose">GLUCOSE (RBS/FBS)</label>
        </div>
        <div class="service-check">
            <input type="checkbox" name="lab_services[]" value="HbA1c" id="labHba1c">
            <label for="labHba1c">HbA1c</label>
        </div>
        <div class="service-check">
            <input type="checkbox" name="lab_services[]" value="Cholesterol" id="labCholesterol">
            <label for="labCholesterol">CHOLESTEROL</label>
        </div>
        <div class="service-check">
            <input type="checkbox" name="lab_services[]" value="Triglycerides" id="labTriglycerides">
            <label for="labTriglycerides">TRIGLYCERIDES</label>
        </div>
        <div class="service-check">
            <input type="checkbox" name="lab_services[]" value="HDL/LDL" id="labHdlLdl">
            <label for="labHdlLdl">HDL / LDL</label>
        </div>

        <!-- KIDNEY FUNCTION TESTS -->
        <div style="grid-column: 1 / -1; font-weight: 700; color: #28a745; font-size: 0.85rem; margin-top: 0.5rem; border-bottom: 1px solid #eef2f7; padding-bottom: 0.25rem;">
            <i class="bi bi-cpu me-2"></i>KIDNEY FUNCTION TESTS
        </div>
        <div class="service-check">
            <input type="checkbox" name="lab_services[]" value="Creatinine" id="labCreatinine">
            <label for="labCreatinine">CREATININE</label>
        </div>
        <div class="service-check">
            <input type="checkbox" name="lab_services[]" value="Blood Uric Acid" id="labUricAcid">
            <label for="labUricAcid">BLOOD URIC ACID</label>
        </div>
        <div class="service-check">
            <input type="checkbox" name="lab_services[]" value="Blood Urea Nitrogen" id="labBun">
            <label for="labBun">BLOOD UREA NITROGEN</label>
        </div>

        <!-- LIVER FUNCTION TESTS -->
        <div style="grid-column: 1 / -1; font-weight: 700; color: #ff6b00; font-size: 0.85rem; margin-top: 0.5rem; border-bottom: 1px solid #eef2f7; padding-bottom: 0.25rem;">
            <i class="bi bi-heart-pulse me-2"></i>LIVER FUNCTION TESTS
        </div>
        <div class="service-check">
            <input type="checkbox" name="lab_services[]" value="SGPT/ALT" id="labSgpt">
            <label for="labSgpt">SGPT/ALT</label>
        </div>
        <div class="service-check">
            <input type="checkbox" name="lab_services[]" value="SGOT/AST" id="labSgot">
            <label for="labSgot">SGOT/AST</label>
        </div>

        <!-- ELECTROLYTES -->
        <div style="grid-column: 1 / -1; font-weight: 700; color: #17a2b8; font-size: 0.85rem; margin-top: 0.5rem; border-bottom: 1px solid #eef2f7; padding-bottom: 0.25rem;">
            <i class="bi bi-droplet-half me-2"></i>ELECTROLYTES
        </div>
        <div class="service-check">
            <input type="checkbox" name="lab_services[]" value="Sodium (Na)" id="labSodium">
            <label for="labSodium">SODIUM (Na)</label>
        </div>
        <div class="service-check">
            <input type="checkbox" name="lab_services[]" value="Potassium (K)" id="labPotassium">
            <label for="labPotassium">POTASSIUM (K)</label>
        </div>
        <div class="service-check">
            <input type="checkbox" name="lab_services[]" value="Chloride (Cl)" id="labChloride">
            <label for="labChloride">CHLORIDE (Cl)</label>
        </div>
        <div class="service-check">
            <input type="checkbox" name="lab_services[]" value="Calcium (Ca)" id="labCalcium">
            <label for="labCalcium">CALCIUM (Ca)</label>
        </div>

        <!-- CLINICAL MICROSCOPY -->
        <div style="grid-column: 1 / -1; font-weight: 700; color: #ffc107; font-size: 0.85rem; margin-top: 0.5rem; border-bottom: 1px solid #eef2f7; padding-bottom: 0.25rem;">
            <i class="bi bi-eye me-2"></i>CLINICAL MICROSCOPY
        </div>
        <div class="service-check">
            <input type="checkbox" name="lab_services[]" value="Urinalysis" id="labUrinalysis">
            <label for="labUrinalysis">URINALYSIS</label>
        </div>
        <div class="service-check">
            <input type="checkbox" name="lab_services[]" value="Fecalysis" id="labFecalysis">
            <label for="labFecalysis">FECALYSIS</label>
        </div>
        <div class="service-check">
            <input type="checkbox" name="lab_services[]" value="Semenanalysis" id="labSemenanalysis">
            <label for="labSemenanalysis">SEMENANALYSIS</label>
        </div>

        <!-- HEMATOLOGY -->
        <div style="grid-column: 1 / -1; font-weight: 700; color: #dc3545; font-size: 0.85rem; margin-top: 0.5rem; border-bottom: 1px solid #eef2f7; padding-bottom: 0.25rem;">
            <i class="bi bi-droplet me-2"></i>HEMATOLOGY
        </div>
        <div class="service-check">
            <input type="checkbox" name="lab_services[]" value="Complete Blood Count (CBC)" id="labCbc">
            <label for="labCbc">COMPLETE BLOOD COUNT (CBC)</label>
        </div>
        <div class="service-check">
            <input type="checkbox" name="lab_services[]" value="Platelet Count" id="labPlatelet">
            <label for="labPlatelet">PLATELET COUNT</label>
        </div>
        <div class="service-check">
            <input type="checkbox" name="lab_services[]" value="ESR" id="labEsr">
            <label for="labEsr">ESR</label>
        </div>
        <div class="service-check">
            <input type="checkbox" name="lab_services[]" value="Hemoglobin (Hgb)" id="labHgb">
            <label for="labHgb">HEMOGLOBIN (Hgb)</label>
        </div>
        <div class="service-check">
            <input type="checkbox" name="lab_services[]" value="Hematocrit (Hct)" id="labHct">
            <label for="labHct">HEMATOCRIT (Hct)</label>
        </div>
        <div class="service-check">
            <input type="checkbox" name="lab_services[]" value="Blood Typing" id="labBloodTyping">
            <label for="labBloodTyping">BLOOD TYPING</label>
        </div>

        <!-- SEROLOGY -->
        <div style="grid-column: 1 / -1; font-weight: 700; color: #800080; font-size: 0.85rem; margin-top: 0.5rem; border-bottom: 1px solid #eef2f7; padding-bottom: 0.25rem;">
            <i class="bi bi-shield-plus me-2"></i>SEROLOGY
        </div>
        <div style="grid-column: 1 / -1; font-weight: 600; color: #495057; font-size: 0.8rem; margin-top: 0.25rem;">
            <i class="bi bi-arrow-right me-1"></i>THYROID PANEL TESTS
        </div>
        <div class="service-check">
            <input type="checkbox" name="lab_services[]" value="T3" id="labT3">
            <label for="labT3">T3</label>
        </div>
        <div class="service-check">
            <input type="checkbox" name="lab_services[]" value="T4" id="labT4">
            <label for="labT4">T4</label>
        </div>
        <div class="service-check">
            <input type="checkbox" name="lab_services[]" value="FT3" id="labFt3">
            <label for="labFt3">FT3</label>
        </div>
        <div class="service-check">
            <input type="checkbox" name="lab_services[]" value="FT4" id="labFt4">
            <label for="labFt4">FT4</label>
        </div>
        <div class="service-check">
            <input type="checkbox" name="lab_services[]" value="TSH" id="labTsh">
            <label for="labTsh">TSH</label>
        </div>
        <div style="grid-column: 1 / -1; font-weight: 600; color: #495057; font-size: 0.8rem; margin-top: 0.25rem;">
            <i class="bi bi-arrow-right me-1"></i>RAPID CARD TESTS
        </div>
        <div class="service-check">
            <input type="checkbox" name="lab_services[]" value="HBsAg (Hepatitis B)" id="labHbsag">
            <label for="labHbsag">HBsAg (HEPATITIS B)</label>
        </div>
        <div class="service-check">
            <input type="checkbox" name="lab_services[]" value="HCV (Pregnancy Test)" id="labHcv">
            <label for="labHcv">HCV (PREGNANCY TEST)</label>
        </div>
        <div class="service-check">
            <input type="checkbox" name="lab_services[]" value="HIV (IgG/IgM)" id="labHiv">
            <label for="labHiv">HIV (IgG/IgM)</label>
        </div>
        <div class="service-check">
            <input type="checkbox" name="lab_services[]" value="Salmonella typhi IgG/IgM" id="labSalmonella">
            <label for="labSalmonella">SALMONELLA TYPHI IgG/IgM</label>
        </div>
        <div class="service-check">
            <input type="checkbox" name="lab_services[]" value="Syphilis (T. pallidum)" id="labSyphilis">
            <label for="labSyphilis">SYPHILIS (T. PALLIDUM)</label>
        </div>
    </div>
</div>
                        
                       <!-- X-Ray Services -->
<div id="xrayServicesContainer" style="display: none;">
    <div class="service-category-title">
        <i class="bi bi-x-ray" style="color: #0a2b4e;"></i> X-Ray Services
    </div>
    <div class="service-grid">
        <!-- CHEST -->
        <div style="grid-column: 1 / -1; font-weight: 700; color: #0148ca; font-size: 0.85rem; margin-top: 0.25rem; border-bottom: 1px solid #eef2f7; padding-bottom: 0.25rem;">
            <i class="bi bi-lungs me-2"></i>CHEST
        </div>
        <div class="service-check">
            <input type="checkbox" name="xray_services[]" value="Chest A/P" id="xrayChestAP">
            <label for="xrayChestAP">CHEST A/P</label>
        </div>
        <div class="service-check">
            <input type="checkbox" name="xray_services[]" value="Chest APL" id="xrayChestAPL">
            <label for="xrayChestAPL">CHEST APL</label>
        </div>
        <div class="service-check">
            <input type="checkbox" name="xray_services[]" value="Thoracic Bony Cage/TBC" id="xrayTbc">
            <label for="xrayTbc">THORACIC BONY CAGE/TBC</label>
        </div>

        <!-- UPPER EXTREMITIES -->
        <div style="grid-column: 1 / -1; font-weight: 700; color: #28a745; font-size: 0.85rem; margin-top: 0.5rem; border-bottom: 1px solid #eef2f7; padding-bottom: 0.25rem;">
            <i class="bi bi-hand-index-thumb me-2"></i>UPPER EXTREMITIES
        </div>
        <div class="service-check">
            <input type="checkbox" name="xray_services[]" value="Shoulder AP / APL" id="xrayShoulder">
            <label for="xrayShoulder">SHOULDER AP / APL</label>
        </div>
        <div class="service-check">
            <input type="checkbox" name="xray_services[]" value="Humerus AP / APL" id="xrayHumerus">
            <label for="xrayHumerus">HUMERUS AP / APL</label>
        </div>
        <div class="service-check">
            <input type="checkbox" name="xray_services[]" value="Arm AP Only" id="xrayArm">
            <label for="xrayArm">ARM AP ONLY</label>
        </div>
        <div class="service-check">
            <input type="checkbox" name="xray_services[]" value="Elbow AP / APL" id="xrayElbow">
            <label for="xrayElbow">ELBOW AP / APL</label>
        </div>
        <div class="service-check">
            <input type="checkbox" name="xray_services[]" value="Forearm AP / APL" id="xrayForearm">
            <label for="xrayForearm">FOREARM AP / APL</label>
        </div>
        <div class="service-check">
            <input type="checkbox" name="xray_services[]" value="Radius/Ulna AP" id="xrayRadius">
            <label for="xrayRadius">RADIUS/ULNA AP</label>
        </div>
        <div class="service-check">
            <input type="checkbox" name="xray_services[]" value="Wrist AP / APL" id="xrayWrist">
            <label for="xrayWrist">WRIST AP / APL</label>
        </div>
        <div class="service-check">
            <input type="checkbox" name="xray_services[]" value="Hand AP/Metacarpal Only" id="xrayHand">
            <label for="xrayHand">HAND AP/METACARPAL ONLY</label>
        </div>
        <div class="service-check">
            <input type="checkbox" name="xray_services[]" value="Hand APL/Oblique/Hand APD" id="xrayHandOblique">
            <label for="xrayHandOblique">HAND APL/OBLIQUE/HAND APD</label>
        </div>
        <div class="service-check">
            <input type="checkbox" name="xray_services[]" value="Finger" id="xrayFinger">
            <label for="xrayFinger">FINGER</label>
        </div>

        <!-- SKULL & FACE -->
        <div style="grid-column: 1 / -1; font-weight: 700; color: #ffc107; font-size: 0.85rem; margin-top: 0.5rem; border-bottom: 1px solid #eef2f7; padding-bottom: 0.25rem;">
            <i class="bi bi-person-bounding-box me-2"></i>SKULL &amp; FACE
        </div>
        <div class="service-check">
            <input type="checkbox" name="xray_services[]" value="Skull AP / APL" id="xraySkull">
            <label for="xraySkull">SKULL AP / APL</label>
        </div>
        <div class="service-check">
            <input type="checkbox" name="xray_services[]" value="Skull APL/Towner" id="xrayTowner">
            <label for="xrayTowner">SKULL APL/TOWNER</label>
        </div>
        <div class="service-check">
            <input type="checkbox" name="xray_services[]" value="Orbit AP / APL" id="xrayOrbit">
            <label for="xrayOrbit">ORBIT AP / APL</label>
        </div>
        <div class="service-check">
            <input type="checkbox" name="xray_services[]" value="Nasap AP / APL" id="xrayNasap">
            <label for="xrayNasap">NASAP AP / APL</label>
        </div>
        <div class="service-check">
            <input type="checkbox" name="xray_services[]" value="Paranasal Water Only" id="xrayPnsWater">
            <label for="xrayPnsWater">PARANASAL WATER ONLY</label>
        </div>
        <div class="service-check">
            <input type="checkbox" name="xray_services[]" value="Paranasal (Water Lateral Cardwell)/PNS" id="xrayPnsFull">
            <label for="xrayPnsFull">PARANASAL (WATER LATERAL CARDWELL)/PNS</label>
        </div>
        <div class="service-check">
            <input type="checkbox" name="xray_services[]" value="Neck/Cervical APL" id="xrayNeck">
            <label for="xrayNeck">NECK/CERVICAL APL</label>
        </div>
        <div class="service-check">
            <input type="checkbox" name="xray_services[]" value="Neck/Cervical APL/Oblique" id="xrayNeckOblique">
            <label for="xrayNeckOblique">NECK/CERVICAL APL/OBLIQUE</label>
        </div>

        <!-- SPINE & PELVIS -->
        <div style="grid-column: 1 / -1; font-weight: 700; color: #dc3545; font-size: 0.85rem; margin-top: 0.5rem; border-bottom: 1px solid #eef2f7; padding-bottom: 0.25rem;">
            <i class="bi bi-body-text me-2"></i>SPINE &amp; PELVIS
        </div>
        <div class="service-check">
            <input type="checkbox" name="xray_services[]" value="Cervical APL" id="xrayCervical">
            <label for="xrayCervical">CERVICAL APL</label>
        </div>
        <div class="service-check">
            <input type="checkbox" name="xray_services[]" value="Thoracic Vert AP / APL" id="xrayThoracic">
            <label for="xrayThoracic">THORACIC VERT AP / APL</label>
        </div>
        <div class="service-check">
            <input type="checkbox" name="xray_services[]" value="Thoracolumbar AP / APL" id="xrayThoracolumbar">
            <label for="xrayThoracolumbar">THORACOLUMBAR AP / APL</label>
        </div>
        <div class="service-check">
            <input type="checkbox" name="xray_services[]" value="Thoracolumbar Spine APL/Thigh" id="xrayThoracolumbarThigh">
            <label for="xrayThoracolumbarThigh">THORACOLUMBAR SPINE APL/THIGH</label>
        </div>
        <div class="service-check">
            <input type="checkbox" name="xray_services[]" value="Lumbosacral AP / APL" id="xrayLumbosacral">
            <label for="xrayLumbosacral">LUMBOSACRAL AP / APL</label>
        </div>
        <div class="service-check">
            <input type="checkbox" name="xray_services[]" value="Lumbosacral APL/Lumbar Vertebrae APL" id="xrayLumbarVertebrae">
            <label for="xrayLumbarVertebrae">LUMBOSACRAL APL/LUMBAR VERTEBRAE APL</label>
        </div>
        <div class="service-check">
            <input type="checkbox" name="xray_services[]" value="Pelvis AP / APL" id="xrayPelvis">
            <label for="xrayPelvis">PELVIS AP / APL</label>
        </div>
        <div class="service-check">
            <input type="checkbox" name="xray_services[]" value="Frog Leg/View" id="xrayFrogLeg">
            <label for="xrayFrogLeg">FROG LEG/VIEW</label>
        </div>
        <div class="service-check">
            <input type="checkbox" name="xray_services[]" value="Whole Spine APL" id="xrayWholeSpine">
            <label for="xrayWholeSpine">WHOLE SPINE APL</label>
        </div>

        <!-- LOWER EXTREMITIES & ABDOMEN -->
        <div style="grid-column: 1 / -1; font-weight: 700; color: #17a2b8; font-size: 0.85rem; margin-top: 0.5rem; border-bottom: 1px solid #eef2f7; padding-bottom: 0.25rem;">
            <i class="bi bi-person-walking me-2"></i>LOWER EXTREMITIES &amp; ABDOMEN
        </div>
        <div class="service-check">
            <input type="checkbox" name="xray_services[]" value="Leg APL/Knee" id="xrayLeg">
            <label for="xrayLeg">LEG APL/KNEE</label>
        </div>
        <div class="service-check">
            <input type="checkbox" name="xray_services[]" value="Knee APL" id="xrayKnee">
            <label for="xrayKnee">KNEE APL</label>
        </div>
        <div class="service-check">
            <input type="checkbox" name="xray_services[]" value="One Foot AP" id="xrayOneFoot">
            <label for="xrayOneFoot">ONE FOOT AP</label>
        </div>
        <div class="service-check">
            <input type="checkbox" name="xray_services[]" value="Foot APL" id="xrayFoot">
            <label for="xrayFoot">FOOT APL</label>
        </div>
        <div class="service-check">
            <input type="checkbox" name="xray_services[]" value="Ankle APL" id="xrayAnkle">
            <label for="xrayAnkle">ANKLE APL</label>
        </div>
        <div class="service-check">
            <input type="checkbox" name="xray_services[]" value="Abdomen Plain" id="xrayAbdomen">
            <label for="xrayAbdomen">ABDOMEN PLAIN</label>
        </div>
        <div class="service-check">
            <input type="checkbox" name="xray_services[]" value="Abdomen APL/Upright" id="xrayAbdomenApl">
            <label for="xrayAbdomenApl">ABDOMEN APL/UPRIGHT</label>
        </div>
        <div class="service-check">
            <input type="checkbox" name="xray_services[]" value="Abdomen Upright Only" id="xrayAbdomenUpright">
            <label for="xrayAbdomenUpright">ABDOMEN UPRIGHT ONLY</label>
        </div>
    </div>
</div>
                        
                        <!-- Other Requests -->
                        <div class="mt-4">
                            <label class="form-label-custom">
                                <i class="bi bi-clipboard2-pulse me-1"></i> Other Requests
                            </label>
                            <textarea class="form-control form-control-custom" name="other_requests" id="other_requests" rows="3" placeholder="Any additional requests or special instructions..."></textarea>
                        </div>
                        
                        <div class="btn-group-custom">
                            <a href="/polymedic/public/" class="btn-home">
                                <i class="bi bi-house me-2"></i>Home
                            </a>
                            <button type="button" class="btn btn-outline-custom" id="step3Prev">
                                <i class="bi bi-arrow-left"></i> Back
                            </button>
                            <button type="button" class="btn btn-primary-custom" id="step3Next">
                                Review Booking <i class="bi bi-arrow-right"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- ===== STEP 4: Review & Payment ===== -->
                    <div class="form-step" data-step="4">
                        <div class="step-title">
                            <span class="badge bg-primary rounded-pill">4</span>
                            Review &amp; Payment
                        </div>
                        <p class="step-subtitle">Please review your details and confirm payment.</p>
                        
                        <!-- Review Section -->
                        <div class="review-section">
                            <div class="review-item">
                                <span class="review-label"><i class="bi bi-calendar3 me-1"></i> Date &amp; Time</span>
                                <span class="review-value" id="reviewDateTime">-</span>
                            </div>
                            <div class="review-item">
                                <span class="review-label"><i class="bi bi-envelope me-1"></i> Contact</span>
                                <span class="review-value" id="reviewContact">-</span>
                            </div>
                            <div class="review-item">
                                <span class="review-label"><i class="bi bi-person me-1"></i> Patient</span>
                                <span class="review-value" id="reviewPatient">-</span>
                            </div>
                            <div class="review-item">
                                <span class="review-label"><i class="bi bi-tags me-1"></i> Service Type</span>
                                <span class="review-value" id="reviewServiceType">-</span>
                            </div>
                            <div class="review-item">
                                <span class="review-label"><i class="bi bi-clipboard2 me-1"></i> Selected Services</span>
                                <span class="review-value" id="reviewServices" style="font-size: 0.85rem;">-</span>
                            </div>
                            <div class="review-item">
                                <span class="review-label"><i class="bi bi-clipboard2 me-1"></i> Other Requests</span>
                                <span class="review-value" id="reviewOthers">None</span>
                            </div>
                        </div>
                        
                        <!-- Payment Section -->
                        <div class="payment-summary">
                            <h6 class="fw-bold mb-3" style="color: var(--primary-blue);">
                                <i class="bi bi-credit-card me-2"></i>Payment Summary
                            </h6>
                            <div class="payment-row">
                                <span>Consultation Fee</span>
                                <strong>₱ 500.00</strong>
                            </div>
                            <div class="payment-row">
                                <span>Service Fee</span>
                                <strong>₱ 0.00</strong>
                            </div>
                            <div class="payment-row payment-total">
                                <span class="fw-bold">Total</span>
                                <strong style="color: var(--accent-blue); font-size: 1.3rem;">₱ 500.00</strong>
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="paymentAgree" style="width: 20px; height: 20px; accent-color: var(--accent-teal);">
                                <label class="form-check-label" for="paymentAgree" style="font-size: 0.95rem;">
                                    <strong>I agree</strong> to pay the consultation fee upon arrival at the clinic
                                </label>
                            </div>
                        </div>
                        
                        <div class="btn-group-custom">
                            <a href="/polymedic/public/" class="btn-home">
                                <i class="bi bi-house me-2"></i>Home
                            </a>
                            <button type="button" class="btn btn-outline-custom" id="step4Prev">
                                <i class="bi bi-arrow-left"></i> Back
                            </button>
                            <button type="button" class="btn btn-success-custom" id="confirmPayment" disabled>
                                <i class="bi bi-shield-check me-2"></i>Confirm Payment
                            </button>
                        </div>
                    </div>
                </form>
                
            </div>
        </div>
        
        <!-- ===== HELP TEXT ===== -->
        <div class="text-center mt-4">
            <p class="text-muted small">
                <i class="bi bi-shield-lock me-1"></i> Your information is secure and confidential.
                <br>
                For urgent concerns, please call us at <strong>(064) 123-4567</strong>
            </p>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // ===== STEP NAVIGATION =====
    let currentStep = 1;
    const totalSteps = 4;
    let formData = {};
    
    function updateStep(step) {
        document.querySelectorAll('.form-step').forEach((el, index) => {
            el.classList.toggle('active', (index + 1) === step);
        });
        
        for (let i = 1; i <= totalSteps; i++) {
            const circle = document.getElementById('stepCircle' + i);
            const label = document.getElementById('stepLabel' + i);
            
            circle.classList.remove('active', 'completed');
            label.classList.remove('active', 'completed');
            
            if (i < step) {
                circle.classList.add('completed');
                label.classList.add('completed');
            } else if (i === step) {
                circle.classList.add('active');
                label.classList.add('active');
            }
        }
        
        const progress = ((step - 1) / (totalSteps - 1)) * 100;
        document.getElementById('progressLine').style.width = progress + '%';
        
        currentStep = step;
        document.querySelector('.booking-card').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
    
    // ===== SERVICE TYPE SELECTION =====
    const serviceTypeCards = document.querySelectorAll('.service-type-card');
    const labContainer = document.getElementById('labServicesContainer');
    const xrayContainer = document.getElementById('xrayServicesContainer');
    
    serviceTypeCards.forEach(card => {
        card.addEventListener('click', function() {
            serviceTypeCards.forEach(c => c.classList.remove('active'));
            this.classList.add('active');
            
            const type = this.dataset.type;
            
            if (type === 'laboratory') {
                labContainer.style.display = 'block';
                xrayContainer.style.display = 'none';
            } else if (type === 'xray') {
                labContainer.style.display = 'none';
                xrayContainer.style.display = 'block';
            } else if (type === 'both') {
                labContainer.style.display = 'block';
                xrayContainer.style.display = 'block';
            }
        });
    });
    
    // ===== STEP NAVIGATION BUTTONS =====
    document.getElementById('step1Next').addEventListener('click', function() {
        const date = document.getElementById('appointment_date').value;
        const time = document.getElementById('appointment_time').value;
        
        if (!date || !time) {
            alert('⚠️ Please select both a date and time to continue.');
            return;
        }
        
        formData.date = date;
        formData.time = time;
        updateStep(2);
    });
    
    document.getElementById('step2Next').addEventListener('click', function() {
        const email = document.getElementById('email').value;
        const phone = document.getElementById('phone').value;
        const name = document.getElementById('full_name').value;
        const age = document.getElementById('age').value;
        const gender = document.getElementById('gender').value;
        
        if (!email || !phone || !name || !age || !gender) {
            alert('⚠️ Please fill in all required fields.');
            return;
        }
        
        formData.email = email;
        formData.phone = phone;
        formData.name = name;
        formData.age = age;
        formData.gender = gender;
        updateStep(3);
    });
    
    document.getElementById('step3Next').addEventListener('click', function() {
        const others = document.getElementById('other_requests').value || 'None';
        formData.others = others;
        
        const activeCard = document.querySelector('.service-type-card.active');
        const serviceType = activeCard ? activeCard.dataset.type : 'none';
        const serviceTypeLabel = activeCard ? activeCard.querySelector('.title').textContent : 'None';
        formData.serviceType = serviceTypeLabel;
        
        const labChecked = document.querySelectorAll('#labServicesContainer input:checked');
        let labServices = [];
        labChecked.forEach(cb => labServices.push(cb.value));
        
        const xrayChecked = document.querySelectorAll('#xrayServicesContainer input:checked');
        let xrayServices = [];
        xrayChecked.forEach(cb => xrayServices.push(cb.value));
        
        const allServices = [...labServices, ...xrayServices];
        
        if (allServices.length === 0) {
            alert('⚠️ Please select at least one service.');
            return;
        }
        
        formData.services = allServices.join(', ');
        formData.serviceCount = allServices.length;
        
        document.getElementById('reviewDateTime').textContent = formData.date + ' at ' + formData.time;
        document.getElementById('reviewContact').textContent = formData.email + ' | ' + formData.phone;
        document.getElementById('reviewPatient').textContent = formData.name + ' (' + formData.age + ' yrs, ' + formData.gender + ')';
        document.getElementById('reviewServiceType').textContent = formData.serviceType;
        document.getElementById('reviewServices').textContent = formData.services;
        document.getElementById('reviewOthers').textContent = formData.others;
        
        document.getElementById('paymentAgree').checked = false;
        document.getElementById('confirmPayment').disabled = true;
        
        updateStep(4);
    });
    
    // ===== BACK BUTTONS =====
    document.getElementById('step2Prev').addEventListener('click', function() { updateStep(1); });
    document.getElementById('step3Prev').addEventListener('click', function() { updateStep(2); });
    document.getElementById('step4Prev').addEventListener('click', function() { updateStep(3); });
    
    // ===== PAYMENT AGREEMENT =====
    document.getElementById('paymentAgree').addEventListener('change', function() {
        document.getElementById('confirmPayment').disabled = !this.checked;
    });
    
    // ===== CONFIRM PAYMENT =====
    document.getElementById('confirmPayment').addEventListener('click', function() {
        document.getElementById('bookingForm').submit();
    });
});
</script>

<?= $this->endSection() ?>