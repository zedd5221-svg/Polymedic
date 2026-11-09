<?php
/* ------------------------------------------------------------------
   Appointment / patient detail page body, shared by Admin and
   Receptionist (included from {Role}/appointment_view.php).

   Data: $role ('admin' | 'receptionist'), $appointment, $viewMode
   ('appointment' | 'patient'), $lab_services, $xray_services,
   $patient (appointment mode: the matched patient record, or null).
   Every fact appears once: identity in the header, the request facts
   in one row, then services, contact and activity.
   ------------------------------------------------------------------ */
    $appointment   = $appointment ?? [];
    $isPatientView = ($viewMode ?? 'appointment') === 'patient';
    $isAdmin       = ($role ?? '') === 'admin';
    $base          = $isAdmin ? 'admin' : 'receptionist';
    $patientRow    = $patient ?? null;

    $initials = static function ($name): string {
        $parts = preg_split('/\s+/', trim((string) $name));
        $first = mb_substr($parts[0] ?? '', 0, 1);
        $last  = count($parts) > 1 ? mb_substr(end($parts), 0, 1) : '';
        return mb_strtoupper($first . $last) ?: '?';
    };

    $fmt = static function ($value, string $format = 'M j, Y · g:i A'): ?string {
        if (empty($value)) { return null; }
        $ts = strtotime((string) $value);
        return $ts ? date($format, $ts) : null;
    };

    $statusKey = (string) ($appointment['status'] ?? 'pending');
    $statusMeta = [
        'pending'   => ['Pending',   'pending'],
        'approved'  => ['Approved',  'approved'],
        'cancelled' => ['Cancelled', 'cancelled'],
        'late'      => ['Late',      'late'],
        'no_show'   => ['No show',   'cancelled'],
    ];
    [$statusLabel, $statusTone] = $statusMeta[$statusKey] ?? [ucfirst($statusKey), 'pending'];

    $labServices  = $lab_services  ?? [];
    $xrayServices = $xray_services ?? [];
    $serviceTotal = count($labServices) + count($xrayServices);

    $id       = (int) ($appointment['id'] ?? 0);
    $backUrl  = base_url($base . ($isPatientView ? '/patients' : '/appointments'));
    $backText = $isPatientView ? 'Patients' : 'Appointments';

    $demo = implode(' · ', array_filter([
        ($appointment['age'] ?? '') !== '' ? $appointment['age'] . ' y' : '',
        trim((string) ($appointment['gender'] ?? '')),
    ]));

    /* Source: patient view carries it in service_type; appointments are online
       bookings unless the front desk entered an override booking. */
    if ($isPatientView) {
        $sourceKey = stripos((string) ($appointment['service_type'] ?? ''), 'walk') !== false ? 'walkin' : 'online';
    } else {
        $sourceKey = !empty($appointment['is_override']) ? 'override' : 'online';
    }
    $sourceText = ['online' => 'Online', 'walkin' => 'Walk-in', 'override' => 'Front-desk booking'][$sourceKey];

    $serviceType = strtolower((string) ($appointment['service_type'] ?? ''));
    $serviceText = ['laboratory' => 'Laboratory', 'lab' => 'Laboratory', 'xray' => 'X-Ray', 'both' => 'Laboratory + X-Ray'][$serviceType] ?? '';

    $patientCode = $isPatientView
        ? (string) ($appointment['reference_number'] ?? '')
        : (string) ($patientRow['patient_code'] ?? '');
    $patientUrl  = (!$isPatientView && !empty($patientRow['id'])) ? base_url($base . '/patient/view/' . (int) $patientRow['id']) : null;

    $dash = '<span class="av-muted">&mdash;</span>';
?>

<div class="av">

    <a href="<?= esc($backUrl, 'attr') ?>" class="av-back">
        <i class="bi bi-arrow-left" aria-hidden="true"></i><?= esc($backText) ?>
    </a>

    <?php if (empty($appointment)): ?>

        <div class="av-card av-empty">
            <i class="bi bi-exclamation-circle" aria-hidden="true"></i>
            <h3><?= $isPatientView ? 'Patient not found' : 'Appointment not found' ?></h3>
            <p>It does not exist or has been removed.</p>
        </div>

    <?php else: ?>

        <!-- ===== SUMMARY: who, and the request facts ===== -->
        <section class="av-card av-summary" aria-label="Summary">
            <header class="av-head">
                <div class="av-head-main">
                    <span class="av-avatar" aria-hidden="true"><?= esc($initials($appointment['full_name'] ?? '')) ?></span>
                    <div class="av-head-text">
                        <h1 class="av-name"><?= esc($appointment['full_name'] ?? 'Unknown') ?></h1>
                        <p class="av-meta">
                            <?php if ($demo !== ''): ?>
                                <span><?= esc($demo) ?></span>
                                <span class="av-sep" aria-hidden="true">·</span>
                            <?php endif; ?>
                            <span class="av-src av-src--<?= $sourceKey ?>">
                                <i class="bi <?= $sourceKey === 'walkin' ? 'bi-person-walking' : ($sourceKey === 'override' ? 'bi-shield-check' : 'bi-globe2') ?>" aria-hidden="true"></i><?= esc($sourceText) ?>
                            </span>
                            <?php if (!$isPatientView): ?>
                                <span class="av-sep" aria-hidden="true">·</span>
                                <span class="av-mono"><?= esc($appointment['reference_number'] ?? '—') ?></span>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>

                <?php if (!$isPatientView): ?>
                    <div class="av-head-side">
                        <span class="av-status av-status--<?= esc($statusTone, 'attr') ?>"><?= esc($statusLabel) ?></span>

                        <?php if ($statusKey === 'pending' || $statusKey === 'late'): ?>
                            <a data-post href="<?= base_url($base . '/appointment/cancel/' . $id) ?>" class="av-btn av-btn--quiet"
                               onclick="return confirm('Cancel this appointment?')">Cancel</a>
                            <a data-post href="<?= base_url($base . '/appointment/approve/' . $id) ?>" class="av-btn av-btn--primary"
                               onclick="return confirm('Approve this appointment?')">
                                <i class="bi bi-check2" aria-hidden="true"></i>Approve
                            </a>
                        <?php elseif ($statusKey === 'approved'): ?>
                            <a data-post href="<?= base_url($base . '/appointment/cancel/' . $id) ?>" class="av-btn av-btn--quiet"
                               onclick="return confirm('Cancel this appointment?')">Cancel</a>
                        <?php endif; ?>

                        <?php if ($isAdmin): ?>
                            <a data-post href="<?= base_url('admin/appointment/delete/' . $id) ?>" class="av-icon-btn av-icon-btn--danger"
                               title="Delete appointment" aria-label="Delete appointment"
                               onclick="return confirm('Permanently delete this appointment? This cannot be undone.')">
                                <i class="bi bi-trash3" aria-hidden="true"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </header>

            <dl class="av-facts">
                <?php if ($isPatientView): ?>
                    <div class="av-fact"><dt>Patient code</dt><dd class="av-mono"><?= esc($patientCode ?: '—') ?></dd></div>
                    <div class="av-fact"><dt>Registered</dt><dd><?= esc($fmt($appointment['created_at'] ?? null, 'M j, Y') ?? '—') ?></dd></div>
                    <div class="av-fact"><dt>Last updated</dt><dd><?= esc($fmt($appointment['updated_at'] ?? null) ?? '—') ?></dd></div>
                    <div class="av-fact"><dt>Services on file</dt><dd><?= $serviceTotal ?></dd></div>
                <?php else: ?>
                    <div class="av-fact av-fact--wide">
                        <dt>Scheduled</dt>
                        <dd>
                            <?= esc($fmt($appointment['appointment_date'] ?? null, 'D, M j, Y') ?? '—') ?>
                            <?php if (!empty($appointment['appointment_time'])): ?>
                                <span class="av-muted">· <?= esc($fmt($appointment['appointment_time'], 'g:i A') ?? $appointment['appointment_time']) ?></span>
                            <?php endif; ?>
                        </dd>
                    </div>
                    <div class="av-fact"><dt>Service</dt><dd><?= $serviceText !== '' ? esc($serviceText) : $dash ?></dd></div>
                    <div class="av-fact"><dt>Booked</dt><dd><?= esc($fmt($appointment['created_at'] ?? null) ?? '—') ?></dd></div>
                    <div class="av-fact"><dt>Arrived</dt><dd><?= ($a = $fmt($appointment['arrival_time'] ?? null)) ? esc($a) : '<span class="av-muted">Not yet</span>' ?></dd></div>
                    <div class="av-fact"><dt>Patient code</dt><dd class="av-mono"><?= $patientCode !== '' ? esc($patientCode) : $dash ?></dd></div>
                <?php endif; ?>
            </dl>
        </section>

        <div class="av-grid">

            <!-- ===== MAIN: services + notes ===== -->
            <div class="av-main">
                <section class="av-card" aria-labelledby="avSvcTitle">
                    <header class="av-card-head">
                        <h2 id="avSvcTitle"><?= $isPatientView ? 'Service history' : 'Requested services' ?></h2>
                        <?php if ($serviceTotal > 0): ?><span class="av-count"><?= $serviceTotal ?></span><?php endif; ?>
                    </header>

                    <?php if ($serviceTotal > 0): ?>
                        <div class="av-services">
                            <?php foreach ([['Laboratory', 'lab', $labServices], ['X-Ray', 'xray', $xrayServices]] as [$label, $kind, $list]): ?>
                                <?php if ($list): ?>
                                    <div class="av-svc-group">
                                        <h3 class="av-svc-head av-svc-head--<?= $kind ?>"><?= $label ?> <span><?= count($list) ?></span></h3>
                                        <ul class="av-svc-list">
                                            <?php foreach ($list as $service): ?>
                                                <li><?= esc($service) ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="av-none"><?= $isPatientView ? 'No services on file yet.' : 'No services were selected for this appointment.' ?></p>
                    <?php endif; ?>
                </section>

                <?php if (!empty($appointment['other_requests'])): ?>
                    <section class="av-card" aria-labelledby="avNoteTitle">
                        <header class="av-card-head"><h2 id="avNoteTitle">Notes from the patient</h2></header>
                        <p class="av-note"><?= nl2br(esc($appointment['other_requests'])) ?></p>
                    </section>
                <?php endif; ?>
            </div>

            <!-- ===== SIDE: contact + activity ===== -->
            <aside class="av-side">
                <section class="av-card" aria-labelledby="avContactTitle">
                    <header class="av-card-head"><h2 id="avContactTitle">Contact</h2></header>
                    <dl class="av-list">
                        <div>
                            <dt>Email</dt>
                            <dd><?= !empty($appointment['email'])
                                ? '<a class="av-link" href="mailto:' . esc($appointment['email'], 'attr') . '">' . esc($appointment['email']) . '</a>'
                                : '<span class="av-muted">Not provided</span>' ?></dd>
                        </div>
                        <div>
                            <dt>Phone</dt>
                            <dd><?= !empty($appointment['phone'])
                                ? '<a class="av-link" href="tel:' . esc($appointment['phone'], 'attr') . '">' . esc($appointment['phone']) . '</a>'
                                : '<span class="av-muted">Not provided</span>' ?></dd>
                        </div>
                    </dl>
                    <?php if ($patientUrl): ?>
                        <a class="av-row-link" href="<?= esc($patientUrl, 'attr') ?>">
                            Open patient record <span class="av-mono"><?= esc($patientCode) ?></span>
                            <i class="bi bi-chevron-right" aria-hidden="true"></i>
                        </a>
                    <?php endif; ?>
                </section>

                <?php if (!$isPatientView): ?>
                    <?php
                        /* Steps: done / current / upcoming / stopped */
                        $bookedLabel = 'Booked online';
                        if ($sourceKey === 'override') {
                            $bookedLabel = 'Override booking'
                                . (!empty($appointment['override_by']) ? ' by ' . $appointment['override_by'] : '')
                                . (!empty($appointment['override_reason']) ? ' · ' . $appointment['override_reason'] : '');
                        }
                        $steps = [[$bookedLabel, $fmt($appointment['created_at'] ?? null), 'done']];
                        if ($statusKey === 'cancelled' || $statusKey === 'no_show') {
                            $steps[] = [$statusKey === 'no_show' ? 'Did not show up' : 'Cancelled', $fmt($appointment['updated_at'] ?? null), 'stopped'];
                        } elseif ($statusKey === 'late') {
                            $steps[] = ['Marked late', $fmt($appointment['updated_at'] ?? null), 'warn'];
                            $steps[] = ['Approve to send for tests', null, 'current'];
                        } elseif ($statusKey === 'pending') {
                            $steps[] = ['Awaiting approval', null, 'current'];
                            $steps[] = ['Sent for lab / X-ray tests', null, 'upcoming'];
                        } else {
                            // Approval is the last appointment step; test progress lives on the requests.
                            $steps[] = ['Approved · patient arrived', $fmt($appointment['arrival_time'] ?? null), 'done'];
                            $steps[] = ['Sent for lab / X-ray tests', null, 'done'];
                        }
                    ?>
                    <section class="av-card" aria-labelledby="avActTitle">
                        <header class="av-card-head"><h2 id="avActTitle">Activity</h2></header>
                        <ol class="av-steps">
                            <?php foreach ($steps as [$what, $when, $state]): ?>
                                <li class="av-step av-step--<?= $state ?>">
                                    <span class="av-step-what"><?= esc($what) ?></span>
                                    <?php if ($when): ?><time class="av-step-when"><?= esc($when) ?></time><?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                    </section>
                <?php endif; ?>
            </aside>
        </div>

    <?php endif; ?>
</div>

<style>
.av {
    --av-ink:    var(--pm-text, #10202E);
    --av-text:   var(--pm-text-soft, #3B4C5E);
    --av-muted:  var(--pm-text-muted, #5D6E80);
    --av-faint:  var(--pm-text-muted);
    --av-line:   var(--pm-line, #DDE4EC);
    --av-strong: var(--pm-line-strong, #C3CFDB);
    --av-soft:   var(--pm-subtle);
    --av-subtle: var(--pm-subtle);
    --av-accent: var(--pm-accent, #175563);
    --av-accent-dark: var(--pm-accent-hover, #10404B);
    --av-mono:   var(--pm-mono, ui-monospace, monospace);

    max-width: 1120px;
    margin: 0 auto;
    color: var(--av-text);
    font-size: 0.875rem;
}

.av *:focus-visible { outline: 2px solid var(--av-accent); outline-offset: 2px; }

.av-muted { color: var(--av-faint); }
.av-mono  { font-family: var(--av-mono); font-size: 0.78rem; }

.av-back {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    margin-bottom: 0.75rem;
    font-size: 0.8rem;
    font-weight: 500;
    color: var(--av-muted);
    text-decoration: none;
}

.av-back:hover { color: var(--av-ink); }

.av-card {
    background: var(--pm-surface);
    border: 1px solid var(--av-line);
    border-radius: 10px;
}

/* ---------- Summary ---------- */

.av-summary { margin-bottom: 1rem; overflow: hidden; }

.av-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
    padding: 1.1rem 1.25rem;
}

.av-head-main { display: flex; align-items: center; gap: 0.9rem; min-width: 0; }

.av-avatar {
    display: grid;
    place-items: center;
    flex-shrink: 0;
    width: 48px;
    height: 48px;
    font-size: 0.9rem;
    font-weight: 600;
    letter-spacing: 0.03em;
    color: var(--av-ink);
    background: var(--pm-avatar);
    border-radius: 50%;
}

.av-head-text { min-width: 0; }

.av-name {
    margin: 0;
    font-size: 1.3rem;
    font-weight: 600;
    letter-spacing: -0.015em;
    line-height: 1.25;
    color: var(--av-ink);
}

.av-meta {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.25rem 0.45rem;
    margin: 0.3rem 0 0;
    font-size: 0.8125rem;
    color: var(--av-muted);
}

.av-sep { color: var(--av-strong); }

/* Same source colours as the worklists and the Patients page */
.av-src {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    padding: 0.05rem 0.4rem;
    font-size: 0.72rem;
    font-weight: 600;
    border-radius: 4px;
}

.av-src i { font-size: 0.75rem; }
.av-src--online   { color: var(--pm-dk-fg-teal, #10404B); background: var(--pm-dk-bg-teal, #EBF2F4); }
.av-src--walkin   { color: var(--pm-dk-fg-amber, #8A5A00); background: var(--pm-dk-bg-amber, #FDF3DC); }
.av-src--override { color: var(--pm-text-soft); background: var(--pm-subtle); }

.av-head-side { display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; }

.av-status {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    margin-right: 0.25rem;
    padding: 0.2rem 0.55rem;
    font-size: 0.74rem;
    font-weight: 600;
    white-space: nowrap;
    color: var(--fg);
    background: var(--bg);
    border-radius: 4px;
}

.av-status::before { content: ""; width: 6px; height: 6px; border-radius: 50%; background: var(--dot); }

.av-status--pending   { --bg: #FEF6E7; --fg: #93370D; --dot: #DC6803; }
.av-status--approved  { --bg: #EBF2F4; --fg: #10404B; --dot: #175563; }
.av-status--cancelled { --bg: #F2F4F7; --fg: #475467; --dot: #98A2B3; }
.av-status--late      { --bg: #FEF3F2; --fg: #B42318; --dot: #F04438; }

.av-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    height: 34px;
    padding: 0 0.85rem;
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--av-ink);
    background: var(--pm-surface);
    border: 1px solid var(--av-line);
    border-radius: 6px;
    text-decoration: none;
    white-space: nowrap;
    transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
}

.av-btn:hover { color: var(--av-ink); background: var(--av-subtle); border-color: var(--av-strong); }

.av-btn--primary { color: #FFFFFF; background: var(--av-accent); border-color: var(--av-accent); }
.av-btn--primary:hover { color: #FFFFFF; background: var(--av-accent-dark); border-color: var(--av-accent-dark); }

.av-btn--quiet { color: var(--av-muted); border-color: transparent; background: transparent; }
.av-btn--quiet:hover { color: var(--pm-dk-fg-red, #B42318); background: var(--pm-dk-bg-red, #FEF3F2); border-color: transparent; }

.av-icon-btn {
    display: inline-grid;
    place-items: center;
    width: 34px;
    height: 34px;
    color: var(--av-muted);
    border: 1px solid transparent;
    border-radius: 6px;
    text-decoration: none;
}

.av-icon-btn--danger:hover { color: var(--pm-dk-fg-red, #B42318); background: var(--pm-dk-bg-red, #FEF3F2); }

/* Request facts: one row, hairline cells, wraps on small screens */
.av-facts {
    display: flex;
    flex-wrap: wrap;
    margin: 0;
    overflow: hidden;
    border-top: 1px solid var(--av-soft);
}

.av-fact {
    flex: 1 1 150px;
    min-width: 0;
    margin: 0 0 0 -1px;
    padding: 0.7rem 1.25rem 0.75rem;
    border-left: 1px solid var(--av-soft);
    box-shadow: 0 -1px 0 0 var(--av-soft);
}

.av-fact--wide { flex: 2 1 220px; }

.av-fact dt {
    margin: 0;
    font-size: 0.66rem;
    font-weight: 600;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: var(--av-faint);
}

.av-fact dd {
    margin: 0.2rem 0 0;
    font-size: 0.85rem;
    font-weight: 500;
    color: var(--av-ink);
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.av-fact--wide dd { font-weight: 600; }

/* ---------- Layout ---------- */

.av-grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 340px;
    gap: 1rem;
    align-items: start;
}

.av-main,
.av-side { display: flex; flex-direction: column; gap: 1rem; min-width: 0; }

.av-card-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    padding: 0.8rem 1.15rem;
    border-bottom: 1px solid var(--av-soft);
}

.av-card-head h2 {
    margin: 0;
    font-size: 0.72rem;
    font-weight: 600;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: var(--av-muted);
}

.av-count {
    min-width: 1.4rem;
    padding: 0 0.35rem;
    font-size: 0.72rem;
    font-weight: 600;
    text-align: center;
    color: var(--av-text);
    background: var(--av-soft);
    border-radius: 4px;
    font-variant-numeric: tabular-nums;
}

.av-none { margin: 0; padding: 1.1rem 1.15rem; font-size: 0.82rem; color: var(--av-faint); }

/* ---------- Services ---------- */

.av-services {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
}

.av-svc-group { padding: 0.9rem 1.15rem 1rem; }
.av-svc-group + .av-svc-group { border-left: 1px solid var(--av-soft); }

.av-svc-head {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    margin: 0 0 0.6rem;
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--av-ink);
}

.av-svc-head::before {
    content: "";
    width: 8px;
    height: 8px;
    border-radius: 2px;
    background: var(--kind);
}

.av-svc-head--lab  { --kind: #175563; }
.av-svc-head--xray { --kind: #0E8A7E; }

.av-svc-head span {
    margin-left: auto;
    font-size: 0.72rem;
    font-weight: 500;
    color: var(--av-faint);
    font-variant-numeric: tabular-nums;
}

.av-svc-list {
    display: grid;
    gap: 0.35rem;
    margin: 0;
    padding: 0;
    list-style: none;
}

.av-svc-list li {
    padding: 0.45rem 0.6rem;
    font-size: 0.82rem;
    color: var(--av-ink);
    background: var(--av-subtle);
    border: 1px solid var(--av-soft);
    border-radius: 6px;
}

.av-note {
    margin: 0;
    padding: 1rem 1.15rem;
    font-size: 0.85rem;
    line-height: 1.6;
    color: var(--av-text);
}

/* ---------- Contact ---------- */

.av-list { display: grid; gap: 0.7rem; margin: 0; padding: 0.9rem 1.15rem 1rem; }
.av-list dt { font-size: 0.74rem; font-weight: 500; color: var(--av-muted); }
.av-list dd { margin: 0.1rem 0 0; font-size: 0.85rem; color: var(--av-ink); overflow-wrap: anywhere; }

.av-link { color: var(--av-ink); text-decoration: none; }
.av-link:hover { color: var(--av-accent); text-decoration: underline; text-underline-offset: 2px; }

.av-row-link {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.7rem 1.15rem;
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--av-accent);
    text-decoration: none;
    border-top: 1px solid var(--av-soft);
}

.av-row-link .av-mono { font-weight: 500; color: var(--av-muted); }
.av-row-link i { margin-left: auto; font-size: 0.75rem; }
.av-row-link:hover { color: var(--av-accent-dark); background: var(--av-subtle); }

/* ---------- Activity: a vertical stepper ---------- */

.av-steps {
    margin: 0;
    padding: 1rem 1.15rem 1.05rem;
    list-style: none;
}

.av-step {
    position: relative;
    padding: 0 0 1rem 1.5rem;
}

.av-step:last-child { padding-bottom: 0; }

/* Connector */
.av-step::after {
    content: "";
    position: absolute;
    left: 5px;
    top: 14px;
    bottom: -2px;
    width: 2px;
    background: var(--av-soft);
}

.av-step:last-child::after { content: none; }

/* Marker */
.av-step::before {
    content: "";
    position: absolute;
    left: 0;
    top: 3px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: var(--pm-surface);
    border: 2px solid var(--av-strong);
}

.av-step--done::before     { background: var(--av-accent); border-color: var(--av-accent); }
.av-step--done::after      { background: var(--av-accent); }
.av-step--current::before  { border-color: var(--av-accent); box-shadow: 0 0 0 3px var(--pm-accent-soft, #EBF2F4); }
.av-step--warn::before     { background: #F04438; border-color: #F04438; }
.av-step--stopped::before  { background: #98A2B3; border-color: #98A2B3; }

.av-step-what { display: block; font-size: 0.82rem; font-weight: 500; color: var(--av-ink); }
.av-step--upcoming .av-step-what { color: var(--av-faint); }
.av-step--current .av-step-what  { font-weight: 600; color: var(--av-accent); }

.av-step-when {
    display: block;
    margin-top: 0.1rem;
    font-size: 0.74rem;
    color: var(--av-muted);
    font-variant-numeric: tabular-nums;
}

/* ---------- Empty ---------- */

.av-empty { padding: 3rem 1rem; text-align: center; }
.av-empty i { font-size: 1.4rem; color: var(--av-faint); }
.av-empty h3 { margin: 0.6rem 0 0.25rem; font-size: 0.95rem; font-weight: 600; color: var(--av-ink); }
.av-empty p { margin: 0; font-size: 0.82rem; color: var(--av-muted); }

/* ---------- Responsive ---------- */

@media (max-width: 992px) {
    .av-grid { grid-template-columns: minmax(0, 1fr); }
}

@media (max-width: 640px) {
    .av-head { padding: 1rem; }
    .av-head-side { width: 100%; }
    .av-head-side .av-btn--primary { flex: 1; justify-content: center; }
    .av-svc-group + .av-svc-group { border-left: 0; border-top: 1px solid var(--av-soft); }
}
</style>
