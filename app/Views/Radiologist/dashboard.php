<?= $this->extend('layouts/RadiologistLayout') ?>

<?= $this->section('pageTitle') ?>Radiologist Dashboard<?= $this->endSection() ?>

<?= $this->section('radiologistContent') ?>

<?php
/* ------------------------------------------------------------------
   Radiologist Dashboard

   Layout:
     1. Seven KPI stat cards
     2. Weekly Study Volume chart (left) + Available X-ray Services
        scrollable catalogue (right)
     3. Two donut cards side by side — Status Breakdown (left) and
        Services by Region (right). Each donut has its legend on the
        right side of the canvas.
     4. Reading Worklist table

   All values come from the existing controller:
     $pending, $processing, $completed, $released, $total,
     $today_revenue, $monthly_revenue, $pending_examinations,
     $weekly_data, $servicesCatalog
   ------------------------------------------------------------------ */

$pending    = (int) ($pending ?? 0);
$processing = (int) ($processing ?? 0);
$completed  = (int) ($completed ?? 0);
$released   = (int) ($released ?? 0);
$total      = (int) ($total ?? 0);

$todayRevenue   = (float) ($today_revenue ?? 0);
$monthlyRevenue = (float) ($monthly_revenue ?? 0);

$recentExaminations  = (isset($recent_examinations)  && is_array($recent_examinations))  ? $recent_examinations  : [];
$pendingExaminations = (isset($pending_examinations) && is_array($pending_examinations)) ? $pending_examinations : [];

$weeklyData     = (isset($weekly_data) && is_array($weekly_data)) ? $weekly_data : [];
$weeklyLabels   = (isset($weeklyData['labels'])   && is_array($weeklyData['labels']))   ? $weeklyData['labels']   : [];
$weeklyDatasets = (isset($weeklyData['datasets']) && is_array($weeklyData['datasets'])) ? $weeklyData['datasets'] : [];

$hasWeeklyChart = !empty($weeklyLabels) && !empty($weeklyDatasets);

/* X-ray services catalogue — supplied by Radiologist::getAvailableXrayServices(). */
$servicesCatalog = (isset($servicesCatalog) && is_array($servicesCatalog)) ? $servicesCatalog : [
    'services'       => [],
    'count'          => 0,
    'categoryCounts' => [],
];

$xrayServices   = $servicesCatalog['services'];
$xrayCount      = (int) $servicesCatalog['count'];
$categoryCounts = $servicesCatalog['categoryCounts'];

$slug = static function ($value, $fallback = 'unknown') {
    $value = preg_replace('/[^a-z0-9_-]/', '', strtolower(trim((string) $value)));
    return $value !== '' ? $value : $fallback;
};

$initialsOf = static function ($name) {
    $parts = preg_split('/\s+/', trim((string) $name));
    $first = mb_substr($parts[0] ?? '', 0, 1);
    $last  = count($parts) > 1 ? mb_substr(end($parts), 0, 1) : '';
    return mb_strtoupper($first . $last);
};

$peso = static function ($amount) {
    $amount = (float) $amount;
    $decimals = (floor($amount) == $amount) ? 0 : 2;
    return '₱' . number_format($amount, $decimals);
};

$statusMeta = [
    'pending'     => ['label' => 'Pending',    'tone' => 'pending'],
    'in_progress' => ['label' => 'In reading', 'tone' => 'progress'],
    'processing'  => ['label' => 'In reading', 'tone' => 'progress'],
    'completed'   => ['label' => 'Completed',  'tone' => 'completed'],
    'released'    => ['label' => 'Released',   'tone' => 'released'],
];

$statusCounts = [];
foreach ($pendingExaminations as $exam) {
    $st = $slug($exam['status'] ?? '', 'unknown');
    $statusCounts[$st] = ($statusCounts[$st] ?? 0) + 1;
}

$statCards = [
    [
        'key'   => 'pending',
        'icon'  => 'time.png',
        'tone'  => 'icon-orange',
        'label' => 'Pending Read',
        'value' => number_format($pending),
        'note'  => 'Awaiting interpretation',
        'link'  => base_url('radiologist/examinations'),
    ],
    [
        'key'   => 'processing',
        'icon'  => 'file (1).png',
        'tone'  => 'icon-blue',
        'label' => 'In Reading',
        'value' => number_format($processing),
        'note'  => 'Currently open',
        'link'  => base_url('radiologist/examinations'),
    ],
    [
        'key'   => 'completed',
        'icon'  => 'people-check-blue.png',
        'tone'  => 'icon-green',
        'label' => 'Completed',
        'value' => number_format($completed),
        'note'  => 'Report signed',
        'link'  => base_url('radiologist/examinations'),
    ],
    [
        'key'   => 'released',
        'icon'  => 'check.png',
        'tone'  => 'icon-teal',
        'label' => 'Released',
        'value' => number_format($released),
        'note'  => 'Sent to reception',
        'link'  => base_url('radiologist/examinations'),
    ],
    [
        'key'   => 'total',
        'icon'  => 'analysis.png',
        'tone'  => 'icon-cyan',
        'label' => 'Total Studies',
        'value' => number_format($total),
        'note'  => 'All X-ray examinations',
        'link'  => null,
    ],
    [
        'key'   => 'revenue_today',
        'icon'  => 'money-yellow.png',
        'tone'  => 'icon-cyan',
        'label' => 'Revenue Today',
        'value' => $peso($todayRevenue),
        'note'  => 'Paid X-ray studies today',
        'raw'   => true,
        'link'  => null,
    ],
    [
        'key'   => 'revenue_month',
        'icon'  => 'money-yellow.png',
        'tone'  => 'icon-cyan',
        'label' => 'Revenue This Month',
        'value' => $peso($monthlyRevenue),
        'note'  => 'Paid X-ray studies month to date',
        'raw'   => true,
        'link'  => null,
    ],
];
?>

<div class="dashboard-container">

    <!-- ===== STATS ROW ===== -->
    <div class="stats-row">
        <?php foreach ($statCards as $card): ?>
            <?php
                $tag  = !empty($card['link']) ? 'a' : 'div';
                $href = !empty($card['link']) ? ' href="' . esc($card['link'], 'attr') . '"' : '';
                $icon = (string) ($card['icon'] ?? '');
                $isMoney = !empty($card['raw']);
            ?>
            <<?= $tag ?> class="stat-card<?= $isMoney ? ' stat-card--revenue' : '' ?>"<?= $href ?>>

                <div class="stat-top">
                    <div class="stat-icon <?= esc($card['tone'], 'attr') ?>">
                        <?php if ($icon !== ''): ?>
                            <img src="<?= esc(base_url('assets/images/' . $icon), 'attr') ?>"
                                 alt=""
                                 class="stat-img"
                                 loading="lazy"
                                 decoding="async">
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($card['link'])): ?>
                        <svg class="stat-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" stroke-linecap="round"
                             stroke-linejoin="round" aria-hidden="true">
                            <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                    <?php endif; ?>
                </div>

                <div class="stat-value<?= $isMoney ? ' stat-value-money' : '' ?>">
                    <?= esc($card['value']) ?>
                </div>

                <div class="stat-label"><?= esc($card['label']) ?></div>

                <div class="stat-sub"><?= esc($card['note']) ?></div>

            </<?= $tag ?>>
        <?php endforeach; ?>
    </div>


    <!-- ===== TOP ROW: Weekly Volume + Available X-ray Services ===== -->
    <div class="charts-row charts-row--top">

        <div class="chart-card">
            <div class="chart-header">
                <div class="chart-heading">
                    <h5>Weekly Study Volume</h5>
                    <small>Stacked counts by modality</small>
                </div>
                <?php if ($hasWeeklyChart): ?>
                    <span class="chart-pill">
                        <span class="pill-dot" style="background: #2450d8;"></span>
                        <?= number_format(count($weeklyLabels)) ?> days
                    </span>
                <?php endif; ?>
            </div>

            <?php if ($hasWeeklyChart): ?>
                <div class="chart-body">
                    <canvas id="weeklyVolumeChart"></canvas>
                </div>
            <?php else: ?>
                <div class="chart-body chart-empty">
                    <i class="bi bi-bar-chart" aria-hidden="true"></i>
                    <p>No volume data for this week</p>
                    <small>Once examinations are scheduled, their counts appear here.</small>
                </div>
            <?php endif; ?>
        </div>

        <div class="chart-card chart-card--services">
            <div class="chart-header">
                <div class="chart-heading">
                    <h5>Available X-ray Services</h5>
                    <small>Active catalogue (<?= number_format($xrayCount) ?>)</small>
                </div>
            </div>

            <?php if (!empty($xrayServices)): ?>
                <ul class="service-list" aria-label="Active X-ray services">
                    <?php foreach ($xrayServices as $service): ?>
                        <li class="service-list-item">
                            <span class="service-list-name"><?= esc($service['service_name'] ?? '') ?></span>
                            <span class="service-list-price">
                                <?= esc($peso((float) ($service['charge'] ?? 0))) ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="service-list-empty">No X-ray services are currently active.</p>
            <?php endif; ?>
        </div>
    </div>


    <!-- ===== MIDDLE ROW: Two donuts side by side ===== -->
    <div class="charts-row charts-row--donuts">

        <!-- Status breakdown -->
        <div class="chart-card">
            <div class="chart-header">
                <div class="chart-heading">
                    <h5>Status Breakdown</h5>
                    <small>Current pipeline</small>
                </div>
            </div>

            <?php if ($total > 0): ?>
                <?php
                $donutRaw = [
                    'Pending'    => $pending,
                    'In reading' => $processing,
                    'Completed'  => $completed,
                    'Released'   => $released,
                ];
                $donutData = array_filter($donutRaw, static fn ($n) => $n > 0);
                arsort($donutData);
                $donutTotal = array_sum($donutData);
                $donutPalette = ['#2450d8', '#f0b429', '#0f9d76', '#0e7490', '#d9534f', '#7b8794'];
                ?>

                <?php if ($donutTotal > 0): ?>
                    <div class="donut-wrap">
                        <div class="donut-canvas">
                            <canvas id="statusChart"></canvas>
                            <div class="donut-center">
                                <span class="donut-value"><?= number_format($donutTotal) ?></span>
                                <span class="donut-label">Studies</span>
                            </div>
                        </div>

                        <ul class="donut-legend">
                            <?php $legendIndex = 0; ?>
                            <?php foreach ($donutData as $label => $count): ?>
                                <?php
                                    $pct = $donutTotal > 0 ? ($count / $donutTotal) * 100 : 0;
                                    $color = $donutPalette[$legendIndex % count($donutPalette)];
                                    $legendIndex++;
                                ?>
                                <li class="legend-item">
                                    <span class="legend-dot" style="background: <?= esc($color, 'attr') ?>;"></span>
                                    <span class="legend-name"><?= esc($label) ?></span>
                                    <span class="legend-pct"><?= number_format($pct, $pct < 10 ? 1 : 0) ?>%</span>
                                    <span class="legend-count"><?= number_format($count) ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php else: ?>
                    <div class="chart-body chart-empty">
                        <i class="bi bi-pie-chart" aria-hidden="true"></i>
                        <p>No active studies</p>
                        <small>The breakdown appears once examinations exist.</small>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="chart-body chart-empty">
                    <i class="bi bi-pie-chart" aria-hidden="true"></i>
                    <p>No studies recorded</p>
                    <small>Examination counts appear here once the pipeline has entries.</small>
                </div>
            <?php endif; ?>
        </div>

        <!-- Services breakdown by clinical region -->
        <div class="chart-card">
            <div class="chart-header">
                <div class="chart-heading">
                    <h5>Services by Region</h5>
                    <small>Catalogue split across clinical areas</small>
                </div>
            </div>

            <?php if (!empty($categoryCounts)): ?>
                <?php
                $svcData = $categoryCounts;
                arsort($svcData);
                $svcTotal = array_sum($svcData);
                $svcPalette = ['#2450d8', '#0f9d76', '#f0b429', '#0e7490', '#d9534f', '#7b8794', '#6d28d9'];
                ?>
                <div class="donut-wrap">
                    <div class="donut-canvas">
                        <canvas id="servicesChart"></canvas>
                        <div class="donut-center">
                            <span class="donut-value"><?= number_format($svcTotal) ?></span>
                            <span class="donut-label">Services</span>
                        </div>
                    </div>

                    <ul class="donut-legend">
                        <?php $si = 0; ?>
                        <?php foreach ($svcData as $cat => $count): ?>
                            <?php
                                $pct = $svcTotal > 0 ? ($count / $svcTotal) * 100 : 0;
                                $color = $svcPalette[$si % count($svcPalette)];
                                $si++;
                            ?>
                            <li class="legend-item">
                                <span class="legend-dot" style="background: <?= esc($color, 'attr') ?>;"></span>
                                <span class="legend-name"><?= esc($cat) ?></span>
                                <span class="legend-pct"><?= number_format($pct, $pct < 10 ? 1 : 0) ?>%</span>
                                <span class="legend-count"><?= number_format($count) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php else: ?>
                <div class="chart-body chart-empty">
                    <i class="bi bi-pie-chart" aria-hidden="true"></i>
                    <p>No services on file</p>
                    <small>Add X-ray services to see their distribution here.</small>
                </div>
            <?php endif; ?>
        </div>
    </div>


    <!-- ===== READING WORKLIST ===== -->
    <div class="appointments-card">

        <div class="card-header-custom">
            <div class="chart-heading">
                <h5><i class="bi bi-list-check" aria-hidden="true"></i> Reading Worklist</h5>
                <small>
                    <?php if (count($pendingExaminations) > 0): ?>
                        <?= number_format($statusCounts['pending'] ?? 0) ?> pending
                        &middot;
                        <?= number_format($statusCounts['in_progress'] ?? 0) ?> in reading
                        &middot;
                        <?= number_format($statusCounts['completed'] ?? 0) ?> completed
                    <?php else: ?>
                        Nothing waiting
                    <?php endif; ?>
                </small>
            </div>

            <div class="header-right-group">
                <span class="badge-custom"><?= date('F d, Y') ?></span>
                <a href="<?= base_url('radiologist/examinations') ?>" class="btn-view-all">
                    View All <i class="bi bi-arrow-right" aria-hidden="true"></i>
                </a>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table receptionist-table" id="queueTable">
                <thead>
                    <tr>
                        <th scope="col">Accession</th>
                        <th scope="col">Patient</th>
                        <th scope="col">Exam</th>
                        <th scope="col">Referred by</th>
                        <th scope="col">Priority</th>
                        <th scope="col">Date</th>
                        <th scope="col">Status</th>
                        <th scope="col" class="col-action">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($pendingExaminations) > 0): ?>
                        <?php foreach ($pendingExaminations as $exam): ?>
                            <?php
                                $examId    = (int) ($exam['id'] ?? 0);
                                $statusKey = $slug($exam['status'] ?? '', 'pending');
                                $meta      = $statusMeta[$exam['status'] ?? ''] ?? ['label' => ucfirst($statusKey), 'tone' => 'pending'];
                                $fullName  = (string) ($exam['patient_name'] ?? 'Unknown');
                                $examType  = trim((string) ($exam['exam_type'] ?? ''));
                                $doctor    = trim((string) ($exam['doctor_name'] ?? ''));
                                $isStat    = strtolower((string) ($exam['priority'] ?? '')) === 'stat';
                                $dateTs    = !empty($exam['exam_date']) ? strtotime($exam['exam_date']) : false;
                            ?>
                            <tr class="queue-row"
                                data-status="<?= esc($statusKey, 'attr') ?>"
                                data-stat="<?= $isStat ? '1' : '0' ?>">

                                <td data-label="Accession" class="accession-cell">
                                    XR-<?= esc(date('y')) ?>-<?= esc(str_pad((string) $examId, 4, '0', STR_PAD_LEFT)) ?>
                                </td>

                                <td data-label="Patient">
                                    <div class="patient-cell">
                                        <span class="patient-avatar" aria-hidden="true"><?= esc($initialsOf($fullName)) ?></span>
                                        <div class="patient-meta">
                                            <span class="patient-name"><?= esc($fullName !== '' ? $fullName : 'Unknown') ?></span>
                                            <small>
                                                <?= esc(($exam['age'] ?? '') !== '' ? $exam['age'] . ' yrs' : 'Age n/a') ?>
                                                &middot;
                                                <?= esc(($exam['gender'] ?? '') !== '' ? $exam['gender'] : 'n/a') ?>
                                            </small>
                                        </div>
                                    </div>
                                </td>

                                <td data-label="Exam">
                                    <span class="service-tag"><?= esc($examType !== '' ? $examType : '—') ?></span>
                                </td>

                                <td data-label="Referred by">
                                    <?= $doctor !== '' ? esc($doctor) : '<span class="muted">&mdash;</span>' ?>
                                </td>

                                <td data-label="Priority">
                                    <?php if ($isStat): ?>
                                        <span class="status-badge stat">STAT</span>
                                    <?php else: ?>
                                        <span class="muted">Routine</span>
                                    <?php endif; ?>
                                </td>

                                <td data-label="Date">
                                    <?= $dateTs ? esc(date('M j, Y', $dateTs)) : '&mdash;' ?>
                                </td>

                                <td data-label="Status">
                                    <span class="status-badge <?= esc($meta['tone'], 'attr') ?>">
                                        <?= esc($meta['label']) ?>
                                    </span>
                                </td>

                                <td data-label="Action" class="action-cell">
                                    <a href="<?= base_url('radiologist/examination/view/' . $examId) ?>"
                                       class="action-icon-btn view" title="Open study"
                                       aria-label="Open <?= esc($fullName, 'attr') ?>">
                                        <i class="bi bi-eye" aria-hidden="true"></i>
                                    </a>
                                    <a href="<?= base_url('radiologist/examination/view/' . $examId) ?>"
                                       class="action-icon-btn complete" title="Read / interpret"
                                       aria-label="Read study for <?= esc($fullName, 'attr') ?>">
                                        <i class="bi bi-pencil-square" aria-hidden="true"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8">
                                <div class="empty-state">
                                    <div class="empty-icon">
                                        <i class="bi bi-inbox" aria-hidden="true"></i>
                                    </div>
                                    <p>Nothing waiting</p>
                                    <small>Every pending study has been picked up. New requests will appear here as the receptionist creates them.</small>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>

                    <tr id="rdNoMatch" hidden>
                        <td colspan="8">
                            <div class="empty-state">
                                <div class="empty-icon">
                                    <i class="bi bi-search" aria-hidden="true"></i>
                                </div>
                                <p>No matching studies</p>
                                <small>Try a different filter, or select All to see the full worklist.</small>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <footer class="table-filter-footer">
            <div class="filter-group" role="group" aria-label="Filter worklist">
                <button type="button" class="filter-btn is-active" data-filter="all">All</button>
                <button type="button" class="filter-btn" data-filter="stat">STAT</button>
                <button type="button" class="filter-btn" data-filter="pending">Pending</button>
                <button type="button" class="filter-btn" data-filter="reading">Reading</button>
            </div>
            <button type="button" class="refresh-btn" onclick="refreshTable(event)">
                <i class="bi bi-arrow-clockwise" aria-hidden="true"></i> Refresh
            </button>
        </footer>

    </div>

</div>

<style>
/* =========================================================
   DESIGN TOKENS
   ========================================================= */

.dashboard-container {
    --db-accent:      #2450d8;
    --db-accent-dark: #1b3fb0;
    --db-accent-soft: #eef3ff;

    --db-ink:         #0b1220;
    --db-ink-soft:    #414e63;
    --db-ink-mute:    #6b7789;
    --db-ink-faint:   #9aa5b5;

    --db-line:        #e4e8ef;
    --db-line-soft:   #eef1f6;
    --db-surface:     #ffffff;
    --db-canvas:      #f4f6fa;
    --db-rail:        #f7f9fc;

    --db-amber:       #b45309;
    --db-green:       #067a55;
    --db-teal:        #0f766e;
    --db-cyan:        #0e7490;
    --db-red:         #c2323b;

    --db-r-lg:        14px;
    --db-r-md:        10px;
    --db-r-sm:        7px;
    --db-r-xs:        5px;

    --db-mono:        ui-monospace, SFMono-Regular, "SF Mono", Menlo, Consolas, monospace;

    padding: 1.5rem;
    background: var(--db-canvas);
    min-height: 100vh;
    color: var(--db-ink);
    -webkit-font-smoothing: antialiased;
}

.dashboard-container :focus-visible {
    outline: 2px solid var(--db-accent);
    outline-offset: 2px;
    border-radius: var(--db-r-xs);
}

.dashboard-container .muted { color: var(--db-ink-faint); }

/* =========================================================
   STATS ROW
   ========================================================= */

.dashboard-container .stats-row {
    display: grid;
    grid-template-columns: repeat(7, minmax(0, 1fr));
    gap: 16px;
    margin-bottom: 20px;
}

.dashboard-container .stat-card {
    display: block;
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 12px;
    padding: 18px;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    text-decoration: none;
    color: inherit;
    transition: border-color 0.18s ease, box-shadow 0.18s ease, background-color 0.18s ease;
    min-width: 0;
    width: 100%;
    box-sizing: border-box;
}

.dashboard-container a.stat-card:hover,
.dashboard-container a.stat-card:focus-visible {
    border-color: #CBD5E1;
    box-shadow: 0 6px 16px rgba(15, 23, 42, 0.07);
    text-decoration: none;
    color: inherit;
}

.dashboard-container .stat-card:hover .stat-arrow { color: #475569; }

.dashboard-container .stat-top {
    display: grid;
    grid-template-columns: 40px 1fr 16px;
    align-items: center;
    margin-bottom: 14px;
    width: 100%;
}

.dashboard-container .stat-top > .stat-icon  { grid-column: 1; }
.dashboard-container .stat-top > .stat-arrow { grid-column: 3; justify-self: end; }

.dashboard-container .stat-top:not(:has(.stat-arrow)) {
    grid-template-columns: 40px 1fr;
}

.dashboard-container .stat-icon {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.dashboard-container .stat-img {
    width: 25px;
    height: 25px;
    object-fit: contain;
    display: block;
}

.dashboard-container .stat-arrow {
    color: #CBD5E1;
    flex-shrink: 0;
    transition: color 0.18s ease;
    justify-self: end;
}

.dashboard-container .icon-cyan,
.dashboard-container .icon-teal,
.dashboard-container .icon-orange,
.dashboard-container .icon-green,
.dashboard-container .icon-blue,
.dashboard-container .icon-yellow,
.dashboard-container .icon-pink {
    background: transparent;
}

.dashboard-container .stat-value {
    font-size: 1.65rem;
    font-weight: 700;
    color: #0F172A;
    line-height: 1.15;
    margin-bottom: 6px;
    letter-spacing: -0.02em;
    font-variant-numeric: tabular-nums;
}

.dashboard-container .stat-value-money {
    font-size: 1.35rem;
    overflow-wrap: anywhere;
}

.dashboard-container .stat-card--revenue {
    border-color: #d7e3fb;
    background: linear-gradient(180deg, #fbfdff 0%, #ffffff 100%);
}

.dashboard-container .stat-card--revenue .stat-label { color: #1e3a8a; }

.dashboard-container .stat-label {
    font-size: 0.875rem;
    font-weight: 600;
    color: #475569;
    margin-bottom: 3px;
}

.dashboard-container .stat-sub {
    font-size: 0.775rem;
    color: #94A3B8;
}

/* =========================================================
   CHART ROWS
   ========================================================= */

.dashboard-container .charts-row {
    display: grid;
    gap: 0.9rem;
    margin-bottom: 1.1rem;
}

.dashboard-container .charts-row--top {
    grid-template-columns: minmax(0, 1.6fr) minmax(0, 1fr);
}

.dashboard-container .charts-row--donuts {
    grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
}

.dashboard-container .chart-card {
    background: var(--db-surface);
    border: 1px solid var(--db-line);
    border-radius: var(--db-r-lg);
    padding: 1.15rem 1.25rem 1.25rem;
    box-shadow: 0 1px 2px rgba(11, 18, 32, 0.04),
                0 12px 28px -20px rgba(11, 18, 32, 0.18);
    min-width: 0;
}

.dashboard-container .chart-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.75rem;
    flex-wrap: wrap;
    margin-bottom: 1rem;
}

.dashboard-container .chart-heading { min-width: 0; }

.dashboard-container .chart-heading h5 {
    font-size: 0.94rem;
    font-weight: 700;
    letter-spacing: -0.015em;
    color: var(--db-ink);
    margin: 0 0 0.15rem;
    display: flex;
    align-items: center;
    gap: 0.45rem;
}

.dashboard-container .chart-heading h5 i { color: var(--db-accent); font-size: 0.9rem; }

.dashboard-container .chart-heading small {
    font-size: 0.75rem;
    color: var(--db-ink-mute);
}

.dashboard-container .chart-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.735rem;
    font-weight: 600;
    color: var(--db-ink-soft);
    background: var(--db-rail);
    border: 1px solid var(--db-line);
    border-radius: var(--db-r-sm);
    padding: 0.28rem 0.55rem;
    white-space: nowrap;
    font-variant-numeric: tabular-nums;
}

.dashboard-container .pill-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    flex-shrink: 0;
}

.dashboard-container .chart-body {
    height: 280px;
    position: relative;
}

.dashboard-container .chart-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    background: var(--db-rail);
    border: 1px dashed var(--db-line);
    border-radius: var(--db-r-md);
    color: var(--db-ink-mute);
    padding: 1rem;
}

.dashboard-container .chart-empty i { font-size: 1.5rem; color: var(--db-ink-faint); margin-bottom: 0.5rem; }
.dashboard-container .chart-empty p { margin: 0 0 0.2rem; font-weight: 600; font-size: 0.87rem; color: var(--db-ink); }
.dashboard-container .chart-empty small { font-size: 0.74rem; color: var(--db-ink-faint); }

/* =========================================================
   AVAILABLE X-RAY SERVICES — scrollable catalogue
   ========================================================= */

.dashboard-container .chart-card--services {
    display: flex;
    flex-direction: column;
}

.dashboard-container .service-list {
    list-style: none;
    margin: 0;
    padding: 0;
    max-height: 280px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 0.2rem;

    scrollbar-width: thin;
    scrollbar-color: #cbd5e1 transparent;
}

.dashboard-container .service-list::-webkit-scrollbar { width: 6px; }
.dashboard-container .service-list::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
}
.dashboard-container .service-list::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
.dashboard-container .service-list::-webkit-scrollbar-track { background: transparent; }

.dashboard-container .service-list-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    padding: 0.5rem 0.65rem;
    border-radius: var(--db-r-sm);
    background: var(--db-rail);
    min-height: 34px;
    transition: background-color 0.15s ease;
}

.dashboard-container .service-list-item:hover { background: #eef2f7; }

.dashboard-container .service-list-name {
    font-size: 0.8125rem;
    color: var(--db-ink);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    min-width: 0;
}

.dashboard-container .service-list-price {
    font-size: 0.78rem;
    font-weight: 600;
    color: var(--db-ink-soft);
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
    flex-shrink: 0;
}

.dashboard-container .service-list-empty {
    margin: 0;
    padding: 1rem 0.25rem;
    font-size: 0.8125rem;
    color: var(--db-ink-mute);
    text-align: center;
}

/* =========================================================
   DONUT + LEGEND
   Donut on the left, legend on the right. The grid gives the
   donut a fixed slot so it stays perfectly square, and the
   legend takes the remaining width.
   ========================================================= */

.dashboard-container .charts-row--donuts .chart-card {
    padding: 0.9rem 1rem 1rem;
}

.dashboard-container .charts-row--donuts .chart-header {
    margin-bottom: 0.6rem;
}

.dashboard-container .donut-wrap {
    display: grid;
    grid-template-columns: 150px minmax(0, 1fr);
    align-items: center;
    gap: 1.1rem;
}

.dashboard-container .donut-canvas {
    position: relative;
    height: 140px;
    flex-shrink: 0;
}

.dashboard-container .donut-center {
    position: absolute;
    inset: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    pointer-events: none;
    text-align: center;
}

.dashboard-container .donut-value {
    font-size: 1.35rem;
    font-weight: 800;
    letter-spacing: -0.04em;
    line-height: 1;
    color: var(--db-ink);
    font-variant-numeric: tabular-nums;
}

.dashboard-container .donut-label {
    font-size: 0.62rem;
    font-weight: 500;
    color: var(--db-ink-mute);
    margin-top: 0.1rem;
}

.dashboard-container .donut-legend {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 0;
}

.dashboard-container .legend-item {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    padding: 0.2rem 0.35rem;
    border-radius: var(--db-r-sm);
    font-size: 0.72rem;
    transition: background-color 0.16s ease;
}

.dashboard-container .legend-item:hover { background: var(--db-rail); }

.dashboard-container .legend-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    flex-shrink: 0;
}

.dashboard-container .legend-name {
    color: var(--db-ink-soft);
    font-weight: 500;
    flex: 1;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.dashboard-container .legend-pct {
    font-weight: 700;
    color: var(--db-ink);
    font-variant-numeric: tabular-nums;
    font-size: 0.72rem;
}

.dashboard-container .legend-count {
    font-size: 0.68rem;
    color: var(--db-ink-faint);
    font-variant-numeric: tabular-nums;
    min-width: 1.25rem;
    text-align: right;
}

/* =========================================================
   WORKLIST CARD
   ========================================================= */

.dashboard-container .appointments-card {
    background: var(--db-surface);
    border: 1px solid var(--db-line);
    border-radius: var(--db-r-lg);
    box-shadow: 0 1px 2px rgba(11, 18, 32, 0.04),
                0 12px 28px -20px rgba(11, 18, 32, 0.18);
    overflow: hidden;
}

.dashboard-container .card-header-custom {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.85rem;
    flex-wrap: wrap;
    padding: 1.15rem 1.25rem;
    border-bottom: 1px solid var(--db-line-soft);
}

.dashboard-container .header-right-group {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.dashboard-container .badge-custom {
    font-size: 0.735rem;
    font-weight: 600;
    color: var(--db-ink-soft);
    background: var(--db-rail);
    border: 1px solid var(--db-line);
    border-radius: var(--db-r-sm);
    padding: 0.34rem 0.6rem;
    white-space: nowrap;
}

.dashboard-container .btn-view-all {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.78rem;
    font-weight: 600;
    color: #ffffff;
    background: var(--db-accent);
    border: 1px solid var(--db-accent);
    border-radius: var(--db-r-sm);
    padding: 0.4rem 0.75rem;
    text-decoration: none;
    white-space: nowrap;
    box-shadow: 0 1px 2px rgba(36, 80, 216, 0.28), inset 0 1px 0 rgba(255, 255, 255, 0.12);
    transition: background-color 0.16s ease, border-color 0.16s ease;
}

.dashboard-container .btn-view-all:hover {
    background: var(--db-accent-dark);
    border-color: var(--db-accent-dark);
    color: #ffffff;
    text-decoration: none;
}

.dashboard-container .btn-view-all i { font-size: 0.75rem; }

.dashboard-container .receptionist-table {
    margin: 0;
    border-collapse: separate;
    border-spacing: 0;
}

.dashboard-container .receptionist-table thead th {
    font-size: 0.665rem;
    font-weight: 700;
    letter-spacing: 0.075em;
    text-transform: uppercase;
    color: var(--db-ink-mute);
    background: var(--db-rail);
    border-bottom: 1px solid var(--db-line);
    border-top: none;
    padding: 0.7rem 1rem;
    white-space: nowrap;
}

.dashboard-container .receptionist-table tbody td {
    padding: 0.75rem 1rem;
    vertical-align: middle;
    font-size: 0.84rem;
    color: var(--db-ink-soft);
    border-bottom: 1px solid var(--db-line-soft);
    background: transparent;
}

.dashboard-container .receptionist-table tbody tr:last-child td { border-bottom: none; }
.dashboard-container .receptionist-table tbody tr:hover td { background: var(--db-rail); }

.dashboard-container .accession-cell {
    font-family: var(--db-mono);
    font-size: 0.735rem;
    font-weight: 600;
    letter-spacing: 0.03em;
    color: var(--db-ink-faint);
    white-space: nowrap;
}

.dashboard-container .col-action { text-align: right; }

.dashboard-container .patient-cell {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    min-width: 0;
}

.dashboard-container .patient-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: var(--db-accent-soft);
    color: var(--db-accent);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
    font-weight: 700;
    flex-shrink: 0;
}

.dashboard-container .patient-meta { min-width: 0; }

.dashboard-container .patient-name {
    display: block;
    font-weight: 600;
    color: var(--db-ink);
    font-size: 0.855rem;
    line-height: 1.3;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.dashboard-container .patient-meta small {
    font-size: 0.725rem;
    color: var(--db-ink-faint);
}

.dashboard-container .service-tag {
    display: inline-block;
    font-size: 0.735rem;
    color: var(--db-ink-soft);
    background: var(--db-rail);
    border: 1px solid var(--db-line-soft);
    border-radius: var(--db-r-xs);
    padding: 0.2rem 0.45rem;
    white-space: nowrap;
}

.dashboard-container .status-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    font-size: 0.715rem;
    font-weight: 600;
    padding: 0.2rem 0.5rem;
    border-radius: var(--db-r-xs);
    border: 1px solid transparent;
    white-space: nowrap;
    line-height: 1.35;
}

.dashboard-container .status-badge::before {
    content: "";
    width: 5px;
    height: 5px;
    border-radius: 50%;
    background: currentColor;
    flex-shrink: 0;
}

.dashboard-container .status-badge.pending   { background: #fdf6ec; color: var(--db-amber); border-color: #f4e2c4; }
.dashboard-container .status-badge.progress  { background: var(--db-accent-soft); color: var(--db-accent); border-color: #d3e0fb; }
.dashboard-container .status-badge.completed { background: #eefaf4; color: var(--db-green); border-color: #c6ebda; }
.dashboard-container .status-badge.released  { background: #f0fdfa; color: var(--db-teal); border-color: #cdeae4; }
.dashboard-container .status-badge.stat      { background: #fdeef0; color: var(--db-red); border-color: #f4d2d5; }

.dashboard-container .action-cell {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 0.2rem;
}

.dashboard-container .action-icon-btn {
    width: 28px;
    height: 28px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: var(--db-r-xs);
    color: var(--db-ink-mute);
    background: transparent;
    border: 1px solid transparent;
    text-decoration: none;
    font-size: 0.82rem;
    transition: background-color 0.16s ease, color 0.16s ease, border-color 0.16s ease;
}

.dashboard-container .action-icon-btn:hover          { background: var(--db-line-soft); color: var(--db-ink); }
.dashboard-container .action-icon-btn.view:hover     { background: var(--db-accent-soft); color: var(--db-accent); border-color: #d3e0fb; }
.dashboard-container .action-icon-btn.complete:hover { background: #eefaf4; color: var(--db-green); border-color: #c6ebda; }

.dashboard-container .table-filter-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    flex-wrap: wrap;
    padding: 0.75rem 1.25rem;
    background: var(--db-rail);
    border-top: 1px solid var(--db-line-soft);
}

.dashboard-container .filter-group {
    display: inline-flex;
    align-items: center;
    gap: 0.15rem;
    padding: 3px;
    background: var(--db-surface);
    border: 1px solid var(--db-line);
    border-radius: 9px;
}

.dashboard-container .filter-btn {
    padding: 0.3rem 0.7rem;
    font-size: 0.78rem;
    font-weight: 600;
    color: var(--db-ink-mute);
    background: none;
    border: 0;
    border-radius: 6px;
    cursor: pointer;
    white-space: nowrap;
    transition: background-color 0.15s ease, color 0.15s ease, box-shadow 0.15s ease;
}

.dashboard-container .filter-btn:hover { color: var(--db-ink); }
.dashboard-container .filter-btn.is-active {
    color: var(--db-ink);
    background: var(--db-surface);
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.08);
}

.dashboard-container .refresh-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    height: 32px;
    padding: 0 0.75rem;
    font-size: 0.78rem;
    font-weight: 600;
    color: var(--db-ink-soft);
    background: var(--db-surface);
    border: 1px solid var(--db-line);
    border-radius: var(--db-r-sm);
    cursor: pointer;
    transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
}

.dashboard-container .refresh-btn:hover {
    background: var(--db-canvas);
    border-color: #cbd5e1;
    color: var(--db-ink);
}

.dashboard-container .empty-state {
    text-align: center;
    padding: 3rem 1rem;
    color: var(--db-ink-mute);
}

.dashboard-container .empty-icon {
    width: 46px;
    height: 46px;
    margin: 0 auto 0.85rem;
    border-radius: var(--db-r-md);
    background: var(--db-rail);
    border: 1px solid var(--db-line);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    color: var(--db-ink-faint);
}

.dashboard-container .empty-state p {
    font-size: 0.92rem;
    font-weight: 600;
    color: var(--db-ink);
    margin: 0 0 0.2rem;
}

.dashboard-container .empty-state small { font-size: 0.79rem; color: var(--db-ink-faint); }

/* =========================================================
   RESPONSIVE
   ========================================================= */

@media (max-width: 1500px) {
    .dashboard-container .stats-row { grid-template-columns: repeat(4, minmax(0, 1fr)); }
}

@media (max-width: 1200px) {
    .dashboard-container .stats-row { grid-template-columns: repeat(3, minmax(0, 1fr)); }
}

@media (max-width: 1100px) {
    .dashboard-container .charts-row--top,
    .dashboard-container .charts-row--donuts {
        grid-template-columns: minmax(0, 1fr);
    }

    /* Legend drops below the donut on narrow screens so the
       side-by-side grid does not get squeezed. */
    .dashboard-container .donut-wrap {
        grid-template-columns: minmax(0, 1fr);
        gap: 0.65rem;
        justify-items: center;
    }

    .dashboard-container .donut-canvas { height: 160px; }

    .dashboard-container .donut-legend {
        width: 100%;
    }

    .dashboard-container .service-list { max-height: 240px; }
}

@media (max-width: 992px) {
    .dashboard-container { padding: 1rem; }
    .dashboard-container .stats-row { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}

@media (max-width: 768px) {
    .dashboard-container { padding: 0.9rem; }

    .dashboard-container .stat-card { padding: 16px; }
    .dashboard-container .stat-value { font-size: 1.45rem; }
    .dashboard-container .stat-value-money { font-size: 1.2rem; }

    .dashboard-container .chart-card { padding: 1rem; }
    .dashboard-container .chart-body { height: 240px; }

    .dashboard-container .card-header-custom { padding: 1rem; }

    .dashboard-container .receptionist-table thead { display: none; }

    .dashboard-container .receptionist-table,
    .dashboard-container .receptionist-table tbody,
    .dashboard-container .receptionist-table tr,
    .dashboard-container .receptionist-table td { display: block; width: 100%; }

    .dashboard-container .receptionist-table tbody tr {
        border-bottom: 1px solid var(--db-line);
        padding: 0.35rem 0.25rem;
    }

    .dashboard-container .receptionist-table tbody tr:hover td { background: transparent; }

    .dashboard-container .receptionist-table tbody td {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 0.5rem 0.75rem;
        border-bottom: none;
        text-align: right;
    }

    .dashboard-container .receptionist-table tbody td::before {
        content: attr(data-label);
        font-size: 0.665rem;
        font-weight: 700;
        letter-spacing: 0.075em;
        text-transform: uppercase;
        color: var(--db-ink-mute);
        flex-shrink: 0;
        text-align: left;
    }

    .dashboard-container .receptionist-table tbody td[data-label="Patient"] { justify-content: flex-start; }
    .dashboard-container .receptionist-table tbody td[data-label="Patient"]::before { display: none; }

    .dashboard-container .action-cell { justify-content: flex-end; }

    .dashboard-container .table-filter-footer { flex-direction: column; align-items: stretch; }
    .dashboard-container .filter-group { justify-content: space-between; }

    .dashboard-container .service-list { max-height: 220px; }
}

@media (max-width: 576px) {
    .dashboard-container { padding: 0.75rem; }
    .dashboard-container .stats-row { grid-template-columns: minmax(0, 1fr); gap: 12px; }
    .dashboard-container .card-header-custom { flex-direction: column; align-items: stretch; }
    .dashboard-container .header-right-group { justify-content: space-between; }

    .dashboard-container .service-list { max-height: 200px; }
}

@media (prefers-reduced-motion: reduce) {
    .dashboard-container * {
        transition-duration: 0.01ms !important;
        animation-duration: 0.01ms !important;
    }
}

@media print {
    .dashboard-container { background: #ffffff; padding: 0; min-height: 0; }
    .dashboard-container .charts-row,
    .dashboard-container .action-cell,
    .dashboard-container .table-filter-footer,
    .dashboard-container .btn-view-all { display: none !important; }
    .dashboard-container .stat-card,
    .dashboard-container .appointments-card { box-shadow: none; break-inside: avoid; }
}
</style>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
(function () {
    'use strict';

    var weeklyLabels   = <?= json_encode($weeklyLabels,   JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    var weeklyDatasets = <?= json_encode($weeklyDatasets, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

    var statusLabels = <?= json_encode(array_keys($donutData ?? []),    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    var statusValues = <?= json_encode(array_map('intval', array_values($donutData ?? [])), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    var donutPalette = <?= json_encode($donutPalette ?? [],             JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

    var servicesLabels = <?= json_encode(array_keys($categoryCounts ?? []),              JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    var servicesValues = <?= json_encode(array_map('intval', array_values($categoryCounts ?? [])), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

    var TOOLTIP = {
        backgroundColor: '#ffffff',
        titleColor: '#0b1220',
        bodyColor: '#414e63',
        borderColor: '#e4e8ef',
        borderWidth: 1,
        padding: 10,
        cornerRadius: 8,
        displayColors: false,
        titleFont: { size: 12, weight: '600' },
        bodyFont: { size: 12 }
    };

    function buildCharts() {
        if (typeof Chart === 'undefined') { return; }

        // ----- Weekly volume (stacked bar) -----
        var weeklyCanvas = document.getElementById('weeklyVolumeChart');
        if (weeklyCanvas && Array.isArray(weeklyDatasets) && weeklyDatasets.length) {
            new Chart(weeklyCanvas.getContext('2d'), {
                type: 'bar',
                data: { labels: weeklyLabels, datasets: weeklyDatasets },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: Object.assign({}, TOOLTIP)
                    },
                    scales: {
                        x: {
                            stacked: true,
                            grid: { display: false },
                            border: { display: false },
                            ticks: { color: '#9aa5b5', font: { size: 11 } }
                        },
                        y: {
                            stacked: true,
                            beginAtZero: true,
                            grid: { color: '#eef1f6', drawBorder: false, drawTicks: false },
                            border: { display: false },
                            ticks: { color: '#9aa5b5', font: { size: 11 }, stepSize: 1, precision: 0 }
                        }
                    }
                }
            });
        }

        // ----- Status donut -----
        var statusCanvas = document.getElementById('statusChart');
        if (statusCanvas && statusValues.length) {
            new Chart(statusCanvas.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: statusLabels,
                    datasets: [{
                        data: statusValues,
                        backgroundColor: statusLabels.map(function (_, i) {
                            return donutPalette[i % donutPalette.length];
                        }),
                        borderWidth: 3,
                        borderColor: '#ffffff',
                        borderRadius: 6,
                        spacing: 2,
                        hoverOffset: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '72%',
                    plugins: {
                        legend: { display: false },
                        tooltip: Object.assign({}, TOOLTIP, {
                            callbacks: {
                                label: function (ctx) {
                                    var total = statusValues.reduce(function (a, b) { return a + b; }, 0);
                                    var pct = total ? Math.round((ctx.parsed / total) * 100) : 0;
                                    return ctx.parsed + ' studies (' + pct + '%)';
                                }
                            }
                        })
                    }
                }
            });
        }

        // ----- Services donut -----
        var servicesCanvas = document.getElementById('servicesChart');
        if (servicesCanvas && servicesValues.length) {
            var svcPalette = ['#2450d8', '#0f9d76', '#f0b429', '#0e7490', '#d9534f', '#7b8794', '#6d28d9'];

            new Chart(servicesCanvas.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: servicesLabels,
                    datasets: [{
                        data: servicesValues,
                        backgroundColor: servicesLabels.map(function (_, i) {
                            return svcPalette[i % svcPalette.length];
                        }),
                        borderWidth: 3,
                        borderColor: '#ffffff',
                        borderRadius: 6,
                        spacing: 2,
                        hoverOffset: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '72%',
                    plugins: {
                        legend: { display: false },
                        tooltip: Object.assign({}, TOOLTIP, {
                            callbacks: {
                                label: function (ctx) {
                                    var total = servicesValues.reduce(function (a, b) { return a + b; }, 0);
                                    var pct = total ? Math.round((ctx.parsed / total) * 100) : 0;
                                    return ctx.parsed + ' services (' + pct + '%)';
                                }
                            }
                        })
                    }
                }
            });
        }
    }

    function ensureCharts() {
        if (typeof Chart !== 'undefined') { buildCharts(); return; }

        var tag = document.createElement('script');
        tag.src = 'https://cdn.jsdelivr.net/npm/chart.js';
        tag.onload = buildCharts;
        tag.onerror = function () { /* the empty containers simply stay */ };
        document.head.appendChild(tag);
    }

    document.addEventListener('DOMContentLoaded', ensureCharts);
})();


/* =====================================================
   WORKLIST FILTER
   ===================================================== */

(function () {
    'use strict';

    var pills = Array.prototype.slice.call(document.querySelectorAll('.filter-btn'));
    var table = document.getElementById('queueTable');
    var noMatch = document.getElementById('rdNoMatch');

    function rows() {
        return table ? Array.prototype.slice.call(table.querySelectorAll('tbody tr.queue-row')) : [];
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

    pills.forEach(function (pill) {
        pill.addEventListener('click', function () {
            pills.forEach(function (p) {
                var on = p === pill;
                p.classList.toggle('is-active', on);
                p.setAttribute('aria-pressed', on ? 'true' : 'false');
            });
            applyFilter(pill.dataset.filter || 'all');
        });
    });

    applyFilter('all');
})();


/* =====================================================
   REFRESH
   ===================================================== */

function refreshTable(event) {
    var btn = (event && event.currentTarget)
        ? event.currentTarget
        : document.querySelector('.refresh-btn');
    var icon = btn ? btn.querySelector('i') : null;

    if (icon) { icon.style.animation = 'rd-spin 0.8s linear infinite'; }

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