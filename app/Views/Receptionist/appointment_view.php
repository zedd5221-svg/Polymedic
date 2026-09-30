<?= $this->extend('layouts/ReceptionistLayout') ?>

<?= $this->section('pageTitle') ?><?= ($viewMode ?? 'appointment') === 'patient' ? 'Patient Details' : 'Appointment Details' ?><?= $this->endSection() ?>

<?= $this->section('receptionistContent') ?>

<?php
    $appointment = $appointment ?? [];

    $viewMode      = $viewMode ?? 'appointment';
    $isPatientView = ($viewMode === 'patient');

    $maleAvatar   = 'man-avatar.png';
    $femaleAvatar = 'woman-avatar.png';

    $initialsOf = static function ($name) {
        $parts = preg_split('/\s+/', trim((string) $name));
        $first = mb_substr($parts[0] ?? '', 0, 1);
        $last  = count($parts) > 1 ? mb_substr(end($parts), 0, 1) : '';
        return mb_strtoupper($first . $last);
    };

    $fmt = static function ($value, $format = 'M d, Y h:i A') {
        if (empty($value)) { return null; }
        $ts = strtotime((string) $value);
        return $ts ? date($format, $ts) : null;
    };

    $statusKey  = (string) ($appointment['status'] ?? 'pending');
    $genderRaw  = strtolower(trim((string) ($appointment['gender'] ?? '')));
    $isMale     = $genderRaw === 'male'   || $genderRaw === 'm';
    $isFemale   = $genderRaw === 'female' || $genderRaw === 'f';
    $avatarKind = $isMale ? 'male' : ($isFemale ? 'female' : 'neutral');

    $statusCopy = [
        'pending'   => ['label' => 'Pending',   'tone' => 'pending',   'icon' => 'bi-clock-history',        'note' => 'Waiting for approval.'],
        'approved'  => ['label' => 'Approved',  'tone' => 'approved',  'icon' => 'bi-check-circle',         'note' => 'Approved and confirmed.'],
        'completed' => ['label' => 'Completed', 'tone' => 'completed', 'icon' => 'bi-check2-circle',        'note' => 'Completed successfully.'],
        'cancelled' => ['label' => 'Cancelled', 'tone' => 'cancelled', 'icon' => 'bi-x-circle',             'note' => 'This appointment was cancelled.'],
        'late'      => ['label' => 'Late',      'tone' => 'late',      'icon' => 'bi-exclamation-triangle', 'note' => 'Patient is an hour or more late.'],
    ];

    $status = $statusCopy[$statusKey] ?? [
        'label' => ucfirst($statusKey),
        'tone'  => 'pending',
        'icon'  => 'bi-info-circle',
        'note'  => '',
    ];

    $labServices  = $lab_services  ?? [];
    $xrayServices = $xray_services ?? [];
    $serviceTotal = count($labServices) + count($xrayServices);

    $appointmentId = (int) ($appointment['id'] ?? 0);
    $backUrl       = base_url($isPatientView ? 'receptionist/patients' : 'receptionist/appointments');
    $backLabel     = $isPatientView ? 'Patients' : 'Appointments';
?>

<div class="av<?= $isPatientView ? ' av--patient' : '' ?>">

    <?php if (! empty($appointment)): ?>

        <!-- =========================================================
             PAGE TOOLBAR
             ========================================================= -->
        <div class="av-toolbar">

            <div class="av-toolbar-lead">

                <?php if (! $isPatientView): ?>
                    <a href="<?= esc($backUrl, 'attr') ?>" class="av-back" title="Back to <?= esc($backLabel) ?>">
                        <i class="bi bi-arrow-left" aria-hidden="true"></i>
                        <span><?= esc($backLabel) ?></span>
                    </a>

                    <span class="av-toolbar-divider" aria-hidden="true"></span>
                <?php endif; ?>

                <div class="av-toolbar-id">
                    <h1 class="av-toolbar-title">
                        <?= $isPatientView ? 'Patient record' : 'Appointment' ?>
                    </h1>
                    <span class="av-ref">
                        <?= $isPatientView ? 'PT' : 'REF' ?>
                        <strong><?= esc($appointment['reference_number'] ?? '—') ?></strong>
                    </span>
                </div>
            </div>

            <div class="av-toolbar-actions">

                <?php if (! $isPatientView): ?>
                    <span class="av-chip av-chip--<?= esc($status['tone'], 'attr') ?>">
                        <i class="bi <?= esc($status['icon'], 'attr') ?>" aria-hidden="true"></i>
                        <?= esc($status['label']) ?>
                    </span>
                <?php endif; ?>

                <?php if (! $isPatientView): ?>
                    <?php if ($statusKey === 'pending' || $statusKey === 'late'): ?>
                        <a href="<?= base_url('receptionist/appointment/cancel/' . $appointmentId) ?>"
                           class="av-btn av-btn--danger-ghost"
                           onclick="return confirm('Cancel this appointment?')">
                            <i class="bi bi-x-lg" aria-hidden="true"></i>
                            <span>Cancel</span>
                        </a>
                        <a href="<?= base_url('receptionist/appointment/approve/' . $appointmentId) ?>"
                           class="av-btn av-btn--primary"
                           onclick="return confirm('Approve this appointment?')">
                            <i class="bi bi-check-lg" aria-hidden="true"></i>
                            <span>Approve</span>
                        </a>

                    <?php elseif ($statusKey === 'approved'): ?>
                        <a href="<?= base_url('receptionist/appointment/cancel/' . $appointmentId) ?>"
                           class="av-btn av-btn--danger-ghost"
                           onclick="return confirm('Cancel this appointment?')">
                            <i class="bi bi-x-lg" aria-hidden="true"></i>
                            <span>Cancel</span>
                        </a>
                        <a href="<?= base_url('receptionist/appointment/complete/' . $appointmentId) ?>"
                           class="av-btn av-btn--primary"
                           onclick="return confirm('Mark this appointment as completed?')">
                            <i class="bi bi-check2-all" aria-hidden="true"></i>
                            <span>Mark completed</span>
                        </a>
                    <?php endif; ?>
                <?php endif; ?>

            </div>
        </div>

        <!-- =========================================================
             HERO
             ========================================================= -->
        <section class="av-hero av-hero--<?= esc($status['tone'], 'attr') ?>">

            <div class="av-hero-main">
                <span class="av-avatar av-avatar--<?= esc($avatarKind, 'attr') ?>" aria-hidden="true">
                    <?php if ($isMale || $isFemale): ?>
                        <img src="<?= esc(base_url('assets/images/' . ($isMale ? $maleAvatar : $femaleAvatar)), 'attr') ?>"
                             alt=""
                             class="av-avatar-img"
                             loading="lazy"
                             decoding="async">
                    <?php else: ?>
                        <span class="av-avatar-initials"><?= esc($initialsOf($appointment['full_name'] ?? '')) ?></span>
                    <?php endif; ?>
                </span>

                <div class="av-hero-copy">
                    <h2 class="av-hero-name"><?= esc($appointment['full_name'] ?? 'Unknown') ?></h2>

                    <div class="av-hero-meta">
                        <span><i class="bi bi-gender-ambiguous" aria-hidden="true"></i><?= esc($appointment['gender'] ?? '—') ?></span>
                        <span><i class="bi bi-cake2" aria-hidden="true"></i><?= esc($appointment['age'] ?? '—') ?> years old</span>
                        <?php if (! empty($appointment['service_type'])): ?>
                            <span><i class="bi bi-clipboard2-pulse" aria-hidden="true"></i><?= esc(ucfirst($appointment['service_type'])) ?></span>
                        <?php endif; ?>
                    </div>

                    <?php if (! $isPatientView && ! empty($status['note'])): ?>
                        <p class="av-hero-note"><?= esc($status['note']) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <dl class="av-stats">
                <?php if (! $isPatientView): ?>
                    <div class="av-stat">
                        <dt>Date</dt>
                        <dd><?= esc($fmt($appointment['appointment_date'] ?? null, 'M d, Y') ?? '—') ?></dd>
                    </div>
                    <div class="av-stat">
                        <dt>Time</dt>
                        <dd><?= esc($appointment['appointment_time'] ?? '—') ?></dd>
                    </div>
                <?php else: ?>
                    <div class="av-stat">
                        <dt>Registered</dt>
                        <dd><?= esc($fmt($appointment['created_at'] ?? null, 'M d, Y') ?? '—') ?></dd>
                    </div>
                <?php endif; ?>

                <div class="av-stat">
                    <dt><?= $isPatientView ? 'Services on file' : 'Services' ?></dt>
                    <dd><?= $serviceTotal ?><span class="av-stat-unit"><?= $serviceTotal === 1 ? 'item' : 'items' ?></span></dd>
                </div>

                <div class="av-stat">
                    <dt>Arrival</dt>
                    <dd><?= esc($fmt($appointment['arrival_time'] ?? null, 'M d · h:i A') ?? 'Not recorded') ?></dd>
                </div>
            </dl>
        </section>

        <!-- =========================================================
             CONTENT — single aligned grid
             ========================================================= -->
        <div class="av-grid">

            <!-- ROW 1 · Contact & details (main) -->
            <section class="av-card av-cell av-cell--contact">
                <header class="av-card-head">
                    <h3 class="av-card-title">
                        <i class="bi bi-person-vcard" aria-hidden="true"></i>
                        Contact &amp; details
                    </h3>
                </header>

                <dl class="av-facts">
                    <div class="av-fact">
                        <dt>Email address</dt>
                        <dd>
                            <?php if (! empty($appointment['email'])): ?>
                                <a class="av-link" href="mailto:<?= esc($appointment['email'], 'attr') ?>">
                                    <?= esc($appointment['email']) ?>
                                </a>
                            <?php else: ?>
                                <span class="av-muted">Not provided</span>
                            <?php endif; ?>
                        </dd>
                    </div>

                    <div class="av-fact">
                        <dt>Phone number</dt>
                        <dd>
                            <?php if (! empty($appointment['phone'])): ?>
                                <a class="av-link" href="tel:<?= esc($appointment['phone'], 'attr') ?>">
                                    <?= esc($appointment['phone']) ?>
                                </a>
                            <?php else: ?>
                                <span class="av-muted">Not provided</span>
                            <?php endif; ?>
                        </dd>
                    </div>

                    <div class="av-fact">
                        <dt><?= $isPatientView ? 'Registered on' : 'Requested on' ?></dt>
                        <dd><?= esc($fmt($appointment['created_at'] ?? null) ?? '—') ?></dd>
                    </div>

                    <div class="av-fact">
                        <dt>Last updated</dt>
                        <dd><?= esc($fmt($appointment['updated_at'] ?? null) ?? '—') ?></dd>
                    </div>

                    <?php if (! $isPatientView): ?>
                        <div class="av-fact">
                            <dt>Scheduled for</dt>
                            <dd>
                                <?= esc($fmt($appointment['appointment_date'] ?? null, 'l, M d, Y') ?? '—') ?>
                                <?php if (! empty($appointment['appointment_time'])): ?>
                                    <span class="av-muted">· <?= esc($appointment['appointment_time']) ?></span>
                                <?php endif; ?>
                            </dd>
                        </div>
                    <?php endif; ?>

                    <div class="av-fact">
                        <dt><?= $isPatientView ? 'Patient code' : 'Reference number' ?></dt>
                        <dd><span class="av-mono"><?= esc($appointment['reference_number'] ?? '—') ?></span></dd>
                    </div>
                </dl>
            </section>

            <!-- ROW 1 · Quick actions (side) -->
            <section class="av-card av-cell av-cell--quick">
                <header class="av-card-head">
                    <h3 class="av-card-title">
                        <i class="bi bi-lightning-charge" aria-hidden="true"></i>
                        Quick actions
                    </h3>
                </header>

                <div class="av-quick">
                    <?php if (! empty($appointment['email'])): ?>
                        <a href="mailto:<?= esc($appointment['email'], 'attr') ?>" class="av-quick-item">
                            <span class="av-quick-icon"><i class="bi bi-envelope" aria-hidden="true"></i></span>
                            <span class="av-quick-copy">
                                <strong>Send email</strong>
                                <small><?= esc($appointment['email']) ?></small>
                            </span>
                            <i class="bi bi-chevron-right av-quick-chevron" aria-hidden="true"></i>
                        </a>
                    <?php endif; ?>

                    <?php if (! empty($appointment['phone'])): ?>
                        <a href="tel:<?= esc($appointment['phone'], 'attr') ?>" class="av-quick-item">
                            <span class="av-quick-icon"><i class="bi bi-telephone" aria-hidden="true"></i></span>
                            <span class="av-quick-copy">
                                <strong>Call patient</strong>
                                <small><?= esc($appointment['phone']) ?></small>
                            </span>
                            <i class="bi bi-chevron-right av-quick-chevron" aria-hidden="true"></i>
                        </a>
                    <?php endif; ?>

                    <?php if (! $isPatientView): ?>
                        <a href="<?= esc($backUrl, 'attr') ?>" class="av-quick-item">
                            <span class="av-quick-icon"><i class="bi bi-arrow-left" aria-hidden="true"></i></span>
                            <span class="av-quick-copy">
                                <strong>Back to <?= esc(strtolower($backLabel)) ?></strong>
                                <small>Return to the list</small>
                            </span>
                            <i class="bi bi-chevron-right av-quick-chevron" aria-hidden="true"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </section>

            <!-- ROW 2 · Service history (spans both columns) -->
            <section class="av-card av-cell av-cell--services">
                <header class="av-card-head">
                    <h3 class="av-card-title">
                        <i class="bi bi-clipboard2-check" aria-hidden="true"></i>
                        <?= $isPatientView ? 'Service history' : 'Selected services' ?>
                    </h3>
                    <?php if ($serviceTotal > 0): ?>
                        <span class="av-count"><?= $serviceTotal ?> total</span>
                    <?php endif; ?>
                </header>

                <?php if ($serviceTotal > 0): ?>
                    <div class="av-services">

                        <?php if (! empty($labServices)): ?>
                            <div class="av-service-group av-service-group--lab">
                                <h4 class="av-service-title">
                                    <span class="av-service-icon"><i class="bi bi-droplet-half" aria-hidden="true"></i></span>
                                    Laboratory tests
                                    <span class="av-service-count"><?= count($labServices) ?></span>
                                </h4>
                                <ul class="av-service-list">
                                    <?php foreach ($labServices as $service): ?>
                                        <li><?= esc($service) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <?php if (! empty($xrayServices)): ?>
                            <div class="av-service-group av-service-group--xray">
                                <h4 class="av-service-title">
                                    <span class="av-service-icon"><i class="bi bi-radioactive" aria-hidden="true"></i></span>
                                    X-Ray services
                                    <span class="av-service-count"><?= count($xrayServices) ?></span>
                                </h4>
                                <ul class="av-service-list">
                                    <?php foreach ($xrayServices as $service): ?>
                                        <li><?= esc($service) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                    </div>
                <?php else: ?>
                    <div class="av-empty av-empty--inline">
                        <div class="av-empty-icon"><i class="bi bi-inbox" aria-hidden="true"></i></div>
                        <p><?= $isPatientView ? 'No service history recorded yet.' : 'No services were selected for this appointment.' ?></p>
                    </div>
                <?php endif; ?>
            </section>

            <!-- ROW 3 · Activity (appointment view only) · spans both columns -->
            <?php if (! $isPatientView): ?>
                <section class="av-card av-cell av-cell--activity">
                    <header class="av-card-head">
                        <h3 class="av-card-title">
                            <i class="bi bi-activity" aria-hidden="true"></i>
                            Activity
                        </h3>
                    </header>

                    <ol class="av-timeline">
                        <li class="av-timeline-item av-timeline-item--teal">
                            <div class="av-timeline-body">
                                <strong>Request created</strong>
                                <time><?= esc($fmt($appointment['created_at'] ?? null) ?? '—') ?></time>
                            </div>
                        </li>

                        <?php if ($statusKey === 'approved' || $statusKey === 'completed'): ?>
                            <li class="av-timeline-item av-timeline-item--blue">
                                <div class="av-timeline-body">
                                    <strong>Approved</strong>
                                    <time><?= esc($fmt($appointment['arrival_time'] ?? null) ?? '—') ?></time>
                                </div>
                            </li>
                        <?php endif; ?>

                        <?php if ($statusKey === 'completed'): ?>
                            <li class="av-timeline-item av-timeline-item--green">
                                <div class="av-timeline-body">
                                    <strong>Completed</strong>
                                    <time><?= esc($fmt($appointment['updated_at'] ?? null) ?? '—') ?></time>
                                </div>
                            </li>
                        <?php endif; ?>

                        <?php if ($statusKey === 'cancelled'): ?>
                            <li class="av-timeline-item av-timeline-item--red">
                                <div class="av-timeline-body">
                                    <strong>Cancelled</strong>
                                    <time><?= esc($fmt($appointment['updated_at'] ?? null) ?? '—') ?></time>
                                </div>
                            </li>
                        <?php endif; ?>

                        <?php if ($statusKey === 'late'): ?>
                            <li class="av-timeline-item av-timeline-item--amber">
                                <div class="av-timeline-body">
                                    <strong>Marked as late</strong>
                                    <time><?= esc($fmt($appointment['updated_at'] ?? null) ?? '—') ?></time>
                                </div>
                            </li>
                        <?php endif; ?>

                        <?php if ($statusKey === 'pending'): ?>
                            <li class="av-timeline-item av-timeline-item--pending">
                                <div class="av-timeline-body">
                                    <strong>Awaiting approval</strong>
                                    <time>Next step</time>
                                </div>
                            </li>
                        <?php endif; ?>
                    </ol>

                    <?php if ($statusKey === 'completed'): ?>
                        <div class="av-state av-state--success">
                            <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
                            No further action needed.
                        </div>
                    <?php elseif ($statusKey === 'cancelled'): ?>
                        <div class="av-state av-state--danger">
                            <i class="bi bi-x-circle-fill" aria-hidden="true"></i>
                            This appointment is closed.
                        </div>
                    <?php endif; ?>
                </section>
            <?php endif; ?>

            <!-- NOTES · spans both columns -->
            <?php if (! empty($appointment['other_requests'])): ?>
                <section class="av-card av-cell av-cell--notes">
                    <header class="av-card-head">
                        <h3 class="av-card-title">
                            <i class="bi bi-chat-square-quote" aria-hidden="true"></i>
                            Other requests
                        </h3>
                    </header>
                    <p class="av-note-body"><?= esc($appointment['other_requests']) ?></p>
                </section>
            <?php endif; ?>

        </div>

    <?php else: ?>

        <div class="av-empty">
            <div class="av-empty-icon"><i class="bi bi-exclamation-circle" aria-hidden="true"></i></div>
            <h3><?= $isPatientView ? 'Patient not found' : 'Appointment not found' ?></h3>
            <p>
                <?= $isPatientView
                    ? 'The patient you tried to open does not exist or has been removed.'
                    : 'The appointment you tried to open does not exist or has been removed.' ?>
            </p>
            <?php if (! $isPatientView): ?>
                <a href="<?= esc($backUrl, 'attr') ?>" class="av-btn av-btn--primary">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i>
                    Back to <?= esc(strtolower($backLabel)) ?>
                </a>
            <?php endif; ?>
        </div>

    <?php endif; ?>

</div>

<style>
/* =========================================================
   APPOINTMENT / PATIENT VIEW
   ========================================================= */

.av {
    --av-ink:         #0f172a;
    --av-text:        #334155;
    --av-muted:       #64748b;
    --av-faint:       #94a3b8;
    --av-line:        #e5e9f0;
    --av-line-soft:   #f1f5f9;
    --av-surface:     #ffffff;
    --av-subtle:      #f8fafc;

    --av-accent:      #0d9488;
    --av-accent-dark: #0f766e;
    --av-accent-soft: #e6f7f6;

    --av-danger:      #dc2626;
    --av-danger-soft: #fef2f2;
    --av-success:     #059669;
    --av-amber:       #b45309;
    --av-blue:        #2563eb;

    --av-radius:      14px;
    --av-radius-sm:   9px;
    --av-shadow:      0 1px 2px rgba(15, 23, 42, 0.04), 0 1px 3px rgba(15, 23, 42, 0.03);
    --av-mono:        ui-monospace, SFMono-Regular, "SF Mono", Menlo, Consolas, monospace;

    color: var(--av-text);
    font-size: 0.875rem;
    line-height: 1.55;
}

.av *:focus-visible {
    outline: 2px solid var(--av-accent);
    outline-offset: 2px;
    border-radius: 4px;
}

.av-muted { color: var(--av-faint); }

.av-mono {
    font-family: var(--av-mono);
    font-size: 0.82rem;
    letter-spacing: 0.02em;
    color: var(--av-ink);
}

.av-link {
    color: var(--av-accent-dark);
    text-decoration: none;
    font-weight: 500;
    border-bottom: 1px solid transparent;
    transition: border-color 0.15s ease;
}

.av-link:hover { border-bottom-color: currentColor; }

/* =========================================================
   TOOLBAR
   ========================================================= */

.av-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
    padding-bottom: 1rem;
    margin-bottom: 1.25rem;
    border-bottom: 1px solid var(--av-line);
}

.av-toolbar-lead {
    display: flex;
    align-items: center;
    gap: 0.85rem;
    min-width: 0;
}

.av-toolbar-divider {
    width: 1px;
    height: 26px;
    background: var(--av-line);
    flex-shrink: 0;
}

.av-toolbar-id { min-width: 0; }

.av-toolbar-title {
    margin: 0;
    font-size: 1.0625rem;
    font-weight: 650;
    letter-spacing: -0.01em;
    color: var(--av-ink);
    line-height: 1.2;
}

.av-ref {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    font-size: 0.7rem;
    font-weight: 600;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: var(--av-faint);
}

.av-ref strong {
    font-family: var(--av-mono);
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: 0.02em;
    text-transform: none;
    color: var(--av-muted);
}

.av-toolbar-actions {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.av-back {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    height: 36px;
    padding: 0 0.85rem;
    font-size: 0.8125rem;
    font-weight: 600;
    color: var(--av-text);
    background: var(--av-surface);
    border: 1px solid var(--av-line);
    border-radius: var(--av-radius-sm);
    text-decoration: none;
    white-space: nowrap;
    transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
}

.av-back:hover {
    background: var(--av-subtle);
    border-color: #cbd5e1;
    color: var(--av-ink);
}

.av-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.45rem;
    height: 36px;
    padding: 0 0.95rem;
    font-size: 0.8125rem;
    font-weight: 600;
    font-family: inherit;
    border-radius: var(--av-radius-sm);
    border: 1px solid transparent;
    text-decoration: none;
    white-space: nowrap;
    cursor: pointer;
    transition: background-color 0.15s ease, border-color 0.15s ease,
                color 0.15s ease, box-shadow 0.15s ease, transform 0.12s ease;
}

.av-btn:active { transform: translateY(1px); }

.av-btn--primary {
    background: var(--av-accent);
    color: #ffffff;
    box-shadow: 0 1px 2px rgba(13, 148, 136, 0.35);
}

.av-btn--primary:hover {
    background: var(--av-accent-dark);
    color: #ffffff;
    box-shadow: 0 4px 12px rgba(13, 148, 136, 0.28);
}

.av-btn--ghost {
    background: var(--av-surface);
    border-color: var(--av-line);
    color: var(--av-text);
}

.av-btn--ghost:hover {
    background: var(--av-subtle);
    border-color: #cbd5e1;
    color: var(--av-ink);
}

.av-btn--danger-ghost {
    background: var(--av-surface);
    border-color: #fecaca;
    color: var(--av-danger);
}

.av-btn--danger-ghost:hover {
    background: var(--av-danger-soft);
    border-color: #fca5a5;
    color: #b91c1c;
}

.av-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    height: 28px;
    padding: 0 0.7rem;
    font-size: 0.75rem;
    font-weight: 600;
    border-radius: 99px;
    border: 1px solid transparent;
    white-space: nowrap;
}

.av-chip i { font-size: 0.85em; }

.av-chip--pending   { background: #fffbeb; color: #b45309; border-color: #fde68a; }
.av-chip--approved  { background: #eff6ff; color: #1d4ed8; border-color: #bfdbfe; }
.av-chip--completed { background: #ecfdf5; color: #047857; border-color: #a7f3d0; }
.av-chip--cancelled { background: #f8fafc; color: #64748b; border-color: #e2e8f0; }
.av-chip--late      { background: #fff7ed; color: #c2410c; border-color: #fed7aa; }

/* =========================================================
   HERO
   ========================================================= */

.av-hero {
    position: relative;
    background: var(--av-surface);
    border: 1px solid var(--av-line);
    border-radius: var(--av-radius);
    box-shadow: var(--av-shadow);
    margin-bottom: 1.25rem;
    overflow: hidden;
}

.av-hero::before {
    content: '';
    position: absolute;
    inset: 0 0 auto 0;
    height: 3px;
    background: var(--av-tone, var(--av-accent));
}

.av-hero--pending   { --av-tone: #f59e0b; }
.av-hero--approved  { --av-tone: #3b82f6; }
.av-hero--completed { --av-tone: #10b981; }
.av-hero--cancelled { --av-tone: #cbd5e1; }
.av-hero--late      { --av-tone: #ea580c; }

.av--patient .av-hero::before { background: var(--av-accent); }

.av-hero-main {
    display: flex;
    align-items: center;
    gap: 1.15rem;
    padding: 1.4rem 1.5rem;
}

.av-avatar {
    position: relative;
    display: grid;
    place-items: center;
    width: 72px;
    height: 72px;
    flex-shrink: 0;
    border-radius: 18px;
    background: var(--av-subtle);
    border: 1px solid var(--av-line);
    overflow: hidden;
    font-weight: 650;
    font-size: 1.15rem;
    color: var(--av-muted);
}

.av-avatar--male   { background: #eff6ff; border-color: #dbeafe; }
.av-avatar--female { background: #fdf2f8; border-color: #fce7f3; }

.av-avatar-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.av-hero-copy { min-width: 0; }

.av-hero-name {
    margin: 0 0 0.35rem;
    font-size: 1.35rem;
    font-weight: 650;
    letter-spacing: -0.02em;
    color: var(--av-ink);
    line-height: 1.2;
}

.av-hero-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 0.4rem 1rem;
    font-size: 0.8125rem;
    color: var(--av-muted);
}

.av-hero-meta span {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
}

.av-hero-meta i { color: var(--av-faint); font-size: 0.9em; }

.av-hero-note {
    margin: 0.55rem 0 0;
    font-size: 0.8125rem;
    color: var(--av-muted);
}

.av-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    margin: 0;
    border-top: 1px solid var(--av-line);
    background: var(--av-subtle);
}

.av-stat {
    padding: 0.85rem 1.5rem;
    border-right: 1px solid var(--av-line);
    min-width: 0;
}

.av-stat:last-child { border-right: none; }

.av-stat dt {
    font-size: 0.68rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: var(--av-faint);
    margin-bottom: 0.2rem;
}

.av-stat dd {
    margin: 0;
    font-size: 0.9375rem;
    font-weight: 600;
    color: var(--av-ink);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.av-stat-unit {
    margin-left: 0.3rem;
    font-size: 0.75rem;
    font-weight: 500;
    color: var(--av-faint);
}

/* =========================================================
   GRID — one aligned two-column matrix
   ========================================================= */

.av-grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 336px;
    gap: 1.25rem;
    align-items: stretch;   /* every card in a row shares the row height */
}

/* Default placement: main column */
.av-cell {
    min-width: 0;
    display: flex;
    flex-direction: column;
}

/* Row 1: Contact (col 1) · Quick actions (col 2) */
.av-cell--contact { grid-column: 1; grid-row: 1; }
.av-cell--quick   { grid-column: 2; grid-row: 1; }

/* Row 2: Service history spans both columns */
.av-cell--services {
    grid-column: 1 / -1;
    grid-row: 2;
}

/* Row 3: Activity spans both columns (appointment view only) */
.av-cell--activity {
    grid-column: 1 / -1;
    grid-row: 3;
}

/* Row 4: Notes spans both columns */
.av-cell--notes {
    grid-column: 1 / -1;
    grid-row: 4;
}

/* When in patient view there is no Activity row,
   Notes naturally falls back to row 3 via auto-flow. */
.av--patient .av-cell--notes { grid-row: auto; }

/* =========================================================
   CARDS
   ========================================================= */

.av-card {
    background: var(--av-surface);
    border: 1px solid var(--av-line);
    border-radius: var(--av-radius);
    box-shadow: var(--av-shadow);
    overflow: hidden;
    height: 100%;             /* fill the row so left/right edges line up */
}

.av-card-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    padding: 0.9rem 1.25rem;
    border-bottom: 1px solid var(--av-line-soft);
    background: linear-gradient(180deg, #fcfdfe, #ffffff);
}

.av-card-title {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin: 0;
    font-size: 0.8125rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: var(--av-ink);
}

.av-card-title i {
    font-size: 0.95rem;
    color: var(--av-accent);
}

.av-count {
    font-size: 0.72rem;
    font-weight: 600;
    color: var(--av-muted);
    background: var(--av-subtle);
    border: 1px solid var(--av-line);
    border-radius: 99px;
    padding: 0.1rem 0.55rem;
    white-space: nowrap;
}

/* =========================================================
   FACT LIST — 3-column aligned grid inside contact card
   ========================================================= */

.av-facts {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 0;
    margin: 0;
    flex: 1;                  /* fill the card even if the row is taller */
}

.av-fact {
    padding: 0.9rem 1.25rem;
    border-bottom: 1px solid var(--av-line-soft);
    border-right: 1px solid var(--av-line-soft);
    min-width: 0;
}

.av-fact:nth-child(3n)      { border-right: none; }
.av-fact:nth-last-child(-n+3):nth-child(3n),
.av-fact:last-child          { border-bottom: none; }

.av-fact dt {
    font-size: 0.68rem;
    font-weight: 700;
    letter-spacing: 0.07em;
    text-transform: uppercase;
    color: var(--av-faint);
    margin-bottom: 0.25rem;
}

.av-fact dd {
    margin: 0;
    font-size: 0.875rem;
    color: var(--av-ink);
    word-break: break-word;
}

/* On patient view there is 1 fewer fact, so the grid stays
   3 columns but the trailing cell keeps its border-none rule. */
.av--patient .av-facts { grid-template-columns: repeat(3, minmax(0, 1fr)); }

/* =========================================================
   SERVICES
   ========================================================= */

.av-services {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
}

.av-service-group {
    padding: 1.1rem 1.25rem;
    border-right: 1px solid var(--av-line-soft);
}

.av-service-group:last-child { border-right: none; }

.av-service-title {
    display: flex;
    align-items: center;
    gap: 0.55rem;
    margin: 0 0 0.75rem;
    font-size: 0.8125rem;
    font-weight: 650;
    color: var(--av-ink);
}

.av-service-icon {
    display: grid;
    place-items: center;
    width: 26px;
    height: 26px;
    border-radius: 8px;
    font-size: 0.8rem;
    flex-shrink: 0;
}

.av-service-group--lab  .av-service-icon { background: #fef2f2; color: #dc2626; }
.av-service-group--xray .av-service-icon { background: #eef2ff; color: #4f46e5; }

.av-service-count {
    margin-left: auto;
    font-size: 0.7rem;
    font-weight: 700;
    color: var(--av-muted);
    background: var(--av-subtle);
    border-radius: 99px;
    padding: 0.05rem 0.45rem;
    min-width: 22px;
    text-align: center;
}

.av-service-list {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 0.3rem;
}

.av-service-list li {
    position: relative;
    padding: 0.45rem 0.7rem 0.45rem 1.65rem;
    font-size: 0.8125rem;
    color: var(--av-text);
    background: var(--av-subtle);
    border: 1px solid var(--av-line-soft);
    border-radius: var(--av-radius-sm);
}

.av-service-list li::before {
    content: '';
    position: absolute;
    left: 0.7rem;
    top: 50%;
    width: 5px;
    height: 5px;
    border-radius: 50%;
    transform: translateY(-50%);
    background: var(--av-faint);
}

.av-service-group--lab  .av-service-list li::before { background: #dc2626; }
.av-service-group--xray .av-service-list li::before { background: #4f46e5; }

/* =========================================================
   NOTE
   ========================================================= */

.av-note-body {
    margin: 0;
    padding: 1.1rem 1.25rem;
    font-size: 0.875rem;
    color: var(--av-text);
    white-space: pre-line;
    border-left: 3px solid var(--av-accent-soft);
}

/* =========================================================
   TIMELINE
   ========================================================= */

.av-timeline {
    list-style: none;
    margin: 0;
    padding: 1.15rem 1.25rem 1.15rem 1.35rem;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 0.85rem;
}

.av-timeline-item {
    position: relative;
    padding: 0 0 0 1.4rem;
    border-left: 2px solid var(--av-line);
}

.av-timeline-item::before {
    content: '';
    position: absolute;
    left: -6px;
    top: 3px;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: var(--av-dot, var(--av-faint));
    box-shadow: 0 0 0 3px #ffffff, 0 0 0 4px var(--av-dot, var(--av-faint));
}

.av-timeline-item--teal    { --av-dot: #0d9488; }
.av-timeline-item--blue    { --av-dot: #2563eb; }
.av-timeline-item--green   { --av-dot: #059669; }
.av-timeline-item--red     { --av-dot: #dc2626; }
.av-timeline-item--amber   { --av-dot: #ea580c; }
.av-timeline-item--pending { --av-dot: #cbd5e1; }

.av-timeline-body {
    display: flex;
    flex-direction: column;
    gap: 0.1rem;
    margin-top: -0.2rem;
}

.av-timeline-body strong {
    font-size: 0.82rem;
    font-weight: 600;
    color: var(--av-ink);
}

.av-timeline-body time {
    font-size: 0.75rem;
    color: var(--av-faint);
}

/* =========================================================
   STATE STRIP
   ========================================================= */

.av-state {
    display: flex;
    align-items: center;
    gap: 0.55rem;
    margin: 0 1.25rem 1.25rem;
    padding: 0.7rem 0.9rem;
    font-size: 0.8125rem;
    font-weight: 500;
    border-radius: var(--av-radius-sm);
    border: 1px solid transparent;
}

.av-state--success { background: #ecfdf5; color: #047857; border-color: #a7f3d0; }
.av-state--danger  { background: var(--av-danger-soft); color: #b91c1c; border-color: #fecaca; }

.av-state i { font-size: 1rem; flex-shrink: 0; }

/* =========================================================
   QUICK ACTIONS
   ========================================================= */

.av-quick {
    padding: 0.5rem;
    display: flex;
    flex-direction: column;
    flex: 1;
}

.av-quick-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    width: 100%;
    padding: 0.6rem 0.7rem;
    background: transparent;
    border: none;
    border-radius: var(--av-radius-sm);
    text-align: left;
    text-decoration: none;
    font-family: inherit;
    color: var(--av-text);
    cursor: pointer;
    transition: background-color 0.15s ease;
}

.av-quick-item:hover { background: var(--av-subtle); }

.av-quick-item:hover .av-quick-chevron {
    opacity: 1;
    transform: translateX(0);
}

.av-quick-icon {
    display: grid;
    place-items: center;
    width: 34px;
    height: 34px;
    flex-shrink: 0;
    border-radius: 9px;
    background: var(--av-accent-soft);
    color: var(--av-accent-dark);
    font-size: 0.9rem;
}

.av-quick-copy {
    display: flex;
    flex-direction: column;
    min-width: 0;
    flex: 1;
}

.av-quick-copy strong {
    font-size: 0.8125rem;
    font-weight: 600;
    color: var(--av-ink);
}

.av-quick-copy small {
    font-size: 0.72rem;
    color: var(--av-faint);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.av-quick-chevron {
    font-size: 0.75rem;
    color: var(--av-faint);
    opacity: 0;
    transform: translateX(-4px);
    transition: opacity 0.15s ease, transform 0.15s ease;
}

/* =========================================================
   EMPTY STATES
   ========================================================= */

.av-empty {
    text-align: center;
    padding: 3.5rem 1.5rem;
    background: var(--av-surface);
    border: 1px solid var(--av-line);
    border-radius: var(--av-radius);
    box-shadow: var(--av-shadow);
}

.av-empty--inline {
    padding: 2.25rem 1.25rem;
    border: none;
    box-shadow: none;
    background: transparent;
    text-align: center;
}

.av-empty-icon {
    display: grid;
    place-items: center;
    width: 48px;
    height: 48px;
    margin: 0 auto 0.85rem;
    font-size: 1.3rem;
    color: var(--av-faint);
    background: var(--av-subtle);
    border-radius: 12px;
}

.av-empty h3 {
    margin: 0 0 0.35rem;
    font-size: 1rem;
    font-weight: 650;
    color: var(--av-ink);
}

.av-empty p {
    max-width: 30rem;
    margin: 0 auto 1.25rem;
    font-size: 0.8125rem;
    color: var(--av-muted);
}

.av-empty--inline p { margin-bottom: 0; }

/* =========================================================
   RESPONSIVE
   ========================================================= */

@media (max-width: 1180px) {
    .av-grid { grid-template-columns: minmax(0, 1fr); }

    /* Collapse to a single column — every cell just stacks */
    .av-cell--contact,
    .av-cell--quick,
    .av-cell--services,
    .av-cell--activity,
    .av-cell--notes {
        grid-column: 1;
        grid-row: auto;
    }
}

@media (max-width: 900px) {
    .av-facts { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .av-fact:nth-child(3n) { border-right: 1px solid var(--av-line-soft); }
    .av-fact:nth-child(2n) { border-right: none; }
}

@media (max-width: 768px) {
    .av-toolbar {
        align-items: flex-start;
        flex-direction: column;
        gap: 0.85rem;
    }

    .av-toolbar-actions { width: 100%; }
    .av-toolbar-actions .av-btn { flex: 1; }

    .av-hero-main { padding: 1.15rem; gap: 0.9rem; }
    .av-hero-name { font-size: 1.15rem; }

    .av-avatar { width: 60px; height: 60px; border-radius: 15px; }

    .av-stat { padding: 0.75rem 1.15rem; }

    .av-fact,
    .av-service-group { border-right: none; }
}

@media (max-width: 520px) {
    .av-toolbar-lead { width: 100%; }
    .av-toolbar-divider { display: none; }

    .av-hero-main {
        flex-direction: column;
        align-items: flex-start;
        text-align: left;
    }

    .av-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .av-stat dd { white-space: normal; font-size: 0.875rem; }
    .av-stat:nth-child(2n) { border-right: none; }
    .av-stat { border-bottom: 1px solid var(--av-line); }

    .av-facts { grid-template-columns: minmax(0, 1fr); }
    .av-fact { border-right: none; }

    .av-chip { order: -1; }
}

@media (prefers-reduced-motion: reduce) {
    .av *, .av *::before, .av *::after { transition: none !important; }
}

/* =========================================================
   PRINT
   ========================================================= */

@media print {
    .av-toolbar,
    .av-quick,
    .av-col-side .av-state { display: none !important; }

    .av { font-size: 11pt; color: #000; }

    .av-grid {
        grid-template-columns: minmax(0, 1fr);
        gap: 0.75rem;
    }

    .av-card,
    .av-hero,
    .av-empty {
        box-shadow: none !important;
        border: 1px solid #cbd5e1 !important;
        break-inside: avoid;
    }

    .av-card-head { background: none !important; }

    .av-hero::before,
    .av-quick-chevron { display: none; }

    .av-stats { background: none !important; }

    a[href^="mailto"]::after,
    a[href^="tel"]::after { content: ''; }
}
</style>

<?= $this->endSection() ?>