<?= $this->extend('layouts/AppointmentLayout') ?>

<?= $this->section('AppointmentContent') ?>

<!-- Load booking CSS -->
<link href="/polymedic/public/assets/css/booking.css" rel="stylesheet">

<?php
    // Country dialling options for the phone field.
    // 'len' is the number of digits expected AFTER the dial code.
    // 'groups' controls the live display formatting.
    $phoneCountries = [
        ['iso' => 'PH', 'name' => 'Philippines',        'dial' => '63',  'len' => 10, 'groups' => [3, 3, 4], 'example' => '0912 345 6789', 'trunk' => '0'],
        ['iso' => 'US', 'name' => 'United States',      'dial' => '1',   'len' => 10, 'groups' => [3, 3, 4], 'example' => '415 555 0132', 'trunk' => ''],
        ['iso' => 'CA', 'name' => 'Canada',             'dial' => '1',   'len' => 10, 'groups' => [3, 3, 4], 'example' => '416 555 0132', 'trunk' => ''],
        ['iso' => 'AU', 'name' => 'Australia',          'dial' => '61',  'len' => 9,  'groups' => [3, 3, 3], 'example' => '0412 345 678', 'trunk' => '0'],
        ['iso' => 'GB', 'name' => 'United Kingdom',     'dial' => '44',  'len' => 10, 'groups' => [4, 6],    'example' => '07400 123456', 'trunk' => '0'],
        ['iso' => 'SG', 'name' => 'Singapore',          'dial' => '65',  'len' => 8,  'groups' => [4, 4],    'example' => '8123 4567',    'trunk' => ''],
        ['iso' => 'HK', 'name' => 'Hong Kong',          'dial' => '852', 'len' => 8,  'groups' => [4, 4],    'example' => '5123 4567',    'trunk' => ''],
        ['iso' => 'JP', 'name' => 'Japan',              'dial' => '81',  'len' => 10, 'groups' => [2, 4, 4], 'example' => '090 1234 5678', 'trunk' => '0'],
        ['iso' => 'KR', 'name' => 'South Korea',        'dial' => '82',  'len' => 10, 'groups' => [2, 4, 4], 'example' => '010 1234 5678', 'trunk' => '0'],
        ['iso' => 'MY', 'name' => 'Malaysia',           'dial' => '60',  'len' => 9,  'groups' => [2, 3, 4], 'example' => '012 345 6789', 'trunk' => '0'],
        ['iso' => 'ID', 'name' => 'Indonesia',          'dial' => '62',  'len' => 10, 'groups' => [3, 4, 3], 'example' => '0812 3456 789', 'trunk' => '0'],
        ['iso' => 'AE', 'name' => 'United Arab Emirates','dial' => '971','len' => 9,  'groups' => [2, 3, 4], 'example' => '050 123 4567', 'trunk' => '0'],
        ['iso' => 'SA', 'name' => 'Saudi Arabia',       'dial' => '966', 'len' => 9,  'groups' => [2, 3, 4], 'example' => '050 123 4567', 'trunk' => '0'],
        ['iso' => 'QA', 'name' => 'Qatar',              'dial' => '974', 'len' => 8,  'groups' => [4, 4],    'example' => '3312 3456',    'trunk' => ''],
        ['iso' => 'KW', 'name' => 'Kuwait',             'dial' => '965', 'len' => 8,  'groups' => [4, 4],    'example' => '5012 3456',    'trunk' => ''],
        ['iso' => 'IT', 'name' => 'Italy',              'dial' => '39',  'len' => 10, 'groups' => [3, 3, 4], 'example' => '312 345 6789', 'trunk' => ''],
        ['iso' => 'DE', 'name' => 'Germany',            'dial' => '49',  'len' => 10, 'groups' => [3, 3, 4], 'example' => '0151 234 5678', 'trunk' => '0'],
    ];

    $oldCountry = old('phone_country') ?: 'PH';
    $maxDate    = date('Y-m-d', strtotime('+90 days'));
?>

<!-- ====== BOOKING PAGE ====== -->
<section class="booking-page">
    <div class="container">

        <!-- ===== STEP PROGRESS ===== -->
        <div class="step-progress-wrapper">
            <div class="step-progress">
                <div class="progress-line" id="progressLine"></div>

                <div class="step-item" data-step="1">
                    <div class="step-circle active" id="stepCircle1">
                        <span class="step-number">1</span>
                        <span class="step-icon"><i class="bi bi-calendar3"></i></span>
                    </div>
                    <span class="step-label active" id="stepLabel1">Date &amp; Time</span>
                </div>

                <div class="step-item" data-step="2">
                    <div class="step-circle" id="stepCircle2">
                        <span class="step-number">2</span>
                        <span class="step-icon"><i class="bi bi-person"></i></span>
                    </div>
                    <span class="step-label" id="stepLabel2">Your Details</span>
                </div>

                <div class="step-item" data-step="3">
                    <div class="step-circle" id="stepCircle3">
                        <span class="step-number">3</span>
                        <span class="step-icon"><i class="bi bi-clipboard2-pulse"></i></span>
                    </div>
                    <span class="step-label" id="stepLabel3">Services</span>
                </div>

                <!-- Review & Payment is one step; the old markup had a fifth
                     circle the script never advanced to. -->
                <div class="step-item" data-step="4">
                    <div class="step-circle" id="stepCircle4">
                        <span class="step-number">4</span>
                        <span class="step-icon"><i class="bi bi-check2-circle"></i></span>
                    </div>
                    <span class="step-label" id="stepLabel4">Review &amp; Pay</span>
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

                <form action="/polymedic/public/appointment/submit" method="POST" id="bookingForm" novalidate>
                    <?= csrf_field() ?>

                    <!-- =========================================================
                         STEP 1 — Date & Time
                         ========================================================= -->
                    <div class="form-step active" data-step="1">
                        <div class="step-title">
                            <span class="badge bg-primary rounded-pill">1</span>
                            Select Date &amp; Time
                        </div>
                        <p class="step-subtitle">Choose your preferred appointment schedule.</p>

                        <div class="row g-4">
                            <div class="col-md-7">
                                <label class="form-label-custom" for="appointment_date">
                                    <i class="bi bi-calendar-event me-1"></i> Appointment Date
                                    <span class="req">*</span>
                                </label>
                                <input type="date"
                                       class="form-control form-control-custom"
                                       name="appointment_date"
                                       id="appointment_date"
                                       value="<?= esc(old('appointment_date') ?? '', 'attr') ?>"
                                       min="<?= date('Y-m-d') ?>"
                                       max="<?= $maxDate ?>"
                                       aria-describedby="appointment_date_hint appointment_date_error"
                                       required>
                                <p class="field-hint" id="appointment_date_hint">
                                    Format <code>YYYY-MM-DD</code> — e.g. <code><?= date('Y-m-d', strtotime('+3 days')) ?></code>.
                                    You can book up to 90 days ahead.
                                </p>
                                <p class="field-echo" id="dateEcho" hidden></p>
                                <p class="field-error" id="appointment_date_error" role="alert"></p>
                            </div>

                            <div class="col-md-5">
                                <label class="form-label-custom" for="appointment_time">
                                    <i class="bi bi-clock me-1"></i> Preferred Time
                                    <span class="req">*</span>
                                </label>
                                <select class="form-select form-select-custom"
                                        name="appointment_time"
                                        id="appointment_time"
                                        aria-describedby="appointment_time_hint appointment_time_error"
                                        required>
                                    <option value="">Select time</option>
                                    <optgroup label="Morning">
                                        <option value="08:00">8:00 AM</option>
                                        <option value="09:00">9:00 AM</option>
                                        <option value="10:00">10:00 AM</option>
                                        <option value="11:00">11:00 AM</option>
                                    </optgroup>
                                    <optgroup label="Afternoon">
                                        <option value="13:00">1:00 PM</option>
                                        <option value="14:00">2:00 PM</option>
                                        <option value="15:00">3:00 PM</option>
                                        <option value="16:00">4:00 PM</option>
                                    </optgroup>
                                </select>
                                <p class="field-hint" id="appointment_time_hint">
                                    Clinic hours are 8:00 AM – 5:00 PM. Lunch break 12:00 – 1:00 PM.
                                </p>
                                <p class="field-error" id="appointment_time_error" role="alert"></p>
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

                    <!-- =========================================================
                         STEP 2 — Personal details
                         ========================================================= -->
                    <div class="form-step" data-step="2">
                        <div class="step-title">
                            <span class="badge bg-primary rounded-pill">2</span>
                            Your Information
                        </div>
                        <p class="step-subtitle">Tell us about yourself so we can prepare for your visit.</p>

                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="form-label-custom" for="full_name">
                                    <i class="bi bi-person me-1"></i> Full Name
                                    <span class="req">*</span>
                                </label>
                                <input type="text"
                                       class="form-control form-control-custom"
                                       name="full_name"
                                       id="full_name"
                                       value="<?= esc(old('full_name') ?? '', 'attr') ?>"
                                       placeholder="Juan Dela Cruz"
                                       maxlength="100"
                                       autocomplete="name"
                                       autocapitalize="words"
                                       aria-describedby="full_name_hint full_name_error"
                                       required>
                                <p class="field-hint" id="full_name_hint">
                                    First name then last name — e.g. <code>Juan Dela Cruz</code>.
                                    Letters, spaces, hyphens and apostrophes only.
                                </p>
                                <p class="field-error" id="full_name_error" role="alert"></p>
                            </div>

                            <div class="col-md-3 col-6">
                                <label class="form-label-custom" for="age">
                                    <i class="bi bi-cake2 me-1"></i> Age
                                    <span class="req">*</span>
                                </label>
                                <input type="number"
                                       class="form-control form-control-custom"
                                       name="age"
                                       id="age"
                                       value="<?= esc(old('age') ?? '', 'attr') ?>"
                                       placeholder="25"
                                       min="0" max="120" step="1"
                                       inputmode="numeric"
                                       aria-describedby="age_hint age_error"
                                       required>
                                <p class="field-hint" id="age_hint">Whole years — e.g. <code>25</code>.</p>
                                <p class="field-error" id="age_error" role="alert"></p>
                            </div>

                            <div class="col-md-3 col-6">
                                <label class="form-label-custom" for="gender">
                                    <i class="bi bi-gender-ambiguous me-1"></i> Gender
                                    <span class="req">*</span>
                                </label>
                                <select class="form-select form-select-custom"
                                        name="gender"
                                        id="gender"
                                        aria-describedby="gender_hint gender_error"
                                        required>
                                    <option value="">Select</option>
                                    <?php foreach (['Male', 'Female', 'Other'] as $g): ?>
                                        <option value="<?= $g ?>"<?= old('gender') === $g ? ' selected' : '' ?>><?= $g ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="field-hint" id="gender_hint">Used for lab reference ranges.</p>
                                <p class="field-error" id="gender_error" role="alert"></p>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label-custom" for="email">
                                    <i class="bi bi-envelope me-1"></i> Email Address
                                    <span class="req">*</span>
                                </label>
                                <input type="email"
                                       class="form-control form-control-custom"
                                       name="email"
                                       id="email"
                                       value="<?= esc(old('email') ?? '', 'attr') ?>"
                                       placeholder="juan.delacruz@gmail.com"
                                       maxlength="150"
                                       autocomplete="email"
                                       autocapitalize="none"
                                       spellcheck="false"
                                       aria-describedby="email_hint email_error"
                                       required>
                                <p class="field-hint" id="email_hint">
                                    Format <code>name@domain.com</code> — your confirmation and results link go here.
                                </p>
                                <p class="field-suggest" id="emailSuggest" hidden></p>
                                <p class="field-error" id="email_error" role="alert"></p>
                            </div>

                            <!-- ===== PHONE WITH COUNTRY SELECTOR ===== -->
                            <div class="col-md-6">
                                <label class="form-label-custom" for="phone_national">
                                    <i class="bi bi-phone me-1"></i> Mobile Number
                                    <span class="req">*</span>
                                </label>

                                <div class="phone-field" id="phoneField">
                                    <select class="phone-country"
                                            name="phone_country"
                                            id="phone_country"
                                            aria-label="Country code">
                                        <?php foreach ($phoneCountries as $c): ?>
                                            <option value="<?= esc($c['iso'], 'attr') ?>"
                                                    data-dial="<?= esc($c['dial'], 'attr') ?>"
                                                    data-len="<?= esc($c['len'], 'attr') ?>"
                                                    data-groups="<?= esc(implode(',', $c['groups']), 'attr') ?>"
                                                    data-example="<?= esc($c['example'], 'attr') ?>"
                                                    data-trunk="<?= esc($c['trunk'], 'attr') ?>"
                                                    data-name="<?= esc($c['name'], 'attr') ?>"
                                                    <?= $oldCountry === $c['iso'] ? 'selected' : '' ?>>
                                                <?= esc($c['iso']) ?> +<?= esc($c['dial']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>

                                    <span class="phone-dial" id="phoneDial" aria-hidden="true">+63</span>

                                    <input type="tel"
                                           class="phone-input"
                                           id="phone_national"
                                           value="<?= esc(old('phone_national') ?? '', 'attr') ?>"
                                           placeholder="0912 345 6789"
                                           inputmode="tel"
                                           autocomplete="tel-national"
                                           aria-describedby="phone_hint phone_error"
                                           required>
                                </div>

                                <!-- What the controller receives (unchanged field name) -->
                                <input type="hidden" name="phone" id="phone">
                                <input type="hidden" name="phone_e164" id="phone_e164">

                                <p class="field-hint" id="phone_hint">
                                    Example for <span id="phoneCountryName">Philippines</span>:
                                    <code id="phoneExample">0912 345 6789</code>.
                                    Digits are spaced automatically.
                                </p>
                                <p class="field-error" id="phone_error" role="alert"></p>
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

                    <!-- =========================================================
                         STEP 3 — Services
                         ========================================================= -->
                    <div class="form-step" data-step="3">
                        <div class="step-title">
                            <span class="badge bg-primary rounded-pill">3</span>
                            Select Services
                        </div>
                        <p class="step-subtitle">Choose the type of service and specific tests you need.</p>

                        <label class="form-label-custom">
                            <i class="bi bi-tags me-1"></i> Service Type
                            <span class="req">*</span>
                        </label>
                        <div class="service-type-grid">
                            <div class="service-type-card active" data-type="laboratory" id="serviceLab" tabindex="0" role="button" aria-pressed="true">
                                <span class="check-mark"><i class="bi bi-check"></i></span>
                                <span class="icon">
                                    <img src="/polymedic/public/assets/images/lab-icon.png" alt="" style="width: 55px; height: 55px; object-fit: contain;">
                                </span>
                                <div class="title">Laboratory</div>
                                <div class="desc">Blood tests, urinalysis, etc.</div>
                            </div>
                            <div class="service-type-card" data-type="xray" id="serviceXray" tabindex="0" role="button" aria-pressed="false">
                                <span class="check-mark"><i class="bi bi-check"></i></span>
                                <span class="icon">
                                    <img src="/polymedic/public/assets/images/xray-icon.png" alt="" style="width: 55px; height: 55px; object-fit: contain;">
                                </span>
                                <div class="title">X-Ray &amp; Imaging</div>
                                <div class="desc">Chest, skeletal, etc.</div>
                            </div>
                            <div class="service-type-card" data-type="both" id="serviceBoth" tabindex="0" role="button" aria-pressed="false">
                                <span class="check-mark"><i class="bi bi-check"></i></span>
                                <span class="icon">
                                    <img src="/polymedic/public/assets/images/both-icon.png" alt="" style="width: 55px; height: 55px; object-fit: contain;">
                                </span>
                                <div class="title">Both</div>
                                <div class="desc">Laboratory &amp; X-Ray</div>
                            </div>
                        </div>

                        <p class="field-error" id="services_error" role="alert"></p>

                        <!-- Laboratory services -->
                        <div id="labServicesContainer">
                            <div class="service-category-title">
                                <i class="bi bi-droplet" style="color: #0148ca;"></i> Laboratory Tests
                                <span class="picked-count" id="labPicked">0 selected</span>
                            </div>
                            <div class="service-grid">
                                <?php if (! empty($labServices)): ?>
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

                        <!-- X-Ray services -->
                        <div id="xrayServicesContainer" style="display: none;">
                            <div class="service-category-title">
                                <i class="bi bi-radioactive" style="color: #0a2b4e;"></i> X-Ray Services
                                <span class="picked-count" id="xrayPicked">0 selected</span>
                            </div>
                            <div class="service-grid">
                                <?php if (! empty($xrayServices)): ?>
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

                        <!-- Other requests -->
                        <div class="mt-4">
                            <label class="form-label-custom" for="other_requests">
                                <i class="bi bi-clipboard2-pulse me-1"></i> Other Requests
                                <span class="optional">Optional</span>
                            </label>
                            <textarea class="form-control form-control-custom"
                                      name="other_requests"
                                      id="other_requests"
                                      rows="3"
                                      maxlength="500"
                                      aria-describedby="other_requests_hint"
                                      placeholder="e.g. I need a fasting blood test before 9 AM, or I need a medical certificate for work."><?= esc(old('other_requests') ?? '') ?></textarea>
                            <div class="hint-row">
                                <p class="field-hint mb-0" id="other_requests_hint">
                                    Tell us about fasting, mobility needs, or documents you need.
                                </p>
                                <span class="char-count" id="otherCount">0 / 500</span>
                            </div>
                        </div>

                        <!-- Running total -->
                        <div class="picked-summary" id="pickedSummary">
                            <div>
                                <strong id="pickedSummaryCount">0 services selected</strong>
                                <small>Consultation fee ₱500.00 is added at review.</small>
                            </div>
                            <span class="picked-summary-total" id="pickedSummaryTotal">₱ 0.00</span>
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

                    <!-- =========================================================
                         STEP 4 — Review & payment
                         ========================================================= -->
                    <div class="form-step" data-step="4">
                        <div class="step-title">
                            <span class="badge bg-primary rounded-pill">4</span>
                            Review &amp; Payment
                        </div>
                        <p class="step-subtitle">Please review your details and confirm payment.</p>

                        <div class="review-section">
                            <div class="review-item">
                                <span class="review-label"><i class="bi bi-calendar3 me-1"></i> Date &amp; Time</span>
                                <span class="review-value" id="reviewDateTime">-</span>
                                <button type="button" class="review-edit" data-goto="1">Edit</button>
                            </div>
                            <div class="review-item">
                                <span class="review-label"><i class="bi bi-person me-1"></i> Patient</span>
                                <span class="review-value" id="reviewPatient">-</span>
                                <button type="button" class="review-edit" data-goto="2">Edit</button>
                            </div>
                            <div class="review-item">
                                <span class="review-label"><i class="bi bi-envelope me-1"></i> Contact</span>
                                <span class="review-value" id="reviewContact">-</span>
                                <button type="button" class="review-edit" data-goto="2">Edit</button>
                            </div>
                            <div class="review-item">
                                <span class="review-label"><i class="bi bi-tags me-1"></i> Service Type</span>
                                <span class="review-value" id="reviewServiceType">-</span>
                                <button type="button" class="review-edit" data-goto="3">Edit</button>
                            </div>
                            <div class="review-item">
                                <span class="review-label"><i class="bi bi-clipboard2 me-1"></i> Selected Services</span>
                                <span class="review-value" id="reviewServices" style="font-size: 0.85rem;">-</span>
                                <button type="button" class="review-edit" data-goto="3">Edit</button>
                            </div>
                            <div class="review-item">
                                <span class="review-label"><i class="bi bi-chat-left-text me-1"></i> Other Requests</span>
                                <span class="review-value" id="reviewOthers">None</span>
                                <button type="button" class="review-edit" data-goto="3">Edit</button>
                            </div>
                        </div>

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
                            <p class="payment-note">
                                <i class="bi bi-info-circle me-1"></i>
                                Payable at the clinic on the day of your visit. No online payment is required now.
                            </p>
                        </div>

                        <div class="mt-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="paymentAgree" style="width: 20px; height: 20px; accent-color: var(--accent-teal);">
                                <label class="form-check-label" for="paymentAgree" style="font-size: 0.95rem;">
                                    <strong>I agree</strong> to pay the consultation fee upon arrival at the clinic
                                </label>
                            </div>
                            <p class="field-error" id="agree_error" role="alert"></p>
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
/* ===== LABEL EXTRAS ===== */
.req { color: #dc3545; margin-left: 0.15rem; }

.optional {
    margin-left: 0.4rem;
    font-size: 0.68rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #94a3b8;
    background: #f1f5f9;
    border-radius: 30px;
    padding: 0.1rem 0.45rem;
}

/* ===== HINTS, ERRORS, ECHOES ===== */
.field-hint {
    margin: 0.35rem 0 0;
    font-size: 0.76rem;
    line-height: 1.5;
    color: #7c8ba1;
}

.field-hint code,
.field-echo code {
    background: #eef3fb;
    color: #0148ca;
    padding: 0.05rem 0.35rem;
    border-radius: 4px;
    font-size: 0.95em;
}

.field-echo {
    margin: 0.35rem 0 0;
    font-size: 0.78rem;
    font-weight: 600;
    color: #0f766e;
}

.field-error {
    display: none;
    margin: 0.35rem 0 0;
    font-size: 0.78rem;
    font-weight: 500;
    color: #dc3545;
}

.field-error.show { display: block; }

.field-error::before {
    content: '\F33A';
    font-family: 'bootstrap-icons';
    margin-right: 0.3rem;
    font-size: 0.9em;
}

.field-suggest {
    margin: 0.35rem 0 0;
    font-size: 0.78rem;
    color: #b45309;
}

.field-suggest button {
    background: none;
    border: none;
    padding: 0;
    font: inherit;
    font-weight: 700;
    color: #0148ca;
    cursor: pointer;
    text-decoration: underline;
}

.is-invalid,
.form-control-custom.is-invalid,
.form-select-custom.is-invalid {
    border-color: #dc3545 !important;
    box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.12) !important;
}

.hint-row {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: 1rem;
}

.char-count {
    font-size: 0.72rem;
    color: #94a3b8;
    white-space: nowrap;
}

.char-count.near-limit { color: #b45309; font-weight: 600; }

/* ===== PHONE FIELD ===== */
.phone-field {
    display: flex;
    align-items: stretch;
    border: 1px solid #dbe3ef;
    border-radius: 10px;
    background: #fff;
    overflow: hidden;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.phone-field:focus-within {
    border-color: #0148ca;
    box-shadow: 0 0 0 3px rgba(1, 72, 202, 0.12);
}

.phone-field.is-invalid {
    border-color: #dc3545;
    box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.12);
}

.phone-country {
    border: none;
    background: #f6f9fe;
    border-right: 1px solid #e4ebf5;
    padding: 0 0.6rem;
    font-size: 0.82rem;
    font-weight: 600;
    color: #0a2b4e;
    cursor: pointer;
    outline: none;
    max-width: 105px;
}

.phone-dial {
    display: flex;
    align-items: center;
    padding: 0 0.15rem 0 0.7rem;
    font-size: 0.9rem;
    font-weight: 600;
    color: #64748b;
    white-space: nowrap;
}

.phone-input {
    flex: 1;
    min-width: 0;
    border: none;
    outline: none;
    padding: 0.7rem 0.75rem;
    font-size: 0.95rem;
    font-family: inherit;
    letter-spacing: 0.02em;
    color: #0a2b4e;
    background: transparent;
}

.phone-input::placeholder { color: #b6c2d3; letter-spacing: normal; }

/* ===== SERVICE COUNTS + SUMMARY ===== */
.picked-count {
    margin-left: auto;
    font-size: 0.72rem;
    font-weight: 600;
    color: #64748b;
    background: #f1f5f9;
    border-radius: 30px;
    padding: 0.1rem 0.55rem;
}

.service-category-title {
    display: flex;
    align-items: center;
    gap: 0.45rem;
}

.picked-summary {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    margin-top: 1.25rem;
    padding: 0.85rem 1.1rem;
    border-radius: 12px;
    background: #f6f9fe;
    border: 1px solid #e4ebf5;
}

.picked-summary strong {
    display: block;
    font-size: 0.9rem;
    color: #0a2b4e;
}

.picked-summary small {
    font-size: 0.74rem;
    color: #7c8ba1;
}

.picked-summary-total {
    font-size: 1.15rem;
    font-weight: 700;
    color: #0148ca;
    white-space: nowrap;
}

/* ===== REVIEW EDIT LINKS ===== */
.review-item { position: relative; }

.review-edit {
    background: none;
    border: none;
    padding: 0;
    margin-left: 0.6rem;
    font-size: 0.75rem;
    font-weight: 700;
    color: #0148ca;
    cursor: pointer;
    text-decoration: underline;
    flex-shrink: 0;
}

.review-edit:hover { color: #012f85; }

.payment-note {
    margin: 0.85rem 0 0;
    font-size: 0.75rem;
    color: #7c8ba1;
}

/* ===== BUTTON LOADING ===== */
.btn-success-custom.is-loading {
    pointer-events: none;
    opacity: 0.8;
}

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

.service-check:hover { background: #f8faff; }

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
    .service-check { flex-wrap: wrap; }
    .service-price { font-size: 0.65rem; padding: 0.1rem 0.4rem; }
    .service-check label { font-size: 0.75rem; flex-wrap: wrap; }

    .phone-country { max-width: 88px; font-size: 0.76rem; }
    .phone-input { font-size: 0.9rem; }

    .picked-summary { flex-direction: column; align-items: flex-start; gap: 0.5rem; }
}

@media (max-width: 480px) {
    .service-grid { grid-template-columns: 1fr !important; }

    .service-price {
        display: block;
        margin-left: 0;
        margin-top: 0.2rem;
        text-align: left;
    }

    .hint-row { flex-direction: column; gap: 0.2rem; }
}

@media (prefers-reduced-motion: reduce) {
    .form-step, .service-check, .phone-field { transition: none !important; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {

    // =========================================================
    // SETUP
    // =========================================================
    const CONSULTATION_FEE = 500.00;
    const totalSteps = 4;

    let currentStep = 1;
    let formData = {};
    const servicePrices = {};

    const peso = function(value) {
        return '₱ ' + value.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    };

    const $ = function(id) { return document.getElementById(id); };

    document.querySelectorAll('.service-checkbox').forEach(function(cb) {
        servicePrices[cb.value] = parseFloat(cb.dataset.price) || 0;
    });

    // =========================================================
    // INLINE ERRORS (replaces native popups and alert())
    // =========================================================
    function showError(field, errorId, message) {
        const box = $(errorId);
        if (box) {
            box.textContent = message;
            box.classList.add('show');
        }
        if (field) {
            field.classList.add('is-invalid');
            field.setAttribute('aria-invalid', 'true');
            if (typeof field.focus === 'function') {
                field.focus({ preventScroll: false });
            }
        }
        return false;
    }

    function clearError(field, errorId) {
        const box = $(errorId);
        if (box) {
            box.textContent = '';
            box.classList.remove('show');
        }
        if (field) {
            field.classList.remove('is-invalid');
            field.removeAttribute('aria-invalid');
        }
        return true;
    }

    // =========================================================
    // STEP NAVIGATION
    // =========================================================
    function updateStep(step) {
        document.querySelectorAll('.form-step').forEach(function(el) {
            el.classList.toggle('active', Number(el.dataset.step) === step);
        });

        for (let i = 1; i <= totalSteps; i++) {
            const circle = $('stepCircle' + i);
            const label  = $('stepLabel' + i);
            if (!circle || !label) continue;

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

        const line = $('progressLine');
        if (line) line.style.width = (((step - 1) / (totalSteps - 1)) * 100) + '%';

        currentStep = step;
        document.querySelector('.booking-card').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    // =========================================================
    // STEP 1 — date & time
    // =========================================================
    const dateInput = $('appointment_date');
    const timeInput = $('appointment_time');
    const dateEcho  = $('dateEcho');

    function describeDate(value) {
        const parts = value.split('-');
        if (parts.length !== 3) return '';
        const d = new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]));
        if (isNaN(d.getTime())) return '';
        return d.toLocaleDateString(undefined, {
            weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
        });
    }

    dateInput.addEventListener('change', function() {
        const text = describeDate(this.value);
        if (text) {
            dateEcho.textContent = 'You selected ' + text;
            dateEcho.hidden = false;
        } else {
            dateEcho.hidden = true;
        }
        clearError(dateInput, 'appointment_date_error');
    });

    function validateStep1() {
        if (!dateInput.value) {
            return showError(dateInput, 'appointment_date_error', 'Please choose an appointment date.');
        }

        const chosen = new Date(dateInput.value + 'T00:00:00');
        const today  = new Date(); today.setHours(0, 0, 0, 0);

        if (isNaN(chosen.getTime())) {
            return showError(dateInput, 'appointment_date_error', 'That date is not valid. Use the format YYYY-MM-DD.');
        }
        if (chosen < today) {
            return showError(dateInput, 'appointment_date_error', 'The date has already passed. Pick today or a later date.');
        }
        if (dateInput.max && dateInput.value > dateInput.max) {
            return showError(dateInput, 'appointment_date_error', 'Bookings are open up to 90 days ahead only.');
        }
        clearError(dateInput, 'appointment_date_error');

        if (!timeInput.value) {
            return showError(timeInput, 'appointment_time_error', 'Please choose a preferred time.');
        }
        clearError(timeInput, 'appointment_time_error');

        formData.date        = dateInput.value;
        formData.dateDisplay = describeDate(dateInput.value);
        formData.time        = timeInput.value;
        formData.timeDisplay = timeInput.options[timeInput.selectedIndex].text.trim();
        return true;
    }

    $('step1Next').addEventListener('click', function() {
        if (validateStep1()) updateStep(2);
    });

    // =========================================================
    // STEP 2 — details
    // =========================================================
    const nameInput    = $('full_name');
    const ageInput     = $('age');
    const genderInput  = $('gender');
    const emailInput   = $('email');
    const countrySel   = $('phone_country');
    const phoneNat     = $('phone_national');
    const phoneHidden  = $('phone');
    const phoneE164    = $('phone_e164');
    const phoneField   = $('phoneField');

    // ---- phone: country-aware formatting ----
    function country() {
        const opt = countrySel.options[countrySel.selectedIndex];
        return {
            iso:     opt.value,
            dial:    opt.dataset.dial,
            len:     parseInt(opt.dataset.len, 10),
            groups:  opt.dataset.groups.split(',').map(Number),
            example: opt.dataset.example,
            trunk:   opt.dataset.trunk || '',
            name:    opt.dataset.name
        };
    }

    // Strip the trunk prefix (the leading 0 in PH, UK, AU...) and any
    // symbols, leaving the national significant number.
    function nationalDigits(raw, c) {
        let digits = (raw || '').replace(/\D/g, '');

        if (digits.indexOf(c.dial) === 0 && digits.length > c.len) {
            digits = digits.slice(c.dial.length);
        }
        if (c.trunk && digits.indexOf(c.trunk) === 0) {
            digits = digits.slice(c.trunk.length);
        }
        return digits.slice(0, c.len);
    }

    function formatPhone(digits, c) {
        const out = [];
        let i = 0;
        for (const size of c.groups) {
            if (i >= digits.length) break;
            out.push(digits.substr(i, size));
            i += size;
        }
        const body = out.join(' ');
        return c.trunk ? (digits ? c.trunk + body : '') : body;
    }

    function syncCountryUi() {
        const c = country();
        $('phoneDial').textContent = '+' + c.dial;
        $('phoneExample').textContent = c.example;
        $('phoneCountryName').textContent = c.name;
        phoneNat.placeholder = c.example;
        phoneNat.setAttribute('maxlength', String(c.len + c.groups.length + 2));
    }

    function reformatPhone() {
        const c = country();
        const digits = nationalDigits(phoneNat.value, c);
        phoneNat.value = formatPhone(digits, c);
        return digits;
    }

    countrySel.addEventListener('change', function() {
        syncCountryUi();
        reformatPhone();
        clearError(phoneField, 'phone_error');
    });

    phoneNat.addEventListener('input', function() {
        reformatPhone();
        clearError(phoneField, 'phone_error');
    });

    syncCountryUi();
    if (phoneNat.value) reformatPhone();

    // ---- email typo suggestion ----
    const commonDomains = ['gmail.com', 'yahoo.com', 'outlook.com', 'hotmail.com', 'icloud.com'];
    const emailSuggest  = $('emailSuggest');

    function suggestDomain(value) {
        const at = value.lastIndexOf('@');
        if (at < 1) return null;

        const domain = value.slice(at + 1).toLowerCase();
        if (!domain || commonDomains.indexOf(domain) !== -1) return null;

        for (const good of commonDomains) {
            // one-character difference is almost always a typo
            if (Math.abs(good.length - domain.length) <= 1) {
                let diff = 0;
                const len = Math.max(good.length, domain.length);
                for (let i = 0, j = 0; i < len; i++, j++) {
                    if (good[i] !== domain[j]) {
                        diff++;
                        if (good.length > domain.length) j--;
                        else if (domain.length > good.length) i--;
                    }
                    if (diff > 1) break;
                }
                if (diff === 1) return value.slice(0, at + 1) + good;
            }
        }
        return null;
    }

    emailInput.addEventListener('blur', function() {
        const fixed = suggestDomain(this.value.trim());
        if (fixed) {
            emailSuggest.innerHTML = 'Did you mean <button type="button" id="emailFix"></button>?';
            $('emailFix').textContent = fixed;
            $('emailFix').addEventListener('click', function() {
                emailInput.value = fixed;
                emailSuggest.hidden = true;
                clearError(emailInput, 'email_error');
            });
            emailSuggest.hidden = false;
        } else {
            emailSuggest.hidden = true;
        }
    });

    function validateStep2() {
        // name
        const nameVal = nameInput.value.trim().replace(/\s+/g, ' ');
        nameInput.value = nameVal;

        if (nameVal.length < 2) {
            return showError(nameInput, 'full_name_error', 'Please enter your full name (at least 2 characters).');
        }
        if (!/^[A-Za-zÀ-ÿ.'\-\s]+$/.test(nameVal)) {
            return showError(nameInput, 'full_name_error', 'Use letters only — e.g. Juan Dela Cruz. Numbers and symbols are not allowed.');
        }
        clearError(nameInput, 'full_name_error');

        // age
        const ageVal = parseInt(ageInput.value, 10);
        if (ageInput.value === '' || isNaN(ageVal)) {
            return showError(ageInput, 'age_error', 'Please enter your age in years — e.g. 25.');
        }
        if (ageVal < 0 || ageVal > 120) {
            return showError(ageInput, 'age_error', 'Age must be between 0 and 120.');
        }
        clearError(ageInput, 'age_error');

        // gender
        if (!genderInput.value) {
            return showError(genderInput, 'gender_error', 'Please select a gender.');
        }
        clearError(genderInput, 'gender_error');

        // email
        const emailVal = emailInput.value.trim();
        if (!emailVal) {
            return showError(emailInput, 'email_error', 'Please enter your email address.');
        }
        if (!/^[A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,}$/.test(emailVal)) {
            return showError(emailInput, 'email_error', 'That email does not look right. Use the format name@domain.com.');
        }
        emailInput.value = emailVal;
        clearError(emailInput, 'email_error');

        // phone
        const c = country();
        const digits = nationalDigits(phoneNat.value, c);

        if (!digits) {
            return showError(phoneField, 'phone_error', 'Please enter your mobile number — e.g. ' + c.example + '.');
        }
        if (digits.length !== c.len) {
            return showError(
                phoneField,
                'phone_error',
                'A ' + c.name + ' number needs ' + c.len + ' digits after +' + c.dial +
                '. You entered ' + digits.length + '. Example: ' + c.example + '.'
            );
        }
        if (c.iso === 'PH' && digits.charAt(0) !== '9') {
            return showError(phoneField, 'phone_error', 'Philippine mobile numbers start with 9 after the 0 — e.g. 0912 345 6789.');
        }
        clearError(phoneField, 'phone_error');

        /* What gets posted:
           - phone      → local format for PH (0912 345 6789 → 09123456789) so
                          existing server-side rules keep working; international
                          numbers post as +<dial><digits>.
           - phone_e164 → always the full international form.
           Change the PH branch below if you'd rather always store E.164. */
        const e164 = '+' + c.dial + digits;
        phoneE164.value = e164;
        phoneHidden.value = (c.iso === 'PH') ? (c.trunk + digits) : e164;

        formData.name         = nameVal;
        formData.age          = ageVal;
        formData.gender       = genderInput.value;
        formData.email        = emailVal;
        formData.phoneDisplay = '+' + c.dial + ' ' + formatPhone(digits, c).replace(/^0/, '');
        return true;
    }

    $('step2Next').addEventListener('click', function() {
        if (validateStep2()) updateStep(3);
    });

    // =========================================================
    // STEP 3 — services
    // =========================================================
    const labContainer  = $('labServicesContainer');
    const xrayContainer = $('xrayServicesContainer');
    const serviceCards  = document.querySelectorAll('.service-type-card');

    function visibleChecked(container) {
        if (container.style.display === 'none') return [];
        return Array.prototype.slice.call(container.querySelectorAll('.service-checkbox:checked'));
    }

    function calculateTotal() {
        const lab  = visibleChecked(labContainer);
        const xray = visibleChecked(xrayContainer);
        const all  = lab.concat(xray);

        let fee = 0;
        all.forEach(function(cb) { fee += servicePrices[cb.value] || 0; });

        $('labPicked').textContent  = lab.length + ' selected';
        $('xrayPicked').textContent = xray.length + ' selected';

        $('pickedSummaryCount').textContent = all.length + (all.length === 1 ? ' service selected' : ' services selected');
        $('pickedSummaryTotal').textContent = peso(fee);

        $('serviceFeeDisplay').textContent  = peso(fee);
        $('totalAmountDisplay').textContent = peso(fee + CONSULTATION_FEE);

        formData.serviceFee  = fee;
        formData.totalAmount = fee + CONSULTATION_FEE;
        formData.labServices  = lab.map(function(cb) { return cb.value; });
        formData.xrayServices = xray.map(function(cb) { return cb.value; });

        return all;
    }

    function selectServiceType(card) {
        serviceCards.forEach(function(c) {
            c.classList.remove('active');
            c.setAttribute('aria-pressed', 'false');
        });
        card.classList.add('active');
        card.setAttribute('aria-pressed', 'true');

        const type = card.dataset.type;
        labContainer.style.display  = (type === 'xray') ? 'none' : 'block';
        xrayContainer.style.display = (type === 'laboratory') ? 'none' : 'block';

        // Uncheck anything that just became hidden
        if (type === 'laboratory') {
            xrayContainer.querySelectorAll('.service-checkbox').forEach(function(cb) { cb.checked = false; });
        } else if (type === 'xray') {
            labContainer.querySelectorAll('.service-checkbox').forEach(function(cb) { cb.checked = false; });
        }

        formData.serviceType = card.querySelector('.title').textContent.trim();
        calculateTotal();
        clearError(null, 'services_error');
    }

    serviceCards.forEach(function(card) {
        card.addEventListener('click', function() { selectServiceType(card); });
        card.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                selectServiceType(card);
            }
        });
    });

    document.querySelectorAll('.service-checkbox').forEach(function(cb) {
        cb.addEventListener('change', function() {
            calculateTotal();
            clearError(null, 'services_error');
        });
    });

    // character counter
    const otherInput = $('other_requests');
    const otherCount = $('otherCount');

    function updateCount() {
        const len = otherInput.value.length;
        otherCount.textContent = len + ' / 500';
        otherCount.classList.toggle('near-limit', len > 450);
    }

    otherInput.addEventListener('input', updateCount);
    updateCount();

    function validateStep3() {
        const all = calculateTotal();

        if (all.length === 0) {
            const box = $('services_error');
            box.textContent = 'Please select at least one test or service below.';
            box.classList.add('show');
            box.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return false;
        }
        clearError(null, 'services_error');

        formData.others  = otherInput.value.trim() || 'None';
        formData.services = all.map(function(cb) { return cb.value; }).join(', ');

        const active = document.querySelector('.service-type-card.active');
        formData.serviceType = active ? active.querySelector('.title').textContent.trim() : 'None';
        return true;
    }

    $('step3Next').addEventListener('click', function() {
        if (!validateStep3()) return;

        $('reviewDateTime').textContent   = formData.dateDisplay + ' at ' + formData.timeDisplay;
        $('reviewPatient').textContent    = formData.name + ' · ' + formData.age + ' yrs · ' + formData.gender;
        $('reviewContact').textContent    = formData.email + ' · ' + formData.phoneDisplay;
        $('reviewServiceType').textContent = formData.serviceType;
        $('reviewServices').textContent   = formData.services;
        $('reviewOthers').textContent     = formData.others;

        $('paymentAgree').checked = false;
        $('confirmBooking').disabled = true;
        clearError(null, 'agree_error');

        updateStep(4);
    });

    // =========================================================
    // BACK + EDIT
    // =========================================================
    $('step2Prev').addEventListener('click', function() { updateStep(1); });
    $('step3Prev').addEventListener('click', function() { updateStep(2); });
    $('step4Prev').addEventListener('click', function() { updateStep(3); });

    document.querySelectorAll('.review-edit').forEach(function(btn) {
        btn.addEventListener('click', function() {
            updateStep(parseInt(btn.dataset.goto, 10));
        });
    });

    // =========================================================
    // STEP 4 — confirm
    // =========================================================
    $('paymentAgree').addEventListener('change', function() {
        $('confirmBooking').disabled = !this.checked;
        if (this.checked) clearError(null, 'agree_error');
    });

    let submitted = false;

    $('confirmBooking').addEventListener('click', function() {
        if (submitted) return;

        if (!validateStep1()) { updateStep(1); return; }
        if (!validateStep2()) { updateStep(2); return; }
        if (!validateStep3()) { updateStep(3); return; }

        if (!$('paymentAgree').checked) {
            const box = $('agree_error');
            box.textContent = 'Please tick the box to confirm you agree to pay on arrival.';
            box.classList.add('show');
            return;
        }

        submitted = true;
        this.classList.add('is-loading');
        this.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Submitting...';

        $('bookingForm').submit();
    });

    // Restore the button if the user comes back with the Back button
    window.addEventListener('pageshow', function(e) {
        if (e.persisted) {
            submitted = false;
            const btn = $('confirmBooking');
            btn.classList.remove('is-loading');
            btn.innerHTML = '<i class="bi bi-shield-check me-2"></i>Confirm Booking';
            btn.disabled = !$('paymentAgree').checked;
        }
    });

    // =========================================================
    // CLEAR ERRORS AS THE USER TYPES
    // =========================================================
    [
        [nameInput,   'full_name_error'],
        [ageInput,    'age_error'],
        [genderInput, 'gender_error'],
        [emailInput,  'email_error'],
        [timeInput,   'appointment_time_error']
    ].forEach(function(pair) {
        pair[0].addEventListener('input',  function() { clearError(pair[0], pair[1]); });
        pair[0].addEventListener('change', function() { clearError(pair[0], pair[1]); });
    });

    calculateTotal();
});
</script>

<?= $this->endSection() ?>