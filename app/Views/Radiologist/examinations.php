<?= $this->extend('layouts/RadiologistLayout') ?>

<?= $this->section('pageTitle') ?>Examinations<?= $this->endSection() ?>

<?= $this->section('radiologistContent') ?>

<?php
    $examinations = (isset($examinations) && is_array($examinations)) ? $examinations : [];
    $counts       = is_array($counts ?? null) ? $counts : [];

    // PNG avatar filenames inside public/assets/images/.
    // Change these two strings if your files are named differently.
    $maleAvatar   = 'man-avatar.png';
    $femaleAvatar = 'woman-avatar.png';

    $statusTabs = [
        'all'         => ['label' => 'All',         'count' => (int) ($counts['total']      ?? count($examinations))],
        'pending'     => ['label' => 'Pending',     'count' => (int) ($counts['pending']    ?? 0)],
        'in_progress' => ['label' => 'In reading',  'count' => (int) ($counts['processing'] ?? 0)],
        'completed'   => ['label' => 'Completed',   'count' => (int) ($counts['completed']  ?? 0)],
        'released'    => ['label' => 'Released',    'count' => (int) ($counts['released']   ?? 0)],
    ];

    $statusMeta = [
        'pending'     => ['label' => 'Pending',    'tone' => 'pending'],
        'in_progress' => ['label' => 'In reading', 'tone' => 'progress'],
        'processing'  => ['label' => 'In reading', 'tone' => 'progress'],
        'completed'   => ['label' => 'Completed',  'tone' => 'completed'],
        'released'    => ['label' => 'Released',   'tone' => 'released'],
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

<div class="ex">
    <!-- STATS -->
    <div class="ex-stats">
        <div class="ex-stat">
            <div class="ex-stat-icon ex-stat-icon--blue">
                <i class="bi bi-clipboard2-pulse" aria-hidden="true"></i>
            </div>
            <div class="ex-stat-body">
                <div class="ex-stat-value"><?= number_format($counts['total'] ?? 0) ?></div>
                <div class="ex-stat-label">Total studies</div>
                <div class="ex-stat-sub">All X-Ray examinations</div>
            </div>
        </div>

        <div class="ex-stat">
            <div class="ex-stat-icon ex-stat-icon--amber">
                <i class="bi bi-clock-history" aria-hidden="true"></i>
            </div>
            <div class="ex-stat-body">
                <div class="ex-stat-value"><?= number_format($counts['pending'] ?? 0) ?></div>
                <div class="ex-stat-label">Pending</div>
                <div class="ex-stat-sub">Awaiting read</div>
            </div>
        </div>

        <div class="ex-stat">
            <div class="ex-stat-icon ex-stat-icon--blue">
                <i class="bi bi-arrow-repeat" aria-hidden="true"></i>
            </div>
            <div class="ex-stat-body">
                <div class="ex-stat-value"><?= number_format($counts['processing'] ?? 0) ?></div>
                <div class="ex-stat-label">In reading</div>
                <div class="ex-stat-sub">Currently open</div>
            </div>
        </div>

        <div class="ex-stat">
            <div class="ex-stat-icon ex-stat-icon--green">
                <i class="bi bi-check2-circle" aria-hidden="true"></i>
            </div>
            <div class="ex-stat-body">
                <div class="ex-stat-value"><?= number_format($counts['completed'] ?? 0) ?></div>
                <div class="ex-stat-label">Completed</div>
                <div class="ex-stat-sub">Report signed</div>
            </div>
        </div>

        <div class="ex-stat">
            <div class="ex-stat-icon ex-stat-icon--teal">
                <i class="bi bi-send-check" aria-hidden="true"></i>
            </div>
            <div class="ex-stat-body">
                <div class="ex-stat-value"><?= number_format($counts['released'] ?? 0) ?></div>
                <div class="ex-stat-label">Released</div>
                <div class="ex-stat-sub">Sent to reception</div>
            </div>
        </div>
    </div>


    <!-- PANEL -->
    <section class="ex-panel" aria-label="X-Ray examinations">

        <div class="ex-tabs" role="group" aria-label="Filter by status">
            <?php foreach ($statusTabs as $key => $tab): ?>
                <button type="button"
                        class="ex-tab<?= $key === 'all' ? ' is-active' : '' ?>"
                        data-status="<?= esc($key, 'attr') ?>"
                        aria-pressed="<?= $key === 'all' ? 'true' : 'false' ?>">
                    <?= esc($tab['label']) ?>
                    <span class="ex-tab-count"><?= (int) $tab['count'] ?></span>
                </button>
            <?php endforeach; ?>
        </div>

        <div class="ex-filters">
            <div class="ex-search">
                <i class="bi bi-search" aria-hidden="true"></i>
                <label class="visually-hidden" for="searchExams">Search examinations</label>
                <input type="text"
                       class="ex-input"
                       id="searchExams"
                       placeholder="Search patient, accession, exam or physician"
                       autocomplete="off">
                <kbd class="ex-kbd" aria-hidden="true">/</kbd>
            </div>
        </div>

        <div class="ex-grid-wrap">
            <div class="ex-cards" id="examinationsTable">
                <?php if (count($examinations) > 0): ?>
                    <?php foreach ($examinations as $exam):
                        $examId    = (int) ($exam['id'] ?? 0);
                        $statusKey = (string) ($exam['status'] ?? 'pending');
                        $meta      = $statusMeta[$statusKey] ?? ['label' => ucfirst($statusKey), 'tone' => 'pending'];
                        $name      = (string) ($exam['patient_name'] ?? 'Unknown');
                        $examType  = trim((string) ($exam['exam_type'] ?? ''));
                        $doctor    = trim((string) ($exam['doctor_name'] ?? ''));
                        $radiologist = trim((string) ($exam['radiologist_name'] ?? ''));
                        $isStat    = strtolower((string) ($exam['priority'] ?? '')) === 'stat';
                        $dateTs    = !empty($exam['exam_date']) ? strtotime($exam['exam_date']) : false;
                        $createdTs = !empty($exam['created_at']) ? strtotime($exam['created_at']) : false;

                        $hasImage  = !empty($exam['image_path']);
                        $hasDraft  = trim((string) ($exam['findings'] ?? '')) !== ''
                                  || trim((string) ($exam['interpretation'] ?? '')) !== '';

                        // Avatar kind: male, female, or neutral fallback to initials
                        $genderRaw  = strtolower(trim((string) ($exam['gender'] ?? '')));
                        $isMale     = $genderRaw === 'male'   || $genderRaw === 'm';
                        $isFemale   = $genderRaw === 'female' || $genderRaw === 'f';
                        $avatarKind = $isMale ? 'male' : ($isFemale ? 'female' : 'neutral');

                        $modality = 'xray';
                        if (stripos($examType, 'ct') !== false)      { $modality = 'ct'; }
                        elseif (stripos($examType, 'mri') !== false) { $modality = 'mri'; }
                        elseif (stripos($examType, 'ultra') !== false
                             || stripos($examType, ' us') !== false) { $modality = 'us'; }

                        $searchIndex = strtolower(implode(' ', [
                            $name, $examType, $doctor, $radiologist,
                            'xr-' . date('y') . '-' . str_pad((string) $examId, 4, '0', STR_PAD_LEFT),
                        ]));
                    ?>
                        <article class="ex-card ex-card--<?= esc($statusKey, 'attr') ?><?= $isStat ? ' is-stat' : '' ?>"
                                 data-status="<?= esc($statusKey, 'attr') ?>"
                                 data-search="<?= esc($searchIndex, 'attr') ?>">

                            <header class="ex-card-head">
                                <span class="ex-ref">XR-<?= esc(date('y')) ?>-<?= esc(str_pad((string) $examId, 4, '0', STR_PAD_LEFT)) ?></span>

                                <div class="ex-card-flags">
                                    <?php if ($isStat): ?>
                                        <span class="ex-tag ex-tag--stat" title="Urgent">STAT</span>
                                    <?php endif; ?>
                                    <span class="ex-status ex-status--<?= esc($meta['tone'], 'attr') ?>">
                                        <?= esc($meta['label']) ?>
                                    </span>
                                </div>
                            </header>

                            <div class="ex-card-patient">
                                <span class="ex-avatar ex-avatar--<?= esc($avatarKind, 'attr') ?>" aria-hidden="true">
                                    <?php if ($isMale): ?>
                                        <img src="<?= esc(base_url('assets/images/' . $maleAvatar), 'attr') ?>"
                                             alt=""
                                             class="ex-avatar-img"
                                             loading="lazy"
                                             decoding="async">
                                    <?php elseif ($isFemale): ?>
                                        <img src="<?= esc(base_url('assets/images/' . $femaleAvatar), 'attr') ?>"
                                             alt=""
                                             class="ex-avatar-img"
                                             loading="lazy"
                                             decoding="async">
                                    <?php else: ?>
                                        <?= esc($initialsOf($name)) ?>
                                    <?php endif; ?>
                                </span>
                                <div class="ex-card-patient-body">
                                    <h3 class="ex-name"><?= esc($name) ?></h3>
                                    <p class="ex-sub">
                                        <?= esc($exam['age'] ?? '—') ?> yrs
                                        <span aria-hidden="true">·</span>
                                        <?= esc($exam['gender'] ?? '—') ?>
                                    </p>
                                </div>
                            </div>

                            <div class="ex-card-exam">
                                <span class="ex-mod ex-mod--<?= esc($modality, 'attr') ?>">
                                    <?= esc(strtoupper($modality === 'xray' ? 'XR' : $modality)) ?>
                                </span>
                                <span class="ex-exam-name" title="<?= esc($examType, 'attr') ?>">
                                    <?= esc($shorten($examType !== '' ? $examType : '—')) ?>
                                </span>
                            </div>

                            <dl class="ex-card-meta">
                                <div>
                                    <dt>Requested</dt>
                                    <dd>
                                        <?php if ($createdTs): ?>
                                            <?= esc(date('M j, Y', $createdTs)) ?>
                                            <span aria-hidden="true">·</span>
                                            <?= esc(date('g:i A', $createdTs)) ?>
                                        <?php elseif ($dateTs): ?>
                                            <?= esc(date('M j, Y', $dateTs)) ?>
                                        <?php else: ?>
                                            &mdash;
                                        <?php endif; ?>
                                    </dd>
                                </div>
                                <div>
                                    <dt>Referred by</dt>
                                    <dd><?= $doctor !== '' ? esc($doctor) : '<span class="ex-muted">&mdash;</span>' ?></dd>
                                </div>
                                <div>
                                    <dt>Radiologist</dt>
                                    <dd><?= $radiologist !== '' ? esc($radiologist) : '<span class="ex-muted">&mdash;</span>' ?></dd>
                                </div>
                            </dl>

                            <?php if ($hasImage || $hasDraft): ?>
                                <div class="ex-card-chips">
                                    <?php if ($hasImage): ?>
                                        <span class="ex-chip ex-chip--image">
                                            <i class="bi bi-image" aria-hidden="true"></i>
                                            Image attached
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($hasDraft && $statusKey !== 'released'): ?>
                                        <span class="ex-chip ex-chip--draft">
                                            <i class="bi bi-pencil" aria-hidden="true"></i>
                                            Draft saved
                                        </span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <footer class="ex-card-foot">
                                <a href="<?= base_url('radiologist/examination/view/' . $examId) ?>"
                                   class="ex-btn ex-btn--primary ex-btn--sm">
                                    <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>
                                    Open study
                                </a>

                                <div class="dropdown ex-card-menu">
                                    <button type="button" class="ex-icon-btn"
                                            data-bs-toggle="dropdown"
                                            data-bs-popper-config='{"strategy":"fixed"}'
                                            aria-expanded="false"
                                            aria-label="More actions for XR-<?= esc(str_pad((string) $examId, 4, '0', STR_PAD_LEFT), 'attr') ?>">
                                        <i class="bi bi-three-dots" aria-hidden="true"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end ex-menu">
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
                <?php else: ?>
                    <div class="ex-empty">
                        <div class="ex-empty-icon"><i class="bi bi-x-ray" aria-hidden="true"></i></div>
                        <h3>No examinations yet</h3>
                        <p>X-Ray requests created by the receptionist will appear in this list.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Shown only when a filter or search matches nothing -->
        <div class="ex-empty ex-empty--filter" id="exNoMatch" hidden>
            <div class="ex-empty-icon"><i class="bi bi-search" aria-hidden="true"></i></div>
            <h3>No matching examinations</h3>
            <p>Try a different search term, or select All to clear the filter.</p>
            <button type="button" class="ex-btn" id="exClearFilters">Clear filters</button>
        </div>

    </section>

</div>

<style>
/* =========================================================
   RADIOLOGIST · EXAMINATIONS
   Card grid matching the receptionist's diagnostic-requests
   page. Namespaced under .ex.
   ========================================================= */

.ex {
    --ex-ink:         #0f172a;
    --ex-text:        #334155;
    --ex-muted:       #64748b;
    --ex-faint:       #94a3b8;
    --ex-line:        #e2e8f0;
    --ex-line-soft:   #f1f5f9;
    --ex-surface:     #ffffff;
    --ex-subtle:      #f8fafc;
    --ex-accent:      #1d4ed8;
    --ex-accent-dark: #1e40af;
    --ex-accent-soft: #eaf2fe;
    --ex-green:       #047857;
    --ex-green-soft:  #ecfdf5;
    --ex-amber:       #b45309;
    --ex-amber-soft:  #fff4e5;
    --ex-teal:        #0f766e;
    --ex-teal-soft:   #f0fdfa;
    --ex-danger:      #b91c1c;
    --ex-danger-soft: #fef2f2;
    --ex-radius:      10px;
    --ex-radius-sm:   7px;
    --ex-ring:        0 0 0 3px rgba(29, 78, 216, 0.18);
    --ex-mono:        ui-monospace, SFMono-Regular, "SF Mono", Menlo, Consolas, monospace;

    color: var(--ex-text);
    font-size: 0.875rem;
}

.ex *:focus-visible { outline: 2px solid var(--ex-accent); outline-offset: 2px; }

.ex-muted { color: var(--ex-faint); }

/* ---------- Page header ---------- */

.ex-head {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
    margin-bottom: 1.25rem;
}

.ex-title {
    margin: 0 0 0.2rem;
    font-size: 1.25rem;
    font-weight: 650;
    letter-spacing: -0.015em;
    color: var(--ex-ink);
}

.ex-lede {
    margin: 0;
    font-size: 0.8125rem;
    color: var(--ex-muted);
}

.ex-lede strong { font-weight: 600; color: var(--ex-ink); }
.ex-lede span { margin: 0 0.2rem; color: var(--ex-faint); }

/* ---------- Stats ---------- */

.ex-stats {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 0.9rem;
    margin-bottom: 1.25rem;
}

.ex-stat {
    display: flex;
    align-items: center;
    gap: 0.85rem;
    min-width: 0;
    padding: 0.9rem 1rem;
    background: var(--ex-surface);
    border: 1px solid var(--ex-line);
    border-radius: var(--ex-radius);
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
}

.ex-stat-icon {
    display: grid;
    place-items: center;
    flex-shrink: 0;
    width: 36px;
    height: 36px;
    font-size: 1rem;
    border-radius: var(--ex-radius-sm);
}

.ex-stat-icon--blue  { background: var(--ex-accent-soft); color: var(--ex-accent); }
.ex-stat-icon--amber { background: var(--ex-amber-soft); color: var(--ex-amber); }
.ex-stat-icon--green { background: var(--ex-green-soft); color: var(--ex-green); }
.ex-stat-icon--teal  { background: var(--ex-teal-soft); color: var(--ex-teal); }

.ex-stat-body { min-width: 0; }

.ex-stat-value {
    font-size: 1.35rem;
    font-weight: 700;
    line-height: 1.1;
    letter-spacing: -0.02em;
    color: var(--ex-ink);
    font-variant-numeric: tabular-nums;
}

.ex-stat-label {
    margin-top: 0.15rem;
    font-size: 0.8125rem;
    font-weight: 600;
    color: var(--ex-text);
}

.ex-stat-sub {
    font-size: 0.72rem;
    color: var(--ex-muted);
}

/* ---------- Panel ---------- */

.ex-panel {
    background: var(--ex-surface);
    border: 1px solid var(--ex-line);
    border-radius: 12px;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    overflow: hidden;
}

/* ---------- Tabs ---------- */

.ex-tabs {
    display: flex;
    gap: 0.25rem;
    padding: 0 0.75rem;
    border-bottom: 1px solid var(--ex-line);
    overflow-x: auto;
    scrollbar-width: none;
}

.ex-tabs::-webkit-scrollbar { display: none; }

.ex-tab {
    position: relative;
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    padding: 0.85rem 0.6rem;
    font-size: 0.8125rem;
    font-weight: 600;
    color: var(--ex-muted);
    background: none;
    border: 0;
    white-space: nowrap;
    cursor: pointer;
    transition: color 0.15s ease;
}

.ex-tab::after {
    content: "";
    position: absolute;
    left: 0.4rem;
    right: 0.4rem;
    bottom: -1px;
    height: 2px;
    border-radius: 2px 2px 0 0;
    background: transparent;
}

.ex-tab:hover { color: var(--ex-ink); }
.ex-tab.is-active { color: var(--ex-ink); }
.ex-tab.is-active::after { background: var(--ex-accent); }

.ex-tab-count {
    min-width: 1.4rem;
    padding: 0.05rem 0.4rem;
    font-size: 0.7rem;
    font-weight: 600;
    font-variant-numeric: tabular-nums;
    text-align: center;
    color: var(--ex-muted);
    background: var(--ex-line-soft);
    border-radius: 999px;
}

.ex-tab.is-active .ex-tab-count { color: var(--ex-accent); background: var(--ex-accent-soft); }

/* ---------- Filters ---------- */

.ex-filters {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
    padding: 0.75rem 1rem;
}

.ex-search { position: relative; flex: 1 1 280px; max-width: 420px; }

.ex-search > i {
    position: absolute;
    left: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    font-size: 0.8rem;
    color: var(--ex-faint);
    pointer-events: none;
}

.ex-input {
    width: 100%;
    height: 36px;
    padding: 0 0.75rem 0 2.1rem;
    font-size: 0.8125rem;
    color: var(--ex-ink);
    background-color: var(--ex-surface);
    border: 1px solid var(--ex-line);
    border-radius: var(--ex-radius-sm);
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

.ex-input::placeholder { color: var(--ex-faint); }

.ex-input:focus {
    outline: none;
    border-color: var(--ex-accent);
    box-shadow: var(--ex-ring);
}

.ex-kbd {
    position: absolute;
    right: 0.6rem;
    top: 50%;
    transform: translateY(-50%);
    padding: 0.05rem 0.35rem;
    font-family: var(--ex-mono);
    font-size: 0.7rem;
    line-height: 1.3;
    color: var(--ex-muted);
    background: var(--ex-subtle);
    border: 1px solid var(--ex-line);
    border-radius: 4px;
    pointer-events: none;
}

.ex-search .ex-input:focus ~ .ex-kbd,
.ex-search .ex-input:not(:placeholder-shown) ~ .ex-kbd { display: none; }

/* ---------- Grid ---------- */

.ex-grid-wrap { border-top: 1px solid var(--ex-line); }

.ex-cards {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 1rem;
    padding: 1rem;
}

/* ---------- Card ---------- */

.ex-card {
    position: relative;
    display: flex;
    flex-direction: column;
    min-width: 0;
    padding: 1rem 1.05rem 1rem;
    background: var(--ex-surface);
    border: 1px solid var(--ex-line);
    border-left: 3px solid var(--ex-line);
    border-radius: var(--ex-radius);
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

.ex-card:hover {
    border-color: #cbd5e1;
    border-left-color: #cbd5e1;
    box-shadow: 0 6px 18px -10px rgba(15, 23, 42, 0.22);
}

.ex-card--pending     { border-left-color: #f59e0b; }
.ex-card--in_progress { border-left-color: #3b82f6; }
.ex-card--processing  { border-left-color: #3b82f6; }
.ex-card--completed   { border-left-color: #10b981; }
.ex-card--released    { border-left-color: #14b8a6; }

.ex-card.is-stat {
    background-image: linear-gradient(180deg, rgba(254, 242, 242, 0.6), transparent 45%);
}

.ex-card.is-hidden { display: none; }

.ex-card-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    margin-bottom: 0.6rem;
}

.ex-ref {
    padding: 0.15rem 0.5rem;
    font-family: var(--ex-mono);
    font-size: 0.76rem;
    font-weight: 600;
    color: var(--ex-ink);
    background: var(--ex-line-soft);
    border-radius: 5px;
    white-space: nowrap;
}

.ex-card-flags {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    flex-shrink: 0;
}

.ex-tag {
    display: inline-flex;
    align-items: center;
    padding: 0 0.35rem;
    font-size: 0.62rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    line-height: 1.6;
    border-radius: 4px;
}

.ex-tag--stat {
    color: var(--ex-danger);
    background: var(--ex-danger-soft);
    border: 1px solid #fecaca;
}

/* Patient block */

.ex-card-patient {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    margin-bottom: 0.55rem;
}

.ex-avatar {
    display: grid;
    place-items: center;
    flex-shrink: 0;
    width: 36px;
    height: 36px;
    font-size: 0.72rem;
    font-weight: 700;
    color: var(--ex-accent);
    background: var(--ex-accent-soft);
    border-radius: 50%;
    overflow: hidden;
}

/* Male / female / neutral tints. The PNG fills the circle; the tint
   shows through transparent PNG edges as a subtle backdrop. */
.ex-avatar--male    { color: #1d4ed8; background: #eaf2fe; }
.ex-avatar--female  { color: #b32e50; background: #fce9ee; }
.ex-avatar--neutral { color: var(--ex-accent); background: var(--ex-accent-soft); }

.ex-avatar-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.ex-card-patient-body { min-width: 0; }

.ex-name {
    margin: 0;
    font-size: 0.95rem;
    font-weight: 650;
    letter-spacing: -0.01em;
    line-height: 1.3;
    color: var(--ex-ink);
    overflow-wrap: anywhere;
}

.ex-sub {
    margin: 0.1rem 0 0;
    font-size: 0.76rem;
    color: var(--ex-muted);
}

.ex-sub span { margin: 0 0.15rem; color: var(--ex-faint); }

/* Exam block */

.ex-card-exam {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.85rem;
    min-width: 0;
}

.ex-mod {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    min-width: 30px;
    padding: 0.15rem 0.45rem;
    font-size: 0.66rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    border-radius: 5px;
    color: #4338ca;
    background: #eef2ff;
    border: 1px solid #e0e7ff;
}

.ex-mod--ct  { color: var(--ex-teal);  background: var(--ex-teal-soft);  border-color: #99f6e4; }
.ex-mod--mri { color: #6d28d9;         background: #f3e8ff;               border-color: #e9d5ff; }
.ex-mod--us  { color: var(--ex-green); background: var(--ex-green-soft); border-color: #a7f3d0; }

.ex-exam-name {
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-size: 0.8125rem;
    color: var(--ex-text);
}

/* Meta */

.ex-card-meta {
    display: grid;
    grid-template-columns: 1fr;
    gap: 0.4rem;
    margin: 0 0 0.85rem;
    padding: 0.7rem 0;
    border-top: 1px solid var(--ex-line-soft);
    border-bottom: 1px solid var(--ex-line-soft);
    font-size: 0.78rem;
}

.ex-card-meta > div {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: 0.75rem;
    min-width: 0;
}

.ex-card-meta dt {
    flex-shrink: 0;
    color: var(--ex-muted);
    font-weight: 500;
}

.ex-card-meta dd {
    margin: 0;
    text-align: right;
    color: var(--ex-ink);
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* Chips */

.ex-card-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 0.3rem;
    margin-bottom: 0.85rem;
}

.ex-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    padding: 0.15rem 0.5rem;
    font-size: 0.72rem;
    font-weight: 500;
    color: var(--ex-text);
    background: var(--ex-surface);
    border: 1px solid var(--ex-line);
    border-radius: 5px;
    white-space: nowrap;
}

.ex-chip i { font-size: 0.72em; }

.ex-chip--image { color: #0e7490; background: #ecfeff; border-color: #cffafe; }
.ex-chip--draft { color: var(--ex-amber); background: var(--ex-amber-soft); border-color: #fde68a; }

/* Status pill */

.ex-status {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.2rem 0.55rem;
    font-size: 0.72rem;
    font-weight: 600;
    white-space: nowrap;
    color: var(--ex-tone-fg);
    background: var(--ex-tone-bg);
    border-radius: 999px;
}

.ex-status::before {
    content: "";
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: var(--ex-tone-dot);
}

.ex-status--pending   { --ex-tone-bg: var(--ex-amber-soft);  --ex-tone-fg: var(--ex-amber);  --ex-tone-dot: #f59e0b; }
.ex-status--progress  { --ex-tone-bg: var(--ex-accent-soft); --ex-tone-fg: var(--ex-accent); --ex-tone-dot: #3b82f6; }
.ex-status--completed { --ex-tone-bg: var(--ex-green-soft);  --ex-tone-fg: var(--ex-green);  --ex-tone-dot: #10b981; }
.ex-status--released  { --ex-tone-bg: var(--ex-teal-soft);   --ex-tone-fg: var(--ex-teal);   --ex-tone-dot: #14b8a6; }

/* Footer */

.ex-card-foot {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    margin-top: auto;
}

.ex-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.4rem;
    height: 36px;
    padding: 0 0.9rem;
    font-size: 0.8125rem;
    font-weight: 600;
    line-height: 1;
    color: var(--ex-text);
    background: var(--ex-surface);
    border: 1px solid var(--ex-line);
    border-radius: var(--ex-radius-sm);
    text-decoration: none;
    white-space: nowrap;
    cursor: pointer;
    transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
}

.ex-btn:hover { background: var(--ex-subtle); border-color: #cbd5e1; color: var(--ex-ink); }
.ex-btn i { font-size: 0.9em; }

.ex-btn--sm { height: 32px; padding: 0 0.75rem; font-size: 0.78rem; flex: 1; min-width: 0; }

.ex-btn--primary,
.ex-btn--primary:hover { color: #ffffff; }
.ex-btn--primary { background: var(--ex-accent); border-color: var(--ex-accent); }
.ex-btn--primary:hover { background: var(--ex-accent-dark); border-color: var(--ex-accent-dark); }

.ex-card-menu { flex-shrink: 0; }

.ex-icon-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    font-size: 0.85rem;
    color: var(--ex-muted);
    background: transparent;
    border: 1px solid transparent;
    border-radius: var(--ex-radius-sm);
    cursor: pointer;
    transition: background-color 0.15s ease, color 0.15s ease, border-color 0.15s ease;
}

.ex-icon-btn:hover,
.ex-icon-btn[aria-expanded="true"] { background: var(--ex-line-soft); color: var(--ex-ink); border-color: var(--ex-line); }

.ex-menu {
    min-width: 11rem;
    padding: 0.3rem;
    font-size: 0.8125rem;
    border: 1px solid var(--ex-line);
    border-radius: 9px;
    box-shadow: 0 12px 28px -8px rgba(15, 23, 42, 0.2);
}

.ex-menu .dropdown-item {
    display: flex;
    align-items: center;
    gap: 0.55rem;
    padding: 0.45rem 0.6rem;
    color: var(--ex-text);
    border-radius: 6px;
}

.ex-menu .dropdown-item i { font-size: 0.85rem; color: var(--ex-faint); }
.ex-menu .dropdown-item:hover,
.ex-menu .dropdown-item:focus { background: var(--ex-line-soft); color: var(--ex-ink); }
.ex-menu .dropdown-divider { margin: 0.3rem 0; border-color: var(--ex-line-soft); }

/* ---------- Empty states ---------- */

.ex-empty {
    grid-column: 1 / -1;
    padding: 3.5rem 1rem;
    text-align: center;
}
.ex-empty--filter { border-top: 1px solid var(--ex-line); }

.ex-empty-icon {
    display: grid;
    place-items: center;
    width: 44px;
    height: 44px;
    margin: 0 auto 0.85rem;
    font-size: 1.2rem;
    color: var(--ex-faint);
    background: var(--ex-line-soft);
    border-radius: 10px;
}

.ex-empty h3 { margin: 0 0 0.25rem; font-size: 0.95rem; font-weight: 600; color: var(--ex-ink); }
.ex-empty p { max-width: 26rem; margin: 0 auto 1rem; font-size: 0.8125rem; color: var(--ex-muted); }

/* ---------- Responsive ---------- */

@media (max-width: 1200px) {
    .ex-cards { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .ex-stats { grid-template-columns: repeat(3, minmax(0, 1fr)); }
}

@media (max-width: 900px) {
    .ex-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}

@media (max-width: 768px) {
    .ex-filters .ex-search { flex-basis: 100%; max-width: none; }
    .ex-kbd { display: none; }

    .ex-cards { grid-template-columns: minmax(0, 1fr); padding: 0.75rem; }

    .ex-card-meta > div { align-items: flex-start; flex-direction: column; gap: 0.1rem; }
    .ex-card-meta dd { text-align: left; white-space: normal; }
}

@media (max-width: 576px) {
    .ex-stats { grid-template-columns: minmax(0, 1fr); gap: 0.6rem; }
}

@media (prefers-reduced-motion: reduce) {
    .ex *, .ex *::before, .ex *::after { transition: none !important; }
}
</style>

<script>
(function () {
    'use strict';

    var search   = document.getElementById('searchExams');
    var grid     = document.getElementById('examinationsTable');
    var noMatch  = document.getElementById('exNoMatch');
    var clearBtn = document.getElementById('exClearFilters');
    var tabs     = Array.prototype.slice.call(document.querySelectorAll('.ex-tab'));

    var activeStatus = 'all';

    function cards() {
        return grid ? Array.prototype.slice.call(grid.querySelectorAll('.ex-card')) : [];
    }

    function applyFilters() {
        var term = search ? search.value.toLowerCase().trim() : '';
        var visible = 0;

        cards().forEach(function (card) {
            var text = card.dataset.search || card.textContent.toLowerCase();
            var status = (card.dataset.status || '').toLowerCase();

            var matchesSearch = term === '' || text.indexOf(term) !== -1;

            var matchesStatus =
                activeStatus === 'all' ||
                status === activeStatus ||
                (activeStatus === 'in_progress' && status === 'processing') ||
                (activeStatus === 'processing'   && status === 'in_progress');

            var show = matchesSearch && matchesStatus;
            card.classList.toggle('is-hidden', !show);
            if (show) { visible++; }
        });

        if (noMatch) {
            noMatch.hidden = !(cards().length > 0 && visible === 0);
        }
    }

    if (search) {
        search.addEventListener('input', applyFilters);
        search.addEventListener('search', applyFilters);
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