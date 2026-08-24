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
                
                <form action="/polymedic/public/appointment/submit" method="POST" id="bookingForm">
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
                                    <option value="08:00"> 8:00 AM</option>
                                    <option value="09:00"> 9:00 AM</option>
                                    <option value="10:00"> 10:00 AM</option>
                                    <option value="11:00"> 11:00 AM</option>
                                    <option value="13:00"> 1:00 PM</option>
                                    <option value="14:00"> 2:00 PM</option>
                                    <option value="15:00"> 3:00 PM</option>
                                    <option value="16:00"> 4:00 PM</option>
                                </select>
                            </div>
                        </div>
                        
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
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
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
                            <div class="service-type-card active" data-type="laboratory" id="serviceLab">
                                <span class="check-mark"><i class="bi bi-check"></i></span>
                                <span class="icon">
                                    <img src="/polymedic/public/assets/images/lab-icon.png" alt="Laboratory" style="width: 55px; height: 55px; object-fit: contain;">
                                </span>
                                <div class="title">Laboratory</div>
                                <div class="desc">Blood tests, urinalysis, etc.</div>
                            </div>
                            <div class="service-type-card" data-type="xray" id="serviceXray">
                                <span class="check-mark"><i class="bi bi-check"></i></span>
                                <span class="icon">
                                    <img src="/polymedic/public/assets/images/xray-icon.png" alt="X-Ray" style="width: 55px; height: 55px; object-fit: contain;">
                                </span>
                                <div class="title">X-Ray &amp; Imaging</div>
                                <div class="desc">Chest, skeletal, etc.</div>
                            </div>
                            <div class="service-type-card" data-type="both" id="serviceBoth">
                                <span class="check-mark"><i class="bi bi-check"></i></span>
                                <span class="icon">
                                    <img src="/polymedic/public/assets/images/both-icon.png" alt="Both" style="width: 55px; height: 55px; object-fit: contain;">
                                </span>
                                <div class="title">Both</div>
                                <div class="desc">Laboratory &amp; X-Ray</div>
                            </div>
                        </div>
                        
                        <!-- Laboratory Services (Dynamic from Database) -->
                        <div id="labServicesContainer">
                            <div class="service-category-title">
                                <i class="bi bi-droplet" style="color: #0148ca;"></i> Laboratory Tests
                            </div>
                            <div class="service-grid">
                                <?php if (!empty($labServices)): ?>
                                    <?php foreach ($labServices as $service): ?>
                                        <?php if ($service['is_active'] == 1): ?>
                                            <div class="service-check">
                                                <input type="checkbox" name="lab_services[]" value="<?= esc($service['service_name']) ?>" 
                                                       id="lab_<?= esc($service['id']) ?>" 
                                                       data-price="<?= esc($service['charge']) ?>"
                                                       class="service-checkbox">
                                                <label for="lab_<?= esc($service['id']) ?>">
                                                    <?= esc($service['service_name']) ?>
                                                    <span class="service-price">₱<?= number_format($service['charge'], 2) ?></span>
                                                </label>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="text-muted">No laboratory services available</div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- X-Ray Services (Dynamic from Database) -->
                        <div id="xrayServicesContainer" style="display: none;">
                            <div class="service-category-title">
                                <i class="bi bi-x-ray" style="color: #0a2b4e;"></i> X-Ray Services
                            </div>
                            <div class="service-grid">
                                <?php if (!empty($xrayServices)): ?>
                                    <?php foreach ($xrayServices as $service): ?>
                                        <?php if ($service['is_active'] == 1): ?>
                                            <div class="service-check">
                                                <input type="checkbox" name="xray_services[]" value="<?= esc($service['service_name']) ?>" 
                                                       id="xray_<?= esc($service['id']) ?>"
                                                       data-price="<?= esc($service['charge']) ?>"
                                                       class="service-checkbox">
                                                <label for="xray_<?= esc($service['id']) ?>">
                                                    <?= esc($service['service_name']) ?>
                                                    <span class="service-price">₱<?= number_format($service['charge'], 2) ?></span>
                                                </label>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="text-muted">No X-Ray services available</div>
                                <?php endif; ?>
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
                            <div class="payment-row" id="serviceFeeRow">
                                <span>Service Fee</span>
                                <strong id="serviceFeeDisplay">₱ 0.00</strong>
                            </div>
                            <div class="payment-row payment-total">
                                <span class="fw-bold">Total</span>
                                <strong style="color: var(--accent-blue); font-size: 1.3rem;" id="totalAmountDisplay">₱ 500.00</strong>
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
                            <button type="button" class="btn btn-success-custom" id="confirmBooking" disabled>
                                <i class="bi bi-shield-check me-2"></i>Confirm Booking
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

<style>
/* ===== SERVICE PRICE STYLES ===== */
.service-price {
    color: #0148ca;
    font-weight: 600;
    font-size: 0.75rem;
    margin-left: 0.5rem;
    background: #e6f0fa;
    padding: 0.1rem 0.5rem;
    border-radius: 30px;
}

.service-check {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.3rem 0.5rem;
    border-radius: 6px;
    transition: background 0.2s ease;
}

.service-check:hover {
    background: #f8faff;
}

.service-check input[type="checkbox"] {
    width: 16px;
    height: 16px;
    accent-color: #0148ca;
    cursor: pointer;
}

.service-check label {
    font-size: 0.85rem;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    flex: 1;
    margin: 0;
}

/* ===== PAYMENT SUMMARY ===== */
.payment-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
    border-bottom: 1px solid #f0f4ff;
}

.payment-row.payment-total {
    border-bottom: none;
    padding-top: 0.75rem;
    margin-top: 0.5rem;
    border-top: 2px solid #0148ca;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 768px) {
    .service-check {
        flex-wrap: wrap;
    }
    
    .service-price {
        font-size: 0.65rem;
        padding: 0.1rem 0.4rem;
    }
    
    .service-check label {
        font-size: 0.75rem;
        flex-wrap: wrap;
    }
}

@media (max-width: 480px) {
    .service-grid {
        grid-template-columns: 1fr !important;
    }
    
    .service-price {
        display: block;
        margin-left: 0;
        margin-top: 0.2rem;
        text-align: left;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // ===== STEP NAVIGATION =====
    let currentStep = 1;
    const totalSteps = 4;
    let formData = {};
    let selectedServices = [];
    let servicePrices = {};
    
    // ===== LOAD SERVICE PRICES =====
    function loadServicePrices() {
        // Get prices from data attributes on checkboxes
        document.querySelectorAll('.service-checkbox').forEach(cb => {
            const name = cb.value;
            const price = parseFloat(cb.dataset.price) || 0;
            servicePrices[name] = price;
        });
    }
    loadServicePrices();
    
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
    
    // ===== CALCULATE TOTAL =====
    function calculateTotal() {
        let totalServiceFee = 0;
        const selectedNames = [];
        
        document.querySelectorAll('.service-checkbox:checked').forEach(cb => {
            const name = cb.value;
            const price = servicePrices[name] || 0;
            totalServiceFee += price;
            selectedNames.push(name);
        });
        
        const consultationFee = 500.00;
        const total = consultationFee + totalServiceFee;
        
        // Update display
        document.getElementById('serviceFeeDisplay').textContent = '₱ ' + totalServiceFee.toFixed(2);
        document.getElementById('totalAmountDisplay').textContent = '₱ ' + total.toFixed(2);
        
        // Store for review
        selectedServices = selectedNames;
        formData.serviceFee = totalServiceFee;
        formData.totalAmount = total;
        
        return total;
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
            
            // Recalculate total
            calculateTotal();
        });
    });
    
    // ===== SERVICE CHECKBOX CHANGE =====
    document.querySelectorAll('.service-checkbox').forEach(cb => {
        cb.addEventListener('change', function() {
            calculateTotal();
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
        
        const labChecked = document.querySelectorAll('#labServicesContainer .service-checkbox:checked');
        const xrayChecked = document.querySelectorAll('#xrayServicesContainer .service-checkbox:checked');
        
        let labServices = [];
        let xrayServices = [];
        let allServices = [];
        
        labChecked.forEach(cb => {
            labServices.push(cb.value);
            allServices.push(cb.value);
        });
        
        xrayChecked.forEach(cb => {
            xrayServices.push(cb.value);
            allServices.push(cb.value);
        });
        
        if (allServices.length === 0) {
            alert('⚠️ Please select at least one service.');
            return;
        }
        
        // Calculate total
        const total = calculateTotal();
        
        formData.labServices = labServices;
        formData.xrayServices = xrayServices;
        formData.services = allServices.join(', ');
        formData.serviceCount = allServices.length;
        formData.totalAmount = total;
        
        document.getElementById('reviewDateTime').textContent = formData.date + ' at ' + formData.time;
        document.getElementById('reviewContact').textContent = formData.email + ' | ' + formData.phone;
        document.getElementById('reviewPatient').textContent = formData.name + ' (' + formData.age + ' yrs, ' + formData.gender + ')';
        document.getElementById('reviewServiceType').textContent = formData.serviceType;
        document.getElementById('reviewServices').textContent = formData.services;
        document.getElementById('reviewOthers').textContent = formData.others;
        
        document.getElementById('paymentAgree').checked = false;
        document.getElementById('confirmBooking').disabled = true;
        
        updateStep(4);
    });
    
    // ===== BACK BUTTONS =====
    document.getElementById('step2Prev').addEventListener('click', function() { updateStep(1); });
    document.getElementById('step3Prev').addEventListener('click', function() { updateStep(2); });
    document.getElementById('step4Prev').addEventListener('click', function() { updateStep(3); });
    
    // ===== PAYMENT AGREEMENT =====
    document.getElementById('paymentAgree').addEventListener('change', function() {
        document.getElementById('confirmBooking').disabled = !this.checked;
    });
    
    // ===== CONFIRM PAYMENT =====
    document.getElementById('confirmBooking').addEventListener('click', function() {
        document.getElementById('bookingForm').submit();
    });
});
</script>

<?= $this->endSection() ?>