<?= $this->extend('layouts/AdminLayout') ?>

<?= $this->section('pageTitle') ?>Appointments<?= $this->endSection() ?>

<?= $this->section('adminContent') ?>

<?php
/* ------------------------------------------------------------------
   View-local helpers. No controller or database logic is changed.
   ------------------------------------------------------------------ */
$appointmentList = (isset($appointments) && is_array($appointments)) ? $appointments : [];

/* Reduce any value to a safe CSS class fragment. */
$slug = static function ($value, $fallback = '') {
    $value = strtolower(trim((string) $value));
    $value = preg_replace('/[^a-z0-9_-]/', '', $value);
    return $value !== '' ? $value : $fallback;
};

$initialsOf = static function ($name) {
    $name = trim((string) $name);
    if ($name === '') { return '?'; }
    $parts = preg_split('/\s+/', $name);
    $first = function_exists('mb_substr') ? mb_substr($parts[0], 0, 1) : substr($parts[0], 0, 1);
    $last  = '';
    if (count($parts) > 1) {
        $lastPart = end($parts);
        $last = function_exists('mb_substr') ? mb_substr($lastPart, 0, 1) : substr($lastPart, 0, 1);
    }
    $out = $first . $last;
    return function_exists('mb_strtoupper') ? mb_strtoupper($out) : strtoupper($out);
};

/* PNG avatar filenames inside public/assets/images/. */
$maleAvatar   = 'man-avatar.png';
$femaleAvatar = 'woman-avatar.png';

$statusMeta = [
    'pending'   => ['label' => 'Pending',   'tone' => 'pending'],
    'approved'  => ['label' => 'Approved',  'tone' => 'approved'],
    'completed' => ['label' => 'Completed', 'tone' => 'completed'],
    'cancelled' => ['label' => 'Cancelled', 'tone' => 'cancelled'],
    'late'      => ['label' => 'Late',      'tone' => 'late'],
];

/* Tally each status so the tabs can show real counts. */
$statusCounts = ['all' => count($appointmentList)];
foreach ($appointmentList as $row) {
    $s = $slug($row['status'] ?? '', 'pending');
    $statusCounts[$s] = ($statusCounts[$s] ?? 0) + 1;
}

$filterTabs = [
    'all'       => ['label' => 'All',       'count' => $statusCounts['all']       ?? 0],
    'pending'   => ['label' => 'Pending',   'count' => $statusCounts['pending']   ?? 0],
    'approved'  => ['label' => 'Approved',  'count' => $statusCounts['approved']  ?? 0],
    'completed' => ['label' => 'Completed', 'count' => $statusCounts['completed'] ?? 0],
    'cancelled' => ['label' => 'Cancelled', 'count' => $statusCounts['cancelled'] ?? 0],
    'late'      => ['label' => 'Late',      'count' => $statusCounts['late']      ?? 0],
];

/* Export rows. Source column has been removed. */
$exportRows = [];
foreach ($appointmentList as $appt) {
    $id = (string) ($appt['id'] ?? '');
    if ($id === '') { continue; }

    $labList  = json_decode($appt['lab_services']  ?? '[]', true);
    $xrayList = json_decode($appt['xray_services'] ?? '[]', true);

    $labList  = is_array($labList)  ? array_filter(array_map('trim', $labList))  : [];
    $xrayList = is_array($xrayList) ? array_filter(array_map('trim', $xrayList)) : [];
    $services = array_merge($labList, $xrayList);

    $exportRows[$id] = [
        'reference' => (string) ($appt['reference_number'] ?? ''),
        'patient'   => (string) ($appt['full_name'] ?? ''),
        'age'       => $appt['age'] ?? '',
        'sex'       => (string) ($appt['gender'] ?? ''),
        'type'      => (!empty($labList) && !empty($xrayList))
                        ? 'Both'
                        : (!empty($xrayList) ? 'X-Ray' : (!empty($labList) ? 'Laboratory' : '')),
        'status'    => ucfirst($slug($appt['status'] ?? '', 'pending')),
        'services'  => implode('; ', $services),
        'phone'     => (string) ($appt['phone'] ?? ''),
        'email'     => (string) ($appt['email'] ?? ''),
        'requested' => !empty($appt['appointment_date']) ? $appt['appointment_date'] . ' ' . ($appt['appointment_time'] ?? '') : '',
    ];
}
?>

<div class="dx">

    <?php if (session()->getFlashdata('success')): ?>
        <div class="dx-alert dx-alert--success alert alert-dismissible fade show" role="status">
            <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
            <span><?= esc(session()->getFlashdata('success')) ?></span>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Dismiss"></button>
        </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="dx-alert dx-alert--error alert alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i>
            <span><?= esc(session()->getFlashdata('error')) ?></span>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Dismiss"></button>
        </div>
    <?php endif; ?>


    <!-- PAGE HEADER -->
    <header class="dx-head">
        <div>
            <h2 class="dx-title">Appointments</h2>
        </div>

        <div class="dx-head-actions">
            <button type="button" class="dx-btn" id="dxExport">
                <i class="bi bi-download" aria-hidden="true"></i>
                Export CSV
            </button>
            <a class="dx-btn dx-btn--primary" href="<?= base_url('appointment/book') ?>" target="_blank" rel="noopener">
                <i class="bi bi-plus-lg" aria-hidden="true"></i>
                New booking
            </a>
        </div>
    </header>


    <!-- WORKLIST -->
    <section class="dx-panel" aria-label="Appointment worklist">

        <div class="dx-tabs" role="group" aria-label="Filter by status">
            <?php foreach ($filterTabs as $key => $tab): ?>
                <button type="button"
                        class="dx-tab<?= $key === 'all' ? ' is-active' : '' ?>"
                        data-status="<?= esc($key, 'attr') ?>"
                        aria-pressed="<?= $key === 'all' ? 'true' : 'false' ?>">
                    <?= esc($tab['label']) ?>
                    <span class="dx-tab-count"><?= (int) $tab['count'] ?></span>
                </button>
            <?php endforeach; ?>
        </div>

        <div class="dx-filters">
            <div class="dx-search">
                <i class="bi bi-search" aria-hidden="true"></i>
                <label class="visually-hidden" for="searchAppointments">Search appointments</label>
                <input type="search"
                       class="dx-input"
                       id="searchAppointments"
                       placeholder="Search patient, reference, email or phone"
                       autocomplete="off">
                <kbd class="dx-kbd" aria-hidden="true">/</kbd>
            </div>
        </div>

        <div class="dx-grid-wrap" id="dxTableWrap"<?= empty($appointmentList) ? ' hidden' : '' ?>>
            <div class="dx-cards" id="dxRows">
                <?php foreach ($appointmentList as $appt):

                    $id        = (string) ($appt['id'] ?? '');
                    $reference = (string) ($appt['reference_number'] ?? '');
                    $patient   = trim((string) ($appt['full_name'] ?? '')) ?: 'Unknown patient';
                    $status    = $slug($appt['status'] ?? '', 'pending');
                    $meta      = $statusMeta[$status] ?? $statusMeta['pending'];
                    $isLate    = $status === 'late';
                    $canApprove  = in_array($status, ['pending', 'late'], true);
                    $canCancel   = !in_array($status, ['completed', 'cancelled'], true);
                    $isCancelled = $status === 'cancelled';
                    $isCompleted = $status === 'completed';

                    $ageRaw = $appt['age'] ?? '';
                    $ageText = is_numeric($ageRaw) ? ((int) $ageRaw) . ' y' : '';

                    $gender   = trim((string) ($appt['gender'] ?? ''));
                    $gender   = strtoupper($gender) === 'N/A' ? '' : $gender;
                    $demo     = implode(' · ', array_filter([$ageText, $gender]));

                    $genderRaw  = strtolower($gender);
                    $isMale     = $genderRaw === 'male'   || $genderRaw === 'm';
                    $isFemale   = $genderRaw === 'female' || $genderRaw === 'f';
                    $avatarKind = $isMale ? 'male' : ($isFemale ? 'female' : 'neutral');

                    $email = trim((string) ($appt['email'] ?? ''));
                    $phone = trim((string) ($appt['phone'] ?? ''));

                    $dateTs  = !empty($appt['appointment_date']) ? strtotime($appt['appointment_date']) : false;
                    $timeTs  = !empty($appt['appointment_time']) ? strtotime($appt['appointment_time']) : false;

                    $labList  = json_decode($appt['lab_services']  ?? '[]', true);
                    $xrayList = json_decode($appt['xray_services'] ?? '[]', true);

                    $labList  = is_array($labList)  ? array_values(array_filter(array_map('trim', array_map('strval', $labList))))  : [];
                    $xrayList = is_array($xrayList) ? array_values(array_filter(array_map('trim', array_map('strval', $xrayList)))) : [];

                    $allServices = array_merge($labList, $xrayList);

                    if (!empty($labList) && !empty($xrayList)) {
                        $kindLabel = 'Both';
                        $kindSlug  = 'both';
                    } elseif (!empty($xrayList)) {
                        $kindLabel = 'X-Ray';
                        $kindSlug  = 'xray';
                    } elseif (!empty($labList)) {
                        $kindLabel = 'Laboratory';
                        $kindSlug  = 'lab';
                    } else {
                        $kindLabel = 'Unspecified';
                        $kindSlug  = 'lab';
                    }

                    $shown = array_slice($allServices, 0, 3);
                    $extra = count($allServices) - count($shown);

                    $search = strtolower(implode(' ', array_filter([
                        $patient, $reference, $email, $phone, $kindLabel,
                        implode(' ', $allServices),
                    ])));

                    /* Show the LATE tag only when the status pill isn't
                       already saying "Late" — otherwise the card shows
                       "LATE" and "Late" side-by-side. */
                    $showLateTag = $isLate && $status !== 'late';
                ?>
                    <article class="dx-card<?= $isLate ? ' is-stat' : '' ?>"
                             data-key="<?= esc($id, 'attr') ?>"
                             data-id="<?= esc($id, 'attr') ?>"
                             data-status="<?= esc($status, 'attr') ?>"
                             data-reference="<?= esc($reference, 'attr') ?>"
                             data-patient="<?= esc($patient, 'attr') ?>"
                             data-search="<?= esc($search, 'attr') ?>">

                        <header class="dx-card-head">
                            <button type="button"
                                    class="dx-ref"
                                    aria-label="Open <?= esc($reference, 'attr') ?> for <?= esc($patient, 'attr') ?>">
                                <?= esc($reference) ?>
                            </button>

                            <div class="dx-card-flags">
                                <?php if ($showLateTag): ?>
                                    <span class="dx-tag-stat" title="Late">LATE</span>
                                <?php endif; ?>
                                <span class="dx-status dx-status--<?= esc($meta['tone'], 'attr') ?>">
                                    <?= esc($meta['label']) ?>
                                </span>
                            </div>
                        </header>

                        <div class="dx-card-patient">
                            <span class="dx-avatar dx-avatar--<?= esc($avatarKind, 'attr') ?>" aria-hidden="true">
                                <?php if ($isMale): ?>
                                    <img src="<?= esc(base_url('assets/images/' . $maleAvatar), 'attr') ?>"
                                         alt=""
                                         class="dx-avatar-img"
                                         loading="lazy"
                                         decoding="async">
                                <?php elseif ($isFemale): ?>
                                    <img src="<?= esc(base_url('assets/images/' . $femaleAvatar), 'attr') ?>"
                                         alt=""
                                         class="dx-avatar-img"
                                         loading="lazy"
                                         decoding="async">
                                <?php else: ?>
                                    <?= esc($initialsOf($patient)) ?>
                                <?php endif; ?>
                            </span>

                            <div class="dx-card-patient-body">
                                <h3 class="dx-card-name"><?= esc($patient) ?></h3>
                                <p class="dx-card-demo">
                                    <?php if ($demo !== ''): ?>
                                        <?= esc($demo) ?>
                                    <?php else: ?>
                                        <span class="dx-muted">No age or sex on file</span>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>

                        <div class="dx-card-tags">
                            <span class="dx-kind dx-kind--<?= esc($kindSlug, 'attr') ?>">
                                <i class="bi <?= $kindSlug === 'xray' ? 'bi-radioactive' : ($kindSlug === 'both' ? 'bi-stack' : 'bi-droplet-half') ?>" aria-hidden="true"></i>
                                <?= esc($kindLabel) ?>
                            </span>
                        </div>

                        <div class="dx-card-services">
                            <?php if ($allServices): ?>
                                <?php foreach ($shown as $service): ?>
                                    <span class="dx-chip"><?= esc($service) ?></span>
                                <?php endforeach; ?>
                                <?php if ($extra > 0): ?>
                                    <span class="dx-chip dx-chip--more">+<?= $extra ?> more</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="dx-muted">No services listed</span>
                            <?php endif; ?>
                        </div>

                        <dl class="dx-card-meta">
                            <div>
                                <dt>Scheduled</dt>
                                <dd>
                                    <?php if ($dateTs): ?>
                                        <time datetime="<?= esc(date('c', $dateTs), 'attr') ?>">
                                            <?= esc(date('M j, Y', $dateTs)) ?>
                                            <?php if ($timeTs): ?>
                                                <span aria-hidden="true">·</span>
                                                <?= esc(date('g:i A', $timeTs)) ?>
                                            <?php endif; ?>
                                        </time>
                                    <?php else: ?>
                                        &mdash;
                                    <?php endif; ?>
                                </dd>
                            </div>
                            <div>
                                <dt>Contact</dt>
                                <dd>
                                    <?php if ($phone !== '' || $email !== ''): ?>
                                        <?= esc($phone !== '' ? $phone : $email) ?>
                                    <?php else: ?>
                                        <span class="dx-muted">&mdash;</span>
                                    <?php endif; ?>
                                </dd>
                            </div>
                        </dl>

                        <footer class="dx-card-foot">
                            <a class="dx-btn dx-btn--sm dx-btn--primary"
                               href="<?= base_url('admin/appointment/view/' . (int) $id) ?>"
                               aria-label="View appointment <?= esc($reference, 'attr') ?>">
                                <i class="bi bi-eye" aria-hidden="true"></i>
                                View
                            </a>

                            <?php if ($canApprove): ?>
                                <a class="dx-btn dx-btn--sm dx-btn--success"
                                   href="<?= base_url('admin/appointment/approve/' . (int) $id) ?>"
                                   aria-label="Approve appointment <?= esc($reference, 'attr') ?>"
                                   onclick="return confirm('Approve this appointment?')">
                                    <i class="bi bi-check2-circle" aria-hidden="true"></i>
                                    Approve
                                </a>
                            <?php endif; ?>

                            <div class="dropdown dx-card-menu">
                                <button type="button"
                                        class="dx-icon-btn"
                                        data-bs-toggle="dropdown"
                                        data-bs-popper-config='{"strategy":"fixed"}'
                                        aria-expanded="false"
                                        aria-label="More actions for <?= esc($reference, 'attr') ?>">
                                    <i class="bi bi-three-dots" aria-hidden="true"></i>
                                </button>

                                <ul class="dropdown-menu dropdown-menu-end dx-menu">
                                    <li>
                                        <a class="dropdown-item"
                                           href="<?= base_url('admin/appointment/view/' . (int) $id) ?>">
                                            <i class="bi bi-eye" aria-hidden="true"></i>
                                            View details
                                        </a>
                                    </li>

                                    <?php if ($canApprove): ?>
                                        <li>
                                            <a class="dropdown-item"
                                               href="<?= base_url('admin/appointment/approve/' . (int) $id) ?>"
                                               onclick="return confirm('Approve this appointment?')">
                                                <i class="bi bi-check2-circle" aria-hidden="true"></i>
                                                Approve appointment
                                            </a>
                                        </li>
                                    <?php endif; ?>

                                    <?php if ($canCancel): ?>
                                        <li>
                                            <a class="dropdown-item dx-menu-danger"
                                               href="<?= base_url('admin/appointment/cancel/' . (int) $id) ?>"
                                               onclick="return confirm('Cancel this appointment?')">
                                                <i class="bi bi-x-circle" aria-hidden="true"></i>
                                                Cancel appointment
                                            </a>
                                        </li>
                                    <?php endif; ?>

                                    <?php if ($isCancelled || $isCompleted): ?>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <span class="dropdown-item-text dx-menu-note">
                                                <i class="bi bi-lock" aria-hidden="true"></i>
                                                <?= $isCancelled ? 'Cancelled — no further actions' : 'Completed — no further actions' ?>
                                            </span>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </footer>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- No appointments at all -->
        <div class="dx-empty" id="dxEmpty"<?= empty($appointmentList) ? '' : ' hidden' ?>>
            <div class="dx-empty-icon"><i class="bi bi-inbox" aria-hidden="true"></i></div>
            <h3>No appointments yet</h3>
            <p>Bookings made online and walk-in registrations will appear here.</p>
            <a class="dx-btn dx-btn--primary" href="<?= base_url('appointment/book') ?>" target="_blank" rel="noopener">
                <i class="bi bi-plus-lg" aria-hidden="true"></i>
                New booking
            </a>
        </div>

        <!-- Filters match nothing -->
        <div class="dx-empty" id="dxNoMatch" hidden>
            <div class="dx-empty-icon"><i class="bi bi-search" aria-hidden="true"></i></div>
            <h3>No matching appointments</h3>
            <p>Try a different search term or clear the filters.</p>
            <button type="button" class="dx-btn" id="dxClearFilters">Clear filters</button>
        </div>

        <footer class="dx-foot" id="dxFoot"<?= empty($appointmentList) ? ' hidden' : '' ?>>
            <span id="dxRange" aria-live="polite"></span>
            <nav class="dx-pager" id="dxPager" aria-label="Pagination"></nav>
        </footer>

    </section>

</div>

<div class="dx-toast" id="dxToast" role="status" aria-live="polite" hidden></div>

<style>
/* =========================================================
   APPOINTMENTS
   Namespaced under .dx so the admin layout's generic card
   and table rules cannot leak in.
   ========================================================= */

.dx {
    --dx-ink:         #0f172a;
    --dx-text:        #334155;
    --dx-muted:       #64748b;
    --dx-faint:       #94a3b8;
    --dx-line:        #e2e8f0;
    --dx-line-soft:   #f1f5f9;
    --dx-surface:     #ffffff;
    --dx-subtle:      #f8fafc;
    --dx-accent:      #1976d2;
    --dx-accent-dark: #1565c0;
    --dx-accent-soft: #e8f1fb;
    --dx-success:     #16a34a;
    --dx-success-dark:#15803d;
    --dx-danger:      #dc2626;
    --dx-danger-dark: #b91c1c;
    --dx-radius:      10px;
    --dx-radius-sm:   7px;
    --dx-ring:        0 0 0 3px rgba(25, 118, 210, 0.2);
    --dx-mono:        ui-monospace, SFMono-Regular, "SF Mono", Menlo, Consolas, monospace;

    color: var(--dx-text);
}

.dx *:focus-visible {
    outline: 2px solid var(--dx-accent);
    outline-offset: 2px;
}

.dx-muted { color: var(--dx-faint); }

/* ---------- Alerts ---------- */

.dx-alert {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    padding: 0.75rem 1rem;
    margin-bottom: 1rem;
    border: 1px solid transparent;
    border-radius: var(--dx-radius);
    font-size: 0.8125rem;
}

.dx-alert > span { flex: 1; min-width: 0; }
.dx-alert.alert-dismissible { padding-right: 0.75rem; }
.dx-alert .btn-close { position: static; padding: 0.5rem; margin-left: auto; font-size: 0.7rem; }
.dx-alert--success { background: #f0fdf4; border-color: #bbf7d0; color: #166534; }
.dx-alert--error   { background: #fef2f2; border-color: #fecaca; color: #991b1b; }

/* ---------- Page header ---------- */

.dx-head {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
    margin-bottom: 1.25rem;
}

.dx-title {
    margin: 0 0 0.2rem;
    font-size: 1.25rem;
    font-weight: 650;
    letter-spacing: -0.015em;
    color: var(--dx-ink);
}

.dx-lede {
    margin: 0;
    font-size: 0.8125rem;
    color: var(--dx-muted);
}

.dx-head-actions { display: flex; gap: 0.5rem; }

/* ---------- Buttons ---------- */

.dx-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.4rem;
    height: 36px;
    padding: 0 0.9rem;
    font-size: 0.8125rem;
    font-weight: 600;
    line-height: 1;
    color: var(--dx-text);
    background: var(--dx-surface);
    border: 1px solid var(--dx-line);
    border-radius: var(--dx-radius-sm);
    box-shadow: 0 1px 1px rgba(15, 23, 42, 0.03);
    white-space: nowrap;
    text-decoration: none;
    cursor: pointer;
    transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
}

.dx-btn:hover { background: var(--dx-subtle); border-color: #cbd5e1; color: var(--dx-ink); }
.dx-btn:disabled { opacity: 0.6; cursor: not-allowed; }
.dx-btn i { font-size: 0.9em; }

.dx-btn--primary,
.dx-btn--primary:hover { color: #ffffff; }
.dx-btn--primary { background: var(--dx-accent); border-color: var(--dx-accent); }
.dx-btn--primary:hover { background: var(--dx-accent-dark); border-color: var(--dx-accent-dark); }

.dx-btn--success,
.dx-btn--success:hover { color: #ffffff; }
.dx-btn--success { background: var(--dx-success); border-color: var(--dx-success); }
.dx-btn--success:hover { background: var(--dx-success-dark); border-color: var(--dx-success-dark); }

.dx-btn--danger,
.dx-btn--danger:hover { color: #ffffff; }
.dx-btn--danger { background: var(--dx-danger); border-color: var(--dx-danger); }
.dx-btn--danger:hover { background: var(--dx-danger-dark); border-color: var(--dx-danger-dark); }

.dx-btn--sm { height: 32px; padding: 0 0.75rem; font-size: 0.78rem; flex: 1; min-width: 0; }

/* Icon-only button used for the kebab (⋯) menu trigger. */
.dx-icon-btn {
    width: 32px;
    height: 32px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0;
    color: var(--dx-muted);
    background: transparent;
    border: 1px solid transparent;
    border-radius: var(--dx-radius-sm);
    cursor: pointer;
    transition: background-color 0.15s ease, color 0.15s ease, border-color 0.15s ease;
}

.dx-icon-btn:hover,
.dx-icon-btn[aria-expanded="true"] {
    background: var(--dx-line-soft);
    color: var(--dx-ink);
    border-color: var(--dx-line);
}

/* ---------- Inputs ---------- */

.dx-input,
.dx-select {
    width: 100%;
    height: 36px;
    padding: 0 0.75rem;
    font-size: 0.8125rem;
    color: var(--dx-ink);
    background-color: var(--dx-surface);
    border: 1px solid var(--dx-line);
    border-radius: var(--dx-radius-sm);
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

.dx-input::placeholder { color: var(--dx-faint); }

.dx-input:focus,
.dx-select:focus {
    outline: none;
    border-color: var(--dx-accent);
    box-shadow: var(--dx-ring);
}

.dx-search { position: relative; flex: 1 1 280px; max-width: 380px; }
.dx-search > i {
    position: absolute;
    left: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    font-size: 0.8rem;
    color: var(--dx-faint);
    pointer-events: none;
}

.dx-search .dx-input { padding-left: 2.1rem; }

.dx-kbd {
    position: absolute;
    right: 0.6rem;
    top: 50%;
    transform: translateY(-50%);
    padding: 0.05rem 0.35rem;
    font-family: var(--dx-mono);
    font-size: 0.7rem;
    line-height: 1.3;
    color: var(--dx-muted);
    background: var(--dx-subtle);
    border: 1px solid var(--dx-line);
    border-radius: 4px;
    box-shadow: none;
    pointer-events: none;
}

.dx-search .dx-input:focus ~ .dx-kbd,
.dx-search .dx-input:not(:placeholder-shown) ~ .dx-kbd { display: none; }

/* ---------- Worklist panel ---------- */

.dx-panel {
    background: var(--dx-surface);
    border: 1px solid var(--dx-line);
    border-radius: 12px;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    overflow: hidden;
}

.dx-tabs {
    display: flex;
    gap: 0.25rem;
    padding: 0 0.75rem;
    border-bottom: 1px solid var(--dx-line);
    overflow-x: auto;
    scrollbar-width: none;
}

.dx-tabs::-webkit-scrollbar { display: none; }

.dx-tab {
    position: relative;
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    padding: 0.85rem 0.6rem;
    font-size: 0.8125rem;
    font-weight: 600;
    color: var(--dx-muted);
    background: none;
    border: 0;
    white-space: nowrap;
    cursor: pointer;
    transition: color 0.15s ease;
}

.dx-tab::after {
    content: "";
    position: absolute;
    left: 0.4rem;
    right: 0.4rem;
    bottom: -1px;
    height: 2px;
    border-radius: 2px 2px 0 0;
    background: transparent;
}

.dx-tab:hover { color: var(--dx-ink); }
.dx-tab.is-active { color: var(--dx-ink); }
.dx-tab.is-active::after { background: var(--dx-accent); }

.dx-tab-count {
    min-width: 1.4rem;
    padding: 0.05rem 0.4rem;
    font-size: 0.7rem;
    font-weight: 600;
    font-variant-numeric: tabular-nums;
    text-align: center;
    color: var(--dx-muted);
    background: var(--dx-line-soft);
    border-radius: 999px;
}

.dx-tab.is-active .dx-tab-count { color: var(--dx-accent); background: var(--dx-accent-soft); }

.dx-filters {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
    padding: 0.75rem 1rem;
}

/* ---------- Card grid ---------- */

.dx-grid-wrap {
    border-top: 1px solid var(--dx-line);
    background: #f8fafc;
}

.dx-cards {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 1.1rem;
    padding: 1.15rem;
}

.dx-card {
    position: relative;
    display: flex;
    flex-direction: column;
    min-width: 0;
    padding: 1rem 1.05rem 1rem;
    background: var(--dx-surface);
    border: 1px solid #d4dbe5;
    border-radius: var(--dx-radius);
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04), 0 1px 3px rgba(15, 23, 42, 0.06);
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

.dx-card:hover {
    border-color: #b6c2d2;
    box-shadow: 0 8px 20px -8px rgba(15, 23, 42, 0.25), 0 2px 4px rgba(15, 23, 42, 0.06);
}

.dx-card.is-stat {
    background-image: linear-gradient(180deg, rgba(254, 242, 242, 0.55), transparent 45%);
}

.dx-card.is-hidden { display: none; }

/* ---- header ---- */

.dx-card-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    margin-bottom: 0.6rem;
}

.dx-ref {
    padding: 0;
    font-family: var(--dx-mono);
    font-size: 0.76rem;
    font-weight: 600;
    letter-spacing: 0.01em;
    color: var(--dx-ink);
    background: none;
    border: 0;
    white-space: nowrap;
    cursor: default;
}

.dx-card-flags {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    flex-shrink: 0;
}

.dx-tag-stat {
    padding: 0 0.35rem;
    font-size: 0.62rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    line-height: 1.6;
    color: var(--dx-danger-dark);
    background: #fef2f2;
    border: 1px solid #fecaca;
    border-radius: 4px;
}

/* ---- patient block ---- */

.dx-card-patient {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    margin-bottom: 0.7rem;
}

.dx-avatar {
    display: grid;
    place-items: center;
    flex-shrink: 0;
    width: 36px;
    height: 36px;
    font-size: 0.72rem;
    font-weight: 700;
    color: var(--dx-accent);
    background: var(--dx-accent-soft);
    border-radius: 50%;
    overflow: hidden;
}

.dx-avatar--male    { color: #1d4ed8; background: #eaf2fe; }
.dx-avatar--female  { color: #b32e50; background: #fce9ee; }
.dx-avatar--neutral { color: var(--dx-accent); background: var(--dx-accent-soft); }

.dx-avatar-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.dx-card-patient-body { min-width: 0; }

.dx-card-name {
    margin: 0;
    font-size: 0.95rem;
    font-weight: 650;
    letter-spacing: -0.01em;
    line-height: 1.3;
    color: var(--dx-ink);
    overflow-wrap: anywhere;
}

.dx-card-demo {
    margin: 0.1rem 0 0;
    font-size: 0.76rem;
    color: var(--dx-muted);
}

/* ---- kind chip ---- */

.dx-card-tags {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    flex-wrap: wrap;
    margin-bottom: 0.7rem;
}

.dx-kind {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    padding: 0.15rem 0.5rem;
    font-size: 0.72rem;
    font-weight: 600;
    border-radius: 5px;
    border: 1px solid transparent;
    white-space: nowrap;
}

.dx-kind--lab  { color: #0e7490; background: #ecfeff; border-color: #cffafe; }
.dx-kind--xray { color: #4338ca; background: #eef2ff; border-color: #e0e7ff; }
.dx-kind--both { color: #b45309; background: #fff7ed; border-color: #fed7aa; }

.dx-kind i { font-size: 0.72em; }

/* ---- services ---- */

.dx-card-services {
    display: flex;
    flex-wrap: wrap;
    gap: 0.3rem;
    margin-bottom: 0.85rem;
    min-height: 1.55rem;
}

.dx-chip {
    padding: 0.15rem 0.5rem;
    font-size: 0.72rem;
    color: var(--dx-text);
    background: var(--dx-surface);
    border: 1px solid var(--dx-line);
    border-radius: 5px;
    white-space: nowrap;
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
}

.dx-chip--more {
    color: var(--dx-muted);
    background: var(--dx-line-soft);
    font-weight: 600;
}

/* ---- meta ---- */

.dx-card-meta {
    display: grid;
    grid-template-columns: 1fr;
    gap: 0.4rem;
    margin: 0 0 0.85rem;
    padding: 0.7rem 0;
    border-top: 1px solid var(--dx-line-soft);
    border-bottom: 1px solid var(--dx-line-soft);
    font-size: 0.78rem;
}

.dx-card-meta > div {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: 0.75rem;
    min-width: 0;
}

.dx-card-meta dt {
    flex-shrink: 0;
    color: var(--dx-muted);
    font-weight: 500;
}

.dx-card-meta dd {
    margin: 0;
    text-align: right;
    color: var(--dx-ink);
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.dx-card-meta time { font-variant-numeric: tabular-nums; }
.dx-card-meta time > span { color: var(--dx-faint); margin: 0 0.1rem; }

/* ---- footer ---- */

.dx-card-foot {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    margin-top: auto;
    flex-wrap: wrap;
}

/* The kebab menu sits flush against the right edge of the footer.
   When only View is present, the menu still pins to the right. */
.dx-card-menu {
    flex-shrink: 0;
    margin-left: auto;
}

/* ---- dropdown menu ---- */

.dx-menu {
    min-width: 13rem;
    padding: 0.3rem;
    font-size: 0.8125rem;
    border: 1px solid var(--dx-line);
    border-radius: 9px;
    box-shadow: 0 12px 28px -8px rgba(15, 23, 42, 0.22), 0 2px 6px rgba(15, 23, 42, 0.06);
    background: var(--dx-surface);
}

.dx-menu .dropdown-item {
    display: flex;
    align-items: center;
    gap: 0.55rem;
    padding: 0.5rem 0.65rem;
    color: var(--dx-text);
    border-radius: 6px;
    text-decoration: none;
    font-weight: 500;
}

.dx-menu .dropdown-item i {
    font-size: 0.9rem;
    color: var(--dx-faint);
    width: 1.05rem;
    text-align: center;
    flex-shrink: 0;
}

.dx-menu .dropdown-item:hover,
.dx-menu .dropdown-item:focus {
    background: var(--dx-line-soft);
    color: var(--dx-ink);
}

.dx-menu .dropdown-item:hover i,
.dx-menu .dropdown-item:focus i {
    color: var(--dx-text);
}

.dx-menu .dropdown-divider {
    margin: 0.3rem 0.2rem;
    border-color: var(--dx-line-soft);
}

/* Red variant for the destructive Cancel action. */
.dx-menu .dx-menu-danger,
.dx-menu .dx-menu-danger i { color: var(--dx-danger); }

.dx-menu .dx-menu-danger:hover,
.dx-menu .dx-menu-danger:focus {
    background: #fef2f2;
    color: var(--dx-danger-dark);
}

.dx-menu .dx-menu-danger:hover i,
.dx-menu .dx-menu-danger:focus i {
    color: var(--dx-danger-dark);
}

/* Read-only note shown for completed / cancelled rows. */
.dx-menu .dx-menu-note {
    display: flex;
    align-items: center;
    gap: 0.55rem;
    padding: 0.4rem 0.65rem;
    font-size: 0.75rem;
    color: var(--dx-faint);
    cursor: default;
}

.dx-menu .dx-menu-note i { color: var(--dx-faint); font-size: 0.8rem; }
.dx-menu .dx-menu-note:hover { background: transparent; }

/* ---- status pill ---- */

.dx-status {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.2rem 0.55rem;
    font-size: 0.72rem;
    font-weight: 600;
    white-space: nowrap;
    color: var(--tone-fg);
    background: var(--tone-bg);
    border-radius: 999px;
}

.dx-status::before {
    content: "";
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: var(--tone-dot);
}

.dx-status--pending   { --tone-bg: #fffbeb; --tone-fg: #b45309; --tone-dot: #f59e0b; }
.dx-status--approved  { --tone-bg: #eff6ff; --tone-fg: #1d4ed8; --tone-dot: #3b82f6; }
.dx-status--completed { --tone-bg: #ecfdf5; --tone-fg: #047857; --tone-dot: #10b981; }
.dx-status--cancelled { --tone-bg: #f1f5f9; --tone-fg: #64748b; --tone-dot: #94a3b8; }
.dx-status--late      { --tone-bg: #fff7ed; --tone-fg: #b45309; --tone-dot: #f97316; }

/* ---------- Empty states and footer ---------- */

.dx-empty { padding: 3.5rem 1rem; text-align: center; border-top: 1px solid var(--dx-line); }

.dx-empty-icon {
    display: grid;
    place-items: center;
    width: 44px;
    height: 44px;
    margin: 0 auto 0.85rem;
    font-size: 1.2rem;
    color: var(--dx-faint);
    background: var(--dx-line-soft);
    border-radius: 10px;
}

.dx-empty h3 { margin: 0 0 0.25rem; font-size: 0.95rem; font-weight: 600; color: var(--dx-ink); }
.dx-empty p { max-width: 26rem; margin: 0 auto 1rem; font-size: 0.8125rem; color: var(--dx-muted); }

.dx-foot {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    flex-wrap: wrap;
    padding: 0.7rem 1rem;
    font-size: 0.78rem;
    color: var(--dx-muted);
    border-top: 1px solid var(--dx-line);
}

.dx-foot strong { font-weight: 600; color: var(--dx-ink); font-variant-numeric: tabular-nums; }

.dx-pager { display: flex; align-items: center; gap: 0.25rem; }

.dx-page {
    min-width: 30px;
    height: 30px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0 0.45rem;
    font-size: 0.78rem;
    font-weight: 600;
    font-variant-numeric: tabular-nums;
    color: var(--dx-text);
    background: var(--dx-surface);
    border: 1px solid var(--dx-line);
    border-radius: var(--dx-radius-sm);
    cursor: pointer;
}

.dx-page:hover:not(:disabled) { background: var(--dx-subtle); border-color: #cbd5e1; }
.dx-page:disabled { opacity: 0.45; cursor: not-allowed; }
.dx-page[aria-current="page"] { color: var(--dx-accent); background: var(--dx-accent-soft); border-color: #bcd6f3; }
.dx-page-gap { min-width: 20px; text-align: center; color: var(--dx-faint); }

/* ---------- Toast ---------- */

.dx-toast {
    position: fixed;
    right: 1.25rem;
    bottom: 1.25rem;
    z-index: 1090;
    display: flex;
    align-items: center;
    gap: 0.55rem;
    max-width: min(26rem, calc(100vw - 2.5rem));
    padding: 0.7rem 1rem;
    font-size: 0.8125rem;
    color: #f8fafc;
    background: #0f172a;
    border-radius: 9px;
    box-shadow: 0 12px 28px -8px rgba(15, 23, 42, 0.45);
}

.dx-toast i { color: #34d399; }
.dx-toast.is-error i { color: #f87171; }

/* ---------- Responsive ---------- */

@media (max-width: 1200px) {
    .dx-cards { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}

@media (max-width: 768px) {
    .dx-head { flex-direction: column; align-items: stretch; }
    .dx-head-actions .dx-btn { flex: 1; }

    .dx-filters .dx-search { flex-basis: 100%; max-width: none; }
    .dx-kbd { display: none; }

    .dx-cards { grid-template-columns: minmax(0, 1fr); padding: 0.75rem; }

    .dx-card-meta > div { align-items: flex-start; flex-direction: column; gap: 0.1rem; }
    .dx-card-meta dd { text-align: left; white-space: normal; }

    .dx-foot { justify-content: center; }
}

@media (prefers-reduced-motion: reduce) {
    .dx * { transition: none !important; }
}

@media print {
    .dx-head-actions,
    .dx-tabs,
    .dx-filters,
    .dx-foot,
    .dx-card-foot { display: none !important; }

    .dx-panel { border: 0; box-shadow: none; }
    .dx-cards { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .dx-card.is-hidden { display: flex; }
}
</style>

<script>
(function () {
    'use strict';

    var PER_PAGE = 9;

    var EXPORT_ROWS = <?= json_encode($exportRows ?: new stdClass(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

    function byId(id) { return document.getElementById(id); }

    function esc(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    var container = byId('dxRows');
    var cards = container ? Array.prototype.slice.call(container.querySelectorAll('.dx-card')) : [];
    var matches = cards.slice();
    var state = { status: 'all', term: '', page: 1 };

    var searchInput = byId('searchAppointments');

    function applyFilters(keepPage) {
        matches = cards.filter(function (card) {
            return (state.status === 'all' || card.dataset.status === state.status) &&
                   (state.term === '' || (card.dataset.search || '').indexOf(state.term) !== -1);
        });
        if (!keepPage) { state.page = 1; }
        render();
    }

    function render() {
        var pages = Math.max(1, Math.ceil(matches.length / PER_PAGE));
        state.page = Math.min(Math.max(1, state.page), pages);

        var start = (state.page - 1) * PER_PAGE;
        var end   = Math.min(start + PER_PAGE, matches.length);

        cards.forEach(function (card) { card.classList.add('is-hidden'); });
        matches.slice(start, end).forEach(function (card) { card.classList.remove('is-hidden'); });

        var none = cards.length === 0;
        byId('dxEmpty').hidden     = !none;
        byId('dxNoMatch').hidden   = none || matches.length > 0;
        byId('dxTableWrap').hidden = none || matches.length === 0;
        byId('dxFoot').hidden      = none || matches.length === 0;

        byId('dxRange').innerHTML = matches.length
            ? 'Showing <strong>' + (start + 1) + '–' + end + '</strong> of <strong>' + matches.length + '</strong>'
            : '';

        renderPager(pages);
    }

    function renderPager(pages) {
        var pager = byId('dxPager');
        if (!pager) { return; }

        if (pages <= 1) { pager.innerHTML = ''; return; }

        var list = [];
        var i;

        if (pages <= 7) {
            for (i = 1; i <= pages; i++) { list.push(i); }
        } else {
            var from = Math.max(2, state.page - 1);
            var to   = Math.min(pages - 1, state.page + 1);
            if (state.page <= 3)         { from = 2; to = 4; }
            if (state.page >= pages - 2) { from = pages - 3; to = pages - 1; }

            list.push(1);
            if (from > 2) { list.push('gap'); }
            for (i = from; i <= to; i++) { list.push(i); }
            if (to < pages - 1) { list.push('gap'); }
            list.push(pages);
        }

        var html = '<button type="button" class="dx-page" data-page="' + (state.page - 1) + '"' +
                   (state.page === 1 ? ' disabled' : '') + ' aria-label="Previous page">' +
                   '<i class="bi bi-chevron-left" aria-hidden="true"></i></button>';

        list.forEach(function (item) {
            if (item === 'gap') {
                html += '<span class="dx-page-gap" aria-hidden="true">…</span>';
                return;
            }
            html += '<button type="button" class="dx-page" data-page="' + item + '"' +
                    (item === state.page ? ' aria-current="page"' : '') +
                    ' aria-label="Page ' + item + '">' + item + '</button>';
        });

        html += '<button type="button" class="dx-page" data-page="' + (state.page + 1) + '"' +
                (state.page === pages ? ' disabled' : '') + ' aria-label="Next page">' +
                '<i class="bi bi-chevron-right" aria-hidden="true"></i></button>';

        pager.innerHTML = html;
    }

    function setStatusTab(status) {
        document.querySelectorAll('.dx-tab').forEach(function (tab) {
            var on = tab.dataset.status === status;
            tab.classList.toggle('is-active', on);
            tab.setAttribute('aria-pressed', on ? 'true' : 'false');
        });
        state.status = status;
    }

    document.querySelectorAll('.dx-tab').forEach(function (tab) {
        tab.addEventListener('click', function () {
            setStatusTab(tab.dataset.status || 'all');
            applyFilters();
        });
    });

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            state.term = searchInput.value.toLowerCase().trim();
            applyFilters();
        });
    }

    byId('dxPager').addEventListener('click', function (e) {
        var btn = e.target.closest('.dx-page');
        if (!btn || btn.disabled) { return; }
        state.page = parseInt(btn.dataset.page, 10) || 1;
        render();
        byId('dxTableWrap').scrollIntoView({ block: 'nearest' });
    });

    byId('dxClearFilters').addEventListener('click', function () {
        if (searchInput) { searchInput.value = ''; }
        state.term = '';
        setStatusTab('all');
        applyFilters();
    });

    document.addEventListener('keydown', function (e) {
        if (e.key !== '/' || e.ctrlKey || e.metaKey || e.altKey) { return; }
        var t = e.target;
        if (t.isContentEditable || /^(INPUT|TEXTAREA|SELECT)$/.test(t.tagName)) { return; }
        if (document.querySelector('.modal.show')) { return; }
        e.preventDefault();
        if (searchInput) { searchInput.focus(); }
    });


    /* =====================================================
       EXPORT CSV
       ===================================================== */

    var EXPORT_COLUMNS = [
        ['reference', 'Reference'], ['patient', 'Patient'], ['age', 'Age'], ['sex', 'Sex'],
        ['type', 'Type'], ['status', 'Status'], ['services', 'Services'],
        ['phone', 'Phone'], ['email', 'Email'], ['requested', 'Scheduled']
    ];

    function csvCell(value) {
        var v = value == null ? '' : String(value);
        if (/^[=@\t\r]/.test(v) || /^[+\-](?![\d\s()]+$)/.test(v)) { v = "'" + v; }
        return '"' + v.replace(/"/g, '""') + '"';
    }

    byId('dxExport').addEventListener('click', function () {
        var list = matches
            .map(function (card) { return EXPORT_ROWS[card.dataset.key]; })
            .filter(Boolean);

        if (!list.length) {
            showToast('There are no appointments in the current view to export.', true);
            return;
        }

        var lines = [EXPORT_COLUMNS.map(function (c) { return csvCell(c[1]); }).join(',')];
        list.forEach(function (item) {
            lines.push(EXPORT_COLUMNS.map(function (c) { return csvCell(item[c[0]]); }).join(','));
        });

        var blob = new Blob([String.fromCharCode(0xFEFF) + lines.join('\r\n')], { type: 'text/csv;charset=utf-8' });
        var link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'appointments-' + new Date().toISOString().slice(0, 10) + '.csv';
        document.body.appendChild(link);
        link.click();
        link.remove();
        setTimeout(function () { URL.revokeObjectURL(link.href); }, 1000);

        showToast('Exported ' + list.length + ' appointment' + (list.length === 1 ? '' : 's'));
    });


    /* =====================================================
       TOAST
       ===================================================== */

    var toastTimer = null;

    function showToast(message, isError) {
        var el = byId('dxToast');
        if (!el) { return; }
        el.className = 'dx-toast' + (isError ? ' is-error' : '');
        el.innerHTML =
            '<i class="bi ' + (isError ? 'bi-exclamation-circle-fill' : 'bi-check-circle-fill') + '" aria-hidden="true"></i>' +
            '<span>' + esc(message) + '</span>';
        el.hidden = false;
        clearTimeout(toastTimer);
        toastTimer = setTimeout(function () { el.hidden = true; }, 4000);
    }


    /* =====================================================
       START
       ===================================================== */

    applyFilters();

})();
</script>

<?= $this->endSection() ?>