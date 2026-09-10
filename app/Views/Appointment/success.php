<?= $this->extend('layouts/AppointmentLayout') ?>

<?= $this->section('AppointmentContent') ?>

<?php
    // Optional flags the controller can set. When absent, this view
    // simply does not make the claim.
    $emailSent = !empty($emailSent ?? false);
?>

<style>
/* =========================================================
   APPOINTMENT CONFIRMATION
   Calm, printable, single-purpose. No confetti, no emoji.
   ========================================================= */

:root {
    --ac-blue:      #0a2b4e;
    --ac-blue-soft: #eef4ff;
    --ac-teal:      #0f766e;
    --ac-teal-soft: #ecfdf5;
    --ac-line:      #e2e8f0;
    --ac-line-soft: #f1f5f9;
    --ac-text:      #334155;
    --ac-muted:     #64748b;
    --ac-ink:       #0f172a;
    --ac-surface:   #ffffff;
    --ac-canvas:    #f6f8fc;
    --ac-mono:      ui-monospace, SFMono-Regular, "SF Mono", Menlo, Consolas, monospace;
}

.success-page {
    background: var(--ac-canvas);
    min-height: 100vh;
    padding: 3rem 0 4rem;
    color: var(--ac-text);
    font-family: Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}

/* ---------- Card ---------- */

.success-card {
    background: var(--ac-surface);
    border: 1px solid var(--ac-line);
    border-radius: 14px;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    overflow: hidden;
    max-width: 720px;
    margin: 0 auto;
}

.card-header-success {
    background: var(--ac-blue);
    padding: 2rem 2.25rem 1.75rem;
    text-align: center;
    color: #ffffff;
}

.success-icon-wrapper {
    width: 56px;
    height: 56px;
    background: var(--ac-teal);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
}

.success-icon-wrapper i {
    font-size: 1.75rem;
    color: #ffffff;
}

.card-header-success h2 {
    color: #ffffff;
    font-weight: 650;
    font-size: 1.5rem;
    letter-spacing: -0.015em;
    margin: 0 0 0.35rem;
}

.card-header-success p {
    color: rgba(255, 255, 255, 0.78);
    margin: 0;
    font-size: 0.9rem;
}

.card-body-success {
    padding: 1.75rem 2.25rem 2rem;
}

/* ---------- Reference number ---------- */

.ref-number {
    background: var(--ac-blue-soft);
    border: 1px dashed #c7d8f4;
    border-radius: 12px;
    padding: 1.1rem;
    text-align: center;
    margin-bottom: 1.5rem;
}

.ref-number .label {
    display: block;
    color: var(--ac-muted);
    font-size: 0.72rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    margin-bottom: 0.35rem;
}

.ref-number .number {
    display: inline-block;
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--ac-blue);
    letter-spacing: 0.04em;
    font-family: var(--ac-mono);
    overflow-wrap: anywhere;
}

.copy-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    margin-left: 0.5rem;
    padding: 0.35rem 0.7rem;
    font-size: 0.78rem;
    font-weight: 600;
    color: var(--ac-blue);
    background: var(--ac-surface);
    border: 1px solid #c7d8f4;
    border-radius: 7px;
    cursor: pointer;
    vertical-align: middle;
    transition: background-color 0.15s ease, border-color 0.15s ease;
}

.copy-btn:hover { background: #ffffff; border-color: var(--ac-blue); }

/* ---------- Screenshot reminder ---------- */

.screenshot-reminder {
    display: flex;
    gap: 0.85rem;
    padding: 1rem 1.1rem;
    margin-bottom: 1.5rem;
    background: #fff8e6;
    border: 1px solid #fde68a;
    border-radius: 12px;
    color: #78350f;
}

.screenshot-reminder i {
    font-size: 1.15rem;
    color: #b45309;
    flex-shrink: 0;
    margin-top: 0.1rem;
}

.screenshot-reminder strong {
    display: block;
    font-size: 0.875rem;
    font-weight: 700;
    color: #78350f;
    margin-bottom: 0.2rem;
}

.screenshot-reminder p {
    margin: 0;
    font-size: 0.83rem;
    line-height: 1.55;
    color: #92400e;
}

.screenshot-reminder .ref-inline {
    font-family: var(--ac-mono);
    font-weight: 700;
    color: #78350f;
    background: #fef3c7;
    border-radius: 4px;
    padding: 0.05rem 0.35rem;
}

/* ---------- Details grid ---------- */

.details-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.75rem;
    margin-bottom: 1.5rem;
}

.detail-item {
    background: var(--ac-line-soft);
    border: 1px solid var(--ac-line);
    border-radius: 10px;
    padding: 0.75rem 1rem;
    min-width: 0;
}

.detail-item .label {
    display: block;
    font-size: 0.68rem;
    color: var(--ac-muted);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    margin-bottom: 0.2rem;
}

.detail-item .label i { color: var(--ac-blue); }

.detail-item .value {
    font-weight: 600;
    color: var(--ac-ink);
    font-size: 0.9rem;
    overflow-wrap: anywhere;
}

/* ---------- Payment badge ---------- */

.payment-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.28rem 0.85rem;
    font-size: 0.78rem;
    font-weight: 700;
    color: var(--ac-teal);
    background: var(--ac-teal-soft);
    border: 1px solid #a7f3d0;
    border-radius: 999px;
}

.payment-badge i { font-size: 0.8em; }

/* ---------- Services ---------- */

.services-list {
    background: var(--ac-line-soft);
    border: 1px solid var(--ac-line);
    border-radius: 10px;
    padding: 1rem 1.15rem;
    margin-bottom: 1.5rem;
}

.services-list .label {
    display: block;
    font-size: 0.68rem;
    color: var(--ac-muted);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    margin-bottom: 0.55rem;
}

.services-list .label i { color: var(--ac-blue); }

.services-list .tags {
    display: flex;
    flex-wrap: wrap;
    gap: 0.4rem;
}

.services-list .tag {
    background: var(--ac-surface);
    border: 1px solid #d8e2f2;
    padding: 0.2rem 0.65rem;
    border-radius: 6px;
    font-size: 0.78rem;
    color: var(--ac-blue);
    font-weight: 500;
}

/* ---------- Buttons ---------- */

.btn-group-success {
    display: flex;
    gap: 0.6rem;
    justify-content: center;
    flex-wrap: wrap;
    margin-top: 1.5rem;
}

.btn-primary-custom,
.btn-outline-custom {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.45rem;
    padding: 0.7rem 1.5rem;
    font-size: 0.875rem;
    font-weight: 600;
    border-radius: 9px;
    text-decoration: none;
    cursor: pointer;
    transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
}

.btn-primary-custom {
    background: var(--ac-blue);
    color: #ffffff;
    border: 1px solid var(--ac-blue);
}

.btn-primary-custom:hover {
    background: #08223d;
    border-color: #08223d;
    color: #ffffff;
    text-decoration: none;
}

.btn-outline-custom {
    background: var(--ac-surface);
    color: var(--ac-text);
    border: 1px solid var(--ac-line);
}

.btn-outline-custom:hover {
    background: var(--ac-line-soft);
    border-color: #cbd5e1;
    color: var(--ac-ink);
    text-decoration: none;
}

/* ---------- Footer note ---------- */

.help-note {
    margin-top: 1.5rem;
    padding-top: 1rem;
    border-top: 1px solid var(--ac-line-soft);
    text-align: center;
    font-size: 0.8rem;
    color: var(--ac-muted);
    line-height: 1.6;
}

.help-note strong { color: var(--ac-ink); }

/* ---------- Print ---------- */

@media print {
    .success-page { background: #ffffff; padding: 0; }

    .btn-group-success,
    .copy-btn,
    .footer { display: none !important; }

    .success-card {
        box-shadow: none;
        border: 1px solid #cbd5e1;
        border-radius: 0;
        max-width: 100%;
    }

    .screenshot-reminder {
        background: #ffffff;
        border-style: solid;
    }
}

/* ---------- Responsive ---------- */

@media (max-width: 768px) {
    .success-page { padding: 1.5rem 0 2.5rem; }

    .card-header-success { padding: 1.5rem 1.25rem 1.25rem; }

    .card-body-success { padding: 1.25rem 1.25rem 1.5rem; }

    .details-grid { grid-template-columns: 1fr; }

    .ref-number .number { font-size: 1.2rem; }

    .copy-btn { margin-left: 0; margin-top: 0.5rem; display: block; width: fit-content; }

    .btn-group-success { flex-direction: column; align-items: stretch; }

    .btn-group-success .btn-primary-custom,
    .btn-group-success .btn-outline-custom { width: 100%; }
}

@media (prefers-reduced-motion: reduce) {
    .success-page * { transition-duration: 0.01ms !important; animation-duration: 0.01ms !important; }
}
</style>

<!-- ===== SUCCESS PAGE ===== -->
<section class="success-page">
    <div class="container">

        <div class="success-card">

            <div class="card-header-success">
                <div class="success-icon-wrapper" aria-hidden="true">
                    <i class="bi bi-check-lg"></i>
                </div>
                <h2>Appointment confirmed</h2>
                <p>Your appointment has been successfully booked.</p>
            </div>

            <div class="card-body-success">

                <!-- Reference number -->
                <div class="ref-number">
                    <span class="label">Reference number</span>
                    <span class="number" id="refNumber"><?= esc($reference ?? '—') ?></span>
                    <button type="button" class="copy-btn" onclick="copyReference()" title="Copy reference number">
                        <i class="bi bi-clipboard" aria-hidden="true"></i>
                        <span id="copyBtnLabel">Copy</span>
                    </button>
                </div>

                <!-- Screenshot reminder -->
                <div class="screenshot-reminder" role="note">
                    <i class="bi bi-camera" aria-hidden="true"></i>
                    <div>
                        <strong>Save this page for your reference.</strong>
                        <p>
                            Take a screenshot of this page, or write down the reference number
                            <span class="ref-inline"><?= esc($reference ?? '—') ?></span>.
                            You will need it when you arrive at the clinic and if you contact us about this booking.
                        </p>
                    </div>
                </div>

                <?php if (isset($appointment) && is_array($appointment)): ?>

                    <div class="details-grid">
                        <div class="detail-item">
                            <span class="label"><i class="bi bi-calendar3" aria-hidden="true"></i> Date</span>
                            <span class="value"><?= esc($appointment['appointment_date'] ?? 'N/A') ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="label"><i class="bi bi-clock" aria-hidden="true"></i> Time</span>
                            <span class="value"><?= esc($appointment['appointment_time'] ?? 'N/A') ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="label"><i class="bi bi-person" aria-hidden="true"></i> Patient</span>
                            <span class="value"><?= esc($appointment['full_name'] ?? 'N/A') ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="label"><i class="bi bi-gender-ambiguous" aria-hidden="true"></i> Age / sex</span>
                            <span class="value"><?= esc(($appointment['age'] ?? 'N/A') . ' yrs, ' . ($appointment['gender'] ?? 'N/A')) ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="label"><i class="bi bi-envelope" aria-hidden="true"></i> Email</span>
                            <span class="value" style="font-size: 0.85rem;"><?= esc($appointment['email'] ?? 'N/A') ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="label"><i class="bi bi-telephone" aria-hidden="true"></i> Phone</span>
                            <span class="value"><?= esc($appointment['phone'] ?? 'N/A') ?></span>
                        </div>
                        <div class="detail-item" style="grid-column: 1 / -1; text-align: center;">
                            <span class="label"><i class="bi bi-credit-card" aria-hidden="true"></i> Payment status</span>
                            <span class="value">
                                <span class="payment-badge">
                                    <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
                                    Booking confirmed
                                </span>
                            </span>
                        </div>
                    </div>

                    <?php
                    $services = [];

                    if (!empty($appointment['lab_services']) && is_array($appointment['lab_services'])) {
                        $services = array_merge($services, $appointment['lab_services']);
                    }
                    if (!empty($appointment['xray_services']) && is_array($appointment['xray_services'])) {
                        $services = array_merge($services, $appointment['xray_services']);
                    }
                    ?>

                    <?php if (!empty($services)): ?>
                        <div class="services-list">
                            <span class="label"><i class="bi bi-clipboard2-pulse" aria-hidden="true"></i> Selected services</span>
                            <div class="tags">
                                <?php foreach ($services as $service): ?>
                                    <span class="tag"><?= esc($service) ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($appointment['other_requests'])): ?>
                        <div class="services-list" style="background: #fff8f0; border-color: #fde8d0;">
                            <span class="label"><i class="bi bi-clipboard" aria-hidden="true"></i> Other requests</span>
                            <p class="mb-0" style="font-size: 0.9rem; color: #92400e; margin: 0;">
                                <?= esc($appointment['other_requests']) ?>
                            </p>
                        </div>
                    <?php endif; ?>

                <?php else: ?>
                    <div class="alert alert-warning rounded-3">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        No appointment data found. Please contact us for assistance.
                    </div>
                <?php endif; ?>

                <div class="btn-group-success">
                    <a href="<?= base_url('/') ?>" class="btn-outline-custom">
                        <i class="bi bi-house" aria-hidden="true"></i>
                        Back home
                    </a>
                    <a href="<?= base_url('appointment/book') ?>" class="btn-primary-custom">
                        <i class="bi bi-plus-circle" aria-hidden="true"></i>
                        New appointment
                    </a>
                    <button type="button" onclick="window.print()" class="btn-outline-custom">
                        <i class="bi bi-printer" aria-hidden="true"></i>
                        Print
                    </button>
                </div>

                <div class="help-note">
                    <?php if ($emailSent): ?>
                        <p style="margin: 0 0 0.35rem;">
                            <i class="bi bi-envelope" aria-hidden="true"></i>
                            A confirmation email has been sent to your email address.
                        </p>
                    <?php endif; ?>
                    <p style="margin: 0;">
                        For urgent concerns, please contact the clinic using the number listed on our website.
                    </p>
                </div>

            </div>
        </div>

    </div>
</section>

<footer class="py-2 bg-dark text-white footer">
    <div class="container px-5">
        <p class="text-center mb-0">&copy; <?= date('Y') ?> PolyMedic. All rights reserved.</p>
    </div>
</footer>

<script>
(function () {
    'use strict';

    /* Copy the reference number. Falls back to a manual-selection
       dialog on browsers where the Clipboard API is unavailable
       (older Safari, or when the page is served over plain HTTP). */
    window.copyReference = function () {
        var el = document.getElementById('refNumber');
        var label = document.getElementById('copyBtnLabel');

        if (!el) { return; }

        var text = el.textContent.trim();

        function showCopied() {
            if (!label) { return; }
            var original = label.textContent;
            label.textContent = 'Copied';
            window.setTimeout(function () { label.textContent = original; }, 2000);
        }

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(showCopied).catch(function () {
                window.prompt('Copy the reference number below.', text);
            });
            return;
        }

        window.prompt('Copy the reference number below.', text);
    };
})();
</script>

<?= $this->endSection() ?>