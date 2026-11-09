<?= $this->extend($layout) ?>
<?= $this->section('pageTitle') ?>Billing Reports<?= $this->endSection() ?>
<?= $this->section($section) ?>

<?php
/**
 * @var array  $daily           [ ['d' => 'YYYY-MM-DD', 'n' => int, 'collected' => float], ... ]
 * @var array  $serviceRevenue  [ 'Service name' => ['count' => int, 'revenue' => float], ... ]
 * @var array  $aging           [ '0-30' => float, '31-60' => float, '61+' => float ]
 * @var float  $outstanding
 * @var string $from
 * @var string $to
 * @var string $routeBase
 */

/* ---- Derived figures, all from the data we already have ---- */
$dailyCollected   = array_sum(array_column($daily, 'collected'));
$dailyPayments    = array_sum(array_column($daily, 'n'));
$activeDays       = count(array_filter($daily, fn($d) => (int) ($d['n'] ?? 0) > 0));
$avgPerActiveDay  = $activeDays > 0 ? $dailyCollected / $activeDays : 0;

$serviceTotal     = 0.0;
$serviceCount     = 0;
foreach ($serviceRevenue as $row) {
    $serviceTotal += (float) ($row['revenue'] ?? 0);
    $serviceCount += (int)   ($row['count']   ?? 0);
}
$avgTicket = $dailyPayments > 0 ? $dailyCollected / $dailyPayments : 0;

$bestDay = null;
$bestDayValue = 0.0;
foreach ($daily as $d) {
    if ((float) $d['collected'] > $bestDayValue) {
        $bestDayValue = (float) $d['collected'];
        $bestDay      = $d['d'];
    }
}

$trendPct   = null;
$trendLabel = 'vs first half of range';
if (count($daily) >= 4) {
    $half   = (int) floor(count($daily) / 2);
    $first  = array_sum(array_column(array_slice($daily, 0, $half), 'collected'));
    $second = array_sum(array_column(array_slice($daily, $half), 'collected'));
    if ($first > 0) {
        $trendPct = (($second - $first) / $first) * 100;
    }
}

$rankedServices = $serviceRevenue;
uasort($rankedServices, fn($a, $b) => ((float) ($b['revenue'] ?? 0)) <=> ((float) ($a['revenue'] ?? 0)));

$topService    = null;
$topServiceRev = 0.0;
if ($rankedServices) {
    $topService    = array_key_first($rankedServices);
    $topServiceRev = (float) ($rankedServices[$topService]['revenue'] ?? 0);
}
$maxServiceRev = $topServiceRev;

$topThreeRev = 0.0;
$seen = 0;
foreach ($rankedServices as $row) {
    if ($seen++ >= 3) { break; }
    $topThreeRev += (float) ($row['revenue'] ?? 0);
}
$topThreeShare = $serviceTotal > 0 ? ($topThreeRev / $serviceTotal) * 100 : 0;

$agingTotal = array_sum(array_map('floatval', $aging));
$overduePct = $agingTotal > 0 ? (((float) ($aging['61+'] ?? 0)) / $agingTotal) * 100 : 0;

/* Server-side sparkline so the headline figure still reads
   correctly even if Chart.js never loads */
$spark = '';
if (count($daily) > 1) {
    $sw = 240; $sh = 44;
    $svals = array_map(fn($d) => (float) $d['collected'], $daily);
    $smax  = max($svals) ?: 1;
    $sstep = $sw / (count($svals) - 1);
    $spts  = [];
    foreach ($svals as $k => $v) {
        $spts[] = round($k * $sstep, 1) . ',' . round($sh - ($v / $smax) * ($sh - 6) - 3, 1);
    }
    $spark = implode(' ', $spts);
}

$chartLabels  = array_map(fn($d) => date('M j', strtotime($d['d'])), $daily);
$chartValues  = array_map(fn($d) => (float) $d['collected'], $daily);
$chartCounts  = array_map(fn($d) => (int) $d['n'], $daily);


$rangeLabel = ($from && $to)
    ? date('M j, Y', strtotime($from)) . ' – ' . date('M j, Y', strtotime($to))
    : 'All time';

$csvRows = [
    ['PolyMedic billing report'],
    ['Range', $from ?: 'all', 'to', $to ?: 'all'],
    ['Generated', date('Y-m-d H:i:s')],
    [],
    ['Date', 'Payments', 'Collected'],
];
foreach ($daily as $d) {
    $csvRows[] = [date('Y-m-d', strtotime($d['d'])), (int) $d['n'], number_format((float) $d['collected'], 2, '.', '')];
}
$csvRows[] = ['Total', $dailyPayments, number_format($dailyCollected, 2, '.', '')];
$csvRows[] = [];
$csvRows[] = ['Service', 'Times billed', 'Revenue'];
foreach ($rankedServices as $name => $row) {
    $csvRows[] = [$name, (int) $row['count'], number_format((float) $row['revenue'], 2, '.', '')];
}
$csvRows[] = [];
$csvRows[] = ['Receivables aging'];
foreach (['0-30', '31-60', '61+'] as $bucket) {
    $csvRows[] = [$bucket . ' days', number_format((float) ($aging[$bucket] ?? 0), 2, '.', '')];
}
$csvRows[] = ['Total outstanding', number_format((float) $outstanding, 2, '.', '')];
?>

<div class="bl rep">

    <div class="rep-print-head" aria-hidden="true">
        <div>
            <strong>PolyMedic Diagnostic Center</strong>
            <span>Billing report · <?= esc($rangeLabel) ?></span>
        </div>
        <span>Generated <?= esc(date('M j, Y · g:i A')) ?></span>
    </div>

    <!-- ─── HEADER ────────────────────────────────────────────── -->
    <header class="bl-head">
        <div>
            <h2 class="bl-title">Billing reports</h2>
            <p class="bl-lede">
                Collections, revenue by service, and outstanding balances.
                <span class="bl-dot">·</span>
                <span class="rep-range"><?= esc($rangeLabel) ?></span>
            </p>
        </div>
        <div class="bl-head-actions">
            <button type="button" class="bl-btn" onclick="window.print()">
                <i class="bi bi-printer"></i>
                <span>Print</span>
            </button>
            <button type="button" class="bl-btn" id="repExport">
                <i class="bi bi-download"></i>
                <span>Export CSV</span>
            </button>
            <?php if (!empty($isAdminUser)): ?>
                <a class="bl-btn bl-btn--primary" target="_blank" rel="noopener"
                   href="<?= base_url('admin/billing/reports/revenue') . '?' . http_build_query(['range' => 'custom', 'from' => $from, 'to' => $to]) ?>">
                    <i class="bi bi-file-earmark-text"></i>
                    <span>Revenue report</span>
                </a>
            <?php endif; ?>
            <a class="bl-btn" href="<?= base_url($routeBase) ?>">
                <i class="bi bi-arrow-left"></i>
                <span>Back to billing</span>
            </a>
        </div>
    </header>

    <!-- ─── FILTERS ───────────────────────────────────────────── -->
    <form class="bl-filters rep-filters" method="get" action="<?= base_url($routeBase . '/reports') ?>">
        <div class="bl-date-range">
            <i class="bi bi-calendar-event bl-date-icon"></i>
            <label class="visually-hidden" for="rep_from">From</label>
            <input type="date" class="bl-input bl-input--date" id="rep_from" name="from"
                   value="<?= esc($from, 'attr') ?>">
            <span class="bl-to">→</span>
            <label class="visually-hidden" for="rep_to">To</label>
            <input type="date" class="bl-input bl-input--date" id="rep_to" name="to"
                   value="<?= esc($to, 'attr') ?>">
        </div>

        <div class="rep-quick" role="group" aria-label="Quick ranges">
            <button type="button" class="rep-quick-btn" data-range="7">7d</button>
            <button type="button" class="rep-quick-btn" data-range="30">30d</button>
            <button type="button" class="rep-quick-btn" data-range="90">90d</button>
            <button type="button" class="rep-quick-btn" data-range="month">This month</button>
        </div>

        <div class="rep-filters-actions">
            <button type="submit" class="bl-btn bl-btn--primary"><i class="bi bi-funnel"></i> Apply</button>
            <a class="bl-btn" href="<?= base_url($routeBase . '/reports') ?>">
                <i class="bi bi-arrow-counterclockwise"></i> Reset
            </a>
        </div>
    </form>

    <!-- ─── FIGURES — one strip, the money collected leads ───── -->
    <dl class="rep-kpis">
        <div class="rep-kpi rep-kpi--lead">
            <dt>Collected</dt>
            <dd class="rep-kpi-value">₱<?= number_format($dailyCollected, 2) ?></dd>
            <dd class="rep-kpi-note">
                <?php if ($trendPct !== null): ?>
                    <span class="rep-delta rep-delta--<?= $trendPct >= 0 ? 'up' : 'down' ?>"><?= $trendPct >= 0 ? '+' : '−' ?><?= number_format(abs($trendPct), 1) ?>%</span>
                    <?= esc($trendLabel) ?>
                <?php else: ?>
                    <?= esc($rangeLabel) ?>
                <?php endif; ?>
            </dd>
        </div>
        <div class="rep-kpi">
            <dt>Payments</dt>
            <dd class="rep-kpi-value"><?= number_format($dailyPayments) ?></dd>
            <dd class="rep-kpi-note"><?= $activeDays > 0 ? 'on ' . number_format($activeDays) . ' day' . ($activeDays === 1 ? '' : 's') : 'None in range' ?></dd>
        </div>
        <div class="rep-kpi">
            <dt>Average payment</dt>
            <dd class="rep-kpi-value"><?= $dailyPayments > 0 ? '₱' . number_format($avgTicket, 2) : '<span class="rep-none">&mdash;</span>' ?></dd>
            <dd class="rep-kpi-note"><?= $activeDays > 0 ? '₱' . number_format($avgPerActiveDay, 2) . ' per active day' : 'No payments yet' ?></dd>
        </div>
        <div class="rep-kpi">
            <dt>Outstanding</dt>
            <dd class="rep-kpi-value<?= (float) ($aging['61+'] ?? 0) > 0 ? ' is-bad' : '' ?>">₱<?= number_format($outstanding, 2) ?></dd>
            <dd class="rep-kpi-note"><?= $overduePct > 0 ? number_format($overduePct, 0) . '% is over 60 days old' : ($outstanding > 0 ? 'All within 60 days' : 'Every invoice settled') ?></dd>
        </div>
        <div class="rep-kpi">
            <dt>Best day</dt>
            <dd class="rep-kpi-value"><?= $bestDay ? '₱' . number_format($bestDayValue, 2) : '<span class="rep-none">&mdash;</span>' ?></dd>
            <dd class="rep-kpi-note"><?= $bestDay ? esc(date('D, M j', strtotime($bestDay))) : 'No activity' ?></dd>
        </div>
    </dl>

    <?php
        /* One plain summary line, built from the same figures */
        $summary = [];
        if ($bestDay && $dailyCollected > 0) {
            $summary[] = esc(date('l, M j', strtotime($bestDay))) . ' was the strongest day at <b>₱' . number_format($bestDayValue, 2) . '</b>, '
                       . number_format(($bestDayValue / $dailyCollected) * 100, 0) . '% of the range.';
        }
        if ($topService && $topThreeShare > 0) {
            $summary[] = 'The top three services bring in <b>' . number_format($topThreeShare, 0) . '%</b> of revenue, led by <b>' . esc($topService) . '</b>.';
        }
        $summaryWarn = (float) ($aging['61+'] ?? 0) > 0
            ? '<b>₱' . number_format((float) $aging['61+'], 2) . '</b> has been unpaid for more than 60 days. Follow these up first.'
            : '';
    ?>
    <?php if ($summary || $summaryWarn): ?>
        <p class="rep-summary">
            <?= implode(' ', $summary) ?>
            <?php if ($summaryWarn): ?><span class="rep-summary-warn"><?= $summaryWarn ?></span><?php endif; ?>
        </p>
    <?php endif; ?>

    <!-- ─── MAIN GRID — collections (wide) + aging rail ───────── -->
    <div class="rep-grid">

        <!-- ============ COLLECTIONS ============ -->
        <section class="bl-panel rep-panel">
            <header class="rep-panel-head">
                <div>
                    <h3 class="rep-panel-title">Daily collections</h3>
                    <p class="rep-panel-sub">
                        <?= $dailyCollected > 0
                            ? '₱' . number_format($dailyCollected, 2) . ' across ' . $activeDays . ' active day' . ($activeDays === 1 ? '' : 's')
                            : 'No collections in this range' ?>
                    </p>
                </div>

                <?php if (!empty($daily)): ?>
                    <div class="rep-toggle" role="tablist" aria-label="View">
                        <button type="button" id="viewChart" role="tab" aria-selected="true" aria-controls="panelChart">Chart
                        </button>
                        <button type="button" id="viewTable" role="tab" aria-selected="false" aria-controls="panelTable">Table
                        </button>
                    </div>
                <?php endif; ?>
            </header>

            <?php if (empty($daily)): ?>
                <div class="rep-panel-body">
                    <div class="rep-empty">
                        <i class="bi bi-bar-chart"></i>
                        <p>No collections in the selected range.</p>
                        <span>Widen the dates, or check that payments were recorded against invoices.</span>
                    </div>
                </div>
            <?php else: ?>

                <div class="rep-panel-body" id="panelChart" role="tabpanel">
                    <div class="rep-chart rep-chart--line">
                        <canvas id="repDailyChart" aria-label="Daily collections chart"></canvas>
                        <div class="rep-chart-fallback" id="dailyFallback" hidden>
                            <i class="bi bi-wifi-off"></i>
                            <p>Charts need an internet connection. The figures are in the Table view.</p>
                        </div>
                    </div>
                </div>

                <div class="rep-panel-body rep-panel-body--flush" id="panelTable" role="tabpanel" hidden>
                    <table class="bl-lines rep-lines">
                        <caption class="visually-hidden">Daily collections in the selected range</caption>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th class="bl-num">Payments</th>
                                <th class="bl-num">Collected</th>
                                <th class="bl-num rep-hide-sm">Cumulative</th>
                                <th class="rep-hide-sm">Share</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $running = 0.0;
                            foreach ($daily as $d):
                                $col      = (float) $d['collected'];
                                $running += $col;
                                $share    = $dailyCollected > 0 ? ($col / $dailyCollected) * 100 : 0;
                                $isBest   = $bestDay !== null && $d['d'] === $bestDay;
                            ?>
                                <tr<?= $isBest ? ' class="is-best"' : '' ?>>
                                    <td>
                                        <span class="rep-date-main"><?= esc(date('M j, Y', strtotime($d['d']))) ?></span>
                                        <span class="rep-date-sub">
                                            <?= esc(date('D', strtotime($d['d']))) ?>
                                            <?php if ($isBest): ?><span class="rep-best-tag">Best</span><?php endif; ?>
                                        </span>
                                    </td>
                                    <td class="bl-num"><?= number_format((int) $d['n']) ?></td>
                                    <td class="bl-num bl-strong">₱<?= number_format($col, 2) ?></td>
                                    <td class="bl-num rep-hide-sm rep-dim">₱<?= number_format($running, 2) ?></td>
                                    <td class="rep-hide-sm">
                                        <div class="rep-share">
                                            <div class="rep-share-bar" role="progressbar"
                                                 aria-label="<?= round($share, 1) ?>% of range"
                                                 aria-valuenow="<?= round($share, 1) ?>"
                                                 aria-valuemin="0" aria-valuemax="100">
                                                <span style="width: <?= round($share, 2) ?>%"></span>
                                            </div>
                                            <span class="rep-share-pct"><?= round($share, 1) ?>%</span>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td>Total</td>
                                <td class="bl-num"><?= number_format($dailyPayments) ?></td>
                                <td class="bl-num">₱<?= number_format($dailyCollected, 2) ?></td>
                                <td class="bl-num rep-hide-sm">₱<?= number_format($dailyCollected, 2) ?></td>
                                <td class="rep-hide-sm"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <!-- ============ AGING RAIL ============ -->
        <section class="bl-panel rep-panel">
            <header class="rep-panel-head">
                <div>
                    <h3 class="rep-panel-title">Receivables aging</h3>
                    <p class="rep-panel-sub">₱<?= number_format($outstanding, 2) ?> outstanding</p>
                </div>
            </header>

            <div class="rep-panel-body">
                <?php if ($agingTotal <= 0): ?>
                    <div class="rep-empty">
                        <i class="bi bi-check2-circle"></i>
                        <p>Nothing outstanding.</p>
                        <span>Every issued invoice has been settled.</span>
                    </div>
                <?php else: ?>

                    <?php
                    $agingBuckets = [
                        ['key' => '0-30',  'label' => '0–30 days',  'tone' => 'green',  'note' => 'Current'],
                        ['key' => '31-60', 'label' => '31–60 days', 'tone' => 'blue',   'note' => 'Watch'],
                        ['key' => '61+',   'label' => '61+ days',   'tone' => 'danger', 'note' => 'At risk'],
                    ];
                    ?>

                    <div class="rep-stack" role="img" aria-label="Outstanding balance split by age">
                        <?php foreach ($agingBuckets as $b):
                            $value = (float) ($aging[$b['key']] ?? 0);
                            if ($value <= 0) { continue; }
                        ?>
                            <span class="rep-stack-seg rep-stack-seg--<?= esc($b['tone'], 'attr') ?>"
                                  style="width: <?= round(($value / $agingTotal) * 100, 2) ?>%"
                                  title="<?= esc($b['label']) ?>: ₱<?= number_format($value, 2) ?>"></span>
                        <?php endforeach; ?>
                    </div>

                    <ul class="rep-aging-list">
                        <?php foreach ($agingBuckets as $b):
                            $value = (float) ($aging[$b['key']] ?? 0);
                            $pct   = ($value / $agingTotal) * 100;
                        ?>
                            <li class="rep-aging-row rep-aging-row--<?= esc($b['tone'], 'attr') ?>">
                                <span class="rep-aging-dot"></span>
                                <div class="rep-aging-text">
                                    <span class="rep-aging-label"><?= esc($b['label']) ?></span>
                                    <span class="rep-aging-note"><?= esc($b['note']) ?></span>
                                </div>
                                <div class="rep-aging-figures">
                                    <span class="rep-aging-value">₱<?= number_format($value, 2) ?></span>
                                    <span class="rep-aging-pct"><?= number_format($pct, 1) ?>%</span>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                    <?php if ((float) ($aging['61+'] ?? 0) > 0): ?>
                        <p class="rep-aging-flag">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                            ₱<?= number_format((float) $aging['61+'], 2) ?> is more than 60 days old.
                        </p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <!-- ─── REVENUE BY SERVICE — a compact ranked list, filterable by department ── -->
    <?php
        /* Per-department totals for the filter and the subtitle */
        $svcTypeName = ['lab' => 'Laboratory', 'xray' => 'X-ray', 'other' => 'Other'];
        $svcTypes    = ['all' => ['n' => 0, 'rev' => 0.0, 'billed' => 0], 'lab' => ['n' => 0, 'rev' => 0.0, 'billed' => 0], 'xray' => ['n' => 0, 'rev' => 0.0, 'billed' => 0]];
        foreach ($rankedServices as $row) {
            $t = $row['type'] ?? 'other';
            foreach (array_unique(['all', $t]) as $k) {
                if (!isset($svcTypes[$k])) { continue; }
                $svcTypes[$k]['n']++;
                $svcTypes[$k]['rev']    += (float) ($row['revenue'] ?? 0);
                $svcTypes[$k]['billed'] += (int) ($row['count'] ?? 0);
            }
        }
        $svcShown = 6;
    ?>
    <section class="bl-panel rep-panel rep-svc">
        <header class="rep-panel-head">
            <div>
                <h3 class="rep-panel-title">Revenue by service</h3>
                <p class="rep-panel-sub" id="repSvcSub">
                    <?= number_format($svcTypes['all']['n']) ?> service<?= $svcTypes['all']['n'] === 1 ? '' : 's' ?>
                    <span class="bl-dot">·</span> ₱<?= number_format($svcTypes['all']['rev'], 2) ?> from <?= number_format($svcTypes['all']['billed']) ?> billing<?= $svcTypes['all']['billed'] === 1 ? '' : 's' ?>
                    <?php if ($topThreeShare > 0): ?>
                        <span class="bl-dot">·</span> top 3 bring in <?= number_format($topThreeShare, 0) ?>%
                    <?php endif; ?>
                </p>
            </div>

            <?php if (!empty($rankedServices)): ?>
                <div class="rep-svc-filter" role="tablist" aria-label="Filter by department">
                    <?php foreach (['all' => 'All', 'lab' => 'Laboratory', 'xray' => 'X-ray'] as $key => $label):
                        $t = $svcTypes[$key]; ?>
                        <button type="button" role="tab" class="rep-svc-filter-btn<?= $key === 'all' ? ' is-active' : '' ?>"
                                aria-selected="<?= $key === 'all' ? 'true' : 'false' ?>" data-type="<?= $key ?>"
                                data-n="<?= $t['n'] ?>" data-billed="<?= $t['billed'] ?>"
                                data-rev="<?= esc('₱' . number_format($t['rev'], 2), 'attr') ?>"
                                data-share="<?= $svcTypes['all']['rev'] > 0 ? number_format($t['rev'] / $svcTypes['all']['rev'] * 100, 0) : 0 ?>"
                                <?= $t['n'] === 0 && $key !== 'all' ? 'disabled' : '' ?>>
                            <?php if ($key !== 'all'): ?><i class="rep-svc-dot rep-svc-dot--<?= $key ?>" aria-hidden="true"></i><?php endif; ?>
                            <?= esc($label) ?> <span><?= number_format($t['n']) ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </header>

        <?php if (empty($rankedServices)): ?>
            <div class="rep-panel-body">
                <div class="rep-empty">
                    <i class="bi bi-tags"></i>
                    <p>No service revenue in the selected range.</p>
                    <span>Revenue appears here once invoices with line items are settled.</span>
                </div>
            </div>
        <?php else: ?>
            <ol class="rep-svc-list" id="repSvcList" data-shown="<?= $svcShown ?>">
                <?php $rank = 0; foreach ($rankedServices as $name => $row):
                    $rank++;
                    $rev   = (float) ($row['revenue'] ?? 0);
                    $cnt   = (int)   ($row['count']   ?? 0);
                    $type  = $row['type'] ?? 'other';
                    $share = $serviceTotal  > 0 ? ($rev / $serviceTotal) * 100 : 0;
                    $barW  = $maxServiceRev > 0 ? ($rev / $maxServiceRev) * 100 : 0;
                ?>
                    <li class="rep-svc-row rep-svc-row--<?= esc($type, 'attr') ?><?= $rank <= 3 ? ' is-top' : '' ?>" data-type="<?= esc($type, 'attr') ?>"
                        <?= $rank > $svcShown ? 'hidden' : '' ?>
                        title="<?= esc($name . ' · ' . ($svcTypeName[$type] ?? 'Other') . ' · ₱' . number_format($rev, 2) . ' · ' . $cnt . ' billed · avg ₱' . number_format($cnt > 0 ? $rev / $cnt : 0, 2), 'attr') ?>">
                        <span class="rep-svc-rank"><?= $rank ?></span>
                        <span class="rep-svc-name">
                            <span class="rep-svc-name-text"><?= esc($name) ?></span>
                            <span class="rep-svc-type rep-svc-type--<?= esc($type, 'attr') ?>"><?= $type === 'xray' ? 'X-ray' : ($type === 'lab' ? 'Lab' : 'Other') ?></span>
                        </span>
                        <span class="rep-svc-count"><?= number_format($cnt) ?>&times;</span>
                        <span class="rep-svc-rev">₱<?= number_format($rev, 2) ?></span>
                        <span class="rep-svc-pct"><?= number_format($share, 1) ?>%</span>
                        <span class="rep-svc-bar" aria-hidden="true"><span style="width: <?= round($barW, 2) ?>%"></span></span>
                    </li>
                <?php endforeach; ?>
            </ol>

            <button type="button" class="rep-svc-more" id="repSvcMore" aria-expanded="false" aria-controls="repSvcList"
                    <?= count($rankedServices) <= $svcShown ? 'hidden' : '' ?>>
                Show all <?= count($rankedServices) ?> services
            </button>
        <?php endif; ?>
    </section>

    <p class="rep-foot">
        Figures cover <?= esc($rangeLabel) ?>. Outstanding balances are current as of
        <?= esc(date('M j, Y · g:i A')) ?> and are not limited to the selected range.
    </p>
</div>

<style>
/* =========================================================
   REPORTS — palette aligned with the admin chrome.

   The .bl tokens are declared here so the page renders on its
   own even if the billing stylesheet is not loaded. Accent
   colours read from the same navy family the layout uses for
   its sidebar and header, so this page no longer looks like
   a different application.
   ========================================================= */

.bl {
    /* Neutrals — unchanged from the billing stylesheet */
    --bl-ink:var(--pm-text); --bl-text:var(--pm-text-soft); --bl-muted:var(--pm-text-muted); --bl-faint:var(--pm-text-muted);
    --bl-line:var(--pm-line); --bl-line-soft:var(--pm-subtle); --bl-surface:var(--pm-surface); --bl-subtle:var(--pm-subtle);

    /* Accent — matched to the layout's --active-blue / --sb-active-* */
    --bl-accent:var(--pm-accent);
    --bl-accent-dark:var(--pm-accent-text);
    --bl-accent-soft:var(--pm-accent-soft);
    --bl-accent-glow:rgba(23, 85, 99,.45);

    --bl-warn:#b45309; --bl-warn-soft:var(--pm-tint-amber);
    --bl-danger:#b91c1c; --bl-danger-soft:var(--pm-tint-red);
    --bl-green:#047857; --bl-green-soft:var(--pm-tint-green);
    --bl-blue:#1d4ed8; --bl-blue-soft:var(--pm-tint-blue);
    --bl-mono:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;
    --bl-radius:8px;
    --bl-shadow:none;
    color:var(--bl-text); font-size:.875rem; line-height:1.55;
}

.bl, .bl *, .bl *::before, .bl *::after { box-sizing:border-box; }
.bl *:focus-visible { outline:2px solid var(--bl-accent); outline-offset:2px; border-radius:4px; }
.bl [hidden] { display:none !important; }

/* ---- primitives this page uses ---- */
.bl-head { display:flex; align-items:flex-end; justify-content:space-between; gap:1rem;
           flex-wrap:wrap; padding-bottom:1rem; margin-bottom:1rem;
           border-bottom:1px solid var(--bl-line); }
.bl-title { margin:0 0 .2rem; font-size:1.25rem; font-weight:650;
            letter-spacing:-.02em; color:var(--bl-ink); }
.bl-lede { margin:0; font-size:.8125rem; color:var(--bl-muted); }
.bl-dot { margin:0 .3rem; color:var(--bl-faint); }
.bl-head-actions { display:flex; gap:.5rem; flex-wrap:wrap; }

.bl-btn { display:inline-flex; align-items:center; justify-content:center; gap:.4rem;
          height:36px; padding:0 .9rem; font-family:inherit; font-size:.8125rem;
          font-weight:600; line-height:1; color:var(--bl-text); background:var(--bl-surface);
          border:1px solid var(--bl-line); border-radius:6px; text-decoration:none;
          white-space:nowrap; cursor:pointer;
          transition:background .15s, border-color .15s, color .15s, box-shadow .15s; }
.bl-btn:hover { background:var(--bl-subtle); border-color:var(--pm-line); color:var(--bl-ink); }
.bl-btn--primary, .bl-btn--primary:hover { color:#fff; }
.bl-btn--primary { background:var(--bl-accent); border-color:var(--bl-accent); }
.bl-btn--primary:hover { background:var(--bl-accent-dark); border-color:var(--bl-accent-dark); }

.bl-input { height:36px; font-family:inherit; font-size:.8125rem; color:var(--bl-ink);
            background:var(--bl-surface); border:1px solid var(--bl-line); border-radius:6px;
            padding:0 .75rem; transition:border-color .15s, box-shadow .15s; }
.bl-input:focus { outline:none; border-color:var(--bl-accent);
                  box-shadow:0 0 0 3px color-mix(in srgb, var(--bl-accent) 18%, transparent); }
.bl-input--date { min-width:148px; }
.bl-date-range { position:relative; display:inline-flex; align-items:center; gap:.35rem; padding-left:1.8rem; }
.bl-date-icon { position:absolute; left:.55rem; font-size:.8rem; color:var(--bl-faint); pointer-events:none; }
.bl-to { font-size:.75rem; color:var(--bl-faint); }

.bl-panel { background:var(--bl-surface); border:1px solid var(--bl-line);
            border-radius:var(--bl-radius); box-shadow:var(--bl-shadow); overflow:hidden; }

.bl-lines { width:100%; border-collapse:collapse; font-size:.8125rem; }
.bl-num { text-align:right; font-variant-numeric:tabular-nums; white-space:nowrap; }
.bl-strong { font-weight:600; color:var(--bl-ink); }

/* =========================================================
   HEADER
   ========================================================= */

.rep-range { font-weight:600; color:var(--bl-ink); }

/* ---- Sticky filters ---- */
.rep-filters {
    position:sticky; top:0; z-index:5;
    display:flex; align-items:center; gap:.6rem; flex-wrap:wrap;
    background:var(--bl-surface);
    border:1px solid var(--bl-line);
    border-radius:var(--bl-radius);
    padding:.65rem .8rem;
    margin-bottom:1.1rem;
}
.rep-quick {
    display:inline-flex; gap:2px; padding:3px;
    background:var(--bl-subtle); border:1px solid var(--bl-line);
    border-radius:8px;
}
.rep-quick-btn {
    border:none; background:transparent; border-radius:6px;
    padding:.28rem .7rem; font-size:.74rem; font-weight:600;
    font-family:inherit; color:var(--bl-muted); cursor:pointer;
    white-space:nowrap; transition:background .15s, color .15s;
}
.rep-quick-btn:hover { color:var(--bl-ink); }
.rep-quick-btn.is-active { background:#142D36; color:#fff; }
.rep-filters-actions { display:inline-flex; gap:.5rem; margin-left:auto; }

/* =========================================================
   FIGURES — one hairline strip, five equal cells
   ========================================================= */

.rep-kpis {
    display:grid; grid-template-columns:repeat(5, minmax(0,1fr));
    margin:0 0 .75rem; background:var(--bl-surface);
    border:1px solid var(--bl-line); border-radius:var(--bl-radius); overflow:hidden;
}
.rep-kpi { min-width:0; padding:1rem 1.15rem 1.05rem; }
.rep-kpi + .rep-kpi { border-left:1px solid var(--bl-line-soft); }
.rep-kpi dt { font-size:.78rem; font-weight:500; color:var(--bl-muted); }
.rep-kpi dd { margin:0; }
.rep-kpi-value {
    margin-top:.3rem !important; font-size:1.35rem; font-weight:600; line-height:1.2;
    letter-spacing:-.015em; color:var(--bl-ink); font-variant-numeric:tabular-nums; white-space:nowrap;
}
.rep-kpi--lead .rep-kpi-value { font-size:1.7rem; }
.rep-kpi-value.is-bad { color:var(--pm-dk-fg-red, #B42318); }
.rep-kpi-note {
    margin-top:.25rem !important; font-size:.74rem; color:var(--bl-muted);
    white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
}
.rep-none { color:var(--bl-faint); font-weight:400; }
.rep-delta { font-weight:600; font-variant-numeric:tabular-nums; }
.rep-delta--up   { color:var(--pm-dk-fg-green, #067647); }
.rep-delta--down { color:var(--pm-dk-fg-red, #B42318); }

.rep-summary { margin:0 0 1rem; padding:0 .15rem; font-size:.82rem; line-height:1.6; color:var(--bl-text); }
.rep-summary b { font-weight:600; color:var(--bl-ink); }
.rep-summary-warn { display:block; color:var(--pm-dk-fg-amber, #93370D); }
.rep-summary-warn b { color:var(--pm-dk-fg-amber, #93370D); }

/* =========================================================
   GRID + PANELS
   ========================================================= */

.rep-grid { display:grid; grid-template-columns:minmax(0,1.65fr) minmax(300px,1fr);
            gap:1rem; align-items:start; margin-bottom:1rem; }

.rep-panel { margin-bottom:1rem; }
.rep-grid .rep-panel { margin-bottom:0; }

.rep-panel-head { display:flex; align-items:center; justify-content:space-between;
                  gap:1rem; flex-wrap:wrap; padding:.95rem 1.2rem;
                  border-bottom:1px solid var(--bl-line-soft); }

.rep-panel-title { margin:0; font-size:.95rem; font-weight:600; letter-spacing:-.005em; color:var(--bl-ink); }
.rep-panel-sub { margin:.2rem 0 0; font-size:.76rem; color:var(--bl-muted); }

.rep-panel-body { padding:1.1rem 1.2rem; }
.rep-panel-body--flush { padding:0; }

/* Chart / Table toggle */
.rep-toggle { display:inline-flex; gap:2px; padding:3px;
              background:var(--bl-subtle); border:1px solid var(--bl-line); border-radius:8px; }

.rep-toggle button { display:inline-flex; align-items:center; gap:.3rem;
                     border:none; background:transparent; border-radius:6px;
                     padding:.3rem .75rem; font-family:inherit; font-size:.74rem;
                     font-weight:650; color:var(--bl-muted); cursor:pointer;
                     transition:background .15s, color .15s; }
.rep-toggle button:hover { color:var(--bl-ink); }
.rep-toggle button[aria-selected="true"] { background:#142D36; color:#fff; }

/* =========================================================
   AGING RAIL
   ========================================================= */

.rep-stack { display:flex; gap:2px; height:10px; border-radius:4px; overflow:hidden;
             background:var(--bl-line-soft); margin-bottom:1rem; }
.rep-stack-seg { display:block; height:100%; }
.rep-stack-seg--green  { background:#17B26A; }
.rep-stack-seg--blue   { background:var(--bl-accent); }
.rep-stack-seg--danger { background:#D92D20; }

.rep-aging-list { list-style:none; margin:0; padding:0; display:flex; flex-direction:column; }

.rep-aging-row { display:flex; align-items:center; gap:.65rem; padding:.6rem 0;
                 border-bottom:1px solid var(--bl-line-soft); }
.rep-aging-row:last-child { border-bottom:0; }

.rep-aging-dot { width:9px; height:9px; border-radius:3px; flex-shrink:0; background:var(--tone,#cbd5e1); }
.rep-aging-row--green  { --tone:#17B26A; }
.rep-aging-row--blue   { --tone:var(--bl-accent); }
.rep-aging-row--danger { --tone:#D92D20; }

.rep-aging-text { display:flex; flex-direction:column; min-width:0; }
.rep-aging-label { font-size:.8125rem; font-weight:600; color:var(--bl-ink); }
.rep-aging-note { font-size:.74rem; color:var(--bl-muted); }

.rep-aging-figures { margin-left:auto; text-align:right; }
.rep-aging-value { display:block; font-size:.9rem; font-weight:700; color:var(--bl-ink);
                   font-variant-numeric:tabular-nums; }
.rep-aging-pct { display:block; font-size:.7rem; color:var(--bl-faint); font-variant-numeric:tabular-nums; }

.rep-aging-flag { display:flex; align-items:flex-start; gap:.45rem;
                  margin:1rem 0 0; padding:.6rem .75rem; border-radius:9px;
                  background:var(--bl-warn-soft); border:1px solid var(--pm-dk-edge-amber, #fed7aa);
                  font-size:.76rem; color:var(--bl-warn); }

.rep-foot { margin:0; padding-top:.25rem; font-size:.72rem;
            color:var(--bl-faint); text-align:center; }

/* ---- Charts ---- */
.rep-chart { position:relative; }
.rep-chart--line { height:268px; }

.rep-chart-fallback {
    position:absolute; inset:0;
    display:flex; flex-direction:column; align-items:center; justify-content:center;
    gap:.3rem; text-align:center;
    background:var(--bl-subtle); border:1px dashed var(--bl-line); border-radius:10px;
}
.rep-chart-fallback i { font-size:1.3rem; color:var(--bl-faint); }
.rep-chart-fallback p { margin:0; font-size:.8125rem; color:var(--bl-muted); max-width:28rem; }

/* ---- Empty states inside panels ---- */
.rep-empty {
    padding:2.25rem 1rem; text-align:center;
    background:var(--bl-subtle); border:1px dashed var(--bl-line);
    border-radius:10px;
}
.rep-empty i { font-size:1.4rem; color:var(--bl-faint); display:block; margin-bottom:.45rem; }
.rep-empty p { margin:0 0 .2rem; font-size:.875rem; font-weight:600; color:var(--bl-ink); }
.rep-empty span { font-size:.78rem; color:var(--bl-muted); }

/* ---- Revenue by service: two columns of ranked rows ---- */
.rep-svc-list {
    columns:2; column-gap:2.25rem; margin:0; padding:.35rem 1.2rem .5rem; list-style:none;
}
.rep-svc-row {
    display:grid; grid-template-columns:1.4rem minmax(0,1fr) auto auto 3.2rem;
    grid-template-rows:auto 3px; align-items:center; column-gap:.6rem; row-gap:.35rem;
    padding:.6rem 0 .55rem; border-bottom:1px solid var(--bl-line-soft);
    break-inside:avoid;
}
.rep-svc-row[hidden] { display:none; }
.rep-svc-rank  { font-size:.75rem; color:var(--bl-faint); font-variant-numeric:tabular-nums; }
.rep-svc-name  { display:flex; align-items:center; gap:.45rem; min-width:0; }
.rep-svc-name-text { font-size:.82rem; font-weight:500; color:var(--bl-ink); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; min-width:0; }
/* Department tag: the same blue / teal the worklists use for Lab and X-ray */
.rep-svc-type  { flex:none; display:inline-flex; align-items:center; gap:.3rem; padding:.05rem .4rem; font-size:.68rem; font-weight:600; border-radius:4px; }
.rep-svc-type::before { content:""; width:6px; height:6px; border-radius:2px; background:currentColor; }
.rep-svc-type--lab   { color:var(--pm-dk-fg-teal, #10404B); background:var(--pm-dk-bg-teal, #EBF2F4); }
.rep-svc-type--xray  { color:var(--pm-dk-fg-teal, #0A6E65); background:var(--pm-dk-bg-teal, #E3F4F1); }
.rep-svc-type--other { color:var(--pm-text-soft); background:var(--pm-subtle); }
.rep-svc-count { font-size:.75rem; color:var(--bl-muted); font-variant-numeric:tabular-nums; }
.rep-svc-rev   { font-size:.82rem; font-weight:600; color:var(--bl-ink); font-variant-numeric:tabular-nums; text-align:right; }
.rep-svc-pct   { font-size:.74rem; color:var(--bl-muted); font-variant-numeric:tabular-nums; text-align:right; }
.rep-svc-row.is-top .rep-svc-rank { color:var(--bl-ink); font-weight:600; }
.rep-svc-bar   { grid-column:2 / -1; height:3px; border-radius:2px; background:var(--bl-line-soft); overflow:hidden; }
.rep-svc-bar > span { display:block; height:100%; border-radius:2px; background:var(--kind, #98A2B3); opacity:.45; }
.rep-svc-row.is-top .rep-svc-bar > span { opacity:1; }
.rep-svc-row--lab  { --kind:#175563; }
.rep-svc-row--xray { --kind:#0E8A7E; }

.rep-svc-filter { display:inline-flex; gap:2px; padding:3px; background:var(--bl-subtle); border:1px solid var(--bl-line); border-radius:8px; }
.rep-svc-filter-btn { display:inline-flex; align-items:center; gap:.4rem; height:28px; padding:0 .7rem; font:inherit; font-size:.75rem; font-weight:600;
    color:var(--bl-muted); background:transparent; border:0; border-radius:6px; cursor:pointer; transition:background .15s, color .15s; }
.rep-svc-filter-btn span { min-width:1.1rem; padding:0 .3rem; font-size:.68rem; line-height:1.1rem; text-align:center; color:var(--bl-muted); background:var(--bl-line-soft); border-radius:4px; font-variant-numeric:tabular-nums; }
.rep-svc-filter-btn:hover { color:var(--bl-ink); }
.rep-svc-filter-btn:disabled { opacity:.45; cursor:not-allowed; }
.rep-svc-filter-btn.is-active { color:#fff; background:#142D36; }
.rep-svc-filter-btn.is-active span { color:#fff; background:rgba(255,255,255,.18); }
.rep-svc-dot { width:7px; height:7px; border-radius:2px; flex:none; }
.rep-svc-dot--lab  { background:#175563; }
.rep-svc-dot--xray { background:#0E8A7E; }

.rep-svc-more {
    display:block; width:calc(100% - 2.4rem); height:32px; margin:.35rem 1.2rem 1rem;
    font:inherit; font-size:.78rem; font-weight:600; color:var(--bl-ink);
    background:var(--bl-surface); border:1px solid var(--bl-line); border-radius:6px; cursor:pointer;
}
.rep-svc-more:hover { background:var(--bl-subtle); }

/* ---- Share bars inside tables ---- */
.rep-share {
    display:grid; grid-template-columns:minmax(60px,1fr) auto;
    align-items:center; gap:.5rem; min-width:130px;
}
.rep-share-bar {
    height:5px; border-radius:99px; overflow:hidden;
    background:var(--bl-line); position:relative;
}
.rep-share-bar > span {
    display:block; height:100%; border-radius:99px;
    background:var(--bl-accent);
    transition:width .4s ease;
}
.rep-share-pct {
    font-size:.72rem; font-weight:600; color:var(--bl-muted);
    font-variant-numeric:tabular-nums; min-width:38px; text-align:right;
}

/* ---- Tables ---- */
.rep-lines th {
    padding:.6rem 1.2rem; text-align:left;
    font-size:.75rem; font-weight:500; color:var(--bl-muted);
    background:var(--bl-surface); border-bottom:1px solid var(--bl-line);
    white-space:nowrap; position:sticky; top:0; z-index:1;
}

.rep-lines th.bl-num { text-align:right; }

.rep-lines td { padding:.7rem 1.2rem; border-bottom:1px solid var(--bl-line-soft); }
.rep-lines tbody tr:last-child td { border-bottom:0; }
.rep-lines tbody tr { transition:background .12s; }
.rep-lines tbody tr:hover { background:var(--pm-subtle); }
.rep-lines tbody tr.is-best { background:var(--pm-dk-bg-blue, #f0f7ff); }

.rep-lines tfoot td {
    padding:.75rem 1.2rem; background:var(--bl-subtle);
    border-top:1px solid var(--bl-line);
    font-weight:700; color:var(--bl-ink); font-variant-numeric:tabular-nums;
}

.rep-dim { color:var(--bl-muted); font-weight:400; }


.rep-date-main { display:block; font-weight:600; color:var(--bl-ink); }
.rep-date-sub  { display:block; font-size:.72rem; color:var(--bl-faint); }

.rep-best-tag {
    display:inline-block; margin-left:.25rem; padding:0 .3rem; border-radius:4px;
    font-size:.68rem; font-weight:600;
    color:var(--bl-accent-dark); background:var(--bl-accent-soft);
}

.bl-empty-cell { padding:1.6rem 1rem; text-align:center; color:var(--bl-muted); font-style:italic; }
.bl-empty-cell i { display:block; font-size:1.3rem; color:var(--bl-faint); margin-bottom:.4rem; }
.bl-empty-cell span { font-size:.8125rem; }

/* ---- Responsive ---- */
@media (max-width:1180px) {
    .rep-kpis { grid-template-columns:repeat(3, minmax(0,1fr)); }
    .rep-kpi:nth-child(4) { border-left:0; }
    .rep-kpi:nth-child(n+4) { border-top:1px solid var(--bl-line-soft); }
    .rep-grid { grid-template-columns:minmax(0,1fr); }
}
@media (max-width:900px) {
    .rep-section-sub { max-width:100%; }
    .rep-chart--line { height:224px; }
}
@media (max-width:640px) {
    .rep-kpis { grid-template-columns:repeat(2, minmax(0,1fr)); }
    .rep-kpi--lead { grid-column:1 / -1; }
    .rep-kpi:nth-child(n) { border-left:0; border-top:1px solid var(--bl-line-soft); }
    .rep-kpi:nth-child(even) { border-left:0; }
    .rep-kpi:nth-child(odd):not(.rep-kpi--lead) { border-left:1px solid var(--bl-line-soft); }
    .rep-kpi--lead { border-top:0 !important; }
    .rep-filters { position:static; }
    .rep-quick { order:3; }
    .rep-filters-actions { margin-left:0; width:100%; }
    .rep-filters-actions .bl-btn { flex:1; justify-content:center; }
    .rep-hide-sm { display:none; }
    .rep-svc-list { columns:1; padding:.25rem .85rem .5rem; }
    .rep-svc-more { width:calc(100% - 1.7rem); margin:.35rem .85rem 1rem; }
    .rep-chart--line { height:200px; }
    .rep-lines th, .rep-lines td { padding-left:.85rem; padding-right:.85rem; }
}
@media (prefers-reduced-motion:reduce) {
    .rep *, .rep *::before, .rep *::after { transition:none !important; }
}

/* =========================================================
   PRINT
   ========================================================= */

.rep-print-head { display:none; }

@media print {
    @page { size:A4 portrait; margin:14mm; }

    .bl-head-actions,
    .rep-filters,
    .rep-quick,
    .rep-toggle { display:none !important; }

    .rep-print-head {
        display:flex; align-items:flex-end; justify-content:space-between; gap:1rem;
        padding-bottom:.5rem; margin-bottom:1rem;
        border-bottom:2px solid #0f172a; font-size:10pt;
    }
    .rep-print-head strong { display:block; font-size:12pt; letter-spacing:.02em; }
    .rep-print-head span { color:var(--pm-text-soft); }

    .bl-panel, .rep-kpis {
        box-shadow:none !important; border-color:var(--pm-line) !important; break-inside:avoid;
    }


    #panelTable[hidden] { display:block !important; }
    .rep-hide-sm { display:table-cell !important; }
    .rep-chart { break-inside:avoid; page-break-inside:avoid; }
    .rep-grid { grid-template-columns:1fr; }
    .rep-lines thead th { position:static; }
    .rep-svc-row[hidden] { display:grid !important; }
    .rep-svc-filter { display:none !important; }
    .rep-svc-more { display:none !important; }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script>
(function () {
    'use strict';

    var DAILY_LABELS  = <?= json_encode($chartLabels, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    var DAILY_VALUES  = <?= json_encode($chartValues, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    var DAILY_COUNTS  = <?= json_encode($chartCounts, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

    var CSV_ROWS      = <?= json_encode($csvRows, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

    var $ = function (id) { return document.getElementById(id); };

    var peso = function (v) {
        return '₱' + (Number(v) || 0).toLocaleString('en-PH', {
            minimumFractionDigits: 2, maximumFractionDigits: 2,
        });
    };

    /* Accent colour, matched to the layout's --active-blue */
    var ACCENT       = '#175563';
    var ACCENT_DARK  = '#10404B';
    var ACCENT_SOFT  = 'rgba(23, 85, 99,.28)';
    var ACCENT_FADE  = 'rgba(23, 85, 99,0)';
    var BAR_MUTED    = 'rgba(148,163,184,.30)';

    var TOOLTIP = {
        backgroundColor: 'rgba(15,23,42,.94)',
        titleColor: '#f8fafc', bodyColor: '#e2e8f0',
        borderColor: 'rgba(255,255,255,.08)', borderWidth: 1,
        padding: 10, cornerRadius: 8, boxPadding: 4, caretSize: 6,
        titleFont: { size: 12, weight: '600' },
        bodyFont:  { size: 12 },
        displayColors: false,
    };

    var hasChart = function () { return typeof Chart !== 'undefined'; };

    function drawDailyChart() {
        var canvas = $('repDailyChart');
        if (!canvas || !DAILY_LABELS.length) { return; }

        if (!hasChart()) {
            canvas.hidden = true;
            if ($('dailyFallback')) { $('dailyFallback').hidden = false; }
            return;
        }

        /* One series: money collected per day; the best day in the darker blue.
           Payment counts ride in the tooltip instead of a second axis. */
        var best = DAILY_VALUES.indexOf(Math.max.apply(null, DAILY_VALUES));

        new Chart(canvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels: DAILY_LABELS,
                datasets: [{
                    label: 'Collected',
                    data: DAILY_VALUES,
                    backgroundColor: DAILY_VALUES.map(function (v, i) { return i === best && v > 0 ? ACCENT_DARK : ACCENT; }),
                    hoverBackgroundColor: ACCENT_DARK,
                    borderRadius: { topLeft: 4, topRight: 4, bottomLeft: 0, bottomRight: 0 },
                    borderSkipped: false,
                    barPercentage: 0.7,
                    categoryPercentage: 0.82,
                    maxBarThickness: 36,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: false },
                    tooltip: Object.assign({}, TOOLTIP, {
                        callbacks: {
                            label: function (c) { return 'Collected: ' + peso(c.parsed.y); },
                            afterLabel: function (c) {
                                var n = DAILY_COUNTS[c.dataIndex] || 0;
                                return n + (n === 1 ? ' payment' : ' payments');
                            },
                        },
                    }),
                },
                scales: {
                    x: {
                        grid: { display: false },
                        border: { display: false },
                        ticks: { color: '#64748b', font: { size: 11 }, maxRotation: 0, autoSkip: true, maxTicksLimit: 10 },
                    },
                    y: {
                        beginAtZero: true,
                        grace: '8%',
                        grid: { color: '#EEF2F6', drawTicks: false },
                        border: { display: false },
                        ticks: {
                            color: '#64748b', font: { size: 11 }, padding: 8, maxTicksLimit: 5,
                            callback: function (v) {
                                if (v >= 1000) return '₱' + (v / 1000).toLocaleString('en-PH', { maximumFractionDigits: 1 }) + 'k';
                                return '₱' + v;
                            },
                        },
                    },
                },
            },
        });
    }
    /* Revenue by service: filter by department, then show the top rows or all of them */
    function setupServiceMore() {
        var list = $('repSvcList');
        if (!list) { return; }
        var rows    = Array.prototype.slice.call(list.querySelectorAll('.rep-svc-row'));
        var more    = $('repSvcMore');
        var sub     = $('repSvcSub');
        var allSub  = sub ? sub.innerHTML : '';
        var shown   = parseInt(list.dataset.shown, 10) || 6;
        var NAMES   = { lab: 'laboratory', xray: 'X-ray' };
        var state   = { type: 'all', open: false };

        function render() {
            var k = 0;
            rows.forEach(function (row) {
                var match = state.type === 'all' || row.dataset.type === state.type;
                if (match) { k++; }
                row.hidden = !match || (!state.open && k > shown);
                row.querySelector('.rep-svc-rank').textContent = match ? k : '';
                row.classList.toggle('is-top', match && k <= 3);
            });
            if (more) {
                var label = state.type === 'all' ? 'services' : NAMES[state.type] + ' services';
                more.hidden = k <= shown;
                more.setAttribute('aria-expanded', state.open ? 'true' : 'false');
                more.textContent = state.open ? 'Show top ' + shown : 'Show all ' + k + ' ' + label;
            }
        }

        document.querySelectorAll('.rep-svc-filter-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                state.type = btn.dataset.type;
                state.open = false;
                document.querySelectorAll('.rep-svc-filter-btn').forEach(function (b) {
                    var on = b === btn;
                    b.classList.toggle('is-active', on);
                    b.setAttribute('aria-selected', on ? 'true' : 'false');
                });
                if (sub) {
                    if (state.type === 'all') {
                        sub.innerHTML = allSub;
                    } else {
                        var n = Number(btn.dataset.n), billed = Number(btn.dataset.billed);
                        sub.textContent = n + ' ' + NAMES[state.type] + ' service' + (n === 1 ? '' : 's')
                            + ' · ' + btn.dataset.rev + ' from ' + billed + ' billing' + (billed === 1 ? '' : 's')
                            + ' · ' + btn.dataset.share + '% of revenue';
                    }
                }
                render();
            });
        });

        if (more) { more.addEventListener('click', function () { state.open = !state.open; render(); }); }
        render();
    }
    function setupToggle() {
        var chartBtn = $('viewChart');
        var tableBtn = $('viewTable');
        if (!chartBtn || !tableBtn) { return; }

        function show(which) {
            var chart = which === 'chart';
            $('panelChart').hidden = !chart;
            $('panelTable').hidden = chart;
            chartBtn.setAttribute('aria-selected', chart ? 'true' : 'false');
            tableBtn.setAttribute('aria-selected', chart ? 'false' : 'true');
        }

        chartBtn.addEventListener('click', function () { show('chart'); });
        tableBtn.addEventListener('click', function () { show('table'); });

        if (!hasChart()) { show('table'); }
    }

    function exportCsv() {
        if (!CSV_ROWS || !CSV_ROWS.length) { return; }

        var lines = CSV_ROWS.map(function (row) {
            return row.map(function (cell) {
                var v = cell == null ? '' : String(cell);
                if (/^[=@\t\r]/.test(v) || /^[+\-](?![\d\s()]+$)/.test(v)) { v = "'" + v; }
                return '"' + v.replace(/"/g, '""') + '"';
            }).join(',');
        }).join('\r\n');

        var blob = new Blob(['\uFEFF' + lines], { type: 'text/csv;charset=utf-8;' });
        var url = URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = 'billing-report-' + new Date().toISOString().slice(0, 10) + '.csv';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        setTimeout(function () { URL.revokeObjectURL(url); }, 1000);
    }

    function setupQuickRanges() {
        var from = $('rep_from');
        var to   = $('rep_to');
        if (!from || !to) { return; }

        var today = new Date();

        function iso(d) {
            var tz = d.getTimezoneOffset() * 60000;
            return new Date(d.getTime() - tz).toISOString().slice(0, 10);
        }

        document.querySelectorAll('.rep-quick-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var range = btn.dataset.range;
                var start = new Date(today);

                if (range === 'month') {
                    start = new Date(today.getFullYear(), today.getMonth(), 1);
                } else {
                    var days = parseInt(range, 10);
                    start.setDate(start.getDate() - (days - 1));
                }

                from.value = iso(start);
                to.value   = iso(today);

                btn.closest('form').submit();
            });
        });
    }

    function ready(fn) {
        if (document.readyState !== 'loading') { fn(); }
        else { document.addEventListener('DOMContentLoaded', fn); }
    }

    ready(function () {
        setupQuickRanges();
        setupToggle();

        var exportBtn = $('repExport');
        if (exportBtn) { exportBtn.addEventListener('click', exportCsv); }

        drawDailyChart();
        setupServiceMore();
    });
})();
</script>

<?= $this->endSection() ?>