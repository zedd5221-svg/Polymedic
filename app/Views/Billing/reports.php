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

$topServices  = array_slice($rankedServices, 0, 8, true);
$svcLabels    = array_keys($topServices);
$svcValues    = array_map(fn($r) => (float) ($r['revenue'] ?? 0), $topServices);

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
            <a class="bl-btn" href="<?= base_url($routeBase) ?>">
                <i class="bi bi-arrow-left"></i>
                <span>Back to billing</span>
            </a>
        </div>
    </header>

    <!-- ─── FILTERS ───────────────────────────────────────────── -->
    <form class="bl-filters rep-filters" method="get" action="<?= base_url($routeBase . '/reports') ?>">
        <div class="rep-filters-label">
            <i class="bi bi-calendar3"></i>
            <span>Range</span>
        </div>

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

    <!-- ─── HERO ──────────────────────────────────────────────── -->
    <section class="rep-hero">

        <div class="rep-hero-main">
            <span class="rep-hero-label">Collected in range</span>
            <div class="rep-hero-value">₱<?= number_format($dailyCollected, 2) ?></div>

            <div class="rep-hero-meta">
                <?= number_format($dailyPayments) ?> payment<?= $dailyPayments === 1 ? '' : 's' ?>
                <?php if ($activeDays > 0): ?>
                    <span class="bl-dot">·</span> <?= number_format($activeDays) ?> active day<?= $activeDays === 1 ? '' : 's' ?>
                    <span class="bl-dot">·</span> avg ₱<?= number_format($avgTicket, 2) ?> per payment
                <?php endif; ?>
            </div>

            <?php if ($trendPct !== null): ?>
                <span class="rep-trend rep-trend--<?= $trendPct >= 0 ? 'up' : 'down' ?>">
                    <i class="bi bi-arrow-<?= $trendPct >= 0 ? 'up' : 'down' ?>-right"></i>
                    <?= number_format(abs($trendPct), 1) ?>%
                    <small><?= esc($trendLabel) ?></small>
                </span>
            <?php endif; ?>

            <?php if ($spark !== ''): ?>
                <svg class="rep-spark" viewBox="0 0 240 44" preserveAspectRatio="none" aria-hidden="true">
                    <polyline points="<?= esc($spark, 'attr') ?>" fill="none" stroke="currentColor"
                              stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            <?php endif; ?>
        </div>

        <div class="rep-hero-side">
            <div class="rep-mini">
                <span class="rep-mini-label"><i class="bi bi-exclamation-triangle"></i> Outstanding</span>
                <span class="rep-mini-value">₱<?= number_format($outstanding, 2) ?></span>
                <span class="rep-mini-sub">
                    <?= $overduePct > 0
                        ? number_format($overduePct, 0) . '% is 61+ days old'
                        : 'Nothing past 60 days' ?>
                </span>
            </div>

            <div class="rep-mini">
                <span class="rep-mini-label"><i class="bi bi-calendar-check"></i> Best day</span>
                <span class="rep-mini-value">₱<?= number_format($bestDayValue, 2) ?></span>
                <span class="rep-mini-sub"><?= $bestDay ? esc(date('D, M j', strtotime($bestDay))) : 'No activity' ?></span>
            </div>

            <div class="rep-mini">
                <span class="rep-mini-label"><i class="bi bi-tags"></i> Services billed</span>
                <span class="rep-mini-value"><?= number_format($serviceCount) ?></span>
                <span class="rep-mini-sub"><?= $topService ? 'Top: ' . esc($topService) : 'No services yet' ?></span>
            </div>
        </div>
    </section>

    <!-- ─── AT A GLANCE ───────────────────────────────────────── -->
    <?php if ($dailyCollected > 0 || $outstanding > 0): ?>
    <aside class="rep-insights">
        <span class="rep-insights-label"><i class="bi bi-lightbulb"></i> At a glance</span>
        <ul>
            <?php if ($bestDay): ?>
                <li>
                    Strongest day was <strong><?= esc(date('l, M j', strtotime($bestDay))) ?></strong>
                    at ₱<?= number_format($bestDayValue, 2) ?><?php
                        echo $dailyCollected > 0
                            ? ', ' . number_format(($bestDayValue / $dailyCollected) * 100, 0) . '% of the range.'
                            : '.'; ?>
                </li>
            <?php endif; ?>

            <?php if ($topService && $topThreeShare > 0): ?>
                <li>
                    The top three services carry <strong><?= number_format($topThreeShare, 0) ?>%</strong>
                    of revenue, led by <strong><?= esc($topService) ?></strong>.
                </li>
            <?php endif; ?>

            <?php if ((float) ($aging['61+'] ?? 0) > 0): ?>
                <li class="is-warn">
                    <strong>₱<?= number_format((float) $aging['61+'], 2) ?></strong>
                    has been outstanding more than 60 days — chase these first.
                </li>
            <?php elseif ($outstanding > 0): ?>
                <li>All ₱<?= number_format($outstanding, 2) ?> outstanding is still within 60 days.</li>
            <?php endif; ?>
        </ul>
    </aside>
    <?php endif; ?>

    <!-- ─── MAIN GRID — collections (wide) + aging rail ───────── -->
    <div class="rep-grid">

        <!-- ============ COLLECTIONS ============ -->
        <section class="bl-panel rep-panel">
            <header class="rep-panel-head">
                <div>
                    <h3 class="rep-panel-title"><i class="bi bi-graph-up-arrow"></i> Daily collections</h3>
                    <p class="rep-panel-sub">
                        <?= $dailyCollected > 0
                            ? '₱' . number_format($dailyCollected, 2) . ' across ' . $activeDays . ' active day' . ($activeDays === 1 ? '' : 's')
                            : 'No collections in this range' ?>
                    </p>
                </div>

                <?php if (!empty($daily)): ?>
                    <div class="rep-toggle" role="tablist" aria-label="View">
                        <button type="button" id="viewChart" role="tab" aria-selected="true" aria-controls="panelChart">
                            <i class="bi bi-bar-chart-line"></i> Chart
                        </button>
                        <button type="button" id="viewTable" role="tab" aria-selected="false" aria-controls="panelTable">
                            <i class="bi bi-table"></i> Table
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
                    <h3 class="rep-panel-title"><i class="bi bi-hourglass-split"></i> Receivables aging</h3>
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

    <!-- ─── REVENUE BY SERVICE ────────────────────────────────── -->
    <section class="bl-panel rep-panel">
        <header class="rep-panel-head">
            <div>
                <h3 class="rep-panel-title"><i class="bi bi-tags"></i> Revenue by service</h3>
                <p class="rep-panel-sub">
                    <?= number_format(count($rankedServices)) ?> service<?= count($rankedServices) === 1 ? '' : 's' ?>
                    <span class="bl-dot">·</span> ₱<?= number_format($serviceTotal, 2) ?> total
                    <?php if ($topThreeShare > 0): ?>
                        <span class="bl-dot">·</span> top 3 = <?= number_format($topThreeShare, 0) ?>%
                    <?php endif; ?>
                </p>
            </div>
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

            <div class="rep-panel-body">
                <div class="rep-chart rep-chart--bar">
                    <canvas id="repServiceChart" aria-label="Revenue by service chart"></canvas>
                    <div class="rep-chart-fallback" id="serviceFallback" hidden>
                        <i class="bi bi-wifi-off"></i>
                        <p>Charts need an internet connection. The figures are in the table below.</p>
                    </div>
                </div>
            </div>

            <div class="rep-panel-body rep-panel-body--flush">
                <table class="bl-lines rep-lines">
                    <caption class="visually-hidden">Revenue by service in the selected range</caption>
                    <thead>
                        <tr>
                            <th class="rep-rank-col">#</th>
                            <th>Service</th>
                            <th class="bl-num">Times billed</th>
                            <th class="bl-num rep-hide-sm">Avg / billing</th>
                            <th class="bl-num">Revenue</th>
                            <th class="rep-hide-sm">Share</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $rank = 0; foreach ($rankedServices as $name => $row):
                            $rank++;
                            $rev   = (float) ($row['revenue'] ?? 0);
                            $cnt   = (int)   ($row['count']   ?? 0);
                            $share = $serviceTotal  > 0 ? ($rev / $serviceTotal) * 100 : 0;
                            $barW  = $maxServiceRev > 0 ? ($rev / $maxServiceRev) * 100 : 0;
                            $avg   = $cnt > 0 ? $rev / $cnt : 0;
                        ?>
                            <tr<?= $rank <= 3 ? ' class="is-top"' : '' ?>>
                                <td class="rep-rank-col"><span class="rep-rank"><?= $rank ?></span></td>
                                <td class="bl-strong"><?= esc($name) ?></td>
                                <td class="bl-num"><?= number_format($cnt) ?></td>
                                <td class="bl-num rep-hide-sm rep-dim">₱<?= number_format($avg, 2) ?></td>
                                <td class="bl-num bl-strong">₱<?= number_format($rev, 2) ?></td>
                                <td class="rep-hide-sm">
                                    <div class="rep-share">
                                        <div class="rep-share-bar" role="progressbar"
                                             aria-label="<?= round($share, 1) ?>% of total revenue"
                                             aria-valuenow="<?= round($share, 1) ?>"
                                             aria-valuemin="0" aria-valuemax="100">
                                            <span style="width: <?= round($barW, 2) ?>%"></span>
                                        </div>
                                        <span class="rep-share-pct"><?= round($share, 1) ?>%</span>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td class="rep-rank-col"></td>
                            <td>Total</td>
                            <td class="bl-num"><?= number_format($serviceCount) ?></td>
                            <td class="bl-num rep-hide-sm"></td>
                            <td class="bl-num">₱<?= number_format($serviceTotal, 2) ?></td>
                            <td class="rep-hide-sm"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
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
    --bl-ink:#0f172a; --bl-text:#334155; --bl-muted:#64748b; --bl-faint:#94a3b8;
    --bl-line:#e2e8f0; --bl-line-soft:#f1f5f9; --bl-surface:#fff; --bl-subtle:#f8fafc;

    /* Accent — matched to the layout's --active-blue / --sb-active-* */
    --bl-accent:#1976d2;
    --bl-accent-dark:#1565c0;
    --bl-accent-soft:#e3f2fd;
    --bl-accent-glow:rgba(25,118,210,.45);

    --bl-warn:#b45309; --bl-warn-soft:#fff7ed;
    --bl-danger:#b91c1c; --bl-danger-soft:#fef2f2;
    --bl-green:#047857; --bl-green-soft:#ecfdf5;
    --bl-blue:#1d4ed8; --bl-blue-soft:#eaf2fe;
    --bl-mono:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;
    --bl-radius:14px;
    --bl-shadow:0 1px 2px rgba(15,23,42,.04), 0 1px 3px rgba(15,23,42,.03);
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
          border:1px solid var(--bl-line); border-radius:9px; text-decoration:none;
          white-space:nowrap; cursor:pointer;
          transition:background .15s, border-color .15s, color .15s, box-shadow .15s; }
.bl-btn:hover { background:var(--bl-subtle); border-color:#cbd5e1; color:var(--bl-ink); }
.bl-btn--primary, .bl-btn--primary:hover { color:#fff; }
.bl-btn--primary { background:var(--bl-accent); border-color:var(--bl-accent);
                   box-shadow:0 1px 2px var(--bl-accent-glow); }
.bl-btn--primary:hover { background:var(--bl-accent-dark); border-color:var(--bl-accent-dark);
                         box-shadow:0 4px 12px var(--bl-accent-glow); }

.bl-input { height:36px; font-family:inherit; font-size:.8125rem; color:var(--bl-ink);
            background:var(--bl-surface); border:1px solid var(--bl-line); border-radius:9px;
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

.rep-range {
    display:inline-block;
    font-family:var(--bl-mono);
    font-size:.75rem;
    padding:.1rem .5rem;
    border-radius:6px;
    background:var(--bl-accent-soft);
    color:var(--bl-accent-dark);
}

/* ---- Sticky filters ---- */
.rep-filters {
    position:sticky; top:0; z-index:5;
    display:flex; align-items:center; gap:.6rem; flex-wrap:wrap;
    background:rgba(255,255,255,.93);
    backdrop-filter:blur(8px);
    border:1px solid var(--bl-line);
    border-radius:var(--bl-radius);
    padding:.7rem .9rem;
    box-shadow:0 4px 16px -10px rgba(15,23,42,.18);
    margin-bottom:1.1rem;
}
.rep-filters-label {
    display:inline-flex; align-items:center; gap:.4rem;
    font-size:.72rem; font-weight:600; letter-spacing:.06em;
    text-transform:uppercase; color:var(--bl-muted);
    padding-right:.25rem;
}
.rep-filters-label i { color:var(--bl-accent); }

.rep-quick {
    display:inline-flex; gap:2px; padding:3px;
    background:var(--bl-subtle); border:1px solid var(--bl-line);
    border-radius:99px;
}
.rep-quick-btn {
    border:none; background:transparent; border-radius:99px;
    padding:.28rem .7rem; font-size:.74rem; font-weight:600;
    font-family:inherit; color:var(--bl-muted); cursor:pointer;
    white-space:nowrap; transition:background .15s, color .15s;
}
.rep-quick-btn:hover { color:var(--bl-ink); }
.rep-quick-btn.is-active {
    background:#fff; color:var(--bl-accent-dark);
    box-shadow:0 1px 2px rgba(15,23,42,.1);
}
.rep-filters-actions { display:inline-flex; gap:.5rem; margin-left:auto; }

/* =========================================================
   HERO
   ========================================================= */

.rep-hero {
    display:grid;
    grid-template-columns:minmax(0,1.15fr) minmax(0,1.85fr);
    gap:1rem;
    margin-bottom:1rem;
}

.rep-hero-main {
    position:relative; overflow:hidden;
    padding:1.3rem 1.4rem 1.1rem;
    border-radius:var(--bl-radius);
    /* Matches the sidebar gradient — navy → blue, same two-stop rhythm */
    background:linear-gradient(145deg,#123566 0%,#1976d2 60%,#43a3f5 100%);
    color:#fff;
    box-shadow:0 10px 24px -14px var(--bl-accent-glow);
}

.rep-hero-label { font-size:.7rem; font-weight:700; letter-spacing:.09em;
                  text-transform:uppercase; color:rgba(255,255,255,.75); }

.rep-hero-value { margin-top:.25rem; font-size:2rem; font-weight:750;
                  letter-spacing:-.03em; font-variant-numeric:tabular-nums; line-height:1.1; }

.rep-hero-meta { margin-top:.3rem; font-size:.76rem; color:rgba(255,255,255,.82); }
.rep-hero-meta .bl-dot { color:rgba(255,255,255,.45); }

.rep-trend { display:inline-flex; align-items:baseline; gap:.3rem;
             margin-top:.7rem; padding:.2rem .55rem; border-radius:99px;
             font-size:.75rem; font-weight:700; background:rgba(255,255,255,.18); }
.rep-trend small { font-weight:500; opacity:.82; font-size:.68rem; }
.rep-trend--down { background:rgba(15,23,42,.22); }

.rep-spark { position:absolute; right:0; bottom:0; width:62%; height:46px;
             color:rgba(255,255,255,.4); pointer-events:none; }

.rep-hero-side { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:1rem; }

.rep-mini { display:flex; flex-direction:column; gap:.15rem;
            padding:1rem 1.05rem; background:var(--bl-surface);
            border:1px solid var(--bl-line); border-radius:var(--bl-radius);
            box-shadow:var(--bl-shadow); }

.rep-mini-label { display:flex; align-items:center; gap:.35rem;
                  font-size:.7rem; font-weight:700; letter-spacing:.07em;
                  text-transform:uppercase; color:var(--bl-faint); }
.rep-mini-label i { color:var(--bl-accent); font-size:.95em; }

.rep-mini-value { font-size:1.3rem; font-weight:700; color:var(--bl-ink);
                  font-variant-numeric:tabular-nums; letter-spacing:-.02em; line-height:1.2; }

.rep-mini-sub { font-size:.74rem; color:var(--bl-muted);
                overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }

/* =========================================================
   INSIGHTS
   ========================================================= */

.rep-insights { display:flex; align-items:flex-start; gap:1rem;
                padding:.85rem 1.1rem; margin-bottom:1rem;
                background:var(--bl-accent-soft);
                border:1px solid color-mix(in srgb, var(--bl-accent) 25%, #ffffff);
                border-radius:var(--bl-radius); }

.rep-insights-label { display:inline-flex; align-items:center; gap:.35rem; flex-shrink:0;
                      font-size:.68rem; font-weight:700; letter-spacing:.08em;
                      text-transform:uppercase; color:var(--bl-accent-dark); padding-top:.15rem; }

.rep-insights ul { margin:0; padding:0; list-style:none; display:flex; flex-direction:column; gap:.25rem; }
.rep-insights li { font-size:.8125rem; color:#0d47a1; }
.rep-insights li strong { color:var(--bl-accent-dark); }
.rep-insights li.is-warn { color:var(--bl-warn); }
.rep-insights li.is-warn strong { color:#92400e; }

/* =========================================================
   GRID + PANELS
   ========================================================= */

.rep-grid { display:grid; grid-template-columns:minmax(0,1.65fr) minmax(300px,1fr);
            gap:1rem; align-items:start; margin-bottom:1rem; }

.rep-panel { margin-bottom:1rem; }
.rep-grid .rep-panel { margin-bottom:0; }

.rep-panel-head { display:flex; align-items:center; justify-content:space-between;
                  gap:1rem; flex-wrap:wrap; padding:.95rem 1.2rem;
                  border-bottom:1px solid var(--bl-line-soft);
                  background:linear-gradient(180deg,#fcfdfe,#fff); }

.rep-panel-title { display:flex; align-items:center; gap:.5rem; margin:0;
                   font-size:.8125rem; font-weight:700; letter-spacing:.05em;
                   text-transform:uppercase; color:var(--bl-ink); }
.rep-panel-title i { color:var(--bl-accent); font-size:1.05em; }
.rep-panel-sub { margin:.2rem 0 0; font-size:.76rem; color:var(--bl-muted); }

.rep-panel-body { padding:1.1rem 1.2rem; }
.rep-panel-body--flush { padding:0; }

/* Chart / Table toggle */
.rep-toggle { display:inline-flex; gap:2px; padding:3px;
              background:var(--bl-subtle); border:1px solid var(--bl-line); border-radius:99px; }

.rep-toggle button { display:inline-flex; align-items:center; gap:.3rem;
                     border:none; background:transparent; border-radius:99px;
                     padding:.3rem .75rem; font-family:inherit; font-size:.74rem;
                     font-weight:650; color:var(--bl-muted); cursor:pointer;
                     transition:background .15s, color .15s; }
.rep-toggle button:hover { color:var(--bl-ink); }
.rep-toggle button[aria-selected="true"] {
    background:#fff; color:var(--bl-accent-dark);
    box-shadow:0 1px 2px rgba(15,23,42,.12);
}

/* =========================================================
   AGING RAIL
   ========================================================= */

.rep-stack { display:flex; height:12px; border-radius:99px; overflow:hidden;
             background:var(--bl-line-soft); margin-bottom:1rem; }
.rep-stack-seg { display:block; height:100%; }
.rep-stack-seg--green  { background:#10b981; }
.rep-stack-seg--blue   { background:var(--bl-accent); }
.rep-stack-seg--danger { background:#dc2626; }

.rep-aging-list { list-style:none; margin:0; padding:0; display:flex; flex-direction:column; }

.rep-aging-row { display:flex; align-items:center; gap:.65rem; padding:.6rem 0;
                 border-bottom:1px solid var(--bl-line-soft); }
.rep-aging-row:last-child { border-bottom:0; }

.rep-aging-dot { width:9px; height:9px; border-radius:3px; flex-shrink:0; background:var(--tone,#cbd5e1); }
.rep-aging-row--green  { --tone:#10b981; }
.rep-aging-row--blue   { --tone:var(--bl-accent); }
.rep-aging-row--danger { --tone:#dc2626; }

.rep-aging-text { display:flex; flex-direction:column; min-width:0; }
.rep-aging-label { font-size:.8125rem; font-weight:600; color:var(--bl-ink); }
.rep-aging-note { font-size:.68rem; letter-spacing:.05em; text-transform:uppercase; color:var(--bl-faint); }

.rep-aging-figures { margin-left:auto; text-align:right; }
.rep-aging-value { display:block; font-size:.9rem; font-weight:700; color:var(--bl-ink);
                   font-variant-numeric:tabular-nums; }
.rep-aging-pct { display:block; font-size:.7rem; color:var(--bl-faint); font-variant-numeric:tabular-nums; }

.rep-aging-flag { display:flex; align-items:flex-start; gap:.45rem;
                  margin:1rem 0 0; padding:.6rem .75rem; border-radius:9px;
                  background:var(--bl-warn-soft); border:1px solid #fed7aa;
                  font-size:.76rem; color:var(--bl-warn); }

.rep-foot { margin:0; padding-top:.25rem; font-size:.72rem;
            color:var(--bl-faint); text-align:center; }

/* ---- Charts ---- */
.rep-chart { position:relative; }
.rep-chart--line { height:268px; }
.rep-chart--bar  { height:320px; }

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
    background:linear-gradient(90deg,#90caf9,var(--bl-accent));
    transition:width .4s ease;
}
.rep-share-pct {
    font-size:.72rem; font-weight:600; color:var(--bl-muted);
    font-variant-numeric:tabular-nums; min-width:38px; text-align:right;
}

/* ---- Tables ---- */
.rep-lines th {
    padding:.6rem 1.2rem; text-align:left;
    font-size:.68rem; font-weight:700; letter-spacing:.06em;
    text-transform:uppercase; color:var(--bl-faint);
    background:var(--bl-subtle); border-bottom:1px solid var(--bl-line);
    white-space:nowrap; position:sticky; top:0; z-index:1;
}

.rep-lines td { padding:.7rem 1.2rem; border-bottom:1px solid var(--bl-line-soft); }
.rep-lines tbody tr:last-child td { border-bottom:0; }
.rep-lines tbody tr { transition:background .12s; }
.rep-lines tbody tr:hover { background:#fafbfd; }
.rep-lines tbody tr.is-best { background:#f0f7ff; }
.rep-lines tbody tr.is-top .rep-rank { background:var(--bl-accent); color:#fff; }

.rep-lines tfoot td {
    padding:.7rem 1.2rem; background:var(--bl-subtle);
    border-top:2px solid var(--bl-ink);
    font-weight:700; color:var(--bl-ink); font-variant-numeric:tabular-nums;
}

.rep-dim { color:var(--bl-muted); font-weight:400; }
.rep-rank-col { width:1%; }

.rep-rank {
    display:grid; place-items:center; width:20px; height:20px; border-radius:6px;
    font-size:.68rem; font-weight:700; color:var(--bl-muted);
    background:var(--bl-line-soft); font-variant-numeric:tabular-nums;
}

.rep-date-main { display:block; font-weight:600; color:var(--bl-ink); }
.rep-date-sub  { display:block; font-size:.72rem; color:var(--bl-faint); }

.rep-best-tag {
    display:inline-block; margin-left:.25rem; padding:0 .3rem; border-radius:4px;
    font-size:.62rem; font-weight:700; letter-spacing:.04em; text-transform:uppercase;
    color:var(--bl-accent-dark); background:var(--bl-accent-soft);
}

.bl-empty-cell { padding:1.6rem 1rem; text-align:center; color:var(--bl-muted); font-style:italic; }
.bl-empty-cell i { display:block; font-size:1.3rem; color:var(--bl-faint); margin-bottom:.4rem; }
.bl-empty-cell span { font-size:.8125rem; }

/* ---- Responsive ---- */
@media (max-width:1180px) {
    .rep-hero { grid-template-columns:minmax(0,1fr); }
    .rep-grid { grid-template-columns:minmax(0,1fr); }
}
@media (max-width:900px) {
    .rep-hero-side { gap:.75rem; }
    .rep-mini { padding:.85rem .9rem; }
    .rep-mini-value { font-size:1.1rem; }
    .rep-section-sub { max-width:100%; }
    .rep-chart--line { height:224px; }
    .rep-chart--bar  { height:280px; }
}
@media (max-width:640px) {
    .rep-hero-side { grid-template-columns:minmax(0,1fr); }
    .rep-hero-value { font-size:1.7rem; }
    .rep-filters { position:static; }
    .rep-quick { order:3; }
    .rep-filters-actions { margin-left:0; width:100%; }
    .rep-filters-actions .bl-btn { flex:1; justify-content:center; }
    .rep-hide-sm { display:none; }
    .rep-chart--line { height:200px; }
    .rep-chart--bar  { height:260px; }
    .rep-insights { flex-direction:column; gap:.5rem; }
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
    .rep-print-head span { color:#475569; }

    .bl-panel, .rep-mini, .rep-insights {
        box-shadow:none !important; border-color:#cbd5e1 !important; break-inside:avoid;
    }

    /* The gradient would drink a cartridge; print it as paper */
    .rep-hero-main {
        background:#fff !important; color:#0f172a !important;
        border:1px solid #cbd5e1; box-shadow:none !important;
    }
    .rep-hero-label, .rep-hero-meta { color:#475569 !important; }
    .rep-trend { background:#f1f5f9 !important; color:#0f172a !important; }
    .rep-spark { color:#94a3b8 !important; }

    #panelTable[hidden] { display:block !important; }
    .rep-hide-sm { display:table-cell !important; }
    .rep-chart { break-inside:avoid; page-break-inside:avoid; }
    .rep-grid { grid-template-columns:1fr; }
    .rep-lines thead th { position:static; }
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
    var SVC_LABELS    = <?= json_encode($svcLabels,   JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    var SVC_VALUES    = <?= json_encode($svcValues,   JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

    var CSV_ROWS      = <?= json_encode($csvRows, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

    var $ = function (id) { return document.getElementById(id); };

    var peso = function (v) {
        return '₱' + (Number(v) || 0).toLocaleString('en-PH', {
            minimumFractionDigits: 2, maximumFractionDigits: 2,
        });
    };

    /* Accent colour, matched to the layout's --active-blue */
    var ACCENT       = '#1976d2';
    var ACCENT_DARK  = '#1565c0';
    var ACCENT_SOFT  = 'rgba(25,118,210,.28)';
    var ACCENT_FADE  = 'rgba(25,118,210,0)';
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

        var ctx = canvas.getContext('2d');
        var gradient = ctx.createLinearGradient(0, 0, 0, 260);
        gradient.addColorStop(0, ACCENT_SOFT);
        gradient.addColorStop(1, ACCENT_FADE);

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: DAILY_LABELS,
                datasets: [{
                    label: 'Collected',
                    data: DAILY_VALUES,
                    borderColor: ACCENT,
                    backgroundColor: gradient,
                    fill: true,
                    tension: 0.35,
                    borderWidth: 2.4,
                    pointRadius: 0,
                    pointHoverRadius: 6,
                    pointHoverBackgroundColor: ACCENT,
                    pointHoverBorderColor: '#ffffff',
                    pointHoverBorderWidth: 2.5,
                    yAxisID: 'y',
                }, {
                    label: 'Payments',
                    data: DAILY_COUNTS,
                    backgroundColor: BAR_MUTED,
                    type: 'bar',
                    borderWidth: 0,
                    borderRadius: 4,
                    barPercentage: 0.55,
                    categoryPercentage: 0.8,
                    maxBarThickness: 22,
                    yAxisID: 'y1',
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: {
                        display: true, position: 'top', align: 'end',
                        labels: {
                            usePointStyle: true, pointStyle: 'circle',
                            boxWidth: 8, boxHeight: 8, padding: 14,
                            font: { size: 11 }, color: '#64748b',
                        },
                    },
                    tooltip: Object.assign({}, TOOLTIP, {
                        callbacks: {
                            label: function (c) {
                                if (c.dataset.yAxisID === 'y') {
                                    return 'Collected: ' + peso(c.parsed.y);
                                }
                                var n = c.parsed.y || 0;
                                return 'Payments: ' + n + (n === 1 ? ' payment' : ' payments');
                            },
                        },
                    }),
                },
                scales: {
                    x: {
                        grid: { display: false, drawBorder: false },
                        border: { display: false },
                        ticks: { color: '#94a3b8', font: { size: 11 }, maxRotation: 0, autoSkip: true, maxTicksLimit: 10 },
                    },
                    y: {
                        beginAtZero: true,
                        position: 'left',
                        grid: { color: '#f1f5f9', drawBorder: false, drawTicks: false },
                        border: { display: false },
                        ticks: {
                            color: '#94a3b8', font: { size: 11 }, padding: 8, maxTicksLimit: 6,
                            callback: function (v) {
                                if (v >= 1000) return '₱' + (v / 1000).toLocaleString('en-PH', { maximumFractionDigits: 1 }) + 'k';
                                return '₱' + v;
                            },
                        },
                    },
                    y1: {
                        beginAtZero: true, position: 'right',
                        grid: { display: false },
                        border: { display: false },
                        ticks: { color: '#cbd5e1', font: { size: 11 }, precision: 0, maxTicksLimit: 5, padding: 8 },
                    },
                },
            },
        });
    }

    function drawServiceChart() {
        var canvas = $('repServiceChart');
        if (!canvas || !SVC_LABELS.length) { return; }

        if (!hasChart()) {
            canvas.hidden = true;
            if ($('serviceFallback')) { $('serviceFallback').hidden = false; }
            return;
        }

        /* Sequential blue shades, darkest = highest revenue. */
        var shades = ['#0b3d91', '#1565c0', '#1976d2', '#2196f3',
                      '#42a5f5', '#64b5f6', '#90caf9', '#bbdefb'];

        new Chart(canvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels: SVC_LABELS,
                datasets: [{
                    label: 'Revenue',
                    data: SVC_VALUES,
                    backgroundColor: SVC_LABELS.map(function (_, i) {
                        return shades[i] || '#bbdefb';
                    }),
                    borderRadius: 6,
                    borderSkipped: false,
                    barPercentage: 0.75,
                    categoryPercentage: 0.85,
                    maxBarThickness: 26,
                }],
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: Object.assign({}, TOOLTIP, {
                        callbacks: {
                            label: function (c) {
                                var total = SVC_VALUES.reduce(function (a, b) { return a + b; }, 0);
                                var pct = total ? Math.round((c.parsed.x / total) * 1000) / 10 : 0;
                                return peso(c.parsed.x) + ' · ' + pct + '% of revenue';
                            },
                        },
                    }),
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        grid: { color: '#f1f5f9', drawBorder: false, drawTicks: false },
                        border: { display: false },
                        ticks: {
                            color: '#94a3b8', font: { size: 11 }, padding: 8, maxTicksLimit: 6,
                            callback: function (v) {
                                if (v >= 1000) return '₱' + (v / 1000).toLocaleString('en-PH', { maximumFractionDigits: 1 }) + 'k';
                                return '₱' + v;
                            },
                        },
                    },
                    y: {
                        grid: { display: false },
                        border: { display: false },
                        ticks: { color: '#334155', font: { size: 11.5, weight: '600' } },
                    },
                },
            },
        });
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
        drawServiceChart();
    });
})();
</script>

<?= $this->endSection() ?>