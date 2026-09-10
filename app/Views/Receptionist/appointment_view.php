<?= $this->extend('layouts/ReceptionistLayout') ?>

<?= $this->section('pageTitle') ?>Appointment Details<?= $this->endSection() ?>

<?= $this->section('receptionistContent') ?>

<?php
    $appointment = $appointment ?? [];

    // PNG avatar filenames inside public/assets/images/.
    // Change these two strings if your files are named differently.
    $maleAvatar   = 'man-avatar.png';
    $femaleAvatar = 'woman-avatar.png';

    // Two-letter initials fallback, same rule the appointments list uses.
    $initialsOf = static function ($name) {
        $parts = preg_split('/\s+/', trim((string) $name));
        $first = mb_substr($parts[0] ?? '', 0, 1);
        $last  = count($parts) > 1 ? mb_substr(end($parts), 0, 1) : '';
        return mb_strtoupper($first . $last);
    };

    $statusKey = (string) ($appointment['status'] ?? 'pending');
    $genderRaw = strtolower(trim((string) ($appointment['gender'] ?? '')));
    $isMale    = $genderRaw === 'male'   || $genderRaw === 'm';
    $isFemale  = $genderRaw === 'female' || $genderRaw === 'f';
    $avatarKind = $isMale ? 'male' : ($isFemale ? 'female' : 'neutral');

    $statusCopy = [
        'pending'   => ['label' => 'Pending',   'tone' => 'pending',   'note' => 'This appointment is waiting for approval.'],
        'approved'  => ['label' => 'Approved',  'tone' => 'approved',  'note' => 'This appointment has been approved and confirmed.'],
        'completed' => ['label' => 'Completed', 'tone' => 'completed', 'note' => 'This appointment has been completed successfully.'],
        'cancelled' => ['label' => 'Cancelled', 'tone' => 'cancelled', 'note' => 'This appointment has been cancelled.'],
        'late'      => ['label' => 'Late',      'tone' => 'late',      'note' => 'Patient is one hour or more late for their appointment.'],
    ];
    $status = $statusCopy[$statusKey] ?? ['label' => ucfirst($statusKey), 'tone' => 'pending', 'note' => ''];
?>

<div class="av">

    <?php if (!empty($appointment)): ?>

        <!-- =========================================================
             STATUS BANNER
             ========================================================= -->
        <div class="av-banner av-banner--<?= esc($status['tone'], 'attr') ?>">
            <div class="av-banner-icon" aria-hidden="true">
                <?php if ($statusKey === 'pending'): ?>
                    <i class="bi bi-clock-history"></i>
                <?php elseif ($statusKey === 'approved'): ?>
                    <i class="bi bi-check-circle"></i>
                <?php elseif ($statusKey === 'completed'): ?>
                    <i class="bi bi-check2-circle"></i>
                <?php elseif ($statusKey === 'cancelled'): ?>
                    <i class="bi bi-x-circle"></i>
                <?php elseif ($statusKey === 'late'): ?>
                    <i class="bi bi-exclamation-triangle"></i>
                <?php else: ?>
                    <i class="bi bi-info-circle"></i>
                <?php endif; ?>
            </div>

            <div class="av-banner-copy">
                <div class="av-banner-title"><?= esc($status['label']) ?></div>
                <div class="av-banner-sub"><?= esc($status['note']) ?></div>
            </div>

            <div class="av-banner-ref">
                <span class="av-banner-ref-label">Reference</span>
                <span class="av-banner-ref-value"><?= esc($appointment['reference_number'] ?? '—') ?></span>
            </div>
        </div>


        <!-- =========================================================
             MAIN GRID
             ========================================================= -->
        <div class="av-grid">

            <!-- LEFT COLUMN -->
            <div class="av-col-main">

                <!-- PATIENT CARD -->
                <section class="av-card">
                    <header class="av-card-head">
                        <h5 class="av-card-title">
                            <i class="bi bi-person-circle" aria-hidden="true"></i>
                            Patient information
                        </h5>
                    </header>

                    <div class="av-patient">
                        <span class="av-avatar av-avatar--<?= esc($avatarKind, 'attr') ?>" aria-hidden="true">
                            <?php if ($isMale): ?>
                                <img src="<?= esc(base_url('assets/images/' . $maleAvatar), 'attr') ?>"
                                     alt=""
                                     class="av-avatar-img"
                                     loading="lazy"
                                     decoding="async">
                            <?php elseif ($isFemale): ?>
                                <img src="<?= esc(base_url('assets/images/' . $femaleAvatar), 'attr') ?>"
                                     alt=""
                                     class="av-avatar-img"
                                     loading="lazy"
                                     decoding="async">
                            <?php else: ?>
                                <span class="av-avatar-initials"><?= esc($initialsOf($appointment['full_name'] ?? '')) ?></span>
                            <?php endif; ?>
                        </span>

                        <div class="av-patient-body">
                            <h3 class="av-patient-name"><?= esc($appointment['full_name'] ?? 'Unknown') ?></h3>

                            <div class="av-patient-meta">
                                <span class="av-meta-item">
                                    <i class="bi bi-gender-ambiguous" aria-hidden="true"></i>
                                    <?= esc($appointment['gender'] ?? '—') ?>
                                </span>
                                <span class="av-meta-item">
                                    <i class="bi bi-cake2" aria-hidden="true"></i>
                                    <?= esc($appointment['age'] ?? '—') ?> years old
                                </span>
                                <span class="av-meta-item">
                                    <i class="bi bi-calendar3" aria-hidden="true"></i>
                                    <?= !empty($appointment['appointment_date']) ? esc(date('M d, Y', strtotime($appointment['appointment_date']))) : '—' ?>
                                </span>
                                <span class="av-meta-item">
                                    <i class="bi bi-clock" aria-hidden="true"></i>
                                    <?= esc($appointment['appointment_time'] ?? '—') ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <dl class="av-facts">
                        <div>
                            <dt><i class="bi bi-envelope" aria-hidden="true"></i> Email</dt>
                            <dd>
                                <?php if (!empty($appointment['email'])): ?>
                                    <a class="av-link" href="mailto:<?= esc($appointment['email'], 'attr') ?>"><?= esc($appointment['email']) ?></a>
                                <?php else: ?>
                                    <span class="av-muted">Not provided</span>
                                <?php endif; ?>
                            </dd>
                        </div>

                        <div>
                            <dt><i class="bi bi-phone" aria-hidden="true"></i> Phone</dt>
                            <dd>
                                <?php if (!empty($appointment['phone'])): ?>
                                    <a class="av-link" href="tel:<?= esc($appointment['phone'], 'attr') ?>"><?= esc($appointment['phone']) ?></a>
                                <?php else: ?>
                                    <span class="av-muted">Not provided</span>
                                <?php endif; ?>
                            </dd>
                        </div>

                        <?php if (!empty($appointment['arrival_time'])): ?>
                            <div>
                                <dt><i class="bi bi-check-circle" aria-hidden="true"></i> Arrival</dt>
                                <dd><?= esc(date('M d, Y h:i A', strtotime($appointment['arrival_time']))) ?></dd>
                            </div>
                        <?php endif; ?>

                        <div>
                            <dt><i class="bi bi-calendar-plus" aria-hidden="true"></i> Requested</dt>
                            <dd><?= !empty($appointment['created_at']) ? esc(date('M d, Y h:i A', strtotime($appointment['created_at']))) : '—' ?></dd>
                        </div>
                    </dl>
                </section>

                <!-- SERVICES CARD -->
                <section class="av-card">
                    <header class="av-card-head">
                        <h5 class="av-card-title">
                            <i class="bi bi-clipboard2-pulse" aria-hidden="true"></i>
                            Selected services
                        </h5>
                        <span class="av-tag av-tag--soft"><?= esc(ucfirst($appointment['service_type'] ?? '')) ?></span>
                    </header>

                    <?php if (!empty($lab_services) || !empty($xray_services)): ?>
                        <div class="av-services">

                            <?php if (!empty($lab_services)): ?>
                                <div class="av-service-group">
                                    <h6 class="av-service-title av-service-title--lab">
                                        <i class="bi bi-droplet-half" aria-hidden="true"></i>
                                        Laboratory tests
                                        <span class="av-service-count"><?= count($lab_services) ?></span>
                                    </h6>
                                    <ul class="av-service-list">
                                        <?php foreach ($lab_services as $service): ?>
                                            <li><?= esc($service) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($xray_services)): ?>
                                <div class="av-service-group">
                                    <h6 class="av-service-title av-service-title--xray">
                                        <i class="bi bi-radioactive" aria-hidden="true"></i>
                                        X-Ray services
                                        <span class="av-service-count"><?= count($xray_services) ?></span>
                                    </h6>
                                    <ul class="av-service-list">
                                        <?php foreach ($xray_services as $service): ?>
                                            <li><?= esc($service) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>

                        </div>
                    <?php else: ?>
                        <div class="av-empty av-empty--inline">
                            <div class="av-empty-icon"><i class="bi bi-inbox" aria-hidden="true"></i></div>
                            <p>No services selected for this appointment.</p>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($appointment['other_requests'])): ?>
                        <div class="av-note">
                            <h6 class="av-note-title">
                                <i class="bi bi-chat-quote" aria-hidden="true"></i>
                                Other requests
                            </h6>
                            <p class="av-note-body"><?= esc($appointment['other_requests']) ?></p>
                        </div>
                    <?php endif; ?>
                </section>

            </div>

            <!-- RIGHT COLUMN -->
            <aside class="av-col-side">

                <!-- ACTIONS -->
                <section class="av-card">
                    <header class="av-card-head">
                        <h5 class="av-card-title">
                            <i class="bi bi-gear" aria-hidden="true"></i>
                            Actions
                        </h5>
                    </header>

                    <div class="av-actions">

                        <?php if ($statusKey === 'pending' || $statusKey === 'late'): ?>
                            <a href="<?= base_url('receptionist/appointment/approve/' . ($appointment['id'] ?? 0)) ?>"
                               class="av-action av-action--primary"
                               onclick="return confirm('Approve this appointment?')">
                                <i class="bi bi-check-circle" aria-hidden="true"></i>
                                Approve appointment
                            </a>
                            <a href="<?= base_url('receptionist/appointment/cancel/' . ($appointment['id'] ?? 0)) ?>"
                               class="av-action av-action--danger"
                               onclick="return confirm('Cancel this appointment?')">
                                <i class="bi bi-x-circle" aria-hidden="true"></i>
                                Cancel appointment
                            </a>

                        <?php elseif ($statusKey === 'approved'): ?>
                            <a href="<?= base_url('receptionist/appointment/complete/' . ($appointment['id'] ?? 0)) ?>"
                               class="av-action av-action--primary"
                               onclick="return confirm('Mark this appointment as completed?')">
                                <i class="bi bi-check2-circle" aria-hidden="true"></i>
                                Mark as completed
                            </a>
                            <a href="<?= base_url('receptionist/appointment/cancel/' . ($appointment['id'] ?? 0)) ?>"
                               class="av-action av-action--danger"
                               onclick="return confirm('Cancel this appointment?')">
                                <i class="bi bi-x-circle" aria-hidden="true"></i>
                                Cancel appointment
                            </a>

                        <?php elseif ($statusKey === 'completed'): ?>
                            <div class="av-state av-state--success">
                                <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
                                This appointment has been completed.
                            </div>

                        <?php elseif ($statusKey === 'cancelled'): ?>
                            <div class="av-state av-state--danger">
                                <i class="bi bi-x-circle-fill" aria-hidden="true"></i>
                                This appointment has been cancelled.
                            </div>
                        <?php endif; ?>

                    </div>
                </section>

                <!-- TIMELINE -->
                <section class="av-card">
                    <header class="av-card-head">
                        <h5 class="av-card-title">
                            <i class="bi bi-clock-history" aria-hidden="true"></i>
                            Timeline
                        </h5>
                    </header>

                    <ul class="av-timeline">
                        <li>
                            <span class="av-timeline-dot av-timeline-dot--teal" aria-hidden="true"></span>
                            <div class="av-timeline-body">
                                <strong>Request created</strong>
                                <span class="av-timeline-time">
                                    <?= !empty($appointment['created_at']) ? esc(date('M d, Y h:i A', strtotime($appointment['created_at']))) : '—' ?>
                                </span>
                            </div>
                        </li>

                        <?php if ($statusKey === 'approved' || $statusKey === 'completed'): ?>
                            <li>
                                <span class="av-timeline-dot av-timeline-dot--success" aria-hidden="true"></span>
                                <div class="av-timeline-body">
                                    <strong>Approved</strong>
                                    <span class="av-timeline-time">
                                        <?= !empty($appointment['arrival_time']) ? esc(date('M d, Y h:i A', strtotime($appointment['arrival_time']))) : '—' ?>
                                    </span>
                                </div>
                            </li>
                        <?php endif; ?>

                        <?php if ($statusKey === 'completed'): ?>
                            <li>
                                <span class="av-timeline-dot av-timeline-dot--info" aria-hidden="true"></span>
                                <div class="av-timeline-body">
                                    <strong>Completed</strong>
                                    <span class="av-timeline-time">
                                        <?= !empty($appointment['updated_at']) ? esc(date('M d, Y h:i A', strtotime($appointment['updated_at']))) : '—' ?>
                                    </span>
                                </div>
                            </li>
                        <?php endif; ?>

                        <?php if ($statusKey === 'cancelled'): ?>
                            <li>
                                <span class="av-timeline-dot av-timeline-dot--danger" aria-hidden="true"></span>
                                <div class="av-timeline-body">
                                    <strong>Cancelled</strong>
                                    <span class="av-timeline-time">
                                        <?= !empty($appointment['updated_at']) ? esc(date('M d, Y h:i A', strtotime($appointment['updated_at']))) : '—' ?>
                                    </span>
                                </div>
                            </li>
                        <?php endif; ?>

                        <?php if ($statusKey === 'late'): ?>
                            <li>
                                <span class="av-timeline-dot av-timeline-dot--warning" aria-hidden="true"></span>
                                <div class="av-timeline-body">
                                    <strong>Marked as late</strong>
                                    <span class="av-timeline-time">
                                        <?= !empty($appointment['updated_at']) ? esc(date('M d, Y h:i A', strtotime($appointment['updated_at']))) : '—' ?>
                                    </span>
                                </div>
                            </li>
                        <?php endif; ?>
                    </ul>
                </section>

                <!-- QUICK ACTIONS -->
                <section class="av-card">
                    <header class="av-card-head">
                        <h5 class="av-card-title">
                            <i class="bi bi-lightning-charge" aria-hidden="true"></i>
                            Quick actions
                        </h5>
                    </header>

                    <div class="av-quick">
                        <a href="mailto:<?= esc($appointment['email'] ?? '', 'attr') ?>" class="av-quick-item">
                            <i class="bi bi-envelope" aria-hidden="true"></i>
                            <span>Send email</span>
                        </a>
                        <a href="tel:<?= esc($appointment['phone'] ?? '', 'attr') ?>" class="av-quick-item">
                            <i class="bi bi-telephone" aria-hidden="true"></i>
                            <span>Call patient</span>
                        </a>
                        <button type="button" class="av-quick-item" onclick="window.print()">
                            <i class="bi bi-printer" aria-hidden="true"></i>
                            <span>Print details</span>
                        </button>
                    </div>
                </section>

            </aside>
        </div>

    <?php else: ?>

        <div class="av-empty">
            <div class="av-empty-icon"><i class="bi bi-exclamation-circle" aria-hidden="true"></i></div>
            <h3>Appointment not found</h3>
            <p>The appointment you tried to open does not exist or has been removed.</p>
            <a href="<?= base_url('receptionist/appointments') ?>" class="av-action av-action--primary">
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
                Back to appointments
            </a>
        </div>

    <?php endif; ?>

</div>

<style>
/* =========================================================
   APPOINTMENT VIEW
   Namespaced under .av so the layout's generic card and table
   rules can't leak in. Visual tokens match the appointments
   list and diagnostic requests pages.
   ========================================================= */

.av {
    --av-ink:         #0f172a;
    --av-text:        #334155;
    --av-muted:       #64748b;
    --av-faint:       #94a3b8;
    --av-line:        #e2e8f0;
    --av-line-soft:   #f1f5f9;
    --av-surface:     #ffffff;
    --av-subtle:      #f8fafc;
    --av-accent:      #0d9488;
    --av-accent-dark: #0f766e;
    --av-accent-soft: #e6f7f7;
    --av-danger:      #dc2626;
    --av-danger-dark: #b91c1c;
    --av-success:     #059669;
    --av-amber:       #b45309;
    --av-radius:      12px;
    --av-radius-sm:   8px;
    --av-ring:        0 0 0 3px rgba(13, 148, 136, 0.18);
    --av-mono:        ui-monospace, SFMono-Regular, "SF Mono", Menlo, Consolas, monospace;

    color: var(--av-text);
    font-size: 0.875rem;
}

.av *:focus-visible { outline: 2px solid var(--av-accent); outline-offset: 2px; }

.av-muted { color: var(--av-faint); }

/* =========================================================
   TOP BAR
   ========================================================= */

.av-topbar {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 1rem;
    flex-wrap: wrap;
}

.av-back {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    height: 34px;
    padding: 0 0.85rem;
    font-size: 0.8125rem;
    font-weight: 600;
    color: var(--av-text);
    background: var(--av-surface);
    border: 1px solid var(--av-line);
    border-radius: var(--av-radius-sm);
    text-decoration: none;
    transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
}

.av-back:hover {
    background: var(--av-subtle);
    border-color: #cbd5e1;
    color: var(--av-ink);
}

.av-back--ghost { color: var(--av-muted); }

.av-back i { font-size: 0.9em; }

/* =========================================================
   STATUS BANNER
   ========================================================= */

.av-banner {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem 1.15rem;
    margin-bottom: 1.25rem;
    background: var(--av-surface);
    border: 1px solid var(--av-line);
    border-left: 3px solid var(--av-tone, var(--av-line));
    border-radius: var(--av-radius);
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    flex-wrap: wrap;
}

.av-banner--pending   { --av-tone: #f59e0b; }
.av-banner--approved  { --av-tone: #3b82f6; }
.av-banner--completed { --av-tone: #10b981; }
.av-banner--cancelled { --av-tone: #94a3b8; }
.av-banner--late      { --av-tone: #ea580c; }

.av-banner-icon {
    display: grid;
    place-items: center;
    width: 40px;
    height: 40px;
    font-size: 1.1rem;
    color: var(--av-tone, var(--av-muted));
    background: color-mix(in srgb, var(--av-tone, var(--av-muted)) 12%, white);
    border-radius: 10px;
    flex-shrink: 0;
}

.av-banner-copy { flex: 1 1 240px; min-width: 0; }

.av-banner-title {
    font-size: 0.95rem;
    font-weight: 650;
    color: var(--av-ink);
    line-height: 1.25;
}

.av-banner-sub {
    margin-top: 0.15rem;
    font-size: 0.8rem;
    color: var(--av-muted);
}

.av-banner-ref {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.15rem;
    flex-shrink: 0;
}

.av-banner-ref-label {
    font-size: 0.65rem;
    font-weight: 600;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: var(--av-faint);
}

.av-banner-ref-value {
    font-family: var(--av-mono);
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--av-ink);
}

/* =========================================================
   GRID
   ========================================================= */

.av-grid {
    display: grid;
    grid-template-columns: minmax(0, 2fr) minmax(280px, 1fr);
    gap: 1.1rem;
    align-items: start;
}

.av-col-main,
.av-col-side {
    display: flex;
    flex-direction: column;
    gap: 1.1rem;
    min-width: 0;
}

/* =========================================================
   CARD
   ========================================================= */

.av-card {
    background: var(--av-surface);
    border: 1px solid var(--av-line);
    border-radius: var(--av-radius);
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    padding: 1.15rem 1.25rem 1.25rem;
}

.av-card-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    margin-bottom: 1rem;
    flex-wrap: wrap;
}

.av-card-title {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin: 0;
    font-size: 0.95rem;
    font-weight: 650;
    color: var(--av-ink);
    letter-spacing: -0.01em;
}

.av-card-title i { color: var(--av-accent); font-size: 0.95rem; }

.av-tag {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    padding: 0.2rem 0.55rem;
    font-size: 0.72rem;
    font-weight: 600;
    border-radius: 999px;
    white-space: nowrap;
}

.av-tag--soft {
    color: var(--av-accent-dark);
    background: var(--av-accent-soft);
}

/* =========================================================
   PATIENT BLOCK
   ========================================================= */

.av-patient {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding-bottom: 1rem;
    margin-bottom: 1rem;
    border-bottom: 1px solid var(--av-line-soft);
    flex-wrap: wrap;
}

.av-avatar {
    display: grid;
    place-items: center;
    flex-shrink: 0;
    width: 56px;
    height: 56px;
    border-radius: 50%;
    overflow: hidden;
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--av-accent);
    background: var(--av-accent-soft);
}

.av-avatar--male    { color: #1d4ed8; background: #eaf2fe; }
.av-avatar--female  { color: #b32e50; background: #fce9ee; }
.av-avatar--neutral { color: var(--av-accent); background: var(--av-accent-soft); }

.av-avatar-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.av-avatar-initials { line-height: 1; }

.av-patient-body { min-width: 0; flex: 1 1 200px; }

.av-patient-name {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 650;
    color: var(--av-ink);
    letter-spacing: -0.01em;
    line-height: 1.3;
    overflow-wrap: anywhere;
}

.av-patient-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem 1rem;
    margin-top: 0.35rem;
}

.av-meta-item {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    font-size: 0.8rem;
    color: var(--av-muted);
}

.av-meta-item i { color: var(--av-accent); font-size: 0.78rem; }

/* =========================================================
   FACTS
   ========================================================= */

.av-facts {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.75rem 1.25rem;
    margin: 0;
}

.av-facts > div { min-width: 0; }

.av-facts dt {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    margin: 0 0 0.15rem;
    font-size: 0.68rem;
    font-weight: 600;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: var(--av-faint);
}

.av-facts dt i { font-size: 0.75rem; color: var(--av-faint); }

.av-facts dd {
    margin: 0;
    font-size: 0.85rem;
    color: var(--av-ink);
    overflow-wrap: anywhere;
}

.av-link {
    color: var(--av-ink);
    text-decoration: none;
}

.av-link:hover {
    color: var(--av-accent-dark);
    text-decoration: underline;
    text-underline-offset: 2px;
}

/* =========================================================
   SERVICES
   ========================================================= */

.av-services {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 1rem;
}

.av-service-group { min-width: 0; }

.av-service-title {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    margin: 0 0 0.5rem;
    font-size: 0.78rem;
    font-weight: 650;
    color: var(--av-ink);
}

.av-service-title i { font-size: 0.8rem; }
.av-service-title--lab i  { color: #0e7490; }
.av-service-title--xray i { color: #4338ca; }

.av-service-count {
    margin-left: auto;
    padding: 0.1rem 0.45rem;
    font-size: 0.68rem;
    font-weight: 700;
    font-variant-numeric: tabular-nums;
    color: var(--av-muted);
    background: var(--av-line-soft);
    border-radius: 999px;
}

.av-service-list {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.av-service-list li {
    position: relative;
    padding: 0.4rem 0.65rem 0.4rem 1.4rem;
    font-size: 0.82rem;
    color: var(--av-text);
    background: var(--av-subtle);
    border: 1px solid var(--av-line-soft);
    border-radius: var(--av-radius-sm);
    overflow-wrap: anywhere;
}

.av-service-list li::before {
    content: "";
    position: absolute;
    left: 0.65rem;
    top: 0.75em;
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: var(--av-accent);
}

/* =========================================================
   NOTE
   ========================================================= */

.av-note {
    margin-top: 1rem;
    padding: 0.85rem 1rem;
    background: var(--av-subtle);
    border: 1px solid var(--av-line-soft);
    border-radius: var(--av-radius-sm);
}

.av-note-title {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    margin: 0 0 0.35rem;
    font-size: 0.8rem;
    font-weight: 650;
    color: var(--av-ink);
}

.av-note-title i { color: var(--av-accent); font-size: 0.85rem; }

.av-note-body {
    margin: 0;
    font-size: 0.84rem;
    line-height: 1.6;
    color: var(--av-text);
    white-space: pre-wrap;
}

/* =========================================================
   ACTIONS
   ========================================================= */

.av-actions {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.av-action {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    height: 38px;
    padding: 0 1rem;
    font-size: 0.84rem;
    font-weight: 600;
    text-decoration: none;
    border: 1px solid var(--av-line);
    border-radius: var(--av-radius-sm);
    background: var(--av-surface);
    color: var(--av-text);
    cursor: pointer;
    transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
    width: 100%;
}

.av-action:hover {
    background: var(--av-subtle);
    border-color: #cbd5e1;
    color: var(--av-ink);
}

.av-action i { font-size: 0.9em; }

.av-action--primary,
.av-action--primary:hover { color: #ffffff; }
.av-action--primary { background: var(--av-accent); border-color: var(--av-accent); }
.av-action--primary:hover { background: var(--av-accent-dark); border-color: var(--av-accent-dark); }

.av-action--danger,
.av-action--danger:hover { color: #ffffff; }
.av-action--danger { background: var(--av-danger); border-color: var(--av-danger); }
.av-action--danger:hover { background: var(--av-danger-dark); border-color: var(--av-danger-dark); }

.av-state {
    display: flex;
    align-items: center;
    gap: 0.55rem;
    padding: 0.75rem 0.9rem;
    font-size: 0.84rem;
    border-radius: var(--av-radius-sm);
    border: 1px solid transparent;
}

.av-state--success {
    color: #047857;
    background: #ecfdf5;
    border-color: #a7f3d0;
}

.av-state--danger {
    color: #b91c1c;
    background: #fef2f2;
    border-color: #fecaca;
}

.av-state i { font-size: 1rem; flex-shrink: 0; }

/* =========================================================
   TIMELINE
   ========================================================= */

.av-timeline {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
}

.av-timeline li {
    display: flex;
    align-items: flex-start;
    gap: 0.7rem;
    padding: 0 0 0.8rem 0;
    position: relative;
}

.av-timeline li:last-child { padding-bottom: 0; }

.av-timeline li:not(:last-child)::before {
    content: "";
    position: absolute;
    left: 4.5px;
    top: 14px;
    bottom: 0;
    width: 2px;
    background: var(--av-line);
}

.av-timeline-dot {
    width: 11px;
    height: 11px;
    border-radius: 50%;
    flex-shrink: 0;
    margin-top: 3px;
    background: var(--av-line);
    box-shadow: 0 0 0 3px var(--av-surface);
    position: relative;
    z-index: 1;
}

.av-timeline-dot--teal    { background: var(--av-accent); }
.av-timeline-dot--success { background: #10b981; }
.av-timeline-dot--info    { background: #3b82f6; }
.av-timeline-dot--danger  { background: #ef4444; }
.av-timeline-dot--warning { background: #f59e0b; }

.av-timeline-body { min-width: 0; }

.av-timeline-body strong {
    display: block;
    font-size: 0.84rem;
    font-weight: 600;
    color: var(--av-ink);
    line-height: 1.3;
}

.av-timeline-time {
    display: block;
    margin-top: 0.1rem;
    font-size: 0.74rem;
    color: var(--av-muted);
    font-variant-numeric: tabular-nums;
}

/* =========================================================
   QUICK ACTIONS
   ========================================================= */

.av-quick {
    display: flex;
    flex-direction: column;
    gap: 0.4rem;
}

.av-quick-item {
    display: flex;
    align-items: center;
    gap: 0.55rem;
    padding: 0.55rem 0.75rem;
    font-size: 0.84rem;
    font-weight: 500;
    color: var(--av-text);
    background: var(--av-subtle);
    border: 1px solid var(--av-line-soft);
    border-radius: var(--av-radius-sm);
    text-decoration: none;
    cursor: pointer;
    transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
    width: 100%;
    text-align: left;
    font-family: inherit;
}

.av-quick-item:hover {
    background: var(--av-surface);
    border-color: var(--av-accent);
    color: var(--av-ink);
    text-decoration: none;
}

.av-quick-item i {
    font-size: 0.95rem;
    color: var(--av-accent);
    width: 20px;
    text-align: center;
    flex-shrink: 0;
}

/* =========================================================
   EMPTY STATE
   ========================================================= */

.av-empty {
    padding: 3rem 1rem;
    text-align: center;
    background: var(--av-surface);
    border: 1px solid var(--av-line);
    border-radius: var(--av-radius);
}

.av-empty--inline {
    padding: 1.5rem 1rem;
    border: 1px dashed var(--av-line);
    border-radius: var(--av-radius-sm);
    background: var(--av-subtle);
}

.av-empty-icon {
    display: grid;
    place-items: center;
    width: 44px;
    height: 44px;
    margin: 0 auto 0.85rem;
    font-size: 1.2rem;
    color: var(--av-faint);
    background: var(--av-line-soft);
    border-radius: 10px;
}

.av-empty h3 {
    margin: 0 0 0.25rem;
    font-size: 0.95rem;
    font-weight: 600;
    color: var(--av-ink);
}

.av-empty p {
    max-width: 28rem;
    margin: 0 auto 1rem;
    font-size: 0.82rem;
    color: var(--av-muted);
}

.av-empty .av-action {
    display: inline-flex;
    width: auto;
}

/* =========================================================
   RESPONSIVE
   ========================================================= */

@media (max-width: 1100px) {
    .av-grid { grid-template-columns: minmax(0, 1fr); }
}

@media (max-width: 768px) {
    .av-banner { padding: 0.9rem 1rem; }
    .av-banner-ref { align-items: flex-start; width: 100%; }

    .av-patient { align-items: flex-start; }

    .av-facts { grid-template-columns: minmax(0, 1fr); }
    .av-services { grid-template-columns: minmax(0, 1fr); }

    .av-card { padding: 1rem; }
}

@media (max-width: 480px) {
    .av-avatar { width: 48px; height: 48px; font-size: 0.95rem; }
    .av-patient-name { font-size: 1rem; }
    .av-topbar .av-back { flex: 1; justify-content: center; }
}

@media (prefers-reduced-motion: reduce) {
    .av *, .av *::before, .av *::after { transition: none !important; }
}

@media print {
    .av-topbar,
    .av-actions,
    .av-quick { display: none !important; }

    .av-card,
    .av-banner,
    .av-empty { box-shadow: none; break-inside: avoid; }

    .av-grid { grid-template-columns: minmax(0, 1fr); }
}
</style>

<?= $this->endSection() ?>