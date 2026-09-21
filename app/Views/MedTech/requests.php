<?= $this->extend('layouts/MedTechLayout') ?>

<?= $this->section('pageTitle') ?>Laboratory Requests<?= $this->endSection() ?>

<?= $this->section('medtechContent') ?>

<?php
/* ------------------------------------------------------------------
   Laboratory Requests — same UI as the Diagnostic Requests page.

   Row shape comes from the existing MedTech controller: id, patient_name,
   age, gender, lab_services, request_date, status — plus 'priority'
   when the controller supplies it (values: 'routine' | 'stat').

   NOTE: TAT is a placeholder random value in the original file. The
   view below keeps that behaviour so nothing breaks, but the value
   should come from a real controller column when one is available.
   ------------------------------------------------------------------ */

$statusMeta = [
    'pending'     => ['label' => 'Pending',     'tone' => 'pending'],
    'in_progress' => ['label' => 'In progress', 'tone' => 'progress'],
    'completed'   => ['label' => 'Completed',   'tone' => 'completed'],
    'released'    => ['label' => 'Released',    'tone' => 'released'],
    'draft'       => ['label' => 'Draft',       'tone' => 'draft'],
    'cancelled'   => ['label' => 'Cancelled',   'tone' => 'cancelled'],
];

$requests = is_array($requests ?? null) ? $requests : [];

$pendingTotal = (int) ($counts['pending'] ?? 0);
$exportRows   = [];

$initialsOf = static function ($name) {
    $parts = preg_split('/\s+/', trim((string) $name));
    $first = mb_substr($parts[0] ?? '', 0, 1);
    $last  = count($parts) > 1 ? mb_substr(end($parts), 0, 1) : '';
    return mb_strtoupper($first . $last);
};

$filterTabs = [
    'all'         => ['label' => 'All',         'count' => $counts['total']       ?? count($requests)],
    'pending'     => ['label' => 'Pending',     'count' => $counts['pending']     ?? 0],
    'in_progress' => ['label' => 'In progress', 'count' => $counts['in_progress'] ?? 0],
    'draft'       => ['label' => 'Drafts',      'count' => $counts['draft']       ?? 0],
    'completed'   => ['label' => 'Completed',   'count' => $counts['completed']   ?? 0],
    'released'    => ['label' => 'Released',    'count' => $counts['released']    ?? 0],
];
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
            <h2 class="dx-title">Laboratory Requests</h2>
        </div>

        <div class="dx-head-actions">
            <button type="button" class="dx-btn" id="dxExport">
                <i class="bi bi-download" aria-hidden="true"></i>
                Export CSV
            </button>
        </div>
    </header>


    <!-- WORKLIST -->
    <section class="dx-panel" aria-label="Laboratory request worklist">

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
                <label class="visually-hidden" for="searchRequests">Search requests</label>
                <input type="search"
                       class="dx-input"
                       id="searchRequests"
                       placeholder="Search patient, accession number, or test"
                       autocomplete="off">
                <kbd class="dx-kbd" aria-hidden="true">/</kbd>
            </div>
        </div>

        <div class="dx-grid-wrap" id="dxTableWrap"<?= empty($requests) ? ' hidden' : '' ?>>
            <div class="dx-cards" id="dxRows">
                <?php foreach ($requests as $request):

                    $id        = (string) ($request['id'] ?? '');
                    $key       = $id;
                    $accession = 'ACC-26-' . str_pad($id, 4, '0', STR_PAD_LEFT);
                    $patient   = (string) ($request['patient_name'] ?? 'Unknown patient');
                    $status    = strtolower((string) ($request['status'] ?? 'pending'));
                    $meta      = $statusMeta[$status] ?? $statusMeta['pending'];

                    /* Priority — same rule as the Admin diagnostic view.
                       Anything other than "stat" is treated as routine. */
                    $isStat = strtolower((string) ($request['priority'] ?? '')) === 'stat';

                    $age    = $request['age'] ?? '';
                    $gender = trim((string) ($request['gender'] ?? ''));
                    $gender = strtoupper($gender) === 'N/A' ? '' : $gender;

                    $ageText = is_numeric($age) ? ((int) $age) . ' yrs' : '';
                    $demo    = implode(' · ', array_filter([$ageText, $gender]));

                    $genderRaw  = strtolower($gender);
                    $isMale     = $genderRaw === 'male'   || $genderRaw === 'm';
                    $isFemale   = $genderRaw === 'female' || $genderRaw === 'f';
                    $avatarKind = $isMale ? 'male' : ($isFemale ? 'female' : 'neutral');

                    $services = isset($request['lab_services'])
                        ? array_values(array_filter(array_map('trim', explode(',', (string) $request['lab_services'])), 'strlen'))
                        : [];
                    $shown = array_slice($services, 0, 3);
                    $extra = count($services) - count($shown);

                    /*
                     * TAT placeholder — same as the previous view.
                     * Replace with $request['tat_minutes'] when the
                     * controller supplies a real value.
                     */
                    $tatMinutes = rand(18, 94);

                    $createdTs = !empty($request['request_date']) ? strtotime($request['request_date']) : false;

                    $exportRows[$key] = [
                        'accession' => $accession,
                        'patient'   => $patient,
                        'age'       => is_numeric($age) ? (int) $age : '',
                        'sex'       => $gender,
                        'services'  => implode('; ', $services),
                        'priority'  => $isStat ? 'STAT' : 'Routine',
                        'requested' => $createdTs ? date('Y-m-d', $createdTs) : '',
                        'tat'       => $tatMinutes . ' min',
                        'status'    => $meta['label'],
                    ];
                ?>
                    <article class="dx-card dx-card--<?= esc($status, 'attr') ?><?= $isStat ? ' is-stat' : '' ?>"
                             data-dx-ctx
                             data-key="<?= esc($key, 'attr') ?>"
                             data-id="<?= esc($id, 'attr') ?>"
                             data-status="<?= esc($status, 'attr') ?>"
                             data-accession="<?= esc($accession, 'attr') ?>"
                             data-patient="<?= esc($patient, 'attr') ?>"
                             data-priority="<?= $isStat ? 'stat' : 'routine' ?>"
                             data-search="<?= esc(strtolower(implode(' ', array_filter([$patient, $accession, implode(' ', $services), $isStat ? 'stat urgent' : '']))), 'attr') ?>">

                        <header class="dx-card-head">
                            <span class="dx-ref"><?= esc($accession) ?></span>

                            <div class="dx-card-flags">
                                <?php if ($isStat): ?>
                                    <span class="dx-tag-stat" title="Urgent">STAT</span>
                                <?php endif; ?>
                                <span class="dx-status dx-status--<?= $meta['tone'] ?>"><?= esc($meta['label']) ?></span>
                            </div>
                        </header>

                        <div class="dx-card-patient">
                            <span class="dx-avatar dx-avatar--<?= esc($avatarKind, 'attr') ?>" aria-hidden="true">
                                <?php if ($isMale): ?>
                                    <img src="<?= esc(base_url('assets/images/man-avatar.png'), 'attr') ?>"
                                         alt=""
                                         class="dx-avatar-img"
                                         loading="lazy"
                                         decoding="async">
                                <?php elseif ($isFemale): ?>
                                    <img src="<?= esc(base_url('assets/images/woman-avatar.png'), 'attr') ?>"
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

                        <div class="dx-card-services">
                            <?php if ($services): ?>
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
                                <dt>Requested</dt>
                                <dd>
                                    <?php if ($createdTs): ?>
                                        <time datetime="<?= esc(date('c', $createdTs), 'attr') ?>">
                                            <?= esc(date('M d, Y', $createdTs)) ?>
                                        </time>
                                    <?php else: ?>
                                        &mdash;
                                    <?php endif; ?>
                                </dd>
                            </div>
                            <div>
                                <dt>Priority</dt>
                                <dd class="<?= $isStat ? 'dx-priority-stat' : '' ?>">
                                    <?= $isStat ? 'STAT' : 'Routine' ?>
                                </dd>
                            </div>
                            <div>
                                <dt>TAT</dt>
                                <dd class="dx-amount">
                                    <i class="bi bi-clock" aria-hidden="true"></i>
                                    <?= (int) $tatMinutes ?> min
                                </dd>
                            </div>
                        </dl>

                        <footer class="dx-card-foot">
                            <a class="dx-btn dx-btn--sm dx-btn--primary"
                               href="<?= base_url('medtech/request/view/' . (int) $id) ?>">
                                <i class="bi bi-eye" aria-hidden="true"></i>
                                View
                            </a>

                            <?php if ($status === 'released'): ?>
                                <a class="dx-btn dx-btn--sm"
                                   href="<?= base_url('medtech/request/print/' . (int) $id) ?>"
                                   target="_blank">
                                    <i class="bi bi-download" aria-hidden="true"></i>
                                    Download
                                </a>
                            <?php elseif ($status !== 'completed'): ?>
                                <a class="dx-btn dx-btn--sm"
                                   href="<?= base_url('medtech/request/view/' . (int) $id) ?>">
                                    <i class="bi bi-pencil" aria-hidden="true"></i>
                                    Process
                                </a>
                            <?php endif; ?>

                            <div class="dropdown dx-card-menu">
                                <button type="button" class="dx-icon-btn"
                                        data-bs-toggle="dropdown"
                                        data-bs-popper-config='{"strategy":"fixed"}'
                                        aria-expanded="false"
                                        aria-label="More actions for <?= esc($accession, 'attr') ?>">
                                    <i class="bi bi-three-dots" aria-hidden="true"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end dx-menu">
                                    <li>
                                        <a class="dropdown-item"
                                           href="<?= base_url('medtech/request/view/' . (int) $id) ?>">
                                            <i class="bi bi-file-text" aria-hidden="true"></i> View details
                                        </a>
                                    </li>
                                    <?php if ($status === 'released'): ?>
                                        <li>
                                            <a class="dropdown-item"
                                               href="<?= base_url('medtech/request/print/' . (int) $id) ?>"
                                               target="_blank">
                                                <i class="bi bi-printer" aria-hidden="true"></i> Print result
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </footer>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- No requests at all -->
        <div class="dx-empty" id="dxEmpty"<?= empty($requests) ? '' : ' hidden' ?>>
            <div class="dx-empty-icon"><i class="bi bi-inbox" aria-hidden="true"></i></div>
            <h3>No laboratory requests</h3>
            <p>New requests from reception will appear here.</p>
        </div>

        <!-- Filters match nothing -->
        <div class="dx-empty" id="dxNoMatch" hidden>
            <div class="dx-empty-icon"><i class="bi bi-search" aria-hidden="true"></i></div>
            <h3>No matching requests</h3>
            <p>Try a different search term or clear the filters.</p>
            <button type="button" class="dx-btn" id="dxClearFilters">Clear filters</button>
        </div>

        <footer class="dx-foot" id="dxFoot"<?= empty($requests) ? ' hidden' : '' ?>>
            <span id="dxRange" aria-live="polite"></span>
            <nav class="dx-pager" id="dxPager" aria-label="Pagination"></nav>
        </footer>

    </section>

</div>

<div class="dx-toast" id="dxToast" role="status" aria-live="polite" hidden></div>


<style>
/* =========================================================
   LABORATORY REQUESTS
   Namespaced under .dx so the layout's generic rules can't
   leak in. Visual language matches the Diagnostic Requests page.
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
.dx-btn i { font-size: 0.9em; }

.dx-btn--primary,
.dx-btn--primary:hover { color: #ffffff; }
.dx-btn--primary { background: var(--dx-accent); border-color: var(--dx-accent); }
.dx-btn--primary:hover { background: var(--dx-accent-dark); border-color: var(--dx-accent-dark); }

.dx-btn--sm { height: 32px; padding: 0 0.75rem; font-size: 0.78rem; flex: 1; min-width: 0; }

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
}

.dx-icon-btn:hover,
.dx-icon-btn[aria-expanded="true"] { background: var(--dx-line-soft); color: var(--dx-ink); }

/* ---------- Inputs ---------- */

.dx-input {
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

.dx-input:focus {
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
    pointer-events: none;
}

.dx-search .dx-input:focus ~ .dx-kbd,
.dx-search .dx-input:not(:placeholder-shown) ~ .dx-kbd { display: none; }

/* ---------- Panel ---------- */

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

/* Same subtle pink wash the Admin diagnostic view uses for STAT
   requests, so urgent cards read differently at a glance. */
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
    font-family: var(--dx-mono);
    font-size: 0.76rem;
    font-weight: 600;
    letter-spacing: 0.01em;
    color: var(--dx-ink);
    white-space: nowrap;
}

.dx-card-flags {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    flex-shrink: 0;
}

/* Same STAT pill as the Admin diagnostic view. */
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

.dx-amount { font-variant-numeric: tabular-nums; font-weight: 600; }
.dx-amount i { color: var(--dx-faint); margin-right: 0.15rem; }

/* Priority row — plain for routine, red and bold for STAT. */
.dx-priority-stat {
    color: var(--dx-danger-dark);
    font-weight: 700;
}

/* ---- footer ---- */

.dx-card-foot {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    margin-top: auto;
}

.dx-card-menu { flex-shrink: 0; }

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
.dx-status--progress  { --tone-bg: #eff6ff; --tone-fg: #1d4ed8; --tone-dot: #3b82f6; }
.dx-status--completed { --tone-bg: #ecfdf5; --tone-fg: #047857; --tone-dot: #10b981; }
.dx-status--released  { --tone-bg: #f0fdfa; --tone-fg: #0f766e; --tone-dot: #14b8a6; }
.dx-status--draft     { --tone-bg: #f1f5f9; --tone-fg: #64748b; --tone-dot: #94a3b8; }
.dx-status--cancelled { --tone-bg: #f1f5f9; --tone-fg: #64748b; --tone-dot: #94a3b8; }

/* ---- row menu ---- */

.dx-menu {
    min-width: 11rem;
    padding: 0.3rem;
    font-size: 0.8125rem;
    border: 1px solid var(--dx-line);
    border-radius: 9px;
    box-shadow: 0 12px 28px -8px rgba(15, 23, 42, 0.2);
}

.dx-menu .dropdown-item {
    display: flex;
    align-items: center;
    gap: 0.55rem;
    padding: 0.45rem 0.6rem;
    color: var(--dx-text);
    border-radius: 6px;
}

.dx-menu .dropdown-item i { font-size: 0.85rem; color: var(--dx-faint); }
.dx-menu .dropdown-item:hover,
.dx-menu .dropdown-item:focus { background: var(--dx-line-soft); color: var(--dx-ink); }

/* ---------- Empty state and footer ---------- */

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

    var searchInput = byId('searchRequests');

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
        ['accession', 'Accession No.'],
        ['patient',   'Patient'],
        ['age',       'Age'],
        ['sex',       'Sex'],
        ['services',  'Lab Services'],
        ['priority',  'Priority'],
        ['requested', 'Request Date'],
        ['tat',       'TAT'],
        ['status',    'Status']
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
            showToast('There are no requests in the current view to export.', true);
            return;
        }

        var lines = [EXPORT_COLUMNS.map(function (c) { return csvCell(c[1]); }).join(',')];
        list.forEach(function (item) {
            lines.push(EXPORT_COLUMNS.map(function (c) { return csvCell(item[c[0]]); }).join(','));
        });

        var blob = new Blob([String.fromCharCode(0xFEFF) + lines.join('\r\n')], { type: 'text/csv;charset=utf-8' });
        var link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'laboratory-requests-' + new Date().toISOString().slice(0, 10) + '.csv';
        document.body.appendChild(link);
        link.click();
        link.remove();
        setTimeout(function () { URL.revokeObjectURL(link.href); }, 1000);

        showToast('Exported ' + list.length + ' request' + (list.length === 1 ? '' : 's'));
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