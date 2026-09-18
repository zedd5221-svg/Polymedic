<?= $this->extend('layouts/RadiologistLayout') ?>

<?= $this->section('pageTitle') ?>Examinations<?= $this->endSection() ?>

<?= $this->section('radiologistContent') ?>

<?php
/* ------------------------------------------------------------------
   View-local helpers only. No controller or query logic is changed.
   ------------------------------------------------------------------ */
$examinations = (isset($examinations) && is_array($examinations)) ? $examinations : [];
$counts       = is_array($counts ?? null) ? $counts : [];

// PNG avatar filenames inside public/assets/images/.
$maleAvatar   = 'man-avatar.png';
$femaleAvatar = 'woman-avatar.png';

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

$shorten = static function ($text, $max = 46) {
    $text = trim((string) $text);
    if ($text === '') { return ''; }
    if (mb_strlen($text) <= $max) { return $text; }
    return mb_substr($text, 0, $max - 1) . '…';
};
?>

<div class="dx">

    <!-- PAGE HEADER -->
    <header class="dx-head">
        <div>
            <h2 class="dx-title">X-Ray examinations</h2>
        </div>

        <div class="dx-head-actions">
            <button type="button" class="dx-btn" onclick="window.location.reload()">
                <i class="bi bi-arrow-clockwise" aria-hidden="true"></i>
                Refresh
            </button>
        </div>
    </header>


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

                    $genderRaw  = strtolower(trim((string) ($exam['gender'] ?? '')));
                    $isMale     = $genderRaw === 'male'   || $genderRaw === 'm';
                    $isFemale   = $genderRaw === 'female' || $genderRaw === 'f';
                    $avatarKind = $isMale ? 'male' : ($isFemale ? 'female' : 'neutral');

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

                        <header class="dx-card-head">
                            <span class="dx-ref"><?= esc($reference) ?></span>

                            <div class="dx-card-flags">
                                <?php if ($isStat): ?>
                                    <span class="dx-tag-stat" title="Urgent">STAT</span>
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
                                    <?= esc($initialsOf($name)) ?>
                                <?php endif; ?>
                            </span>

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

                        <div class="dx-card-tags">
                            <span class="dx-kind dx-kind--xray">
                                <i class="bi bi-radioactive" aria-hidden="true"></i>
                                <?= esc($kindLabel) ?>
                            </span>
                        </div>

                        <div class="dx-card-services">
                            <span class="dx-chip" title="<?= esc($examType, 'attr') ?>">
                                <?= esc($shorten($examType !== '' ? $examType : '—')) ?>
                            </span>
                        </div>

                        <dl class="dx-card-meta">
                            <div>
                                <dt>Requested</dt>
                                <dd>
                                    <?php if ($createdTs): ?>
                                        <time datetime="<?= esc(date('c', $createdTs), 'attr') ?>">
                                            <?= esc(date('M j, Y', $createdTs)) ?>
                                            <span aria-hidden="true">·</span>
                                            <?= esc(date('g:i A', $createdTs)) ?>
                                        </time>
                                    <?php elseif ($dateTs): ?>
                                        <time datetime="<?= esc(date('c', $dateTs), 'attr') ?>">
                                            <?= esc(date('M j, Y', $dateTs)) ?>
                                        </time>
                                    <?php else: ?>
                                        &mdash;
                                    <?php endif; ?>
                                </dd>
                            </div>
                            <div>
                                <dt>Referred by</dt>
                                <dd><?= $doctor !== '' ? esc($doctor) : '<span class="dx-muted">&mdash;</span>' ?></dd>
                            </div>
                            <div>
                                <dt>Radiologist</dt>
                                <dd><?= $radiologist !== '' ? esc($radiologist) : '<span class="dx-muted">&mdash;</span>' ?></dd>
                            </div>
                        </dl>

                        <?php if ($hasImage || $hasDraft): ?>
                            <div class="dx-card-chips">
                                <?php if ($hasImage): ?>
                                    <span class="dx-chip dx-chip--image">
                                        <i class="bi bi-image" aria-hidden="true"></i>
                                        Image attached
                                    </span>
                                <?php endif; ?>
                                <?php if ($hasDraft && $statusKey !== 'released'): ?>
                                    <span class="dx-chip dx-chip--draft">
                                        <i class="bi bi-pencil" aria-hidden="true"></i>
                                        Draft saved
                                    </span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <footer class="dx-card-foot">
                            <a href="<?= base_url('radiologist/examination/view/' . $examId) ?>"
                               class="dx-btn dx-btn--sm dx-btn--primary">
                                <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>
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
    --dx-ink:         #0f172a;
    --dx-text:        #334155;
    --dx-muted:       #64748b;
    --dx-faint:       #94a3b8;
    --dx-line:        #e2e8f0;
    --dx-line-soft:   #f1f5f9;
    --dx-surface:     #ffffff;
    --dx-subtle:      #f8fafc;
    --dx-accent:      #1d4ed8;
    --dx-accent-dark: #1e40af;
    --dx-accent-soft: #eaf2fe;
    --dx-danger:      #dc2626;
    --dx-danger-dark: #b91c1c;
    --dx-radius:      10px;
    --dx-radius-sm:   7px;
    --dx-ring:        0 0 0 3px rgba(29, 78, 216, 0.18);
    --dx-mono:        ui-monospace, SFMono-Regular, "SF Mono", Menlo, Consolas, monospace;

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
    transition: border-color 0.15s ease, box-shadow 0.15s ease, transform 0.15s ease;
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

/* ---- patient block with avatar ---- */

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

/* ---- kind + source chips ---- */

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

.dx-kind--xray { color: #4338ca; background: #eef2ff; border-color: #e0e7ff; }

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

.dx-card-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 0.3rem;
    margin-bottom: 0.85rem;
}

.dx-chip--image { color: #0e7490; background: #ecfeff; border-color: #cffafe; }
.dx-chip--draft { color: #b45309; background: #fff4e5; border-color: #fde68a; }

.dx-chip i { font-size: 0.72em; }

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