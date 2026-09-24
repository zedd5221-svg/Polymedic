<?= $this->extend('layouts/AdminLayout') ?>

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

$hasRevenueChart  = !empty($revenueDataArr['labels'])  && !empty($revenueDataArr['values']);
$hasVisitsChart   = !empty($visitsDataArr['labels'])
                    && (!empty($visitsDataArr['lab']) || !empty($visitsDataArr['xray']));

/* Pending appointments shown in the mini approval panel. */
$pendingAppointmentsArr = $pendingAppointments ?? [];
$pendingPerPage         = 4;
$pendingTotal           = count($pendingAppointmentsArr);
$pendingPages           = (int) max(1, ceil($pendingTotal / $pendingPerPage));
$hasRequestsChart = !empty($requestsDataArr['labels']) && !empty($requestsDataArr['requested']);

$monthlyRevenueVal = (float) ($monthlyRevenue ?? 0);
$avgMonthlyRevenue = $monthlyRevenueVal / max(1, (int) date('m'));

/* Only allow a colour value that is safe to place inside a style attribute. */
$safeColor = static function ($color, $fallback = '#0D9488') {
    $color = is_string($color) ? trim($color) : '';
    if ($color !== '' && preg_match('/^(#[0-9A-Fa-f]{3,8}|rgba?\([0-9.,%\s]+\)|hsla?\([0-9.,%\sdegrad]+\)|[A-Za-z]{3,20})$/', $color)) {
        return $color;
    }
    return $fallback;
};

$jsonFlags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;

/* ------------------------------------------------------------------
   STAT CARDS
   ------------------------------------------------------------------ */
$statCards = [
    [
        'key'   => 'patients',
        'icon'  => 'people-blue.png',
        'tone'  => 'icon-cyan',
        'label' => 'Total Patients',
        'value' => number_format((int) ($totalPatients ?? 0)),
        'note'  => 'All registered patients',
        'link'  => base_url('admin/patients'),
    ],
    [
        'key'   => 'today',
        'icon'  => 'document.png',
        'tone'  => 'icon-teal',
        'label' => "Today's Patients",
        'value' => number_format((int) ($todayPatients ?? 0)),
        'note'  => 'Checked in today',
        'link'  => base_url('admin/visits'),
    ],
    [
        'key'   => 'pending',
        'icon'  => 'file (1).png',
        'tone'  => 'icon-orange',
        'label' => 'Pending Requests',
        'value' => number_format((int) ($pendingRequests ?? 0)),
        'note'  => 'Awaiting processing',
        'link'  => base_url('admin/requests'),
    ],
    [
        'key'   => 'completed',
        'icon'  => 'people-check-blue.png',
        'tone'  => 'icon-green',
        'label' => 'Completed Requests',
        'value' => number_format((int) ($completedRequests ?? 0)),
        'note'  => 'Results encoded',
        'link'  => base_url('admin/requests'),
    ],
    [
        'key'   => 'released',
        'icon'  => 'clock (4).png',
        'tone'  => 'icon-blue',
        'label' => 'Released Results',
        'value' => number_format((int) ($releasedResults ?? 0)),
        'note'  => 'Sent to doctors',
        'link'  => base_url('admin/results'),
    ],
    [
        'key'   => 'revenue_today',
        'icon'  => 'money-yellow.png',
        'tone'  => 'icon-yellow',
        'label' => "Today's Revenue",
        'value' => '&#8369;' . number_format((float) ($todayRevenue ?? 0), 2),
        'note'  => 'Collected today',
        'raw'   => true,
        'link'  => base_url('admin/payments'),
    ],
    [
        'key'   => 'revenue_month',
        'icon'  => 'money-yellow.png',
        'tone'  => 'icon-pink',
        'label' => 'Monthly Revenue',
        'value' => '&#8369;' . number_format($monthlyRevenueVal, 2),
        'note'  => date('F Y'),
        'raw'   => true,
        'link'  => base_url('admin/reports/revenue'),
    ],
];

/* ------------------------------------------------------------------
   SPARKLINES
   ------------------------------------------------------------------ */
$kpiTrends = $kpiTrends ?? [];
$sparkMeta = [
    'patients'      => ['color' => '#0891B2', 'goodUp' => true,  'cumulative' => true,  'unit' => 'patients'],
    'today'         => ['color' => '#0D9488', 'goodUp' => true,  'cumulative' => false, 'unit' => 'appointments'],
    'pending'       => ['color' => '#EA580C', 'goodUp' => false, 'cumulative' => false, 'unit' => 'requests received'],
    'completed'     => ['color' => '#16A34A', 'goodUp' => true,  'cumulative' => false, 'unit' => 'completed'],
    'released'      => ['color' => '#2563EB', 'goodUp' => true,  'cumulative' => false, 'unit' => 'released'],
    'revenue_today' => ['color' => '#CA8A04', 'goodUp' => true,  'cumulative' => false, 'unit' => 'revenue', 'money' => true],
    'revenue_month' => ['color' => '#DB2777', 'goodUp' => true,  'cumulative' => true,  'unit' => 'revenue', 'money' => true],
];

/* Returns [svgPath, lastX%, lastY%] for a value series. */
$sparkGeometry = static function (array $vals): array {
    $n = count($vals);
    if ($n < 2) { return ['', 100, 50]; }
    $min = min($vals);
    $max = max($vals);
    $rng = $max - $min;
    $w = 100; $h = 32; $pad = 3;
    $pts = [];
    foreach (array_values($vals) as $i => $v) {
        $x = $i / ($n - 1) * $w;
        $y = $rng > 0 ? $h - $pad - (($v - $min) / $rng) * ($h - 2 * $pad) : $h / 2;
        $pts[] = [round($x, 2), round($y, 2)];
    }
    $line = 'M' . $pts[0][0] . ',' . $pts[0][1];
    for ($i = 1; $i < $n; $i++) {
        $cx = round(($pts[$i - 1][0] + $pts[$i][0]) / 2, 2);
        $line .= ' C' . $cx . ',' . $pts[$i - 1][1] . ' ' . $cx . ',' . $pts[$i][1] . ' ' . $pts[$i][0] . ',' . $pts[$i][1];
    }
    $last = end($pts);
    return [$line, $last[0], round($last[1] / $h * 100, 2)];
};
?>

<div class="dashboard-wrapper">

    <!-- ===== TOP STATS ROW (7 Cards) ===== -->
    <div class="stats-row">
        <?php foreach ($statCards as $card): ?>
            <?php
                $tag  = !empty($card['link']) ? 'a' : 'div';
                $href = !empty($card['link']) ? ' href="' . esc($card['link'], 'attr') . '"' : '';
                $icon = (string) ($card['icon'] ?? '');

                $meta   = $sparkMeta[$card['key']] ?? null;
                $vals   = $meta ? array_map('floatval', $kpiTrends[$card['key']] ?? []) : [];
                $hasSpk = $meta && count($vals) >= 2;
                if ($hasSpk) {
                    [$spkLine, $spkX, $spkY] = $sparkGeometry($vals);
                    $dates = $kpiTrends['dates'] ?? [];
                    $fmt   = !empty($meta['money'])
                        ? static fn($v) => '₱' . number_format($v, 2)
                        : static fn($v) => number_format($v);
                    $spkTitle = $meta['unit'] . ': ' . $fmt(end($vals))
                        . ' on ' . (isset($dates[count($vals) - 1]) ? date('M j', strtotime($dates[count($vals) - 1])) : 'today')
                        . ' · 14-day peak ' . $fmt(max($vals));
                }
            ?>
            <<?= $tag ?> class="stat-card"<?= $href ?><?= $hasSpk ? ' style="--spark:' . esc($meta['color'], 'attr') . '"' : '' ?>>

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

                <div class="stat-value-row">
                    <div class="stat-value<?= !empty($card['raw']) ? ' stat-value-money' : '' ?>">
                        <?= !empty($card['raw']) ? $card['value'] : esc($card['value']) ?>
                    </div>
                    <?php if ($hasSpk): ?>
                        <div class="spark spark-inline" role="img" aria-label="<?= esc($spkTitle, 'attr') ?>" title="<?= esc($spkTitle, 'attr') ?>">
                            <svg viewBox="0 0 100 32" preserveAspectRatio="none" aria-hidden="true">
                                <path d="<?= $spkLine ?>" class="spark-line" fill="none" vector-effect="non-scaling-stroke"/>
                            </svg>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="stat-label"><?= esc($card['label']) ?></div>

                <div class="stat-foot">
                    <div class="stat-sub"><?= esc($card['note']) ?></div>
                </div>

            </<?= $tag ?>>
        <?php endforeach; ?>
    </div>

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
                    <span class="card-subtitle">Monthly revenue <?= date('Y') ?></span>
                </div>
                <div class="avg-box">
                    <span class="avg-label">Avg. monthly</span>
                    <span class="avg-value">&#8369;<?= number_format($avgMonthlyRevenue, 2) ?></span>
                </div>
            </div>
            <?php if ($hasRevenueChart): ?>
                <div class="chart-container">
                    <canvas id="revenueChart"></canvas>
                </div>
            <?php else: ?>
                <div class="empty-state empty-state-chart">
                    <p>No revenue data recorded yet</p>
                </div>
            <?php endif; ?>
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
                                <a href="<?= base_url('admin/appointment/approve/' . $apptId) ?>?from=dashboard"
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
                    foreach ($topTests as $test):
                        $count = (float) ($test['count'] ?? 0);
                        $width = ($maxCount > 0) ? min(100, ($count / $maxCount) * 100) : 0;
                    ?>
                        <div class="test-item">
                            <div class="test-top">
                                <span class="test-name"><?= esc($test['name'] ?? '') ?></span>
                                <span class="test-count"><?= number_format($count) ?></span>
                            </div>
                            <div class="test-progress">
                                <div class="test-fill" style="width: <?= round($width, 2) ?>%; background: <?= $safeColor($test['color'] ?? '') ?>;"></div>
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
   ============================================ */

.dashboard-wrapper,
#toastContainer {
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
    --db-radius:        12px;
    --db-radius-sm:     8px;
    --db-radius-xs:     6px;
    --db-shadow:        0 1px 2px rgba(15, 23, 42, 0.04);
    --db-shadow-hover:  0 6px 16px rgba(15, 23, 42, 0.07);
    --db-gap:           20px;
}

.dashboard-wrapper {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
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

/* ===== STATS ROW ===== */

.stats-row {
    display: grid;
    grid-template-columns: repeat(7, minmax(0, 1fr));
    gap: 16px;
    margin-bottom: var(--db-gap);
}

.stat-card {
    display: block;
    background: var(--db-surface);
    border-radius: var(--db-radius);
    padding: 18px;
    box-shadow: var(--db-shadow);
    border: 1px solid var(--db-border);
    text-decoration: none;
    color: inherit;
    transition: border-color 0.18s ease, box-shadow 0.18s ease, background-color 0.18s ease;
    min-width: 0;
    width: 100%;
    box-sizing: border-box;
}

.stat-card:hover,
.stat-card:focus-visible {
    border-color: var(--db-border-strong);
    box-shadow: var(--db-shadow-hover);
    text-decoration: none;
    color: inherit;
}

.stat-card:hover .stat-arrow {
    color: var(--db-text-soft);
}

.stat-top {
    display: grid;
    grid-template-columns: 40px 1fr 16px;
    align-items: center;
    margin-bottom: 14px;
    width: 100%;
}

.stat-top > .stat-icon  { grid-column: 1; }
.stat-top > .stat-arrow { grid-column: 3; justify-self: end; }

.stat-top:not(:has(.stat-arrow)) {
    grid-template-columns: 40px 1fr;
}

.stat-icon {
    width: 40px;
    height: 40px;
    border-radius: var(--db-radius-sm);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    background: transparent;
}

.stat-img {
    width: 25px;
    height: 25px;
    object-fit: contain;
    display: block;
}

.stat-arrow {
    color: #CBD5E1;
    flex-shrink: 0;
    transition: color 0.18s ease;
    justify-self: end;
}

.icon-cyan,
.icon-teal,
.icon-orange,
.icon-green,
.icon-blue,
.icon-yellow,
.icon-pink {
    background: transparent;
}

.stat-value-row {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 6px;
    min-width: 0;
}

/* Smaller KPI number to match the reference design. */
.stat-value {
    font-size: 1.35rem;
    font-weight: 680;
    color: var(--db-text);
    line-height: 1.1;
    letter-spacing: -0.02em;
    font-variant-numeric: tabular-nums;
    min-width: 0;
}

.stat-value-money {
    font-size: 1.0rem;
    overflow-wrap: anywhere;
}

/* Small inline sparkline, sits to the right of the value. */
.spark-inline {
    flex-shrink: 0;
    width: 56px;
    height: 20px;
    position: relative;
}

.spark-inline svg {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    display: block;
    overflow: visible;
}

.spark-line {
    stroke: var(--spark);
    stroke-width: 1.8;
    stroke-linecap: round;
    stroke-linejoin: round;
}

.stat-label {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--db-text-soft);
    margin-bottom: 3px;
}

.stat-sub {
    font-size: 0.775rem;
    color: var(--db-text-muted);
}

.stat-foot {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    margin-top: 4px;
    min-width: 0;
}

.stat-foot .stat-sub {
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
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
    border-radius: 3px;
    transition: width 0.5s ease;
}

/* ============================================
   PENDING APPOINTMENTS PANEL
   ============================================ */

.pending-card {
    display: flex;
    flex-direction: column;
}

.badge-pending {
    color: #B45309;
    background: #FEF3C7;
    border-color: #FDE68A;
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
    border: 1px solid #E2E8F0;
    border-radius: 10px;
    background: #FFFFFF;
    transition: border-color 0.15s ease, box-shadow 0.15s ease, transform 0.15s ease;
    animation: pendingIn 0.28s cubic-bezier(0.2, 0.7, 0.3, 1) both;
}

.pending-item:hover {
    border-color: #CBD5E1;
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
    color: #0F766E;
    background: #E6FBF6;
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
    color: #1E293B;
    overflow-wrap: anywhere;
}

.pending-ref {
    font-size: 0.66rem;
    font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
    color: #94A3B8;
}

.pending-meta {
    display: flex;
    align-items: center;
    gap: 0.3rem;
    margin-top: 0.15rem;
    font-size: 0.7rem;
    color: #64748B;
}

.pending-tag {
    margin-left: 0.25rem;
    padding: 0.05rem 0.4rem;
    font-size: 0.62rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    border-radius: 999px;
    color: #475569;
    background: #F1F5F9;
}

.pending-tag--laboratory { color: #15803D; background: #DCFCE7; }
.pending-tag--xray       { color: #6D28D9; background: #EDE9FE; }

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
    color: #64748B;
    background: transparent;
    border: 1px solid #E2E8F0;
}

.pending-btn--ghost:hover { color: #1E293B; background: #F8FAFC; border-color: #CBD5E1; }

.pending-btn--approve {
    color: #FFFFFF;
    background: #0D9488;
    border: 1px solid #0D9488;
}

.pending-btn--approve:hover { background: #0F766E; border-color: #0F766E; color: #FFFFFF; }

.pending-pager {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.6rem;
    padding-top: 0.7rem;
    margin-top: 0.7rem;
    border-top: 1px solid #E2E8F0;
}

.pager-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    color: #64748B;
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 7px;
    cursor: pointer;
    transition: background-color 0.15s ease, color 0.15s ease;
}

.pager-btn:hover:not(:disabled) { color: #0D9488; background: #F0FDFA; border-color: #99F6E4; }
.pager-btn:disabled { opacity: 0.4; cursor: not-allowed; }

.pager-status {
    font-size: 0.72rem;
    color: #64748B;
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
    color: #0D9488;
    text-decoration: none;
    border-radius: 7px;
    transition: background-color 0.15s ease;
}

.pending-all:hover { background: #F0FDFA; color: #0F766E; }

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
.toast.error i   { color: #DC2626; }
.toast.warning i { color: #D97706; }
.toast.info i    { color: #2563EB; }

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

@media (max-width: 1500px) {
    .stats-row { grid-template-columns: repeat(4, minmax(0, 1fr)); }
}

@media (max-width: 1200px) {
    .stats-row  { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .main-row   { grid-template-columns: minmax(0, 1fr); }
    .bottom-row { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}

@media (max-width: 992px) {
    .dashboard-wrapper { padding: 16px; }
    .stats-row  { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .bottom-row { grid-template-columns: minmax(0, 1fr); }
}

@media (max-width: 768px) {
    .stat-card         { padding: 16px; }
    .chart-card        { padding: 16px; }
    .stat-value        { font-size: 1.2rem; }
    .stat-value-money  { font-size: 1.05rem; }
    .spark-inline      { width: 44px; height: 16px; }
    .chart-container,
    .empty-state-chart { height: 240px; }
}

@media (max-width: 576px) {
    .dashboard-wrapper { padding: 12px; }
    .stats-row  { grid-template-columns: minmax(0, 1fr); gap: 12px; }
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
   MODERN SKIN
   ============================================ */

.dashboard-wrapper,
#toastContainer {
    --db-radius:       16px;
    --db-radius-sm:    10px;
    --db-shadow:       0 1px 2px rgba(15, 23, 42, 0.04), 0 4px 14px -6px rgba(15, 23, 42, 0.06);
    --db-shadow-hover: 0 2px 4px rgba(15, 23, 42, 0.04), 0 14px 28px -10px rgba(15, 23, 42, 0.16);
}

.dashboard-wrapper {
    background:
        radial-gradient(900px 320px at 8% -80px, rgba(13, 148, 136, 0.08), transparent 70%),
        radial-gradient(700px 300px at 100% -60px, rgba(37, 99, 235, 0.05), transparent 70%),
        var(--db-canvas);
}

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

.stat-card {
    position: relative;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    background: linear-gradient(180deg, #FFFFFF 0%, #FCFDFE 100%);
    border: 1px solid rgba(226, 232, 240, 0.9);
    padding: 16px;
    transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
}

.stat-card:hover,
.stat-card:focus-visible {
    transform: translateY(-3px);
    border-color: color-mix(in srgb, var(--spark, #CBD5E1) 35%, #E2E8F0);
    box-shadow: var(--db-shadow-hover);
}

/* NOTE: the colored top line (::before) has been removed. */

.stat-top { margin-bottom: 12px; }

/* Icon wrapper stays transparent — no tile behind the PNG. */
.stat-icon { background: transparent; border-radius: 0; }

/* Keep the reduced KPI size stable across the modern skin. */
.stat-value { font-size: 1.35rem; margin-bottom: 0; }
.stat-label { margin-bottom: 0; }

.chart-card {
    border-color: rgba(226, 232, 240, 0.9);
    padding: 22px;
    transition: box-shadow 0.2s ease;
}

.chart-card:hover { box-shadow: var(--db-shadow-hover); }

.card-header { border-bottom: 0; padding-bottom: 0; margin-bottom: 18px; }

.card-title svg {
    box-sizing: content-box;
    padding: 6px;
    border-radius: 9px;
    background: var(--db-accent-soft);
}

.badge-time {
    border-radius: 999px;
    background: #FFFFFF;
    padding: 4px 11px;
}

.badge-pending { background: #FEF3C7; }

.avg-box {
    padding: 6px 12px;
    border-radius: 10px;
    background: var(--db-accent-soft);
}
.avg-value { color: #0F766E; }

.test-progress { height: 8px; border-radius: 999px; }
.test-fill { border-radius: 999px; }

.pending-item { border-radius: 12px; }

.btn-refresh {
    border-radius: 10px;
    box-shadow: var(--db-shadow);
}

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
    .dashboard-wrapper { background: #FFFFFF; padding: 0; min-height: 0; }
    .stat-card,
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
    accent:  '#0D9488',
    success: '#10B981',
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

function dbCompactPeso(value) {
    const number = Number(value) || 0;
    if (Math.abs(number) >= 1000000) {
        return '\u20B1' + (number / 1000000).toLocaleString('en-PH', { maximumFractionDigits: 1 }) + 'M';
    }
    if (Math.abs(number) >= 1000) {
        return '\u20B1' + (number / 1000).toLocaleString('en-PH', { maximumFractionDigits: 1 }) + 'k';
    }
    return '\u20B1' + number.toLocaleString('en-PH', { maximumFractionDigits: 0 });
}

const dbBaseTooltip = {
    backgroundColor: 'rgba(15, 23, 42, 0.94)',
    titleColor: '#F8FAFC',
    bodyColor: '#E2E8F0',
    footerColor: '#F8FAFC',
    borderColor: 'rgba(255, 255, 255, 0.08)',
    borderWidth: 1,
    padding: 12,
    cornerRadius: 10,
    boxPadding: 4,
    caretSize: 6,
    titleFont: { size: 12, weight: '600' },
    bodyFont: { size: 12 }
};

function dbGradient(color, topAlpha, bottomAlpha) {
    return function (context) {
        const chart = context.chart;
        const area = chart.chartArea;
        if (!area) { return color; }
        const g = chart.ctx.createLinearGradient(0, area.top, 0, area.bottom);
        g.addColorStop(0, color.replace('__A__', topAlpha));
        g.addColorStop(1, color.replace('__A__', bottomAlpha));
        return g;
    };
}

const dbCrosshair = {
    id: 'dbCrosshair',
    afterDatasetsDraw: function (chart) {
        const active = chart.tooltip && chart.tooltip.getActiveElements();
        if (!active || !active.length) { return; }
        const x = active[0].element.x;
        const area = chart.chartArea;
        const ctx = chart.ctx;
        ctx.save();
        ctx.beginPath();
        ctx.setLineDash([4, 4]);
        ctx.strokeStyle = 'rgba(13, 148, 136, 0.35)';
        ctx.lineWidth = 1;
        ctx.moveTo(x, area.top);
        ctx.lineTo(x, area.bottom);
        ctx.stroke();
        ctx.restore();
    }
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

function dbLineAnimation() {
    if (DB_REDUCED_MOTION) { return { duration: 0 }; }
    return {
        duration: 900,
        easing: 'easeOutQuart',
        x: {
            type: 'number',
            easing: 'linear',
            duration: 22,
            from: NaN,
            delay: function (context) {
                if (context.type !== 'data' || context.xStarted) { return 0; }
                context.xStarted = true;
                return context.index * 22;
            }
        },
        y: {
            type: 'number',
            easing: 'easeOutQuart',
            duration: 320,
            from: function (context) {
                return context.chart.scales.y.getPixelForValue(0);
            },
            delay: function (context) {
                if (context.type !== 'data' || context.yStarted) { return 0; }
                context.yStarted = true;
                return context.index * 22;
            }
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

dbInitChart('revenueChart', {
    type: 'line',
    data: {
        labels: revenueData.labels,
        datasets: [{
            label: 'Revenue',
            data: revenueData.values,
            borderColor: DB_COLORS.accent,
            backgroundColor: dbGradient('rgba(13, 148, 136, __A__)', 0.28, 0),
            fill: true,
            tension: 0.4,
            pointBackgroundColor: DB_COLORS.accent,
            pointBorderColor: '#FFFFFF',
            pointBorderWidth: 2,
            pointHoverRadius: 6,
            pointRadius: function (ctx) {
                return ctx.dataIndex === ctx.dataset.data.length - 1 ? 5 : 0;
            },
            borderWidth: 2.5
        }]
    },
    plugins: [dbCrosshair],
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: {
            legend: { display: false },
            tooltip: Object.assign({}, dbBaseTooltip, {
                displayColors: false,
                callbacks: {
                    label: function (context) {
                        return 'Revenue: ' + dbFormatPeso(context.parsed.y, 2);
                    }
                }
            })
        },
        animation: dbLineAnimation(),
        scales: dbScales(function (value) { return dbCompactPeso(value); })
    }
});

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
                hoverBackgroundColor: '#0F766E',
                borderRadius: { topLeft: 0, topRight: 0, bottomLeft: 6, bottomRight: 6 },
                borderSkipped: false,
                barPercentage: 0.7,
                categoryPercentage: 0.8,
                maxBarThickness: 34,
                stack: 'departments'
            },
            {
                label: 'X-Ray',
                data: deptXray,
                backgroundColor: '#7C3AED',
                hoverBackgroundColor: '#6D28D9',
                borderRadius: { topLeft: 6, topRight: 6, bottomLeft: 0, bottomRight: 0 },
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
                borderRadius: 6,
                borderSkipped: false,
                barPercentage: 0.7,
                categoryPercentage: 0.8,
                maxBarThickness: 34
            },
            {
                label: 'Completed',
                data: requestsData.completed,
                backgroundColor: DB_COLORS.success,
                borderRadius: 6,
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