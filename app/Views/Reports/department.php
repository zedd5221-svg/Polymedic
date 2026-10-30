<?php
    /*
     * Department Report — printable A4 (Radiology / Laboratory).
     * Data: HandlesDepartmentReports::departmentReportData() + reportNo, annex, backUrl.
     */
    $s = $summary;
    $p = $prev;
    $d = $dept;
    [$caseOne, $caseMany] = $d['case'];
    [$itemOne, $itemMany] = $d['item'];
    [$staffTitle, $staffMany] = $d['staff'];
    $scopeLabel = $staff !== '' ? $staff : 'All ' . $staffMany;

    $paper = [
        'kind'       => 'Department report',
        'title'      => $d['paperTitle'],
        'unit'       => $d['unitName'],
        'no'         => $reportNo,
        'meta'       => [
            'Reporting period'   => $periodLabel,
            'Comparative period' => $prevLabel,
            'Coverage'           => $scopeLabel,
            'Date prepared'      => date('F j, Y · g:i A'),
        ],
        'conf'       => 'Confidential · For internal use only · Contains patient information',
        'backUrl'    => $backUrl,
        'annex'      => $annex,
        'annexLabel' => 'Include ' . $caseOne . ' register (Annex A)',
        'endnote'    => 'Not a diagnostic result',
    ];
    include __DIR__ . '/_paper_top.php';

    // 0.75 -> "45 min", 5.2 -> "5.2 h", 80 -> "3.3 d"
    $dur = static function (?float $h): string {
        if ($h === null) return '–';
        if ($h < 1)  return max(1, (int) round($h * 60)) . ' min';
        if ($h < 48) return number_format($h, 1) . ' h';
        return number_format($h / 24, 1) . ' d';
    };
    $durDiff = static function (?float $a, ?float $b) use ($dur): string {
        if ($a === null || $b === null) return '–';
        $diff = $a - $b;
        if (abs($diff) < 0.05) return '–';
        $text = $dur(abs($diff));
        return $diff < 0 ? '(' . $text . ')' : $text;
    };
    $pctPts = static function (?float $a, ?float $b): string {
        if ($a === null || $b === null) return '–';
        $diff = $a - $b;
        if (abs($diff) < 0.05) return '–';
        return ($diff > 0 ? '+' : '−') . number_format(abs($diff), 1) . ' pts';
    };
    $rate = static fn(?float $v) => $v === null ? '–' : number_format($v, 1) . '%';
    $cases = static fn(int $n) => $n === 1 ? $caseOne : $caseMany;

    // ---- Executive summary, written from the numbers ----
    $summaryText = [];
    if ($s['received'] === 0 && $s['released'] === 0) {
        $summaryText[] = 'No ' . $caseMany . ' were received or released for ' . $periodLabel . ' (' . $scopeLabel . ').';
    } else {
        $trend = '';
        if ($p['received'] > 0) {
            $chg = ($s['received'] - $p['received']) / $p['received'] * 100;
            $trend = abs($chg) < 0.05 ? ', unchanged from the comparative period'
                : ', ' . ($chg > 0 ? 'up ' : 'down ') . number_format(abs($chg), 1) . '% from ' . $p['received'] . ' in the comparative period';
        } elseif ($s['received'] > 0) {
            $trend = ', with none in the comparative period';
        }
        $summaryText[] = sprintf(
            'The %s received %d %s covering %d %s for %d patient%s%s.',
            strtolower($d['name']) === 'laboratory' ? 'laboratory' : 'department',
            $s['received'], $cases($s['received']), $s['items'], $s['items'] === 1 ? $itemOne : $itemMany,
            $s['patients'], $s['patients'] === 1 ? '' : 's', $trend
        );
        $summaryText[] = sprintf(
            '%d result%s %s released in the period%s.',
            $s['released'], $s['released'] === 1 ? '' : 's', $s['released'] === 1 ? 'was' : 'were',
            $s['release_rate'] !== null ? '; ' . number_format($s['release_rate'], 0) . '% of this period\'s ' . $caseMany . ' are already released' : ''
        );
        if ($s['tat_median'] !== null) {
            $summaryText[] = sprintf(
                'Median turnaround from payment to release was %s (average %s), and %s of results were released within target.',
                $dur($s['tat_median']), $dur($s['tat_avg']), $rate($s['within_target'])
            );
        }
    }
    $summaryText[] = count($backlog['rows'])
        ? sprintf('As of printing, %d %s %s unreleased, %d of them past target.',
            count($backlog['rows']), $cases(count($backlog['rows'])), count($backlog['rows']) === 1 ? 'is' : 'are', $backlog['overdue'])
        : 'As of printing, there is no unreleased backlog.';

    // ---- Schedule ----
    $buckets = $daily['buckets'];
    $unitWord = $daily['hourly'] ? 'hour' : 'day';
    $chart = [
        'rows' => array_map(static fn($b) => [
            'value' => $b['received'],
            'tick'  => $b['label'],
            'title' => $b['label'] . ': ' . $b['received'] . ' received · ' . $b['released'] . ' released',
        ], $buckets),
        'short'   => static fn(float $v) => number_format($v),
        'integer' => true,
        'aria'    => ucfirst($caseMany) . ' received per ' . $unitWord,
        'caption' => 'Figure 1. ' . ucfirst($caseMany) . ' received per ' . $unitWord . ', ' . $periodLabel . '. Values in the table below.',
    ];

    $priorityLabels = ['stat' => 'STAT', 'urgent' => 'Urgent', 'routine' => 'Routine'];
    $topItems = $s['top'];
    $oldest   = $backlog['rows'];   // backlog() is oldest first
?>

    <!-- ===== 1. SUMMARY ===== -->
    <section>
        <h2><span class="num">1</span> Executive Summary</h2>
        <p class="lead"><?= esc(implode(' ', $summaryText)) ?></p>

        <dl class="figs">
            <div class="fig"><dt><?= esc(ucfirst($caseMany)) ?> received</dt><dd><?= number_format($s['received']) ?></dd><span class="vs"><?= $pct($s['received'], $p['received']) ?> vs comparative</span></div>
            <div class="fig"><dt>Results released</dt><dd><?= number_format($s['released']) ?></dd><span class="vs"><?= $pct($s['released'], $p['released']) ?> vs comparative</span></div>
            <div class="fig"><dt>Median turnaround</dt><dd><?= $dur($s['tat_median']) ?></dd><span class="vs">Comparative <?= $dur($p['tat_median']) ?></span></div>
            <div class="fig"><dt>Within target</dt><dd><?= $rate($s['within_target']) ?></dd><span class="vs">Comparative <?= $rate($p['within_target']) ?></span></div>
        </dl>
    </section>

    <!-- ===== 2. WORKLOAD STATEMENT ===== -->
    <section>
        <h2><span class="num">2</span> Workload Statement <small>By request date; releases by release date</small></h2>
        <div class="scroll">
        <table class="stmt">
            <thead>
                <tr><th>Particulars</th><th class="r">This period</th><th class="r">Comparative</th><th class="r">Change</th><th class="r">%</th></tr>
            </thead>
            <tbody>
                <tr class="group"><td colspan="5">Volume</td></tr>
                <?php foreach ([
                    [ucfirst($caseMany) . ' received', $s['received'], $p['received']],
                    [ucfirst($itemMany) . ' ordered', $s['items'], $p['items']],
                    ['Patients served', $s['patients'], $p['patients']],
                    ['STAT / urgent ' . $caseMany, $s['stat'], $p['stat']],
                ] as [$label, $a, $b]): ?>
                    <tr class="indent">
                        <td><?= esc($label) ?></td>
                        <td class="r"><?= $num($a) ?></td>
                        <td class="r"><?= $num($b) ?></td>
                        <td class="r"><?= $num($a - $b) ?></td>
                        <td class="r muted"><?= $pct($a, $b) ?></td>
                    </tr>
                <?php endforeach; ?>

                <tr class="group"><td colspan="5">Output</td></tr>
                <tr class="indent">
                    <td>Results released</td>
                    <td class="r"><b><?= $num($s['released']) ?></b></td>
                    <td class="r"><?= $num($p['released']) ?></td>
                    <td class="r"><?= $num($s['released'] - $p['released']) ?></td>
                    <td class="r muted"><?= $pct($s['released'], $p['released']) ?></td>
                </tr>
                <tr class="indent">
                    <td>Release rate (period's <?= $caseMany ?>)</td>
                    <td class="r"><?= $rate($s['release_rate']) ?></td>
                    <td class="r"><?= $rate($p['release_rate']) ?></td>
                    <td class="r"><?= $pctPts($s['release_rate'], $p['release_rate']) ?></td>
                    <td class="r muted">–</td>
                </tr>

                <tr class="group"><td colspan="5">Turnaround, payment to release</td></tr>
                <tr class="indent">
                    <td>Median turnaround</td>
                    <td class="r"><?= $dur($s['tat_median']) ?></td>
                    <td class="r"><?= $dur($p['tat_median']) ?></td>
                    <td class="r <?= $s['tat_median'] !== null && $p['tat_median'] !== null && $s['tat_median'] > $p['tat_median'] ? 'neg' : '' ?>"><?= $durDiff($s['tat_median'], $p['tat_median']) ?></td>
                    <td class="r muted"><?= $pct($s['tat_median'], $p['tat_median']) ?></td>
                </tr>
                <tr class="indent">
                    <td>Average turnaround</td>
                    <td class="r"><?= $dur($s['tat_avg']) ?></td>
                    <td class="r"><?= $dur($p['tat_avg']) ?></td>
                    <td class="r"><?= $durDiff($s['tat_avg'], $p['tat_avg']) ?></td>
                    <td class="r muted"><?= $pct($s['tat_avg'], $p['tat_avg']) ?></td>
                </tr>
                <tr class="total">
                    <td>RELEASED WITHIN TARGET</td>
                    <td class="r"><?= $rate($s['within_target']) ?></td>
                    <td class="r"><?= $rate($p['within_target']) ?></td>
                    <td class="r <?= $s['within_target'] !== null && $p['within_target'] !== null && $s['within_target'] < $p['within_target'] ? 'neg' : '' ?>"><?= $pctPts($s['within_target'], $p['within_target']) ?></td>
                    <td class="r">–</td>
                </tr>

                <tr class="group"><td colspan="5">Collections</td></tr>
                <tr class="indent memo">
                    <td><?= esc($d['revenueLabel']) ?> (department-wide)</td>
                    <td class="r"><?= $amt($revenue, true) ?></td>
                    <td class="r"><?= $amt($prevRevenue, true) ?></td>
                    <td class="r"><?= $amt($revenue - $prevRevenue) ?></td>
                    <td class="r muted"><?= $pct($revenue, $prevRevenue) ?></td>
                </tr>
            </tbody>
        </table>
        </div>
    </section>

    <!-- ===== 3. TURNAROUND COMPLIANCE ===== -->
    <section>
        <h2><span class="num">3</span> Turnaround Compliance <small>Results released in the period</small></h2>
        <div class="two">
            <table>
                <thead><tr><th>Priority</th><th class="r">Target</th><th class="r">Released</th><th class="r">Within</th><th class="r">Compliance</th><th class="r">Median</th></tr></thead>
                <tbody>
                    <?php foreach ($s['priority'] as $key => $pr): ?>
                        <tr>
                            <td><?= $priorityLabels[$key] ?? ucfirst($key) ?></td>
                            <td class="r muted"><?= $targets[$key] ?> h</td>
                            <td class="r"><?= $num($pr['released']) ?></td>
                            <td class="r"><?= $num($pr['within']) ?></td>
                            <td class="r <?= $pr['released'] && $pr['within'] < $pr['released'] ? 'neg' : '' ?>"><?= $pr['released'] ? $share($pr['within'], $pr['released']) : '–' ?></td>
                            <td class="r"><?= $dur($pr['median']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td>All priorities</td><td></td>
                        <td class="r"><?= $num($s['released']) ?></td>
                        <td class="r"><?= $num(array_sum(array_column($s['priority'], 'within'))) ?></td>
                        <td class="r"><?= $rate($s['within_target']) ?></td>
                        <td class="r"><?= $dur($s['tat_median']) ?></td>
                    </tr>
                </tfoot>
            </table>

            <table>
                <thead><tr><th>Time to release</th><th class="r">Results</th><th class="r">Share</th></tr></thead>
                <tbody>
                    <?php $tatTotal = array_sum($s['tat_buckets']); foreach ($s['tat_buckets'] as $label => $n): ?>
                        <tr>
                            <td><?= esc($label) ?></td>
                            <td class="r"><?= $num($n) ?></td>
                            <td class="r muted"><?= $share($n, $tatTotal) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot><tr><td>Total</td><td class="r"><?= $num($tatTotal) ?></td><td class="r"><?= $tatTotal ? '100.0%' : '–' ?></td></tr></tfoot>
            </table>
        </div>
    </section>

    <!-- ===== 4. STATUS + STAFF ===== -->
    <section>
        <h2><span class="num">4</span> Status &amp; <?= esc($staffTitle) ?> Workload</h2>
        <div class="two">
            <table>
                <thead><tr><th>Status of period's <?= $caseMany ?></th><th class="r">Count</th><th class="r">Share</th></tr></thead>
                <tbody>
                    <?php foreach ($d['status'] as $key => $label): ?>
                        <tr>
                            <td><?= esc($label) ?></td>
                            <td class="r"><?= $num($s['status'][$key]) ?></td>
                            <td class="r muted"><?= $share($s['status'][$key], $s['received']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot><tr><td>Total received</td><td class="r"><?= $num($s['received']) ?></td><td class="r"><?= $s['received'] ? '100.0%' : '–' ?></td></tr></tfoot>
            </table>

            <table>
                <thead><tr><th><?= esc($staffTitle) ?></th><th class="r">Results</th><th class="r"><?= ucfirst($itemMany) ?></th><th class="r">Avg TAT</th></tr></thead>
                <tbody>
                    <?php if (!$s['staff']): ?>
                        <tr><td colspan="4" class="muted">No results released in this period.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($s['staff'] as $name => $rd): ?>
                        <tr>
                            <td><?= esc($name) ?></td>
                            <td class="r"><?= $num($rd['count']) ?></td>
                            <td class="r"><?= $num($rd['items']) ?></td>
                            <td class="r"><?= $dur($rd['tat_avg']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot><tr><td>Total</td><td class="r"><?= $num($s['released']) ?></td><td class="r"><?= $num(array_sum(array_column($s['staff'], 'items'))) ?></td><td class="r"><?= $dur($s['tat_avg']) ?></td></tr></tfoot>
            </table>
        </div>
    </section>

    <!-- ===== 5. SCHEDULE ===== -->
    <section class="long">
        <div class="keep">
            <h2><span class="num">5</span> Volume Schedule <small><?= ucfirst($caseMany) ?> received and results released per <?= $unitWord ?></small></h2>

            <?php include __DIR__ . '/_paper_chart.php'; ?>
        </div>

        <div class="scroll">
        <table class="<?= count($buckets) > 14 ? 'dense' : '' ?>">
            <thead><tr><th><?= $daily['hourly'] ? 'Hour' : 'Date' ?></th><th class="r">Received</th><th class="r">Released</th><th class="r">Net change in queue</th></tr></thead>
            <tbody>
                <?php foreach ($buckets as $b): $empty = !$b['received'] && !$b['released']; ?>
                    <tr class="<?= $empty ? 'muted' : '' ?>">
                        <td><?= esc(isset($b['date']) ? date('D, M j', strtotime($b['date'])) : $b['label']) ?></td>
                        <td class="r"><?= $num($b['received']) ?></td>
                        <td class="r"><?= $num($b['released']) ?></td>
                        <td class="r"><?= $num($b['received'] - $b['released']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td>Total</td>
                    <td class="r"><?= $num(array_sum(array_column($buckets, 'received'))) ?></td>
                    <td class="r"><?= $num(array_sum(array_column($buckets, 'released'))) ?></td>
                    <td class="r"><?= $num(array_sum(array_column($buckets, 'received')) - array_sum(array_column($buckets, 'released'))) ?></td>
                </tr>
            </tfoot>
        </table>
        </div>
        <p class="note">Received counts <?= $caseMany ?> by <?= $daily['hourly'] ? 'time paid' : 'request date' ?>; released counts results by release date, whatever their request date. A positive net change means the queue grew.</p>
    </section>

    <!-- ===== 6. TOP ITEMS (+ SECTIONS) ===== -->
    <section class="<?= count($topItems) > 25 ? "long" : "" ?>">
        <h2><span class="num">6</span> <?= $d['sections'] ? ucfirst($itemMany) . ' by Section and Volume' : 'Top ' . ucfirst($itemMany) ?></h2>
        <div class="<?= $d['sections'] ? 'two' : '' ?>">
            <table>
                <thead><tr><th>#</th><th><?= ucfirst($itemOne) ?></th><?php if ($d['sections']): ?><th>Section</th><?php endif; ?><th class="r">Ordered</th><th class="r">Share</th></tr></thead>
                <tbody>
                    <?php if (!$topItems): ?>
                        <tr><td colspan="5" class="muted">No <?= $itemMany ?> in this period.</td></tr>
                    <?php endif; ?>
                    <?php $i = 0; foreach ($topItems as $name => $n): ?>
                        <tr>
                            <td class="muted"><?= ++$i ?></td>
                            <td><?= esc($name) ?></td>
                            <?php if ($d['sections']): ?><td class="muted"><?= esc(\App\Models\DiagnosticRequestModel::labSection($name)) ?></td><?php endif; ?>
                            <td class="r"><?= $num($n) ?></td>
                            <td class="r muted"><?= $share($n, $s['items']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php if ($d['sections']): ?>
                <table>
                    <thead><tr><th>Section</th><th class="r"><?= ucfirst($itemMany) ?></th><th class="r">Share</th></tr></thead>
                    <tbody>
                        <?php if (!$s['sections']): ?>
                            <tr><td colspan="3" class="muted">No <?= $itemMany ?> in this period.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($s['sections'] as $name => $n): ?>
                            <tr>
                                <td><?= esc($name) ?></td>
                                <td class="r"><?= $num($n) ?></td>
                                <td class="r muted"><?= $share($n, $s['items']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot><tr><td>Total</td><td class="r"><?= $num($s['items']) ?></td><td class="r"><?= $s['items'] ? '100.0%' : '–' ?></td></tr></tfoot>
                </table>
            <?php endif; ?>
        </div>
    </section>

    <!-- ===== 7. BACKLOG ===== -->
    <section class="long">
        <h2><span class="num">7</span> Unreleased Backlog <small>As of <?= esc(date('F j, Y · g:i A')) ?></small></h2>
        <table>
            <thead><tr><th>Waiting since payment</th><th class="r">Under 24 h</th><th class="r">1–3 days</th><th class="r">Over 3 days</th><th class="r">Total</th><th class="r">Past target</th></tr></thead>
            <tbody>
                <tr>
                    <td><?= ucfirst($caseMany) ?></td>
                    <td class="r"><?= $num($backlog['aging']['< 24 h']) ?></td>
                    <td class="r"><?= $num($backlog['aging']['1–3 days']) ?></td>
                    <td class="r <?= $backlog['aging']['> 3 days'] ? 'neg' : '' ?>"><?= $num($backlog['aging']['> 3 days']) ?></td>
                    <td class="r"><b><?= $num(count($backlog['rows'])) ?></b></td>
                    <td class="r <?= $backlog['overdue'] ? 'neg' : '' ?>"><?= $num($backlog['overdue']) ?></td>
                </tr>
            </tbody>
        </table>

        <?php if ($oldest): ?>
            <table style="margin-top:10px">
                <thead><tr><th>Reference</th><th>Patient</th><th><?= ucfirst($itemMany) ?></th><th>Priority</th><th>Status</th><th class="r">Waiting</th></tr></thead>
                <tbody>
                    <?php foreach ($oldest as $r): ?>
                        <tr>
                            <td class="mono"><?= esc($r['reference_number']) ?></td>
                            <td><?= esc($r['patient_name']) ?></td>
                            <td><?= esc(implode(', ', $r['items'])) ?></td>
                            <td><?= $priorityLabels[$r['priority_key']] ?? ucfirst($r['priority_key']) ?></td>
                            <td><?= esc($d['status'][$r['status']] ?? ucfirst($r['status'])) ?></td>
                            <td class="r <?= $r['overdue'] ? 'neg' : '' ?>"><?= $dur($r['age_hours']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p class="note">
                All unreleased <?= $caseMany ?>, longest-waiting first.
                Red marks <?= $caseMany ?> waiting longer than their priority's target. The backlog is not limited to the reporting period.
            </p>
        <?php else: ?>
            <p class="note">No unreleased <?= $caseMany ?>.</p>
        <?php endif; ?>
    </section>

    <!-- ===== 8. NOTES ===== -->
    <section>
        <h2><span class="num">8</span> Notes to the Report</h2>
        <ol class="notes">
            <li>A <?= $caseOne ?> counts as received in the period of its request date, once paid; unpaid requests are excluded.</li>
            <li>Turnaround is measured from first payment (when the <?= $caseOne ?> reaches <?= esc($d['name']) ?>) to release. Targets: STAT <?= $targets['stat'] ?> h, urgent <?= $targets['urgent'] ?> h, routine <?= $targets['routine'] ?> h.</li>
            <li>The comparative period is the <?= $days ?>-day period immediately before the reporting period (<?= esc($prevLabel) ?>).</li>
            <li><?= $staff !== '' ? 'Figures are limited to ' . $caseMany . ' handled by ' . esc($staff) . '; the backlog also includes unassigned ' . $caseMany . '.' : 'Figures cover all ' . esc($staffMany) . '.' ?> Collections are department-wide.</li>
        </ol>
    </section>

<?php
    $paper['cert'] = 'I hereby certify that the foregoing is a true and correct summary of the work performed by the '
        . esc($d['unitName']) . ' of ' . esc($clinic['name']) . ' for the period ' . esc($periodLabel)
        . ', as recorded in the clinic information system.';
    $paper['signs'] = [];
    foreach ($d['signs'] as $i => [$role, $title]) {
        $paper['signs'][] = [$i === 0 ? $preparedBy : '', $role, $title];
    }
    include __DIR__ . '/_paper_signoff.php';
?>

    <!-- ===== ANNEX A ===== -->
    <section class="annex long <?= $annex ? '' : 'is-off' ?>" id="annex">
        <h2><span class="num">A</span> Annex A · <?= ucfirst($caseOne) ?> Register <small><?= count($studies) ?> <?= $cases(count($studies)) ?> · <?= esc($periodLabel) ?></small></h2>
        <div class="scroll">
        <table>
            <thead>
                <tr><th>Date</th><th>Reference</th><th>Patient</th><th><?= ucfirst($itemMany) ?></th><th>Priority</th><th>Status</th><th><?= esc($staffTitle) ?></th><th class="r">TAT</th></tr>
            </thead>
            <tbody>
                <?php if (!$studies): ?>
                    <tr><td colspan="8" class="muted">No <?= $caseMany ?> in this period.</td></tr>
                <?php endif; ?>
                <?php foreach (array_reverse($studies) as $r): ?>
                    <tr>
                        <td class="nowrap"><?= date('M j', strtotime($r['request_date'])) ?></td>
                        <td class="mono"><?= esc($r['reference_number']) ?></td>
                        <td><?= esc($r['patient_name']) ?></td>
                        <td><?= esc(implode(', ', $r['items'])) ?></td>
                        <td><?= $priorityLabels[$r['priority_key']] ?? ucfirst($r['priority_key']) ?></td>
                        <td class="nowrap"><?= esc($d['status'][$r['status']] ?? ucfirst($r['status'])) ?></td>
                        <td><?= esc($r['staff'] ?: '—') ?></td>
                        <td class="r <?= $r['within_target'] === false ? 'neg' : '' ?>"><?= $dur($r['tat_hours']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <p class="note">TAT in red is over its priority's target.</p>
    </section>

<?php include __DIR__ . '/_paper_end.php'; ?>
