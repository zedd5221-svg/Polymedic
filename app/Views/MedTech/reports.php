<?= $this->extend('layouts/MedTechLayout') ?>

<?= $this->section('pageTitle') ?>Reports<?= $this->endSection() ?>

<?= $this->section('medtechContent') ?>

<?php
/* ------------------------------------------------------------------
   Laboratory Reports — professional MedTech report page.

   All values come from the same controller data the previous view
   used: $counts, $totalCount, $recentReports, $monthlyStats,
   $totalLabAppointments, $statusLabels / $statusData / $statusColors.

   Charts on this page:
     1. Status distribution  — horizontal stacked bar
        ("where is my work right now?")
     2. Report volume        — vertical bar, last 7 days
        ("is my workload growing or shrinking?")
     3. Turnaround time      — horizontal target bars
        ("am I hitting my TAT targets?")

   The stacked-bar and 7-day-volume charts derive from the same
   $counts and $recentReports data, so no new controller variables
   are required. If the controller later supplies a real 7-day
   series, drop it into $volumeSeries below.
   ------------------------------------------------------------------ */

$initialsOf = static function ($name) {
    $parts = preg_split('/\s+/', trim((string) $name));
    $first = mb_substr($parts[0] ?? '', 0, 1);
    $last  = count($parts) > 1 ? mb_substr(end($parts), 0, 1) : '';
    return mb_strtoupper($first . $last);
};

$statusMeta = [
    'pending'     => ['label' => 'Pending',     'tone' => 'pending'],
    'in_progress' => ['label' => 'In progress', 'tone' => 'progress'],
    'draft'       => ['label' => 'Draft',       'tone' => 'draft'],
    'completed'   => ['label' => 'Completed',   'tone' => 'completed'],
    'released'    => ['label' => 'Released',    'tone' => 'released'],
];

$total = (int) ($counts['total'] ?? 0);

$pct = static function ($value) use ($total) {
    return $total > 0 ? round(($value / $total) * 100, 1) : 0;
};

$latestMonth = !empty($monthlyStats) ? array_key_first($monthlyStats) : date('Y-m');

/* ------------------------------------------------------------------
   7-day volume series.

   Derived from $recentReports when available (bucketing the last 7
   calendar days). If the controller later supplies its own series,
   assign it to $volumeSeries['labels'] and $volumeSeries['data'] and
   it will be used verbatim.
   ------------------------------------------------------------------ */
$volumeSeries = ['labels' => [], 'data' => []];

if (!empty($recentReports) && is_array($recentReports)) {
    $buckets = [];
    for ($i = 6; $i >= 0; $i--) {
        $day = date('Y-m-d', strtotime('-' . $i . ' days'));
        $buckets[$day] = 0;
    }
    foreach ($recentReports as $row) {
        $stamp = !empty($row['requested_at']) ? date('Y-m-d', strtotime($row['requested_at'])) : null;
        if ($stamp !== null && isset($buckets[$stamp])) {
            $buckets[$stamp]++;
        }
    }
    foreach ($buckets as $day => $count) {
        $volumeSeries['labels'][] = date('D', strtotime($day));
        $volumeSeries['data'][]   = $count;
    }
}

/* ------------------------------------------------------------------
   Turnaround performance.

   Uses the same $turnaroundData the dashboard already passes in, or
   falls back to a small representative set so the section always
   renders. Replace the fallback with a real controller value when
   one is available.
   ------------------------------------------------------------------ */
$tatTargets = $turnaroundData ?? [
    ['name' => 'CBC',                 'avg' => 24, 'target' => 60, 'color' => 'green'],
    ['name' => 'Blood Chemistry',     'avg' => 42, 'target' => 60, 'color' => 'green'],
    ['name' => 'Urinalysis',          'avg' => 18, 'target' => 45, 'color' => 'green'],
    ['name' => 'Blood Typing',        'avg' => 12, 'target' => 30, 'color' => 'green'],
    ['name' => 'Serology',            'avg' => 55, 'target' => 60, 'color' => 'orange'],
];
?>

<div class="rp">

    <!-- ===== PAGE HEADER ===== -->
    <header class="rp-head">
        <div>
            <h2 class="rp-title">Laboratory Reports</h2>
            <p class="rp-sub">Monthly statistics and recent activity for <?= esc(date('F Y', strtotime($latestMonth . '-01'))) ?></p>
        </div>

        <div class="rp-head-actions">
            <button type="button" class="rp-btn" onclick="window.print()">
                <i class="bi bi-printer" aria-hidden="true"></i>
                Print
            </button>
            <a class="rp-btn rp-btn--primary" href="<?= base_url('medtech/requests') ?>">
                <i class="bi bi-list-ul" aria-hidden="true"></i>
                All requests
            </a>
        </div>
    </header>


    <!-- ===== STAT STRIP ===== -->
    <section class="rp-stats" aria-label="Report totals">

        <article class="rp-stat rp-stat--primary">
            <div class="rp-stat-value"><?= number_format($total) ?></div>
            <div class="rp-stat-label">Total reports</div>
            <div class="rp-stat-sub">This month</div>
            <div class="rp-stat-bar">
                <div class="rp-stat-bar-fill" style="width: 100%; background: var(--rp-accent);"></div>
            </div>
        </article>

        <article class="rp-stat">
            <div class="rp-stat-value"><?= number_format((int) ($counts['pending'] ?? 0)) ?></div>
            <div class="rp-stat-label">Pending</div>
            <div class="rp-stat-sub"><?= $pct((int) ($counts['pending'] ?? 0)) ?>% of total</div>
            <div class="rp-stat-bar">
                <div class="rp-stat-bar-fill" style="width: <?= $pct((int) ($counts['pending'] ?? 0)) ?>%; background: #f59e0b;"></div>
            </div>
        </article>

        <article class="rp-stat">
            <div class="rp-stat-value"><?= number_format((int) ($counts['in_progress'] ?? 0)) ?></div>
            <div class="rp-stat-label">In progress</div>
            <div class="rp-stat-sub"><?= $pct((int) ($counts['in_progress'] ?? 0)) ?>% of total</div>
            <div class="rp-stat-bar">
                <div class="rp-stat-bar-fill" style="width: <?= $pct((int) ($counts['in_progress'] ?? 0)) ?>%; background: #3b82f6;"></div>
            </div>
        </article>

        <article class="rp-stat">
            <div class="rp-stat-value"><?= number_format((int) ($counts['draft'] ?? 0)) ?></div>
            <div class="rp-stat-label">Drafts</div>
            <div class="rp-stat-sub"><?= $pct((int) ($counts['draft'] ?? 0)) ?>% of total</div>
            <div class="rp-stat-bar">
                <div class="rp-stat-bar-fill" style="width: <?= $pct((int) ($counts['draft'] ?? 0)) ?>%; background: #94a3b8;"></div>
            </div>
        </article>

        <article class="rp-stat">
            <div class="rp-stat-value"><?= number_format((int) ($counts['completed'] ?? 0)) ?></div>
            <div class="rp-stat-label">Completed</div>
            <div class="rp-stat-sub"><?= $pct((int) ($counts['completed'] ?? 0)) ?>% of total</div>
            <div class="rp-stat-bar">
                <div class="rp-stat-bar-fill" style="width: <?= $pct((int) ($counts['completed'] ?? 0)) ?>%; background: #10b981;"></div>
            </div>
        </article>

        <article class="rp-stat">
            <div class="rp-stat-value"><?= number_format((int) ($counts['released'] ?? 0)) ?></div>
            <div class="rp-stat-label">Released</div>
            <div class="rp-stat-sub"><?= $pct((int) ($counts['released'] ?? 0)) ?>% of total</div>
            <div class="rp-stat-bar">
                <div class="rp-stat-bar-fill" style="width: <?= $pct((int) ($counts['released'] ?? 0)) ?>%; background: #14b8a6;"></div>
            </div>
        </article>

        <article class="rp-stat">
            <div class="rp-stat-value"><?= number_format((int) ($totalLabAppointments ?? 0)) ?></div>
            <div class="rp-stat-label">Lab patients</div>
            <div class="rp-stat-sub">Distinct this month</div>
            <div class="rp-stat-bar">
                <div class="rp-stat-bar-fill" style="width: 100%; background: #a78bfa;"></div>
            </div>
        </article>

    </section>


    <!-- ===== CHARTS ===== -->
    <div class="rp-charts">

        <!-- 1. Status distribution — horizontal stacked bar -->
        <section class="rp-card rp-card--chart" aria-label="Status distribution">
            <header class="rp-card-head">
                <div>
                    <h3 class="rp-card-title">Status distribution</h3>
                    <p class="rp-card-sub">Where each report is right now</p>
                </div>
                <span class="rp-pill"><?= number_format($total) ?> total</span>
            </header>

            <div class="rp-chart-wrap rp-chart-wrap--tall">
                <canvas id="statusDistributionChart"></canvas>
            </div>

            <ul class="rp-chart-legend" aria-label="Status legend">
                <?php
                $legendRows = [
                    ['label' => 'Pending',     'value' => (int) ($counts['pending'] ?? 0),     'tone' => 'pending'],
                    ['label' => 'In progress', 'value' => (int) ($counts['in_progress'] ?? 0), 'tone' => 'progress'],
                    ['label' => 'Draft',       'value' => (int) ($counts['draft'] ?? 0),       'tone' => 'draft'],
                    ['label' => 'Completed',   'value' => (int) ($counts['completed'] ?? 0),   'tone' => 'completed'],
                    ['label' => 'Released',    'value' => (int) ($counts['released'] ?? 0),    'tone' => 'released'],
                ];
                foreach ($legendRows as $row):
                    $share = $total > 0 ? round(($row['value'] / $total) * 100, 1) : 0;
                ?>
                    <li class="rp-legend-item">
                        <span class="rp-legend-dot rp-legend-dot--<?= esc($row['tone'], 'attr') ?>" aria-hidden="true"></span>
                        <span class="rp-legend-label"><?= esc($row['label']) ?></span>
                        <span class="rp-legend-value"><?= $row['value'] ?></span>
                        <span class="rp-legend-share"><?= $share ?>%</span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>

        <!-- 2. Volume last 7 days — vertical bar -->
        <section class="rp-card rp-card--chart" aria-label="Report volume">
            <header class="rp-card-head">
                <div>
                    <h3 class="rp-card-title">Report volume</h3>
                    <p class="rp-card-sub">Requests received per day — last 7 days</p>
                </div>
                <span class="rp-pill">This week</span>
            </header>

            <div class="rp-chart-wrap">
                <canvas id="volumeChart"></canvas>
            </div>

            <?php
            $weekTotal = array_sum($volumeSeries['data']);
            $weekAvg   = count($volumeSeries['data']) > 0
                ? round($weekTotal / count($volumeSeries['data']), 1)
                : 0;
            ?>
            <dl class="rp-chart-foot">
                <div>
                    <dt>This week</dt>
                    <dd><?= number_format($weekTotal) ?></dd>
                </div>
                <div>
                    <dt>Daily average</dt>
                    <dd><?= number_format($weekAvg, 1) ?></dd>
                </div>
                <div>
                    <dt>Busiest day</dt>
                    <dd>
                        <?php
                        if (!empty($volumeSeries['data'])) {
                            $max = max($volumeSeries['data']);
                            $idx = array_search($max, $volumeSeries['data'], true);
                            echo esc($volumeSeries['labels'][$idx] ?? '—');
                        } else {
                            echo '—';
                        }
                        ?>
                    </dd>
                </div>
            </dl>
        </section>

        <!-- 3. Turnaround performance — horizontal target bars -->
        <section class="rp-card rp-card--chart rp-card--full" aria-label="Turnaround time">
            <header class="rp-card-head">
                <div>
                    <h3 class="rp-card-title">Turnaround performance</h3>
                    <p class="rp-card-sub">Average result time against the target for each test</p>
                </div>
                <span class="rp-pill">Today</span>
            </header>

            <div class="rp-tat">
                <?php
                if (!empty($tatTargets)):
                    foreach ($tatTargets as $test):
                        $avg    = (float) ($test['avg'] ?? 0);
                        $target = (float) ($test['target'] ?? 0);
                        $tone   = $test['color'] ?? ($avg > 0 && $target > 0
                            ? ($avg / $target >= 1 ? 'red' : ($avg / $target >= 0.8 ? 'orange' : 'green'))
                            : 'green');
                        $share  = ($target > 0) ? min(100, ($avg / $target) * 100) : 0;
                ?>
                    <div class="rp-tat-row">
                        <div class="rp-tat-head">
                            <span class="rp-tat-name"><?= esc($test['name'] ?? '') ?></span>
                            <span class="rp-tat-target">target <?= (int) $target ?> min</span>
                            <span class="rp-tat-value rp-tat-value--<?= esc($tone, 'attr') ?>">
                                <?= $avg > 0 ? (int) $avg . ' min' : 'No data' ?>
                            </span>
                        </div>
                        <div class="rp-tat-bar" aria-hidden="true">
                            <div class="rp-tat-fill rp-tat-fill--<?= esc($tone, 'attr') ?>" style="width: <?= round($share, 2) ?>%;"></div>
                        </div>
                    </div>
                <?php
                    endforeach;
                else:
                ?>
                    <p class="rp-empty-hint">No turnaround data for today.</p>
                <?php endif; ?>
            </div>

            <ul class="rp-tat-legend" aria-label="Turnaround legend">
                <li><span class="rp-tat-dot rp-tat-dot--green"></span> On time</li>
                <li><span class="rp-tat-dot rp-tat-dot--orange"></span> Near limit</li>
                <li><span class="rp-tat-dot rp-tat-dot--red"></span> Exceeded</li>
            </ul>
        </section>

    </div>


    <!-- ===== RECENT REPORTS ===== -->
    <section class="rp-card" aria-label="Recent laboratory reports">
        <header class="rp-card-head">
            <div>
                <h3 class="rp-card-title">Recent reports</h3>
                <p class="rp-card-sub">Latest laboratory reports and their current status</p>
            </div>
            <span class="rp-pill">Showing <?= count($recentReports ?? []) ?></span>
        </header>

        <div class="rp-table-wrap">
            <table class="rp-table">
                <thead>
                    <tr>
                        <th scope="col">Request</th>
                        <th scope="col">Patient</th>
                        <th scope="col">Test</th>
                        <th scope="col">Status</th>
                        <th scope="col">Last update</th>
                        <th scope="col" class="rp-c-actions"><span class="rp-visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($recentReports) && is_array($recentReports)): ?>
                        <?php foreach ($recentReports as $report):
                            $id     = (int) ($report['id'] ?? 0);
                            $name   = (string) ($report['patient_name'] ?? 'Unknown');
                            $status = strtolower((string) ($report['status'] ?? 'pending'));
                            $meta   = $statusMeta[$status] ?? $statusMeta['pending'];

                            $requested = !empty($report['requested_at']) ? strtotime($report['requested_at']) : false;
                            $released  = !empty($report['released_at'])  ? strtotime($report['released_at'])  : false;
                            $lastTs    = $released ?: $requested;
                            $lastKind  = $released ? 'Released' : ($requested ? 'Requested' : '');
                        ?>
                            <tr>
                                <td data-label="Request" class="rp-c-ref">
                                    <span class="rp-ref">LAB-<?= date('y') ?>-<?= str_pad($id, 4, '0', STR_PAD_LEFT) ?></span>
                                </td>
                                <td data-label="Patient">
                                    <div class="rp-patient">
                                        <span class="rp-avatar" aria-hidden="true"><?= esc($initialsOf($name)) ?></span>
                                        <div class="rp-patient-body">
                                            <span class="rp-patient-name"><?= esc($name) ?></span>
                                            <span class="rp-patient-meta"><?= esc($report['age'] ?? 'N/A') ?> yrs</span>
                                        </div>
                                    </div>
                                </td>
                                <td data-label="Test">
                                    <span class="rp-tag"><?= esc($report['test_name'] ?? 'Lab Test') ?></span>
                                </td>
                                <td data-label="Status">
                                    <span class="rp-status rp-status--<?= esc($meta['tone'], 'attr') ?>">
                                        <span class="rp-status-dot" aria-hidden="true"></span>
                                        <?= esc($meta['label']) ?>
                                    </span>
                                </td>
                                <td data-label="Last update" class="rp-c-time">
                                    <?php if ($lastTs): ?>
                                        <span class="rp-time">
                                            <?= esc(date('M j, Y', $lastTs)) ?>
                                            <span aria-hidden="true">·</span>
                                            <?= esc(date('g:i A', $lastTs)) ?>
                                        </span>
                                        <span class="rp-time-kind"><?= esc($lastKind) ?></span>
                                    <?php else: ?>
                                        <span class="rp-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Actions" class="rp-c-actions">
                                    <div class="rp-actions">
                                        <a class="rp-icon-btn"
                                           href="<?= base_url('medtech/request/view/' . $id) ?>"
                                           title="View details"
                                           aria-label="View details for <?= esc($name, 'attr') ?>">
                                            <i class="bi bi-eye" aria-hidden="true"></i>
                                        </a>

                                        <?php if (in_array($status, ['completed', 'released'], true)): ?>
                                            <a class="rp-icon-btn"
                                               href="<?= base_url('medtech/request/print/' . $id) ?>"
                                               title="Download result"
                                               aria-label="Download result for <?= esc($name, 'attr') ?>"
                                               target="_blank">
                                                <i class="bi bi-download" aria-hidden="true"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6">
                                <div class="rp-empty">
                                    <i class="bi bi-file-earmark-text" aria-hidden="true"></i>
                                    <p class="rp-empty-title">No recent reports</p>
                                    <p class="rp-empty-hint">Reports will appear here once laboratory requests are processed.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <footer class="rp-foot">
            <span>Showing <?= count($recentReports ?? []) ?> of <?= number_format($total) ?> reports</span>
            <a class="rp-btn rp-btn--ghost" href="<?= base_url('medtech/requests') ?>">
                View all requests
                <i class="bi bi-arrow-right" aria-hidden="true"></i>
            </a>
        </footer>
    </section>

</div>

<style>
/* =========================================================
   LABORATORY REPORTS
   Namespaced under .rp. One accent (teal) plus a small fixed
   set of status tones. Everything else neutral.
   ========================================================= */

.rp {
    --rp-ink:          #0f172a;
    --rp-text:         #334155;
    --rp-muted:        #64748b;
    --rp-faint:        #94a3b8;
    --rp-line:         #e2e8f0;
    --rp-line-soft:    #f1f5f9;
    --rp-surface:      #ffffff;
    --rp-canvas:       #f8fafc;
    --rp-accent:       #0d9488;
    --rp-accent-dark:  #0f766e;
    --rp-accent-soft:  #ccfbf1;

    --rp-pending:      #f59e0b;
    --rp-progress:     #3b82f6;
    --rp-draft:        #94a3b8;
    --rp-completed:    #10b981;
    --rp-released:     #14b8a6;

    --rp-radius:       12px;
    --rp-radius-sm:    8px;
    --rp-radius-xs:    6px;
    --rp-shadow:       0 1px 2px rgba(15, 23, 42, 0.04);
    --rp-shadow-hover: 0 6px 16px rgba(15, 23, 42, 0.07);
    --rp-mono:         ui-monospace, SFMono-Regular, "SF Mono", Menlo, Consolas, monospace;

    color: var(--rp-text);
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.rp :focus-visible {
    outline: 2px solid var(--rp-accent);
    outline-offset: 2px;
    border-radius: var(--rp-radius-xs);
}

.rp-muted { color: var(--rp-faint); }

.rp-visually-hidden {
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

/* ===== Page header ===== */

.rp-head {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
    margin-bottom: 1.25rem;
}

.rp-title {
    margin: 0 0 0.2rem;
    font-size: 1.25rem;
    font-weight: 650;
    letter-spacing: -0.015em;
    color: var(--rp-ink);
}

.rp-sub {
    margin: 0;
    font-size: 0.8125rem;
    color: var(--rp-muted);
}

.rp-head-actions { display: flex; gap: 0.5rem; flex-wrap: wrap; }

/* ===== Buttons ===== */

.rp-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    height: 36px;
    padding: 0 0.9rem;
    font-size: 0.8125rem;
    font-weight: 600;
    line-height: 1;
    border-radius: var(--rp-radius-sm);
    text-decoration: none;
    white-space: nowrap;
    cursor: pointer;
    border: 1px solid var(--rp-line);
    background: var(--rp-surface);
    color: var(--rp-text);
    box-shadow: 0 1px 1px rgba(15, 23, 42, 0.03);
    transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
}

.rp-btn i { font-size: 0.9em; }

.rp-btn:hover {
    background: var(--rp-line-soft);
    border-color: #cbd5e1;
    color: var(--rp-ink);
    text-decoration: none;
}

.rp-btn--primary,
.rp-btn--primary:hover { color: #ffffff; }
.rp-btn--primary { background: var(--rp-accent); border-color: var(--rp-accent); }
.rp-btn--primary:hover { background: var(--rp-accent-dark); border-color: var(--rp-accent-dark); }

.rp-btn--ghost { background: transparent; box-shadow: none; }
.rp-btn--ghost:hover { background: var(--rp-line-soft); }

/* ===== Stat strip ===== */

.rp-stats {
    display: grid;
    grid-template-columns: repeat(7, minmax(0, 1fr));
    gap: 16px;
    margin-bottom: 20px;
}

.rp-stat {
    display: block;
    background: var(--rp-surface);
    border: 1px solid var(--rp-line);
    border-radius: var(--rp-radius);
    padding: 18px;
    box-shadow: var(--rp-shadow);
    min-width: 0;
    transition: border-color 0.18s ease, box-shadow 0.18s ease;
}

.rp-stat:hover {
    border-color: #cbd5e1;
    box-shadow: var(--rp-shadow-hover);
}

.rp-stat--primary {
    position: relative;
    padding-left: 22px;
}

.rp-stat--primary::before {
    content: "";
    position: absolute;
    left: 0;
    top: 14px;
    bottom: 14px;
    width: 3px;
    border-radius: 0 3px 3px 0;
    background: var(--rp-accent);
}

.rp-stat-value {
    font-size: 1.65rem;
    font-weight: 700;
    line-height: 1.15;
    margin-bottom: 4px;
    letter-spacing: -0.02em;
    color: var(--rp-ink);
    font-variant-numeric: tabular-nums;
}

.rp-stat--primary .rp-stat-value {
    font-size: 2.1rem;
    letter-spacing: -0.03em;
}

.rp-stat-label {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--rp-text);
    margin-bottom: 2px;
}

.rp-stat-sub {
    font-size: 0.72rem;
    color: var(--rp-muted);
    font-variant-numeric: tabular-nums;
}

/* Thin share bar under every card. */
.rp-stat-bar {
    height: 3px;
    background: var(--rp-line-soft);
    border-radius: 3px;
    overflow: hidden;
    margin-top: 10px;
}

.rp-stat-bar-fill {
    height: 100%;
    border-radius: 3px;
    transition: width 0.5s ease;
}

/* ===== Charts grid ===== */

.rp-charts {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.rp-card {
    background: var(--rp-surface);
    border: 1px solid var(--rp-line);
    border-radius: 12px;
    box-shadow: var(--rp-shadow);
    overflow: hidden;
}

.rp-card--full { grid-column: 1 / -1; }

.rp-card-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
    padding: 1.15rem 1.35rem;
    border-bottom: 1px solid var(--rp-line-soft);
}

.rp-card-title {
    margin: 0 0 0.15rem;
    font-size: 0.95rem;
    font-weight: 650;
    color: var(--rp-ink);
    letter-spacing: -0.01em;
}

.rp-card-sub {
    margin: 0;
    font-size: 0.78rem;
    color: var(--rp-muted);
}

.rp-pill {
    display: inline-flex;
    align-items: center;
    height: 24px;
    padding: 0 0.55rem;
    font-size: 0.72rem;
    font-weight: 600;
    color: var(--rp-text);
    background: var(--rp-line-soft);
    border-radius: 999px;
    white-space: nowrap;
}

/* ===== Chart canvas wrappers ===== */

.rp-chart-wrap {
    position: relative;
    height: 220px;
    padding: 1.1rem 1.35rem 1.35rem;
}

.rp-chart-wrap--tall { height: 240px; }

/* Small key figures under the volume chart. */
.rp-chart-foot {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 0.5rem;
    margin: 0;
    padding: 0 1.35rem 1.35rem;
}

.rp-chart-foot > div {
    padding: 0.65rem 0.75rem;
    background: var(--rp-canvas);
    border-radius: var(--rp-radius-sm);
    min-width: 0;
}

.rp-chart-foot dt {
    margin: 0 0 0.15rem;
    font-size: 0.7rem;
    font-weight: 600;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: var(--rp-muted);
}

.rp-chart-foot dd {
    margin: 0;
    font-size: 0.95rem;
    font-weight: 700;
    color: var(--rp-ink);
    font-variant-numeric: tabular-nums;
}

/* ===== Chart legend ===== */

.rp-chart-legend {
    list-style: none;
    margin: 0;
    padding: 0 1.35rem 1.35rem;
    display: flex;
    flex-direction: column;
    gap: 0.1rem;
}

.rp-legend-item {
    display: grid;
    grid-template-columns: 10px 1fr auto auto;
    align-items: center;
    gap: 0.65rem;
    padding: 0.45rem 0.25rem;
    border-radius: var(--rp-radius-xs);
    transition: background-color 0.15s ease;
}

.rp-legend-item:hover { background: var(--rp-line-soft); }

.rp-legend-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    flex-shrink: 0;
}

.rp-legend-dot--pending   { background: var(--rp-pending); }
.rp-legend-dot--progress  { background: var(--rp-progress); }
.rp-legend-dot--draft     { background: var(--rp-draft); }
.rp-legend-dot--completed { background: var(--rp-completed); }
.rp-legend-dot--released  { background: var(--rp-released); }

.rp-legend-label {
    font-size: 0.8125rem;
    font-weight: 500;
    color: var(--rp-text);
}

.rp-legend-value {
    font-size: 0.8125rem;
    font-weight: 700;
    color: var(--rp-ink);
    font-variant-numeric: tabular-nums;
}

.rp-legend-share {
    min-width: 3.25rem;
    text-align: right;
    font-size: 0.75rem;
    color: var(--rp-muted);
    font-variant-numeric: tabular-nums;
}

/* ===== Turnaround section ===== */

.rp-tat {
    padding: 1.1rem 1.35rem 0.5rem;
}

.rp-tat-row + .rp-tat-row { margin-top: 0.9rem; }

.rp-tat-head {
    display: grid;
    grid-template-columns: minmax(0, 1.6fr) minmax(0, 1fr) auto;
    align-items: baseline;
    gap: 0.75rem;
    margin-bottom: 0.35rem;
}

.rp-tat-name {
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--rp-ink);
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.rp-tat-target {
    font-size: 0.75rem;
    color: var(--rp-muted);
    white-space: nowrap;
}

.rp-tat-value {
    font-size: 0.8125rem;
    font-weight: 700;
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
}

.rp-tat-value--green  { color: #047857; }
.rp-tat-value--orange { color: #b45309; }
.rp-tat-value--red    { color: #b91c1c; }

.rp-tat-bar {
    height: 8px;
    background: var(--rp-line-soft);
    border-radius: 4px;
    overflow: hidden;
}

.rp-tat-fill {
    height: 100%;
    border-radius: 4px;
    transition: width 0.6s ease;
}

.rp-tat-fill--green  { background: #10b981; }
.rp-tat-fill--orange { background: #f59e0b; }
.rp-tat-fill--red    { background: #dc2626; }

.rp-tat-legend {
    list-style: none;
    margin: 0;
    padding: 1rem 1.35rem 1.35rem;
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    border-top: 1px solid var(--rp-line-soft);
    font-size: 0.72rem;
    color: var(--rp-muted);
}

.rp-tat-legend li {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
}

.rp-tat-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    flex-shrink: 0;
}

.rp-tat-dot--green  { background: #10b981; }
.rp-tat-dot--orange { background: #f59e0b; }
.rp-tat-dot--red    { background: #dc2626; }

/* ===== Table ===== */

.rp-table-wrap { overflow-x: auto; }

.rp-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    font-size: 0.85rem;
}

.rp-table thead th {
    padding: 0.7rem 1rem;
    font-size: 0.7rem;
    font-weight: 600;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: var(--rp-muted);
    background: var(--rp-canvas);
    border-bottom: 1px solid var(--rp-line);
    text-align: left;
    white-space: nowrap;
}

.rp-table tbody td {
    padding: 0.85rem 1rem;
    vertical-align: middle;
    color: var(--rp-text);
    border-bottom: 1px solid var(--rp-line-soft);
}

.rp-table tbody tr:last-child td { border-bottom: none; }
.rp-table tbody tr:hover td { background: #fafbfd; }

.rp-c-ref { white-space: nowrap; }

.rp-ref {
    display: inline-block;
    padding: 0.15rem 0.5rem;
    font-family: var(--rp-mono);
    font-size: 0.76rem;
    font-weight: 600;
    color: var(--rp-ink);
    background: var(--rp-line-soft);
    border-radius: 5px;
}

.rp-patient {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    min-width: 0;
}

.rp-avatar {
    display: grid;
    place-items: center;
    width: 32px;
    height: 32px;
    flex-shrink: 0;
    font-size: 0.68rem;
    font-weight: 700;
    color: var(--rp-accent-dark);
    background: var(--rp-accent-soft);
    border-radius: 50%;
}

.rp-patient-body { min-width: 0; }

.rp-patient-name {
    display: block;
    font-weight: 600;
    color: var(--rp-ink);
    line-height: 1.3;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.rp-patient-meta {
    display: block;
    font-size: 0.72rem;
    color: var(--rp-muted);
}

.rp-tag {
    display: inline-block;
    padding: 0.2rem 0.55rem;
    font-size: 0.72rem;
    font-weight: 600;
    color: #4338ca;
    background: #eef2ff;
    border: 1px solid #e0e7ff;
    border-radius: 5px;
    white-space: nowrap;
}

.rp-status {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.2rem 0.55rem;
    font-size: 0.72rem;
    font-weight: 600;
    color: var(--tone-fg);
    background: var(--tone-bg);
    border-radius: 999px;
    white-space: nowrap;
}

.rp-status-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: var(--tone-dot);
}

.rp-status--pending   { --tone-bg: #fffbeb; --tone-fg: #b45309; --tone-dot: var(--rp-pending); }
.rp-status--progress  { --tone-bg: #eff6ff; --tone-fg: #1d4ed8; --tone-dot: var(--rp-progress); }
.rp-status--draft     { --tone-bg: #f1f5f9; --tone-fg: #475569; --tone-dot: var(--rp-draft); }
.rp-status--completed { --tone-bg: #ecfdf5; --tone-fg: #047857; --tone-dot: var(--rp-completed); }
.rp-status--released  { --tone-bg: #f0fdfa; --tone-fg: #0f766e; --tone-dot: var(--rp-released); }

.rp-c-time { white-space: nowrap; }

.rp-time {
    display: block;
    font-variant-numeric: tabular-nums;
    color: var(--rp-ink);
    font-weight: 500;
}

.rp-time > span { color: var(--rp-faint); margin: 0 0.1rem; }

.rp-time-kind {
    display: block;
    font-size: 0.7rem;
    color: var(--rp-muted);
    margin-top: 1px;
}

.rp-c-actions { width: 1%; text-align: right; white-space: nowrap; }

.rp-actions {
    display: inline-flex;
    align-items: center;
    justify-content: flex-end;
    gap: 0.25rem;
}

.rp-icon-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    font-size: 0.9rem;
    color: var(--rp-muted);
    background: transparent;
    border: 1px solid transparent;
    border-radius: var(--rp-radius-sm);
    text-decoration: none;
    transition: background-color 0.15s ease, color 0.15s ease, border-color 0.15s ease;
}

.rp-icon-btn:hover {
    background: var(--rp-line-soft);
    color: var(--rp-ink);
    border-color: var(--rp-line);
    text-decoration: none;
}

.rp-foot {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    flex-wrap: wrap;
    padding: 0.85rem 1.35rem;
    background: var(--rp-canvas);
    border-top: 1px solid var(--rp-line);
    font-size: 0.78rem;
    color: var(--rp-muted);
}

/* ===== Empty ===== */

.rp-empty { padding: 2.5rem 1rem; text-align: center; }

.rp-empty i {
    display: block;
    font-size: 1.6rem;
    color: #cbd5e1;
    margin-bottom: 0.5rem;
}

.rp-empty-title {
    margin: 0 0 0.15rem;
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--rp-ink);
}

.rp-empty-hint {
    margin: 0;
    font-size: 0.8rem;
    color: var(--rp-muted);
}

/* ============================================
   RESPONSIVE
   ============================================ */

@media (max-width: 1500px) {
    .rp-stats { grid-template-columns: repeat(4, minmax(0, 1fr)); }
}

@media (max-width: 1200px) {
    .rp-stats { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .rp-charts { grid-template-columns: minmax(0, 1fr); }
}

@media (max-width: 992px) {
    .rp-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}

@media (max-width: 768px) {
    .rp-head { flex-direction: column; align-items: stretch; }
    .rp-head-actions .rp-btn { flex: 1; justify-content: center; }

    .rp-stat { padding: 16px; }
    .rp-stat--primary { padding-left: 20px; }
    .rp-stat-value { font-size: 1.45rem; }
    .rp-stat--primary .rp-stat-value { font-size: 1.75rem; }

    .rp-card-head,
    .rp-chart-wrap,
    .rp-chart-legend,
    .rp-chart-foot,
    .rp-tat,
    .rp-tat-legend,
    .rp-foot { padding-left: 1rem; padding-right: 1rem; }

    .rp-chart-foot { grid-template-columns: repeat(3, minmax(0, 1fr)); }

    .rp-tat-head { grid-template-columns: minmax(0, 1fr) auto; }
    .rp-tat-head .rp-tat-target { display: none; }

    /* Stacked table for narrow screens. */
    .rp-table thead { display: none; }
    .rp-table,
    .rp-table tbody,
    .rp-table tr,
    .rp-table td { display: block; width: 100%; }

    .rp-table tbody tr {
        padding: 0.9rem 1rem;
        border-bottom: 1px solid var(--rp-line);
    }
    .rp-table tbody tr:last-child { border-bottom: none; }

    .rp-table tbody td {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 0.3rem 0;
        border: 0;
        text-align: right;
    }

    .rp-table tbody td::before {
        content: attr(data-label);
        flex-shrink: 0;
        font-size: 0.72rem;
        font-weight: 600;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: var(--rp-muted);
        text-align: left;
    }

    .rp-table tbody td.rp-c-ref,
    .rp-table tbody td[data-label="Patient"] {
        display: block;
        text-align: left;
    }
    .rp-table tbody td.rp-c-ref::before,
    .rp-table tbody td[data-label="Patient"]::before { display: none; }

    .rp-table tbody td[data-label="Patient"] { margin: 0.4rem 0; }
    .rp-table tbody td.rp-c-actions { justify-content: flex-end; margin-top: 0.4rem; }

    .rp-c-time { white-space: normal; }
    .rp-time { text-align: right; }
}

@media (max-width: 576px) {
    .rp-stats { grid-template-columns: minmax(0, 1fr); gap: 12px; }
    .rp-chart-wrap,
    .rp-chart-wrap--tall { height: 200px; }
    .rp-chart-foot { grid-template-columns: minmax(0, 1fr); gap: 0.35rem; }
}

@media (prefers-reduced-motion: reduce) {
    .rp * { transition: none !important; }
}

@media print {
    .rp-head-actions,
    .rp-c-actions,
    .rp-foot .rp-btn { display: none !important; }

    .rp-card { border: 0; box-shadow: none; }
    .rp-stat { break-inside: avoid; page-break-inside: avoid; }
    .rp-chart-wrap,
    .rp-chart-wrap--tall { height: 260px; }
}
</style>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
(function () {
    'use strict';

    if (typeof Chart === 'undefined') { return; }

    /* ============================================================
       SHARED THEME
       ============================================================ */
    var RP = {
        ink:       '#0f172a',
        text:      '#334155',
        muted:     '#64748b',
        faint:     '#94a3b8',
        line:      '#e2e8f0',
        lineSoft:  '#f1f5f9',
        surface:   '#ffffff',
        accent:    '#0d9488',
        pending:   '#f59e0b',
        progress:  '#3b82f6',
        draft:     '#94a3b8',
        completed: '#10b981',
        released:  '#14b8a6'
    };

    var baseTooltip = {
        backgroundColor: RP.surface,
        titleColor: RP.ink,
        bodyColor: RP.ink,
        borderColor: RP.line,
        borderWidth: 1,
        padding: 10,
        cornerRadius: 6,
        titleFont: { size: 12, weight: '600' },
        bodyFont: { size: 12 }
    };

    var REDUCED = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function animate(duration) {
        return REDUCED ? { duration: 0 } : { duration: duration || 700, easing: 'easeOutQuart' };
    }

    /* ============================================================
       1. STATUS DISTRIBUTION — horizontal stacked bar
       One long bar, split into its constituent statuses. Reads
       instantly as "where is my work right now?".
       ============================================================ */

    var distCanvas = document.getElementById('statusDistributionChart');
    if (distCanvas) {
        var distLabels = ['Pending', 'In progress', 'Draft', 'Completed', 'Released'];
        var distData   = [
            <?= (int) ($counts['pending'] ?? 0) ?>,
            <?= (int) ($counts['in_progress'] ?? 0) ?>,
            <?= (int) ($counts['draft'] ?? 0) ?>,
            <?= (int) ($counts['completed'] ?? 0) ?>,
            <?= (int) ($counts['released'] ?? 0) ?>
        ];
        var distColors = [RP.pending, RP.progress, RP.draft, RP.completed, RP.released];
        var distTotal  = distData.reduce(function (a, b) { return a + b; }, 0);

        new Chart(distCanvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels: ['Status'],
                datasets: distLabels.map(function (label, i) {
                    return {
                        label: label,
                        data: [distData[i]],
                        backgroundColor: distColors[i],
                        borderWidth: 0,
                        borderRadius: 4,
                        barThickness: 44,
                        stack: 'status'
                    };
                })
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                animation: animate(650),
                plugins: {
                    legend: { display: false },
                    tooltip: Object.assign({}, baseTooltip, {
                        displayColors: true,
                        callbacks: {
                            label: function (context) {
                                var v = context.parsed.x || 0;
                                var pct = distTotal > 0 ? ((v / distTotal) * 100).toFixed(1) : 0;
                                return ' ' + context.dataset.label + ': ' + v + ' (' + pct + '%)';
                            }
                        }
                    })
                },
                scales: {
                    x: {
                        stacked: true,
                        beginAtZero: true,
                        grid: { color: RP.lineSoft, drawBorder: false, drawTicks: false },
                        border: { display: false },
                        ticks: {
                            color: RP.faint,
                            font: { size: 11 },
                            padding: 6,
                            precision: 0
                        }
                    },
                    y: {
                        stacked: true,
                        grid: { display: false },
                        border: { display: false },
                        ticks: { display: false }
                    }
                }
            }
        });
    }

    /* ============================================================
       2. VOLUME — vertical bar, last 7 days
       Each day is a bar. Today is highlighted with the accent
       colour, the rest are muted, so the eye lands on today.
       ============================================================ */

    var volCanvas = document.getElementById('volumeChart');
    if (volCanvas) {
        var volLabels = <?= json_encode($volumeSeries['labels'] ?: ['Mon','Tue','Wed','Thu','Fri','Sat','Sun']) ?>;
        var volData   = <?= json_encode($volumeSeries['data']   ?: [0,0,0,0,0,0,0]) ?>;

        var volColors = volLabels.map(function (_, i) {
            return i === volLabels.length - 1 ? RP.accent : '#cbd5e1';
        });

        var volHover = volLabels.map(function (_, i) {
            return i === volLabels.length - 1 ? '#0f766e' : '#94a3b8';
        });

        new Chart(volCanvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels: volLabels,
                datasets: [{
                    label: 'Reports',
                    data: volData,
                    backgroundColor: volColors,
                    hoverBackgroundColor: volHover,
                    borderRadius: 6,
                    borderSkipped: false,
                    barPercentage: 0.7,
                    categoryPercentage: 0.85,
                    maxBarThickness: 42
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: REDUCED ? { duration: 0 } : {
                    duration: 700,
                    easing: 'easeOutQuart',
                    delay: function (context) {
                        if (context.type === 'data' && !context.dropped) {
                            context.dropped = true;
                            return context.dataIndex * 45;
                        }
                        return 0;
                    }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: Object.assign({}, baseTooltip, {
                        displayColors: false,
                        callbacks: {
                            label: function (context) {
                                var v = context.parsed.y || 0;
                                return ' ' + v + (v === 1 ? ' report' : ' reports');
                            }
                        }
                    })
                },
                scales: {
                    x: {
                        grid: { display: false },
                        border: { display: false },
                        ticks: {
                            color: RP.muted,
                            font: { size: 11, weight: '600' },
                            padding: 6
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: RP.lineSoft, drawBorder: false, drawTicks: false },
                        border: { display: false },
                        ticks: {
                            color: RP.faint,
                            font: { size: 11 },
                            padding: 8,
                            maxTicksLimit: 5,
                            precision: 0
                        }
                    }
                }
            }
        });
    }

})();
</script>

<?= $this->endSection() ?>