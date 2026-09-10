<?= $this->extend('layouts/RadiologistLayout') ?>

<?= $this->section('pageTitle') ?>Radiologist Dashboard<?= $this->endSection() ?>

<?= $this->section('radiologistContent') ?>

<?php
    // The controller passes flat variables to this view:
    //   $pending, $processing, $completed, $released, $total
    //   $recent_examinations, $pending_examinations
    //   $weekly_data (from XrayExaminationModel::getWeeklyVolumeData())
    //
    // Anything else the previous version of this view referenced
    // ($counts[...], $criticalFindings, $breakdownData, $weeklyLabels,
    // $weeklyDatasets, $pendingExaminations) was never supplied. This
    // version reads only what is actually provided.

    $pending    = (int) ($pending ?? 0);
    $processing = (int) ($processing ?? 0);
    $completed  = (int) ($completed ?? 0);
    $released   = (int) ($released ?? 0);
    $total      = (int) ($total ?? 0);

    $recentExaminations  = (isset($recent_examinations)  && is_array($recent_examinations))  ? $recent_examinations  : [];
    $pendingExaminations = (isset($pending_examinations) && is_array($pending_examinations)) ? $pending_examinations : [];

    // Weekly volume: the model returns ['labels' => [...], 'datasets' => [...]].
    // If the controller hasn't been wired to pass it yet, the chart renders
    // an empty placeholder rather than inventing numbers.
    $weeklyData     = (isset($weekly_data) && is_array($weekly_data)) ? $weekly_data : [];
    $weeklyLabels   = (isset($weeklyData['labels'])   && is_array($weeklyData['labels']))   ? $weeklyData['labels']   : [];
    $weeklyDatasets = (isset($weeklyData['datasets']) && is_array($weeklyData['datasets'])) ? $weeklyData['datasets'] : [];

    $hasWeeklyChart = !empty($weeklyLabels) && !empty($weeklyDatasets);

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
?>

<div class="rd">
    <!-- ===== STATS ===== -->
    <div class="rd-stats">
        <div class="rd-stat">
            <div class="rd-stat-icon rd-stat-icon--amber">
                <i class="bi bi-clock-history" aria-hidden="true"></i>
            </div>
            <div class="rd-stat-body">
                <div class="rd-stat-value"><?= number_format($pending) ?></div>
                <div class="rd-stat-label">Pending read</div>
                <div class="rd-stat-sub">Awaiting interpretation</div>
            </div>
        </div>

        <div class="rd-stat">
            <div class="rd-stat-icon rd-stat-icon--blue">
                <i class="bi bi-arrow-repeat" aria-hidden="true"></i>
            </div>
            <div class="rd-stat-body">
                <div class="rd-stat-value"><?= number_format($processing) ?></div>
                <div class="rd-stat-label">In reading</div>
                <div class="rd-stat-sub">Currently open</div>
            </div>
        </div>

        <div class="rd-stat">
            <div class="rd-stat-icon rd-stat-icon--green">
                <i class="bi bi-check2-circle" aria-hidden="true"></i>
            </div>
            <div class="rd-stat-body">
                <div class="rd-stat-value"><?= number_format($completed) ?></div>
                <div class="rd-stat-label">Completed</div>
                <div class="rd-stat-sub">Report signed</div>
            </div>
        </div>

        <div class="rd-stat">
            <div class="rd-stat-icon rd-stat-icon--teal">
                <i class="bi bi-send-check" aria-hidden="true"></i>
            </div>
            <div class="rd-stat-body">
                <div class="rd-stat-value"><?= number_format($released) ?></div>
                <div class="rd-stat-label">Released</div>
                <div class="rd-stat-sub">Sent to reception</div>
            </div>
        </div>

        <div class="rd-stat">
            <div class="rd-stat-icon rd-stat-icon--violet">
                <i class="bi bi-x-ray" aria-hidden="true"></i>
            </div>
            <div class="rd-stat-body">
                <div class="rd-stat-value"><?= number_format($total) ?></div>
                <div class="rd-stat-label">Total studies</div>
                <div class="rd-stat-sub">All X-ray examinations</div>
            </div>
        </div>
    </div>


    <!-- ===== WEEKLY VOLUME ===== -->
    <section class="rd-panel rd-panel--chart">
        <header class="rd-panel-head">
            <div>
                <h5 class="rd-panel-title">
                    <i class="bi bi-bar-chart" aria-hidden="true"></i>
                    Weekly study volume
                </h5>
                <p class="rd-panel-sub">Stacked counts by modality</p>
            </div>
            <div class="rd-legend" id="weeklyLegend" aria-hidden="true"></div>
        </header>

        <?php if ($hasWeeklyChart): ?>
            <div class="rd-chart">
                <canvas id="weeklyVolumeChart"></canvas>
            </div>
        <?php else: ?>
            <div class="rd-chart rd-chart--empty">
                <i class="bi bi-bar-chart" aria-hidden="true"></i>
                <p>No volume data for this week</p>
                <small>Once examinations are scheduled, their counts appear here.</small>
            </div>
        <?php endif; ?>
    </section>


    <!-- ===== WORKLIST ===== -->
    <section class="rd-panel">
        <header class="rd-panel-head">
            <div>
                <h5 class="rd-panel-title">
                    <i class="bi bi-list-check" aria-hidden="true"></i>
                    Reading worklist
                </h5>
                <p class="rd-panel-sub">Pending examinations, oldest first</p>
            </div>

            <div class="rd-panel-actions">
                <div class="rd-tabs" role="group" aria-label="Filter worklist">
                    <button type="button" class="rd-tab is-active" data-filter="all"     aria-pressed="true">All</button>
                    <button type="button" class="rd-tab"          data-filter="stat"    aria-pressed="false">STAT</button>
                    <button type="button" class="rd-tab"          data-filter="pending" aria-pressed="false">Pending</button>
                    <button type="button" class="rd-tab"          data-filter="reading" aria-pressed="false">Reading</button>
                </div>

                <button type="button" class="rd-btn" onclick="refreshTable()">
                    <i class="bi bi-arrow-clockwise" aria-hidden="true"></i>
                    Refresh
                </button>
            </div>
        </header>

        <?php if (!empty($pendingExaminations)): ?>
            <div class="rd-table-wrap">
                <table class="rd-table" id="queueTable">
                    <thead>
                        <tr>
                            <th scope="col">Accession</th>
                            <th scope="col">Patient</th>
                            <th scope="col">Exam</th>
                            <th scope="col">Referred by</th>
                            <th scope="col">Priority</th>
                            <th scope="col">Date</th>
                            <th scope="col">Status</th>
                            <th scope="col" class="rd-c-actions"><span class="visually-hidden">Action</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendingExaminations as $exam):
                            $examId    = (int) ($exam['id'] ?? 0);
                            $statusKey = (string) ($exam['status'] ?? 'pending');
                            $meta      = $statusMeta[$statusKey] ?? ['label' => ucfirst($statusKey), 'tone' => 'pending'];
                            $name      = (string) ($exam['patient_name'] ?? 'Unknown');
                            $examType  = (string) ($exam['exam_type'] ?? '');
                            $doctor    = trim((string) ($exam['doctor_name'] ?? ''));
                            $isStat    = strtolower((string) ($exam['priority'] ?? '')) === 'stat';
                            $dateTs    = !empty($exam['exam_date']) ? strtotime($exam['exam_date']) : false;
                        ?>
                            <tr class="rd-row" data-status="<?= esc($statusKey, 'attr') ?>" data-stat="<?= $isStat ? '1' : '0' ?>">
                                <td data-label="Accession" class="rd-c-accession">
                                    <span class="rd-ref">XR-<?= esc(date('y')) ?>-<?= esc(str_pad((string) $examId, 4, '0', STR_PAD_LEFT)) ?></span>
                                </td>

                                <td data-label="Patient">
                                    <div class="rd-patient">
                                        <span class="rd-avatar" aria-hidden="true"><?= esc($initialsOf($name)) ?></span>
                                        <div class="rd-patient-body">
                                            <span class="rd-name"><?= esc($name) ?></span>
                                            <span class="rd-sub">
                                                <?= esc($exam['age'] ?? '—') ?> yrs
                                                <span aria-hidden="true">·</span>
                                                <?= esc($exam['gender'] ?? '—') ?>
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                <td data-label="Exam"><?= esc($examType !== '' ? $examType : '—') ?></td>

                                <td data-label="Referred by"><?= $doctor !== '' ? esc($doctor) : '<span class="rd-muted">—</span>' ?></td>

                                <td data-label="Priority">
                                    <?php if ($isStat): ?>
                                        <span class="rd-tag rd-tag--stat">STAT</span>
                                    <?php else: ?>
                                        <span class="rd-muted">Routine</span>
                                    <?php endif; ?>
                                </td>

                                <td data-label="Date" class="rd-c-date">
                                    <?= $dateTs ? esc(date('M j, Y', $dateTs)) : '—' ?>
                                </td>

                                <td data-label="Status">
                                    <span class="rd-status rd-status--<?= esc($meta['tone'], 'attr') ?>">
                                        <?= esc($meta['label']) ?>
                                    </span>
                                </td>

                                <td data-label="Action" class="rd-c-actions">
                                    <div class="rd-actions">
                                        <a href="<?= base_url('radiologist/examination/view/' . $examId) ?>"
                                           class="rd-icon-btn"
                                           title="Open study"
                                           aria-label="Open <?= esc($name, 'attr') ?>">
                                            <i class="bi bi-eye" aria-hidden="true"></i>
                                        </a>
                                        <a href="<?= base_url('radiologist/examination/view/' . $examId) ?>"
                                           class="rd-icon-btn rd-icon-btn--read"
                                           title="Read / interpret"
                                           aria-label="Read study for <?= esc($name, 'attr') ?>">
                                            <i class="bi bi-pencil-square" aria-hidden="true"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="rd-empty">
                <div class="rd-empty-icon"><i class="bi bi-inbox" aria-hidden="true"></i></div>
                <h3>Nothing waiting</h3>
                <p>Every pending study has been picked up. New requests will appear here as the receptionist creates them.</p>
                <a href="<?= base_url('radiologist/examinations') ?>" class="rd-btn rd-btn--primary">
                    <i class="bi bi-list-ul" aria-hidden="true"></i>
                    Open examinations
                </a>
            </div>
        <?php endif; ?>

        <!-- Shown when a filter matches nothing -->
        <div class="rd-empty rd-empty--filter" id="rdNoMatch" hidden>
            <div class="rd-empty-icon"><i class="bi bi-search" aria-hidden="true"></i></div>
            <h3>No matching studies</h3>
            <p>Try a different filter, or select All to see the full worklist.</p>
            <button type="button" class="rd-btn" id="rdClearFilters">Show all</button>
        </div>
    </section>

</div>

<style>
/* =========================================================
   RADIOLOGIST · DASHBOARD
   Namespaced under .rd so the layout's generic card and table
   rules can't leak in.
   ========================================================= */

.rd {
    --rd-ink:         #0f172a;
    --rd-text:        #334155;
    --rd-muted:       #64748b;
    --rd-faint:       #94a3b8;
    --rd-line:        #e2e8f0;
    --rd-line-soft:   #f1f5f9;
    --rd-surface:     #ffffff;
    --rd-subtle:      #f8fafc;
    --rd-accent:      #1d4ed8;
    --rd-accent-dark: #1e40af;
    --rd-accent-soft: #eaf2fe;
    --rd-green:       #047857;
    --rd-green-soft:  #ecfdf5;
    --rd-amber:       #b45309;
    --rd-amber-soft:  #fff4e5;
    --rd-teal:        #0f766e;
    --rd-teal-soft:   #f0fdfa;
    --rd-violet:      #6d28d9;
    --rd-violet-soft: #f3e8ff;
    --rd-danger:      #b91c1c;
    --rd-danger-soft: #fef2f2;
    --rd-radius:      12px;
    --rd-radius-sm:   8px;
    --rd-mono:        ui-monospace, SFMono-Regular, "SF Mono", Menlo, Consolas, monospace;

    color: var(--rd-text);
    font-size: 0.875rem;
}

.rd *:focus-visible { outline: 2px solid var(--rd-accent); outline-offset: 2px; }

.rd-muted { color: var(--rd-faint); }

/* ---------- Header ---------- */

.rd-head {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
    margin-bottom: 1.25rem;
}

.rd-title {
    margin: 0 0 0.2rem;
    font-size: 1.25rem;
    font-weight: 650;
    letter-spacing: -0.015em;
    color: var(--rd-ink);
}

.rd-lede {
    margin: 0;
    font-size: 0.8125rem;
    color: var(--rd-muted);
}

.rd-lede strong { font-weight: 600; color: var(--rd-ink); }
.rd-lede span { margin: 0 0.2rem; color: var(--rd-faint); }

.rd-head-actions { display: flex; gap: 0.5rem; }

/* ---------- Buttons ---------- */

.rd-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.4rem;
    height: 36px;
    padding: 0 0.9rem;
    font-size: 0.8125rem;
    font-weight: 600;
    line-height: 1;
    color: var(--rd-text);
    background: var(--rd-surface);
    border: 1px solid var(--rd-line);
    border-radius: var(--rd-radius-sm);
    box-shadow: 0 1px 1px rgba(15, 23, 42, 0.03);
    white-space: nowrap;
    text-decoration: none;
    cursor: pointer;
    transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
}

.rd-btn:hover { background: var(--rd-subtle); border-color: #cbd5e1; color: var(--rd-ink); }
.rd-btn i { font-size: 0.9em; }

.rd-btn--primary,
.rd-btn--primary:hover { color: #ffffff; }
.rd-btn--primary { background: var(--rd-accent); border-color: var(--rd-accent); }
.rd-btn--primary:hover { background: var(--rd-accent-dark); border-color: var(--rd-accent-dark); }

/* ---------- Stats ---------- */

.rd-stats {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 0.9rem;
    margin-bottom: 1.25rem;
}

.rd-stat {
    display: flex;
    align-items: center;
    gap: 0.85rem;
    min-width: 0;
    padding: 0.9rem 1rem;
    background: var(--rd-surface);
    border: 1px solid var(--rd-line);
    border-radius: var(--rd-radius);
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
}

.rd-stat-icon {
    display: grid;
    place-items: center;
    flex-shrink: 0;
    width: 36px;
    height: 36px;
    font-size: 1rem;
    border-radius: var(--rd-radius-sm);
}

.rd-stat-icon--amber  { background: var(--rd-amber-soft); color: var(--rd-amber); }
.rd-stat-icon--blue   { background: var(--rd-accent-soft); color: var(--rd-accent); }
.rd-stat-icon--green  { background: var(--rd-green-soft); color: var(--rd-green); }
.rd-stat-icon--teal   { background: var(--rd-teal-soft); color: var(--rd-teal); }
.rd-stat-icon--violet { background: var(--rd-violet-soft); color: var(--rd-violet); }

.rd-stat-body { min-width: 0; }

.rd-stat-value {
    font-size: 1.35rem;
    font-weight: 700;
    line-height: 1.1;
    letter-spacing: -0.02em;
    color: var(--rd-ink);
    font-variant-numeric: tabular-nums;
}

.rd-stat-label {
    margin-top: 0.15rem;
    font-size: 0.8125rem;
    font-weight: 600;
    color: var(--rd-text);
}

.rd-stat-sub {
    font-size: 0.72rem;
    color: var(--rd-muted);
}

/* ---------- Panel ---------- */

.rd-panel {
    background: var(--rd-surface);
    border: 1px solid var(--rd-line);
    border-radius: var(--rd-radius);
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    overflow: hidden;
    margin-bottom: 1rem;
}

.rd-panel--chart { margin-bottom: 1rem; }

.rd-panel-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.75rem;
    flex-wrap: wrap;
    padding: 0.9rem 1.1rem;
    border-bottom: 1px solid var(--rd-line-soft);
}

.rd-panel-title {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin: 0;
    font-size: 0.9rem;
    font-weight: 650;
    color: var(--rd-ink);
}

.rd-panel-title i { color: var(--rd-accent); font-size: 0.9rem; }

.rd-panel-sub {
    margin: 0.15rem 0 0;
    font-size: 0.78rem;
    color: var(--rd-muted);
}

.rd-panel-actions {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
}

/* ---------- Tabs ---------- */

.rd-tabs {
    display: inline-flex;
    align-items: center;
    gap: 0.15rem;
    padding: 3px;
    background: var(--rd-line-soft);
    border: 1px solid var(--rd-line);
    border-radius: 9px;
}

.rd-tab {
    padding: 0.3rem 0.7rem;
    font-size: 0.78rem;
    font-weight: 600;
    color: var(--rd-muted);
    background: none;
    border: 0;
    border-radius: 6px;
    cursor: pointer;
    white-space: nowrap;
    transition: background-color 0.15s ease, color 0.15s ease, box-shadow 0.15s ease;
}

.rd-tab:hover { color: var(--rd-ink); }
.rd-tab.is-active {
    color: var(--rd-ink);
    background: var(--rd-surface);
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.08);
}

/* ---------- Chart ---------- */

.rd-legend {
    display: flex;
    flex-wrap: wrap;
    gap: 0.65rem;
    align-items: center;
    font-size: 0.75rem;
    color: var(--rd-muted);
}

.rd-legend span {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
}

.rd-legend-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    display: inline-block;
}

.rd-chart {
    position: relative;
    height: 280px;
    padding: 1rem;
}

.rd-chart--empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0.35rem;
    text-align: center;
    color: var(--rd-muted);
}

.rd-chart--empty i { font-size: 1.6rem; color: var(--rd-faint); }
.rd-chart--empty p { margin: 0; font-weight: 600; color: var(--rd-ink); font-size: 0.9rem; }
.rd-chart--empty small { font-size: 0.78rem; color: var(--rd-faint); }

/* ---------- Table ---------- */

.rd-table-wrap { overflow-x: auto; }

.rd-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.8125rem;
    color: var(--rd-text);
}

.rd-table th {
    padding: 0.65rem 1rem;
    font-size: 0.72rem;
    font-weight: 600;
    letter-spacing: 0.01em;
    text-align: left;
    white-space: nowrap;
    color: var(--rd-muted);
    background: var(--rd-subtle);
    border-bottom: 1px solid var(--rd-line);
}

.rd-table td {
    padding: 0.85rem 1rem;
    vertical-align: middle;
    border-bottom: 1px solid var(--rd-line-soft);
}

.rd-table tbody tr:last-child td { border-bottom: 0; }

.rd-row { transition: background-color 0.12s ease; }
.rd-row:hover { background: #fafbfd; }

.rd-row td:first-child { position: relative; }

.rd-row td:first-child::before {
    content: "";
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 3px;
    background: var(--rail, transparent);
}

.rd-row[data-status="pending"]     { --rail: #f59e0b; }
.rd-row[data-status="in_progress"] { --rail: #3b82f6; }
.rd-row[data-status="processing"]  { --rail: #3b82f6; }
.rd-row[data-status="completed"]   { --rail: #10b981; }
.rd-row[data-status="released"]    { --rail: #14b8a6; }

.rd-c-accession { white-space: nowrap; }

.rd-ref {
    padding: 0.15rem 0.5rem;
    font-family: var(--rd-mono);
    font-size: 0.76rem;
    font-weight: 600;
    color: var(--rd-ink);
    background: var(--rd-line-soft);
    border-radius: 5px;
}

.rd-patient { display: flex; align-items: center; gap: 0.6rem; min-width: 0; }

.rd-avatar {
    display: grid;
    place-items: center;
    flex-shrink: 0;
    width: 32px;
    height: 32px;
    font-size: 0.68rem;
    font-weight: 700;
    color: var(--rd-accent);
    background: var(--rd-accent-soft);
    border-radius: 50%;
}

.rd-patient-body { min-width: 0; }

.rd-name {
    display: block;
    font-weight: 600;
    color: var(--rd-ink);
    line-height: 1.3;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.rd-sub { display: block; font-size: 0.72rem; color: var(--rd-muted); }
.rd-sub span { margin: 0 0.15rem; color: var(--rd-faint); }

.rd-c-date { white-space: nowrap; font-variant-numeric: tabular-nums; }

.rd-tag {
    display: inline-flex;
    align-items: center;
    padding: 0.15rem 0.5rem;
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    border-radius: 4px;
}

.rd-tag--stat { color: var(--rd-danger); background: var(--rd-danger-soft); }

.rd-status {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.2rem 0.55rem;
    font-size: 0.72rem;
    font-weight: 600;
    white-space: nowrap;
    color: var(--rd-tone-fg);
    background: var(--rd-tone-bg);
    border-radius: 999px;
}

.rd-status::before {
    content: "";
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: var(--rd-tone-dot);
}

.rd-status--pending   { --rd-tone-bg: var(--rd-amber-soft); --rd-tone-fg: var(--rd-amber); --rd-tone-dot: #f59e0b; }
.rd-status--progress  { --rd-tone-bg: var(--rd-accent-soft); --rd-tone-fg: var(--rd-accent); --rd-tone-dot: #3b82f6; }
.rd-status--completed { --rd-tone-bg: var(--rd-green-soft); --rd-tone-fg: var(--rd-green); --rd-tone-dot: #10b981; }
.rd-status--released  { --rd-tone-bg: var(--rd-teal-soft); --rd-tone-fg: var(--rd-teal); --rd-tone-dot: #14b8a6; }

.rd-c-actions { width: 1%; text-align: right; white-space: nowrap; }

.rd-actions { display: inline-flex; align-items: center; gap: 0.25rem; }

.rd-icon-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    font-size: 0.85rem;
    color: var(--rd-muted);
    background: transparent;
    border: 1px solid transparent;
    border-radius: var(--rd-radius-sm);
    text-decoration: none;
    cursor: pointer;
    transition: background-color 0.15s ease, color 0.15s ease, border-color 0.15s ease;
}

.rd-icon-btn:hover { background: var(--rd-accent-soft); color: var(--rd-accent); border-color: #dbe6fb; }
.rd-icon-btn--read:hover { background: var(--rd-green-soft); color: var(--rd-green); border-color: #a7f3d0; }

/* ---------- Empty states ---------- */

.rd-empty { padding: 3.5rem 1rem; text-align: center; }
.rd-empty--filter { border-top: 1px solid var(--rd-line); }

.rd-empty-icon {
    display: grid;
    place-items: center;
    width: 44px;
    height: 44px;
    margin: 0 auto 0.85rem;
    font-size: 1.2rem;
    color: var(--rd-faint);
    background: var(--rd-line-soft);
    border-radius: 10px;
}

.rd-empty h3 { margin: 0 0 0.25rem; font-size: 0.95rem; font-weight: 600; color: var(--rd-ink); }
.rd-empty p { max-width: 26rem; margin: 0 auto 1rem; font-size: 0.8125rem; color: var(--rd-muted); }

/* ---------- Responsive ---------- */

@media (max-width: 1200px) {
    .rd-stats { grid-template-columns: repeat(3, minmax(0, 1fr)); }
}

@media (max-width: 900px) {
    .rd-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}

@media (max-width: 768px) {
    .rd-panel-head { flex-direction: column; align-items: stretch; }
    .rd-panel-actions { width: 100%; justify-content: space-between; }
    .rd-chart { height: 240px; }

    .rd-table thead { display: none; }

    .rd-table,
    .rd-table tbody,
    .rd-table tr,
    .rd-table td { display: block; width: 100%; }

    .rd-table tr.rd-row {
        padding: 0.9rem 1rem;
        border-bottom: 1px solid var(--rd-line);
    }

    .rd-row td:first-child::before { top: 0; bottom: 0; }

    .rd-table td {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 0.25rem 0;
        text-align: right;
        border: 0;
    }

    .rd-table td[data-label]::before {
        content: attr(data-label);
        flex-shrink: 0;
        font-size: 0.75rem;
        font-weight: 400;
        color: var(--rd-muted);
        text-align: left;
    }

    .rd-table td.rd-c-accession,
    .rd-table td:nth-child(2) { display: block; text-align: left; }
    .rd-table td.rd-c-accession::before,
    .rd-table td:nth-child(2)::before { content: none; }
    .rd-table td:nth-child(2) { margin: 0.35rem 0 0.5rem; }

    .rd-table td.rd-c-actions { justify-content: flex-end; margin-top: 0.5rem; }
}

@media (max-width: 576px) {
    .rd-stats { grid-template-columns: minmax(0, 1fr); gap: 0.6rem; }
}

@media (prefers-reduced-motion: reduce) {
    .rd *, .rd *::before, .rd *::after { transition: none !important; }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
(function () {
    'use strict';

    /* =====================================================
       WEEKLY VOLUME CHART
       Data comes from XrayExaminationModel::getWeeklyVolumeData()
       via the controller. If nothing was passed, the chart canvas
       is not rendered at all (the PHP template shows a placeholder
       instead), so this block just checks and exits.
       ===================================================== */

    var weeklyCanvas = document.getElementById('weeklyVolumeChart');

    if (weeklyCanvas && typeof Chart !== 'undefined') {

        var weeklyLabels   = <?= json_encode($weeklyLabels,   JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        var weeklyDatasets = <?= json_encode($weeklyDatasets, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

        // Legend: one dot per dataset, so the reader sees which colour
        // belongs to which modality without relying on Chart.js's own
        // legend (kept off so the header layout stays tight).
        var legend = document.getElementById('weeklyLegend');

        if (legend && Array.isArray(weeklyDatasets)) {
            legend.innerHTML = weeklyDatasets.map(function (ds) {
                var color = ds.backgroundColor || '#94a3b8';
                return '<span><i class="rd-legend-dot" style="background:' + color + '"></i>' +
                       String(ds.label || '') + '</span>';
            }).join('');
        }

        new Chart(weeklyCanvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels: weeklyLabels,
                datasets: weeklyDatasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#ffffff',
                        titleColor: '#0f172a',
                        bodyColor: '#334155',
                        borderColor: '#e2e8f0',
                        borderWidth: 1,
                        padding: 10,
                        cornerRadius: 6
                    }
                },
                scales: {
                    x: {
                        stacked: true,
                        grid: { display: false },
                        border: { display: false },
                        ticks: { color: '#94a3b8', font: { size: 11 } }
                    },
                    y: {
                        stacked: true,
                        beginAtZero: true,
                        grid: { color: '#f1f5f9', drawBorder: false, drawTicks: false },
                        border: { display: false },
                        ticks: { color: '#94a3b8', font: { size: 11 }, stepSize: 1, precision: 0 }
                    }
                }
            }
        });
    }


    /* =====================================================
       WORKLIST FILTER
       Tabs filter the table rows by status, with a special case
       for the STAT tab which reads the data-stat attribute.
       ===================================================== */

    var tabs = Array.prototype.slice.call(document.querySelectorAll('.rd-tab'));
    var table = document.getElementById('queueTable');
    var noMatch = document.getElementById('rdNoMatch');
    var clearBtn = document.getElementById('rdClearFilters');

    function rows() {
        return table ? Array.prototype.slice.call(table.querySelectorAll('tbody tr.rd-row')) : [];
    }

    function applyFilter(filter) {
        var visible = 0;

        rows().forEach(function (row) {
            var status = (row.dataset.status || '').toLowerCase();
            var isStat = row.dataset.stat === '1';

            var show = false;
            if (filter === 'all') {
                show = true;
            } else if (filter === 'stat') {
                show = isStat;
            } else if (filter === 'reading') {
                // The DB and the codebase use both words for this state.
                show = status === 'in_progress' || status === 'processing';
            } else {
                show = status === filter;
            }

            row.style.display = show ? '' : 'none';
            if (show) { visible++; }
        });

        if (noMatch) {
            noMatch.hidden = !(rows().length > 0 && visible === 0);
        }
    }

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            tabs.forEach(function (t) {
                var on = t === tab;
                t.classList.toggle('is-active', on);
                t.setAttribute('aria-pressed', on ? 'true' : 'false');
            });
            applyFilter(tab.dataset.filter || 'all');
        });
    });

    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            tabs.forEach(function (t) {
                var on = t.dataset.filter === 'all';
                t.classList.toggle('is-active', on);
                t.setAttribute('aria-pressed', on ? 'true' : 'false');
            });
            applyFilter('all');
        });
    }

    applyFilter('all');
})();


/* =====================================================
   REFRESH
   Reloads the page so the counts and worklist re-read from
   the database. A spinner shows on the button while it fires.
   ===================================================== */

function refreshTable() {
    var btn = event && event.currentTarget ? event.currentTarget : document.querySelector('.rd-btn[onclick*="refreshTable"]');
    var icon = btn ? btn.querySelector('i') : null;

    if (icon) {
        icon.style.animation = 'rd-spin 0.8s linear infinite';
    }

    setTimeout(function () {
        if (icon) { icon.style.animation = 'none'; }
        location.reload();
    }, 600);
}

(function () {
    var style = document.createElement('style');
    style.textContent = '@keyframes rd-spin { to { transform: rotate(360deg); } }';
    document.head.appendChild(style);
})();
</script>

<?= $this->endSection() ?>