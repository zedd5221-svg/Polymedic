<?= $this->extend('Layouts/ReceptionistLayout') ?>

<?= $this->section('pageTitle') ?>Dashboard<?= $this->endSection() ?>

<?= $this->section('receptionistContent') ?>

<?php
/* ------------------------------------------------------------------
   View-local helpers. No controller or database logic is changed.
   ------------------------------------------------------------------ */
$appointments = (isset($today_appointments) && is_array($today_appointments)) ? $today_appointments : [];

$slug = static function ($value, $fallback = 'unknown') {
    $value = preg_replace('/[^a-z0-9_-]/', '', strtolower(trim((string) $value)));
    return $value !== '' ? $value : $fallback;
};

$initialsOf = static function ($name) {
    $name = trim((string) $name);
    if ($name === '') {
        return '?';
    }
    $parts = preg_split('/\s+/', $name);
    $first = mb_substr($parts[0], 0, 1);
    $last  = count($parts) > 1 ? mb_substr(end($parts), 0, 1) : '';
    return mb_strtoupper($first . $last);
};

/* ------------------------------------------------------------------
   SERVICE DISTRIBUTION
   ------------------------------------------------------------------ */
$serviceCounts = [];

if (!empty($service_labels) && !empty($service_counts)
    && is_array($service_labels) && is_array($service_counts)
    && count($service_labels) === count($service_counts)) {

    $serviceCounts = array_combine($service_labels, $service_counts);

} else {
    foreach ($appointments as $appt) {
        $svc = trim((string) ($appt['service_type'] ?? ''));
        $svc = $svc !== '' ? ucfirst(strtolower($svc)) : 'Unspecified';
        $serviceCounts[$svc] = ($serviceCounts[$svc] ?? 0) + 1;
    }
}

arsort($serviceCounts);

$serviceTotal = array_sum($serviceCounts);

$donutPalette = ['#175563', '#D29A1E', '#3E9B5A', '#7C8DA0', '#D0654A', '#0E8A7E'];

/* ------------------------------------------------------------------
   APPOINTMENT TRENDS
   ------------------------------------------------------------------ */
$trendValues = (isset($weekly_appointments) && is_array($weekly_appointments)) ? array_values($weekly_appointments) : [];
$trendLabels = (isset($weekly_labels) && is_array($weekly_labels) && count($weekly_labels) === count($trendValues))
    ? array_values($weekly_labels)
    : ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

/* ------------------------------------------------------------------
   APPOINTMENT CALENDAR: one square per day, Mon-Sun down, weeks across.
   Shade = that day's appointments against the busiest day on show.
   ------------------------------------------------------------------ */
$calendar  = (isset($appointment_calendar) && is_array($appointment_calendar)) ? $appointment_calendar : [];
$calYear   = (int) date('Y');
$calToday  = date('Y-m-d');

$calTotal = static function (int $year) use ($calendar): int {
    $sum = 0;
    foreach ($calendar as $d => $n) { if (strncmp($d, $year . '-', 5) === 0) { $sum += (int) $n; } }
    return $sum;
};

$calPeak  = $calendar ? max(array_map('intval', $calendar)) : 0;
/* Square-root scale: one very busy day no longer pushes every 1-3 booking day
   into the palest step. With a peak of 16: 1 -> l1, 2-4 -> l2, 5-9 -> l3, 10+ -> l4. */
$calLevel = static function (int $n) use ($calPeak): int {
    return $n <= 0 ? 0 : max(1, min(4, (int) ceil(sqrt($n / max(1, $calPeak)) * 4)));
};

/* Weeks of one year, Monday first. This year stops at the later of today and the last booking. */
$calBuild = static function (int $year) use ($calendar, $calLevel, $calToday, $calYear): array {
    $lastDay = $year . '-12-31';
    if ($year === $calYear) {
        $lastDay = $calToday;
        foreach ($calendar as $d => $n) { if ($d > $lastDay && strncmp($d, $year . '-', 5) === 0) { $lastDay = $d; } }
        $lastDay = date('Y-m-d', strtotime('sunday this week', strtotime($lastDay)));
    }

    $cursor = strtotime('monday this week', strtotime($year . '-01-01'));
    $end    = strtotime($lastDay);
    $weeks  = [];
    $months = [];
    while ($cursor <= $end) {
        $week = [];
        for ($i = 0; $i < 7; $i++) {
            $iso  = date('Y-m-d', $cursor);
            $inYr = (int) date('Y', $cursor) === $year;
            $n    = (int) ($calendar[$iso] ?? 0);
            if ($inYr && (int) date('j', $cursor) === 1) { $months[count($weeks)] = date('M', $cursor); }
            $week[] = ['iso' => $iso, 'in' => $inYr, 'n' => $n, 'level' => $calLevel($n), 'ahead' => $iso > $calToday];
            $cursor = strtotime('+1 day', $cursor);
        }
        $weeks[] = $week;
    }

    /* The first month label sits on the first column even when Jan 1 is mid-week */
    if (!isset($months[0])) { $months = [0 => 'Jan'] + $months; unset($months[1]); }

    return ['weeks' => $weeks, 'months' => $months];
};

$calYears = [$calYear => $calBuild($calYear), $calYear - 1 => $calBuild($calYear - 1)];

/* Footnotes for the year on show: busiest day, days with appointments, average on those days */
$calFacts = [];
foreach ($calYears as $y => $_) {
    $days = array_filter($calendar, static fn ($n, $d) => strncmp($d, $y . '-', 5) === 0 && $n > 0, ARRAY_FILTER_USE_BOTH);
    arsort($days);
    $calFacts[$y] = [
        'busiest' => $days ? array_key_first($days) : null,
        'peak'    => $days ? (int) reset($days) : 0,
        'active'  => count($days),
        'avg'     => $days ? array_sum($days) / count($days) : 0,
    ];
}


$statusCounts = [];
foreach ($appointments as $appt) {
    $st = $slug($appt['status'] ?? '', 'unknown');
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
            'key'     => 'patients',
            'icon'    => 'people-blue.png',
            'color'   => '#175563',
            'label'   => 'All Patients',
            'value'   => (int) ($total_patients ?? 0),
            'delta'   => 'window',
            'context' => 'new in 14 days',
            'unit'    => 'patients',
            'link'    => base_url('receptionist/patients'),
        ],
        [
            'key'     => 'today',
            'icon'    => 'document.png',
            'color'   => '#0891b2',
            'label'   => "Today's Appointments",
            'value'   => count($appointments),
            'delta'   => 'dod',
            'context' => 'vs yesterday',
            'unit'    => 'appointments',
            'link'    => base_url('receptionist/appointments'),
        ],
        [
            'key'     => 'collections',
            'icon'    => 'money-yellow.png',
            'color'   => '#059669',
            'label'   => "Today's Collections",
            'value'   => (float) ($today_collections ?? 0),
            'money'   => true,
            'delta'   => 'dod',
            'context' => 'vs yesterday',
            'unit'    => 'collections',
            'link'    => null,
        ],
        [
            'key'     => 'unpaid',
            'icon'    => 'no-money-red.png',
            'color'   => '#DB2777',
            'label'   => 'Unpaid Bills',
            'value'   => (int) ($unpaid_bills ?? 0),
            'delta'   => null,
            'context' => 'Outstanding balance',
            'unit'    => 'new unpaid bills',
            'link'    => base_url('receptionist/billing?status=unpaid'),
        ],
    ],
    /* Separate queues, not statuses of one set: no share bar. */
    'pipeline' => [
        'title'  => 'Front Desk Queue',
        'sub'    => 'Items that need front-desk action',
        'link'   => base_url('receptionist/appointments'),
        'split'  => false,
        'stages' => [
            ['key' => 'pending',     'color' => '#f59e0b', 'label' => 'Pending Appts',       'hint' => 'Awaiting approval',     'count' => (int) ($pending_appointments ?? 0), 'today' => 'new today',  'link' => base_url('receptionist/appointments')],
            ['key' => 'completed',   'color' => '#10b981', 'label' => 'Completed Today',     'hint' => 'Appointments done',     'count' => (int) ($today_completed ?? 0),      'today' => 'done today', 'link' => base_url('receptionist/appointments')],
            ['key' => 'diagnostics', 'color' => '#3b82f6', 'label' => 'Pending Diagnostics', 'hint' => 'Waiting to be started', 'count' => (int) ($pending_diagnostic ?? 0),   'today' => 'new today',  'link' => base_url('receptionist/diagnostic-requests')],
        ],
    ],
];
?>

<div class="dashboard-container">

    <!-- ===== KPI OVERVIEW ===== -->
    <?= view('partials/kpi_board', $kpiBoard) ?>


    <!-- ===== CHARTS ROW ===== -->
    <div class="charts-row">

        <!-- Appointment Trends (calendar heatmap) -->
        <div class="chart-card cal-card">

            <div class="chart-header">
                <div class="chart-heading">
                    <h5>Appointment Trends</h5>
                    <small>Appointments per day</small>
                </div>
                <label class="cal-range">
                    <i class="bi bi-calendar3" aria-hidden="true"></i>
                    <span class="cal-range-text">
                        <small>Dates</small>
                        <select id="calYearSelect" aria-label="Year shown">
                            <option value="<?= $calYear ?>" selected>This year</option>
                            <option value="<?= $calYear - 1 ?>">Last year</option>
                        </select>
                    </span>
                    <i class="bi bi-chevron-down cal-range-caret" aria-hidden="true"></i>
                </label>
            </div>

            <div class="cal-stats">
                <div class="cal-stat">
                    <span class="cal-stat-value"><?= number_format($calTotal($calYear)) ?></span>
                    <span class="cal-stat-label">Appointments this year</span>
                </div>
                <div class="cal-stat">
                    <span class="cal-stat-value"><?= number_format($calTotal($calYear - 1)) ?></span>
                    <span class="cal-stat-label">Appointments last year</span>
                </div>
                <div class="cal-key" aria-hidden="true">
                    <span>Less</span>
                    <?php for ($l = 0; $l <= 4; $l++): ?><i class="cal-cell l<?= $l ?>"></i><?php endfor; ?>
                    <span>More</span>
                </div>
            </div>

            <div class="cal-body" id="calBody">
                <?php foreach ($calYears as $y => $cal): $cols = count($cal['weeks']); ?>
                    <div class="cal-year" data-year="<?= $y ?>" <?= $y === $calYear ? '' : 'hidden' ?>
                         role="img" aria-label="<?= esc(number_format($calTotal($y)) . ' appointments in ' . $y, 'attr') ?>">
                        <div class="cal-months" style="--weeks: <?= $cols ?>" aria-hidden="true">
                            <?php foreach ($cal['months'] as $col => $name): ?>
                                <span style="grid-column: <?= $col + 1 ?>"><?= esc($name) ?></span>
                            <?php endforeach; ?>
                        </div>
                        <div class="cal-days" aria-hidden="true">
                            <span>Mon</span><span></span><span>Wed</span><span></span><span>Fri</span><span></span><span></span>
                        </div>
                        <div class="cal-grid" style="--weeks: <?= $cols ?>">
                            <?php foreach ($cal['weeks'] as $week): foreach ($week as $c):
                                if (!$c['in']) { echo '<i class="cal-cell is-out"></i>'; continue; }
                                $when = date('D, M j, Y', strtotime($c['iso']));
                                $what = $c['n'] === 0 ? 'No appointments' : $c['n'] . ($c['n'] === 1 ? ' appointment' : ' appointments');
                                $cls  = 'cal-cell l' . $c['level'] . ($c['ahead'] ? ' is-ahead' : '') . ($c['iso'] === $calToday ? ' is-today' : '');
                            ?><i class="<?= $cls ?>" data-tip="<?= esc($when . '|' . $what . ($c['ahead'] && $c['n'] > 0 ? ' booked' : ''), 'attr') ?>"></i><?php endforeach; endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                <div class="cal-tip" id="calTip" aria-hidden="true"></div>
            </div>

            <?php foreach ($calFacts as $y => $f): ?>
                <dl class="cal-facts" data-year="<?= $y ?>" <?= $y === $calYear ? '' : 'hidden' ?>>
                    <div><dt>Busiest day</dt><dd><?= $f['busiest'] ? esc(date('M j', strtotime($f['busiest']))) . ' <small>' . $f['peak'] . '</small>' : '<span class="cal-none">&mdash;</span>' ?></dd></div>
                    <div><dt>Days with appointments</dt><dd><?= number_format($f['active']) ?></dd></div>
                    <div><dt>Average on those days</dt><dd><?= $f['active'] ? number_format($f['avg'], 1) : '<span class="cal-none">&mdash;</span>' ?></dd></div>
                </dl>
            <?php endforeach; ?>

        </div>

        <!-- Service Distribution (donut) -->
        <div class="chart-card">

            <div class="chart-header">
                <div class="chart-heading">
                    <h5>Service Distribution</h5>
                    <small>Today's appointments</small>
                </div>
            </div>

            <?php if ($serviceTotal > 0): ?>

                <div class="donut-wrap">

                    <div class="donut-side">
                        <div class="donut-total">
                            <span class="donut-value"><?= number_format($serviceTotal) ?></span>
                            <span class="donut-unit"><?= $serviceTotal === 1 ? 'appointment' : 'appointments' ?></span>
                        </div>

                    <ul class="donut-legend">
                        <?php $legendIndex = 0; ?>
                        <?php foreach ($serviceCounts as $svcName => $svcCount): ?>
                            <?php
                                $pct = $serviceTotal > 0 ? ($svcCount / $serviceTotal) * 100 : 0;
                                $color = $donutPalette[$legendIndex % count($donutPalette)];
                                $legendIndex++;
                            ?>
                            <li class="legend-item">
                                <span class="legend-dot" style="background: <?= esc($color, 'attr') ?>;"></span>
                                <span class="legend-name"><?= esc($svcName) ?></span>
                                <span class="legend-count"><?= number_format($svcCount) ?></span>
                                <span class="legend-pct"><?= number_format($pct, $pct < 10 ? 1 : 0) ?>%</span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    </div>

                    <?php
                    /*
                     * Stepped pie: every share is a slice; the biggest reaches the full
                     * radius and the rest step in by rank, over a pale disc. Colours
                     * follow the legend order. Biggest slice sits at the lower left.
                     */
                    $pieSlices = [];
                    foreach (array_keys($serviceCounts) as $pi => $svcName) {
                        $pieSlices[] = ['name' => $svcName, 'count' => (int) $serviceCounts[$svcName],
                                        'color' => $donutPalette[$pi % count($donutPalette)]];
                    }
                    $byRank = $pieSlices;
                    usort($byRank, static fn ($a, $b) => $b['count'] <=> $a['count']);
                    $radii  = [96, 84, 77, 71, 66, 62];
                    $rankOf = [];
                    foreach ($byRank as $rank => $s) { $rankOf[$s['name']] = $rank; }

                    $pt = static fn (float $deg, float $r): string =>
                        number_format(100 + $r * sin(deg2rad($deg)), 2, '.', '') . ' ' . number_format(100 - $r * cos(deg2rad($deg)), 2, '.', '');

                    // start so the biggest slice is centred at about 8 o'clock (240°, clockwise from 12)
                    $firstShare = $serviceTotal > 0 ? $pieSlices[0]['count'] / $serviceTotal * 360 : 0;
                    $angle = 240 - $firstShare / 2;
                    ?>
                    <div class="donut-canvas">
                        <svg class="pie" viewBox="0 0 200 200" role="img"
                             aria-label="Share of today's appointments by service">
                            <circle class="pie-track" cx="100" cy="100" r="88"></circle>
                            <?php foreach ($pieSlices as $n => $s):
                                $sweep = $serviceTotal > 0 ? $s['count'] / $serviceTotal * 360 : 0;
                                if ($sweep <= 0) { continue; }
                                $r   = $radii[min($rankOf[$s['name']], count($radii) - 1)];
                                $pct = $s['count'] / $serviceTotal * 100;
                                $mid = $angle + $sweep / 2;
                                $tip = $s['name'] . ': ' . number_format($s['count']) . ' (' . number_format($pct, $pct < 10 ? 1 : 0) . '%)';
                            ?>
                                <g class="pie-slice" style="--d: <?= $n * 70 ?>ms">
                                    <title><?= esc($tip) ?></title>
                                    <?php if ($sweep >= 359.99): ?>
                                        <circle cx="100" cy="100" r="<?= $r ?>" fill="<?= esc($s['color'], 'attr') ?>"></circle>
                                    <?php else: ?>
                                        <path d="M100 100 L<?= $pt($angle, $r) ?> A<?= $r ?> <?= $r ?> 0 <?= $sweep > 180 ? 1 : 0 ?> 1 <?= $pt($angle + $sweep, $r) ?> Z"
                                              fill="<?= esc($s['color'], 'attr') ?>"></path>
                                    <?php endif; ?>
                                    <?php if ($pct >= 6): ?>
                                        <?php [$lx, $ly] = explode(' ', $pt($mid, $sweep >= 359.99 ? 0 : $r * 0.6)); ?>
                                        <text class="pie-label" x="<?= $lx ?>" y="<?= $ly ?>"><?= number_format($pct, 0) ?>%</text>
                                    <?php endif; ?>
                                </g>
                            <?php $angle += $sweep; endforeach; ?>
                        </svg>
                    </div>

                </div>

            <?php else: ?>
                <div class="chart-body chart-empty">
                    <i class="bi bi-pie-chart" aria-hidden="true"></i>
                    <p>No appointments today</p>
                    <small>The service breakdown appears once appointments are booked</small>
                </div>
            <?php endif; ?>

        </div>
    </div>


    <!-- ===== TODAY'S APPOINTMENTS: the day's schedule ===== -->
    <?php
        /* In time order; the first one still to happen is "next up" */
        $schedule = $appointments;
        usort($schedule, static function ($a, $b) {
            return strcmp((string) ($a['appointment_time'] ?? ''), (string) ($b['appointment_time'] ?? ''));
        });

        $nowClock = date('H:i');
        $nextId   = null;
        foreach ($schedule as $appt) {
            $st = $slug($appt['status'] ?? '', 'unknown');
            $tm = substr(trim((string) ($appt['appointment_time'] ?? '')), 0, 5);
            if (in_array($st, ['pending', 'approved'], true) && $tm !== '' && $tm >= $nowClock) {
                $nextId = $appt['id'] ?? null;
                break;
            }
        }

        $statusText = [
            'pending' => 'Pending', 'approved' => 'Approved', 'completed' => 'Completed',
            'cancelled' => 'Cancelled', 'late' => 'Late', 'no_show' => 'No show',
        ];
        $filters = [
            'all'       => ['All', count($schedule)],
            'pending'   => ['Pending', (int) ($statusCounts['pending'] ?? 0)],
            'approved'  => ['Approved', (int) ($statusCounts['approved'] ?? 0)],
            'completed' => ['Completed', (int) ($statusCounts['completed'] ?? 0)],
        ];
    ?>
    <section class="today-card" aria-labelledby="todayTitle">

        <header class="today-head">
            <div class="chart-heading">
                <h5 id="todayTitle">Today's Appointments</h5>
                <small><?= esc(date('l, F j, Y')) ?></small>
            </div>

            <div class="today-tools">
                <?php if ($schedule): ?>
                    <div class="today-filter" role="tablist" aria-label="Filter by status">
                        <?php foreach ($filters as $key => [$label, $count]): ?>
                            <button type="button" role="tab" class="today-filter-btn<?= $key === 'all' ? ' is-active' : '' ?>"
                                    aria-selected="<?= $key === 'all' ? 'true' : 'false' ?>" data-filter="<?= $key ?>">
                                <?= esc($label) ?> <span><?= number_format($count) ?></span>
                            </button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <a href="<?= base_url('receptionist/appointments') ?>" class="today-all">
                    View all <i class="bi bi-arrow-right" aria-hidden="true"></i>
                </a>
            </div>
        </header>

        <?php if ($schedule): ?>
            <ol class="today-list" id="todayList">
                <?php foreach ($schedule as $appointment):
                    $apptId    = (int) ($appointment['id'] ?? 0);
                    $statusKey = $slug($appointment['status'] ?? '', 'unknown');
                    $fullName  = trim((string) ($appointment['full_name'] ?? ''));
                    $apptTime  = trim((string) ($appointment['appointment_time'] ?? ''));
                    $ts        = $apptTime !== '' ? strtotime($apptTime) : false;

                    $labList  = json_decode($appointment['lab_services']  ?? '[]', true);
                    $xrayList = json_decode($appointment['xray_services'] ?? '[]', true);
                    $hasLab   = is_array($labList)  && count(array_filter($labList))  > 0;
                    $hasXray  = is_array($xrayList) && count(array_filter($xrayList)) > 0;
                    if (!$hasLab && !$hasXray) {
                        $svcRaw  = strtolower(trim((string) ($appointment['service_type'] ?? '')));
                        $hasLab  = in_array($svcRaw, ['laboratory', 'lab', 'both'], true);
                        $hasXray = in_array($svcRaw, ['xray', 'x-ray', 'both'], true);
                    }

                    $meta = array_filter([
                        ($appointment['age'] ?? '') !== '' ? $appointment['age'] . ' y' : '',
                        trim((string) ($appointment['gender'] ?? '')),
                    ]);
                    $isNext = $nextId !== null && $apptId === (int) $nextId;
                ?>
                    <li class="today-row is-<?= esc($statusKey, 'attr') ?><?= $isNext ? ' is-next' : '' ?>" data-status="<?= esc($statusKey, 'attr') ?>">

                        <div class="today-time">
                            <?php if ($ts): ?>
                                <b><?= esc(date('g:i', $ts)) ?></b><span><?= esc(date('A', $ts)) ?></span>
                            <?php else: ?>
                                <b class="today-none">&mdash;</b>
                            <?php endif; ?>
                        </div>

                        <span class="today-rail" aria-hidden="true"></span>

                        <div class="today-who">
                            <span class="today-avatar" aria-hidden="true"><?= esc($initialsOf($fullName)) ?></span>
                            <div class="today-who-text">
                                <a class="today-name" href="<?= base_url('receptionist/appointment/view/' . $apptId) ?>">
                                    <?= esc($fullName !== '' ? $fullName : 'Unknown patient') ?>
                                </a>
                                <span class="today-meta">
                                    <?php if ($meta): ?><?= esc(implode(' · ', $meta)) ?> · <?php endif; ?>
                                    <span class="today-ref">APT-<?= esc(date('y')) ?>-<?= esc(str_pad((string) $apptId, 4, '0', STR_PAD_LEFT)) ?></span>
                                </span>
                            </div>
                            <?php if ($isNext): ?><span class="today-next">Next up</span><?php endif; ?>
                        </div>

                        <div class="today-svc">
                            <?php if ($hasLab): ?><span class="today-tag today-tag--lab">Laboratory</span><?php endif; ?>
                            <?php if ($hasXray): ?><span class="today-tag today-tag--xray">X-Ray</span><?php endif; ?>
                            <?php if (!$hasLab && !$hasXray): ?><span class="today-none">Not set</span><?php endif; ?>
                        </div>

                        <span class="today-status today-status--<?= esc($statusKey, 'attr') ?>"><?= esc($statusText[$statusKey] ?? ucfirst($statusKey)) ?></span>

                        <div class="today-actions">
                            <?php if ($statusKey === 'pending'): ?>
                                <a data-post href="<?= base_url('receptionist/appointment/cancel/' . $apptId) ?>"
                                   class="today-btn today-btn--quiet"
                                   aria-label="Cancel appointment for <?= esc($fullName, 'attr') ?>"
                                   onclick="return confirm('Cancel the <?= esc($apptTime, 'attr') ?> appointment for <?= esc($fullName, 'attr') ?>? This cannot be undone.')">Cancel</a>
                                <a data-post href="<?= base_url('receptionist/appointment/approve/' . $apptId) ?>"
                                   class="today-btn today-btn--primary"
                                   aria-label="Approve appointment for <?= esc($fullName, 'attr') ?>"
                                   onclick="return confirm('Approve the <?= esc($apptTime, 'attr') ?> appointment for <?= esc($fullName, 'attr') ?>?')">
                                    <i class="bi bi-check2" aria-hidden="true"></i>Approve
                                </a>
                            <?php else: ?>
                                <a href="<?= base_url('receptionist/appointment/view/' . $apptId) ?>"
                                   class="today-btn" aria-label="View details for <?= esc($fullName, 'attr') ?>">View</a>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ol>
            <p class="today-filter-empty" id="todayFilterEmpty" hidden>No appointments with this status today.</p>
        <?php else: ?>
            <div class="today-empty">
                <i class="bi bi-calendar-check" aria-hidden="true"></i>
                <p>No appointments scheduled for today</p>
                <small>Online bookings and front-desk appointments for today appear here.</small>
            </div>
        <?php endif; ?>
    </section>

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

/* =========================================================
   CHARTS ROW
   ========================================================= */

.dashboard-container .charts-row {
    display: grid;
    grid-template-columns: minmax(0, 1.55fr) minmax(0, 1fr);
    gap: 0.9rem;
    margin-bottom: 1.1rem;
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

/* =========================================================
   APPOINTMENT CALENDAR (heatmap)
   ========================================================= */

.dashboard-container .cal-card { display: flex; flex-direction: column; }

.dashboard-container .cal-range {
    position: relative;
    display: inline-flex;
    align-items: center;
    gap: 0.55rem;
    height: 42px;
    padding: 0 0.75rem 0 0.8rem;
    margin: 0;
    background: var(--db-rail);
    border: 1px solid var(--db-line);
    border-radius: 10px;
    cursor: pointer;
}

.dashboard-container .cal-range > .bi-calendar3 { font-size: 0.9rem; color: var(--db-ink); }
.dashboard-container .cal-range-text { display: flex; flex-direction: column; line-height: 1.1; }
.dashboard-container .cal-range-text small { font-size: 0.64rem; color: var(--db-ink-mute); }

.dashboard-container .cal-range select {
    padding: 0 1.1rem 0 0;
    margin: 0;
    font: inherit;
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--db-ink);
    background: transparent;
    border: 0;
    appearance: none;
    cursor: pointer;
    outline: none;
}

.dashboard-container .cal-range-caret { position: absolute; right: 0.7rem; font-size: 0.7rem; color: var(--db-ink-mute); pointer-events: none; }
.dashboard-container .cal-range:focus-within { outline: 2px solid #175563; outline-offset: 2px; }

.dashboard-container .cal-stats {
    display: flex;
    align-items: flex-end;
    gap: 2.25rem;
    margin-bottom: 1rem;
}

.dashboard-container .cal-stat { display: flex; flex-direction: column; gap: 0.2rem; }

.dashboard-container .cal-stat-value {
    font-size: 1.7rem;
    font-weight: 600;
    line-height: 1.05;
    letter-spacing: -0.03em;
    color: var(--db-ink);
    font-variant-numeric: tabular-nums;
}

.dashboard-container .cal-stat-label { font-size: 0.75rem; color: var(--db-ink-mute); }

.dashboard-container .cal-key {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    margin-left: auto;
    font-size: 0.7rem;
    color: var(--db-ink-mute);
}

.dashboard-container .cal-key span { margin: 0 4px; }
.dashboard-container .cal-key .cal-cell { width: 12px; height: 12px; }

/* Grid: day names in a 30px column, then one column per week; squares keep their shape */
.dashboard-container .cal-body { position: relative; }
.dashboard-container .cal-year[hidden],
.dashboard-container .cal-facts[hidden] { display: none; }

.dashboard-container .cal-year {
    display: grid;
    grid-template-columns: 30px minmax(0, 1fr);
    grid-template-rows: auto auto;
    column-gap: 6px;
    row-gap: 6px;
}

.dashboard-container .cal-months {
    grid-column: 2;
    display: grid;
    grid-template-columns: repeat(var(--weeks), minmax(0, 1fr));
    column-gap: 3px;
    font-size: 0.7rem;
    color: var(--db-ink-mute);
    white-space: nowrap;
}

.dashboard-container .cal-days {
    grid-column: 1;
    grid-row: 2;
    display: grid;
    grid-template-rows: repeat(7, minmax(0, 1fr));
    row-gap: 3px;
    font-size: 0.68rem;
    color: var(--db-ink-mute);
}

.dashboard-container .cal-days span { display: flex; align-items: center; }

.dashboard-container .cal-grid {
    grid-column: 2;
    grid-row: 2;
    display: grid;
    grid-template-columns: repeat(var(--weeks), minmax(0, 1fr));
    grid-template-rows: repeat(7, auto);
    grid-auto-flow: column;
    gap: 3px;
}

.dashboard-container .cal-cell {
    display: block;
    aspect-ratio: 1;
    border-radius: 3px;
    background: var(--pm-dk-bg-neutral, #F0F3F7);
    transition: box-shadow 0.12s ease;
}

/* The first step must read clearly against an empty day, so it starts mid-blue. */
.dashboard-container .cal-cell.l1 { background: #A9CDD4; }
.dashboard-container .cal-cell.l2 { background: #6AA6B2; }
.dashboard-container .cal-cell.l3 { background: #23697A; }
.dashboard-container .cal-cell.l4 { background: #133B46; }
.dashboard-container .cal-cell.is-out { visibility: hidden; }

/* Days still to come: bookings already made, shown a little lighter */
.dashboard-container .cal-cell.is-ahead    { opacity: 0.55; }
.dashboard-container .cal-cell.is-ahead.l0 { background: var(--pm-subtle); opacity: 1; }
/* Outside the cell so it still shows on the darkest step. */
.dashboard-container .cal-cell.is-today    { outline: 2px solid #142D36; outline-offset: 1px; }

.dashboard-container .cal-grid .cal-cell:hover { box-shadow: 0 0 0 2px #FFFFFF, 0 0 0 3.5px #142D36; }

.dashboard-container .cal-tip {
    position: absolute;
    top: 0;
    left: 0;
    z-index: 3;
    padding: 6px 9px;
    font-size: 0.72rem;
    line-height: 1.35;
    white-space: nowrap;
    color: #D6E2EE;
    background: #142D36;
    border-radius: 6px;
    box-shadow: 0 6px 18px rgba(20, 45, 54, 0.22);
    pointer-events: none;
    opacity: 0;
    transition: opacity 0.1s ease;
}

.dashboard-container .cal-tip.is-on { opacity: 1; }
.dashboard-container .cal-tip b { display: block; font-weight: 600; color: #FFFFFF; }

.dashboard-container .cal-facts {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem 2rem;
    margin: auto 0 0;
    padding-top: 1rem;
}

.dashboard-container .cal-facts > div { min-width: 0; }
.dashboard-container .cal-facts dt { font-size: 0.7rem; font-weight: 500; color: var(--db-ink-mute); }
.dashboard-container .cal-facts dd { margin: 0.15rem 0 0; font-size: 0.9rem; font-weight: 600; color: var(--db-ink); font-variant-numeric: tabular-nums; }
.dashboard-container .cal-facts dd small { font-size: 0.72rem; font-weight: 500; color: var(--db-ink-mute); }
.dashboard-container .cal-none { color: var(--db-ink-faint); font-weight: 400; }

@media (max-width: 640px) {
    .dashboard-container .cal-stats { flex-wrap: wrap; gap: 1rem 1.5rem; }
    .dashboard-container .cal-key { margin-left: 0; width: 100%; }
    .dashboard-container .cal-body { overflow-x: auto; }
    .dashboard-container .cal-year { min-width: 640px; }
}

.dashboard-container .cal-months span { min-width: 0; overflow: visible; }

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
   DONUT + LEGEND
   ========================================================= */

/* The total and its legend on the left, the ring on the right */
.dashboard-container .donut-wrap {
    display: flex;
    align-items: center;
    gap: 1rem;
    min-height: 280px;
}

.dashboard-container .donut-side { flex: 1; min-width: 0; }

.dashboard-container .donut-canvas {
    position: relative;
    width: 156px;
    height: 156px;
    flex-shrink: 0;
}

/* Stepped pie: slices of different radius over a pale disc */
.dashboard-container .pie { display: block; width: 100%; height: 100%; overflow: visible; }
.dashboard-container .pie-track { fill: #EEF0F3; }
.dashboard-container .pie-slice path,
.dashboard-container .pie-slice circle {
    stroke: var(--pm-surface, #fff);
    stroke-width: 2;
    stroke-linejoin: round;
}
.dashboard-container .pie-slice {
    transform-origin: 100px 100px;
    transition: transform .2s cubic-bezier(.32,.72,0,1), opacity .2s ease;
    animation: pieIn .55s cubic-bezier(.32,.72,0,1) both;
    animation-delay: var(--d, 0ms);
}
.dashboard-container .pie:hover .pie-slice { opacity: .55; }
.dashboard-container .pie .pie-slice:hover { opacity: 1; transform: scale(1.04); }
.dashboard-container .pie-label {
    fill: #fff;
    font-size: 11px;
    font-weight: 600;
    text-anchor: middle;
    dominant-baseline: central;
    pointer-events: none;
    font-variant-numeric: tabular-nums;
}
@keyframes pieIn {
    from { opacity: 0; transform: scale(.82); }
    to   { opacity: 1; transform: none; }
}
@media (prefers-reduced-motion: reduce) {
    .dashboard-container .pie-slice { animation: none; transition: none; }
}

.dashboard-container .donut-total {
    display: flex;
    align-items: baseline;
    gap: 0.45rem;
    margin-bottom: 1.25rem;
}

.dashboard-container .donut-value {
    font-size: 2.1rem;
    font-weight: 500;
    letter-spacing: -0.03em;
    line-height: 1;
    color: var(--db-ink);
    font-variant-numeric: tabular-nums;
}

.dashboard-container .donut-unit {
    font-size: 0.95rem;
    font-weight: 500;
    color: var(--db-ink-soft);
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
    gap: 0.6rem;
    padding: 0.6rem 0.25rem;
    border-top: 1px solid var(--db-line);
    border-radius: var(--db-r-sm);
    font-size: 0.8rem;
    transition: background-color 0.16s ease;
}

.dashboard-container .legend-item:hover { background: var(--db-rail); }

.dashboard-container .legend-item:first-child { border-top: 0; }

.dashboard-container .legend-dot {
    width: 12px;
    height: 12px;
    border-radius: 3px;
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

.dashboard-container .legend-count {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--db-ink);
    font-variant-numeric: tabular-nums;
    min-width: 1.75rem;
    text-align: right;
}

.dashboard-container .legend-pct {
    font-size: 0.75rem;
    color: var(--db-ink-mute);
    font-variant-numeric: tabular-nums;
    min-width: 2.6rem;
    text-align: right;
}

/* =========================================================
   TODAY'S APPOINTMENTS: a day schedule
   time | rail | patient | services | status | actions
   ========================================================= */

.dashboard-container .today-card {
    background: var(--db-surface);
    border: 1px solid var(--db-line);
    border-radius: var(--db-r-lg);
    overflow: hidden;
}

.dashboard-container .today-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
    padding: 1.05rem 1.25rem;
    border-bottom: 1px solid var(--db-line);
}

.dashboard-container .today-tools { display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap; }

.dashboard-container .today-filter {
    display: inline-flex;
    gap: 2px;
    padding: 3px;
    background: var(--db-rail);
    border: 1px solid var(--db-line);
    border-radius: 9px;
}

.dashboard-container .today-filter-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    height: 28px;
    padding: 0 0.7rem;
    font: inherit;
    font-size: 0.75rem;
    font-weight: 500;
    color: var(--db-ink-soft);
    background: transparent;
    border: 0;
    border-radius: 6px;
    cursor: pointer;
    transition: background-color 0.15s ease, color 0.15s ease;
}

.dashboard-container .today-filter-btn span {
    min-width: 1.1rem;
    padding: 0 0.3rem;
    font-size: 0.68rem;
    font-weight: 600;
    line-height: 1.1rem;
    text-align: center;
    color: var(--db-ink-mute);
    background: var(--db-line-soft);
    border-radius: 4px;
    font-variant-numeric: tabular-nums;
}

.dashboard-container .today-filter-btn:hover { color: var(--db-ink); }
.dashboard-container .today-filter-btn.is-active { color: #FFFFFF; background: #142D36; }
.dashboard-container .today-filter-btn.is-active span { color: #FFFFFF; background: rgba(255, 255, 255, 0.18); }

.dashboard-container .today-all {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--db-accent);
    text-decoration: none;
}

.dashboard-container .today-all:hover { color: var(--db-accent-dark); }
.dashboard-container .today-all i { font-size: 0.75rem; }

/* ---------- Rows ---------- */

.dashboard-container .today-list { margin: 0; padding: 0.35rem 0; list-style: none; }

.dashboard-container .today-row {
    position: relative;
    display: grid;
    grid-template-columns: 64px 18px minmax(0, 1.6fr) minmax(0, 1fr) 104px 176px;
    align-items: center;
    column-gap: 0.75rem;
    min-height: 68px;
    padding: 0 1.25rem;
    transition: background-color 0.15s ease;
}

.dashboard-container .today-row:hover { background: var(--db-rail); }
.dashboard-container .today-row[hidden] { display: none; }

.dashboard-container .today-row + .today-row::after {
    content: "";
    position: absolute;
    top: 0;
    left: calc(1.25rem + 64px + 18px + 1.5rem);
    right: 1.25rem;
    height: 1px;
    background: var(--db-line-soft);
}

/* Time */
.dashboard-container .today-time { display: flex; flex-direction: column; align-items: flex-end; line-height: 1.1; }
.dashboard-container .today-time b { font-size: 1rem; font-weight: 600; color: var(--db-ink); font-variant-numeric: tabular-nums; }
.dashboard-container .today-time span { font-size: 0.68rem; font-weight: 500; color: var(--db-ink-mute); }

/* Timeline rail with the status dot */
.dashboard-container .today-rail { position: relative; align-self: stretch; }

.dashboard-container .today-rail::before {
    content: "";
    position: absolute;
    left: 50%;
    top: 0;
    bottom: 0;
    width: 2px;
    margin-left: -1px;
    background: var(--db-line-soft);
}

.dashboard-container .today-row:first-child .today-rail::before { top: 50%; }
.dashboard-container .today-row:last-child  .today-rail::before { bottom: 50%; }
.dashboard-container .today-row:only-child  .today-rail::before { display: none; }

.dashboard-container .today-rail::after {
    content: "";
    position: absolute;
    left: 50%;
    top: 50%;
    width: 10px;
    height: 10px;
    margin: -5px 0 0 -5px;
    border-radius: 50%;
    background: var(--pm-surface);
    border: 2px solid var(--tone, #98A2B3);
}

.dashboard-container .today-row.is-completed .today-rail::after { background: var(--tone); }
.dashboard-container .today-row.is-next .today-rail::after { box-shadow: 0 0 0 4px #EBF2F4; }

.dashboard-container .today-row.is-pending   { --tone: #DC6803; }
.dashboard-container .today-row.is-approved  { --tone: #175563; }
.dashboard-container .today-row.is-completed { --tone: #17B26A; }
.dashboard-container .today-row.is-late      { --tone: #F04438; }
.dashboard-container .today-row.is-cancelled,
.dashboard-container .today-row.is-no_show   { --tone: #98A2B3; }

/* Next up: a pale blue band with a left accent */
.dashboard-container .today-row.is-next { background: var(--pm-dk-bg-neutral, #F3F8FD); }
.dashboard-container .today-row.is-next::before {
    content: "";
    position: absolute;
    left: 0;
    top: 8px;
    bottom: 8px;
    width: 3px;
    border-radius: 0 3px 3px 0;
    background: #175563;
}

.dashboard-container .today-next {
    flex: none;
    padding: 0.1rem 0.45rem;
    font-size: 0.68rem;
    font-weight: 600;
    color: var(--pm-dk-fg-teal, #10404B);
    background: var(--pm-dk-bg-blue, #DCEAF7);
    border-radius: 4px;
}

/* Patient */
.dashboard-container .today-who { display: flex; align-items: center; gap: 0.7rem; min-width: 0; }

.dashboard-container .today-avatar {
    display: grid;
    place-items: center;
    flex: none;
    width: 36px;
    height: 36px;
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--db-ink);
    background: var(--pm-avatar);
    border-radius: 50%;
}

.dashboard-container .today-who-text { display: flex; flex-direction: column; gap: 0.1rem; min-width: 0; }

.dashboard-container .today-name {
    font-size: 0.86rem;
    font-weight: 600;
    color: var(--db-ink);
    text-decoration: none;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.dashboard-container .today-name:hover { color: var(--db-accent); }

.dashboard-container .today-meta {
    font-size: 0.74rem;
    color: var(--db-ink-mute);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.dashboard-container .today-ref { font-family: var(--db-mono); font-size: 0.7rem; }

/* Services */
.dashboard-container .today-svc { display: flex; flex-wrap: wrap; gap: 0.35rem; min-width: 0; }

.dashboard-container .today-tag {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.15rem 0.5rem;
    font-size: 0.72rem;
    font-weight: 500;
    color: var(--db-ink-soft);
    background: var(--db-rail);
    border: 1px solid var(--db-line);
    border-radius: 5px;
    white-space: nowrap;
}

.dashboard-container .today-tag::before { content: ""; width: 7px; height: 7px; border-radius: 2px; background: var(--kind); }
.dashboard-container .today-tag--lab  { --kind: #175563; }
.dashboard-container .today-tag--xray { --kind: #0E8A7E; }

.dashboard-container .today-none { font-size: 0.78rem; font-weight: 400; color: var(--db-ink-faint); }

/* Status: same tones as the worklists */
.dashboard-container .today-status {
    justify-self: start;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.2rem 0.55rem;
    font-size: 0.72rem;
    font-weight: 600;
    white-space: nowrap;
    border-radius: 4px;
}

.dashboard-container .today-status::before { content: ""; width: 6px; height: 6px; border-radius: 50%; background: currentColor; opacity: 0.85; }
.dashboard-container .today-status--pending   { color: var(--pm-dk-fg-amber, #93370D); background: var(--pm-dk-bg-amber, #FEF6E7); }
.dashboard-container .today-status--approved  { color: var(--pm-dk-fg-teal, #10404B); background: var(--pm-dk-bg-teal, #EBF2F4); }
.dashboard-container .today-status--completed { color: var(--pm-dk-fg-green, #067647); background: var(--pm-dk-bg-green, #ECFDF3); }
.dashboard-container .today-status--late      { color: var(--pm-dk-fg-red, #B42318); background: var(--pm-dk-bg-red, #FEF3F2); }
.dashboard-container .today-status--cancelled,
.dashboard-container .today-status--no_show,
.dashboard-container .today-status--unknown   { color: var(--pm-text-soft); background: var(--pm-subtle); }

/* Actions */
.dashboard-container .today-actions { display: flex; justify-content: flex-end; gap: 0.4rem; }

.dashboard-container .today-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    height: 32px;
    padding: 0 0.8rem;
    font-size: 0.78rem;
    font-weight: 600;
    color: var(--db-ink);
    background: var(--pm-surface);
    border: 1px solid var(--db-line);
    border-radius: 6px;
    text-decoration: none;
    white-space: nowrap;
    transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
}

.dashboard-container .today-btn:hover { color: var(--db-ink); background: var(--db-rail); border-color: var(--pm-dk-edge-blue, #C3CFDB); }
.dashboard-container .today-btn--primary { color: #FFFFFF; background: #175563; border-color: #175563; }
.dashboard-container .today-btn--primary:hover { color: #FFFFFF; background: #10404B; border-color: #10404B; }
.dashboard-container .today-btn--quiet { color: var(--db-ink-mute); background: transparent; border-color: transparent; }
.dashboard-container .today-btn--quiet:hover { color: var(--pm-dk-fg-red, #B42318); background: var(--pm-dk-bg-red, #FEF3F2); border-color: transparent; }

/* Empty */
.dashboard-container .today-empty { padding: 2.75rem 1rem; text-align: center; color: var(--db-ink-mute); }
.dashboard-container .today-empty i { font-size: 1.5rem; color: var(--db-ink-faint); }
.dashboard-container .today-empty p { margin: 0.6rem 0 0.2rem; font-size: 0.9rem; font-weight: 600; color: var(--db-ink); }
.dashboard-container .today-empty small { font-size: 0.78rem; color: var(--db-ink-faint); }

.dashboard-container .today-filter-empty { margin: 0; padding: 1.5rem 1.25rem; font-size: 0.82rem; text-align: center; color: var(--db-ink-faint); }

@media (max-width: 1100px) {
    .dashboard-container .today-row { grid-template-columns: 58px 18px minmax(0, 1fr) 104px 168px; }
    .dashboard-container .today-svc { display: none; }
}

@media (max-width: 720px) {
    .dashboard-container .today-head { padding: 1rem; }
    .dashboard-container .today-filter { overflow-x: auto; max-width: 100%; }
    .dashboard-container .today-row {
        grid-template-columns: 52px 16px minmax(0, 1fr);
        grid-template-areas: "time rail who" "time rail status" "time rail actions";
        row-gap: 0.4rem;
        padding: 0.75rem 1rem;
    }
    .dashboard-container .today-time    { grid-area: time; align-self: start; padding-top: 0.5rem; }
    .dashboard-container .today-rail    { grid-area: rail; }
    .dashboard-container .today-who     { grid-area: who; }
    .dashboard-container .today-status  { grid-area: status; }
    .dashboard-container .today-actions { grid-area: actions; justify-content: flex-start; }
    .dashboard-container .today-row + .today-row::after { left: 1rem; }
    .dashboard-container .today-rail::after { top: 1.25rem; }
}

/* =========================================================
   RESPONSIVE
   ========================================================= */

@media (max-width: 1100px) {
    .dashboard-container .charts-row { grid-template-columns: minmax(0, 1fr); }
    .dashboard-container .donut-canvas { width: 220px; height: 220px; }
}

@media (max-width: 520px) {
    .dashboard-container .donut-wrap { flex-direction: column-reverse; align-items: stretch; min-height: 0; }
    .dashboard-container .donut-canvas { margin: 0 auto; }
}

@media (max-width: 992px) {
    .dashboard-container { padding: 1rem; }
}

@media (max-width: 768px) {
    .dashboard-container { padding: 0.9rem; }


    .dashboard-container .chart-card { padding: 1rem; }
    .dashboard-container .chart-body { height: 240px; }

}

@media (max-width: 576px) {
    .dashboard-container { padding: 0.75rem; }
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
    .dashboard-container .today-actions,
    .dashboard-container .today-filter,
    .dashboard-container .today-all { display: none !important; }
    .dashboard-container .today-card { break-inside: avoid; }
}
</style>

<script>
(function () {
    'use strict';

    // ============================================
    // CHART DATA
    // ============================================
    var serviceLabels = <?= json_encode(array_keys($serviceCounts), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    var serviceValues = <?= json_encode(array_map('intval', array_values($serviceCounts)), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    var donutPalette  = <?= json_encode($donutPalette, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

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

        // ----- Appointment calendar: year switch and day tooltip -----
        (function () {
            var body   = document.getElementById('calBody');
            var tip    = document.getElementById('calTip');
            var select = document.getElementById('calYearSelect');
            if (!body) { return; }

            if (select) {
                select.addEventListener('change', function () {
                    document.querySelectorAll('.cal-year, .cal-facts').forEach(function (el) {
                        el.hidden = el.dataset.year !== select.value;
                    });
                    if (tip) { tip.classList.remove('is-on'); }
                });
            }

            if (!tip) { return; }

            body.addEventListener('mouseover', function (e) {
                var cell = e.target.closest('[data-tip]');
                if (!cell) { return; }
                var parts = cell.dataset.tip.split('|');
                tip.innerHTML = '<b>' + parts[0] + '</b>' + (parts[1] || '');
                var box = body.getBoundingClientRect(), r = cell.getBoundingClientRect();
                var x = r.left - box.left + r.width / 2 - tip.offsetWidth / 2;
                x = Math.max(0, Math.min(x, box.width - tip.offsetWidth));
                var y = r.top - box.top - tip.offsetHeight - 8;
                if (y < 0) { y = r.bottom - box.top + 8; }
                tip.style.transform = 'translate(' + Math.round(x) + 'px,' + Math.round(y) + 'px)';
                tip.classList.add('is-on');
            });

            body.addEventListener('mouseleave', function () { tip.classList.remove('is-on'); });
        })();

        // ----- Service distribution (donut) -----
        var donutCanvas = document.getElementById('servicesChart');

        if (donutCanvas && serviceValues.length) {

            new Chart(donutCanvas.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: serviceLabels,
                    datasets: [{
                        data: serviceValues,
                        backgroundColor: serviceLabels.map(function (_, i) {
                            return donutPalette[i % donutPalette.length];
                        }),
                        /* Thick ring; each segment rounded, with a small gap between */
                        borderWidth: 0,
                        borderRadius: 10,
                        spacing: 6,
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '60%',
                    layout: { padding: 4 },
                    plugins: {
                        legend: { display: false },
                        tooltip: Object.assign({}, TOOLTIP, {
                            callbacks: {
                                label: function (ctx) {
                                    var total = serviceValues.reduce(function (a, b) { return a + b; }, 0);
                                    var pct = total ? Math.round((ctx.parsed / total) * 100) : 0;
                                    return ctx.parsed + (ctx.parsed === 1 ? ' appointment' : ' appointments') + ' (' + pct + '%)';
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
</script>

<script>
(function () {
    'use strict';
    var list = document.getElementById('todayList');
    if (!list) { return; }
    var rows  = Array.prototype.slice.call(list.querySelectorAll('.today-row'));
    var empty = document.getElementById('todayFilterEmpty');

    document.querySelectorAll('.today-filter-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var want = btn.dataset.filter, shown = 0;
            document.querySelectorAll('.today-filter-btn').forEach(function (b) {
                var on = b === btn;
                b.classList.toggle('is-active', on);
                b.setAttribute('aria-selected', on ? 'true' : 'false');
            });
            rows.forEach(function (row) {
                var show = want === 'all' || row.dataset.status === want;
                row.hidden = !show;
                if (show) { shown++; }
            });
            if (empty) { empty.hidden = shown > 0; }
        });
    });
})();
</script>
<?= $this->endSection() ?>