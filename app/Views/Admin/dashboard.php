<?= $this->extend('Layouts/AdminLayout') ?>

<?= $this->section('pageTitle') ?>Dashboard<?= $this->endSection() ?>

<?= $this->section('adminContent') ?>

<?php
/* ------------------------------------------------------------------
   Safe local defaults. These do not change any controller logic,
   they only stop the view from failing when a variable is missing.
   ------------------------------------------------------------------ */
$revenueDataArr  = $revenueData  ?? ['labels' => [], 'values' => []];
$visitsDataArr   = $visitsData   ?? ['labels' => [], 'lab' => [], 'xray' => []];
$requestsDataArr = $requestsData ?? ['labels' => [], 'requested' => [], 'completed' => []];

$hasVisitsChart   = !empty($visitsDataArr['labels'])
                    && (!empty($visitsDataArr['lab']) || !empty($visitsDataArr['xray']));

/* Pending appointments shown in the mini approval panel. */
$pendingAppointmentsArr = $pendingAppointments ?? [];
$pendingPerPage         = 4;
$pendingTotal           = count($pendingAppointmentsArr);
$pendingPages           = (int) max(1, ceil($pendingTotal / $pendingPerPage));
$hasRequestsChart = !empty($requestsDataArr['labels']) && !empty($requestsDataArr['requested']);

$monthlyRevenueVal = (float) ($monthlyRevenue ?? 0);
$avgMonthlyRevenue = array_sum($revenueDataArr['values']) / max(1, count($revenueDataArr['values']));

$jsonFlags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;

/* ------------------------------------------------------------------
   KPI BOARD (shared partial: partials/kpi_board)
   ------------------------------------------------------------------ */
$pendingRequests    = (int) ($pendingRequests ?? 0);
$inProgressRequests = (int) ($inProgressRequests ?? 0);
$completedRequests = (int) ($completedRequests ?? 0);
$releasedResults   = (int) ($releasedResults ?? 0);

$kpiBoard = [
    'chart'    => 'spike',
    'featured' => true,
    'trends' => $kpiTrends ?? [],
    'cards'  => [
        [
            'key'     => 'patients',
            'icon'    => 'people-blue.png',
            'color'   => '#175563',
            'label'   => 'Total Patients',
            'value'   => (int) ($totalPatients ?? 0),
            'delta'   => 'window',
            'context' => 'new in 14 days',
            'unit'    => 'patients',
            'link'    => base_url('admin/patients'),
        ],
        [
            'key'     => 'today',
            'icon'    => 'document.png',
            'color'   => '#0891b2',
            'label'   => "Today's Patients",
            'value'   => (int) ($todayPatients ?? 0),
            'delta'   => 'dod',
            'context' => 'vs yesterday',
            'unit'    => 'appointments',
            'link'    => base_url('admin/appointments'),
        ],
        [
            'key'     => 'revenue_today',
            'icon'    => 'money-yellow.png',
            'color'   => '#059669',
            'label'   => "Today's Revenue",
            'value'   => (float) ($todayRevenue ?? 0),
            'money'   => true,
            'delta'   => 'dod',
            'context' => 'vs yesterday',
            'unit'    => 'collected',
            'link'    => base_url('admin/payments'),
        ],
        [
            'key'     => 'revenue_month',
            'icon'    => 'money-yellow.png',
            'color'   => '#7c3aed',
            'label'   => 'Monthly Revenue',
            'value'   => $monthlyRevenueVal,
            'money'   => true,
            'delta'   => null,
            'context' => '≈ ₱' . number_format($monthlyRevenueVal / max(1, (int) date('j')), 2) . '/day · ' . date('F'),
            'unit'    => 'month-to-date',
            'link'    => base_url('admin/billing/reports'),
        ],
    ],
    /* Request statuses are mutually exclusive, so the share bar is valid. */
    'pipeline' => [
        'title'  => 'Diagnostic Pipeline',
        'sub'    => number_format($pendingRequests + $inProgressRequests + $completedRequests + $releasedResults) . ' requests in the workflow',
        'link'   => base_url('admin/diagnostic-requests'),
        'split'  => true,
        'stages' => [
            ['key' => 'pending',     'color' => '#f59e0b', 'label' => 'Pending',     'hint' => 'Awaiting the front desk',       'count' => $pendingRequests,    'today' => 'new today',  'link' => base_url('admin/diagnostic-requests')],
            ['key' => 'in_progress', 'color' => '#8b5cf6', 'label' => 'In Progress', 'hint' => 'Being processed, drafts included', 'count' => $inProgressRequests, 'today' => 'new today',  'link' => base_url('admin/diagnostic-requests')],
            ['key' => 'completed',   'color' => '#10b981', 'label' => 'Completed',   'hint' => 'Results encoded',               'count' => $completedRequests,  'today' => 'done today', 'link' => base_url('admin/diagnostic-requests')],
            ['key' => 'released',    'color' => '#3b82f6', 'label' => 'Released',    'hint' => 'Sent to doctors',               'count' => $releasedResults,    'today' => 'sent today', 'link' => base_url('admin/diagnostic-requests')],
        ],
    ],
];
?>

<div class="dashboard-wrapper">

    <!-- ===== KPI OVERVIEW ===== -->
    <?= view('partials/kpi_board', $kpiBoard) ?>

    <!-- ===== MAIN ROW: Revenue Chart + Pending Appointments ===== -->
    <div class="main-row">

        <!-- Revenue Overview Chart -->
        <div class="chart-card">
            <div class="card-header">
                <div class="card-title-group">
                    <h5 class="card-title">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <line x1="18" y1="20" x2="18" y2="10"></line>
                            <line x1="12" y1="20" x2="12" y2="4"></line>
                            <line x1="6" y1="20" x2="6" y2="14"></line>
                            <line x1="3" y1="20" x2="21" y2="20"></line>
                        </svg>
                        Revenue Overview
                    </h5>
                    <span class="card-subtitle" id="revSubtitle">Monthly revenue <?= date('Y') ?></span>
                </div>
                <label class="visually-hidden" for="revRange">Revenue period</label>
                <select class="rev-range" id="revRange">
                    <option value="year" selected>This year</option>
                    <option value="days">Last 14 days</option>
                </select>
            </div>

            <div class="chart-container rev-box">
                <div class="rev-summary">
                    <div>
                        <span class="rev-total-label">Total income</span>
                        <span class="rev-total" id="revTotal">&#8369;<?= number_format(array_sum($revenueDataArr['values']), 2) ?></span>
                    </div>
                    <span class="rev-unit" id="revUnit"><span class="rev-unit-cell" aria-hidden="true"></span><span id="revUnitText"></span></span>
                    <div class="avg-box">
                        <span class="avg-label" id="revAvgLabel">Avg. monthly</span>
                        <span class="avg-value" id="revAvg">&#8369;<?= number_format($avgMonthlyRevenue, 2) ?></span>
                    </div>
                </div>
                <div class="rev-matrix" id="revMatrix" role="img" aria-label="Revenue per period"></div>
                <div class="rev-labels" id="revLabels" aria-hidden="true"></div>
            </div>
        </div>

        <!-- Pending Appointments - approve without leaving the dashboard -->
        <div class="chart-card pending-card">
            <div class="card-header">
                <div class="card-title-group">
                    <h5 class="card-title">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                        Pending Appointments
                    </h5>
                    <span class="card-subtitle">Approve without leaving this page</span>
                </div>
                <?php if ($pendingTotal > 0): ?>
                    <span class="badge-time badge-pending"><?= (int) $pendingTotal ?> waiting</span>
                <?php endif; ?>
            </div>

            <?php if ($pendingTotal > 0): ?>

                <div class="pending-list" id="pendingList">
                    <?php foreach ($pendingAppointmentsArr as $i => $appt): ?>
                        <?php
                        $apptId   = (int) ($appt['id'] ?? 0);
                        $apptName = (string) ($appt['full_name'] ?? 'Unknown patient');
                        $apptRef  = (string) ($appt['reference_number'] ?? ('#' . $apptId));
                        $apptDate = !empty($appt['appointment_date'])
                            ? date('M j', strtotime($appt['appointment_date']))
                            : '';
                        $apptTime = !empty($appt['appointment_time'])
                            ? date('g:i A', strtotime($appt['appointment_time']))
                            : '';
                        $svcType  = strtolower((string) ($appt['service_type'] ?? ''));

                        /* Initials keep the row readable without an avatar image. */
                        $parts    = preg_split('/\s+/', trim($apptName));
                        $initials = mb_strtoupper(
                            mb_substr($parts[0] ?? '', 0, 1) .
                            (count($parts) > 1 ? mb_substr(end($parts), 0, 1) : '')
                        );
                        if ($initials === '') { $initials = '?'; }
                        ?>
                        <article class="pending-item"
                                 data-page="<?= (int) floor($i / $pendingPerPage) ?>"
                                 <?= $i >= $pendingPerPage ? 'hidden' : '' ?>>

                            <span class="pending-avatar" aria-hidden="true"><?= esc($initials) ?></span>

                            <div class="pending-body">
                                <div class="pending-name-row">
                                    <span class="pending-name"><?= esc($apptName) ?></span>
                                    <span class="pending-ref"><?= esc($apptRef) ?></span>
                                </div>
                                <div class="pending-meta">
                                    <?php if ($apptDate !== ''): ?>
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <polyline points="12 6 12 12 16 14"></polyline>
                                        </svg>
                                        <?= esc($apptDate) ?><?= $apptTime !== '' ? ' &middot; ' . esc($apptTime) : '' ?>
                                    <?php endif; ?>
                                    <?php if ($svcType !== ''): ?>
                                        <span class="pending-tag pending-tag--<?= esc($svcType, 'attr') ?>"><?= esc(ucfirst($svcType)) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="pending-actions">
                                <a href="<?= base_url('admin/appointment/view/' . $apptId) ?>"
                                   class="pending-btn pending-btn--ghost"
                                   title="View details">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                    <span class="visually-hidden">View <?= esc($apptName) ?></span>
                                </a>
                                <a data-post href="<?= base_url('admin/appointment/approve/' . $apptId) ?>?from=dashboard"
                                   class="pending-btn pending-btn--approve"
                                   data-name="<?= esc($apptName, 'attr') ?>"
                                   data-confirm-approve>
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <polyline points="20 6 9 17 4 12"></polyline>
                                    </svg>
                                    <span>Approve</span>
                                </a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <?php if ($pendingPages > 1): ?>
                    <div class="pending-pager">
                        <button type="button" class="pager-btn" id="pendingPrev" aria-label="Previous page" disabled>
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <polyline points="15 18 9 12 15 6"></polyline>
                            </svg>
                        </button>
                        <span class="pager-status">
                            Page <span id="pendingPageNum">1</span> of <?= (int) $pendingPages ?>
                        </span>
                        <button type="button" class="pager-btn" id="pendingNext" aria-label="Next page">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <polyline points="9 18 15 12 9 6"></polyline>
                            </svg>
                        </button>
                    </div>
                <?php endif; ?>

                <a href="<?= base_url('admin/appointments') ?>" class="pending-all">
                    View all appointments
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                </a>

            <?php else: ?>
                <div class="empty-state">
                    <p>No appointments waiting for approval</p>
                    <a href="<?= base_url('admin/appointments') ?>" class="pending-all">
                        View all appointments
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ===== BOTTOM ROW: Daily Patient Visits + Diagnostic Requests + Top Lab Tests ===== -->
    <div class="bottom-row">

        <!-- Daily Patient Visits -->
        <div class="chart-card">
            <div class="card-header">
                <div class="card-title-group">
                    <h5 class="card-title">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                        Department Patient Volume
                    </h5>
                    <span class="card-subtitle">Laboratory vs X-Ray</span>
                </div>
                <span class="badge-time">This Week</span>
            </div>
            <?php if ($hasVisitsChart): ?>
                <div class="chart-container">
                    <canvas id="visitsChart"></canvas>
                </div>
            <?php else: ?>
                <div class="empty-state empty-state-chart">
                    <p>No department activity this week</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Diagnostic Requests -->
        <div class="chart-card">
            <div class="card-header">
                <div class="card-title-group">
                    <h5 class="card-title">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M22 12h-4l-3 9L9 3l-3 9H2"></path>
                        </svg>
                        Diagnostic Requests
                    </h5>
                    <span class="card-subtitle">Requested vs Completed</span>
                </div>
                <span class="badge-time">This Week</span>
            </div>
            <?php if ($hasRequestsChart): ?>
                <div class="chart-container">
                    <canvas id="requestsChart"></canvas>
                </div>
            <?php else: ?>
                <div class="empty-state empty-state-chart">
                    <p>No diagnostic requests this week</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Top Lab Tests -->
        <div class="chart-card">
            <div class="card-header">
                <div class="card-title-group">
                    <h5 class="card-title">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M9 3h6"></path>
                            <path d="M10 3v6.5L4.5 18a2 2 0 0 0 1.8 3h11.4a2 2 0 0 0 1.8-3L14 9.5V3"></path>
                        </svg>
                        Top Lab Tests
                    </h5>
                    <span class="card-subtitle">Most requested</span>
                </div>
                <span class="badge-time">All Time</span>
            </div>
            <div class="tests-list">
                <?php if (!empty($topTests)): ?>
                    <?php
                    $testCounts = array_filter(array_column($topTests, 'count'), 'is_numeric');
                    $maxCount   = !empty($testCounts) ? max($testCounts) : 0;
                    foreach ($topTests as $testIx => $test):
                        $count = (float) ($test['count'] ?? 0);
                        $width = ($maxCount > 0) ? min(100, ($count / $maxCount) * 100) : 0;
                    ?>
                        <div class="test-item<?= $testIx === 0 ? ' is-top' : '' ?>">
                            <div class="test-top">
                                <span class="test-name"><?= esc($test['name'] ?? '') ?></span>
                                <span class="test-count"><?= number_format($count) ?></span>
                            </div>
                            <div class="test-progress">
                                <div class="test-fill" style="width: <?= round($width, 2) ?>%;"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <p>No lab test data available</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<!-- Toast Container -->
<div id="toastContainer" role="status" aria-live="polite"></div>

<style>
/* ============================================
   DASHBOARD
   Theme aligned with AdminLayout blue palette.
   ============================================ */

.dashboard-wrapper,
#toastContainer {
    --db-accent:        var(--pm-accent, #175563);
    --db-accent-soft:   var(--pm-accent-soft);
    --db-accent-dark:   var(--pm-accent-hover, #10404B);
    --db-surface:       var(--pm-surface);
    --db-canvas:        var(--pm-canvas);
    --db-subtle:        var(--pm-subtle);
    --db-border:        var(--pm-line);
    --db-border-strong: #CBD5E1;
    --db-text:          var(--pm-text);
    --db-text-soft:     var(--pm-text-soft);
    --db-text-muted:    #94A3B8;
    --db-radius:        12px;
    --db-radius-sm:     8px;
    --db-radius-xs:     6px;
    --db-shadow:        0 1px 2px rgba(15, 23, 42, 0.04);
    --db-shadow-hover:  0 6px 16px rgba(15, 23, 42, 0.07);
    --db-gap:           20px;
}

.dashboard-wrapper {
    font-family: var(--pm-font);
    background: var(--db-canvas);
    padding: 24px;
    min-height: 100vh;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
    color: var(--db-text);
}

.dashboard-wrapper :focus-visible,
#toastContainer :focus-visible {
    outline: 2px solid var(--db-accent);
    outline-offset: 2px;
    border-radius: var(--db-radius-xs);
}

/* ===== LAYOUT ROWS ===== */
.main-row {
    display: grid;
    grid-template-columns: minmax(0, 2fr) minmax(0, 1fr);
    gap: var(--db-gap);
    margin-bottom: var(--db-gap);
}

.bottom-row {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: var(--db-gap);
    margin-bottom: var(--db-gap);
}

/* ===== CHART CARDS ===== */
.chart-card {
    background: var(--db-surface);
    border-radius: var(--db-radius);
    padding: 20px;
    box-shadow: var(--db-shadow);
    border: 1px solid var(--db-border);
    min-width: 0;
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 12px;
    flex-wrap: wrap;
    padding-bottom: 14px;
    margin-bottom: 16px;
    border-bottom: 1px solid var(--db-subtle);
}

.card-title-group {
    display: flex;
    flex-direction: column;
    min-width: 0;
}

.card-title {
    font-size: 0.95rem;
    font-weight: 600;
    color: var(--db-text);
    margin: 0 0 3px;
    display: flex;
    align-items: center;
    gap: 8px;
    letter-spacing: -0.01em;
}

.card-title svg {
    color: var(--db-accent);
    flex-shrink: 0;
}

.card-subtitle {
    font-size: 0.775rem;
    color: var(--db-text-muted);
}

.avg-box {
    text-align: right;
}

.avg-label {
    display: block;
    font-size: 0.725rem;
    color: var(--db-text-muted);
    margin-bottom: 2px;
}

.avg-value {
    font-size: 1.05rem;
    font-weight: 700;
    color: var(--db-text);
    font-variant-numeric: tabular-nums;
}

.badge-time {
    background: var(--db-subtle);
    color: var(--db-text-soft);
    font-size: 0.7rem;
    font-weight: 600;
    letter-spacing: 0.02em;
    padding: 5px 10px;
    border-radius: var(--db-radius-xs);
    border: 1px solid var(--db-border);
    white-space: nowrap;
}

.btn-refresh {
    background: var(--db-surface);
    border: 1px solid var(--db-border);
    color: var(--db-text-soft);
    width: 34px;
    height: 34px;
    border-radius: var(--db-radius-xs);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 0.95rem;
    line-height: 1;
    transition: background-color 0.18s ease, border-color 0.18s ease, color 0.18s ease;
}

.btn-refresh:hover {
    background: var(--db-subtle);
    border-color: var(--db-border-strong);
    color: var(--db-text);
}

.btn-refresh[disabled] {
    opacity: 0.6;
    cursor: default;
}

.btn-refresh.is-busy i {
    animation: db-spin 0.8s linear infinite;
}

@keyframes db-spin {
    to { transform: rotate(360deg); }
}

.chart-container {
    height: 280px;
    position: relative;
}

/* ===== REVENUE: square-cell matrix (same 280px box as before) ===== */
.rev-range {
    height: 32px;
    padding: 0 28px 0 12px;
    font-size: 0.8rem;
    font-weight: 500;
    color: var(--db-text);
    background: #FFFFFF url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%235D6E80'%3E%3Cpath d='M4.5 6.5 8 10l3.5-3.5z'/%3E%3C/svg%3E") no-repeat right 8px center / 14px;
    border: 1px solid var(--db-border);
    border-radius: 999px;
    appearance: none;
    cursor: pointer;
}

.rev-range:hover { border-color: var(--db-border-strong); }
.rev-range:focus-visible { outline: 2px solid var(--db-accent); outline-offset: 2px; }

.rev-box { display: flex; flex-direction: column; gap: 10px; }

.rev-summary {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 16px;
}

.rev-total-label { display: block; font-size: 0.78rem; color: var(--db-text-soft); }

.rev-total {
    display: block;
    margin-top: 2px;
    font-size: 1.6rem;
    font-weight: 600;
    line-height: 1.15;
    letter-spacing: -0.01em;
    color: var(--db-text);
    font-variant-numeric: tabular-nums;
}

/* Each period is a block of square cells, filled bottom-up, row by row */
.rev-matrix {
    flex: 1 1 auto;
    min-height: 0;
    display: flex;
    align-items: flex-end;
    gap: var(--rev-group-gap, 8px);
    overflow: hidden;
}

.rev-col {
    flex: 1 1 0;
    min-width: 0;
    display: flex;
    flex-direction: column-reverse;
    gap: var(--rev-gap, 3px);
    cursor: default;
}

.rev-row { display: flex; gap: var(--rev-gap, 3px); }

.rev-cell {
    flex: 1 1 0;
    height: var(--rev-cell, 12px);
    background: var(--pm-dk-bg-blue, #E6ECF2);
    border-radius: 2px;
    transition: background-color 0.15s ease;
}

.rev-cell.is-on        { background: var(--db-accent); }
/* The last square fills from the bottom by its fraction of one square's value */
.rev-cell.is-part      { box-shadow: inset 0 calc(var(--rev-cell, 12px) * -1 * var(--part, 0)) 0 var(--db-accent); }
.rev-col:hover .rev-cell.is-part { box-shadow: inset 0 calc(var(--rev-cell, 12px) * -1 * var(--part, 0)) 0 var(--db-accent-dark); }

.rev-unit {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin: 0 auto 4px 16px;
    font-size: 0.75rem;
    color: var(--db-text-soft);
    white-space: nowrap;
}

.rev-unit-cell { width: 10px; height: 10px; border-radius: 2px; background: var(--db-accent); }
.rev-col.is-future .rev-cell { background: var(--pm-subtle); }
.rev-col:hover .rev-cell.is-on { background: var(--db-accent-dark); }
.rev-col:hover .rev-cell:not(.is-on) { background: var(--pm-dk-bg-blue, #D5DEE8); }

.rev-labels { display: flex; gap: var(--rev-group-gap, 8px); }

.rev-labels span {
    flex: 1 1 0;
    min-width: 0;
    font-size: 0.68rem;
    text-align: center;
    white-space: nowrap;
    color: var(--db-text-muted);
    font-variant-numeric: tabular-nums;
}

.rev-labels span.is-now { font-weight: 600; color: var(--db-text); }

.rev-labels b {
    display: block;
    margin-top: 1px;
    font-size: 0.68rem;
    font-weight: 600;
    color: var(--db-text);
}

.rev-labels b.is-zero { font-weight: 400; color: var(--db-text-muted); }

/* ===== TESTS LIST ===== */
.tests-list {
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.test-item {
    display: flex;
    flex-direction: column;
    gap: 6px;
    min-width: 0;
}

.test-top {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    gap: 12px;
}

.test-name {
    font-size: 0.825rem;
    font-weight: 500;
    color: var(--db-text-soft);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    min-width: 0;
}

.test-count {
    font-size: 0.825rem;
    font-weight: 600;
    color: var(--db-text);
    font-variant-numeric: tabular-nums;
    flex-shrink: 0;
}

.test-progress {
    height: 6px;
    background: var(--db-subtle);
    border-radius: 3px;
    overflow: hidden;
}

.test-fill {
    height: 100%;
    background: var(--pm-dk-bg-blue, #B9D6F2);
    border-radius: 3px;
    transition: width 0.5s ease;
}

/* One colour: the most requested test in the clinic blue, the rest a pale tint */
.test-item.is-top .test-fill { background: var(--db-accent); }
.test-item.is-top .test-name { color: var(--db-text); font-weight: 600; }

/* ============================================
   PENDING APPOINTMENTS PANEL
   ============================================ */

.pending-card {
    display: flex;
    flex-direction: column;
}

.badge-pending {
    color: var(--pm-dk-fg-amber, #B45309);
    background: var(--pm-dk-bg-amber, #FEF3C7);
    border-color: var(--pm-dk-edge-amber, #FDE68A);
}

.pending-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    flex: 1;
    min-height: 0;
}

.pending-item {
    display: flex;
    align-items: center;
    gap: 0.7rem;
    padding: 0.65rem 0.7rem;
    border: 1px solid var(--pm-line);
    border-radius: 10px;
    background: var(--pm-surface);
    transition: border-color 0.15s ease, box-shadow 0.15s ease, transform 0.15s ease;
    animation: pendingIn 0.28s cubic-bezier(0.2, 0.7, 0.3, 1) both;
}

.pending-item:hover {
    border-color: var(--pm-line);
    box-shadow: 0 4px 12px -6px rgba(15, 23, 42, 0.2);
    transform: translateY(-1px);
}

@keyframes pendingIn {
    from { opacity: 0; transform: translateY(6px); }
    to   { opacity: 1; transform: none; }
}

.pending-item[hidden] { display: none; }

.pending-avatar {
    flex-shrink: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 34px;
    height: 34px;
    font-size: 0.72rem;
    font-weight: 700;
    color: var(--pm-accent-text);
    background: var(--pm-accent-soft);
    border-radius: 50%;
}

.pending-body { flex: 1; min-width: 0; }

.pending-name-row {
    display: flex;
    align-items: baseline;
    gap: 0.4rem;
    flex-wrap: wrap;
}

.pending-name {
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--pm-text);
    overflow-wrap: anywhere;
}

.pending-ref {
    font-size: 0.66rem;
    font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
    color: var(--pm-text-muted);
}

.pending-meta {
    display: flex;
    align-items: center;
    gap: 0.3rem;
    margin-top: 0.15rem;
    font-size: 0.7rem;
    color: var(--pm-text-muted);
}

.pending-tag {
    margin-left: 0.25rem;
    padding: 0.05rem 0.4rem;
    font-size: 0.62rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    border-radius: 999px;
    color: var(--pm-text-soft);
    background: var(--pm-subtle);
}

.pending-tag--laboratory { color: var(--pm-dk-fg-teal, #10404B); background: var(--pm-dk-bg-teal, #EBF2F4); }
.pending-tag--xray       { color: var(--pm-dk-fg-blue, #3B4C5E); background: var(--pm-dk-bg-blue, #EBF0F5); }

.pending-actions {
    display: flex;
    align-items: center;
    gap: 0.3rem;
    flex-shrink: 0;
}

.pending-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.3rem;
    height: 30px;
    padding: 0 0.6rem;
    font-size: 0.72rem;
    font-weight: 600;
    border-radius: 7px;
    text-decoration: none;
    white-space: nowrap;
    cursor: pointer;
    transition: background-color 0.15s ease, color 0.15s ease, border-color 0.15s ease;
}

.pending-btn--ghost {
    width: 30px;
    padding: 0;
    color: var(--pm-text-muted);
    background: transparent;
    border: 1px solid var(--pm-line);
}

.pending-btn--ghost:hover { color: var(--pm-text); background: var(--pm-subtle); border-color: var(--pm-line); }

.pending-btn--approve {
    color: #FFFFFF;
    background: #175563;
    border: 1px solid #175563;
}

.pending-btn--approve:hover { background: #10404B; border-color: #10404B; color: #FFFFFF; }

.pending-pager {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.6rem;
    padding-top: 0.7rem;
    margin-top: 0.7rem;
    border-top: 1px solid var(--pm-line);
}

.pager-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    color: var(--pm-text-muted);
    background: var(--pm-surface);
    border: 1px solid var(--pm-line);
    border-radius: 7px;
    cursor: pointer;
    transition: background-color 0.15s ease, color 0.15s ease;
}

.pager-btn:hover:not(:disabled) { color: var(--pm-dk-fg-teal, #175563); background: var(--pm-dk-bg-teal, #EBF2F4); border-color: var(--pm-dk-edge-blue, #9DC4E8); }
.pager-btn:disabled { opacity: 0.4; cursor: not-allowed; }

.pager-status {
    font-size: 0.72rem;
    color: var(--pm-text-muted);
    font-variant-numeric: tabular-nums;
}

.pending-all {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.3rem;
    margin-top: 0.7rem;
    padding: 0.45rem;
    font-size: 0.74rem;
    font-weight: 600;
    color: var(--pm-dk-fg-teal, #175563);
    text-decoration: none;
    border-radius: 7px;
    transition: background-color 0.15s ease;
}

.pending-all:hover { background: var(--pm-dk-bg-teal, #EBF2F4); color: var(--pm-dk-fg-teal, #10404B); }

.visually-hidden {
    position: absolute; width: 1px; height: 1px;
    padding: 0; margin: -1px; overflow: hidden;
    clip: rect(0 0 0 0); white-space: nowrap; border: 0;
}

@media (max-width: 560px) {
    .pending-item { flex-wrap: wrap; }
    .pending-actions { width: 100%; justify-content: flex-end; }
}

/* ===== EMPTY STATE ===== */
.empty-state {
    text-align: center;
    padding: 24px 16px;
    color: var(--db-text-muted);
    font-size: 0.84rem;
}

.empty-state p {
    margin: 0;
}

.empty-state-chart {
    height: 280px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--db-canvas);
    border: 1px dashed var(--db-border);
    border-radius: var(--db-radius-sm);
}

/* ===== TOAST ===== */
#toastContainer {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 9999;
    display: flex;
    flex-direction: column;
    gap: 10px;
    max-width: calc(100vw - 40px);
}

.toast {
    background: var(--db-surface);
    border: 1px solid var(--db-border);
    border-radius: var(--db-radius-sm);
    box-shadow: 0 8px 20px rgba(15, 23, 42, 0.10);
    padding: 12px 14px;
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 280px;
    animation: slideIn 0.25s ease;
    border-left: 3px solid #2563EB;
    transition: opacity 0.25s ease, transform 0.25s ease;
}

.toast.success { border-left-color: #059669; }
.toast.error   { border-left-color: #DC2626; }
.toast.warning { border-left-color: #D97706; }
.toast.info    { border-left-color: #2563EB; }

.toast i {
    font-size: 1.05rem;
    line-height: 1;
}

.toast.success i { color: #059669; }
.toast.error i   { color: var(--pm-dk-fg-red, #DC2626); }
.toast.warning i { color: #D97706; }
.toast.info i    { color: var(--pm-dk-fg-blue, #2563EB); }

.toast-message {
    flex: 1;
    font-size: 0.84rem;
    color: var(--db-text);
    line-height: 1.45;
}

.toast-close {
    background: none;
    border: none;
    cursor: pointer;
    color: var(--db-text-muted);
    font-size: 0.8rem;
    padding: 2px;
    line-height: 1;
}

.toast-close:hover {
    color: var(--db-text);
}

@keyframes slideIn {
    from { transform: translateX(16px); opacity: 0; }
    to   { transform: translateX(0); opacity: 1; }
}

/* ============================================
   RESPONSIVE
   ============================================ */

@media (max-width: 1200px) {
    .main-row   { grid-template-columns: minmax(0, 1fr); }
    .bottom-row { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}

@media (max-width: 992px) {
    .dashboard-wrapper { padding: 16px; }
    .bottom-row { grid-template-columns: minmax(0, 1fr); }
}

@media (max-width: 768px) {
    .chart-card        { padding: 16px; }
    .chart-container,
    .empty-state-chart { height: 240px; }
}

@media (max-width: 576px) {
    .dashboard-wrapper { padding: 12px; }
    .main-row,
    .bottom-row { gap: 12px; }
    .card-header { flex-direction: column; align-items: flex-start; }
    .avg-box     { text-align: left; }
    .chart-container,
    .empty-state-chart { height: 220px; }

    #toastContainer {
        top: auto;
        bottom: 16px;
        left: 12px;
        right: 12px;
        max-width: none;
    }
    .toast { min-width: 0; }
}

/* ============================================
   THEME (pm-theme.css tokens)
   ============================================ */

.dashboard-wrapper,
#toastContainer {
    --db-canvas:       var(--pm-canvas, #F3F6F9);
    --db-subtle:       var(--pm-subtle, #EBF0F5);
    --db-border:       var(--pm-line, #DDE4EC);
    --db-border-strong:var(--pm-line-strong, #C3CFDB);
    --db-text:         var(--pm-text, #10202E);
    --db-text-soft:    var(--pm-text-soft, #3B4C5E);
    --db-text-muted:   var(--pm-text-muted, #5D6E80);
    --db-radius:       8px;
    --db-radius-sm:    6px;
    --db-shadow:       none;
    --db-shadow-hover: none;
}

/* The layout already pads the content area; doubling it wasted the first 24px. */
.dashboard-wrapper { padding: 0; min-height: 0; background: transparent; }

.dash-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 22px;
}

.dash-title {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 700;
    letter-spacing: -0.03em;
    color: var(--db-text);
}

.dash-sub {
    margin: 2px 0 0;
    font-size: 0.83rem;
    color: var(--db-text-muted);
}

.chart-card { padding: 20px; }

.card-header { border-bottom: 0; padding-bottom: 0; margin-bottom: 16px; }

.card-title { font-weight: 600; }
.card-title svg { color: var(--db-text-muted); }

.badge-time {
    background: transparent;
    border: 0;
    padding: 2px 0;
    color: var(--db-text-muted);
    font-weight: 500;
    letter-spacing: 0;
}

.badge-pending {
    padding: 2px 8px;
    border-radius: 4px;
    color: var(--pm-dk-fg-amber, #93370D);
    background: var(--pm-dk-bg-amber, #FEF0C7);
}

.avg-label { color: var(--db-text-muted); }
.avg-value { font-weight: 600; }

/* Bars keep their data colour; neutral track, square ends */
.test-progress { height: 6px; border-radius: 1px; }
.test-fill { border-radius: 1px; }

/* Pending list reads as rows, not a stack of floating cards */
.pending-list { gap: 0; }
.pending-item {
    border: 0;
    border-bottom: 1px solid var(--db-border);
    border-radius: 0;
    padding: 0.7rem 0;
    animation: none;
}
.pending-item:hover { box-shadow: none; transform: none; border-color: var(--db-border); }
.pending-avatar { color: var(--db-text-soft); background: var(--db-subtle); }
.pending-name { font-size: 0.84rem; color: var(--db-text); }
.pending-ref { font-family: var(--pm-mono, monospace); color: var(--db-text-muted); }
.pending-meta { font-size: 0.74rem; color: var(--db-text-muted); }
.pending-tag { border-radius: 4px; letter-spacing: 0.02em; }
.pending-btn { border-radius: 6px; }
.pending-pager { border-top: 0; }

.btn-refresh { border-radius: 6px; }

@media (max-width: 576px) {
    .dash-head { align-items: flex-start; }
    .card-header { flex-direction: row; }
}

/* ===== MOTION PREFERENCES ===== */
@media (prefers-reduced-motion: reduce) {
    .dashboard-wrapper *,
    #toastContainer * {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
}

/* ===== PRINT ===== */
@media print {
    .dashboard-wrapper { background: var(--pm-surface); padding: 0; min-height: 0; }
    .chart-card { box-shadow: none; break-inside: avoid; page-break-inside: avoid; }
    .btn-refresh,
    .pending-card,
    #toastContainer { display: none !important; }
}
</style>

<script>
// ============================================
// CHART DATA
// ============================================

const revenueData  = <?= json_encode($revenueDataArr, $jsonFlags) ?>;
const visitsData   = <?= json_encode($visitsDataArr, $jsonFlags) ?>;
const requestsData = <?= json_encode($requestsDataArr, $jsonFlags) ?>;

// ============================================
// SHARED HELPERS
// ============================================

const DB_COLORS = {
    accent:  '#175563',
    accent2: '#10404B',
    success: '#067647',
    muted:   '#E2E8F0',
    grid:    '#F1F5F9',
    tick:    '#94A3B8',
    text:    '#1E293B',
    border:  '#E2E8F0',
    surface: '#FFFFFF'
};

function dbFormatPeso(value, decimals) {
    const number = Number(value) || 0;
    return '\u20B1' + number.toLocaleString('en-PH', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals
    });
}

const dbBaseTooltip = {
    backgroundColor: 'rgba(20, 45, 54, 0.95)',
    titleColor: '#F8FAFC',
    bodyColor: '#E2E8F0',
    footerColor: '#F8FAFC',
    borderColor: 'rgba(255, 255, 255, 0.08)',
    borderWidth: 1,
    padding: 12,
    cornerRadius: 6,
    boxPadding: 4,
    caretSize: 6,
    titleFont: { size: 12, weight: '600' },
    bodyFont: { size: 12 }
};

const dbBaseLegend = {
    display: true,
    position: 'bottom',
    labels: {
        usePointStyle: true,
        pointStyle: 'circle',
        boxWidth: 8,
        boxHeight: 8,
        padding: 16,
        font: { size: 11 },
        color: '#64748B'
    }
};

function dbScales(yTickCallback) {
    return {
        y: {
            beginAtZero: true,
            border: { display: false },
            grid: { color: DB_COLORS.grid, drawBorder: false, drawTicks: false, tickBorderDash: [3, 3] },
            ticks: {
                color: DB_COLORS.tick,
                font: { size: 11 },
                padding: 8,
                maxTicksLimit: 6,
                callback: yTickCallback
            }
        },
        x: {
            border: { display: false },
            grid: { display: false, drawBorder: false },
            ticks: { color: DB_COLORS.tick, font: { size: 11 }, padding: 6 }
        }
    };
}

function dbInitChart(canvasId, config) {
    if (typeof Chart === 'undefined') { return null; }
    const canvas = document.getElementById(canvasId);
    if (!canvas) { return null; }
    return new Chart(canvas.getContext('2d'), config);
}

// ============================================
// ANIMATION
// ============================================

const DB_REDUCED_MOTION =
    window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

function dbBarAnimation(stagger) {
    if (DB_REDUCED_MOTION) { return { duration: 0 }; }
    const step = typeof stagger === 'number' ? stagger : 40;
    return {
        duration: 700,
        easing: 'easeOutQuart',
        delay: function (context) {
            if (context.type === 'data' && context.mode === 'default' && !context.dropped) {
                context.dropped = true;
                return context.dataIndex * step + context.datasetIndex * 90;
            }
            return 0;
        }
    };
}

function dbAnimateOnView(chart, canvasId) {
    if (!chart || DB_REDUCED_MOTION || !('IntersectionObserver' in window)) { return; }
    const canvas = document.getElementById(canvasId);
    if (!canvas) { return; }
    const observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                chart.reset();
                chart.update();
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.25 });
    observer.observe(canvas);
}

// ============================================
// 1. REVENUE CHART
// ============================================

(function () {
    const matrix = document.getElementById('revMatrix');
    const labels = document.getElementById('revLabels');
    const select = document.getElementById('revRange');
    if (!matrix || !labels) { return; }

    const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    const daily  = <?= json_encode([
        'dates'  => $kpiTrends['dates'] ?? [],
        'values' => array_map('floatval', $kpiTrends['revenue_today'] ?? []),
    ], $jsonFlags) ?>;

    /* Each range: one entry per period; months after this one are shown as future (empty) */
    const RANGES = {
        year: {
            subtitle: 'Monthly revenue <?= date('Y') ?>',
            avgLabel: 'Avg. monthly',
            unit: 'month',
            periods: MONTHS.map(function (m, i) {
                const known = i < revenueData.values.length;
                return { label: m, value: known ? Number(revenueData.values[i]) || 0 : 0, future: !known, now: i === revenueData.values.length - 1 };
            })
        },
        days: {
            subtitle: 'Daily revenue, last 14 days',
            avgLabel: 'Avg. daily',
            unit: 'day',
            periods: (daily.dates || []).map(function (d, i, all) {
                const date = new Date(d + 'T00:00:00');
                return {
                    label: i === all.length - 1 ? 'Today' : MONTHS[date.getMonth()] + ' ' + date.getDate(),
                    value: Number(daily.values[i]) || 0,
                    future: false,
                    now: i === all.length - 1
                };
            })
        }
    };

    const CELL = 12, GAP = 3, GROUP_GAP = 8;

    /* ₱950, ₱2.8k, ₱12k: short enough to sit under a 14-day label */
    function revShort(v) {
        if (v >= 1000) { return '₱' + (v / 1000).toFixed(v >= 10000 ? 0 : 1).replace(/\.0$/, '') + 'k'; }
        return '₱' + Math.round(v).toLocaleString('en-PH');
    }
    let current = 'year';

    function draw() {
        const range   = RANGES[current];
        const periods = range.periods;
        const n       = periods.length;
        if (!n) { matrix.innerHTML = ''; labels.innerHTML = ''; return; }

        /* Square cells: as many 12px columns per period as fit, then rows to fill the height */
        const width  = matrix.clientWidth;
        const height = matrix.clientHeight;
        const perGroup = (width - (n - 1) * GROUP_GAP) / n;
        const across = Math.max(1, Math.floor((perGroup + GAP) / (CELL + GAP)));
        const size   = (perGroup - (across - 1) * GAP) / across;
        const rows   = Math.max(1, Math.floor((height + GAP) / (size + GAP)));
        const slots  = rows * across;
        const peak   = Math.max.apply(null, periods.map(function (p) { return p.value; }));

        /* One square is worth a round peso amount (1/2/2.5/5 x 10^k), the smallest
           that fits the best period in its block, so every block is read on one scale */
        let unit = 1;
        if (peak > 0) {
            const raw = peak / slots;
            const mag = Math.pow(10, Math.floor(Math.log10(raw)));
            unit = [1, 2, 2.5, 5, 10].map(function (m) { return m * mag; })
                .find(function (u) { return u >= raw; });
            unit = Math.max(1, unit);
        }

        matrix.style.setProperty('--rev-cell', size.toFixed(2) + 'px');
        matrix.style.setProperty('--rev-gap', GAP + 'px');
        matrix.style.setProperty('--rev-group-gap', GROUP_GAP + 'px');
        labels.style.setProperty('--rev-group-gap', GROUP_GAP + 'px');

        let html = '';
        periods.forEach(function (p) {
            const exact = Math.min(slots, p.value / unit);
            const full  = Math.floor(exact);
            const part  = exact - full;
            let cells = 0, col = '';
            for (let r = 0; r < rows; r++) {
                let row = '';
                for (let c = 0; c < across; c++) {
                    if (cells < full) {
                        row += '<span class="rev-cell is-on"></span>';
                    } else if (cells === full && part > 0.04) {
                        row += '<span class="rev-cell is-part" style="--part:' + part.toFixed(3) + '"></span>';
                    } else {
                        row += '<span class="rev-cell"></span>';
                    }
                    cells++;
                }
                col += '<span class="rev-row">' + row + '</span>';
            }
            html += '<span class="rev-col' + (p.future ? ' is-future' : '') + '" title="' + p.label + ' · ' + dbFormatPeso(p.value, 2) + '">' + col + '</span>';
        });
        matrix.innerHTML = html;

        /* Labels: every period for a year, every other day for 14 days (today always shown) */
        const step = n > 12 ? 2 : 1;
        labels.innerHTML = periods.map(function (p, i) {
            const show = (n - 1 - i) % step === 0;
            const amount = p.future ? '' : '<b class="' + (p.value > 0 ? '' : 'is-zero') + '">' + (p.value > 0 ? revShort(p.value) : '₱0') + '</b>';
            return '<span class="' + (p.now ? 'is-now' : '') + '">' + (show ? p.label + amount : '') + '</span>';
        }).join('');

        const known = periods.filter(function (p) { return !p.future; });
        const total = known.reduce(function (s, p) { return s + p.value; }, 0);

        /* Average over the periods since income was first recorded, not the empty ones before it */
        const first  = known.findIndex(function (p) { return p.value > 0; });
        const counted = first < 0 ? known : known.slice(first);
        const since  = first > 0
            ? ' · ' + counted.length + ' ' + range.unit + (counted.length === 1 ? '' : 's') + ' with income'
            : '';
        document.getElementById('revTotal').textContent = dbFormatPeso(total, 2);
        document.getElementById('revAvg').textContent = dbFormatPeso(total / Math.max(1, counted.length), 2);
        document.getElementById('revAvgLabel').textContent = range.avgLabel + since;
        document.getElementById('revUnitText').textContent = '1 square = ' + dbFormatPeso(unit, unit % 1 ? 2 : 0);
        document.getElementById('revSubtitle').textContent = range.subtitle;
        matrix.setAttribute('aria-label', range.subtitle + ': total ' + dbFormatPeso(total, 2));
    }

    if (select) {
        select.addEventListener('change', function () { current = select.value; draw(); });
    }

    if ('ResizeObserver' in window) {
        let frame = 0;
        new ResizeObserver(function () {
            cancelAnimationFrame(frame);
            frame = requestAnimationFrame(draw);
        }).observe(matrix);
    }
    draw();
})();

// ============================================
// 2. DEPARTMENT PATIENT VOLUME CHART
// ============================================

const deptLab  = Array.isArray(visitsData.lab)  ? visitsData.lab  : [];
const deptXray = Array.isArray(visitsData.xray) ? visitsData.xray : [];

const deptChart = dbInitChart('visitsChart', {
    type: 'bar',
    data: {
        labels: visitsData.labels,
        datasets: [
            {
                label: 'Laboratory',
                data: deptLab,
                backgroundColor: DB_COLORS.accent,
                hoverBackgroundColor: DB_COLORS.accent2,
                borderRadius: 0,
                borderSkipped: false,
                barPercentage: 0.7,
                categoryPercentage: 0.8,
                maxBarThickness: 34,
                stack: 'departments'
            },
            {
                label: 'X-Ray',
                data: deptXray,
                backgroundColor: '#7C8DA0',
                hoverBackgroundColor: '#5D6E80',
                borderRadius: { topLeft: 2, topRight: 2, bottomLeft: 0, bottomRight: 0 },
                borderSkipped: false,
                barPercentage: 0.7,
                categoryPercentage: 0.8,
                maxBarThickness: 34,
                stack: 'departments'
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        animation: dbBarAnimation(45),
        plugins: {
            legend: dbBaseLegend,
            tooltip: Object.assign({}, dbBaseTooltip, {
                displayColors: true,
                footerColor: DB_COLORS.text,
                footerFont: { size: 11, weight: '600' },
                callbacks: {
                    label: function (context) {
                        const n = Number(context.parsed.y) || 0;
                        return context.dataset.label + ': ' + n + (n === 1 ? ' patient' : ' patients');
                    },
                    footer: function (items) {
                        let total = 0;
                        items.forEach(function (item) { total += Number(item.parsed.y) || 0; });
                        return 'Total: ' + total + (total === 1 ? ' patient' : ' patients');
                    }
                }
            })
        },
        scales: {
            y: {
                beginAtZero: true,
                stacked: true,
                border: { display: false },
                grid: { color: DB_COLORS.grid, drawBorder: false, drawTicks: false, tickBorderDash: [3, 3] },
                ticks: {
                    color: DB_COLORS.tick,
                    font: { size: 11 },
                    padding: 8,
                    maxTicksLimit: 6,
                    precision: 0
                }
            },
            x: {
                stacked: true,
                border: { display: false },
                grid: { display: false, drawBorder: false },
                ticks: { color: DB_COLORS.tick, font: { size: 11 }, padding: 6 }
            }
        }
    }
});

dbAnimateOnView(deptChart, 'visitsChart');

// ============================================
// 3. DIAGNOSTIC REQUESTS CHART
// ============================================

const requestsChart = dbInitChart('requestsChart', {
    type: 'bar',
    data: {
        labels: requestsData.labels,
        datasets: [
            {
                label: 'Requested',
                data: requestsData.requested,
                backgroundColor: DB_COLORS.accent,
                borderRadius: 2,
                borderSkipped: false,
                barPercentage: 0.7,
                categoryPercentage: 0.8,
                maxBarThickness: 34
            },
            {
                label: 'Completed',
                data: requestsData.completed,
                backgroundColor: DB_COLORS.success,
                borderRadius: 2,
                borderSkipped: false,
                barPercentage: 0.7,
                categoryPercentage: 0.8,
                maxBarThickness: 34
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: dbBarAnimation(45),
        plugins: {
            legend: dbBaseLegend,
            tooltip: Object.assign({}, dbBaseTooltip, { displayColors: true })
        },
        scales: dbScales(undefined)
    }
});

dbAnimateOnView(requestsChart, 'requestsChart');

// ============================================
// TOAST NOTIFICATIONS
// ============================================

function showToast(message, type = 'info') {
    const container = document.getElementById('toastContainer');
    if (!container) { return; }

    const icons = {
        success: 'bi-check-circle-fill',
        error: 'bi-x-circle-fill',
        warning: 'bi-exclamation-triangle-fill',
        info: 'bi-info-circle-fill'
    };

    const toast = document.createElement('div');
    toast.className = `toast ${type}`;

    const icon = document.createElement('i');
    icon.className = `bi ${icons[type] || icons.info}`;
    icon.setAttribute('aria-hidden', 'true');

    const text = document.createElement('span');
    text.className = 'toast-message';
    text.textContent = message;

    const close = document.createElement('button');
    close.type = 'button';
    close.className = 'toast-close';
    close.setAttribute('aria-label', 'Dismiss notification');
    close.innerHTML = '<i class="bi bi-x-lg" aria-hidden="true"></i>';
    close.onclick = function () { toast.remove(); };

    toast.appendChild(icon);
    toast.appendChild(text);
    toast.appendChild(close);
    container.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(16px)';
        setTimeout(() => toast.remove(), 300);
    }, 5000);
}

// ============================================
// PENDING APPOINTMENTS PANEL
// ============================================

(function () {
    const list = document.getElementById('pendingList');
    if (!list) { return; }

    const items = Array.prototype.slice.call(list.querySelectorAll('.pending-item'));
    if (!items.length) { return; }

    const prev  = document.getElementById('pendingPrev');
    const next  = document.getElementById('pendingNext');
    const label = document.getElementById('pendingPageNum');

    const perPage = 4;
    const pages   = Math.ceil(items.length / perPage);
    let page = 0;

    function render() {
        items.forEach(function (item, i) {
            const onPage = Math.floor(i / perPage) === page;
            item.hidden = !onPage;

            if (onPage) {
                item.style.animation = 'none';
                void item.offsetWidth;
                item.style.animation = '';
                item.style.animationDelay = ((i % perPage) * 45) + 'ms';
            }
        });

        if (label) { label.textContent = page + 1; }
        if (prev)  { prev.disabled = page === 0; }
        if (next)  { next.disabled = page >= pages - 1; }
    }

    if (prev) {
        prev.addEventListener('click', function () {
            if (page > 0) { page--; render(); }
        });
    }

    if (next) {
        next.addEventListener('click', function () {
            if (page < pages - 1) { page++; render(); }
        });
    }

    list.addEventListener('click', function (event) {
        const link = event.target.closest('[data-confirm-approve]');
        if (!link) { return; }

        const name = link.dataset.name || 'this patient';
        if (!confirm('Approve the appointment for ' + name + '?')) {
            event.preventDefault();
            return;
        }

        link.style.pointerEvents = 'none';
        link.style.opacity = '0.6';
    });

    render();
})();

function refreshActivity() {
    const btn = document.querySelector('.btn-refresh');
    if (btn) {
        btn.classList.add('is-busy');
        btn.disabled = true;
    }
    window.location.reload();
}
</script>

<?= $this->endSection() ?>