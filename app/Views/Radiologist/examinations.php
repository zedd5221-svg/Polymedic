<?= $this->extend('Layouts/RadiologistLayout') ?>

<?= $this->section('pageTitle') ?>Examinations<?= $this->endSection() ?>

<?= $this->section('radiologistContent') ?>

<?php
/* ------------------------------------------------------------------
   View-local helpers only. No controller or query logic is changed.
   ------------------------------------------------------------------ */
$examinations = (isset($examinations) && is_array($examinations)) ? $examinations : [];
$counts       = is_array($counts ?? null) ? $counts : [];

$statusMeta = [
    'pending'     => ['label' => 'Pending',    'tone' => 'pending'],
    'in_progress' => ['label' => 'In reading', 'tone' => 'progress'],
    'processing'  => ['label' => 'In reading', 'tone' => 'progress'],
    'completed'   => ['label' => 'Completed',  'tone' => 'completed'],
    'released'    => ['label' => 'Released',   'tone' => 'released'],
];

$filterTabs = [
    'all'         => ['label' => 'All',         'count' => (int) ($counts['total']      ?? count($examinations))],
    'pending'     => ['label' => 'Pending',     'count' => (int) ($counts['pending']    ?? 0)],
    'in_progress' => ['label' => 'In reading',  'count' => (int) ($counts['processing'] ?? 0)],
    'completed'   => ['label' => 'Completed',   'count' => (int) ($counts['completed']  ?? 0)],
    'released'    => ['label' => 'Released',    'count' => (int) ($counts['released']   ?? 0)],
];

$initialsOf = static function ($name) {
    $parts = preg_split('/\s+/', trim((string) $name));
    $first = mb_substr($parts[0] ?? '', 0, 1);
    $last  = count($parts) > 1 ? mb_substr(end($parts), 0, 1) : '';
    return mb_strtoupper($first . $last);
};

?>

<div class="dx">

    <!-- PAGE HEADER -->
    <header class="dx-head">
       
    </header>


    <!-- PATIENT HANDOFF -->
    <?= view('partials/transfer_panel', ['transfer' => $transfer ?? []]) ?>

    <!-- WORKLIST -->
    <section class="dx-panel" aria-label="X-Ray examinations">

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
                <label class="visually-hidden" for="searchExams">Search examinations</label>
                <input type="search"
                       class="dx-input"
                       id="searchExams"
                       placeholder="Search patient, accession, exam or physician"
                       autocomplete="off">
                <kbd class="dx-kbd" aria-hidden="true">/</kbd>
            </div>
        </div>

        <div class="dx-grid-wrap" id="dxTableWrap"<?= empty($examinations) ? ' hidden' : '' ?>>
            <div class="dx-cards" id="dxRows">
                <?php foreach ($examinations as $exam):

                    $examId      = (int) ($exam['id'] ?? 0);
                    $statusKey   = (string) ($exam['status'] ?? 'pending');
                    $meta        = $statusMeta[$statusKey] ?? ['label' => ucfirst($statusKey), 'tone' => 'pending'];
                    $reference   = 'XR-' . date('y') . '-' . str_pad((string) $examId, 4, '0', STR_PAD_LEFT);
                    $name        = (string) ($exam['patient_name'] ?? 'Unknown');
                    $examType    = trim((string) ($exam['exam_type'] ?? ''));
                    $doctor      = trim((string) ($exam['doctor_name'] ?? ''));
                    $radiologist = trim((string) ($exam['radiologist_name'] ?? ''));
                    $isStat      = strtolower((string) ($exam['priority'] ?? '')) === 'stat';
                    $dateTs      = !empty($exam['exam_date']) ? strtotime($exam['exam_date']) : false;
                    $createdTs   = !empty($exam['created_at']) ? strtotime($exam['created_at']) : false;

                    $hasImage = !empty($exam['image_path']);
                    $hasDraft = trim((string) ($exam['findings'] ?? '')) !== ''
                             || trim((string) ($exam['interpretation'] ?? '')) !== '';


                    $modality = 'xray';
                    if (stripos($examType, 'ct') !== false)      { $modality = 'ct'; }
                    elseif (stripos($examType, 'mri') !== false) { $modality = 'mri'; }
                    elseif (stripos($examType, 'ultra') !== false
                         || stripos($examType, ' us') !== false) { $modality = 'us'; }

                    $kindLabel = $modality === 'xray' ? 'X-Ray'
                              : ($modality === 'ct' ? 'CT Scan'
                              : ($modality === 'mri' ? 'MRI' : 'Ultrasound'));

                    $ageText = '';
                    if (!empty($exam['age']) && is_numeric($exam['age'])) {
                        $ageText = ((int) $exam['age']) . ' y';
                    }

                    $sexText = '';
                    if (!empty($exam['gender']) && strtoupper(trim((string) $exam['gender'])) !== 'N/A') {
                        $sexText = ucfirst(strtolower(trim((string) $exam['gender'])));
                    }

                    $demo = implode(' · ', array_filter([$ageText, $sexText]));

                    $searchIndex = strtolower(implode(' ', [
                        $name, $examType, $doctor, $radiologist, $reference,
                    ]));
                ?>
                    <article class="dx-card dx-card--<?= esc($statusKey, 'attr') ?><?= $isStat ? ' is-stat' : '' ?>"
                             data-status="<?= esc($statusKey, 'attr') ?>"
                             data-search="<?= esc($searchIndex, 'attr') ?>">

                        <?php
                        /* First stored image (multi-image JSON first, legacy single path second). */
                        $images = [];
                        $decoded = json_decode((string) ($exam['image_paths'] ?? ''), true);
                        if (is_array($decoded)) {
                            foreach ($decoded as $p) {
                                if (is_string($p) && trim($p) !== '') { $images[] = trim($p); }
                            }
                        }
                        if (!$images && !empty($exam['image_path'])) { $images[] = (string) $exam['image_path']; }
                        $images   = array_values(array_unique($images));
                        $thumb    = $images[0] ?? null;
                        $imgCount = count($images);

                        $sinceTs = $createdTs ?: $dateTs;
                        $since   = '';
                        if ($sinceTs) {
                            $ago = time() - $sinceTs;
                            if ($ago < 60)         { $since = 'Just now'; }
                            elseif ($ago < 3600)   { $since = floor($ago / 60) . 'm ago'; }
                            elseif ($ago < 86400)  { $since = floor($ago / 3600) . 'h ago'; }
                            elseif ($ago < 604800) { $since = floor($ago / 86400) . 'd ago'; }
                            else                   { $since = date('M j', $sinceTs); }
                        }
                        ?>
                        <header class="dx-card-head">
                            <span class="dx-card-id">
                                <span class="dx-ref"><?= esc($reference) ?></span>
                                <?php if ($since !== ''): ?>
                                    <span class="dx-since" title="Requested <?= esc(date('M j, Y · g:i A', $sinceTs), 'attr') ?>"><?= esc($since) ?></span>
                                <?php endif; ?>
                            </span>

                            <span class="dx-card-flags">
                                <?php if ($isStat): ?>
                                    <span class="dx-tag-stat" title="Urgent">STAT</span>
                                <?php endif; ?>
                                <span class="dx-status dx-status--<?= esc($meta['tone'], 'attr') ?>"><?= esc($meta['label']) ?></span>
                            </span>
                        </header>

                        <div class="dx-card-body">
                            <div class="dx-card-patient">
                                <span class="dx-avatar" aria-hidden="true"><?= esc($initialsOf($name)) ?></span>
                                <div class="dx-card-patient-body">
                                    <h3 class="dx-card-name"><?= esc($name) ?></h3>
                                    <p class="dx-card-demo">
                                        <?php if ($demo !== ''): ?>
                                            <?= esc($demo) ?>
                                        <?php else: ?>
                                            <span class="dx-muted">No age or sex on file</span>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>

                            <div class="dx-study">
                                <span class="dx-film<?= $thumb ? ' has-image' : '' ?>" aria-hidden="true">
                                    <?php if ($thumb): ?>
                                        <img src="<?= esc(base_url($thumb), 'attr') ?>" alt="" loading="lazy" decoding="async">
                                        <?php if ($imgCount > 1): ?>
                                            <span class="dx-film-count"><?= (int) $imgCount ?></span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <img src="<?= esc(base_url('assets/images/bones.png'), 'attr') ?>" alt="" class="dx-film-art" loading="lazy" decoding="async">
                                    <?php endif; ?>
                                </span>

                                <div class="dx-study-body">
                                    <span class="dx-kind dx-kind--<?= esc($modality, 'attr') ?>"><?= esc($kindLabel) ?></span>
                                    <p class="dx-exam-name" title="<?= esc($examType, 'attr') ?>">
                                        <?= $examType !== '' ? esc($examType) : '<span class="dx-muted">No exam type recorded</span>' ?>
                                    </p>
                                    <?php if (!$thumb || ($hasDraft && $statusKey !== 'released')): ?>
                                        <span class="dx-study-note">
                                            <?php if ($hasDraft && $statusKey !== 'released'): ?>
                                                <i class="bi bi-pencil" aria-hidden="true"></i>Draft saved
                                            <?php else: ?>
                                                No image yet
                                            <?php endif; ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <dl class="dx-card-meta">
                                <div>
                                    <dt>Referred by</dt>
                                    <dd title="<?= esc($doctor, 'attr') ?>"><?= $doctor !== '' ? esc($doctor) : '<span class="dx-muted">&mdash;</span>' ?></dd>
                                </div>
                                <div>
                                    <dt>Radiologist</dt>
                                    <dd title="<?= esc($radiologist, 'attr') ?>"><?= $radiologist !== '' ? esc($radiologist) : '<span class="dx-muted">Unassigned</span>' ?></dd>
                                </div>
                            </dl>
                        </div>

                        <footer class="dx-card-foot">
                            <a href="<?= base_url('radiologist/examination/view/' . $examId) ?>"
                               class="dx-btn dx-btn--sm dx-btn--primary">
                                Open study
                            </a>

                            <div class="dropdown dx-card-menu">
                                <button type="button" class="dx-icon-btn"
                                        data-bs-toggle="dropdown"
                                        data-bs-popper-config='{"strategy":"fixed"}'
                                        aria-expanded="false"
                                        aria-label="More actions for <?= esc($reference, 'attr') ?>">
                                    <i class="bi bi-three-dots" aria-hidden="true"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end dx-menu">
                                    <li>
                                        <a class="dropdown-item" href="<?= base_url('radiologist/examination/view/' . $examId) ?>">
                                            <i class="bi bi-pencil-square" aria-hidden="true"></i> Read / interpret
                                        </a>
                                    </li>
                                    <?php if ($statusKey === 'released'): ?>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <a class="dropdown-item" href="<?= base_url('radiologist/examination/print/' . $examId) ?>" target="_blank">
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

        <!-- No examinations at all -->
        <div class="dx-empty" id="dxEmpty"<?= empty($examinations) ? '' : ' hidden' ?>>
            <div class="dx-empty-icon"><i class="bi bi-x-ray" aria-hidden="true"></i></div>
            <h3>No examinations yet</h3>
            <p>X-Ray requests created by the receptionist will appear in this list.</p>
        </div>

        <!-- Filters match nothing -->
        <div class="dx-empty" id="dxNoMatch" hidden>
            <div class="dx-empty-icon"><i class="bi bi-search" aria-hidden="true"></i></div>
            <h3>No matching examinations</h3>
            <p>Try a different search term or clear the filters.</p>
            <button type="button" class="dx-btn" id="dxClearFilters">Clear filters</button>
        </div>

        <footer class="dx-foot" id="dxFoot"<?= empty($examinations) ? ' hidden' : '' ?>>
            <span id="dxRange" aria-live="polite"></span>
            <nav class="dx-pager" id="dxPager" aria-label="Pagination"></nav>
        </footer>

    </section>

</div>


<style>
/* =========================================================
   RADIOLOGIST · EXAMINATIONS
   Card grid matching the receptionist's diagnostic requests
   page. Namespaced under .dx.
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

.dx-lede strong { font-weight: 600; color: var(--dx-ink); }
.dx-lede span { margin: 0 0.25rem; color: var(--dx-faint); }

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

/* ---------- Worklist panel ---------- */

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

/* As many columns as fit; cards never get narrower than ~290px */
.dx-cards {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(290px, 1fr));
    gap: 0.85rem;
    padding: 1rem;
}

.dx-card {
    position: relative;
    display: flex;
    flex-direction: column;
    min-width: 0;
    overflow: hidden;
    background: var(--dx-surface);
    border: 1px solid var(--dx-line);
    border-radius: 10px;
    transition: border-color 0.18s ease, box-shadow 0.18s ease;
}

.dx-card:hover {
    border-color: var(--dx-line-strong);
    box-shadow: 0 8px 20px -14px rgba(20, 45, 54, 0.35);
}

.dx-card.is-hidden { display: none; }

/* ---- header band: identity of the request, not of the patient ---- */

.dx-card-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    padding: 0.55rem 0.9rem;
    background: var(--pm-surface-top);
    border-bottom: 1px solid var(--dx-line-soft);
}

/* STAT: the band carries the urgency, the rest of the card stays calm */
.dx-card.is-stat .dx-card-head {
    background: var(--pm-tint-red);
    border-bottom-color: rgba(240, 68, 56, 0.2);
}

.dx-card-id {
    display: inline-flex;
    align-items: baseline;
    gap: 0.45rem;
    min-width: 0;
}

.dx-ref {
    font-family: var(--dx-mono);
    font-size: 0.72rem;
    font-weight: 500;
    color: var(--dx-text);
    white-space: nowrap;
}

.dx-since {
    font-size: 0.72rem;
    color: var(--dx-muted);
    white-space: nowrap;
    font-variant-numeric: tabular-nums;
    cursor: help;
}

.dx-since::before { content: "\00B7"; margin-right: 0.45rem; color: var(--dx-faint); }

.dx-card-flags {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    flex-shrink: 0;
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

/* ---- body ---- */

.dx-card-body {
    display: flex;
    flex-direction: column;
    gap: 0.8rem;
    padding: 0.9rem 0.9rem 0.85rem;
    flex: 1;
}

.dx-card-patient {
    display: flex;
    align-items: center;
    gap: 0.7rem;
    min-width: 0;
}

.dx-avatar {
    display: grid;
    place-items: center;
    flex-shrink: 0;
    width: 38px;
    height: 38px;
    font-size: 0.74rem;
    font-weight: 600;
    letter-spacing: 0.03em;
    color: var(--dx-ink);
    background: var(--pm-avatar);
    border-radius: 50%;
}

.dx-card-patient-body { min-width: 0; }

.dx-card-name {
    margin: 0;
    font-size: 0.95rem;
    font-weight: 600;
    letter-spacing: -0.01em;
    line-height: 1.3;
    color: var(--dx-ink);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.dx-card-demo {
    margin: 0.05rem 0 0;
    font-size: 0.75rem;
    color: var(--dx-muted);
}

/* ---- study tile: the film and what was ordered ---- */

.dx-study {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    min-width: 0;
    padding: 0.55rem;
    background: var(--dx-subtle);
    border: 1px solid var(--dx-line-soft);
    border-radius: 8px;
}

.dx-film {
    position: relative;
    flex-shrink: 0;
    width: 56px;
    height: 56px;
    overflow: hidden;
    border-radius: 6px;
    background: #0E8A7E;
    box-shadow: inset 0 0 0 1px rgba(16, 32, 46, 0.08);
}

.dx-film img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

/* Attached image: shown on a film-dark ground */
.dx-film.has-image { background: #0B141C; }
.dx-film.has-image img { filter: grayscale(1) contrast(1.05); }

/* No image yet: the X-Ray artwork, tinted like the notifications tile */
.dx-film-art { mix-blend-mode: screen; opacity: 0.95; }

.dx-film-count {
    position: absolute;
    right: 3px;
    bottom: 3px;
    min-width: 16px;
    padding: 0 0.25rem;
    font-size: 0.6rem;
    font-weight: 600;
    line-height: 16px;
    text-align: center;
    color: #FFFFFF;
    background: rgba(11, 20, 28, 0.72);
    border-radius: 3px;
}

.dx-study-body {
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
    min-width: 0;
}

.dx-kind {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.64rem;
    font-weight: 600;
    letter-spacing: 0.07em;
    text-transform: uppercase;
    color: var(--dx-muted);
}

.dx-kind::before {
    content: "";
    width: 6px;
    height: 6px;
    border-radius: 2px;
    background: var(--kind, #0E8A7E);
}

.dx-kind--xray { --kind: #0E8A7E; }
.dx-kind--ct   { --kind: #175563; }
.dx-kind--mri  { --kind: #7D5A80; }
.dx-kind--us   { --kind: #D29A1E; }

.dx-exam-name {
    margin: 0;
    font-size: 0.84rem;
    font-weight: 500;
    line-height: 1.35;
    color: var(--dx-ink);
    overflow-wrap: anywhere;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.dx-study-note {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    font-size: 0.7rem;
    color: var(--dx-muted);
}

.dx-study-note i { font-size: 0.72rem; color: var(--pm-dk-fg-amber, #B54708); }

/* ---- meta: two quiet columns ---- */

.dx-card-meta {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.75rem;
    margin: 0;
    padding: 0 0.15rem;
}

.dx-card-meta > div { min-width: 0; }

.dx-card-meta dt {
    font-size: 0.64rem;
    font-weight: 600;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: var(--dx-faint);
}

.dx-card-meta dd {
    margin: 0.15rem 0 0;
    font-size: 0.8rem;
    color: var(--dx-ink);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* ---- footer ---- */

.dx-card-foot {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.65rem 0.9rem;
    border-top: 1px solid var(--dx-line-soft);
}

.dx-card-foot .dx-btn--sm { flex: 1; }

.dx-card-menu { flex-shrink: 0; }

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
.dx-status--released  { --tone-bg: #E6F6F4; --tone-fg: #0E6E64; --tone-dot: #0E8A7E; }
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
.dx-menu .dropdown-divider { margin: 0.3rem 0; border-color: var(--dx-line-soft); }

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

/* ---------- Responsive ---------- */

@media (max-width: 768px) {
    .dx-head { flex-direction: column; align-items: stretch; }
    .dx-head-actions .dx-btn { flex: 1; }

    .dx-filters .dx-search { flex-basis: 100%; max-width: none; }
    .dx-kbd { display: none; }

    .dx-cards { grid-template-columns: minmax(0, 1fr); padding: 0.75rem; gap: 0.6rem; }


    .dx-foot { justify-content: center; }
}

@media (prefers-reduced-motion: reduce) {
    .dx * { transition: none !important; }
}
</style>


<script>
(function () {
    'use strict';

    var search   = document.getElementById('searchExams');
    var grid     = document.getElementById('dxRows');
    var noMatch  = document.getElementById('dxNoMatch');
    var clearBtn = document.getElementById('dxClearFilters');
    var tabs     = Array.prototype.slice.call(document.querySelectorAll('.dx-tab'));

    var activeStatus = 'all';

    var cards = grid ? Array.prototype.slice.call(grid.querySelectorAll('.dx-card')) : [];
    var matches = cards.slice();
    var PER_PAGE = 9;
    var page = 1;

    var pager      = document.getElementById('dxPager');
    var rangeLabel = document.getElementById('dxRange');
    var foot       = document.getElementById('dxFoot');
    var wrap       = document.getElementById('dxTableWrap');
    var emptyBox   = document.getElementById('dxEmpty');

    function byId(id) { return document.getElementById(id); }

    function applyFilters(keepPage) {
        var term = search ? search.value.toLowerCase().trim() : '';

        matches = cards.filter(function (card) {
            var text   = card.dataset.search || card.textContent.toLowerCase();
            var status = (card.dataset.status || '').toLowerCase();

            var matchesSearch = term === '' || text.indexOf(term) !== -1;

            var matchesStatus =
                activeStatus === 'all' ||
                status === activeStatus ||
                (activeStatus === 'in_progress' && status === 'processing') ||
                (activeStatus === 'processing'   && status === 'in_progress');

            return matchesSearch && matchesStatus;
        });

        if (!keepPage) { page = 1; }
        render();
    }

    function render() {
        var pages = Math.max(1, Math.ceil(matches.length / PER_PAGE));
        page = Math.min(Math.max(1, page), pages);

        var start = (page - 1) * PER_PAGE;
        var end   = Math.min(start + PER_PAGE, matches.length);

        cards.forEach(function (card) { card.classList.add('is-hidden'); });
        matches.slice(start, end).forEach(function (card) { card.classList.remove('is-hidden'); });

        var none = cards.length === 0;

        if (emptyBox) { emptyBox.hidden = !none; }
        if (noMatch)  { noMatch.hidden  = none || matches.length > 0; }
        if (wrap)     { wrap.hidden     = none || matches.length === 0; }
        if (foot)     { foot.hidden     = none || matches.length === 0; }

        if (rangeLabel) {
            rangeLabel.innerHTML = matches.length
                ? 'Showing <strong>' + (start + 1) + '–' + end + '</strong> of <strong>' + matches.length + '</strong>'
                : '';
        }

        renderPager(pages);
    }

    function renderPager(pages) {
        if (!pager) { return; }

        if (pages <= 1) { pager.innerHTML = ''; return; }

        var list = [];
        var i;

        if (pages <= 7) {
            for (i = 1; i <= pages; i++) { list.push(i); }
        } else {
            var from = Math.max(2, page - 1);
            var to   = Math.min(pages - 1, page + 1);
            if (page <= 3)         { from = 2; to = 4; }
            if (page >= pages - 2) { from = pages - 3; to = pages - 1; }

            list.push(1);
            if (from > 2) { list.push('gap'); }
            for (i = from; i <= to; i++) { list.push(i); }
            if (to < pages - 1) { list.push('gap'); }
            list.push(pages);
        }

        var html = '<button type="button" class="dx-page" data-page="' + (page - 1) + '"' +
                   (page === 1 ? ' disabled' : '') + ' aria-label="Previous page">' +
                   '<i class="bi bi-chevron-left" aria-hidden="true"></i></button>';

        list.forEach(function (item) {
            if (item === 'gap') {
                html += '<span class="dx-page-gap" aria-hidden="true">…</span>';
                return;
            }
            html += '<button type="button" class="dx-page" data-page="' + item + '"' +
                    (item === page ? ' aria-current="page"' : '') +
                    ' aria-label="Page ' + item + '">' + item + '</button>';
        });

        html += '<button type="button" class="dx-page" data-page="' + (page + 1) + '"' +
                (page === pages ? ' disabled' : '') + ' aria-label="Next page">' +
                '<i class="bi bi-chevron-right" aria-hidden="true"></i></button>';

        pager.innerHTML = html;
    }

    if (pager) {
        pager.addEventListener('click', function (e) {
            var btn = e.target.closest('.dx-page');
            if (!btn || btn.disabled) { return; }
            page = parseInt(btn.dataset.page, 10) || 1;
            render();
            if (wrap) { wrap.scrollIntoView({ block: 'nearest' }); }
        });
    }

    if (search) {
        search.addEventListener('input', function () { applyFilters(); });
        search.addEventListener('search', function () { applyFilters(); });
    }

    document.addEventListener('keydown', function (e) {
        if (e.key !== '/' || e.ctrlKey || e.metaKey || e.altKey) { return; }
        var t = e.target;
        if (t.isContentEditable || /^(INPUT|TEXTAREA|SELECT)$/.test(t.tagName)) { return; }
        e.preventDefault();
        if (search) { search.focus(); }
    });

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            activeStatus = tab.dataset.status || 'all';

            tabs.forEach(function (t) {
                var on = t === tab;
                t.classList.toggle('is-active', on);
                t.setAttribute('aria-pressed', on ? 'true' : 'false');
            });

            applyFilters();
        });
    });

    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            if (search) { search.value = ''; }
            activeStatus = 'all';

            tabs.forEach(function (t) {
                var on = t.dataset.status === 'all';
                t.classList.toggle('is-active', on);
                t.setAttribute('aria-pressed', on ? 'true' : 'false');
            });

            applyFilters();
        });
    }

    applyFilters();
})();
</script>

<?= $this->endSection() ?>