<?= $this->extend('Layouts/AppointmentLayout') ?>

<?= $this->section('AppointmentContent') ?>

<link href="<?= base_url('assets/css/patient-booking.css') ?>" rel="stylesheet">

<?php
    $emailSent = ! empty($emailSent ?? false);
    $appt      = (isset($appointment) && is_array($appointment)) ? $appointment : null;
    $ref       = (string) ($reference ?? '');

    $services = [];
    if ($appt) {
        foreach (['lab_services', 'xray_services'] as $k) {
            if (! empty($appt[$k]) && is_array($appt[$k])) {
                $services = array_merge($services, $appt[$k]);
            }
        }
    }

    $ts       = $appt ? strtotime($appt['appointment_date'] . ' ' . $appt['appointment_time']) : false;
    $dateText = $ts ? date('l, F j, Y', $ts) : null;
    $timeText = $ts ? date('g:i A', $ts) : null;

    $ics = null;
    if ($ts) {
        // Floating local time (no Z): calendar apps show it in the clinic's local time.
        $ics = [
            'start' => date('Ymd\THis', $ts),
            'end'   => date('Ymd\THis', $ts + 3600),
            'title' => 'PolyMedic appointment (' . $ref . ')',
            'where' => 'PolyMedic Diagnostic & Laboratory Center, Governor Gutierrez Ave, Cotabato City 9600',
            'notes' => 'Reference ' . $ref . '. Tests: ' . implode(', ', $services),
        ];
    }

    $icon = [
        'check' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12.5 10 17l9-10"/></svg>',
        'copy'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15V5a2 2 0 0 1 2-2h10"/></svg>',
        'cal'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/></svg>',
        'print' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9V3h12v6M6 18H4a1 1 0 0 1-1-1v-6a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v6a1 1 0 0 1-1 1h-2"/><rect x="6" y="14" width="12" height="7" rx="1"/></svg>',
        'home'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m3 11 9-7 9 7M5 9.5V20h14V9.5"/></svg>',
        'alert' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v6M12 16.5h.01"/></svg>',
    ];
?>

<div class="bk">
    <header class="bk-top">
        <div class="bk-wrap bk-top-inner">
            <a class="bk-brand" href="<?= base_url() ?>">
                <img src="<?= base_url('assets/images/logo4.png') ?>" alt="">
                <span>
                    <b>PolyMedic</b>
                    <small>Diagnostic &amp; Laboratory Center</small>
                </span>
            </a>
        </div>
    </header>

    <main class="bk-wrap bk-main">
        <div class="bk-card bk-done">

            <div class="bk-done-hero">
                <div class="bk-done-mark"><?= $icon['check'] ?></div>
                <h1>Booking received</h1>
                <p>
                    <?php if ($appt): ?>
                        Thanks, <?= esc(strtok((string) $appt['full_name'], ' ')) ?>. Our staff will review your request and confirm it before your visit.
                    <?php else: ?>
                        Our staff will review your request and confirm it before your visit.
                    <?php endif; ?>
                </p>
                <span class="bk-status"><i></i>Waiting for clinic confirmation</span>
            </div>

            <div class="bk-ticket">
                <span class="bk-sum-label">Reference number</span>
                <span class="bk-ref" id="refNumber"><?= esc($ref !== '' ? $ref : '—') ?></span>
                <button type="button" class="bk-btn bk-btn-outline" id="copyRef">
                    <?= $icon['copy'] ?><span id="copyLabel">Copy number</span>
                </button>
                <p>Take a screenshot or write this down. Show it at the front desk when you arrive.</p>
            </div>

            <div class="bk-done-body">
                <?php if ($appt): ?>
                    <dl class="bk-facts">
                        <div>
                            <dt>Date</dt>
                            <dd><?= esc($dateText ?? $appt['appointment_date']) ?></dd>
                        </div>
                        <div>
                            <dt>Time</dt>
                            <dd><?= esc($timeText ?? $appt['appointment_time']) ?><small>Please arrive 10 minutes early</small></dd>
                        </div>
                        <div>
                            <dt>Patient</dt>
                            <dd><?= esc($appt['full_name']) ?><small><?= esc($appt['age']) ?> years old · <?= esc($appt['gender']) ?></small></dd>
                        </div>
                        <div>
                            <dt>Contact</dt>
                            <dd><?= esc($appt['email']) ?><small><?= esc($appt['phone']) ?></small></dd>
                        </div>
                        <?php if ($services): ?>
                            <div class="wide">
                                <dt>Tests</dt>
                                <dd class="bk-tags">
                                    <?php foreach ($services as $s): ?><span><?= esc($s) ?></span><?php endforeach; ?>
                                </dd>
                            </div>
                        <?php endif; ?>
                        <?php if (! empty($appt['other_requests'])): ?>
                            <div class="wide">
                                <dt>Your notes</dt>
                                <dd style="font-weight: 400;"><?= esc($appt['other_requests']) ?></dd>
                            </div>
                        <?php endif; ?>
                    </dl>
                <?php else: ?>
                    <div class="bk-alert" role="status" style="background: var(--bk-amber-soft); color: var(--bk-amber);">
                        <?= $icon['alert'] ?>
                        <div>
                            <strong style="color: var(--bk-amber);">Booking details are only shown on the device that made the booking.</strong>
                            Keep the reference number above and show it at the front desk.
                        </div>
                    </div>
                <?php endif; ?>

                <h2 style="margin-top: 1.75rem; font-size: 1.0625rem; font-weight: 700;">What happens next</h2>
                <ol class="bk-next-steps">
                    <li>
                        <strong>We review your request</strong>
                        <span>The clinic checks your tests and time slot<?= $emailSent ? ' and emails you once it is confirmed' : '' ?>.</span>
                    </li>
                    <li>
                        <strong>Come in on your date</strong>
                        <span>Bring a valid ID, your reference number and your doctor's request if you have one.</span>
                    </li>
                    <li>
                        <strong>Pay at the counter</strong>
                        <span>Settle the consultation and test fees at the clinic. Nothing is charged online.</span>
                    </li>
                </ol>

                <div class="bk-done-actions">
                    <a href="<?= base_url() ?>" class="bk-btn bk-btn-quiet"><?= $icon['home'] ?>Home</a>
                    <button type="button" class="bk-btn bk-btn-outline" onclick="window.print()"><?= $icon['print'] ?>Print</button>
                    <?php if ($ics): ?>
                        <button type="button" class="bk-btn bk-btn-primary" id="addToCal"><?= $icon['cal'] ?>Add to calendar</button>
                    <?php else: ?>
                        <a href="<?= base_url('appointment/book') ?>" class="bk-btn bk-btn-primary"><?= $icon['cal'] ?>Book another</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
(function () {
    'use strict';

    var copyBtn = document.getElementById('copyRef');
    var label   = document.getElementById('copyLabel');
    var ref     = document.getElementById('refNumber').textContent.trim();

    copyBtn.addEventListener('click', function () {
        function done() {
            label.textContent = 'Copied';
            setTimeout(function () { label.textContent = 'Copy number'; }, 2000);
        }
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(ref).then(done).catch(function () {
                window.prompt('Copy the reference number below.', ref);
            });
        } else {
            window.prompt('Copy the reference number below.', ref);
        }
    });

    <?php if ($ics): ?>
    var ev = <?= json_encode($ics, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    var esc = function (s) { return String(s).replace(/([,;\\])/g, '\\$1').replace(/\n/g, '\\n'); };
    document.getElementById('addToCal').addEventListener('click', function () {
        var body = [
            'BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//PolyMedic//Booking//EN', 'BEGIN:VEVENT',
            'UID:' + ref + '@polymedic',
            'DTSTAMP:' + new Date().toISOString().replace(/[-:]/g, '').replace(/\.\d+/, ''),
            'DTSTART:' + ev.start, 'DTEND:' + ev.end,
            'SUMMARY:' + esc(ev.title), 'LOCATION:' + esc(ev.where), 'DESCRIPTION:' + esc(ev.notes),
            'BEGIN:VALARM', 'TRIGGER:-PT2H', 'ACTION:DISPLAY', 'DESCRIPTION:PolyMedic appointment', 'END:VALARM',
            'END:VEVENT', 'END:VCALENDAR'
        ].join('\r\n');
        var url = URL.createObjectURL(new Blob([body], { type: 'text/calendar;charset=utf-8' }));
        var a = document.createElement('a');
        a.href = url; a.download = 'polymedic-' + ref + '.ics';
        document.body.appendChild(a); a.click(); a.remove();
        setTimeout(function () { URL.revokeObjectURL(url); }, 1000);
    });
    <?php endif; ?>
})();
</script>

<?= $this->endSection() ?>
