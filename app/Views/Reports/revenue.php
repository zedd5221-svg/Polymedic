<?php
    /*
     * Revenue Report — printable A4 business report (receptionist + admin).
     * Data: BuildsRevenueReport::revenueReportData().
     */
    $s   = $summary;
    $p   = $prev;
    $dep = $departments;

    $scopeLabel  = $cashier !== '' ? $cashier : 'All cashiers';

    $paper = [
        'kind'       => 'Financial report',
        'title'      => 'Revenue Report',
        'no'         => $reportNo,
        'meta'       => [
            'Reporting period'   => $periodLabel,
            'Comparative period' => $prevLabel,
            'Coverage'           => $scopeLabel,
            'Date prepared'      => date('F j, Y · g:i A'),
        ],
        'conf'       => 'Confidential · For internal use only · Amounts in Philippine Peso (PHP)',
        'backUrl'    => $backUrl,
        'annex'      => $annex,
        'annexLabel' => 'Include transaction register (Annex A)',
    ];
    include __DIR__ . '/_paper_top.php';

    $methodLabels = [
        'cash' => 'Cash', 'card' => 'Credit / debit card', 'gcash' => 'GCash',
        'paymaya' => 'Maya', 'insurance' => 'Insurance / HMO', 'other' => 'Other',
    ];
    $typeLabels = ['lab' => 'Laboratory services', 'xray' => 'X-Ray / radiology services', 'other' => 'Other services'];

    // "Other" only appears when some column actually has it.
    $deptRows = ['lab', 'xray'];
    if ($dep['current']['other']['gross'] || $dep['prev']['other']['gross'] || $dep['ytd']['other']['gross']) {
        $deptRows[] = 'other';
    }

    $cur = $dep['current']['total'];
    $old = $dep['prev']['total'];
    $ytd = $dep['ytd']['total'];
    $avg = static fn(array $t) => $t['count'] > 0 ? $t['net'] / $t['count'] : 0.0;

    // ---- Executive summary, written from the numbers ----
    $direction = static function (float $now, float $before): string {
        if ($before == 0.0) return $now > 0 ? 'with no collections in the comparative period' : 'unchanged from the comparative period';
        $d = ($now - $before) / $before * 100;
        if (abs($d) < 0.05) return 'unchanged from the comparative period';
        return ($d > 0 ? 'up ' : 'down ') . number_format(abs($d), 1) . '% from ₱' . number_format($before, 2) . ' in the comparative period';
    };
    $leadDept = $dep['current']['lab']['net'] >= $dep['current']['xray']['net'] ? 'lab' : 'xray';
    $summaryText = [];
    if ($cur['count'] === 0 && $cur['refunds'] == 0.0) {
        $summaryText[] = 'No payments were recorded for ' . $periodLabel . ' (' . $scopeLabel . ').';
    } else {
        $summaryText[] = sprintf(
            'Net revenue for the period was %s from %s transaction%s with %s patient%s, %s.',
            $peso($cur['net']), number_format($cur['count']), $cur['count'] === 1 ? '' : 's',
            number_format($s['patients']), $s['patients'] === 1 ? '' : 's', $direction($cur['net'], $old['net'])
        );
        if ($cur['net'] > 0) {
            $summaryText[] = sprintf(
                '%s contributed %s of net revenue, and cash accounted for %s of collections.',
                ucfirst($typeLabels[$leadDept]), $share($dep['current'][$leadDept]['net'], $cur['net']), $share($s['cash'], $s['net'])
            );
        }
        if ($cur['refunds'] > 0) {
            $summaryText[] = sprintf(
                'Voids and refunds totalled %s across %d transaction%s (%s of gross receipts).',
                $peso($cur['refunds']), $s['refund_count'], $s['refund_count'] === 1 ? '' : 's', $share($cur['refunds'], $cur['gross'])
            );
        }
    }
    if ($unpaid['total'] > 0) {
        $summaryText[] = sprintf(
            'Outstanding receivables stood at %s across %d open invoice%s%s.',
            $peso($unpaid['total']), count($unpaid['rows']), count($unpaid['rows']) === 1 ? '' : 's',
            $unpaid['aging']['31+'] > 0 ? ', of which ' . $peso($unpaid['aging']['31+']) . ' is more than 30 days old' : ''
        );
    }

    // ---- Schedule ----
    $rows      = $schedule['rows'];
    $unit      = $schedule['unit'];
    $unitName  = ['hour' => 'Hour', 'day' => 'Date', 'month' => 'Month'][$unit];
    $unitWord  = ['hour' => 'hour', 'day' => 'day', 'month' => 'month'][$unit];
    $showOther = (bool) array_filter(array_column($rows, 'other'));
    $chart = [
        'rows' => array_map(static fn($r) => [
            'value' => $r['net'],
            'tick'  => $unit === 'hour' ? date('ga', strtotime($r['label'])) : ($unit === 'day' ? substr($r['label'], 5) : date('M', strtotime('1 ' . $r['label']))),
            'title' => $r['label'] . ': ₱' . number_format($r['net'], 2) . ' · ' . $r['count'] . ' txn',
        ], $rows),
        'short' => static function (float $v): string {
            if ($v >= 1000000) return '₱' . rtrim(rtrim(number_format($v / 1000000, 1), '0'), '.') . 'M';
            if ($v >= 1000)    return '₱' . rtrim(rtrim(number_format($v / 1000, 1), '0'), '.') . 'k';
            return '₱' . number_format($v);
        },
        'aria'    => 'Net revenue per ' . $unitWord,
        'caption' => 'Figure 1. Net revenue per ' . $unitWord . ', ' . $periodLabel . '. Values in the table below.',
    ];

    $topServices  = $s['services'];
    $oldestUnpaid = $unpaid['rows'];   // unpaidInvoices() is oldest first
?>

    <!-- ===== 1. SUMMARY ===== -->
    <section>
        <h2><span class="num">1</span> Executive Summary</h2>
        <p class="lead"><?= esc(implode(' ', $summaryText)) ?></p>

        <dl class="figs">
            <div class="fig"><dt>Net revenue</dt><dd><?= $peso($cur['net']) ?></dd><span class="vs"><?= $pct($cur['net'], $old['net']) ?> vs comparative</span></div>
            <div class="fig"><dt>Transactions</dt><dd><?= number_format($cur['count']) ?></dd><span class="vs"><?= $pct($cur['count'], $old['count']) ?> vs comparative</span></div>
            <div class="fig"><dt>Avg per transaction</dt><dd><?= $peso($avg($cur)) ?></dd><span class="vs"><?= $pct($avg($cur), $avg($old)) ?> vs comparative</span></div>
            <div class="fig"><dt>Patients billed</dt><dd><?= number_format($s['patients']) ?></dd><span class="vs"><?= $pct($s['patients'], $p['patients']) ?> vs comparative</span></div>
        </dl>
    </section>

    <!-- ===== 2. STATEMENT ===== -->
    <section>
        <h2><span class="num">2</span> Statement of Revenue <small>Cash basis, by payment date</small></h2>
        <div class="scroll">
        <table class="stmt">
            <thead>
                <tr>
                    <th>Particulars</th>
                    <th class="r">This period</th>
                    <th class="r">Comparative</th>
                    <th class="r">Variance</th>
                    <th class="r">%</th>
                    <th class="r">Year to date</th>
                </tr>
            </thead>
            <tbody>
                <tr class="group"><td colspan="6">Gross receipts from services</td></tr>
                <?php foreach ($deptRows as $t):
                    $a = $dep['current'][$t]['gross']; $b = $dep['prev'][$t]['gross']; ?>
                    <tr class="indent">
                        <td><?= esc($typeLabels[$t]) ?></td>
                        <td class="r"><?= $amt($a) ?></td>
                        <td class="r"><?= $amt($b) ?></td>
                        <td class="r"><?= $amt($a - $b) ?></td>
                        <td class="r muted"><?= $pct($a, $b) ?></td>
                        <td class="r"><?= $amt($dep['ytd'][$t]['gross']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr class="sub">
                    <td>Total gross receipts</td>
                    <td class="r"><?= $amt($cur['gross']) ?></td>
                    <td class="r"><?= $amt($old['gross']) ?></td>
                    <td class="r"><?= $amt($cur['gross'] - $old['gross']) ?></td>
                    <td class="r muted"><?= $pct($cur['gross'], $old['gross']) ?></td>
                    <td class="r"><?= $amt($ytd['gross']) ?></td>
                </tr>
                <tr>
                    <td>Less: Voids and refunds</td>
                    <td class="r"><?= $amt(-$cur['refunds']) ?></td>
                    <td class="r"><?= $amt(-$old['refunds']) ?></td>
                    <td class="r"><?= $amt(-($cur['refunds'] - $old['refunds'])) ?></td>
                    <td class="r muted"><?= $pct($cur['refunds'], $old['refunds']) ?></td>
                    <td class="r"><?= $amt(-$ytd['refunds']) ?></td>
                </tr>
                <tr class="total">
                    <td>NET REVENUE</td>
                    <td class="r"><?= $amt($cur['net'], true) ?></td>
                    <td class="r"><?= $amt($old['net'], true) ?></td>
                    <td class="r <?= $cur['net'] < $old['net'] ? 'neg' : '' ?>"><?= $amt($cur['net'] - $old['net'], true) ?></td>
                    <td class="r"><?= $pct($cur['net'], $old['net']) ?></td>
                    <td class="r"><?= $amt($ytd['net'], true) ?></td>
                </tr>
                <tr class="group"><td colspan="6">Net revenue by department</td></tr>
                <?php foreach ($deptRows as $t):
                    $a = $dep['current'][$t]['net']; $b = $dep['prev'][$t]['net']; ?>
                    <tr class="indent memo">
                        <td><?= esc($typeLabels[$t]) ?> <span class="muted">· <?= $share($a, $cur['net']) ?></span></td>
                        <td class="r"><?= $amt($a) ?></td>
                        <td class="r"><?= $amt($b) ?></td>
                        <td class="r"><?= $amt($a - $b) ?></td>
                        <td class="r muted"><?= $pct($a, $b) ?></td>
                        <td class="r"><?= $amt($dep['ytd'][$t]['net']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr class="group"><td colspan="6">Operating statistics</td></tr>
                <tr class="indent memo">
                    <td>Paid transactions</td>
                    <td class="r"><?= $num($cur['count']) ?></td>
                    <td class="r"><?= $num($old['count']) ?></td>
                    <td class="r"><?= $num($cur['count'] - $old['count']) ?></td>
                    <td class="r muted"><?= $pct($cur['count'], $old['count']) ?></td>
                    <td class="r"><?= $num($ytd['count']) ?></td>
                </tr>
                <tr class="indent memo">
                    <td>Average revenue per transaction</td>
                    <td class="r"><?= $amt($avg($cur)) ?></td>
                    <td class="r"><?= $amt($avg($old)) ?></td>
                    <td class="r"><?= $amt($avg($cur) - $avg($old)) ?></td>
                    <td class="r muted"><?= $pct($avg($cur), $avg($old)) ?></td>
                    <td class="r"><?= $amt($avg($ytd)) ?></td>
                </tr>
            </tbody>
        </table>
        </div>
    </section>

    <!-- ===== 3. COLLECTIONS ===== -->
    <section>
        <h2><span class="num">3</span> Collections by Mode of Payment</h2>
        <div class="two">
            <table>
                <thead><tr><th>Mode of payment</th><th class="r">Txns</th><th class="r">Amount</th><th class="r">Share</th></tr></thead>
                <tbody>
                    <?php if (!$s['methods']): ?>
                        <tr><td colspan="4" class="muted">No collections in this period.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($s['methods'] as $m => $row): ?>
                        <tr>
                            <td><?= esc($methodLabels[$m] ?? ucfirst($m)) ?></td>
                            <td class="r"><?= number_format($row['count']) ?></td>
                            <td class="r"><?= $amt($row['amount']) ?></td>
                            <td class="r muted"><?= $share($row['amount'], $s['net']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot><tr><td>Total collections</td><td class="r"><?= number_format($s['count']) ?></td><td class="r"><?= $amt($s['net'], true) ?></td><td class="r"><?= $s['net'] > 0 ? '100.0%' : '–' ?></td></tr></tfoot>
            </table>

            <div class="box">
                <h3>Cash turnover &amp; reconciliation</h3>
                <dl>
                    <div><dt>Cash collections</dt><dd><?= $peso($s['cash']) ?></dd></div>
                    <div><dt>Non-cash collections</dt><dd><?= $peso($s['noncash']) ?></dd></div>
                    <div class="t"><dt>Total collections</dt><dd><?= $peso($s['net']) ?></dd></div>
                    <div style="margin-top:6px"><dt>Cash for turnover / deposit</dt><dd><b><?= $peso($s['cash']) ?></b></dd></div>
                    <div><dt>Actual cash counted</dt><span class="blank"></span></div>
                    <div><dt>Over / (short)</dt><span class="blank"></span></div>
                    <div><dt>Deposit slip no.</dt><span class="blank"></span></div>
                </dl>
                <p class="note">Change handed back to patients (<?= $peso($s['change']) ?>) is not part of collections.</p>
            </div>
        </div>
    </section>

    <!-- ===== 4. SCHEDULE ===== -->
    <section class="long">
        <div class="keep">
            <h2><span class="num">4</span> Revenue Schedule <small>Net revenue per <?= $unitWord ?></small></h2>

            <?php include __DIR__ . '/_paper_chart.php'; ?>
        </div>

        <div class="scroll">
        <table class="<?= count($rows) > 14 ? 'dense' : '' ?>">
            <thead>
                <tr>
                    <th><?= $unitName ?></th>
                    <th class="r">Txns</th>
                    <th class="r">Laboratory</th>
                    <th class="r">X-Ray</th>
                    <?php if ($showOther): ?><th class="r">Other</th><?php endif; ?>
                    <th class="r">Voids / refunds</th>
                    <th class="r">Net revenue</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row): $empty = !$row['count'] && !$row['refunds']; ?>
                    <tr class="<?= $empty ? 'muted' : '' ?>">
                        <td><?= esc($row['label']) ?></td>
                        <td class="r"><?= $row['count'] ?: '–' ?></td>
                        <td class="r"><?= $amt($row['lab']) ?></td>
                        <td class="r"><?= $amt($row['xray']) ?></td>
                        <?php if ($showOther): ?><td class="r"><?= $amt($row['other']) ?></td><?php endif; ?>
                        <td class="r"><?= $amt(-$row['refunds']) ?></td>
                        <td class="r"><b><?= $amt($row['net']) ?></b></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td>Total</td>
                    <td class="r"><?= number_format(array_sum(array_column($rows, 'count'))) ?></td>
                    <td class="r"><?= $amt(array_sum(array_column($rows, 'lab'))) ?></td>
                    <td class="r"><?= $amt(array_sum(array_column($rows, 'xray'))) ?></td>
                    <?php if ($showOther): ?><td class="r"><?= $amt(array_sum(array_column($rows, 'other'))) ?></td><?php endif; ?>
                    <td class="r"><?= $amt(-array_sum(array_column($rows, 'refunds'))) ?></td>
                    <td class="r"><?= $amt(array_sum(array_column($rows, 'net')), true) ?></td>
                </tr>
            </tfoot>
        </table>
        </div>
        <p class="note">Voids / refunds are shown for reference; refunded receipts are already excluded from the department columns and net revenue.</p>
    </section>

    <!-- ===== 5. SERVICES + 6. CASHIERS ===== -->
    <div class="two">
        <section class="<?= count($topServices) > 25 ? "long" : "" ?>">
            <h2><span class="num">5</span> Top Services</h2>
            <table>
                <thead><tr><th>#</th><th>Service</th><th class="r">Qty</th><th class="r">Billed</th></tr></thead>
                <tbody>
                    <?php if (!$topServices): ?>
                        <tr><td colspan="4" class="muted">No itemised services.</td></tr>
                    <?php endif; ?>
                    <?php $i = 0; foreach ($topServices as $name => $row): ?>
                        <tr>
                            <td class="muted"><?= ++$i ?></td>
                            <td><?= esc($name) ?> <span class="muted">· <?= $row['type'] === 'xray' ? 'X-Ray' : ($row['type'] === 'lab' ? 'Lab' : 'Other') ?></span></td>
                            <td class="r"><?= number_format($row['count']) ?></td>
                            <td class="r"><?= $amt($row['charge']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p class="note">Ranked by volume. Billed charges are list prices of services on paid receipts, not cash received.</p>
        </section>

        <section>
            <h2><span class="num">6</span> Cashier Accountability</h2>
            <table>
                <thead><tr><th>Received by</th><th class="r">Txns</th><th class="r">Amount</th><th class="r">Share</th></tr></thead>
                <tbody>
                    <?php if (!$s['cashiers']): ?>
                        <tr><td colspan="4" class="muted">No collections in this period.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($s['cashiers'] as $who => $row): ?>
                        <tr>
                            <td><?= esc($who) ?></td>
                            <td class="r"><?= number_format($row['count']) ?></td>
                            <td class="r"><?= $amt($row['amount']) ?></td>
                            <td class="r muted"><?= $share($row['amount'], $s['net']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot><tr><td>Total</td><td class="r"><?= number_format($s['count']) ?></td><td class="r"><?= $amt($s['net'], true) ?></td><td></td></tr></tfoot>
            </table>
        </section>
    </div>

    <!-- ===== 7. RECEIVABLES ===== -->
    <section class="long">
        <h2><span class="num">7</span> Accounts Receivable Aging <small>As of <?= esc(date('F j, Y')) ?></small></h2>
        <table>
            <thead><tr><th>Age of balance</th><th class="r">0–7 days</th><th class="r">8–30 days</th><th class="r">Over 30 days</th><th class="r">Total outstanding</th></tr></thead>
            <tbody>
                <tr>
                    <td>Amount</td>
                    <td class="r"><?= $amt($unpaid['aging']['0-7']) ?></td>
                    <td class="r"><?= $amt($unpaid['aging']['8-30']) ?></td>
                    <td class="r <?= $unpaid['aging']['31+'] > 0 ? 'neg' : '' ?>"><?= $amt($unpaid['aging']['31+']) ?></td>
                    <td class="r"><b><?= $amt($unpaid['total'], true) ?></b></td>
                </tr>
                <tr class="memo">
                    <td>Share</td>
                    <td class="r"><?= $share($unpaid['aging']['0-7'], $unpaid['total']) ?></td>
                    <td class="r"><?= $share($unpaid['aging']['8-30'], $unpaid['total']) ?></td>
                    <td class="r"><?= $share($unpaid['aging']['31+'], $unpaid['total']) ?></td>
                    <td class="r"><?= $unpaid['total'] > 0 ? '100.0%' : '–' ?></td>
                </tr>
            </tbody>
        </table>

        <?php if ($oldestUnpaid): ?>
            <table style="margin-top:10px">
                <thead><tr><th>Invoice no.</th><th>Patient</th><th>Status</th><th class="r">Age</th><th class="r">Balance</th></tr></thead>
                <tbody>
                    <?php foreach ($oldestUnpaid as $inv): ?>
                        <tr>
                            <td><?= esc($inv['invoice_no'] ?? '—') ?></td>
                            <td><?= esc($inv['patient_name'] ?? '—') ?></td>
                            <td><?= esc(ucfirst((string) ($inv['status'] ?? ''))) ?></td>
                            <td class="r <?= $inv['age'] > 30 ? 'neg' : '' ?>"><?= $inv['age'] ?> d</td>
                            <td class="r"><?= $amt($inv['balance']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p class="note">
                All open invoices, oldest first.
                Receivables cover every cashier and are a snapshot at the time of printing.
            </p>
        <?php else: ?>
            <p class="note">No open invoices.</p>
        <?php endif; ?>
    </section>

    <!-- ===== 8. NOTES ===== -->
    <section>
        <h2><span class="num">8</span> Notes to the Report</h2>
        <ol class="notes">
            <li>Revenue is recognised on a cash basis: a receipt counts in the period of its payment date, regardless of service date.</li>
            <li>Gross receipts include receipts later voided or refunded; these are deducted in full as voids and refunds.</li>
            <li>The comparative period is the <?= $days ?>-day period immediately before the reporting period (<?= esc($prevLabel) ?>). Year to date covers <?= esc($ytdLabel) ?>.</li>
            <li><?= $cashier !== '' ? 'Collections are limited to receipts issued by ' . esc($cashier) . '.' : 'Collections cover receipts issued by all cashiers.' ?> Pending and failed payments are excluded.</li>
        </ol>
    </section>

<?php
    $paper['cert'] = 'I hereby certify that the foregoing is a true and correct report of revenue collected by '
        . esc($clinic['name']) . ' for the period ' . esc($periodLabel) . ', as recorded in the clinic information system, '
        . 'and that the cash collections of <b>' . esc($peso($s['cash'])) . '</b> have been turned over in full.';
    $paper['signs'] = [
        [$preparedBy, 'Prepared by', $preparedRole],
        ['', 'Reviewed by', 'Billing Supervisor'],
        ['', 'Approved by', 'Clinic Administrator'],
    ];
    include __DIR__ . '/_paper_signoff.php';
?>

    <!-- ===== ANNEX A ===== -->
    <section class="annex long <?= $annex ? '' : 'is-off' ?>" id="annex">
        <h2><span class="num">A</span> Annex A · Transaction Register <small><?= count($payments) ?> record<?= count($payments) === 1 ? '' : 's' ?> · <?= esc($periodLabel) ?></small></h2>
        <div class="scroll">
        <table>
            <thead>
                <tr><th>Date / time</th><th>Reference</th><th>Patient</th><th>Dept</th><th>Mode</th><th>Received by</th><th class="r">Amount</th></tr>
            </thead>
            <tbody>
                <?php if (!$payments): ?>
                    <tr><td colspan="7" class="muted">No transactions in this period.</td></tr>
                <?php endif; ?>
                <?php foreach (array_reverse($payments) as $row):
                    $refunded = ($row['payment_status'] ?? '') === 'refunded';
                    $ts = strtotime((string) $row['payment_date']); ?>
                    <tr>
                        <td class="nowrap"><?= date($from === $to ? 'g:i A' : 'M j, g:i A', $ts) ?></td>
                        <td class="mono"><?= esc(($row['reference_number'] ?? '') ?: ($row['reference_no'] ?? '—')) ?></td>
                        <td><?= esc($row['patient_name'] ?? '—') ?></td>
                        <td><?= ['lab' => 'Lab', 'xray' => 'X-Ray'][strtolower((string) ($row['request_type'] ?? ''))] ?? 'Other' ?></td>
                        <td><?= esc($methodLabels[strtolower((string) ($row['payment_method'] ?? ''))] ?? ucfirst((string) ($row['payment_method'] ?? ''))) ?></td>
                        <td><?= esc($row['received_by'] ?? '') ?></td>
                        <td class="r <?= $refunded ? 'neg' : '' ?>">
                            <?php if ($refunded): ?><span class="tag">Void</span> <s><?= number_format((float) $row['amount_paid'], 2) ?></s>
                            <?php else: ?><?= number_format((float) $row['amount_paid'], 2) ?><?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr><td colspan="6">Total of valid receipts (voided receipts excluded)</td><td class="r"><?= $amt($s['net'], true) ?></td></tr>
            </tfoot>
        </table>
        </div>
    </section>

<?php include __DIR__ . '/_paper_end.php'; ?>
