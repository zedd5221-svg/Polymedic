<?= $this->extend('layouts/ReceptionistLayout') ?>

<?= $this->section('pageTitle') ?>Appointments<?= $this->endSection() ?>

<?= $this->section('receptionistContent') ?>

<?php
    $appointments = (isset($appointments) && is_array($appointments)) ? $appointments : [];

    $statusTabs = [
        'all'       => ['label' => 'All',       'count' => (int) ($total     ?? count($appointments))],
        'pending'   => ['label' => 'Pending',   'count' => (int) ($pending   ?? 0)],
        'approved'  => ['label' => 'Approved',  'count' => (int) ($approved  ?? 0)],
        'completed' => ['label' => 'Completed', 'count' => (int) ($completed ?? 0)],
        'cancelled' => ['label' => 'Cancelled', 'count' => (int) ($cancelled ?? 0)],
    ];

    $initialsOf = static function ($name) {
        $parts = preg_split('/\s+/', trim((string) $name));
        $first = mb_substr($parts[0] ?? '', 0, 1);
        $last  = count($parts) > 1 ? mb_substr(end($parts), 0, 1) : '';
        return mb_strtoupper($first . $last);
    };

    // PNG avatar filenames inside public/assets/images/.
    // Change these two strings if your files are named differently.
    $maleAvatar   = 'man-avatar.png';
    $femaleAvatar = 'woman-avatar.png';

    // PNG stat-card icon filenames. Change any of these five strings
    // to point at your own PNGs. Files must live in:
    //   public/assets/images/
    $statIcons = [
        'total'     => 'appointment123.png',
        'pending'   => 'clock (1).png',
        'approved'  => 'checked.png',
        'completed' => 'file (1).png',
        'cancelled' => 'remove-user.png',
    ];
?>

<div class="ap">

    <!-- PAGE HEADER -->
    <header class="ap-head">
        <div>
            <h2 class="ap-title">Appointment management</h2>
        </div>

        <div class="ap-head-actions">
            <button type="button" class="ap-btn ap-btn--primary"
                    onclick="window.location.href='<?= base_url('appointment/book') ?>'">
                <i class="bi bi-plus-lg" aria-hidden="true"></i>
                New appointment
            </button>
        </div>
    </header>


    <!-- STATS -->
    <div class="ap-stats">
        <div class="ap-stat">
            <div class="ap-stat-icon ap-stat-icon--teal">
                <img src="<?= esc(base_url('assets/images/' . $statIcons['total']), 'attr') ?>"
                     alt=""
                     class="ap-stat-img"
                     loading="lazy"
                     decoding="async">
            </div>
            <div class="ap-stat-body">
                <div class="ap-stat-value"><?= number_format($total ?? 0) ?></div>
                <div class="ap-stat-label">Total</div>
                <div class="ap-stat-sub">All appointments</div>
            </div>
        </div>

        <div class="ap-stat">
            <div class="ap-stat-icon ap-stat-icon--amber">
                <img src="<?= esc(base_url('assets/images/' . $statIcons['pending']), 'attr') ?>"
                     alt=""
                     class="ap-stat-img"
                     loading="lazy"
                     decoding="async">
            </div>
            <div class="ap-stat-body">
                <div class="ap-stat-value"><?= number_format($pending ?? 0) ?></div>
                <div class="ap-stat-label">Pending</div>
                <div class="ap-stat-sub">Awaiting approval</div>
            </div>
        </div>

        <div class="ap-stat">
            <div class="ap-stat-icon ap-stat-icon--blue">
                <img src="<?= esc(base_url('assets/images/' . $statIcons['approved']), 'attr') ?>"
                     alt=""
                     class="ap-stat-img"
                     loading="lazy"
                     decoding="async">
            </div>
            <div class="ap-stat-body">
                <div class="ap-stat-value"><?= number_format($approved ?? 0) ?></div>
                <div class="ap-stat-label">Approved</div>
                <div class="ap-stat-sub">Confirmed</div>
            </div>
        </div>

        <div class="ap-stat">
            <div class="ap-stat-icon ap-stat-icon--green">
                <img src="<?= esc(base_url('assets/images/' . $statIcons['completed']), 'attr') ?>"
                     alt=""
                     class="ap-stat-img"
                     loading="lazy"
                     decoding="async">
            </div>
            <div class="ap-stat-body">
                <div class="ap-stat-value"><?= number_format($completed ?? 0) ?></div>
                <div class="ap-stat-label">Completed</div>
                <div class="ap-stat-sub">Visits done</div>
            </div>
        </div>

        <div class="ap-stat">
            <div class="ap-stat-icon ap-stat-icon--rose">
                <img src="<?= esc(base_url('assets/images/' . $statIcons['cancelled']), 'attr') ?>"
                     alt=""
                     class="ap-stat-img"
                     loading="lazy"
                     decoding="async">
            </div>
            <div class="ap-stat-body">
                <div class="ap-stat-value"><?= number_format($cancelled ?? 0) ?></div>
                <div class="ap-stat-label">Cancelled</div>
                <div class="ap-stat-sub">Cancelled visits</div>
            </div>
        </div>
    </div>


    <!-- TABLE -->
    <section class="ap-panel">

        <div class="ap-tabs" role="group" aria-label="Filter by status">
            <?php foreach ($statusTabs as $key => $tab): ?>
                <button type="button"
                        class="ap-tab<?= $key === 'all' ? ' is-active' : '' ?>"
                        data-status="<?= esc($key, 'attr') ?>"
                        aria-pressed="<?= $key === 'all' ? 'true' : 'false' ?>">
                    <?= esc($tab['label']) ?>
                    <span class="ap-tab-count"><?= (int) $tab['count'] ?></span>
                </button>
            <?php endforeach; ?>

            <select class="ap-visually-hidden" id="filterStatus" aria-hidden="true" tabindex="-1">
                <option value="">All Status</option>
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="completed">Completed</option>
                <option value="cancelled">Cancelled</option>
            </select>
        </div>

        <div class="ap-filters">
            <div class="ap-search">
                <i class="bi bi-search" aria-hidden="true"></i>
                <label class="visually-hidden" for="searchAppointments">Search appointments</label>
                <input type="text"
                       class="ap-input"
                       id="searchAppointments"
                       placeholder="Search by patient, reference or service"
                       autocomplete="off">
            </div>
        </div>

        <div class="ap-table-wrap">
            <table class="ap-table" id="appointmentsTable">
                <thead>
                    <tr>
                        <th scope="col">Reference</th>
                        <th scope="col">Patient</th>
                        <th scope="col">Date</th>
                        <th scope="col">Time</th>
                        <th scope="col">Service</th>
                        <th scope="col">Status</th>
                        <th scope="col" class="ap-c-actions"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($appointments) > 0): ?>
                        <?php foreach ($appointments as $appointment):
                            $apptId    = (int) ($appointment['id'] ?? 0);
                            $statusKey = (string) ($appointment['status'] ?? 'pending');
                            $fullName  = (string) ($appointment['full_name'] ?? 'Unknown');
                            $ref       = (string) ($appointment['reference_number'] ?? '');
                            $dateTs    = !empty($appointment['appointment_date']) ? strtotime($appointment['appointment_date']) : false;
                            $time      = trim((string) ($appointment['appointment_time'] ?? ''));

                            // Derive the service label from the actual service columns,
                            // not from service_type. The booking form writes 'laboratory'
                            // into service_type regardless of what the patient picked, so
                            // it cannot be trusted. lab_services and xray_services are the
                            // real source of truth.
                            $labList  = json_decode($appointment['lab_services']  ?? '[]', true);
                            $xrayList = json_decode($appointment['xray_services'] ?? '[]', true);

                            $hasLab   = is_array($labList)  && count(array_filter($labList))  > 0;
                            $hasXray  = is_array($xrayList) && count(array_filter($xrayList)) > 0;

                            if ($hasLab && $hasXray) {
                                $svcText = 'Laboratory + X-Ray';
                            } elseif ($hasXray) {
                                $svcText = 'X-Ray';
                            } elseif ($hasLab) {
                                $svcText = 'Laboratory';
                            } else {
                                $svcRaw  = trim((string) ($appointment['service_type'] ?? ''));
                                $svcText = $svcRaw !== '' ? ucfirst(strtolower($svcRaw)) : 'Unspecified';
                            }

                            // Avatar kind: male, female, or neutral fallback to initials
                            $genderRaw  = strtolower(trim((string) ($appointment['gender'] ?? '')));
                            $isMale     = $genderRaw === 'male'   || $genderRaw === 'm';
                            $isFemale   = $genderRaw === 'female' || $genderRaw === 'f';
                            $avatarKind = $isMale ? 'male' : ($isFemale ? 'female' : 'neutral');
                        ?>
                            <tr class="ap-row ap-row--<?= esc($statusKey, 'attr') ?>" data-row-status="<?= esc($statusKey, 'attr') ?>">
                                <td data-label="Reference" class="ap-c-ref">
                                    <span class="ap-ref"><?= esc($ref) ?></span>
                                </td>

                                <td data-label="Patient">
                                    <div class="ap-patient">
                                        <span class="ap-avatar ap-avatar--<?= esc($avatarKind, 'attr') ?>" aria-hidden="true">
                                            <?php if ($isMale): ?>
                                                <img src="<?= esc(base_url('assets/images/' . $maleAvatar), 'attr') ?>"
                                                     alt=""
                                                     class="ap-avatar-img"
                                                     loading="lazy"
                                                     decoding="async">
                                            <?php elseif ($isFemale): ?>
                                                <img src="<?= esc(base_url('assets/images/' . $femaleAvatar), 'attr') ?>"
                                                     alt=""
                                                     class="ap-avatar-img"
                                                     loading="lazy"
                                                     decoding="async">
                                            <?php else: ?>
                                                <?= esc($initialsOf($fullName)) ?>
                                            <?php endif; ?>
                                        </span>
                                        <div class="ap-patient-body">
                                            <span class="ap-name"><?= esc($fullName) ?></span>
                                            <span class="ap-sub"><?= $dateTs ? esc(date('D, M j, Y', $dateTs)) : '—' ?></span>
                                        </div>
                                    </div>
                                </td>

                                <td data-label="Date" class="ap-c-date">
                                    <?= $dateTs ? esc(date('M j, Y', $dateTs)) : '—' ?>
                                </td>

                                <td data-label="Time" class="ap-c-time">
                                    <?= esc($time !== '' ? $time : '—') ?>
                                </td>

                                <td data-label="Service">
                                    <span class="ap-svc"><?= esc($svcText) ?></span>
                                </td>

                                <td data-label="Status">
                                    <span class="ap-status ap-status--<?= esc($statusKey, 'attr') ?>">
                                        <?= esc(ucfirst($statusKey)) ?>
                                    </span>
                                </td>

                                <td data-label="Actions" class="ap-c-actions">
                                    <div class="ap-actions">
                                        <a href="<?= base_url('receptionist/appointment/view/' . $apptId) ?>"
                                           class="ap-icon-btn"
                                           title="View details"
                                           aria-label="View details for <?= esc($fullName, 'attr') ?>">
                                            <i class="bi bi-eye" aria-hidden="true"></i>
                                        </a>

                                        <?php if ($statusKey === 'pending'): ?>
                                            <a href="<?= base_url('receptionist/appointment/approve/' . $apptId) ?>"
                                               class="ap-icon-btn ap-icon-btn--approve"
                                               title="Approve"
                                               aria-label="Approve appointment for <?= esc($fullName, 'attr') ?>"
                                               onclick="return confirm('Approve this appointment?')">
                                                <i class="bi bi-check2" aria-hidden="true"></i>
                                            </a>
                                        <?php endif; ?>

                                        <?php if ($statusKey === 'approved'): ?>
                                            <a href="<?= base_url('receptionist/appointment/complete/' . $apptId) ?>"
                                               class="ap-icon-btn ap-icon-btn--complete"
                                               title="Complete"
                                               aria-label="Mark appointment for <?= esc($fullName, 'attr') ?> as complete"
                                               onclick="return confirm('Mark this appointment as completed?')">
                                                <i class="bi bi-check-all" aria-hidden="true"></i>
                                            </a>
                                        <?php endif; ?>

                                        <?php if (!in_array($statusKey, ['completed', 'cancelled'], true)): ?>
                                            <a href="<?= base_url('receptionist/appointment/cancel/' . $apptId) ?>"
                                               class="ap-icon-btn ap-icon-btn--cancel"
                                               title="Cancel"
                                               aria-label="Cancel appointment for <?= esc($fullName, 'attr') ?>"
                                               onclick="return confirm('Cancel this appointment?')">
                                                <i class="bi bi-x" aria-hidden="true"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr class="ap-row-empty">
                            <td colspan="7">
                                <div class="ap-empty">
                                    <div class="ap-empty-icon"><i class="bi bi-calendar-x" aria-hidden="true"></i></div>
                                    <h3>No appointments yet</h3>
                                    <p>New bookings made online, or scheduled here, will appear in this list.</p>
                                    <button type="button" class="ap-btn ap-btn--primary"
                                            onclick="window.location.href='<?= base_url('appointment/book') ?>'">
                                        <i class="bi bi-plus-lg" aria-hidden="true"></i>
                                        New appointment
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="ap-empty ap-empty--filter" id="apNoMatch" hidden>
            <div class="ap-empty-icon"><i class="bi bi-search" aria-hidden="true"></i></div>
            <h3>No matching appointments</h3>
            <p>Try a different search term, or select All to clear the filter.</p>
            <button type="button" class="ap-btn" id="apClearFilters">Clear filters</button>
        </div>

    </section>

</div>

<style>
/* =========================================================
   APPOINTMENTS
   Everything is namespaced under .ap so the layout's generic
   card and table rules can't leak in. Visual tokens match the
   diagnostic requests page.
   ========================================================= */

.ap {
    --ap-ink:         #0f172a;
    --ap-text:        #334155;
    --ap-muted:       #64748b;
    --ap-faint:       #94a3b8;
    --ap-line:        #e2e8f0;
    --ap-line-soft:   #f1f5f9;
    --ap-surface:     #ffffff;
    --ap-subtle:      #f8fafc;
    --ap-accent:      #0d9488;
    --ap-accent-dark: #0f766e;
    --ap-accent-soft: #e6f7f7;
    --ap-radius:      10px;
    --ap-radius-sm:   7px;
    --ap-ring:        0 0 0 3px rgba(13, 148, 136, 0.18);
    --ap-mono:        ui-monospace, SFMono-Regular, "SF Mono", Menlo, Consolas, monospace;

    color: var(--ap-text);
}

.ap *:focus-visible { outline: 2px solid var(--ap-accent); outline-offset: 2px; }

.ap-visually-hidden {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}

/* ---------- Page header ---------- */

.ap-head {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
    margin-bottom: 1.25rem;
}

.ap-title {
    margin: 0 0 0.2rem;
    font-size: 1.25rem;
    font-weight: 650;
    letter-spacing: -0.015em;
    color: var(--ap-ink);
}

.ap-lede {
    margin: 0;
    font-size: 0.8125rem;
    color: var(--ap-muted);
}

.ap-lede strong { font-weight: 600; color: var(--ap-ink); }
.ap-lede span { margin: 0 0.2rem; color: var(--ap-faint); }

.ap-head-actions { display: flex; gap: 0.5rem; }

/* ---------- Buttons ---------- */

.ap-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.4rem;
    height: 36px;
    padding: 0 0.9rem;
    font-size: 0.8125rem;
    font-weight: 600;
    line-height: 1;
    color: var(--ap-text);
    background: var(--ap-surface);
    border: 1px solid var(--ap-line);
    border-radius: var(--ap-radius-sm);
    box-shadow: 0 1px 1px rgba(15, 23, 42, 0.03);
    white-space: nowrap;
    text-decoration: none;
    cursor: pointer;
    transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
}

.ap-btn:hover { background: var(--ap-subtle); border-color: #cbd5e1; color: var(--ap-ink); }
.ap-btn i { font-size: 0.9em; }

.ap-btn--primary,
.ap-btn--primary:hover { color: #ffffff; }
.ap-btn--primary { background: var(--ap-accent); border-color: var(--ap-accent); }
.ap-btn--primary:hover { background: var(--ap-accent-dark); border-color: var(--ap-accent-dark); }

/* ---------- Stats ---------- */

.ap-stats {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 0.9rem;
    margin-bottom: 1.25rem;
}

.ap-stat {
    display: flex;
    align-items: center;
    gap: 0.85rem;
    min-width: 0;
    padding: 0.9rem 1rem;
    background: var(--ap-surface);
    border: 1px solid var(--ap-line);
    border-radius: var(--ap-radius);
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
}

.ap-stat-icon {
    display: grid;
    place-items: center;
    flex-shrink: 0;
    width: 36px;
    height: 36px;
    border-radius: var(--ap-radius-sm);
}

/* Stat icon backgrounds stay transparent so the PNG sits directly
   on the card. The tinted variants below are kept as hooks in case
   you want to turn a subtle color back on for one card. */
.ap-stat-icon--teal  { background: transparent; }
.ap-stat-icon--amber { background: transparent; }
.ap-stat-icon--blue  { background: transparent; }
.ap-stat-icon--green { background: transparent; }
.ap-stat-icon--rose  { background: transparent; }

/* The PNG stat icon. Sized to sit comfortably inside the 36x36
   wrapper with a little breathing room. */
.ap-stat-img {
    width: 28px;
    height: 28px;
    object-fit: contain;
    display: block;
}

.ap-stat-body { min-width: 0; }

.ap-stat-value {
    font-size: 1.35rem;
    font-weight: 700;
    line-height: 1.1;
    letter-spacing: -0.02em;
    color: var(--ap-ink);
    font-variant-numeric: tabular-nums;
}

.ap-stat-label {
    margin-top: 0.15rem;
    font-size: 0.8125rem;
    font-weight: 600;
    color: var(--ap-text);
}

.ap-stat-sub {
    font-size: 0.72rem;
    color: var(--ap-muted);
}

/* ---------- Panel ---------- */

.ap-panel {
    background: var(--ap-surface);
    border: 1px solid var(--ap-line);
    border-radius: 12px;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    overflow: hidden;
}

/* ---------- Tabs ---------- */

.ap-tabs {
    position: relative;
    display: flex;
    gap: 0.25rem;
    padding: 0 0.75rem;
    border-bottom: 1px solid var(--ap-line);
    overflow-x: auto;
    scrollbar-width: none;
}

.ap-tabs::-webkit-scrollbar { display: none; }

.ap-tab {
    position: relative;
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    padding: 0.85rem 0.6rem;
    font-size: 0.8125rem;
    font-weight: 600;
    color: var(--ap-muted);
    background: none;
    border: 0;
    white-space: nowrap;
    cursor: pointer;
    transition: color 0.15s ease;
}

.ap-tab::after {
    content: "";
    position: absolute;
    left: 0.4rem;
    right: 0.4rem;
    bottom: -1px;
    height: 2px;
    border-radius: 2px 2px 0 0;
    background: transparent;
}

.ap-tab:hover { color: var(--ap-ink); }
.ap-tab.is-active { color: var(--ap-ink); }
.ap-tab.is-active::after { background: var(--ap-accent); }

.ap-tab-count {
    min-width: 1.4rem;
    padding: 0.05rem 0.4rem;
    font-size: 0.7rem;
    font-weight: 600;
    font-variant-numeric: tabular-nums;
    text-align: center;
    color: var(--ap-muted);
    background: var(--ap-line-soft);
    border-radius: 999px;
}

.ap-tab.is-active .ap-tab-count { color: var(--ap-accent); background: var(--ap-accent-soft); }

/* ---------- Filters ---------- */

.ap-filters {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
    padding: 0.75rem 1rem;
}

.ap-search { position: relative; flex: 1 1 280px; max-width: 420px; }

.ap-search > i {
    position: absolute;
    left: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    font-size: 0.8rem;
    color: var(--ap-faint);
    pointer-events: none;
}

.ap-input {
    width: 100%;
    height: 36px;
    padding: 0 0.75rem 0 2.1rem;
    font-size: 0.8125rem;
    color: var(--ap-ink);
    background-color: var(--ap-surface);
    border: 1px solid var(--ap-line);
    border-radius: var(--ap-radius-sm);
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

.ap-input::placeholder { color: var(--ap-faint); }

.ap-input:focus {
    outline: none;
    border-color: var(--ap-accent);
    box-shadow: var(--ap-ring);
}

/* ---------- Table ---------- */

.ap-table-wrap { overflow-x: auto; border-top: 1px solid var(--ap-line); }

.ap-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.8125rem;
    color: var(--ap-text);
}

.ap-table th {
    padding: 0.65rem 1rem;
    font-size: 0.72rem;
    font-weight: 600;
    letter-spacing: 0.01em;
    text-align: left;
    white-space: nowrap;
    color: var(--ap-muted);
    background: var(--ap-subtle);
    border-bottom: 1px solid var(--ap-line);
}

.ap-table td {
    padding: 0.85rem 1rem;
    vertical-align: middle;
    border-bottom: 1px solid var(--ap-line-soft);
}

.ap-table tbody tr:last-child td { border-bottom: 0; }

.ap-row { position: relative; transition: background-color 0.12s ease; }
.ap-row:hover { background: #fafbfd; }
.ap-row td:first-child { position: relative; }

.ap-row td:first-child::before {
    content: "";
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 3px;
    background: var(--rail, transparent);
}

.ap-row--pending   { --rail: #f59e0b; }
.ap-row--approved  { --rail: #3b82f6; }
.ap-row--completed { --rail: #10b981; }
.ap-row--cancelled { --rail: #94a3b8; }

.ap-c-ref { white-space: nowrap; }

.ap-ref {
    padding: 0.15rem 0.5rem;
    font-family: var(--ap-mono);
    font-size: 0.76rem;
    font-weight: 600;
    color: var(--ap-ink);
    background: var(--ap-line-soft);
    border-radius: 5px;
}

.ap-patient { display: flex; align-items: center; gap: 0.6rem; min-width: 0; }

.ap-avatar {
    display: grid;
    place-items: center;
    flex-shrink: 0;
    width: 32px;
    height: 32px;
    font-size: 0.68rem;
    font-weight: 700;
    color: var(--ap-accent);
    background: var(--ap-accent-soft);
    border-radius: 50%;
    overflow: hidden;
}

.ap-avatar--male    { background: #eaf2fe; color: #1d4ed8; }
.ap-avatar--female  { background: #fce9ee; color: #b32e50; }
.ap-avatar--neutral { background: var(--ap-accent-soft); color: var(--ap-accent); }

.ap-avatar-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.ap-patient-body { min-width: 0; }

.ap-name {
    display: block;
    font-weight: 600;
    color: var(--ap-ink);
    line-height: 1.3;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.ap-sub { display: block; font-size: 0.72rem; color: var(--ap-muted); white-space: nowrap; }

.ap-c-date,
.ap-c-time { white-space: nowrap; font-variant-numeric: tabular-nums; color: var(--ap-text); }

.ap-svc {
    display: inline-block;
    padding: 0.2rem 0.55rem;
    font-size: 0.75rem;
    font-weight: 600;
    color: #1d4ed8;
    background: #eaf2fe;
    border: 1px solid #dbe6fb;
    border-radius: 5px;
    white-space: nowrap;
}

.ap-status {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.2rem 0.55rem;
    font-size: 0.72rem;
    font-weight: 600;
    white-space: nowrap;
    color: var(--ap-tone-fg, #334155);
    background: var(--ap-tone-bg, #f1f5f9);
    border-radius: 999px;
}

.ap-status::before {
    content: "";
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: var(--ap-tone-dot, #94a3b8);
}

.ap-status--pending   { --ap-tone-bg: #fffbeb; --ap-tone-fg: #b45309; --ap-tone-dot: #f59e0b; }
.ap-status--approved  { --ap-tone-bg: #eff6ff; --ap-tone-fg: #1d4ed8; --ap-tone-dot: #3b82f6; }
.ap-status--completed { --ap-tone-bg: #ecfdf5; --ap-tone-fg: #047857; --ap-tone-dot: #10b981; }
.ap-status--cancelled { --ap-tone-bg: #f1f5f9; --ap-tone-fg: #64748b; --ap-tone-dot: #94a3b8; }

.ap-c-actions { width: 1%; text-align: right; white-space: nowrap; }

.ap-actions { display: inline-flex; align-items: center; gap: 0.25rem; }

.ap-icon-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    font-size: 0.85rem;
    color: var(--ap-muted);
    background: transparent;
    border: 1px solid transparent;
    border-radius: var(--ap-radius-sm);
    text-decoration: none;
    transition: background-color 0.15s ease, color 0.15s ease, border-color 0.15s ease;
}

.ap-icon-btn:hover { background: var(--ap-line-soft); color: var(--ap-ink); border-color: var(--ap-line); }

.ap-icon-btn--approve:hover { background: #ecfdf5; color: #047857; border-color: #a7f3d0; }
.ap-icon-btn--complete:hover { background: var(--ap-accent-soft); color: var(--ap-accent-dark); border-color: #99f6e4; }
.ap-icon-btn--cancel:hover { background: #fef2f2; color: #b91c1c; border-color: #fecaca; }

/* ---------- Empty states ---------- */

.ap-empty { padding: 3.5rem 1rem; text-align: center; }
.ap-empty--filter { border-top: 1px solid var(--ap-line); }

.ap-empty-icon {
    display: grid;
    place-items: center;
    width: 44px;
    height: 44px;
    margin: 0 auto 0.85rem;
    font-size: 1.2rem;
    color: var(--ap-faint);
    background: var(--ap-line-soft);
    border-radius: 10px;
}

.ap-empty h3 { margin: 0 0 0.25rem; font-size: 0.95rem; font-weight: 600; color: var(--ap-ink); }
.ap-empty p { max-width: 26rem; margin: 0 auto 1rem; font-size: 0.8125rem; color: var(--ap-muted); }

/* ---------- Responsive ---------- */

@media (max-width: 1200px) {
    .ap-stats { grid-template-columns: repeat(3, minmax(0, 1fr)); }
}

@media (max-width: 992px) {
    .ap-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}

@media (max-width: 768px) {
    .ap-head { flex-direction: column; align-items: stretch; }
    .ap-head-actions .ap-btn { flex: 1; }

    .ap-filters .ap-search { flex-basis: 100%; max-width: none; }

    .ap-table thead { display: none; }

    .ap-table,
    .ap-table tbody,
    .ap-table tr,
    .ap-table td { display: block; width: 100%; }

    .ap-table tr.ap-row {
        padding: 0.9rem 1rem;
        border-bottom: 1px solid var(--ap-line);
    }

    .ap-row td:first-child::before { top: 0; bottom: 0; }

    .ap-table td {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 0.25rem 0;
        text-align: right;
        border: 0;
    }

    .ap-table td[data-label]::before {
        content: attr(data-label);
        flex-shrink: 0;
        font-size: 0.75rem;
        font-weight: 400;
        color: var(--ap-muted);
        text-align: left;
    }

    .ap-table td.ap-c-ref,
    .ap-table td:nth-child(2) { display: block; text-align: left; }
    .ap-table td.ap-c-ref::before,
    .ap-table td:nth-child(2)::before { content: none; }
    .ap-table td:nth-child(2) { margin: 0.35rem 0 0.5rem; }

    .ap-table td.ap-c-actions { justify-content: flex-end; margin-top: 0.5rem; }

    .ap-c-date,
    .ap-c-time { white-space: normal; }
}

@media (max-width: 576px) {
    .ap-stats { grid-template-columns: minmax(0, 1fr); gap: 0.6rem; }
}

@media (prefers-reduced-motion: reduce) {
    .ap *, .ap *::before, .ap *::after { transition: none !important; }
}

@media print {
    .ap-head-actions,
    .ap-tabs,
    .ap-filters,
    .ap-c-actions { display: none !important; }

    .ap-panel { border: 0; box-shadow: none; }
    .ap-row { display: table-row !important; }
}
</style>

<script>
(function () {
    'use strict';

    var search = document.getElementById('searchAppointments');
    var select = document.getElementById('filterStatus');
    var table  = document.getElementById('appointmentsTable');
    var noMatch = document.getElementById('apNoMatch');
    var clearBtn = document.getElementById('apClearFilters');

    function rows() {
        return table ? Array.prototype.slice.call(table.querySelectorAll('tbody tr.ap-row')) : [];
    }

    function applyFilters() {
        var term = search ? search.value.toLowerCase().trim() : '';
        var status = select ? select.value.toLowerCase() : '';
        var visible = 0;

        rows().forEach(function (row) {
            var text = row.textContent.toLowerCase();
            var rowStatus = (row.dataset.rowStatus || '').toLowerCase();

            var matchesSearch = term === '' || text.indexOf(term) !== -1;
            var matchesStatus = status === '' || rowStatus === status;

            var show = matchesSearch && matchesStatus;
            row.style.display = show ? '' : 'none';
            if (show) { visible++; }
        });

        if (noMatch) {
            noMatch.hidden = !(rows().length > 0 && visible === 0);
        }
    }

    if (search) {
        search.addEventListener('input', applyFilters);
        search.addEventListener('search', applyFilters);
    }

    if (select) {
        select.addEventListener('change', applyFilters);
    }

    var tabs = document.querySelectorAll('.ap-tab');

    function syncTabs(status) {
        tabs.forEach(function (tab) {
            var on = tab.dataset.status === status;
            tab.classList.toggle('is-active', on);
            tab.setAttribute('aria-pressed', on ? 'true' : 'false');
        });
    }

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            var status = tab.dataset.status || 'all';
            syncTabs(status);

            if (select) {
                select.value = status === 'all' ? '' : status;
                select.dispatchEvent(new Event('change'));
            } else {
                applyFilters();
            }
        });
    });

    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            if (search) { search.value = ''; }
            if (select) { select.value = ''; }
            syncTabs('all');
            applyFilters();
        });
    }

})();
</script>

<?= $this->endSection() ?>