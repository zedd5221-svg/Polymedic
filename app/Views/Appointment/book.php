<?= $this->extend('Layouts/AppointmentLayout') ?>

<?= $this->section('AppointmentContent') ?>

<link href="<?= base_url('assets/css/patient-booking.css') ?>" rel="stylesheet">

<?php
    // Country dialling options for the phone field.
    // 'len' is the number of digits expected AFTER the dial code.
    // 'groups' controls the live display formatting.
    $phoneCountries = [
        ['iso' => 'PH', 'name' => 'Philippines',          'dial' => '63',  'len' => 10, 'groups' => [3, 3, 4], 'example' => '0912 345 6789', 'trunk' => '0'],
        ['iso' => 'US', 'name' => 'United States',        'dial' => '1',   'len' => 10, 'groups' => [3, 3, 4], 'example' => '415 555 0132',  'trunk' => ''],
        ['iso' => 'CA', 'name' => 'Canada',               'dial' => '1',   'len' => 10, 'groups' => [3, 3, 4], 'example' => '416 555 0132',  'trunk' => ''],
        ['iso' => 'AU', 'name' => 'Australia',            'dial' => '61',  'len' => 9,  'groups' => [3, 3, 3], 'example' => '0412 345 678',  'trunk' => '0'],
        ['iso' => 'GB', 'name' => 'United Kingdom',       'dial' => '44',  'len' => 10, 'groups' => [4, 6],    'example' => '07400 123456',  'trunk' => '0'],
        ['iso' => 'SG', 'name' => 'Singapore',            'dial' => '65',  'len' => 8,  'groups' => [4, 4],    'example' => '8123 4567',     'trunk' => ''],
        ['iso' => 'HK', 'name' => 'Hong Kong',            'dial' => '852', 'len' => 8,  'groups' => [4, 4],    'example' => '5123 4567',     'trunk' => ''],
        ['iso' => 'JP', 'name' => 'Japan',                'dial' => '81',  'len' => 10, 'groups' => [2, 4, 4], 'example' => '090 1234 5678', 'trunk' => '0'],
        ['iso' => 'KR', 'name' => 'South Korea',          'dial' => '82',  'len' => 10, 'groups' => [2, 4, 4], 'example' => '010 1234 5678', 'trunk' => '0'],
        ['iso' => 'MY', 'name' => 'Malaysia',             'dial' => '60',  'len' => 9,  'groups' => [2, 3, 4], 'example' => '012 345 6789',  'trunk' => '0'],
        ['iso' => 'ID', 'name' => 'Indonesia',            'dial' => '62',  'len' => 10, 'groups' => [3, 4, 3], 'example' => '0812 3456 789', 'trunk' => '0'],
        ['iso' => 'AE', 'name' => 'United Arab Emirates', 'dial' => '971', 'len' => 9,  'groups' => [2, 3, 4], 'example' => '050 123 4567',  'trunk' => '0'],
        ['iso' => 'SA', 'name' => 'Saudi Arabia',         'dial' => '966', 'len' => 9,  'groups' => [2, 3, 4], 'example' => '050 123 4567',  'trunk' => '0'],
        ['iso' => 'QA', 'name' => 'Qatar',                'dial' => '974', 'len' => 8,  'groups' => [4, 4],    'example' => '3312 3456',     'trunk' => ''],
        ['iso' => 'KW', 'name' => 'Kuwait',               'dial' => '965', 'len' => 8,  'groups' => [4, 4],    'example' => '5012 3456',     'trunk' => ''],
        ['iso' => 'IT', 'name' => 'Italy',                'dial' => '39',  'len' => 10, 'groups' => [3, 3, 4], 'example' => '312 345 6789',  'trunk' => ''],
        ['iso' => 'DE', 'name' => 'Germany',              'dial' => '49',  'len' => 10, 'groups' => [3, 3, 4], 'example' => '0151 234 5678', 'trunk' => '0'],
    ];
    $oldCountry = old('phone_country') ?: 'PH';

    $consultationFee = 500.00;

    // Scheduling rules come from clinic_settings.
    $horizonDays = (int) ($scheduling['horizon'] ?? 90);
    $fmtTime     = static fn ($t) => date('g:i A', strtotime((string) $t));
    $hoursText   = $fmtTime($scheduling['open'] ?? '08:00') . ' – ' . $fmtTime($scheduling['close'] ?? '17:00');
    $lunchText   = $fmtTime($scheduling['lunch_start'] ?? '12:00') . ' – ' . $fmtTime($scheduling['lunch_end'] ?? '13:00');

    // Only active services are bookable.
    $active   = static fn ($rows) => array_values(array_filter((array) $rows, static fn ($s) => (int) ($s['is_active'] ?? 0) === 1));
    $lists    = ['lab' => $active($labServices ?? []), 'xray' => $active($xrayServices ?? [])];
    foreach ($lists as &$rowsRef) {
        usort($rowsRef, static fn ($a, $b) => strcasecmp((string) $a['service_name'], (string) $b['service_name']));
    }
    unset($rowsRef);
    $oldPicks = [
        'lab'  => (array) (old('lab_services') ?? []),
        'xray' => (array) (old('xray_services') ?? []),
    ];

    $flashErrors = session()->getFlashdata('errors');
    $flashError  = session()->getFlashdata('error');

    $config = [
        'today'      => date('Y-m-d'),
        'max'        => date('Y-m-d', strtotime('+' . $horizonDays . ' days')),
        'closedDays' => array_map('intval', (array) ($scheduling['closed_days'] ?? [])),
        'holidays'   => array_values((array) ($scheduling['holidays'] ?? [])),
        'slotsUrl'   => site_url('appointment/slots'),
        'fee'        => $consultationFee,
        'oldDate'    => (string) (old('appointment_date') ?? ''),
        'oldTime'    => (string) (old('appointment_time') ?? ''),
        'hasErrors'  => ! empty($flashErrors) || ! empty($flashError),
        'slotTaken'  => is_string($flashError) && stripos($flashError, 'slot') !== false,
    ];

    $icon = [
        'x'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>',
        'left'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>',
        'right'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>',
        'check'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12.5 10 17l9-10"/></svg>',
        'search' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>',
        'flask'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 3h6M10 3v6L4.5 18.5A2 2 0 0 0 6.2 21.5h11.6a2 2 0 0 0 1.7-3L14 9V3"/><path d="M7 15h10"/></svg>',
        'scan'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 8V5a1 1 0 0 1 1-1h3M16 4h3a1 1 0 0 1 1 1v3M20 16v3a1 1 0 0 1-1 1h-3M8 20H5a1 1 0 0 1-1-1v-3"/><path d="M12 8v8M9 10h6M9 14h6"/></svg>',
        'clock'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>',
        'info'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/></svg>',
        'alert'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v6M12 16.5h.01"/></svg>',
        'pin'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 21s-7-6.2-7-11.5A7 7 0 0 1 19 9.5C19 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/></svg>',
        'wallet' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="6" width="18" height="13" rx="2"/><path d="M3 10h18M16 15h2"/></svg>',
        'cal'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/></svg>',
    ];

    $steps = [1 => 'Tests', 2 => 'Date & time', 3 => 'Your details', 4 => 'Review'];
    $stepIcon = [
        1 => $icon['flask'],
        2 => $icon['cal'],
        3 => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>',
        4 => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4V3h6v1M9 11h6M9 15h4"/></svg>',
    ];
    $peso  = static fn ($n) => '₱' . number_format((float) $n, 2);
?>

<div class="bk" id="bk">

    <!-- ===== Top bar + progress ===== -->
    <header class="bk-top is-dark">
        <div class="bk-wrap bk-top-inner">
            <a class="bk-brand" href="<?= base_url() ?>">
                <img src="<?= base_url('assets/images/logo4.png') ?>" alt="">
                <span>
                    <b>PolyMedic</b>
                    <small>Book an appointment</small>
                </span>
            </a>
            <a class="bk-exit" href="<?= base_url() ?>"><?= $icon['x'] ?><span>Cancel booking</span></a>
        </div>
    </header>

    <!-- ===== Brand band: progress + current step heading ===== -->
    <section class="bk-hero">
        <div class="bk-wrap">
            <ol class="bk-progress" id="bkProgress" aria-label="Booking steps">
                <?php foreach ($steps as $n => $label): ?>
                    <li data-step="<?= $n ?>" class="<?= $n === 1 ? 'is-current' : '' ?>">
                        <button type="button" data-goto="<?= $n ?>" <?= $n === 1 ? 'aria-current="step"' : 'disabled' ?>>
                            <span class="bk-step-rail" aria-hidden="true">
                                <span class="bk-step-node">
                                    <span class="bk-step-glyph">
                                        <span class="bk-step-ic"><?= $stepIcon[$n] ?></span>
                                        <svg class="bk-step-ck" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>
                                    </span>
                                    <span class="bk-step-mlabel"><?= esc($label) ?></span>
                                </span>
                                <?php if ($n < count($steps)): ?><span class="bk-step-link"><i></i></span><?php endif; ?>
                            </span>
                            <span class="bk-step-meta">
                                <span class="bk-step-kick">Step <?= $n ?></span>
                                <span class="bk-step-name"><?= esc($label) ?></span>
                                <span class="bk-step-pill" data-pill><?= $n === 1 ? 'In progress' : 'Pending' ?></span>
                            </span>
                            <span class="bk-sr"> — step <?= $n ?> of 4</span>
                        </button>
                    </li>
                <?php endforeach; ?>
            </ol>
            <div class="bk-hero-text">
                <p class="bk-kicker" id="bkKicker">Step 1 of 4</p>
                <h1 id="bkTitle" tabindex="-1">What tests do you need?</h1>
                <p id="bkLead">Pick everything on your doctor's request. You can choose from both lists.</p>
            </div>
        </div>
    </section>

    <main class="bk-wrap bk-main">
        <div class="bk-grid">

            <form class="bk-card bk-form" action="<?= site_url('appointment/submit') ?>" method="POST" id="bookingForm" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="appointment_date" id="appointment_date" value="<?= esc($config['oldDate'], 'attr') ?>">
                <input type="hidden" name="appointment_time" id="appointment_time" value="<?= esc($config['slotTaken'] ? '' : $config['oldTime'], 'attr') ?>">

                <?php if (! empty($flashErrors) || ! empty($flashError)): ?>
                    <div class="bk-alert" role="alert">
                        <?= $icon['alert'] ?>
                        <div>
                            <?php if (! empty($flashError)): ?>
                                <strong><?= esc($flashError) ?></strong>
                            <?php endif; ?>
                            <?php if (! empty($flashErrors)): ?>
                                <strong>Some details need a second look.</strong>
                                <ul>
                                    <?php foreach ((array) $flashErrors as $error): ?>
                                        <li><?= esc($error) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- =========================================================
                     STEP 1 — Tests
                     ========================================================= -->
                <section class="bk-step is-active" data-step="1" aria-labelledby="bkTitle"
                         data-title="What tests do you need?"
                         data-lead="Pick everything on your doctor's request. You can choose from both lists.">

                    <p class="bk-error is-block" id="services_error" role="alert"></p>

                    <div class="bk-picked" id="bkPicked" hidden>
                        <div class="bk-picked-head">
                            <strong id="bkPickedCount">0 selected</strong>
                            <button type="button" class="bk-link" id="bkClearAll">Clear all</button>
                        </div>
                        <div class="bk-chips" id="bkChips"></div>
                    </div>

                    <div class="bk-toolbar">
                        <div class="bk-tabs" role="tablist" aria-label="Test type">
                            <button type="button" class="bk-tab" role="tab" id="tab-lab" aria-controls="list-lab" aria-selected="true">
                                <?= $icon['flask'] ?>Laboratory
                                <span class="bk-tab-count"><?= count($lists['lab']) ?></span>
                                <span class="bk-tab-pill" data-picked="lab" hidden>0</span>
                            </button>
                            <button type="button" class="bk-tab" role="tab" id="tab-xray" aria-controls="list-xray" aria-selected="false" tabindex="-1">
                                <?= $icon['scan'] ?>X-ray
                                <span class="bk-tab-count"><?= count($lists['xray']) ?></span>
                                <span class="bk-tab-pill" data-picked="xray" hidden>0</span>
                            </button>
                        </div>
                        <div class="bk-search">
                            <label for="bkSearch" class="bk-sr">Search tests</label>
                            <?= $icon['search'] ?>
                            <input type="search" id="bkSearch" placeholder="Search, e.g. CBC or chest" autocomplete="off" enterkeyhint="search">
                        </div>
                    </div>

                    <?php foreach ($lists as $dept => $rows): ?>
                        <div class="bk-svc-list" id="list-<?= $dept ?>" role="tabpanel" aria-labelledby="tab-<?= $dept ?>" data-dept="<?= $dept ?>" <?= $dept === 'lab' ? '' : 'hidden' ?>>
                            <?php $letter = null; foreach ($rows as $service):
                                $name    = (string) $service['service_name'];
                                $id      = $dept . '_' . (int) $service['id'];
                                $checked = in_array($name, $oldPicks[$dept], true);
                                $first   = strtoupper(substr(ltrim($name), 0, 1));
                                $first   = ctype_alpha($first) ? $first : '#';
                                if ($first !== $letter): $letter = $first; ?>
                                    <div class="bk-letter" data-letter="<?= esc($letter, 'attr') ?>" aria-hidden="true"><span><?= esc($letter) ?></span></div>
                                <?php endif; ?>
                                <label data-letter="<?= esc($letter, 'attr') ?>" class="bk-svc<?= $checked ? ' is-on' : '' ?>" for="<?= $id ?>">
                                    <input type="checkbox" class="bk-svc-input"
                                           name="<?= $dept === 'lab' ? 'lab_services[]' : 'xray_services[]' ?>"
                                           value="<?= esc($name, 'attr') ?>"
                                           id="<?= $id ?>"
                                           data-dept="<?= $dept ?>"
                                           data-price="<?= esc((string) (float) $service['charge'], 'attr') ?>"
                                           <?= $checked ? 'checked' : '' ?>>
                                    <span class="bk-svc-box"><?= $icon['check'] ?></span>
                                    <span class="bk-svc-name" data-name="<?= esc($name, 'attr') ?>"><?= esc($name) ?></span>
                                    <span class="bk-svc-price"><?= $peso($service['charge']) ?></span>
                                </label>
                            <?php endforeach; ?>

                            <div class="bk-empty" data-empty <?= $rows ? 'hidden' : '' ?>>
                                <?php if ($rows): ?>
                                    <strong>No matching tests</strong>
                                    <span>Check the spelling or try a shorter word.</span>
                                    <br><button type="button" class="bk-btn bk-btn-outline" data-switch hidden></button>
                                <?php else: ?>
                                    <strong>No <?= $dept === 'lab' ? 'laboratory tests' : 'X-ray services' ?> available online right now</strong>
                                    <span>Please ask our front desk on your visit.</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <p class="bk-note">
                        <?= $icon['info'] ?>
                        <span>Test not listed? Book what you can and bring your doctor's request. Our staff will confirm on your visit.</span>
                    </p>
                </section>

                <!-- =========================================================
                     STEP 2 — Date & time
                     ========================================================= -->
                <section class="bk-step" data-step="2" aria-labelledby="bkTitle"
                         data-title="When would you like to come in?"
                         data-lead="<?= esc('Open ' . $hoursText . ', closed for lunch ' . $lunchText . '. You can book up to ' . $horizonDays . ' days ahead.', 'attr') ?>">

                    <div class="bk-when">
                        <div>
                            <div class="bk-cal" id="bkCal">
                                <div class="bk-cal-head">
                                    <h2 id="bkCalTitle" aria-live="polite">Month</h2>
                                    <div class="bk-cal-nav">
                                        <button type="button" class="bk-icon-btn" id="bkCalPrev" aria-label="Previous month"><?= $icon['left'] ?></button>
                                        <button type="button" class="bk-icon-btn" id="bkCalNext" aria-label="Next month"><?= $icon['right'] ?></button>
                                    </div>
                                </div>
                                <div class="bk-cal-dow" aria-hidden="true">
                                    <span>Su</span><span>Mo</span><span>Tu</span><span>We</span><span>Th</span><span>Fr</span><span>Sa</span>
                                </div>
                                <div class="bk-cal-grid" id="bkCalGrid" role="group" aria-labelledby="bkCalTitle"></div>
                                <p class="bk-cal-legend"><span><i></i>Today</span><span>Greyed-out days are closed or fully past</span></p>
                            </div>
                            <p class="bk-error" id="appointment_date_error" role="alert"></p>
                        </div>

                        <div>
                            <div class="bk-times-head">
                                <h2>Available times</h2>
                                <p id="bkDateLabel">Pick a date first</p>
                            </div>
                            <div id="bkSlots" aria-live="polite">
                                <div class="bk-slot-msg"><?= $icon['cal'] ?><span>Choose a day on the calendar to see open times.</span></div>
                            </div>
                            <p class="bk-error" id="appointment_time_error" role="alert"></p>
                            <p class="bk-note is-warn" id="bkTimeCleared" hidden>
                                <?= $icon['alert'] ?>
                                <span>Your earlier time is no longer open for the tests you picked. Please choose another.</span>
                            </p>
                        </div>
                    </div>
                </section>

                <!-- =========================================================
                     STEP 3 — Patient details
                     ========================================================= -->
                <section class="bk-step" data-step="3" aria-labelledby="bkTitle"
                         data-title="Who is the appointment for?"
                         data-lead="Enter the patient's details as they appear on a valid ID.">

                    <div class="bk-fields">
                        <div class="bk-field">
                            <label class="bk-label" for="full_name">Full name</label>
                            <input type="text" class="bk-input" name="full_name" id="full_name"
                                   value="<?= esc(old('full_name') ?? '', 'attr') ?>"
                                   placeholder="Juan Dela Cruz" maxlength="100"
                                   autocomplete="name" autocapitalize="words"
                                   aria-describedby="full_name_error" required>
                            <p class="bk-error" id="full_name_error" role="alert"></p>
                        </div>

                        <div class="bk-field third">
                            <label class="bk-label" for="age">Age</label>
                            <input type="number" class="bk-input" name="age" id="age"
                                   value="<?= esc(old('age') ?? '', 'attr') ?>"
                                   placeholder="25" min="1" max="120" step="1" inputmode="numeric"
                                   aria-describedby="age_hint age_error" required>
                            <p class="bk-hint" id="age_hint">In years</p>
                            <p class="bk-error" id="age_error" role="alert"></p>
                        </div>

                        <div class="bk-field two-thirds">
                            <span class="bk-label" id="gender_label">Sex</span>
                            <div class="bk-seg" id="gender_group" role="radiogroup" aria-labelledby="gender_label" aria-describedby="gender_hint gender_error">
                                <?php foreach (['Male', 'Female', 'Other'] as $g): ?>
                                    <label>
                                        <input type="radio" name="gender" value="<?= $g ?>" <?= old('gender') === $g ? 'checked' : '' ?>>
                                        <span><?= $g ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            <p class="bk-hint" id="gender_hint">Used for lab reference ranges.</p>
                            <p class="bk-error" id="gender_error" role="alert"></p>
                        </div>

                        <div class="bk-field half">
                            <label class="bk-label" for="email">Email</label>
                            <input type="email" class="bk-input" name="email" id="email"
                                   value="<?= esc(old('email') ?? '', 'attr') ?>"
                                   placeholder="juan@gmail.com" maxlength="100"
                                   autocomplete="email" autocapitalize="none" spellcheck="false" inputmode="email"
                                   aria-describedby="email_hint email_error" required>
                            <p class="bk-hint" id="email_hint">We'll send your booking details here.</p>
                            <p class="bk-suggest" id="emailSuggest" hidden></p>
                            <p class="bk-error" id="email_error" role="alert"></p>
                        </div>

                        <div class="bk-field half">
                            <label class="bk-label" for="phone_national">Mobile number</label>
                            <div class="bk-phone" id="phoneField">
                                <select name="phone_country" id="phone_country" aria-label="Country code">
                                    <?php foreach ($phoneCountries as $c): ?>
                                        <option value="<?= esc($c['iso'], 'attr') ?>"
                                                data-dial="<?= esc($c['dial'], 'attr') ?>"
                                                data-len="<?= esc((string) $c['len'], 'attr') ?>"
                                                data-groups="<?= esc(implode(',', $c['groups']), 'attr') ?>"
                                                data-example="<?= esc($c['example'], 'attr') ?>"
                                                data-trunk="<?= esc($c['trunk'], 'attr') ?>"
                                                data-name="<?= esc($c['name'], 'attr') ?>"
                                                <?= $oldCountry === $c['iso'] ? 'selected' : '' ?>>
                                            <?= esc($c['iso']) ?> +<?= esc($c['dial']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <span class="dial" id="phoneDial" aria-hidden="true">+63</span>
                                <input type="tel" id="phone_national"
                                       value="<?= esc(old('phone_national') ?? '', 'attr') ?>"
                                       placeholder="0912 345 6789" inputmode="tel" autocomplete="tel-national"
                                       aria-describedby="phone_hint phone_error" required>
                            </div>
                            <input type="hidden" name="phone" id="phone">
                            <input type="hidden" name="phone_e164" id="phone_e164">
                            <input type="hidden" name="phone_national" id="phone_national_post">
                            <p class="bk-hint" id="phone_hint">e.g. <span id="phoneExample">0912 345 6789</span></p>
                            <p class="bk-error" id="phone_error" role="alert"></p>
                        </div>

                        <div class="bk-field">
                            <label class="bk-label" for="other_requests">Anything we should know? <span class="opt">Optional</span></label>
                            <textarea class="bk-textarea" name="other_requests" id="other_requests" rows="3" maxlength="500"
                                      placeholder="e.g. I'm fasting for a blood test, I use a wheelchair, or I need a medical certificate."
                                      aria-describedby="otherCount"><?= esc(old('other_requests') ?? '') ?></textarea>
                            <div class="bk-hint-row">
                                <p class="bk-hint">Fasting, mobility needs or documents you need.</p>
                                <span class="bk-count" id="otherCount">0 / 500</span>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- =========================================================
                     STEP 4 — Review
                     ========================================================= -->
                <section class="bk-step" data-step="4" aria-labelledby="bkTitle"
                         data-title="Check your booking"
                         data-lead="Make sure everything is right before you confirm.">

                    <dl class="bk-review">
                        <div class="bk-review-row">
                            <dt>Date &amp; time</dt>
                            <dd id="rvWhen">—</dd>
                            <button type="button" class="bk-edit" data-goto="2">Edit<span class="bk-sr"> date and time</span></button>
                        </div>
                        <div class="bk-review-row">
                            <dt>Patient</dt>
                            <dd id="rvPatient">—</dd>
                            <button type="button" class="bk-edit" data-goto="3">Edit<span class="bk-sr"> patient</span></button>
                        </div>
                        <div class="bk-review-row">
                            <dt>Contact</dt>
                            <dd id="rvContact">—</dd>
                            <button type="button" class="bk-edit" data-goto="3">Edit<span class="bk-sr"> contact</span></button>
                        </div>
                        <div class="bk-review-row">
                            <dt>Tests</dt>
                            <dd id="rvTests">—</dd>
                            <button type="button" class="bk-edit" data-goto="1">Edit<span class="bk-sr"> tests</span></button>
                        </div>
                        <div class="bk-review-row" id="rvNotesRow" hidden>
                            <dt>Notes</dt>
                            <dd id="rvNotes"></dd>
                            <button type="button" class="bk-edit" data-goto="3">Edit<span class="bk-sr"> notes</span></button>
                        </div>
                    </dl>

                    <div class="bk-bill">
                        <div class="bk-bill-row"><span id="rvTestsLabel">Tests</span><span id="rvTestsFee">₱0.00</span></div>
                        <div class="bk-bill-row"><span>Consultation fee</span><span><?= $peso($consultationFee) ?></span></div>
                        <div class="bk-bill-row bk-bill-total"><span>Total to pay at the clinic</span><span id="rvTotal"><?= $peso($consultationFee) ?></span></div>
                        <p>Nothing to pay online. Settle the total at the counter on the day of your visit.</p>
                    </div>

                    <label class="bk-agree" for="paymentAgree">
                        <input type="checkbox" id="paymentAgree">
                        <span>I'll pay the total at the clinic on the day of my visit.</span>
                    </label>
                    <p class="bk-error" id="agree_error" role="alert"></p>
                </section>

                <!-- ===== Actions (fixed thumb bar on phones) ===== -->
                <div class="bk-actions" id="bkActions">
                    <button type="button" class="bk-btn bk-btn-outline bk-back" id="bkBack" aria-label="Back" hidden>
                        <?= $icon['left'] ?><span>Back</span>
                    </button>
                    <div class="bk-bar-sum" aria-live="polite">
                        <small id="bkBarInfo">No tests yet</small>
                        <strong id="bkBarTotal"><?= $peso($consultationFee) ?></strong>
                    </div>
                    <button type="button" class="bk-btn bk-btn-primary bk-next" id="bkNext">
                        <span id="bkNextLabel">Continue</span><?= $icon['right'] ?>
                    </button>
                </div>
            </form>

            <!-- ===== Summary (tablet landscape and up) ===== -->
            <aside class="bk-summary" aria-label="Booking summary">
                <div class="bk-card bk-sum">
                    <h2><?= $icon['cal'] ?>Your visit</h2>
                    <div class="bk-sum-block">
                        <span class="bk-sum-label">Tests</span>
                        <ul class="bk-sum-list" id="sumTests"><li class="is-empty">None selected yet</li></ul>
                    </div>
                    <div class="bk-sum-block">
                        <span class="bk-sum-label">Date &amp; time</span>
                        <p id="sumWhen" class="is-empty">Not chosen yet</p>
                    </div>
                    <div class="bk-sum-block" id="sumWhoBlock" hidden>
                        <span class="bk-sum-label">Patient</span>
                        <p id="sumWho"></p>
                    </div>
                    <div class="bk-sum-foot">
                        <div><?= $icon['wallet'] ?><span>Pay at the clinic. No online payment.</span></div>
                        <div><?= $icon['pin'] ?><span>Governor Gutierrez Ave, Cotabato City 9600</span></div>
                    </div>
                    <div class="bk-sum-bill">
                        <div class="bk-bill-row"><span id="sumTestsLabel">Tests</span><span id="sumTestsFee">₱0.00</span></div>
                        <div class="bk-bill-row"><span>Consultation fee</span><span><?= $peso($consultationFee) ?></span></div>
                        <div class="bk-bill-row bk-bill-total"><span>Total</span><span id="sumTotal"><?= $peso($consultationFee) ?></span></div>
                    </div>
                </div>
            </aside>
        </div>
    </main>
</div>

<script>
(function () {
    'use strict';

    var CFG = <?= json_encode($config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    var TOTAL_STEPS = 4;
    var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    var $  = function (id) { return document.getElementById(id); };
    var $$ = function (sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); };

    var peso = function (n) {
        return '₱' + Number(n).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    };
    var escapeHtml = function (s) {
        return String(s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    };

    var form       = $('bookingForm');
    var dateInput  = $('appointment_date');
    var timeInput  = $('appointment_time');
    var state = {
        step: 1,
        reached: 1,
        timeLabel: '',
        slots: null,       // last slot list for the chosen date/type, null while unknown
        slotsKey: '',
        loading: false
    };

    /* =========================================================
       Errors
       ========================================================= */
    function showError(field, errorId, message, focus) {
        var box = $(errorId);
        if (box) { box.textContent = message; box.classList.add('show'); }
        if (field) {
            field.classList.add('is-invalid');
            field.setAttribute('aria-invalid', 'true');
            if (focus !== false && field.focus) field.focus();
        }
        return false;
    }
    function clearError(field, errorId) {
        var box = $(errorId);
        if (box) { box.textContent = ''; box.classList.remove('show'); }
        if (field) { field.classList.remove('is-invalid'); field.removeAttribute('aria-invalid'); }
        return true;
    }

    /* =========================================================
       Step 1 — tests
       ========================================================= */
    var boxes  = $$('.bk-svc-input');
    var tabs   = $$('.bk-tab');
    var lists  = $$('.bk-svc-list');
    var search = $('bkSearch');

    function picked() {
        return boxes.filter(function (b) { return b.checked; }).map(function (b) {
            return { name: b.value, dept: b.dataset.dept, price: parseFloat(b.dataset.price) || 0 };
        });
    }
    function deptType() {
        var p = picked();
        var lab  = p.some(function (s) { return s.dept === 'lab'; });
        var xray = p.some(function (s) { return s.dept === 'xray'; });
        return lab && xray ? 'both' : (xray ? 'xray' : 'laboratory');
    }

    function selectTab(tab, focus) {
        tabs.forEach(function (t) {
            var on = t === tab;
            t.setAttribute('aria-selected', on ? 'true' : 'false');
            t.tabIndex = on ? 0 : -1;
            $(t.getAttribute('aria-controls')).hidden = !on;
        });
        if (focus) tab.focus();
    }
    tabs.forEach(function (tab, i) {
        tab.addEventListener('click', function () { selectTab(tab); });
        tab.addEventListener('keydown', function (e) {
            var next = null;
            if (e.key === 'ArrowRight') next = tabs[(i + 1) % tabs.length];
            if (e.key === 'ArrowLeft')  next = tabs[(i - 1 + tabs.length) % tabs.length];
            if (next) { e.preventDefault(); selectTab(next, true); }
        });
    });

    function filterTests() {
        var q = search.value.trim().toLowerCase();
        var totals = {};
        lists.forEach(function (list) {
            var shown = 0;
            $$('.bk-svc', list).forEach(function (row) {
                var nameEl = row.querySelector('.bk-svc-name');
                var name = nameEl.getAttribute('data-name');
                var at = name.toLowerCase().indexOf(q);
                var hit = !q || at !== -1;
                row.hidden = !hit;
                if (hit) {
                    shown++;
                    nameEl.innerHTML = q
                        ? escapeHtml(name.slice(0, at)) + '<mark>' + escapeHtml(name.slice(at, at + q.length)) + '</mark>' + escapeHtml(name.slice(at + q.length))
                        : escapeHtml(name);
                }
            });
            $$('.bk-letter', list).forEach(function (l) {
                l.hidden = !list.querySelector('.bk-svc[data-letter="' + l.dataset.letter + '"]:not([hidden])');
            });
            totals[list.dataset.dept] = shown;
        });
        lists.forEach(function (list) {
            var dept = list.dataset.dept;
            var rows = $$('.bk-svc', list).length;
            var empty = list.querySelector('[data-empty]');
            var sw = list.querySelector('[data-switch]');
            if (!rows) return; // "none available" message stays as rendered
            empty.hidden = totals[dept] !== 0;
            var other = dept === 'lab' ? 'xray' : 'lab';
            if (sw) {
                sw.hidden = !totals[other];
                sw.textContent = 'See ' + totals[other] + ' ' + (totals[other] === 1 ? 'match' : 'matches') + ' in ' + (other === 'lab' ? 'Laboratory' : 'X-ray');
                sw.onclick = function () { selectTab($('tab-' + other), true); };
            }
        });
    }
    search.addEventListener('input', filterTests);
    search.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') { search.value = ''; filterTests(); }
        if (e.key === 'Enter') { e.preventDefault(); search.blur(); }
    });

    boxes.forEach(function (b) {
        b.addEventListener('change', function () {
            b.closest('.bk-svc').classList.toggle('is-on', b.checked);
            clearError(null, 'services_error');
            refreshSummary();
            // Capacity depends on the department, so open times may change.
            if (dateInput.value && state.reached >= 2) loadSlots();
        });
    });

    function uncheck(b) {
        b.checked = false;
        b.dispatchEvent(new Event('change'));
    }
    $('bkChips').addEventListener('click', function (e) {
        var chip = e.target.closest('.bk-chip');
        if (!chip) return;
        var box = boxes.filter(function (b) { return b.value === chip.dataset.remove && b.dataset.dept === chip.dataset.dept; })[0];
        if (box) uncheck(box);
        var nextChip = $('bkChips').querySelector('.bk-chip');
        (nextChip || search).focus();
    });
    $('bkClearAll').addEventListener('click', function () {
        boxes.filter(function (b) { return b.checked; }).forEach(uncheck);
        search.focus();
    });

    function validateStep1(quiet) {
        if (!picked().length) {
            if (!quiet) {
                showError(null, 'services_error', 'Pick at least one test to continue.');
                $('services_error').scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'center' });
            }
            return false;
        }
        clearError(null, 'services_error');
        return true;
    }

    /* =========================================================
       Step 2 — calendar
       ========================================================= */
    var grid   = $('bkCalGrid');
    var title  = $('bkCalTitle');
    var prev   = $('bkCalPrev');
    var next   = $('bkCalNext');
    var closed = CFG.closedDays || [];
    var hol    = CFG.holidays || [];

    function parseYmd(s) { var p = s.split('-'); return new Date(+p[0], +p[1] - 1, +p[2]); }
    function ymd(d) {
        return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    }
    var today = parseYmd(CFG.today);
    var max   = parseYmd(CFG.max);

    function dayStatus(d) {
        var s = ymd(d);
        if (s < CFG.today || s > CFG.max) return 'out';
        if (closed.indexOf(d.getDay()) !== -1 || hol.indexOf(s) !== -1) return 'closed';
        return 'open';
    }
    function firstOpenFrom(d) {
        var cur = new Date(d);
        while (ymd(cur) <= CFG.max) { if (dayStatus(cur) === 'open') return cur; cur.setDate(cur.getDate() + 1); }
        return null;
    }

    var view = dateInput.value ? parseYmd(dateInput.value) : (firstOpenFrom(today) || today);
    view = new Date(view.getFullYear(), view.getMonth(), 1);

    function longDate(s) {
        return parseYmd(s).toLocaleDateString('en-PH', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' });
    }
    function shortDate(s) {
        return parseYmd(s).toLocaleDateString('en-PH', { weekday: 'short', month: 'short', day: 'numeric' });
    }

    function renderCalendar(focusYmd) {
        var y = view.getFullYear(), m = view.getMonth();
        title.textContent = view.toLocaleDateString('en-PH', { month: 'long', year: 'numeric' });
        prev.disabled = y === today.getFullYear() && m === today.getMonth();
        next.disabled = y === max.getFullYear() && m === max.getMonth();

        var html = '';
        var lead = new Date(y, m, 1).getDay();
        var days = new Date(y, m + 1, 0).getDate();
        for (var i = 0; i < lead; i++) html += '<span class="bk-day-blank" aria-hidden="true"></span>';

        var openDays = [];
        for (var d = 1; d <= days; d++) {
            var date = new Date(y, m, d);
            var s = ymd(date);
            var st = dayStatus(date);
            var cls = 'bk-day' + (s === CFG.today ? ' is-today' : '') + (s === dateInput.value ? ' is-picked' : '') + (st === 'closed' ? ' is-closed' : '');
            var label = date.toLocaleDateString('en-PH', { weekday: 'long', month: 'long', day: 'numeric' })
                + (st === 'closed' ? ', clinic closed' : '') + (s === CFG.today ? ', today' : '');
            html += '<button type="button" class="' + cls + '" data-date="' + s + '" aria-label="' + label + '"'
                + (s === dateInput.value ? ' aria-pressed="true"' : ' aria-pressed="false"')
                + (st !== 'open' ? ' disabled' : '') + ' tabindex="-1">' + d + '</button>';
            if (st === 'open') openDays.push(s);
        }
        grid.innerHTML = html;

        // Roving tabindex: one day in the grid is reachable with Tab.
        var target = [focusYmd, dateInput.value, CFG.today].filter(function (s) {
            return s && openDays.indexOf(s) !== -1;
        })[0] || openDays[0];
        var tb = target ? grid.querySelector('[data-date="' + target + '"]') : null;
        if (tb) {
            tb.tabIndex = 0;
            if (focusYmd) tb.focus();
        }
    }

    prev.addEventListener('click', function () { view.setMonth(view.getMonth() - 1); renderCalendar(); });
    next.addEventListener('click', function () { view.setMonth(view.getMonth() + 1); renderCalendar(); });

    grid.addEventListener('click', function (e) {
        var btn = e.target.closest('.bk-day');
        if (!btn || btn.disabled) return;
        pickDate(btn.dataset.date);
    });

    grid.addEventListener('keydown', function (e) {
        var btn = e.target.closest('.bk-day');
        if (!btn) return;
        var step = { ArrowLeft: -1, ArrowRight: 1, ArrowUp: -7, ArrowDown: 7 }[e.key];
        if (!step) return;
        e.preventDefault();
        var d = parseYmd(btn.dataset.date);
        // Skip over closed days in the direction of travel.
        for (var guard = 0; guard < 60; guard++) {
            d.setDate(d.getDate() + step);
            var st = dayStatus(d);
            if (st === 'out') return;
            if (st === 'open') break;
        }
        if (d.getMonth() !== view.getMonth() || d.getFullYear() !== view.getFullYear()) {
            view = new Date(d.getFullYear(), d.getMonth(), 1);
        }
        renderCalendar(ymd(d));
    });

    function pickDate(s) {
        if (dateInput.value !== s) {
            dateInput.value = s;
            timeInput.value = '';
            state.timeLabel = '';
            $('bkTimeCleared').hidden = true;
        }
        clearError(null, 'appointment_date_error');
        renderCalendar();
        var picked = grid.querySelector('[data-date="' + s + '"]');
        if (picked) picked.focus();
        loadSlots();
        refreshSummary();
        // Phones stack the times under the calendar: bring them into view.
        if (window.matchMedia('(max-width: 767.98px)').matches) {
            var head = document.querySelector('.bk-times-head');
            var y = head.getBoundingClientRect().top + window.scrollY - 80;
            window.scrollTo({ top: y, behavior: reduceMotion ? 'auto' : 'smooth' });
        }
    }

    /* =========================================================
       Step 2 — time slots
       ========================================================= */
    var slotsBox = $('bkSlots');

    function slotLabel(hhmm) {
        var p = hhmm.split(':'); var h = +p[0];
        return ((h % 12) || 12) + ':' + p[1] + ' ' + (h < 12 ? 'AM' : 'PM');
    }

    function slotMessage(html) {
        slotsBox.innerHTML = '<div class="bk-slot-msg"><?= str_replace("'", "\\'", $icon['clock']) ?>' + html + '</div>';
    }

    function renderSlots(slots) {
        var groups = { Morning: [], Afternoon: [] };
        var open = 0;
        slots.forEach(function (s) {
            groups[parseInt(String(s.time).slice(0, 2), 10) < 12 ? 'Morning' : 'Afternoon'].push(s);
            if (s.available) open++;
        });
        if (!open) {
            slotMessage('<strong>Fully booked</strong><span>Every time on this day is taken. Please try another date.</span>');
            return;
        }
        var html = '';
        ['Morning', 'Afternoon'].forEach(function (name) {
            if (!groups[name].length) return;
            html += '<div class="bk-slot-group"><h3>' + name + '</h3><div class="bk-slots">';
            groups[name].forEach(function (s) {
                var on = s.available && s.time === timeInput.value;
                html += '<button type="button" class="bk-slot" data-time="' + escapeHtml(s.time) + '" data-label="' + escapeHtml(s.label) + '"'
                    + ' aria-pressed="' + (on ? 'true' : 'false') + '"'
                    + (s.available ? '' : ' disabled aria-label="' + escapeHtml(s.label) + ', not available"') + '>'
                    + escapeHtml(s.label) + '</button>';
            });
            html += '</div></div>';
        });
        slotsBox.innerHTML = html;
    }

    slotsBox.addEventListener('click', function (e) {
        var btn = e.target.closest('.bk-slot');
        if (!btn || btn.disabled) return;
        timeInput.value = btn.dataset.time;
        state.timeLabel = btn.dataset.label;
        $$('.bk-slot', slotsBox).forEach(function (b) { b.setAttribute('aria-pressed', b === btn ? 'true' : 'false'); });
        $('bkTimeCleared').hidden = true;
        clearError(null, 'appointment_time_error');
        refreshSummary();
    });

    var fetchSeq = 0;
    function loadSlots() {
        var date = dateInput.value;
        if (!date) return;
        var type = deptType();
        var key = date + '|' + type;
        $('bkDateLabel').textContent = longDate(date);
        if (key === state.slotsKey && (state.slots || state.loading)) return;

        state.slotsKey = key;
        state.slots = null;
        state.loading = true;
        var seq = ++fetchSeq;
        slotsBox.innerHTML = '<div class="bk-skel" aria-label="Loading times">' + new Array(9).join('<span></span>') + '</div>';

        var url = new URL(CFG.slotsUrl, window.location.origin);
        url.searchParams.set('date', date);
        url.searchParams.set('type', type);

        fetch(url.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (seq !== fetchSeq) return;
                if (!data || !data.ok) {
                    state.slots = [];
                    slotMessage('<strong>Not open for booking</strong><span>' + escapeHtml((data && data.error) || 'Please choose another date.') + '</span>');
                } else if (!data.working) {
                    state.slots = [];
                    slotMessage('<strong>Clinic closed</strong><span>We are closed on this day. Please choose another date.</span>');
                } else {
                    state.slots = data.slots || [];
                    renderSlots(state.slots);
                }
                // Drop a previously chosen time that is no longer open.
                if (timeInput.value) {
                    var still = (state.slots || []).some(function (s) { return s.time === timeInput.value && s.available; });
                    if (!still) {
                        timeInput.value = '';
                        state.timeLabel = '';
                        $('bkTimeCleared').hidden = false;
                    } else {
                        state.timeLabel = slotLabel(timeInput.value);
                    }
                }
                refreshSummary();
            })
            .catch(function () {
                if (seq !== fetchSeq) return;
                state.slots = null;
                state.slotsKey = '';
                slotMessage('<strong>Couldn\'t load times</strong><span>Check your connection.</span><button type="button" class="bk-btn bk-btn-outline" id="bkRetry">Try again</button>');
                var r = $('bkRetry'); if (r) r.addEventListener('click', loadSlots);
            })
            .finally(function () { if (seq === fetchSeq) state.loading = false; });
    }

    function validateStep2(quiet) {
        if (!dateInput.value) {
            return quiet ? false : showError(null, 'appointment_date_error', 'Choose a date on the calendar.');
        }
        clearError(null, 'appointment_date_error');
        if (state.loading) {
            return quiet ? false : showError(null, 'appointment_time_error', 'Open times are still loading. One moment.');
        }
        if (!timeInput.value) {
            return quiet ? false : showError(null, 'appointment_time_error', 'Choose a time to continue.');
        }
        clearError(null, 'appointment_time_error');
        return true;
    }

    /* =========================================================
       Step 3 — details
       ========================================================= */
    var nameInput  = $('full_name');
    var ageInput   = $('age');
    var genderGrp  = $('gender_group');
    var emailInput = $('email');
    var countrySel = $('phone_country');
    var phoneNat   = $('phone_national');
    var phoneField = $('phoneField');

    function gender() {
        var r = form.querySelector('input[name="gender"]:checked');
        return r ? r.value : '';
    }

    function country() {
        var o = countrySel.options[countrySel.selectedIndex];
        return {
            iso: o.value, dial: o.dataset.dial, len: parseInt(o.dataset.len, 10),
            groups: o.dataset.groups.split(',').map(Number),
            example: o.dataset.example, trunk: o.dataset.trunk || '', name: o.dataset.name
        };
    }
    function nationalDigits(raw, c) {
        var digits = (raw || '').replace(/\D/g, '');
        if (digits.indexOf(c.dial) === 0 && digits.length > c.len) digits = digits.slice(c.dial.length);
        if (c.trunk && digits.indexOf(c.trunk) === 0) digits = digits.slice(c.trunk.length);
        return digits.slice(0, c.len);
    }
    function formatPhone(digits, c) {
        var out = [], i = 0;
        for (var g = 0; g < c.groups.length; g++) {
            if (i >= digits.length) break;
            out.push(digits.substr(i, c.groups[g]));
            i += c.groups[g];
        }
        var body = out.join(' ');
        return c.trunk ? (digits ? c.trunk + body : '') : body;
    }
    function syncCountry() {
        var c = country();
        $('phoneDial').textContent = '+' + c.dial;
        $('phoneExample').textContent = c.example;
        phoneNat.placeholder = c.example;
        phoneNat.setAttribute('maxlength', String(c.len + c.groups.length + 2));
    }
    function reformatPhone() {
        var c = country();
        phoneNat.value = formatPhone(nationalDigits(phoneNat.value, c), c);
    }
    countrySel.addEventListener('change', function () { syncCountry(); reformatPhone(); clearError(phoneField, 'phone_error'); });
    phoneNat.addEventListener('input', function () { reformatPhone(); clearError(phoneField, 'phone_error'); });
    syncCountry();
    if (phoneNat.value) reformatPhone();

    // Email typo hint (gmial.com → gmail.com)
    var domains = ['gmail.com', 'yahoo.com', 'outlook.com', 'hotmail.com', 'icloud.com'];
    var suggestBox = $('emailSuggest');
    function suggestDomain(value) {
        var at = value.lastIndexOf('@');
        if (at < 1) return null;
        var dom = value.slice(at + 1).toLowerCase();
        if (!dom || domains.indexOf(dom) !== -1) return null;
        for (var k = 0; k < domains.length; k++) {
            var good = domains[k];
            if (Math.abs(good.length - dom.length) > 1) continue;
            var diff = 0, len = Math.max(good.length, dom.length);
            for (var i = 0, j = 0; i < len; i++, j++) {
                if (good[i] !== dom[j]) {
                    diff++;
                    if (good.length > dom.length) j--; else if (dom.length > good.length) i--;
                }
                if (diff > 1) break;
            }
            if (diff === 1) return value.slice(0, at + 1) + good;
        }
        return null;
    }
    emailInput.addEventListener('blur', function () {
        var fixed = suggestDomain(this.value.trim());
        if (!fixed) { suggestBox.hidden = true; return; }
        suggestBox.innerHTML = 'Did you mean <button type="button"></button>?';
        var b = suggestBox.querySelector('button');
        b.textContent = fixed;
        b.addEventListener('click', function () {
            emailInput.value = fixed; suggestBox.hidden = true; clearError(emailInput, 'email_error');
        });
        suggestBox.hidden = false;
    });

    var otherInput = $('other_requests');
    var otherCount = $('otherCount');
    function updateCount() {
        var n = otherInput.value.length;
        otherCount.textContent = n + ' / 500';
        otherCount.classList.toggle('near', n > 450);
    }
    otherInput.addEventListener('input', updateCount);
    updateCount();

    function validateStep3(quiet) {
        var focus = !quiet;
        var name = nameInput.value.trim().replace(/\s+/g, ' ');
        nameInput.value = name;
        if (name.length < 2) return quiet ? false : showError(nameInput, 'full_name_error', 'Enter the patient\'s full name.', focus);
        if (!/^[A-Za-zÀ-ÿÑñ.'\-\s]+$/.test(name)) return quiet ? false : showError(nameInput, 'full_name_error', 'Use letters only, e.g. Juan Dela Cruz.', focus);
        clearError(nameInput, 'full_name_error');

        var age = parseInt(ageInput.value, 10);
        if (ageInput.value === '' || isNaN(age)) return quiet ? false : showError(ageInput, 'age_error', 'Enter age in years.', focus);
        if (age < 1 || age > 120) return quiet ? false : showError(ageInput, 'age_error', 'Age must be 1 to 120. For babies under 1, enter 1.', focus);
        clearError(ageInput, 'age_error');

        if (!gender()) {
            if (quiet) return false;
            showError(genderGrp, 'gender_error', 'Choose one.', false);
            var first = genderGrp.querySelector('input');
            if (first) first.focus();
            return false;
        }
        clearError(genderGrp, 'gender_error');

        var email = emailInput.value.trim();
        if (!email) return quiet ? false : showError(emailInput, 'email_error', 'Enter an email address.', focus);
        if (!/^[A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,}$/.test(email)) {
            return quiet ? false : showError(emailInput, 'email_error', 'That email looks incomplete. Use name@domain.com.', focus);
        }
        emailInput.value = email;
        clearError(emailInput, 'email_error');

        var c = country();
        var digits = nationalDigits(phoneNat.value, c);
        if (!digits) {
            if (quiet) return false;
            showError(phoneField, 'phone_error', 'Enter a mobile number, e.g. ' + c.example + '.', false);
            phoneNat.focus(); return false;
        }
        if (digits.length !== c.len) {
            if (quiet) return false;
            showError(phoneField, 'phone_error', 'A ' + c.name + ' number has ' + c.len + ' digits after +' + c.dial + '. You entered ' + digits.length + '.', false);
            phoneNat.focus(); return false;
        }
        if (c.iso === 'PH' && digits.charAt(0) !== '9') {
            if (quiet) return false;
            showError(phoneField, 'phone_error', 'Philippine mobile numbers start with 09, e.g. 0912 345 6789.', false);
            phoneNat.focus(); return false;
        }
        clearError(phoneField, 'phone_error');

        var e164 = '+' + c.dial + digits;
        $('phone_e164').value = e164;
        $('phone').value = c.iso === 'PH' ? c.trunk + digits : e164;
        $('phone_national_post').value = phoneNat.value;
        return true;
    }

    [[nameInput, 'full_name_error'], [ageInput, 'age_error'], [emailInput, 'email_error']].forEach(function (p) {
        p[0].addEventListener('input', function () { clearError(p[0], p[1]); });
    });
    $$('input[name="gender"]').forEach(function (r) {
        r.addEventListener('change', function () { clearError(genderGrp, 'gender_error'); refreshSummary(); });
    });
    nameInput.addEventListener('change', refreshSummary);

    /* =========================================================
       Summary (aside + phone bar + review)
       ========================================================= */
    function refreshSummary() {
        var p = picked();
        var fee = p.reduce(function (t, s) { return t + s.price; }, 0);
        var total = fee + CFG.fee;
        var testsLabel = p.length ? p.length + (p.length === 1 ? ' test' : ' tests') : 'Tests';

        // Tab pills
        ['lab', 'xray'].forEach(function (d) {
            var n = p.filter(function (s) { return s.dept === d; }).length;
            var pill = document.querySelector('[data-picked="' + d + '"]');
            pill.textContent = n; pill.hidden = !n;
            pill.setAttribute('aria-label', n + ' selected');
            pill.previousElementSibling.hidden = !!n;
        });

        // Picked chips (step 1)
        $('bkPicked').hidden = !p.length;
        $('bkPickedCount').textContent = p.length + (p.length === 1 ? ' test selected' : ' tests selected') + ' · ' + peso(fee);
        $('bkChips').innerHTML = p.map(function (s) {
            return '<button type="button" class="bk-chip" data-remove="' + escapeHtml(s.name) + '" data-dept="' + s.dept + '">'
                + '<span>' + escapeHtml(s.name) + '</span>'
                + '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>'
                + '<span class="bk-sr">Remove</span></button>';
        }).join('');

        // Aside
        var list = $('sumTests');
        if (!p.length) {
            list.innerHTML = '<li class="is-empty">None selected yet</li>';
        } else {
            var shown = p.slice(0, 6).map(function (s) {
                return '<li><span>' + escapeHtml(s.name) + '</span><span>' + peso(s.price) + '</span></li>';
            }).join('');
            if (p.length > 6) shown += '<li class="is-more">+ ' + (p.length - 6) + ' more</li>';
            list.innerHTML = shown;
        }
        var when = $('sumWhen');
        if (dateInput.value) {
            when.textContent = shortDate(dateInput.value) + (state.timeLabel ? ' · ' + state.timeLabel : ' · time not chosen');
            when.classList.remove('is-empty');
        } else {
            when.textContent = 'Not chosen yet';
            when.classList.add('is-empty');
        }
        var who = nameInput.value.trim();
        $('sumWhoBlock').hidden = !who || state.reached < 3;
        $('sumWho').textContent = who + (gender() ? ' · ' + gender() : '');

        $('sumTestsLabel').textContent = testsLabel;
        $('sumTestsFee').textContent = peso(fee);
        $('sumTotal').textContent = peso(total);

        // Phone bar
        var info = p.length ? testsLabel : 'No tests yet';
        if (dateInput.value) info += ' · ' + shortDate(dateInput.value) + (state.timeLabel ? ' ' + state.timeLabel : '');
        $('bkBarInfo').textContent = info;
        $('bkBarTotal').textContent = peso(total);

        // Review
        $('rvTestsLabel').textContent = testsLabel;
        $('rvTestsFee').textContent = peso(fee);
        $('rvTotal').textContent = peso(total);
    }

    function fillReview() {
        $('rvWhen').innerHTML = escapeHtml(longDate(dateInput.value)) + '<small>' + escapeHtml(state.timeLabel) + '</small>';
        $('rvPatient').innerHTML = escapeHtml(nameInput.value.trim()) + '<small>' + escapeHtml(ageInput.value) + ' years old · ' + escapeHtml(gender()) + '</small>';
        var c = country();
        $('rvContact').innerHTML = escapeHtml(emailInput.value.trim()) + '<small>+' + escapeHtml(c.dial) + ' ' + escapeHtml(phoneNat.value.replace(/^0/, '')) + '</small>';
        $('rvTests').innerHTML = '<ul>' + picked().map(function (s) {
            return '<li><span>' + escapeHtml(s.name) + '</span><span>' + peso(s.price) + '</span></li>';
        }).join('') + '</ul>';
        var notes = otherInput.value.trim();
        $('rvNotesRow').hidden = !notes;
        $('rvNotes').textContent = notes;
    }

    /* =========================================================
       Navigation
       ========================================================= */
    var validators = { 1: validateStep1, 2: validateStep2, 3: validateStep3 };
    var nextLabels = { 1: 'Choose date', 2: 'Your details', 3: 'Review booking', 4: 'Confirm booking' };
    var backBtn = $('bkBack');
    var nextBtn = $('bkNext');

    function goTo(step, opts) {
        opts = opts || {};
        var back = step < state.step;
        state.step = step;
        state.reached = Math.max(state.reached, step);

        $$('.bk-step').forEach(function (el) {
            var on = Number(el.dataset.step) === step;
            el.classList.toggle('is-active', on);
            el.classList.toggle('is-back', on && back);
            if (on) {
                $('bkKicker').textContent = 'Step ' + step + ' of ' + TOTAL_STEPS;
                $('bkTitle').textContent = el.dataset.title;
                $('bkLead').textContent = el.dataset.lead;
            }
        });
        var hero = document.querySelector('.bk-hero-text');
        hero.classList.remove('is-swap'); void hero.offsetWidth; hero.classList.add('is-swap');
        $$('#bkProgress li').forEach(function (li) {
            var n = Number(li.dataset.step);
            var btn = li.querySelector('button');
            var was = li.className;
            li.className = n < step ? 'is-done' : (n === step ? 'is-current' : '');
            /* Replay the arrival pop only on the step that just changed state */
            if (li.className && li.className !== was) { li.classList.add('is-arriving'); setTimeout(function () { li.classList.remove('is-arriving'); }, 650); }
            var pill = li.querySelector('[data-pill]');
            if (pill) pill.textContent = n < step ? 'Completed' : (n === step ? 'In progress' : 'Pending');
            btn.disabled = n > state.reached || n === step;
            if (n === step) btn.setAttribute('aria-current', 'step'); else btn.removeAttribute('aria-current');
        });

        backBtn.hidden = step === 1;
        $('bkNextLabel').textContent = nextLabels[step];

        if (step === 2) { renderCalendar(); if (dateInput.value) loadSlots(); }
        if (step === 4) fillReview();
        refreshSummary();

        if (!opts.silent) {
            window.scrollTo({ top: 0, behavior: reduceMotion ? 'auto' : 'smooth' });
            $('bkTitle').focus({ preventScroll: true });
        }
    }

    function tryAdvance() {
        for (var s = 1; s <= state.step; s++) {
            if (validators[s] && !validators[s](s !== state.step)) {
                if (s !== state.step) { goTo(s); validators[s](false); }
                return;
            }
        }
        if (state.step < TOTAL_STEPS) goTo(state.step + 1);
        else submit();
    }

    nextBtn.addEventListener('click', tryAdvance);
    backBtn.addEventListener('click', function () { if (state.step > 1) goTo(state.step - 1); });
    $$('[data-goto]').forEach(function (b) {
        b.addEventListener('click', function () { goTo(parseInt(b.dataset.goto, 10)); });
    });

    // Enter in a text field moves forward instead of submitting the form.
    form.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && e.target.matches('input:not([type="checkbox"]):not([type="radio"]):not([type="search"])')) {
            e.preventDefault();
            tryAdvance();
        }
    });
    form.addEventListener('submit', function (e) { if (!submitting) e.preventDefault(); });

    /* =========================================================
       Submit
       ========================================================= */
    var submitting = false;
    $('paymentAgree').addEventListener('change', function () { if (this.checked) clearError(null, 'agree_error'); });

    function submit() {
        if (submitting) return;
        if (!$('paymentAgree').checked) {
            showError(null, 'agree_error', 'Tick the box to confirm you will pay at the clinic.');
            $('paymentAgree').focus();
            return;
        }
        submitting = true;
        nextBtn.classList.add('is-loading');
        nextBtn.innerHTML = '<span class="bk-spin" aria-hidden="true"></span><span>Booking…</span>';
        form.submit();
    }

    window.addEventListener('pageshow', function (e) {
        if (!e.persisted) return;
        submitting = false;
        nextBtn.classList.remove('is-loading');
        nextBtn.innerHTML = '<span id="bkNextLabel">' + nextLabels[state.step] + '</span><?= str_replace("'", "\\'", $icon['right']) ?>';
    });

    /* =========================================================
       Start — after a server-side error, reopen the first step
       that still needs attention, with everything restored.
       ========================================================= */
    if (timeInput.value) state.timeLabel = slotLabel(timeInput.value);
    if (CFG.slotTaken && dateInput.value) $('bkTimeCleared').hidden = false;
    renderCalendar();
    refreshSummary();

    if (CFG.hasErrors) {
        var start = 1;
        if (validateStep1(true)) {
            start = 2;
            if (dateInput.value) { state.reached = 2; loadSlots(); }
            if (dateInput.value && timeInput.value) {
                start = 3;
                if (validateStep3(true)) start = 4;
            }
        }
        state.reached = start;
        goTo(start, { silent: true });
    } else {
        goTo(1, { silent: true });
    }
})();
</script>

<?= $this->endSection() ?>
