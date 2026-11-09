<?= $this->extend('Layouts/MedTechLayout') ?>

<?= $this->section('pageTitle') ?>Laboratory Requests<?= $this->endSection() ?>

<?= $this->section('medtechContent') ?>

<?php
/* ------------------------------------------------------------------
   Laboratory Requests — same UI as the Diagnostic Requests page.

   Row shape comes from the existing MedTech controller: id, patient_name,
   age, gender, lab_services, request_date, status — plus 'priority'
   when the controller supplies it (values: 'routine' | 'stat').

   TAT is computed from the request's own timestamps (see the loop).
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

/* 45 → "45m", 200 → "3h 20m", 1700 → "1d 4h" */
$duration = static function (int $m): string {
    if ($m < 60)   { return $m . 'm'; }
    if ($m < 1440) { return intdiv($m, 60) . 'h' . ($m % 60 ? ' ' . ($m % 60) . 'm' : ''); }
    $d = intdiv($m, 1440);
    $h = intdiv($m % 1440, 60);
    return $d . 'd' . ($h ? ' ' . $h . 'h' : '');
};

/* Lab workflow, in order. A request's stage is how many steps are done. */
$stageNames = ['Received', 'Processing', 'Completed', 'Released'];
$stageOf    = ['pending' => 1, 'in_progress' => 2, 'draft' => 2, 'completed' => 3, 'released' => 4, 'cancelled' => 0];

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


    <!-- PAGE HEADER -->
    <header class="dx-head">

        <div class="dx-head-actions">
            <button type="button" class="dx-btn" id="dxExport">
                <i class="bi bi-download" aria-hidden="true"></i>
                Export CSV
            </button>
        </div>
    </header>


    <!-- PATIENT HANDOFF -->
    <?= view('partials/transfer_panel', ['transfer' => $transfer ?? []]) ?>

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
                    $accession = 'LAB-' . date('y') . '-' . str_pad($id, 4, '0', STR_PAD_LEFT);
                    $patient   = (string) ($request['patient_name'] ?? 'Unknown patient');
                    $status    = strtolower((string) ($request['status'] ?? 'pending'));
                    $meta      = $statusMeta[$status] ?? $statusMeta['pending'];
                    $tags      = \App\Models\DiagnosticRequestModel::statusTags($status);

                    /* Priority — same rule as the Admin diagnostic view.
                       Anything other than "stat" is treated as routine. */
                    $isStat = strtolower((string) ($request['priority'] ?? '')) === 'stat';

                    $age    = $request['age'] ?? '';
                    $gender = trim((string) ($request['gender'] ?? ''));
                    $gender = strtoupper($gender) === 'N/A' ? '' : $gender;

                    $ageText = is_numeric($age) ? ((int) $age) . ' yrs' : '';
                    $demo    = implode(' · ', array_filter([$ageText, $gender]));


                    $services = isset($request['lab_services'])
                        ? array_values(array_filter(array_map('trim', explode(',', (string) $request['lab_services'])), 'strlen'))
                        : [];

                    /*
                     * Turnaround from real timestamps (this used to be rand()):
                     *   released  → request to release
                     *   completed → request to completion (last update)
                     *   open      → how long it has been waiting so far
                     */
                    $startTs = !empty($request['created_at']) ? strtotime($request['created_at']) : false;
                    $endTs   = false;
                    $tatVerb = 'Waiting';
                    if ($status === 'released' && !empty($request['released_at'])) {
                        $endTs   = strtotime($request['released_at']);
                        $tatVerb = 'Released in';
                    } elseif ($status === 'completed' && !empty($request['updated_at'])) {
                        $endTs   = strtotime($request['updated_at']);
                        $tatVerb = 'Completed in';
                    } elseif (in_array($status, ['pending', 'in_progress', 'draft'], true)) {
                        $endTs = time();
                    }
                    $tatMinutes = ($startTs && $endTs && $endTs >= $startTs) ? (int) floor(($endTs - $startTs) / 60) : null;
                    $tatText    = $tatMinutes === null ? '' : $duration($tatMinutes);
                    $tatOpen    = $tatVerb === 'Waiting';

                    $stage = $stageOf[$status] ?? 1;

                    $createdTs = !empty($request['request_date']) ? strtotime($request['request_date']) : false;

                    $exportRows[$key] = [
                        'accession' => $accession,
                        'patient'   => $patient,
                        'age'       => is_numeric($age) ? (int) $age : '',
                        'sex'       => $gender,
                        'services'  => implode('; ', $services),
                        'priority'  => $isStat ? 'STAT' : 'Routine',
                        'requested' => $createdTs ? date('Y-m-d', $createdTs) : '',
                        'tat'       => $tatMinutes !== null ? $tatMinutes . ' min' : '',
                        'status'    => $meta['label'],
                    ];
                ?>
                    <article class="dx-card dx-card--<?= esc($status, 'attr') ?><?= $isStat ? ' is-stat' : '' ?>"
                             data-dx-ctx
                             data-key="<?= esc($key, 'attr') ?>"
                             data-id="<?= esc($id, 'attr') ?>"
                             data-status="<?= esc($status, 'attr') ?>"
                             data-statuses="<?= esc(implode(' ', $tags), 'attr') ?>"
                             data-accession="<?= esc($accession, 'attr') ?>"
                             data-patient="<?= esc($patient, 'attr') ?>"
                             data-priority="<?= $isStat ? 'stat' : 'routine' ?>"
                             data-search="<?= esc(strtolower(implode(' ', array_filter([$patient, $accession, implode(' ', $services), $isStat ? 'stat urgent' : '']))), 'attr') ?>">

                        <header class="dx-card-head">
                            <span class="dx-card-id">
                                <span class="dx-ref"><?= esc($accession) ?></span>
                                <?php if ($isStat): ?>
                                    <span class="dx-tag-stat" title="Urgent">STAT</span>
                                <?php endif; ?>
                            </span>

                            <span class="dx-card-flags">
                                <?php foreach ($tags as $tag): $tm = $statusMeta[$tag] ?? $meta; ?>
                                    <span class="dx-status dx-status--<?= $tm['tone'] ?>"><?= esc($tm['label']) ?></span>
                                <?php endforeach; ?>
                            </span>
                        </header>

                        <div class="dx-card-patient">
                            <h3 class="dx-card-name"><?= esc($patient) ?></h3>
                            <p class="dx-card-demo">
                                <?php if ($demo !== ''): ?>
                                    <?= esc($demo) ?>
                                <?php else: ?>
                                    <span class="dx-muted">No age or sex on file</span>
                                <?php endif; ?>
                            </p>
                        </div>

                        <div class="dx-tests">
                            <div class="dx-tests-head">
                                <span>Ordered tests</span>
                                <span class="dx-tests-count"><?= count($services) ?></span>
                            </div>
                            <?php if ($services): ?>
                                <ul class="dx-tests-list<?= count($services) > 3 ? ' is-scroll' : '' ?>"
                                    title="<?= esc(implode(', ', $services), 'attr') ?>"
                                    <?= count($services) > 3 ? 'tabindex="0" aria-label="' . count($services) . ' ordered tests, scroll for more"' : '' ?>>
                                    <?php foreach ($services as $service): ?>
                                        <li><?= esc($service) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p class="dx-tests-empty">No tests listed</p>
                            <?php endif; ?>
                        </div>

                        <div class="dx-progress">
                            <ol class="dx-track" aria-label="<?= esc('Workflow: ' . ($stage > 0 ? $stageNames[$stage - 1] : 'Cancelled'), 'attr') ?>">
                                <?php foreach ($stageNames as $i => $stageName): ?>
                                    <li class="dx-track-step<?= $i < $stage ? ' is-done' : '' ?><?= $i === $stage - 1 ? ' is-current' : '' ?>"
                                        title="<?= esc($stageName, 'attr') ?>"></li>
                                <?php endforeach; ?>
                            </ol>
                            <p class="dx-progress-meta">
                                <?php if ($createdTs): ?>
                                    <time datetime="<?= esc(date('c', $createdTs), 'attr') ?>">Requested <?= esc(date('M j', $createdTs)) ?></time>
                                <?php else: ?>
                                    <span class="dx-muted">No request date</span>
                                <?php endif; ?>
                                <?php if ($tatText !== ''): ?>
                                    <span class="dx-sep" aria-hidden="true">·</span>
                                    <span class="dx-elapsed<?= $tatOpen ? ' is-open' : '' ?>"><?= esc($tatVerb) ?> <strong><?= esc($tatText) ?></strong></span>
                                <?php endif; ?>
                            </p>
                        </div>

                        <footer class="dx-card-foot">
                            <?php if ($status === 'released'): ?>
                                <a class="dx-btn dx-btn--sm dx-btn--primary"
                                   href="<?= base_url('medtech/request/print/' . (int) $id) ?>"
                                   target="_blank">
                                    <i class="bi bi-printer" aria-hidden="true"></i>
                                    Print result
                                </a>
                                <a class="dx-btn dx-btn--sm"
                                   href="<?= base_url('medtech/request/view/' . (int) $id) ?>">
                                    View
                                </a>
                            <?php elseif ($status === 'completed'): ?>
                                <a class="dx-btn dx-btn--sm dx-btn--primary"
                                   href="<?= base_url('medtech/request/view/' . (int) $id) ?>">
                                    View results
                                </a>
                            <?php else: ?>
                                <a class="dx-btn dx-btn--sm dx-btn--primary"
                                   href="<?= base_url('medtech/request/view/' . (int) $id) ?>">
                                    <?= $status === 'pending' ? 'Start processing' : 'Continue' ?>
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
    --dx-ink:         var(--pm-text, #10202E);
    --dx-text:        var(--pm-text-soft, #3B4C5E);
    --dx-muted:       var(--pm-text-muted, #5D6E80);
    --dx-faint:       var(--pm-text-muted);
    --dx-line:        var(--pm-line, #DDE4EC);
    --dx-line-strong: var(--pm-line-strong, #C3CFDB);
    --dx-line-soft:   var(--pm-line);
    --dx-surface:     var(--pm-surface);
    --dx-subtle:      var(--pm-subtle);
    --dx-accent:      var(--pm-accent, #175563);
    --dx-accent-dark: var(--pm-accent-hover, #10404B);
    --dx-accent-soft: var(--pm-accent-soft, #EBF2F4);
    --dx-danger:      #B42318;
    --dx-danger-dark: #912018;
    --dx-radius:      8px;
    --dx-radius-sm:   6px;
    --dx-ring:        0 0 0 3px rgba(23, 85, 99, 0.18);
    --dx-mono:        var(--pm-mono, ui-monospace, monospace);

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
.dx-alert--success { background: var(--pm-dk-bg-green, #f0fdf4); border-color: var(--pm-dk-edge-green, #bbf7d0); color: var(--pm-dk-fg-green, #166534); }
.dx-alert--error   { background: var(--pm-dk-bg-red, #fef2f2); border-color: var(--pm-dk-edge-red, #fecaca); color: var(--pm-dk-fg-red, #991b1b); }

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
    white-space: nowrap;
    text-decoration: none;
    cursor: pointer;
    transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
}

.dx-btn:hover { background: var(--dx-subtle); border-color: var(--pm-line); color: var(--dx-ink); }
.dx-btn i { font-size: 0.9em; }

.dx-btn--primary,
.dx-btn--primary:hover { color: #ffffff; }
.dx-btn--primary { background: var(--dx-accent); border-color: var(--dx-accent); }
.dx-btn--primary:hover { background: var(--dx-accent-dark); border-color: var(--dx-accent-dark); }

.dx-btn--sm { height: 32px; padding: 0 0.75rem; font-size: 0.78rem; min-width: 0; }

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
    border-radius: var(--dx-radius);
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
    border-radius: 4px;
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
    background: var(--dx-subtle);
}

.dx-cards {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 0.85rem;
    padding: 1rem;
}

/* A requisition slip: name first, the ordered tests, then where it is */
.dx-card {
    position: relative;
    display: flex;
    flex-direction: column;
    min-width: 0;
    padding: 1rem 1.05rem 0;
    background: var(--dx-surface);
    border: 1px solid var(--dx-line);
    border-radius: 10px;
    transition: border-color 0.18s ease, box-shadow 0.18s ease;
}

.dx-card:hover {
    border-color: var(--dx-line-strong);
    box-shadow: 0 8px 20px -14px rgba(20, 45, 54, 0.35);
}

.dx-card.is-stat { border-color: var(--pm-dk-edge-red, #F4C7C2); }
.dx-card.is-stat:hover { border-color: var(--pm-dk-edge-red, #EAA29A); }

.dx-card.is-hidden { display: none; }

/* ---- header ---- */

.dx-card-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    margin-bottom: 0.7rem;
}

.dx-card-id {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    min-width: 0;
}

.dx-ref {
    font-family: var(--dx-mono);
    font-size: 0.72rem;
    font-weight: 500;
    color: var(--dx-muted);
    white-space: nowrap;
}

.dx-card-flags {
    display: inline-flex;
    align-items: center;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: 0.3rem;
}

.dx-tag-stat {
    padding: 0 0.3rem;
    font-size: 0.6rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    line-height: 1.65;
    color: #FFFFFF;
    background: var(--dx-danger);
    border-radius: 3px;
}

/* ---- patient: typographic, no avatar ---- */

.dx-card-patient { min-width: 0; }

.dx-card-name {
    margin: 0;
    font-size: 1rem;
    font-weight: 600;
    letter-spacing: -0.01em;
    line-height: 1.3;
    color: var(--dx-ink);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.dx-card-demo {
    margin: 0.1rem 0 0;
    font-size: 0.76rem;
    color: var(--dx-muted);
}

/* ---- ordered tests: the work itself ---- */

.dx-tests {
    margin-top: 0.85rem;
    padding: 0.65rem 0.75rem 0.7rem;
    background: var(--dx-subtle);
    border: 1px solid var(--dx-line-soft);
    border-radius: 8px;
}

.dx-tests-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 0.4rem;
    font-size: 0.64rem;
    font-weight: 600;
    letter-spacing: 0.07em;
    text-transform: uppercase;
    color: var(--dx-faint);
}

.dx-tests-count {
    min-width: 1.3rem;
    padding: 0 0.3rem;
    font-size: 0.68rem;
    letter-spacing: 0;
    text-align: center;
    color: var(--dx-text);
    background: var(--dx-surface);
    border: 1px solid var(--dx-line);
    border-radius: 4px;
    font-variant-numeric: tabular-nums;
}

.dx-tests-list {
    display: grid;
    gap: 0.25rem;
    margin: 0;
    padding: 0;
    list-style: none;
}

.dx-tests-list li {
    position: relative;
    padding-left: 0.9rem;
    font-size: 0.8rem;
    line-height: 1.4;
    color: var(--dx-ink);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Lab-blue bullet: same colour as "Lab" everywhere else */
.dx-tests-list li::before {
    content: "";
    position: absolute;
    left: 0.1rem;
    top: 0.55em;
    width: 5px;
    height: 5px;
    border-radius: 50%;
    background: var(--dx-accent);
}

/* Four or more tests: three rows show, the rest scroll inside the box.
   Height = 3 rows (0.8rem x 1.4 line-height) + 2 gaps + a peek of the 4th. */
.dx-tests-list.is-scroll {
    max-height: calc(3 * 1.12rem + 2 * 0.25rem + 0.55rem);
    overflow-y: auto;
    overscroll-behavior: contain;
    padding-right: 0.4rem;
    margin-right: -0.4rem;
    scrollbar-width: thin;
    scrollbar-color: var(--pm-line-strong) transparent;
    -webkit-mask-image: linear-gradient(180deg, #000 calc(100% - 1.1rem), transparent);
            mask-image: linear-gradient(180deg, #000 calc(100% - 1.1rem), transparent);
}
.dx-tests-list.is-scroll.is-end { -webkit-mask-image: none; mask-image: none; }
.dx-tests-list.is-scroll::-webkit-scrollbar { width: 5px; }
.dx-tests-list.is-scroll::-webkit-scrollbar-thumb { background: var(--pm-line-strong); border-radius: 3px; }
.dx-tests-list.is-scroll:focus-visible { outline: 2px solid var(--dx-accent); outline-offset: 2px; border-radius: 4px; }

.dx-tests-empty { margin: 0; font-size: 0.78rem; color: var(--dx-faint); }

/* ---- workflow track: Received → Processing → Completed → Released ---- */

.dx-progress { margin: 0.85rem 0 0.9rem; }

.dx-track {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 3px;
    margin: 0 0 0.45rem;
    padding: 0;
    list-style: none;
}

.dx-track-step {
    height: 4px;
    background: var(--dx-line-soft);
    border-radius: 2px;
}

.dx-track-step.is-done { background: var(--dx-accent); }

/* Released: the whole track turns the "released" teal */
.dx-card--released .dx-track-step.is-done { background: #009A85; }

.dx-progress-meta {
    display: flex;
    flex-wrap: wrap;
    align-items: baseline;
    gap: 0.15rem 0.4rem;
    margin: 0;
    font-size: 0.74rem;
    color: var(--dx-muted);
    font-variant-numeric: tabular-nums;
}

.dx-sep { color: var(--dx-line-strong); }

.dx-elapsed strong { font-weight: 600; color: var(--dx-text); }

/* Still waiting: the elapsed time is the number to watch */
.dx-elapsed.is-open strong { color: var(--dx-ink); }
.dx-card.is-stat .dx-elapsed.is-open strong { color: var(--dx-danger); }

/* ---- footer ---- */

/* Pinned to the bottom (cards in a row share a height); bleeds to the card edges */
.dx-card-foot {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    margin: auto -1.05rem 0;
    padding: 0.7rem 1.05rem;
    border-top: 1px solid var(--dx-line-soft);
}

.dx-card-foot .dx-btn--primary { flex: 1; }

.dx-card-menu { flex-shrink: 0; margin-left: auto; }

/* ---- status ---- */

.dx-status {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.12rem 0.45rem;
    font-size: 0.7rem;
    font-weight: 600;
    white-space: nowrap;
    color: var(--tone-fg);
    background: var(--tone-bg);
    border-radius: 4px;
}

.dx-status::before {
    content: "";
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: var(--tone-dot);
}

.dx-status--pending   { --tone-bg: #FEF6E7; --tone-fg: #93370D; --tone-dot: #DC6803; }
.dx-status--progress  { --tone-bg: #EBF2F4; --tone-fg: #10404B; --tone-dot: #175563; }
.dx-status--completed { --tone-bg: #ECFDF3; --tone-fg: #067647; --tone-dot: #17B26A; }
.dx-status--released  { --tone-bg: #E6F6F4; --tone-fg: #0E6E64; --tone-dot: #009A85; }
.dx-status--draft     { --tone-bg: var(--pm-subtle); --tone-fg: #475467; --tone-dot: #98A2B3; }
.dx-status--cancelled { --tone-bg: var(--pm-subtle); --tone-fg: #475467; --tone-dot: #98A2B3; }

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
    border-radius: 8px;
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

.dx-page:hover:not(:disabled) { background: var(--dx-subtle); border-color: var(--pm-line); }
.dx-page:disabled { opacity: 0.45; cursor: not-allowed; }
.dx-page[aria-current="page"] { color: var(--dx-accent); background: var(--dx-accent-soft); border-color: var(--pm-dk-edge-blue, #bcd6f3); }
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
    background: var(--pm-ink, #142D36);
    border-radius: 9px;
    box-shadow: 0 12px 28px -8px rgba(15, 23, 42, 0.45);
}

.dx-toast i { color: #34d399; }
.dx-toast.is-error i { color: #f87171; }

/* ---------- Responsive ---------- */

@media (max-width: 768px) {
    .dx-head { flex-direction: column; align-items: stretch; }
    .dx-head-actions .dx-btn { flex: 1; }

    .dx-filters .dx-search { flex-basis: 100%; max-width: none; }
    .dx-kbd { display: none; }

    .dx-cards { grid-template-columns: minmax(0, 1fr); padding: 0.75rem; }


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
/* Ordered-tests lists that scroll: drop the bottom fade once the end is reached */
if (!window.__dxTestsScroll) {
    window.__dxTestsScroll = true;
    document.addEventListener('scroll', function (e) {
        var el = e.target;
        if (!el.classList || !el.classList.contains('dx-tests-list')) return;
        el.classList.toggle('is-end', el.scrollTop + el.clientHeight >= el.scrollHeight - 2);
    }, true);
}
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
            var tags = ' ' + (card.dataset.statuses || card.dataset.status || '') + ' ';
            return (state.status === 'all' || tags.indexOf(' ' + state.status + ' ') !== -1) &&
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