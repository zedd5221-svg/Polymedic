<?= $this->extend('Layouts/RadiologistLayout') ?>

<?= $this->section('pageTitle') ?>Radiologist Dashboard<?= $this->endSection() ?>

<?= $this->section('radiologistContent') ?>

<?php
/* ------------------------------------------------------------------
   Radiologist Dashboard
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
$weeklyTotal    = array_sum(array_map(static fn ($ds) => array_sum($ds['data'] ?? []), $weeklyDatasets));

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

/* ------------------------------------------------------------------
   KPI BOARD (shared partial: partials/kpi_board, same as Admin)
   ------------------------------------------------------------------ */
$kpiBoard = [
    'featured' => true,
    'trends' => $kpiTrends ?? [],
    'cards'  => [
        [
            'key'     => 'total',
            'icon'    => 'analysis.png',
            'color'   => '#0891B2',
            'label'   => 'Total Studies',
            'value'   => $total,
            'delta'   => 'window',
            'context' => 'new in 14 days',
            'unit'    => 'studies',
            'link'    => null,
        ],
        [
            'key'     => 'pending',
            'icon'    => 'time.png',
            'color'   => '#EA580C',
            'label'   => 'Pending Read',
            'value'   => $pending,
            'delta'   => null,
            'context' => 'Awaiting interpretation',
            'unit'    => 'new pending',
            'link'    => base_url('radiologist/examinations'),
        ],
        [
            'key'     => 'revenue_today',
            'icon'    => 'money-yellow.png',
            'color'   => '#059669',
            'label'   => 'Revenue Today',
            'value'   => $todayRevenue,
            'money'   => true,
            'delta'   => 'dod',
            'context' => 'vs yesterday',
            'unit'    => 'X-ray revenue',
            'link'    => null,
        ],
        [
            'key'     => 'revenue_month',
            'icon'    => 'money-yellow.png',
            'color'   => '#7c3aed',
            'label'   => 'Revenue This Month',
            'value'   => $monthlyRevenue,
            'money'   => true,
            'delta'   => null,
            'context' => '≈ ₱' . number_format($monthlyRevenue / max(1, (int) date('j')), 2) . '/day · ' . date('F'),
            'unit'    => 'month-to-date',
            'link'    => null,
        ],
    ],
    /* In reading, completed and released are mutually exclusive statuses. */
    'pipeline' => [
        'title'  => 'Reading Workflow',
        'sub'    => number_format($processing + $completed + $released) . ' studies past intake',
        'link'   => base_url('radiologist/examinations'),
        'split'  => true,
        'stages' => [
            ['key' => 'processing', 'color' => '#3b82f6', 'label' => 'In Reading', 'hint' => 'Currently open',    'count' => $processing, 'today' => 'new today',       'link' => base_url('radiologist/examinations')],
            ['key' => 'completed',  'color' => '#10b981', 'label' => 'Completed',  'hint' => 'Report signed',     'count' => $completed,  'today' => 'completed today', 'link' => base_url('radiologist/examinations')],
            ['key' => 'released',   'color' => '#0D9488', 'label' => 'Released',   'hint' => 'Sent to reception', 'count' => $released,   'today' => 'released today',  'link' => base_url('radiologist/examinations')],
        ],
    ],
];
?>

<div class="dashboard-container">

    <!-- ===== KPI OVERVIEW ===== -->
    <?= view('partials/kpi_board', $kpiBoard) ?>


    <?php
    /* ------------------------------------------------------------------
       Chart data. All three charts read from real series:
         - throughput : kpiTrends (requested per day, released per day)
         - turnaround : controller getTurnaroundStats (request → release)
         - regions    : services catalogue, ranked
       ------------------------------------------------------------------ */
    $trendDates  = is_array($kpiTrends['dates'] ?? null) ? $kpiTrends['dates'] : [];
    $tpRequested = array_map('intval', $kpiTrends['requested'] ?? []);
    $tpReleased  = array_map('intval', $kpiTrends['released'] ?? []);
    $tpLabels    = array_map(static fn ($d) => date('M j', strtotime($d)), $trendDates);
    $tpReqTotal  = array_sum($tpRequested);
    $tpRelTotal  = array_sum($tpReleased);
    $tpNet       = $tpReqTotal - $tpRelTotal;
    $hasTp       = $trendDates && ($tpReqTotal + $tpRelTotal) > 0;

    $tat        = is_array($turnaround ?? null) ? $turnaround : ['count' => 0, 'buckets' => [], 'days' => 14];
    $tatCount   = (int) ($tat['count'] ?? 0);
    $tatBuckets = is_array($tat['buckets'] ?? null) ? $tat['buckets'] : [];
    $tatPeak    = $tatBuckets ? max($tatBuckets) : 0;

    /* 45 → "45m", 200 → "3h 20m", 1700 → "1d 4h" */
    $duration = static function (?float $minutes): string {
        if ($minutes === null) { return '—'; }
        $m = (int) round($minutes);
        if ($m < 60)   { return $m . 'm'; }
        if ($m < 1440) { return intdiv($m, 60) . 'h' . ($m % 60 ? ' ' . ($m % 60) . 'm' : ''); }
        $d = intdiv($m, 1440);
        $h = intdiv($m % 1440, 60);
        return $d . 'd' . ($h ? ' ' . $h . 'h' : '');
    };

    $regions = array_filter($categoryCounts, static fn ($n) => (int) $n > 0);
    arsort($regions);
    $regionPeak  = $regions ? max($regions) : 0;
    $regionTotal = array_sum($regions);
    ?>

    <!-- ===== TOP ROW: Throughput + Turnaround ===== -->
    <div class="charts-row charts-row--top">

        <section class="chart-card" aria-labelledby="tpTitle">
            <div class="chart-header">
                <div class="chart-heading">
                    <h5 id="tpTitle">Throughput</h5>
                    <small>Studies requested and reports released per day · last 14 days</small>
                </div>

                <?php if ($hasTp): ?>
                    <ul class="tp-legend" aria-label="Series">
                        <li><span class="tp-key tp-key--req" aria-hidden="true"></span>Requested <strong><?= number_format($tpReqTotal) ?></strong></li>
                        <li><span class="tp-key tp-key--rel" aria-hidden="true"></span>Released <strong><?= number_format($tpRelTotal) ?></strong></li>
                    </ul>
                <?php endif; ?>
            </div>

            <?php if ($hasTp): ?>
                <div class="chart-body chart-body--tp">
                    <canvas id="throughputChart"
                            role="img"
                            aria-label="<?= esc('Last 14 days: ' . $tpReqTotal . ' studies requested, ' . $tpRelTotal . ' reports released', 'attr') ?>"></canvas>
                </div>
                <p class="tp-foot">
                    <?php if ($tpNet > 0): ?>
                        <i class="bi bi-arrow-up-right" aria-hidden="true"></i>
                        Queue grew by <strong><?= number_format($tpNet) ?></strong> — more came in than was released.
                    <?php elseif ($tpNet < 0): ?>
                        <i class="bi bi-arrow-down-right" aria-hidden="true"></i>
                        Queue shrank by <strong><?= number_format(abs($tpNet)) ?></strong> — releases outpaced new requests.
                    <?php else: ?>
                        <i class="bi bi-arrow-right" aria-hidden="true"></i>
                        Releases kept pace with new requests.
                    <?php endif; ?>
                </p>

                <!-- Same numbers as a table, for screen readers -->
                <table class="visually-hidden">
                    <caption>Studies requested and reports released per day</caption>
                    <thead><tr><th scope="col">Day</th><th scope="col">Requested</th><th scope="col">Released</th></tr></thead>
                    <tbody>
                        <?php foreach ($tpLabels as $i => $label): ?>
                            <tr><th scope="row"><?= esc($label) ?></th><td><?= (int) ($tpRequested[$i] ?? 0) ?></td><td><?= (int) ($tpReleased[$i] ?? 0) ?></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="chart-body chart-empty">
                    <i class="bi bi-bar-chart" aria-hidden="true"></i>
                    <p>No activity in the last 14 days</p>
                    <small>Requests and released reports will be charted here.</small>
                </div>
            <?php endif; ?>
        </section>

        <section class="chart-card tat-card" aria-labelledby="tatTitle">
            <div class="chart-header">
                <div class="chart-heading">
                    <h5 id="tatTitle">Turnaround</h5>
                    <small>Request to released report · last <?= (int) ($tat['days'] ?? 14) ?> days</small>
                </div>
            </div>

            <?php if ($tatCount > 0): ?>
                <div class="tat-hero">
                    <span class="tat-value"><?= esc($duration($tat['median'] ?? null)) ?></span>
                    <span class="tat-label">median</span>
                </div>
                <p class="tat-sub">
                    90% within <strong><?= esc($duration($tat['p90'] ?? null)) ?></strong>
                    · <?= number_format($tatCount) ?> report<?= $tatCount === 1 ? '' : 's' ?>
                </p>

                <ul class="hbars" aria-label="Turnaround distribution">
                    <?php foreach ($tatBuckets as $bucket => $n): ?>
                        <?php $share = $tatCount > 0 ? round($n / $tatCount * 100) : 0; ?>
                        <li class="hbar">
                            <span class="hbar-label"><?= esc($bucket) ?></span>
                            <span class="hbar-track" aria-hidden="true">
                                <span class="hbar-fill hbar-fill--tat" style="width: <?= $tatPeak > 0 ? max(2, round($n / $tatPeak * 100, 1)) : 0 ?>%"></span>
                            </span>
                            <span class="hbar-value"><?= number_format($n) ?> <small><?= $share ?>%</small></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <div class="chart-body chart-empty chart-empty--sm">
                    <i class="bi bi-stopwatch" aria-hidden="true"></i>
                    <p>No reports released yet</p>
                    <small>Turnaround appears once reports are released.</small>
                </div>
            <?php endif; ?>
        </section>
    </div>


    <!-- ===== SECOND ROW: Catalogue by region + Available services ===== -->
    <div class="charts-row charts-row--bottom">

        <section class="chart-card" aria-labelledby="rgTitle">
            <div class="chart-header">
                <div class="chart-heading">
                    <h5 id="rgTitle">Catalogue by region</h5>
                    <small>Active X-ray services per body region</small>
                </div>
                <?php if ($regionTotal > 0): ?>
                    <span class="chart-count"><?= number_format($regionTotal) ?> services</span>
                <?php endif; ?>
            </div>

            <?php if ($regions): ?>
                <ul class="hbars" aria-label="Services per region">
                    <?php foreach ($regions as $region => $n): ?>
                        <li class="hbar">
                            <span class="hbar-label" title="<?= esc($region, 'attr') ?>"><?= esc($region) ?></span>
                            <span class="hbar-track" aria-hidden="true">
                                <span class="hbar-fill" style="width: <?= $regionPeak > 0 ? round($n / $regionPeak * 100, 1) : 0 ?>%"></span>
                            </span>
                            <span class="hbar-value"><?= number_format($n) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <div class="chart-body chart-empty chart-empty--sm">
                    <i class="bi bi-list-ul" aria-hidden="true"></i>
                    <p>No services on file</p>
                    <small>Add X-ray services to see their spread here.</small>
                </div>
            <?php endif; ?>
        </section>

        <section class="chart-card chart-card--services" aria-labelledby="svTitle">
            <div class="chart-header">
                <div class="chart-heading">
                    <h5 id="svTitle">Available X-ray services</h5>
                    <small>Active catalogue and prices</small>
                </div>
                <span class="chart-count"><?= number_format($xrayCount) ?></span>
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
        </section>
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
    --db-accent:      var(--pm-accent, #175563);
    --db-accent-dark: var(--pm-accent-hover, #10404B);
    --db-accent-soft: var(--pm-accent-soft, #EBF2F4);

    --db-ink:         var(--pm-text, #10202E);
    --db-ink-soft:    var(--pm-text-soft, #3B4C5E);
    --db-ink-mute:    var(--pm-text-muted, #5D6E80);
    --db-ink-faint:   var(--pm-text-muted);

    --db-line:        var(--pm-line, #DDE4EC);
    --db-line-soft:   var(--pm-line);
    --db-surface:     var(--pm-surface);
    --db-canvas:      var(--pm-canvas, #F3F6F9);
    --db-rail:        var(--pm-subtle);

    --db-amber:       #b45309;
    --db-green:       #067a55;
    --db-teal:        #0f766e;
    --db-cyan:        #0E8A7E;
    --db-red:         #c2323b;

    --db-r-lg:        8px;
    --db-r-md:        8px;
    --db-r-sm:        6px;
    --db-r-xs:        5px;

    --db-mono:        var(--pm-mono), ui-monospace, SFMono-Regular, "SF Mono", Menlo, Consolas, monospace;

    padding: 0;
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


.dashboard-container .chart-card {
    background: var(--db-surface);
    border: 1px solid var(--db-line);
    border-radius: var(--db-r-lg);
    padding: 1.15rem 1.25rem 1.25rem;
    box-shadow: none;
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
    font-weight: 600;
    letter-spacing: -0.005em;
    color: var(--db-ink);
    margin: 0 0 0.15rem;
    display: flex;
    align-items: center;
    gap: 0.45rem;
}

.dashboard-container .chart-heading h5 i { color: var(--db-ink-mute); font-size: 0.9rem; }

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
    background: var(--pm-dk-bg-blue, #cbd5e1);
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

.dashboard-container .service-list-item:hover { background: var(--pm-dk-bg-neutral, #eef2f7); }

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
   WORKLIST CARD
   ========================================================= */

.dashboard-container .appointments-card {
    background: var(--db-surface);
    border: 1px solid var(--db-line);
    border-radius: var(--db-r-lg);
    box-shadow: none;
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

.dashboard-container .status-badge.pending   { background: var(--pm-dk-bg-amber, #fdf6ec); color: var(--db-amber); border-color: var(--pm-dk-edge-amber, #f4e2c4); }
.dashboard-container .status-badge.progress  { background: var(--db-accent-soft); color: var(--db-accent); border-color: var(--pm-dk-edge-blue, #d3e0fb); }
.dashboard-container .status-badge.completed { background: var(--pm-dk-bg-green, #eefaf4); color: var(--db-green); border-color: var(--pm-dk-edge-green, #c6ebda); }
.dashboard-container .status-badge.released  { background: var(--pm-dk-bg-teal, #f0fdfa); color: var(--db-teal); border-color: var(--pm-dk-edge-teal, #cdeae4); }
.dashboard-container .status-badge.stat      { background: var(--pm-dk-bg-red, #fdeef0); color: var(--db-red); border-color: var(--pm-dk-edge-red, #f4d2d5); }

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
.dashboard-container .action-icon-btn.view:hover     { background: var(--db-accent-soft); color: var(--db-accent); border-color: var(--pm-dk-edge-blue, #d3e0fb); }
.dashboard-container .action-icon-btn.complete:hover { background: var(--pm-dk-bg-green, #eefaf4); color: var(--db-green); border-color: var(--pm-dk-edge-green, #c6ebda); }

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
    border-color: var(--pm-line);
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

@media (max-width: 1100px) {
    .dashboard-container .service-list { max-height: 240px; }
}

@media (max-width: 768px) {
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
    .dashboard-container { background: var(--pm-surface); padding: 0; min-height: 0; }
    .dashboard-container .charts-row,
    .dashboard-container .action-cell,
    .dashboard-container .table-filter-footer,
    .dashboard-container .btn-view-all { display: none !important; }
    .dashboard-container .appointments-card { box-shadow: none; break-inside: avoid; }
}
/* =========================================================
   CHARTS — throughput, turnaround, catalogue
   Series colours are validated as a pair (dataviz validator,
   light surface): requested #175563, released #009A85.
   ========================================================= */

.dashboard-container {
    --tp-req: #175563;
    --tp-rel: #009A85;
}

.dashboard-container .charts-row--bottom {
    grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
}

.dashboard-container .chart-count {
    font-size: 0.75rem;
    font-weight: 500;
    color: var(--db-ink-mute);
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
}

.dashboard-container .chart-empty--sm { height: 180px; }

/* ---- throughput ---- */

.dashboard-container .tp-legend {
    display: flex;
    gap: 1rem;
    margin: 0;
    padding: 0;
    list-style: none;
}

.dashboard-container .tp-legend li {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    font-size: 0.78rem;
    color: var(--db-ink-soft);
    white-space: nowrap;
}

.dashboard-container .tp-legend strong {
    font-weight: 600;
    color: var(--db-ink);
    font-variant-numeric: tabular-nums;
}

.dashboard-container .tp-key {
    width: 10px;
    height: 10px;
    border-radius: 2px;
}

.dashboard-container .tp-key--req { background: var(--tp-req); }
.dashboard-container .tp-key--rel { background: var(--tp-rel); }

.dashboard-container .chart-body--tp { height: 260px; }

.dashboard-container .tp-foot {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    margin: 0.85rem 0 0;
    padding-top: 0.75rem;
    font-size: 0.78rem;
    color: var(--db-ink-mute);
    border-top: 1px solid var(--db-line-soft);
}

.dashboard-container .tp-foot strong { color: var(--db-ink); font-weight: 600; }
.dashboard-container .tp-foot i { font-size: 0.85rem; color: var(--db-ink-faint); }

/* ---- turnaround: one headline number, then its spread ---- */

.dashboard-container .tat-card { display: flex; flex-direction: column; }

.dashboard-container .tat-hero {
    display: flex;
    align-items: baseline;
    gap: 0.5rem;
}

.dashboard-container .tat-value {
    font-size: 2.25rem;
    font-weight: 600;
    letter-spacing: -0.03em;
    line-height: 1;
    color: var(--db-ink);
    font-variant-numeric: tabular-nums;
}

.dashboard-container .tat-label {
    font-size: 0.8rem;
    color: var(--db-ink-mute);
}

.dashboard-container .tat-sub {
    margin: 0.5rem 0 1.1rem;
    font-size: 0.78rem;
    color: var(--db-ink-mute);
}

.dashboard-container .tat-sub strong { color: var(--db-ink); font-weight: 600; }

.dashboard-container .tat-card .hbars { margin-top: auto; }

/* ---- horizontal bar list (turnaround spread, catalogue) ---- */

.dashboard-container .hbars {
    display: flex;
    flex-direction: column;
    gap: 0.55rem;
    margin: 0;
    padding: 0;
    list-style: none;
}

.dashboard-container .hbar {
    display: grid;
    grid-template-columns: minmax(84px, 9.5rem) minmax(0, 1fr) 3.75rem;
    align-items: center;
    gap: 0.75rem;
    font-size: 0.8rem;
}

.dashboard-container .hbar-label {
    color: var(--db-ink-soft);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.dashboard-container .hbar-track {
    height: 8px;
    overflow: hidden;
    background: var(--db-line-soft);
    border-radius: 4px;
}

.dashboard-container .hbar-fill {
    display: block;
    height: 100%;
    background: var(--tp-req);
    border-radius: 4px;
}

.dashboard-container .hbar-fill--tat { background: var(--tp-rel); }

.dashboard-container .hbar-value {
    font-weight: 600;
    color: var(--db-ink);
    text-align: right;
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
}

.dashboard-container .hbar-value small {
    margin-left: 0.15rem;
    font-size: 0.7rem;
    font-weight: 500;
    color: var(--db-ink-faint);
}

@media (max-width: 1200px) {
    .dashboard-container .charts-row--top,
    .dashboard-container .charts-row--bottom { grid-template-columns: minmax(0, 1fr); }
}

@media (max-width: 576px) {
    .dashboard-container .tp-legend { width: 100%; }
    .dashboard-container .hbar { grid-template-columns: 6.5rem minmax(0, 1fr) 3.25rem; }
}
</style>

<!-- Chart.js (pinned so a new major version cannot silently break the chart) -->
<script src="<?= base_url('assets/vendor/chartjs/chart.umd.min.js') ?>"></script>

<script>
(function () {
    'use strict';

    var tpLabels    = <?= json_encode($tpLabels ?? [],    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    var tpRequested = <?= json_encode($tpRequested ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    var tpReleased  = <?= json_encode($tpReleased ?? [],  JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

    var INK   = '#10202E';
    var SOFT  = '#5D6E80';
    var FAINT = '#8496A8';
    var GRID  = '#EBF0F5';

    function plural(n, one, many) { return n + ' ' + (n === 1 ? one : many); }

    function buildCharts() {
        if (typeof Chart === 'undefined') { return; }

        var canvas = document.getElementById('throughputChart');
        if (!canvas || !tpLabels.length) { return; }

        var existing = Chart.getChart ? Chart.getChart(canvas) : null;
        if (existing) { existing.destroy(); }

        var bar = {
            borderRadius: { topLeft: 4, topRight: 4 },
            borderSkipped: 'bottom',
            /* 2px surface gap between neighbouring bars */
            borderWidth: { left: 1, right: 1 },
            borderColor: '#FFFFFF',
            categoryPercentage: 0.72,
            barPercentage: 0.92,
            maxBarThickness: 18
        };

        new Chart(canvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels: tpLabels,
                datasets: [
                    Object.assign({ label: 'Requested', data: tpRequested, backgroundColor: '#175563', hoverBackgroundColor: '#10404B' }, bar),
                    Object.assign({ label: 'Released',  data: tpReleased,  backgroundColor: '#009A85', hoverBackgroundColor: '#007D6C' }, bar)
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches ? false : { duration: 400 },
                /* One tooltip per day, triggered anywhere in the day's column */
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#FFFFFF',
                        titleColor: INK,
                        bodyColor: SOFT,
                        borderColor: '#DDE4EC',
                        borderWidth: 1,
                        padding: 10,
                        cornerRadius: 6,
                        boxWidth: 8,
                        boxHeight: 8,
                        boxPadding: 4,
                        usePointStyle: true,
                        titleFont: { size: 12, weight: '600' },
                        bodyFont: { size: 12 },
                        callbacks: {
                            label: function (ctx) {
                                var n = ctx.parsed.y;
                                return ' ' + ctx.dataset.label + ': ' + (ctx.datasetIndex === 0 ? plural(n, 'study', 'studies') : plural(n, 'report', 'reports'));
                            },
                            labelPointStyle: function () { return { pointStyle: 'rectRounded', rotation: 0 }; }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        border: { color: '#DDE4EC' },
                        ticks: {
                            color: FAINT,
                            font: { size: 11 },
                            maxRotation: 0,
                            autoSkip: true,
                            autoSkipPadding: 12
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grace: '10%',
                        grid: { color: GRID, drawTicks: false },
                        border: { display: false },
                        ticks: { color: FAINT, font: { size: 11 }, precision: 0, padding: 8, maxTicksLimit: 5 }
                    }
                }
            }
        });
    }

    function ensureCharts() {
        if (typeof Chart !== 'undefined') { buildCharts(); return; }

        var tag = document.createElement('script');
        tag.src = '<?= base_url('assets/vendor/chartjs/chart.umd.min.js') ?>';
        tag.onload = buildCharts;
        tag.onerror = function () { /* the summary text above the canvas still reads correctly */ };
        document.head.appendChild(tag);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', ensureCharts);
    } else {
        ensureCharts();
    }
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