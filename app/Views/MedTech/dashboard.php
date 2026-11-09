<?= $this->extend('Layouts/MedTechLayout') ?>

<?= $this->section('pageTitle') ?>MedTech Dashboard<?= $this->endSection() ?>

<?= $this->section('medtechContent') ?>

<?php
/* ------------------------------------------------------------------
   MedTech Dashboard

   KPI board is the shared Admin design (partials/kpi_board).
   Specimen intake (Today / This month toggle), turnaround as bullet
   bars against each test's target, volume by section as ranked bars,
   the active services list, and the specimen queue.
   ------------------------------------------------------------------ */

/* ------------------------------------------------------------------
   KPI BOARD (shared partial: partials/kpi_board, same as Admin)
   ------------------------------------------------------------------ */
$mtCount = static fn($k) => (int) ($counts[$k] ?? 0);
/* counts['in_progress'] already includes drafts; use the exclusive figure here. */
$mtActive = $mtCount('pending') + $mtCount('in_progress_only') + $mtCount('draft');

$kpiBoard = [
    'featured' => true,
    'trends' => $kpiTrends ?? [],
    'cards'  => [
        [
            'key'     => 'total',
            'icon'    => 'people-blue.png',
            'color'   => '#0891B2',
            'label'   => 'Total Requests',
            'value'   => $mtCount('total'),
            'delta'   => 'window',
            'context' => 'new in 14 days',
            'unit'    => 'requests',
            'link'    => base_url('medtech/requests'),
        ],
        [
            'key'     => 'in_queue',
            'icon'    => 'file (1).png',
            'color'   => '#DB2777',
            'label'   => 'In Queue',
            'value'   => $mtCount('total') - $mtCount('released'),
            'delta'   => null,
            'context' => 'Not yet released',
            'unit'    => 'queued',
            'link'    => base_url('medtech/requests'),
        ],
        [
            'key'     => 'completed',
            'icon'    => 'people-check-blue.png',
            'color'   => '#16A34A',
            'label'   => 'Completed',
            'value'   => $mtCount('completed'),
            'delta'   => 'dod',
            'context' => 'vs yesterday',
            'unit'    => 'completed',
            'link'    => base_url('medtech/requests?status=completed'),
        ],
        [
            'key'     => 'released',
            'icon'    => 'clock (4).png',
            'color'   => '#2563EB',
            'label'   => 'Released',
            'value'   => $mtCount('released'),
            'delta'   => 'dod',
            'context' => 'vs yesterday',
            'unit'    => 'released',
            'link'    => base_url('medtech/requests?status=released'),
        ],
    ],
    /* Pending, in-progress and draft are mutually exclusive statuses. */
    'pipeline' => [
        'title'  => 'Work in Progress',
        'sub'    => number_format($mtActive) . ' specimens being handled',
        'link'   => base_url('medtech/requests'),
        'split'  => true,
        'stages' => [
            ['key' => 'pending',     'color' => '#f59e0b', 'label' => 'Pending',     'hint' => 'Awaiting processing',  'count' => $mtCount('pending'),     'today' => 'new today', 'link' => base_url('medtech/requests?status=pending')],
            ['key' => 'in_progress', 'color' => '#3b82f6', 'label' => 'In-Progress', 'hint' => 'Currently running',    'count' => $mtCount('in_progress_only'), 'today' => 'new today', 'link' => base_url('medtech/requests?status=in_progress')],
            ['key' => 'draft',       'color' => '#8b5cf6', 'label' => 'Drafts',      'hint' => 'Saved, not submitted', 'count' => $mtCount('draft'),       'today' => 'new today', 'link' => base_url('medtech/requests?status=draft')],
        ],
    ],
];

/* Turnaround: on time / near limit (80% of target) / over target. */
$tatRows = $turnaroundData ?? [];

$tatToneOf = static function (array $t): string {
    $avg    = (float) ($t['avg'] ?? 0);
    $target = (float) ($t['target'] ?? 0);
    if ($avg <= 0)    { return 'none'; }
    if ($target <= 0) { return 'green'; }
    $ratio = $avg / $target;
    if ($ratio >= 1)   { return 'red'; }
    if ($ratio >= 0.8) { return 'orange'; }
    return 'green';
};

$catalog = $serviceCatalog ?? [
    'lab'        => [],
    'xray'       => [],
    'labCount'   => 0,
    'xrayCount'  => 0,
    'otherCount' => 0,
    'total'      => 0,
];
?>

<div class="dashboard-wrapper">

    <!-- ===== KPI OVERVIEW ===== -->
    <?= view('partials/kpi_board', $kpiBoard) ?>

    <?php
    /* ------------------------------------------------------------------
       Chart data (all from the controller's real series)
       ------------------------------------------------------------------ */

    /* Minutes → "45 min", "2h 10m" */
    $mtMinutes = static function (float $m): string {
        $m = (int) round($m);
        if ($m < 60) { return $m . ' min'; }
        return intdiv($m, 60) . 'h' . ($m % 60 ? ' ' . ($m % 60) . 'm' : '');
    };

    /* --- Intake today: hourly. Trim the quiet night hours, but never hide data. --- */
    $hourCounts = array_map('intval', $intakeData['data'] ?? array_fill(0, 24, 0));
    $hourCounts = array_pad(array_slice($hourCounts, 0, 24), 24, 0);
    $nowHour    = (int) date('G');
    $nonZero    = array_keys(array_filter($hourCounts));
    $hStart     = min(6, $nonZero ? min($nonZero) : 6);
    $hEnd       = max(20, $nonZero ? max($nonZero) : 0, $nowHour);
    $todayLabels = [];
    $todayValues = [];
    for ($h = $hStart; $h <= $hEnd; $h++) {
        $todayLabels[] = date('ga', mktime($h, 0, 0));
        $todayValues[] = $h > $nowHour ? null : $hourCounts[$h];   // future hours: nothing to draw
    }
    $todayTotal = array_sum($hourCounts);
    $peakHour   = $todayTotal > 0 ? array_search(max($hourCounts), $hourCounts, true) : null;
    $todayNowIx = $nowHour - $hStart;

    /* --- Intake this month: daily, up to today --- */
    $dayCounts  = array_map('intval', $intakeMonthData['data'] ?? []);
    $todayDay   = (int) date('j');
    $monthLabels = [];
    $monthValues = [];
    foreach ($dayCounts as $i => $n) {
        $monthLabels[] = date('M j', mktime(0, 0, 0, (int) date('n'), $i + 1));
        $monthValues[] = ($i + 1) > $todayDay ? null : $n;
    }
    $monthSoFar = array_slice($dayCounts, 0, $todayDay);
    $monthTotal = array_sum($monthSoFar);
    $monthAvg   = $todayDay > 0 ? $monthTotal / $todayDay : 0;
    $busiestDay = $monthTotal > 0 ? array_search(max($monthSoFar), $monthSoFar, true) : null;

    /* --- Volume by section today: ranked --- */
    $sections = [];
    foreach (($sectionData['labels'] ?? []) as $i => $label) {
        $n = (int) (($sectionData['data'] ?? [])[$i] ?? 0);
        if ($n > 0 && $label !== 'No Data') { $sections[$label] = $n; }
    }
    arsort($sections);
    $sectionPeak  = $sections ? max($sections) : 0;
    $sectionTotal = array_sum($sections);

    /* --- Turnaround: bullet bars, each against its own section target --- */
    $tatRowsWith = array_values(array_filter($tatRows, static fn ($t) => (float) ($t['avg'] ?? 0) > 0));
    $tatRowsNone = array_values(array_filter($tatRows, static fn ($t) => (float) ($t['avg'] ?? 0) <= 0));
    usort($tatRowsWith, static fn ($a, $b) => ((float) $b['avg'] / max(1, (float) $b['target'])) <=> ((float) $a['avg'] / max(1, (float) $a['target'])));
    $tatOver = count(array_filter($tatRowsWith, static fn ($t) => $tatToneOf($t) === 'red'));
    ?>

    <!-- ===== ROW 1: Specimen intake + Turnaround ===== -->
    <div class="mt-row mt-row--top">

        <section class="chart-card mt-intake" aria-labelledby="mtIntakeTitle">
            <div class="chart-header">
                <div>
                    <h5 id="mtIntakeTitle">Specimen intake</h5>
                    <small>Lab requests received</small>
                </div>
                <div class="mt-seg" role="tablist" aria-label="Intake period">
                    <button type="button" class="mt-seg-btn is-active" role="tab" aria-selected="true" data-range="today">Today</button>
                    <button type="button" class="mt-seg-btn" role="tab" aria-selected="false" data-range="month">This month</button>
                </div>
            </div>

            <dl class="mt-stats" data-range-panel="today">
                <div><dt>Received today</dt><dd><?= number_format($todayTotal) ?></dd></div>
                <div><dt>Busiest hour</dt><dd><?= $peakHour !== null ? esc(date('g A', mktime($peakHour, 0, 0))) . ' <small>' . (int) $hourCounts[$peakHour] . '</small>' : '<span class="mt-muted">—</span>' ?></dd></div>
                <div><dt>This hour</dt><dd><?= number_format($hourCounts[$nowHour] ?? 0) ?></dd></div>
            </dl>
            <dl class="mt-stats" data-range-panel="month" hidden>
                <div><dt><?= esc(date('F')) ?> so far</dt><dd><?= number_format($monthTotal) ?></dd></div>
                <div><dt>Daily average</dt><dd><?= number_format($monthAvg, $monthAvg < 10 ? 1 : 0) ?></dd></div>
                <div><dt>Busiest day</dt><dd><?= $busiestDay !== null ? esc(date('M j', mktime(0, 0, 0, (int) date('n'), $busiestDay + 1))) . ' <small>' . (int) $monthSoFar[$busiestDay] . '</small>' : '<span class="mt-muted">—</span>' ?></dd></div>
            </dl>

            <div class="chart-body mt-intake-body">
                <canvas id="intakeChart" role="img" aria-label="<?= esc('Specimens received: ' . $todayTotal . ' today, ' . $monthTotal . ' this month', 'attr') ?>"></canvas>
            </div>
        </section>

        <section class="chart-card mt-tat" aria-labelledby="mtTatTitle">
            <div class="chart-header">
                <div>
                    <h5 id="mtTatTitle">Turnaround vs target</h5>
                    <small>Average request to release by section &middot; last 30 days</small>
                </div>
                <?php if ($tatRowsWith): ?>
                    <span class="mt-chip<?= $tatOver ? ' mt-chip--over' : '' ?>">
                        <?php if ($tatOver): ?>
                            <i class="bi bi-exclamation-octagon" aria-hidden="true"></i><?= $tatOver ?> over target
                        <?php else: ?>
                            <i class="bi bi-check2" aria-hidden="true"></i>All on target
                        <?php endif; ?>
                    </span>
                <?php endif; ?>
            </div>

            <?php if ($tatRowsWith): ?>
                <ul class="mt-bullets" aria-label="Turnaround by test">
                    <?php foreach ($tatRowsWith as $t):
                        $avg    = (float) $t['avg'];
                        $target = (float) ($t['target'] ?? 0);
                        $tone   = $tatToneOf($t);
                        $state  = $tone === 'red' ? 'over' : ($tone === 'orange' ? 'near' : 'ok');
                        $gap    = $avg - $target;
                        $scale  = max($avg, $target) * 1.25;   /* own scale: on-target bars end before the tick */
                    ?>
                        <li class="mt-bullet mt-bullet--<?= $state ?>">
                            <div class="mt-bullet-head">
                                <span class="mt-bullet-name"><?= esc($t['name'] ?? '') ?></span>
                                <span class="mt-bullet-value"><?= esc($mtMinutes($avg)) ?></span>
                            </div>
                            <div class="mt-bullet-track" aria-hidden="true">
                                <span class="mt-bullet-fill" style="width: <?= $scale > 0 ? round($avg / $scale * 100, 1) : 0 ?>%"></span>
                                <?php if ($target > 0): ?>
                                    <span class="mt-bullet-target" style="left: <?= round($target / $scale * 100, 1) ?>%"></span>
                                <?php endif; ?>
                            </div>
                            <div class="mt-bullet-foot">
                                <span>Target <?= esc($mtMinutes($target)) ?> &middot; <?= (int) ($t['count'] ?? 0) ?> released</span>
                                <span class="mt-bullet-state">
                                    <?php if ($state === 'over'): ?>
                                        <i class="bi bi-exclamation-octagon" aria-hidden="true"></i><?= esc($mtMinutes($gap)) ?> over
                                    <?php elseif ($state === 'near'): ?>
                                        <i class="bi bi-exclamation-triangle" aria-hidden="true"></i>Near limit · <?= esc($mtMinutes(abs($gap))) ?> left
                                    <?php else: ?>
                                        <i class="bi bi-check2" aria-hidden="true"></i><?= esc($mtMinutes(abs($gap))) ?> under
                                    <?php endif; ?>
                                </span>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <script>
                (function () {
                    var list = document.currentScript.previousElementSibling;
                    if (!list || list.children.length <= 4) return;
                    function fit() {
                        var fourth = list.children[3];
                        list.style.setProperty('--mt-visible', (fourth.offsetTop - list.children[0].offsetTop + fourth.offsetHeight + 2) + 'px');
                        edge();
                    }
                    function edge() {
                        list.classList.toggle('is-end', list.scrollTop + list.clientHeight >= list.scrollHeight - 2);
                    }
                    list.classList.add('is-capped');
                    list.setAttribute('tabindex', '0');
                    list.addEventListener('scroll', edge, { passive: true });
                    fit();
                    window.addEventListener('resize', fit);
                    if (document.fonts && document.fonts.ready) document.fonts.ready.then(fit);
                })();
                </script>

                <p class="mt-key"><span class="mt-key-tick" aria-hidden="true"></span>Target</p>
            <?php else: ?>
                <div class="chart-body chart-empty mt-empty">
                    <i class="bi bi-stopwatch" aria-hidden="true"></i>
                    <p>No released results yet</p>
                    <small>Turnaround appears once results are released.</small>
                </div>
            <?php endif; ?>

            <?php if ($tatRowsWith && $tatRowsNone): ?>
                <p class="mt-none">
                    Awaiting first release:
                    <?= esc(implode(', ', array_map(static fn ($t) => (string) ($t['name'] ?? ''), $tatRowsNone))) ?>
                </p>
            <?php endif; ?>
        </section>
    </div>

    <!-- ===== ROW 2: Volume by section + Available services ===== -->
    <div class="mt-row">

        <section class="chart-card" aria-labelledby="mtSecTitle">
            <div class="chart-header">
                <div>
                    <h5 id="mtSecTitle">Volume by section</h5>
                    <small>Tests requested today per laboratory section</small>
                </div>
                <?php if ($sectionTotal > 0): ?>
                    <span class="mt-count"><?= number_format($sectionTotal) ?> test<?= $sectionTotal === 1 ? '' : 's' ?></span>
                <?php endif; ?>
            </div>

            <?php if ($sections): ?>
                <ul class="mt-hbars" aria-label="Tests per section">
                    <?php foreach ($sections as $section => $n): ?>
                        <li class="mt-hbar">
                            <span class="mt-hbar-label" title="<?= esc($section, 'attr') ?>"><?= esc($section) ?></span>
                            <span class="mt-hbar-track" aria-hidden="true">
                                <span class="mt-hbar-fill" style="width: <?= $sectionPeak > 0 ? round($n / $sectionPeak * 100, 1) : 0 ?>%"></span>
                            </span>
                            <span class="mt-hbar-value"><?= number_format($n) ?> <small><?= $sectionTotal > 0 ? round($n / $sectionTotal * 100) : 0 ?>%</small></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <div class="chart-body chart-empty mt-empty">
                    <i class="bi bi-list-ul" aria-hidden="true"></i>
                    <p>No tests requested today</p>
                    <small>Sections fill in as today's requests arrive.</small>
                </div>
            <?php endif; ?>
        </section>

        <section class="chart-card chart-card--note" aria-labelledby="mtSvcTitle">
            <div class="chart-header">
                <div>
                    <h5 id="mtSvcTitle">Available services</h5>
                    <small>Active laboratory tests and prices</small>
                </div>
                <span class="mt-count"><?= number_format((int) $catalog['labCount']) ?></span>
            </div>

            <?php if (!empty($catalog['lab'])): ?>
                <ul class="service-list" aria-label="Active laboratory services">
                    <?php foreach ($catalog['lab'] as $service): ?>
                        <li class="service-list-item">
                            <span class="service-list-name"><?= esc($service['service_name']) ?></span>
                            <span class="service-list-price">
                                &#8369;<?= number_format((float) $service['charge'], 2) ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="service-list-empty">No laboratory services are currently active.</p>
            <?php endif; ?>
        </section>
    </div>

    <!-- ===== SPECIMEN QUEUE ===== -->
    <div class="mt-queue">
        <div class="queue-card">
            <div class="queue-header">
                <div class="queue-title">
                    <h5>Specimen Queue</h5>
                    <small class="text-muted">Showing <?= count($allRequests ?? []) ?> of <?= $totalRequests ?? 0 ?> specimens</small>
                </div>
                <div class="queue-filters">
                    <button class="filter-btn active" data-filter="all">All</button>
                    <button class="filter-btn" data-filter="pending">Pending</button>
                    <button class="filter-btn" data-filter="in_progress">Processing</button>
                    <button class="filter-btn" data-filter="completed">Completed</button>
                    <button class="filter-btn" data-filter="released">Released</button>
                </div>
                <button class="refresh-btn" onclick="refreshTable()">
                    <i class="bi bi-arrow-clockwise"></i> Refresh
                </button>
            </div>
            <div class="table-responsive">
                <table class="table queue-table" id="queueTable">
                    <thead>
                        <tr>
                            <th>Accession No.</th>
                            <th>Patient</th>
                            <th>Test</th>
                            <th>Received</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($allRequests)): ?>
                            <?php foreach ($allRequests as $request): ?>
                                <?php $rowTags = \App\Models\DiagnosticRequestModel::statusTags((string) $request['status']); ?>
                                <tr class="queue-row" data-status="<?= esc($request['status']) ?>" data-statuses="<?= esc(implode(' ', $rowTags), 'attr') ?>">
                                    <td class="accession">LAB-<?= date('y') ?>-<?= str_pad($request['id'], 4, '0', STR_PAD_LEFT) ?></td>
                                    <td>
                                        <div class="patient-cell">
                                            <span class="patient-name"><?= esc($request['patient_name']) ?></span>
                                            <small><?= esc($request['age']) ?> yrs · <?= esc($request['gender']) ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <?php 
                                        $services = explode(', ', $request['lab_services'] ?? '');
                                        $displayServices = array_slice($services, 0, 2);
                                        $moreCount = count($services) - 2;
                                        ?>
                                        <?php foreach ($displayServices as $service): ?>
                                            <?php if (!empty($service)): ?>
                                                <span class="service-tag"><?= esc($service) ?></span>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                        <?php if ($moreCount > 0): ?>
                                            <span class="service-tag more">+<?= $moreCount ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= date('h:i A', strtotime($request['created_at'] ?? date('Y-m-d H:i:s'))) ?></td>
                                    <td>
                                        <?php foreach ($rowTags as $tag): ?>
                                            <span class="status-badge <?= esc($tag, 'attr') ?>">
                                                <i class="bi bi-circle-fill"></i>
                                                <?= esc(ucfirst(str_replace('_', ' ', $tag))) ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </td>
                                    <td>
                                        <a href="<?= base_url('medtech/request/view/' . $request['id']) ?>" class="action-icons" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <?php if ($request['status'] !== 'released' && $request['status'] !== 'completed'): ?>
                                            <a href="<?= base_url('medtech/request/view/' . $request['id']) ?>" class="action-icons" title="Process">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6">
                                    <div class="empty-state">
                                        <i class="bi bi-inbox"></i>
                                        <p>No specimens in queue</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<style>
/* ============================================
   DASHBOARD
   ============================================ */

.dashboard-wrapper {
    --db-accent:        #0D9488;
    --db-accent-soft:   var(--pm-tint-sea);
    --db-surface:       var(--pm-surface);
    --db-canvas:        var(--pm-canvas, #F3F6F9);
    --db-subtle:        var(--pm-subtle, #EBF0F5);
    --db-border:        var(--pm-line, #DDE4EC);
    --db-border-strong: var(--pm-line-strong, #C3CFDB);
    --db-text:          var(--pm-text, #10202E);
    --db-text-soft:     var(--pm-text-soft, #3B4C5E);
    --db-text-muted:    var(--pm-text-muted, #5D6E80);
    --db-radius:        8px;
    --db-radius-sm:     6px;
    --db-radius-xs:     6px;
    --db-shadow:        none;
    --db-shadow-hover:  none;
    --db-gap:           20px;

    font-family: var(--pm-font);
    background: transparent;
    padding: 0;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
    color: var(--db-text);
}

.dashboard-wrapper :focus-visible {
    outline: 2px solid var(--db-accent);
    outline-offset: 2px;
    border-radius: var(--db-radius-xs);
}

.chart-card {
    background: var(--db-surface);
    border-radius: var(--db-radius);
    padding: 1.25rem;
    border: 1px solid var(--db-border);
}

.chart-card--compact { padding: 1.1rem 1.25rem; }

.chart-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
}

.chart-header h5 {
    font-weight: 600;
    color: var(--db-text);
    margin: 0 0 0.15rem;
    font-size: 0.95rem;
}

.chart-header small {
    color: var(--db-text-muted);
    font-size: 0.78rem;
    font-weight: 400;
}

.chart-body {
    position: relative;
    height: 300px;
}

/* ============================================
   AVAILABLE SERVICES
   ============================================ */

.service-list {
    list-style: none;
    margin: 0;
    padding: 0;
    max-height: 260px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 0.2rem;
    scrollbar-width: thin;
    scrollbar-color: #cbd5e1 transparent;
}

.service-list::-webkit-scrollbar { width: 6px; }
.service-list::-webkit-scrollbar-thumb { background: var(--pm-dk-bg-blue, #cbd5e1); border-radius: 3px; }
.service-list::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
.service-list::-webkit-scrollbar-track { background: transparent; }

.service-list-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    padding: 0.5rem 0.65rem;
    border-radius: var(--db-radius-sm);
    background: var(--db-subtle);
    min-height: 34px;
    transition: background-color 0.15s ease;
}

.service-list-item:hover { background: var(--pm-dk-bg-neutral, #eef2f7); }

.service-list-name {
    font-size: 0.8125rem;
    color: var(--db-text);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    min-width: 0;
}

.service-list-price {
    font-size: 0.78rem;
    font-weight: 600;
    color: var(--db-text-soft);
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
    flex-shrink: 0;
}

.service-list-empty {
    margin: 0;
    padding: 1rem 0.25rem;
    font-size: 0.8125rem;
    color: var(--db-text-muted);
    text-align: center;
}

/* ===== QUEUE CARD ===== */

.queue-card {
    background: var(--db-surface);
    border-radius: var(--db-radius);
    padding: 1.25rem;
    border: 1px solid var(--db-border);
}

.queue-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    flex-wrap: wrap;
    gap: 0.75rem;
}

.queue-title h5 {
    font-weight: 600;
    color: var(--db-text);
    margin: 0;
    font-size: 1rem;
}

.queue-title small {
    font-size: 0.75rem;
    color: var(--db-text-muted);
}

.queue-filters {
    display: flex;
    gap: 0.25rem;
    flex-wrap: wrap;
}

.filter-btn {
    background: transparent;
    border: none;
    padding: 0.3rem 0.75rem;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--db-text-muted);
    cursor: pointer;
    transition: all 0.2s ease;
}

.filter-btn:hover { background: var(--db-subtle); }
.filter-btn.active { background: var(--pm-ink, #10202E); color: #ffffff; }

.refresh-btn {
    background: var(--db-surface);
    border: 1px solid var(--db-border);
    padding: 0.4rem 0.85rem;
    border-radius: 8px;
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--db-text);
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    transition: all 0.2s ease;
}

.refresh-btn:hover { background: var(--db-subtle); }

/* ===== QUEUE TABLE ===== */

.queue-table { margin: 0; }

.queue-table thead th {
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--db-text-muted);
    font-weight: 600;
    border-bottom: 1px solid var(--db-border);
    padding: 0.7rem 0.75rem;
    background: var(--db-subtle);
    white-space: nowrap;
}

.queue-table tbody td {
    padding: 0.75rem;
    vertical-align: middle;
    font-size: 0.85rem;
    color: var(--db-text);
    border-bottom: 1px solid var(--db-border);
}

.queue-table tbody tr:hover { background: var(--db-subtle); }

.accession {
    font-family: var(--pm-mono, monospace);
    font-size: 0.8rem;
    font-weight: 500;
    color: var(--db-text-soft);
}

.patient-cell { display: flex; flex-direction: column; }
.patient-name { font-weight: 600; color: var(--db-text); }
.patient-cell small { font-size: 0.65rem; color: var(--db-text-muted); }

.service-tag {
    background: var(--db-subtle);
    color: var(--db-text-soft);
    padding: 0.1rem 0.5rem;
    border-radius: 4px;
    font-size: 0.7rem;
    font-weight: 500;
    display: inline-block;
    margin: 0.1rem;
}

.service-tag.more { background: var(--db-subtle); color: var(--db-text-muted); }

.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 4px;
    font-size: 0.7rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
}

.status-badge.pending     { background: var(--pm-dk-bg-amber, #FFF1E6); color: var(--pm-dk-fg-amber, #C2410C); }
.status-badge.in_progress { background: var(--pm-dk-bg-blue, #E8EFFE); color: var(--pm-dk-fg-blue, #1D4ED8); }
.status-badge.draft       { background: var(--db-subtle); color: var(--db-text-muted); }
.status-badge.completed   { background: var(--pm-dk-bg-green, #E7F6EC); color: var(--pm-dk-fg-green, #15803D); }
.status-badge.released    { background: var(--pm-dk-bg-teal, #ccfbf1); color: #0d9488; }

.action-icons {
    color: var(--db-text-muted);
    margin: 0 0.25rem;
    font-size: 0.95rem;
    transition: color 0.2s ease;
    text-decoration: none;
}

.action-icons:hover { color: var(--db-accent); }

.empty-state { text-align: center; padding: 2rem; }
.empty-state i {
    font-size: 2rem;
    color: var(--db-text-muted);
    margin-bottom: 0.5rem;
    display: block;
}
.empty-state p { color: var(--db-text-muted); font-weight: 500; }

/* ============================================
   CHARTS — intake, turnaround, sections
   One measure, one colour (#175563). Turnaround states use
   the status set, validated together and always shown with
   an icon and words: on time #175563, near #DC6803, over #B42318.
   ============================================ */

.mt-row {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1rem;
    margin-bottom: 1rem;
}

.mt-row--top { grid-template-columns: minmax(0, 1.55fr) minmax(0, 1fr); }

.mt-queue { margin-bottom: 0; }

.mt-muted { color: var(--db-text-muted); }

.mt-count {
    font-size: 0.75rem;
    font-weight: 500;
    color: var(--db-text-muted);
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
}

.mt-empty { height: 200px; }

/* ---- segmented toggle ---- */

.mt-seg {
    display: inline-flex;
    padding: 2px;
    background: var(--db-subtle);
    border: 1px solid var(--db-border);
    border-radius: 7px;
}

.mt-seg-btn {
    height: 28px;
    padding: 0 0.7rem;
    font-size: 0.76rem;
    font-weight: 600;
    color: var(--db-text-soft);
    background: transparent;
    border: 0;
    border-radius: 5px;
    cursor: pointer;
    transition: background-color 0.15s ease, color 0.15s ease;
}

.mt-seg-btn:hover { color: var(--db-text); }

.mt-seg-btn.is-active {
    color: var(--db-text);
    background: var(--db-surface);
    box-shadow: 0 1px 2px rgba(20, 45, 54, 0.12);
}

/* ---- intake summary ---- */

.mt-stats {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 0.75rem;
    margin: 0 0 0.9rem;
}

.mt-stats[hidden] { display: none; }

.mt-stats dt {
    font-size: 0.68rem;
    font-weight: 600;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    color: var(--db-text-muted);
}

.mt-stats dd {
    margin: 0.15rem 0 0;
    font-size: 1.35rem;
    font-weight: 600;
    letter-spacing: -0.02em;
    line-height: 1.15;
    color: var(--db-text);
    font-variant-numeric: tabular-nums;
}

.mt-stats dd small {
    margin-left: 0.2rem;
    font-size: 0.75rem;
    font-weight: 500;
    letter-spacing: 0;
    color: var(--db-text-muted);
}

.mt-intake-body { height: 220px; }

/* ---- turnaround bullet bars ---- */

.mt-tat { display: flex; flex-direction: column; }

.mt-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.15rem 0.5rem;
    font-size: 0.72rem;
    font-weight: 600;
    color: var(--pm-dk-fg-green, #067647);
    background: var(--pm-dk-bg-green, #ECFDF3);
    border-radius: 4px;
    white-space: nowrap;
}

.mt-chip--over { color: var(--pm-dk-fg-red, #B42318); background: var(--pm-dk-bg-red, #FEF3F2); }

.mt-bullets {
    display: flex;
    flex-direction: column;
    gap: 0.85rem;
    margin: 0;
    padding: 0;
    list-style: none;
}

/* More than four sections: show four, scroll the rest. Height is measured
   in JS (--mt-visible) so exactly four rows fit at any font size. */
.mt-bullets.is-capped {
    max-height: var(--mt-visible, 17rem);
    overflow-y: auto;
    overscroll-behavior: contain;
    padding-right: 0.6rem;
    margin-right: -0.6rem;
    scrollbar-width: thin;
    scrollbar-color: var(--pm-line-strong) transparent;
    /* Fade the last row while more is below; JS drops it at the end */
    -webkit-mask-image: linear-gradient(180deg, #000 calc(100% - 2.25rem), transparent);
            mask-image: linear-gradient(180deg, #000 calc(100% - 2.25rem), transparent);
}
.mt-bullets.is-capped.is-end { -webkit-mask-image: none; mask-image: none; }
.mt-bullets.is-capped::-webkit-scrollbar { width: 6px; }
.mt-bullets.is-capped::-webkit-scrollbar-thumb { background: var(--pm-line-strong); border-radius: 3px; }
.mt-bullets.is-capped::-webkit-scrollbar-track { background: transparent; }

.mt-bullet { --state: #175563; }
.mt-bullet--near { --state: #DC6803; }
.mt-bullet--over { --state: #B42318; }

.mt-bullet-head {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: 0.75rem;
    margin-bottom: 0.3rem;
}

.mt-bullet-name {
    min-width: 0;
    font-size: 0.82rem;
    font-weight: 500;
    color: var(--db-text);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.mt-bullet-value {
    font-size: 0.84rem;
    font-weight: 600;
    color: var(--db-text);
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
}

.mt-bullet-track {
    position: relative;
    height: 8px;
    background: var(--db-subtle);
    border-radius: 4px;
}

.mt-bullet-fill {
    display: block;
    height: 100%;
    background: var(--state);
    border-radius: 4px;
}

/* Target: a dark tick standing proud of the track, with a surface ring */
.mt-bullet-target {
    position: absolute;
    top: -3px;
    width: 2px;
    height: 14px;
    margin-left: -1px;
    background: var(--db-text);
    border-radius: 1px;
    box-shadow: 0 0 0 1.5px var(--db-surface);
}

.mt-bullet-foot {
    display: flex;
    justify-content: space-between;
    gap: 0.75rem;
    margin-top: 0.3rem;
    font-size: 0.72rem;
    color: var(--db-text-muted);
    font-variant-numeric: tabular-nums;
}

.mt-bullet-state {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    font-weight: 600;
    color: var(--db-text-soft);
    white-space: nowrap;
}

.mt-bullet--near .mt-bullet-state { color: var(--pm-dk-fg-amber, #B54708); }
.mt-bullet--over .mt-bullet-state { color: var(--pm-dk-fg-red, #B42318); }

.mt-key {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    margin: 1rem 0 0;
    font-size: 0.72rem;
    color: var(--db-text-muted);
}

.mt-key-tick {
    width: 2px;
    height: 12px;
    background: var(--db-text);
    border-radius: 1px;
}

.mt-none {
    margin: 0.85rem 0 0;
    padding-top: 0.75rem;
    font-size: 0.74rem;
    line-height: 1.5;
    color: var(--db-text-muted);
    border-top: 1px solid var(--db-subtle);
}

/* ---- ranked bars (sections) ---- */

.mt-hbars {
    display: flex;
    flex-direction: column;
    gap: 0.6rem;
    margin: 0;
    padding: 0;
    list-style: none;
}

.mt-hbar {
    display: grid;
    grid-template-columns: minmax(90px, 10rem) minmax(0, 1fr) 4.25rem;
    align-items: center;
    gap: 0.75rem;
    font-size: 0.8rem;
}

.mt-hbar-label {
    color: var(--db-text-soft);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.mt-hbar-track {
    height: 8px;
    overflow: hidden;
    background: var(--db-subtle);
    border-radius: 4px;
}

.mt-hbar-fill {
    display: block;
    height: 100%;
    background: #175563;
    border-radius: 4px;
}

.mt-hbar-value {
    font-weight: 600;
    color: var(--db-text);
    text-align: right;
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
}

.mt-hbar-value small {
    margin-left: 0.15rem;
    font-size: 0.7rem;
    font-weight: 500;
    color: var(--db-text-muted);
}

@media (max-width: 1200px) {
    .mt-row,
    .mt-row--top { grid-template-columns: minmax(0, 1fr); }
}

@media (max-width: 576px) {
    .mt-stats dd { font-size: 1.15rem; }
    .mt-hbar { grid-template-columns: 6.5rem minmax(0, 1fr) 3.75rem; }
}

/* ============================================
   RESPONSIVE
   ============================================ */

@media (max-width: 768px) {
    .chart-card,
    .queue-card { padding: 16px; }

    .queue-header {
        flex-direction: column;
        align-items: flex-start;
    }

    .queue-filters { flex-wrap: wrap; }

    .service-list { max-height: 220px; }
}

@media (max-width: 576px) {
    .mt-row { gap: 12px; }

    .chart-body          { height: 220px; }

    .queue-table { font-size: 0.75rem; }
    .queue-table thead th,
    .queue-table tbody td { padding: 0.5rem; }

    .service-list { max-height: 200px; }
}

@media (prefers-reduced-motion: reduce) {
    .dashboard-wrapper * {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
}
</style>

<!-- Chart.js (pinned so a new major version cannot silently break the chart) -->
<script src="<?= base_url('assets/vendor/chartjs/chart.umd.min.js') ?>"></script>

<script>
(function () {
    'use strict';

    var RANGES = {
        today: {
            labels: <?= json_encode($todayLabels ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
            values: <?= json_encode($todayValues ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
            current: <?= (int) ($todayNowIx ?? -1) ?>,
            title: function (label) { return label; }
        },
        month: {
            labels: <?= json_encode($monthLabels ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
            values: <?= json_encode($monthValues ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
            current: <?= (int) (($todayDay ?? 0) - 1) ?>,
            title: function (label) { return label; }
        }
    };

    /* One hue: the current hour/day is the darker step; both clear 3:1 on white */
    var BAR   = '#10404B';
    var SOFT  = '#4C8D9B';
    var INK   = '#10202E';
    var MUTED = '#5D6E80';
    var FAINT = '#8496A8';

    var chart = null;

    function colours(range) {
        return range.values.map(function (_, i) { return i === range.current ? BAR : SOFT; });
    }

    function build(name) {
        var range  = RANGES[name];
        var canvas = document.getElementById('intakeChart');
        if (!canvas || typeof Chart === 'undefined' || !range) { return; }

        if (chart) {
            chart.data.labels = range.labels;
            chart.data.datasets[0].data = range.values;
            chart.data.datasets[0].backgroundColor = colours(range);
            chart.$range = range;
            chart.update();
            return;
        }

        /* SPA page swaps re-run this script; free a chart left on the canvas */
        var stale = Chart.getChart ? Chart.getChart(canvas) : null;
        if (stale) { stale.destroy(); }

        chart = new Chart(canvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels: range.labels,
                datasets: [{
                    label: 'Specimens',
                    data: range.values,
                    backgroundColor: colours(range),
                    hoverBackgroundColor: '#063F6E',
                    borderRadius: { topLeft: 4, topRight: 4 },
                    borderSkipped: 'bottom',
                    borderWidth: { left: 1, right: 1 },   /* 2px surface gap between bars */
                    borderColor: '#FFFFFF',
                    categoryPercentage: 0.9,
                    barPercentage: 0.95,
                    maxBarThickness: 22
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches ? false : { duration: 350 },
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#FFFFFF',
                        titleColor: INK,
                        bodyColor: MUTED,
                        borderColor: '#DDE4EC',
                        borderWidth: 1,
                        padding: 10,
                        cornerRadius: 6,
                        displayColors: false,
                        titleFont: { size: 12, weight: '600' },
                        bodyFont: { size: 12 },
                        filter: function (item) { return item.raw !== null; },
                        callbacks: {
                            label: function (ctx) {
                                var n = ctx.parsed.y || 0;
                                var tag = ctx.dataIndex === (chart.$range || range).current ? ' · now' : '';
                                return n + (n === 1 ? ' specimen' : ' specimens') + tag;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        border: { color: '#DDE4EC' },
                        ticks: { color: FAINT, font: { size: 11 }, maxRotation: 0, autoSkip: true, autoSkipPadding: 14 }
                    },
                    y: {
                        beginAtZero: true,
                        grace: '10%',
                        grid: { color: '#EBF0F5', drawTicks: false },
                        border: { display: false },
                        ticks: { color: FAINT, font: { size: 11 }, precision: 0, padding: 8, maxTicksLimit: 4 }
                    }
                }
            }
        });
        chart.$range = range;
    }

    /* Today / This month toggle: swaps the data and the summary row */
    document.querySelectorAll('.mt-seg-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var name = btn.dataset.range;
            document.querySelectorAll('.mt-seg-btn').forEach(function (b) {
                var on = b === btn;
                b.classList.toggle('is-active', on);
                b.setAttribute('aria-selected', on ? 'true' : 'false');
            });
            document.querySelectorAll('[data-range-panel]').forEach(function (panel) {
                panel.hidden = panel.dataset.rangePanel !== name;
            });
            build(name);
        });
    });

    function start() {
        if (typeof Chart !== 'undefined') { build('today'); return; }
        var tag = document.createElement('script');
        tag.src = '<?= base_url('assets/vendor/chartjs/chart.umd.min.js') ?>';
        tag.onload = function () { build('today'); };
        document.head.appendChild(tag);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }
})();
</script>

<script>
// ===== QUEUE FILTER =====
document.querySelectorAll('.filter-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
        document.querySelectorAll('.filter-btn').forEach(function (b) { b.classList.remove('active'); });
        this.classList.add('active');

        var filter = this.dataset.filter;
        var rows = document.querySelectorAll('#queueTable .queue-row');

        rows.forEach(function (row) {
            var tags = ' ' + (row.dataset.statuses || row.dataset.status || '').toLowerCase() + ' ';
            row.style.display = (filter === 'all' || tags.indexOf(' ' + filter + ' ') !== -1) ? '' : 'none';
        });
    });
});

// ===== REFRESH =====
function refreshTable() {
    var btn = document.querySelector('.refresh-btn');
    var icon = btn.querySelector('i');
    icon.style.animation = 'spin 0.8s linear infinite';

    setTimeout(function () {
        icon.style.animation = 'none';
        location.reload();
    }, 800);
}

// ===== Spin animation =====
var spinStyle = document.createElement('style');
spinStyle.textContent = '@keyframes spin { to { transform: rotate(360deg); } }';
document.head.appendChild(spinStyle);
</script>

<?= $this->endSection() ?>