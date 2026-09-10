<?= $this->extend('layouts/ReceptionistLayout') ?>

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
   Prefers figures supplied by the controller. Otherwise it is derived
   from the real appointments already rendered below, so the donut
   never shows invented numbers.
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

/* Palette for the donut segments and the legend dots */
$donutPalette = ['#2450d8', '#f0b429', '#0f9d76', '#7b8794', '#d9534f', '#0e7490'];

/* ------------------------------------------------------------------
   APPOINTMENT TRENDS
   Rendered only when the controller supplies a real series. To turn
   the line chart on, pass:
       $data['weekly_appointments'] = [4, 9, 7, 12, 8, 3, 5];
       $data['weekly_labels']       = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
   ------------------------------------------------------------------ */
$trendValues = (isset($weekly_appointments) && is_array($weekly_appointments)) ? array_values($weekly_appointments) : [];
$trendLabels = (isset($weekly_labels) && is_array($weekly_labels) && count($weekly_labels) === count($trendValues))
    ? array_values($weekly_labels)
    : ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

$hasTrend = count($trendValues) >= 2;

/* Real status breakdown of today's schedule */
$statusCounts = [];
foreach ($appointments as $appt) {
    $st = $slug($appt['status'] ?? '', 'unknown');
    $statusCounts[$st] = ($statusCounts[$st] ?? 0) + 1;
}

/* ------------------------------------------------------------------
   STAT CARDS

   Each card has an 'icon' key. That value is the PNG filename inside
   public/assets/images/. Change any filename below to change which
   image appears on that card. If a file is missing, the card still
   renders — the image just shows a broken-image placeholder.

   The 'tone' key is kept so future changes can still target a card
   by a stable name, but every tone now renders transparent so the
   PNG sits directly on the card with no colored tile behind it.
   ------------------------------------------------------------------ */
$statCards = [
    [
        'key'   => 'patients',
        // TODO: replace with your PNG for "All Patients"
        'icon'  => 'user (1).png',
        'tone'  => 'icon-cyan',
        'label' => 'All Patients',
        'value' => number_format((int) ($total_patients ?? 0)),
        'note'  => 'Total registered patients',
        'link'  => base_url('receptionist/patients'),
    ],
    [
        'key'   => 'today',
        // TODO: replace with your PNG for "Today's Appointments"
        'icon'  => 'document.png',
        'tone'  => 'icon-teal',
        'label' => "Today's Appointments",
        'value' => number_format(count($appointments)),
        'note'  => 'All scheduled today',
        'link'  => base_url('receptionist/appointments'),
    ],
    [
        'key'   => 'pending',
        // TODO: replace with your PNG for "Pending Appointments"
        'icon'  => 'file (1).png',
        'tone'  => 'icon-orange',
        'label' => 'Pending Appointments',
        'value' => number_format((int) ($pending_appointments ?? 0)),
        'note'  => 'Awaiting approval',
        'link'  => base_url('receptionist/appointments'),
    ],
    [
        'key'   => 'completed',
        // TODO: replace with your PNG for "Completed Today"
        'icon'  => 'check-mark.png',
        'tone'  => 'icon-green',
        'label' => 'Completed Today',
        'value' => number_format((int) ($today_completed ?? 0)),
        'note'  => 'Appointments done',
        'link'  => base_url('receptionist/appointments'),
    ],
    [
        'key'   => 'diagnostics',
        // TODO: replace with your PNG for "Pending Diagnostics"
        'icon'  => 'time.png',
        'tone'  => 'icon-blue',
        'label' => 'Pending Diagnostics',
        'value' => number_format((int) ($pending_diagnostic ?? 0)),
        'note'  => 'Tests requested',
        'link'  => null,
    ],
    [
        'key'   => 'unpaid',
        // TODO: replace with your PNG for "Unpaid Bills"
        'icon'  => 'decline.png',
        'tone'  => 'icon-pink',
        'label' => 'Unpaid Bills',
        'value' => number_format((int) ($unpaid_bills ?? 0)),
        'note'  => 'Outstanding balance',
        'link'  => null,
    ],
    [
        'key'   => 'collections',
        // TODO: replace with your PNG for "Today's Collections"
        'icon'  => 'money.png',
        'tone'  => 'icon-yellow',
        'label' => "Today's Collections",
        'value' => '&#8369;' . number_format((float) ($today_collections ?? 0), 2),
        'note'  => 'Payments received',
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
            ?>
            <<?= $tag ?> class="stat-card"<?= $href ?>>

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

                <div class="stat-value<?= !empty($card['raw']) ? ' stat-value-money' : '' ?>">
                    <?= !empty($card['raw']) ? $card['value'] : esc($card['value']) ?>
                </div>

                <div class="stat-label"><?= esc($card['label']) ?></div>

                <div class="stat-sub"><?= esc($card['note']) ?></div>

            </<?= $tag ?>>
        <?php endforeach; ?>
    </div>


    <!-- ===== CHARTS ROW ===== -->
    <div class="charts-row">

        <!-- Appointment Trends (line) -->
        <div class="chart-card">

            <div class="chart-header">
                <div class="chart-heading">
                    <h5>Appointment Trends</h5>
                    <small>Last 7 days</small>
                </div>
                <?php if ($hasTrend): ?>
                    <span class="chart-pill">
                        <span class="pill-dot" style="background: #2450d8;"></span>
                        <?= number_format(array_sum($trendValues)) ?> total
                    </span>
                <?php endif; ?>
            </div>

            <?php if ($hasTrend): ?>
                <div class="chart-body">
                    <canvas id="appointmentsChart"></canvas>
                </div>
            <?php else: ?>
                <div class="chart-body chart-empty">
                    <i class="bi bi-graph-up" aria-hidden="true"></i>
                    <p>Trend data not available</p>
                    <small>Pass $weekly_appointments from the controller to enable this chart</small>
                </div>
            <?php endif; ?>

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

                    <div class="donut-canvas">
                        <canvas id="servicesChart"></canvas>
                        <div class="donut-center">
                            <span class="donut-value"><?= number_format($serviceTotal) ?></span>
                            <span class="donut-label">Appointments</span>
                        </div>
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
                                <span class="legend-pct"><?= number_format($pct, $pct < 10 ? 1 : 0) ?>%</span>
                                <span class="legend-count"><?= number_format($svcCount) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>

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


    <!-- ===== TODAY'S APPOINTMENTS TABLE ===== -->
    <div class="appointments-card">

        <div class="card-header-custom">

            <div class="chart-heading">
                <h5><i class="bi bi-calendar3" aria-hidden="true"></i> Today's Appointments</h5>
                <small>
                    <?php if (count($appointments) > 0): ?>
                        <?= number_format($statusCounts['pending'] ?? 0) ?> pending
                        &middot;
                        <?= number_format($statusCounts['approved'] ?? 0) ?> approved
                        &middot;
                        <?= number_format($statusCounts['completed'] ?? 0) ?> completed
                    <?php else: ?>
                        Nothing scheduled
                    <?php endif; ?>
                </small>
            </div>

            <div class="header-right-group">
                <span class="badge-custom"><?= date('F d, Y') ?></span>
                <a href="<?= base_url('receptionist/appointments') ?>" class="btn-view-all">
                    View All <i class="bi bi-arrow-right" aria-hidden="true"></i>
                </a>
            </div>

        </div>

        <div class="table-responsive">
            <table class="table receptionist-table" id="todayAppointmentsTable">
                <thead>
                    <tr>
                        <th scope="col">Accession No.</th>
                        <th scope="col">Patient</th>
                        <th scope="col">Service</th>
                        <th scope="col">Time</th>
                        <th scope="col">Status</th>
                        <th scope="col" class="col-action">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($appointments) > 0): ?>
                        <?php foreach ($appointments as $appointment): ?>
                            <?php
                                $apptId    = $appointment['id'] ?? 0;
                                $statusKey = $slug($appointment['status'] ?? '', 'unknown');
                                $fullName  = $appointment['full_name'] ?? '';
                                $apptTime  = trim((string) ($appointment['appointment_time'] ?? ''));

                                /* Derive the service label from the actual service
                                   columns, not from service_type. The booking form
                                   writes 'laboratory' into service_type regardless
                                   of what the patient selected, so it cannot be
                                   trusted. lab_services and xray_services are the
                                   real source of truth. */
                                $labList  = json_decode($appointment['lab_services']  ?? '[]', true);
                                $xrayList = json_decode($appointment['xray_services'] ?? '[]', true);

                                $hasLab  = is_array($labList)  && count(array_filter($labList))  > 0;
                                $hasXray = is_array($xrayList) && count(array_filter($xrayList)) > 0;

                                if ($hasLab && $hasXray) {
                                    $svcText = 'Laboratory + X-Ray';
                                } elseif ($hasXray) {
                                    $svcText = 'X-Ray';
                                } elseif ($hasLab) {
                                    $svcText = 'Laboratory';
                                } else {
                                    $svcRaw  = trim((string) ($appointment['service_type'] ?? ''));
                                    $svcText = $svcRaw !== '' ? ucfirst(strtolower($svcRaw)) : 'Unspecified';
                                }
                            ?>
                            <tr>
                                <td data-label="Accession No." class="accession-cell">
                                    APT-<?= esc(date('y')) ?>-<?= esc(str_pad((string) $apptId, 4, '0', STR_PAD_LEFT)) ?>
                                </td>
                                <td data-label="Patient">
                                    <div class="patient-cell">
                                        <span class="patient-avatar" aria-hidden="true"><?= esc($initialsOf($fullName)) ?></span>
                                        <div class="patient-meta">
                                            <span class="patient-name"><?= esc($fullName !== '' ? $fullName : 'Unknown') ?></span>
                                            <small>
                                                <?= esc(($appointment['age'] ?? '') !== '' ? $appointment['age'] . ' yrs' : 'Age n/a') ?>
                                                &middot;
                                                <?= esc(($appointment['gender'] ?? '') !== '' ? $appointment['gender'] : 'n/a') ?>
                                            </small>
                                        </div>
                                    </div>
                                </td>
                                <td data-label="Service">
                                    <span class="service-tag"><?= esc($svcText) ?></span>
                                </td>
                                <td data-label="Time">
                                    <strong><?= esc($apptTime !== '' ? $apptTime : '--') ?></strong>
                                </td>
                                <td data-label="Status">
                                    <span class="status-badge <?= esc($statusKey, 'attr') ?>">
                                        <?= esc(ucfirst($statusKey)) ?>
                                    </span>
                                </td>
                                <td data-label="Action" class="action-cell">

                                    <a href="<?= base_url('receptionist/appointment/view/' . $apptId) ?>"
                                       class="action-icon-btn view" title="View details"
                                       aria-label="View details for <?= esc($fullName, 'attr') ?>">
                                        <i class="bi bi-eye" aria-hidden="true"></i>
                                    </a>

                                    <?php if ($statusKey === 'pending'): ?>
                                        <a href="<?= base_url('receptionist/appointment/approve/' . $apptId) ?>"
                                           class="action-icon-btn approve" title="Approve"
                                           aria-label="Approve appointment for <?= esc($fullName, 'attr') ?>"
                                           onclick="return confirm('Approve the <?= esc($apptTime, 'attr') ?> appointment for <?= esc($fullName, 'attr') ?>?')">
                                            <i class="bi bi-check2" aria-hidden="true"></i>
                                        </a>
                                    <?php endif; ?>

                                    <?php if ($statusKey === 'approved'): ?>
                                        <a href="<?= base_url('receptionist/appointment/complete/' . $apptId) ?>"
                                           class="action-icon-btn complete" title="Complete"
                                           aria-label="Mark complete for <?= esc($fullName, 'attr') ?>"
                                           onclick="return confirm('Mark the <?= esc($apptTime, 'attr') ?> appointment for <?= esc($fullName, 'attr') ?> as completed?')">
                                            <i class="bi bi-check-all" aria-hidden="true"></i>
                                        </a>
                                    <?php endif; ?>

                                    <?php if ($statusKey === 'pending' || $statusKey === 'approved'): ?>
                                        <a href="<?= base_url('receptionist/appointment/cancel/' . $apptId) ?>"
                                           class="action-icon-btn cancel" title="Cancel"
                                           aria-label="Cancel appointment for <?= esc($fullName, 'attr') ?>"
                                           onclick="return confirm('Cancel the <?= esc($apptTime, 'attr') ?> appointment for <?= esc($fullName, 'attr') ?>? This cannot be undone.')">
                                            <i class="bi bi-x" aria-hidden="true"></i>
                                        </a>
                                    <?php endif; ?>

                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6">
                                <div class="empty-state">
                                    <div class="empty-icon">
                                        <i class="bi bi-calendar-x" aria-hidden="true"></i>
                                    </div>
                                    <p>No appointments scheduled for today</p>
                                    <small>Check back later or schedule a new appointment</small>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<style>
/* =========================================================
   DESIGN TOKENS
   All selectors below are scoped to .dashboard-container so
   they beat the bare .stat-card / .stats-row rules that live
   in ReceptionistLayout.php, without using !important and
   without changing those layout rules (other pages still use
   them).
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

/* =========================================================
   STATS ROW
   Scoped so the layout's `.stats-row` rule (repeat auto-fit,
   minmax 200px) does not override. Fixed 7-track grid matches
   the admin dashboard card width.
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

/* Grid, not flex, so the icon stays pinned to the left edge and the
   chevron to the right edge regardless of any parent that might
   constrain the card's width. */
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

/* The PNG icon. Sized to sit comfortably inside the 40x40 tile.
   The tile itself is now transparent — see the .icon-* rules
   below — so the PNG sits directly on the card. */
.dashboard-container .stat-img {
    width: 30px;
    height: 30px;
    object-fit: contain;
    display: block;
}

.dashboard-container .stat-arrow {
    color: #CBD5E1;
    flex-shrink: 0;
    transition: color 0.18s ease;
    justify-self: end;
}

/* Tone hooks retained on the icon wrapper for future use, but with
   transparent backgrounds so no colored tile is drawn. The wrapper
   stays 40x40 so all seven cards keep the same row alignment. */
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
   DONUT + LEGEND
   ========================================================= */

.dashboard-container .donut-wrap {
    display: flex;
    flex-direction: column;
    gap: 1.1rem;
}

.dashboard-container .donut-canvas {
    position: relative;
    height: 190px;
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
    font-size: 1.9rem;
    font-weight: 800;
    letter-spacing: -0.04em;
    line-height: 1;
    color: var(--db-ink);
    font-variant-numeric: tabular-nums;
}

.dashboard-container .donut-label {
    font-size: 0.7rem;
    font-weight: 500;
    color: var(--db-ink-mute);
    margin-top: 0.2rem;
}

.dashboard-container .donut-legend {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 0.1rem;
}

.dashboard-container .legend-item {
    display: flex;
    align-items: center;
    gap: 0.55rem;
    padding: 0.4rem 0.5rem;
    border-radius: var(--db-r-sm);
    font-size: 0.8rem;
    transition: background-color 0.16s ease;
}

.dashboard-container .legend-item:hover { background: var(--db-rail); }

.dashboard-container .legend-dot {
    width: 8px;
    height: 8px;
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
}

.dashboard-container .legend-count {
    font-size: 0.735rem;
    color: var(--db-ink-faint);
    font-variant-numeric: tabular-nums;
    min-width: 1.5rem;
    text-align: right;
}

/* =========================================================
   APPOINTMENTS CARD
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

/* =========================================================
   TABLE
   ========================================================= */

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

/* ----- patient cell ----- */
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

/* ----- service + status ----- */
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
.dashboard-container .status-badge.approved  { background: var(--db-accent-soft); color: var(--db-accent); border-color: #d3e0fb; }
.dashboard-container .status-badge.completed { background: #eefaf4; color: var(--db-green); border-color: #c6ebda; }
.dashboard-container .status-badge.cancelled { background: #fdeef0; color: var(--db-red); border-color: #f4d2d5; }
.dashboard-container .status-badge.late      { background: #fdf1ec; color: #c2410c; border-color: #f6d9c9; }
.dashboard-container .status-badge.unknown   { background: var(--db-rail); color: var(--db-ink-mute); border-color: var(--db-line); }

/* ----- actions ----- */
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
.dashboard-container .action-icon-btn.approve:hover  { background: var(--db-accent-soft); color: var(--db-accent); border-color: #d3e0fb; }
.dashboard-container .action-icon-btn.complete:hover { background: #eefaf4; color: var(--db-green); border-color: #c6ebda; }
.dashboard-container .action-icon-btn.cancel:hover   { background: #fdeef0; color: var(--db-red); border-color: #f4d2d5; }

/* =========================================================
   EMPTY STATE
   ========================================================= */

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
    .dashboard-container .charts-row { grid-template-columns: minmax(0, 1fr); }
    .dashboard-container .donut-canvas { height: 220px; }
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
}

@media (max-width: 576px) {
    .dashboard-container { padding: 0.75rem; }
    .dashboard-container .stats-row { grid-template-columns: minmax(0, 1fr); gap: 12px; }
    .dashboard-container .card-header-custom { flex-direction: column; align-items: stretch; }
    .dashboard-container .header-right-group { justify-content: space-between; }
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
    .dashboard-container .btn-view-all { display: none !important; }
    .dashboard-container .stat-card,
    .dashboard-container .appointments-card { box-shadow: none; break-inside: avoid; }
}
</style>

<script>
(function () {
    'use strict';

    // ============================================
    // CHART DATA
    // Service distribution comes from the real appointments
    // rendered above. The trend series is only present when the
    // controller supplied one, so nothing here is invented.
    // ============================================
    var trendLabels   = <?= json_encode($trendLabels, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    var trendValues   = <?= json_encode($trendValues, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
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

        // ----- Appointment trends (line) -----
        var lineCanvas = document.getElementById('appointmentsChart');

        if (lineCanvas && trendValues.length) {

            new Chart(lineCanvas.getContext('2d'), {
                type: 'line',
                data: {
                    labels: trendLabels,
                    datasets: [{
                        label: 'Appointments',
                        data: trendValues,
                        borderColor: '#2450d8',
                        backgroundColor: 'rgba(36, 80, 216, 0.07)',
                        fill: true,
                        tension: 0.38,
                        borderWidth: 2,
                        pointRadius: 3,
                        pointBackgroundColor: '#2450d8',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointHoverRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { display: false },
                        tooltip: Object.assign({}, TOOLTIP, {
                            callbacks: {
                                label: function (ctx) {
                                    var n = ctx.parsed.y;
                                    return n + (n === 1 ? ' appointment' : ' appointments');
                                }
                            }
                        })
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            border: { display: false },
                            grid: { color: '#eef1f6', drawBorder: false, drawTicks: false },
                            ticks: { color: '#9aa5b5', font: { size: 11 }, padding: 8, precision: 0, maxTicksLimit: 6 }
                        },
                        x: {
                            border: { display: false },
                            grid: { display: false, drawBorder: false },
                            ticks: { color: '#9aa5b5', font: { size: 11 }, padding: 6 }
                        }
                    }
                }
            });
        }

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

<?= $this->endSection() ?>