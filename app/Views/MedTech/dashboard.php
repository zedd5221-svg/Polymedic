<?= $this->extend('Layouts/MedTechLayout') ?>

<?= $this->section('pageTitle') ?>MedTech Dashboard<?= $this->endSection() ?>

<?= $this->section('medtechContent') ?>

<?php
/* ------------------------------------------------------------------
   MedTech Dashboard

   KPI board is the shared Admin design (partials/kpi_board).
   Two compact specimen intake charts (Today / This month).
   Volume by Section chart, plus a scrollable "Available services"
   card listing every active laboratory service.
   Turnaround Time panel with bars calibrated to the worst performer.
   ------------------------------------------------------------------ */

/* ------------------------------------------------------------------
   KPI BOARD (shared partial: partials/kpi_board, same as Admin)
   ------------------------------------------------------------------ */
$mtCount = static fn($k) => (int) ($counts[$k] ?? 0);
$mtActive = $mtCount('pending') + $mtCount('in_progress') + $mtCount('draft');

$kpiBoard = [
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
            ['key' => 'in_progress', 'color' => '#3b82f6', 'label' => 'In-Progress', 'hint' => 'Currently running',    'count' => $mtCount('in_progress'), 'today' => 'new today', 'link' => base_url('medtech/requests?status=in_progress')],
            ['key' => 'draft',       'color' => '#8b5cf6', 'label' => 'Drafts',      'hint' => 'Saved, not submitted', 'count' => $mtCount('draft'),       'today' => 'new today', 'link' => base_url('medtech/requests?status=draft')],
        ],
    ],
];

/* Turnaround Time — calibrated bars. */
$tatRows = $turnaroundData ?? [];

$tatWithData = array_values(array_filter($tatRows, static function ($t) {
    return (float) ($t['avg'] ?? 0) > 0;
}));

$tatMaxAvg = 0.0;
foreach ($tatWithData as $t) {
    $tatMaxAvg = max($tatMaxAvg, (float) $t['avg']);
}

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

    <!-- ===== SPECIMEN INTAKE — TWO COMPACT CHARTS ===== -->
    <div class="intake-row">

        <div class="chart-card chart-card--compact">
            <div class="chart-header">
                <div>
                    <h5>Specimen intake — today</h5>
                    <small>Hourly volume received</small>
                </div>
                <span class="date-badge"><?= date('M j, Y') ?></span>
            </div>
            <div class="chart-body chart-body--compact">
                <canvas id="intakeChart"></canvas>
            </div>
        </div>

        <div class="chart-card chart-card--compact">
            <div class="chart-header">
                <div>
                    <h5>Specimen intake — this month</h5>
                    <small>Daily volume received</small>
                </div>
                <span class="date-badge"><?= date('F Y') ?></span>
            </div>
            <div class="chart-body chart-body--compact">
                <canvas id="intakeMonthChart"></canvas>
            </div>
        </div>

    </div>

    <!-- ===== VOLUME BY SECTION + AVAILABLE SERVICES ===== -->
    <div class="charts-row">
        <div class="chart-card">
            <div class="chart-header">
                <div>
                    <h5>Volume by Section</h5>
                    <small>Tests today per laboratory section</small>
                </div>
            </div>
            <div class="chart-body">
                <canvas id="sectionChart"></canvas>
            </div>
        </div>

        <div class="chart-card chart-card--note">
            <div class="chart-header">
                <div>
                    <h5>Available services</h5>
                    <small>Active laboratory tests (<?= number_format((int) $catalog['labCount']) ?>)</small>
                </div>
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
        </div>
    </div>

    <!-- ===== SPECIMEN QUEUE + TURNAROUND ===== -->
    <div class="lower-row">
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

        <!-- ===== TURNAROUND TIME ===== -->
        <div class="tat-card">
            <div class="tat-header">
                <div>
                    <h5>Turnaround Time</h5>
                    <small>Avg vs target (from released results)</small>
                </div>
                <span class="tat-badge"><?= count($tatWithData) ?> active</span>
            </div>

            <div class="tat-body">
                <?php if (!empty($tatRows)): ?>
                    <?php foreach ($tatRows as $test):
                        $avg     = (float) ($test['avg'] ?? 0);
                        $target  = (float) ($test['target'] ?? 0);
                        $tone    = $tatToneOf($test);
                        $hasData = $avg > 0;

                        $share = ($hasData && $tatMaxAvg > 0)
                            ? round(($avg / $tatMaxAvg) * 100, 2)
                            : 0;
                    ?>
                        <?php if ($hasData): ?>
                            <div class="tat-row tat-row--<?= esc($tone, 'attr') ?>">
                                <div class="tat-row-head">
                                    <span class="tat-name"><?= esc($test['name'] ?? '') ?></span>
                                    <span class="tat-value tat-value--<?= esc($tone, 'attr') ?>">
                                        <?= (int) $avg ?> min
                                    </span>
                                </div>
                                <div class="tat-bar-track" aria-hidden="true">
                                    <div class="tat-bar-fill tat-bar-fill--<?= esc($tone, 'attr') ?>"
                                         style="width: <?= $share ?>%;"></div>
                                </div>
                                <div class="tat-row-foot">
                                    <span class="tat-target">Target <?= (int) $target ?> min</span>
                                    <?php
                                    $delta = $avg - $target;
                                    $deltaLabel = $delta >= 0
                                        ? '+' . (int) $delta . ' min over'
                                        : (int) abs($delta) . ' min under';
                                    ?>
                                    <span class="tat-delta tat-delta--<?= esc($tone, 'attr') ?>"><?= $deltaLabel ?></span>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="tat-row tat-row--empty">
                                <span class="tat-name"><?= esc($test['name'] ?? '') ?></span>
                                <span class="tat-value tat-value--muted">No released results yet</span>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="tat-row tat-row--empty">
                        <span class="tat-name">No turnaround data</span>
                        <span class="tat-value tat-value--muted">—</span>
                    </div>
                <?php endif; ?>

                <ul class="tat-legend" aria-label="Turnaround legend">
                    <li><span class="tat-dot tat-dot--green"></span> On time</li>
                    <li><span class="tat-dot tat-dot--orange"></span> Near limit</li>
                    <li><span class="tat-dot tat-dot--red"></span> Exceeded</li>
                </ul>
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
    --db-accent-soft:   #E6F7F7;
    --db-surface:       #FFFFFF;
    --db-canvas:        #F8FAFC;
    --db-subtle:        #F1F5F9;
    --db-border:        #E2E8F0;
    --db-border-strong: #CBD5E1;
    --db-text:          #0F172A;
    --db-text-soft:     #475569;
    --db-text-muted:    #94A3B8;
    --db-radius:        16px;
    --db-radius-sm:     10px;
    --db-radius-xs:     6px;
    --db-shadow:        0 1px 2px rgba(15, 23, 42, 0.04), 0 4px 14px -6px rgba(15, 23, 42, 0.06);
    --db-shadow-hover:  0 2px 4px rgba(15, 23, 42, 0.04), 0 14px 28px -10px rgba(15, 23, 42, 0.16);
    --db-gap:           20px;

    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    background:
        radial-gradient(900px 320px at 8% -80px, rgba(13, 148, 136, 0.08), transparent 70%),
        radial-gradient(700px 300px at 100% -60px, rgba(37, 99, 235, 0.05), transparent 70%),
        var(--db-canvas);
    padding: 24px;
    min-height: 100vh;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
    color: var(--db-text);
}

.dashboard-wrapper :focus-visible {
    outline: 2px solid var(--db-accent);
    outline-offset: 2px;
    border-radius: var(--db-radius-xs);
}

/* ===== INTAKE ROW ===== */

.intake-row {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1rem;
    margin-bottom: 1rem;
}

.chart-card {
    background: var(--db-surface);
    border-radius: 16px;
    padding: 1.25rem 1.5rem;
    box-shadow: 0 1px 3px rgba(16, 24, 40, 0.06);
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
    font-weight: 700;
    color: var(--db-text);
    margin: 0 0 0.15rem;
    font-size: 0.95rem;
}

.chart-header small {
    color: var(--db-text-muted);
    font-size: 0.78rem;
    font-weight: 400;
}

.date-badge {
    background: #E8EFFE;
    color: #1D4ED8;
    padding: 0.2rem 0.65rem;
    border-radius: 30px;
    font-size: 0.75rem;
    font-weight: 600;
    white-space: nowrap;
}

.chart-body {
    position: relative;
    height: 300px;
}

.chart-body--compact { height: 180px; }

/* ===== CHARTS ROW ===== */

.charts-row {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 1rem;
    margin-bottom: 1rem;
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
.service-list::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
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

.service-list-item:hover { background: #eef2f7; }

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

/* ===== LOWER ROW ===== */

.lower-row {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 1rem;
}

/* ===== QUEUE CARD ===== */

.queue-card {
    background: var(--db-surface);
    border-radius: 16px;
    padding: 1.25rem 1.5rem;
    box-shadow: 0 1px 3px rgba(16, 24, 40, 0.06);
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
    font-weight: 700;
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
    border-radius: 30px;
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--db-text-muted);
    cursor: pointer;
    transition: all 0.2s ease;
}

.filter-btn:hover { background: var(--db-subtle); }
.filter-btn.active { background: #1D4ED8; color: #ffffff; }

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
    font-family: 'SFMono-Regular', Consolas, monospace;
    font-size: 0.8rem;
    font-weight: 600;
    color: #1D4ED8;
}

.patient-cell { display: flex; flex-direction: column; }
.patient-name { font-weight: 600; color: var(--db-text); }
.patient-cell small { font-size: 0.65rem; color: var(--db-text-muted); }

.service-tag {
    background: #E8EFFE;
    color: #1D4ED8;
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
    border-radius: 30px;
    font-size: 0.7rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
}

.status-badge.pending     { background: #FFF1E6; color: #C2410C; }
.status-badge.in_progress { background: #E8EFFE; color: #1D4ED8; }
.status-badge.draft       { background: var(--db-subtle); color: var(--db-text-muted); }
.status-badge.completed   { background: #E7F6EC; color: #15803D; }
.status-badge.released    { background: #ccfbf1; color: #0d9488; }

.action-icons {
    color: var(--db-text-muted);
    margin: 0 0.25rem;
    font-size: 0.95rem;
    transition: color 0.2s ease;
    text-decoration: none;
}

.action-icons:hover { color: #1D4ED8; }

.empty-state { text-align: center; padding: 2rem; }
.empty-state i {
    font-size: 2rem;
    color: var(--db-text-muted);
    margin-bottom: 0.5rem;
    display: block;
}
.empty-state p { color: var(--db-text-muted); font-weight: 500; }

/* ============================================
   TURNAROUND TIME
   ============================================ */

.tat-card {
    background: var(--db-surface);
    border-radius: 16px;
    padding: 1.25rem 1.5rem;
    box-shadow: 0 1px 3px rgba(16, 24, 40, 0.06);
    border: 1px solid var(--db-border);
}

.tat-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.tat-header h5 {
    font-weight: 700;
    color: var(--db-text);
    margin: 0 0 0.15rem;
    font-size: 1rem;
}

.tat-header small { color: var(--db-text-muted); font-size: 0.8rem; }

.tat-badge {
    display: inline-flex;
    align-items: center;
    height: 22px;
    padding: 0 0.5rem;
    font-size: 0.7rem;
    font-weight: 600;
    color: var(--db-text-soft);
    background: var(--db-subtle);
    border-radius: 999px;
    white-space: nowrap;
}

.tat-body {
    display: flex;
    flex-direction: column;
    gap: 0.85rem;
}

.tat-row {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
    padding: 0.55rem 0.7rem;
    border-radius: var(--db-radius-sm);
    background: var(--db-canvas);
    border: 1px solid var(--db-border);
}

.tat-row-head {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: 0.5rem;
}

.tat-name {
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--db-text);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    min-width: 0;
}

.tat-value {
    font-size: 0.85rem;
    font-weight: 700;
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
}

.tat-value--green  { color: #15803D; }
.tat-value--orange { color: #C2410C; }
.tat-value--red    { color: #b91c1c; }
.tat-value--muted  { color: var(--db-text-muted); font-weight: 500; font-size: 0.78rem; }

.tat-bar-track {
    height: 6px;
    background: #e5e9ed;
    border-radius: 3px;
    overflow: hidden;
}

.tat-bar-fill {
    height: 100%;
    border-radius: 3px;
    transition: width 0.6s ease;
}

.tat-bar-fill--green  { background: #10b981; }
.tat-bar-fill--orange { background: #f59e0b; }
.tat-bar-fill--red    { background: #dc2626; }

.tat-row-foot {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    font-size: 0.7rem;
}

.tat-target { color: var(--db-text-muted); }

.tat-delta { font-weight: 600; white-space: nowrap; }
.tat-delta--green  { color: #15803D; }
.tat-delta--orange { color: #C2410C; }
.tat-delta--red    { color: #b91c1c; }

.tat-row--empty {
    flex-direction: row;
    align-items: center;
    justify-content: space-between;
    padding: 0.4rem 0.7rem;
    background: transparent;
    border-color: transparent;
}

.tat-row--empty + .tat-row--empty { margin-top: -0.35rem; }

.tat-row--empty .tat-name {
    font-weight: 500;
    color: var(--db-text-muted);
    font-size: 0.8rem;
}

.tat-legend {
    list-style: none;
    margin: 0.85rem 0 0;
    padding: 0.85rem 0 0;
    border-top: 1px solid var(--db-border);
    display: flex;
    flex-wrap: wrap;
    gap: 0.9rem;
    font-size: 0.72rem;
    color: var(--db-text-muted);
}

.tat-legend li {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
}

.tat-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    flex-shrink: 0;
}

.tat-dot--green  { background: #10b981; }
.tat-dot--orange { background: #f59e0b; }
.tat-dot--red    { background: #dc2626; }

/* ============================================
   RESPONSIVE
   ============================================ */

@media (max-width: 1200px) {
    .charts-row,
    .lower-row  { grid-template-columns: minmax(0, 1fr); }
}

@media (max-width: 992px) {
    .dashboard-wrapper { padding: 16px; }
    .intake-row { grid-template-columns: minmax(0, 1fr); }
}

@media (max-width: 768px) {
    .chart-card,
    .queue-card,
    .tat-card          { padding: 16px; }

    .queue-header {
        flex-direction: column;
        align-items: flex-start;
    }

    .queue-filters { flex-wrap: wrap; }

    .service-list { max-height: 220px; }
}

@media (max-width: 576px) {
    .dashboard-wrapper { padding: 12px; }
    .charts-row,
    .lower-row,
    .intake-row { gap: 12px; }

    .chart-body          { height: 220px; }
    .chart-body--compact { height: 160px; }

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

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {

    var AXIS_TICK = { color: '#94A3B8', font: { size: 10 }, padding: 6 };
    var GRID      = { color: '#F0F2F5', drawBorder: false, drawTicks: false };

    // ===== 1. SPECIMEN INTAKE — TODAY =====
    var intakeCanvas = document.getElementById('intakeChart');
    if (intakeCanvas) {
        var intakeLabels = <?= json_encode($intakeData['labels'] ?? []) ?>;
        var intakeValues = <?= json_encode($intakeData['data'] ?? []) ?>;

        var labels = intakeLabels.length > 0
            ? intakeLabels
            : Array.from({ length: 24 }, function (_, i) { return String(i).padStart(2, '0') + ':00'; });

        var values = intakeValues.length > 0 ? intakeValues : Array(24).fill(0);

        new Chart(intakeCanvas.getContext('2d'), {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Specimens',
                    data: values,
                    borderColor: '#1D4ED8',
                    backgroundColor: 'rgba(29, 78, 216, 0.08)',
                    fill: true,
                    tension: 0.35,
                    borderWidth: 2,
                    pointRadius: 2,
                    pointHoverRadius: 5,
                    pointBackgroundColor: '#1D4ED8',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#ffffff',
                        titleColor: '#0f172a',
                        bodyColor: '#0f172a',
                        borderColor: '#e2e8f0',
                        borderWidth: 1,
                        padding: 8,
                        cornerRadius: 6,
                        displayColors: false,
                        callbacks: {
                            label: function (ctx) {
                                var v = ctx.parsed.y || 0;
                                return v + (v === 1 ? ' specimen' : ' specimens');
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: GRID,
                        border: { display: false },
                        ticks: Object.assign({}, AXIS_TICK, { stepSize: 1, maxTicksLimit: 4 })
                    },
                    x: {
                        grid: { display: false },
                        border: { display: false },
                        ticks: Object.assign({}, AXIS_TICK, { maxTicksLimit: 8, maxRotation: 0, autoSkip: true })
                    }
                }
            }
        });
    }

    // ===== 2. SPECIMEN INTAKE — THIS MONTH =====
    var intakeMonthCanvas = document.getElementById('intakeMonthChart');
    if (intakeMonthCanvas) {
        var monthLabels = <?= json_encode($intakeMonthData['labels'] ?? []) ?>;
        var monthValues = <?= json_encode($intakeMonthData['data']   ?? []) ?>;

        if (!monthLabels.length) {
            var days = new Date(new Date().getFullYear(), new Date().getMonth() + 1, 0).getDate();
            monthLabels = Array.from({ length: days }, function (_, i) { return String(i + 1); });
            monthValues = Array(days).fill(0);
        }

        new Chart(intakeMonthCanvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels: monthLabels,
                datasets: [{
                    label: 'Specimens',
                    data: monthValues,
                    backgroundColor: '#0D9488',
                    hoverBackgroundColor: '#0F766E',
                    borderRadius: 4,
                    borderSkipped: false,
                    maxBarThickness: 14
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#ffffff',
                        titleColor: '#0f172a',
                        bodyColor: '#0f172a',
                        borderColor: '#e2e8f0',
                        borderWidth: 1,
                        padding: 8,
                        cornerRadius: 6,
                        displayColors: false,
                        callbacks: {
                            title: function (items) {
                                return 'Day ' + items[0].label;
                            },
                            label: function (ctx) {
                                var v = ctx.parsed.y || 0;
                                return v + (v === 1 ? ' specimen' : ' specimens');
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: GRID,
                        border: { display: false },
                        ticks: Object.assign({}, AXIS_TICK, { stepSize: 1, maxTicksLimit: 4 })
                    },
                    x: {
                        grid: { display: false },
                        border: { display: false },
                        ticks: Object.assign({}, AXIS_TICK, {
                            maxTicksLimit: 10,
                            maxRotation: 0,
                            autoSkip: true
                        })
                    }
                }
            }
        });
    }

    // ===== 3. VOLUME BY SECTION =====
    var sectionCanvas = document.getElementById('sectionChart');
    if (sectionCanvas) {
        var sectionLabels = <?= json_encode($sectionData['labels'] ?? ['No Data']) ?>;
        var sectionValues = <?= json_encode($sectionData['data']   ?? [0]) ?>;
        var maxValue = Math.max.apply(null, sectionValues.concat([1]));

        new Chart(sectionCanvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels: sectionLabels,
                datasets: [{
                    label: 'Tests',
                    data: sectionValues,
                    backgroundColor: '#1D4ED8',
                    hoverBackgroundColor: '#1e40af',
                    borderRadius: 4,
                    barThickness: 16
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#ffffff',
                        titleColor: '#0f172a',
                        bodyColor: '#0f172a',
                        borderColor: '#e2e8f0',
                        borderWidth: 1,
                        padding: 8,
                        cornerRadius: 6,
                        displayColors: false,
                        callbacks: {
                            label: function (ctx) {
                                var v = ctx.parsed.x || 0;
                                return v + (v === 1 ? ' test' : ' tests');
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        max: maxValue + 3,
                        grid: GRID,
                        border: { display: false },
                        ticks: Object.assign({}, AXIS_TICK, { stepSize: 1 })
                    },
                    y: {
                        grid: { display: false },
                        border: { display: false },
                        ticks: { color: '#374151', font: { size: 11, weight: '600' } }
                    }
                }
            }
        });
    }

});

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