<?= $this->extend('Layouts/ReceptionistLayout') ?>

<?= $this->section('pageTitle') ?>Reports<?= $this->endSection() ?>

<?= $this->section('receptionistContent') ?>

<?php
    $s        = $summary;
    $p        = $prev;
    $peso     = static fn($v) => '₱' . number_format((float) $v, 2);
    $singleDay = $from === $to;

    $periodLabel = $singleDay
        ? date('l, F j, Y', strtotime($from))
        : date('M j, Y', strtotime($from)) . ' – ' . date('M j, Y', strtotime($to));
    $prevLabel = $prevFrom === $prevTo
        ? date('M j', strtotime($prevFrom))
        : date('M j', strtotime($prevFrom)) . ' – ' . date('M j', strtotime($prevTo));

    // Percentage change vs the previous window; null when there is no base.
    $delta = static function (float $now, float $before): ?float {
        if ($before == 0.0) return null;
        return ($now - $before) / $before * 100;
    };

    $presets = [
        'today'      => 'Today',
        'yesterday'  => 'Yesterday',
        'week'       => 'This week',
        'month'      => 'This month',
        'last_month' => 'Last month',
    ];

    $methodLabels = [
        'cash' => 'Cash', 'card' => 'Card', 'gcash' => 'GCash',
        'paymaya' => 'Maya', 'insurance' => 'Insurance / HMO', 'other' => 'Other',
    ];
    $typeLabels = ['lab' => 'Laboratory', 'xray' => 'X-Ray / Radiology', 'other' => 'Other'];

    $query = static function (array $extra = []) use ($range, $from, $to, $cashier): string {
        return http_build_query(array_filter([
            'range' => $range, 'from' => $from, 'to' => $to, 'cashier' => $cashier,
        ] + $extra, fn($v) => $v !== ''));
    };

    $appt      = $activity['appointments'];
    $apptTotal = array_sum($appt);
    $apptRows  = [
        'pending'   => ['Pending',   'amber'],
        'approved'  => ['Approved',  'blue'],
        'completed' => ['Completed', 'green'],
        'late'      => ['Late',      'red'],
        'cancelled' => ['Cancelled', 'slate'],
    ];

    $reqTotals = [];
    foreach (['lab', 'xray'] as $t) {
        $r = $activity['requests'][$t] ?? [];
        $reqTotals[$t] = [
            'total'    => array_sum($r),
            'released' => (int) ($r['released'] ?? 0),
            'open'     => (int) ($r['pending'] ?? 0) + (int) ($r['in_progress'] ?? 0) + (int) ($r['draft'] ?? 0),
        ];
    }

    $refunds = array_values(array_filter($payments, fn($row) => ($row['payment_status'] ?? '') === 'refunded'));
    $topServices = array_slice($s['services'], 0, 10, true);
    $maxService  = $topServices ? max(array_column($topServices, 'count')) : 0;

    $kpis = [
        [
            'label' => 'Net collections', 'icon' => 'bi-cash-stack', 'tone' => 'green',
            'value' => $peso($s['net']), 'delta' => $delta($s['net'], $p['net']), 'good' => 'up',
            'sub'   => 'Gross ' . $peso($s['gross']),
        ],
        [
            'label' => 'Transactions', 'icon' => 'bi-receipt', 'tone' => 'blue',
            'value' => number_format($s['count']), 'delta' => $delta($s['count'], $p['count']), 'good' => 'up',
            'sub'   => 'Avg ' . $peso($s['avg']) . ' each',
        ],
        [
            'label' => 'Patients billed', 'icon' => 'bi-people', 'tone' => 'violet',
            'value' => number_format($s['patients']), 'delta' => $delta($s['patients'], $p['patients']), 'good' => 'up',
            'sub'   => number_format($activity['patients']['total']) . ' newly registered',
        ],
        [
            'label' => 'Voids / refunds', 'icon' => 'bi-arrow-counterclockwise', 'tone' => 'red',
            'value' => $peso($s['refunds']), 'delta' => $delta($s['refunds'], $p['refunds']), 'good' => 'down',
            'sub'   => $s['refund_count'] . ' transaction' . ($s['refund_count'] === 1 ? '' : 's'),
        ],
        [
            'label' => 'Unpaid balances', 'icon' => 'bi-hourglass-split', 'tone' => 'amber',
            'value' => $peso($unpaid['total']), 'delta' => null, 'good' => 'down',
            'sub'   => count($unpaid['rows']) . ' open invoice' . (count($unpaid['rows']) === 1 ? '' : 's') . ' · as of now',
        ],
    ];
?>

<div class="rp">

    <!-- ===== PRINT LETTERHEAD (print only) ===== -->
    <div class="rp-letterhead">
        <div>
            <div class="rp-lh-name"><?= esc($clinic['name']) ?></div>
            <div class="rp-lh-sub"><?= esc($clinic['tagline']) ?></div>
            <div class="rp-lh-sub"><?= esc($clinic['address']) ?> · <?= esc($clinic['phone']) ?></div>
        </div>
        <div class="rp-lh-right">
            <div class="rp-lh-title">Collection &amp; Front-Desk Report</div>
            <div class="rp-lh-sub"><?= esc($periodLabel) ?></div>
            <div class="rp-lh-sub">Cashier: <?= esc($cashier !== '' ? $cashier : 'All staff') ?></div>
        </div>
    </div>

    <!-- ===== HEADER ===== -->
    <div class="rp-head">
        <div>
            <h1 class="rp-title">Collection &amp; Front-Desk Report</h1>
            <p class="rp-sub">
                <i class="bi bi-calendar3" aria-hidden="true"></i> <?= esc($periodLabel) ?>
                <span class="rp-dot">·</span>
                <?= esc($cashier !== '' ? $cashier : 'All staff') ?>
                <span class="rp-dot">·</span>
                compared with <?= esc($prevLabel) ?>
            </p>
        </div>
        <div class="rp-head-actions">
            <a class="rp-btn" href="<?= base_url('receptionist/reports') . '?' . $query(['export' => 'csv']) ?>">
                <i class="bi bi-filetype-csv" aria-hidden="true"></i> Export CSV
            </a>
            <button type="button" class="rp-btn" onclick="window.print()">
                <i class="bi bi-printer" aria-hidden="true"></i> Print page
            </button>
            <a class="rp-btn rp-btn--primary" href="<?= base_url('receptionist/reports/revenue') . '?' . $query() ?>" target="_blank" rel="noopener">
                <i class="bi bi-file-earmark-text" aria-hidden="true"></i> Revenue report
            </a>
        </div>
    </div>

    <!-- ===== FILTERS ===== -->
    <form class="rp-filters" method="get" action="<?= base_url('receptionist/reports') ?>" id="rpFilters">
        <div class="rp-presets" role="group" aria-label="Report period">
            <?php foreach ($presets as $key => $label): ?>
                <button type="submit" name="range" value="<?= $key ?>"
                        class="rp-preset<?= $range === $key ? ' is-active' : '' ?>"
                        <?= $range === $key ? 'aria-pressed="true"' : '' ?>><?= $label ?></button>
            <?php endforeach; ?>
        </div>
        <div class="rp-filter-fields">
            <label class="rp-field">
                <span>From</span>
                <input type="date" name="from" value="<?= esc($from) ?>" max="<?= date('Y-m-d') ?>">
            </label>
            <label class="rp-field">
                <span>To</span>
                <input type="date" name="to" value="<?= esc($to) ?>" max="<?= date('Y-m-d') ?>">
            </label>
            <label class="rp-field">
                <span>Cashier</span>
                <select name="cashier">
                    <option value="">All staff</option>
                    <?php foreach ($cashiers as $c): ?>
                        <option value="<?= esc($c) ?>" <?= $c === $cashier ? 'selected' : '' ?>><?= esc($c) ?></option>
                    <?php endforeach; ?>
                    <?php if ($preparedBy !== '' && !in_array($preparedBy, $cashiers, true)): ?>
                        <option value="<?= esc($preparedBy) ?>" <?= $preparedBy === $cashier ? 'selected' : '' ?>><?= esc($preparedBy) ?> (me)</option>
                    <?php endif; ?>
                </select>
            </label>
            <button type="submit" name="range" value="custom" class="rp-btn rp-btn--primary">
                <i class="bi bi-funnel" aria-hidden="true"></i> Apply
            </button>
        </div>
    </form>

    <!-- ===== KPIs ===== -->
    <div class="rp-kpis">
        <?php foreach ($kpis as $k): ?>
            <div class="rp-kpi">
                <span class="rp-kpi-label"><?= $k['label'] ?></span>
                <div class="rp-kpi-value"><?= $k['value'] ?></div>
                <div class="rp-kpi-foot">
                    <?php if ($k['delta'] !== null):
                        $up   = $k['delta'] >= 0;
                        $good = ($k['good'] === 'up') === $up;
                    ?>
                        <span class="rp-delta <?= abs($k['delta']) < 0.05 ? '' : ($good ? 'is-good' : 'is-bad') ?>"
                              title="vs <?= esc($prevLabel) ?>">
                            <?= $up ? '▲' : '▼' ?> <?= number_format(abs($k['delta']), 1) ?>%
                        </span>
                    <?php endif; ?>
                    <span class="rp-kpi-sub"><?= esc($k['sub']) ?></span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- ===== CHART + REMITTANCE ===== -->
    <div class="rp-grid rp-grid--chart">
        <section class="rp-card">
            <header class="rp-card-head">
                <div>
                    <h2><?= $daily['hourly'] ? 'Collections by hour' : 'Daily collections' ?></h2>
                    <small><?= $daily['hourly'] ? 'Net amount received per hour of the day' : 'Net amount received per day' ?></small>
                </div>
            </header>
            <?php if ($s['count'] > 0): ?>
                <div class="rp-chart"><canvas id="rpCollectionsChart" aria-label="Collections chart" role="img"></canvas></div>
            <?php else: ?>
                <div class="rp-empty">
                    <i class="bi bi-bar-chart" aria-hidden="true"></i>
                    <p>No collections in this period</p>
                    <small>Try a wider date range or a different cashier.</small>
                </div>
            <?php endif; ?>
        </section>

        <section class="rp-card rp-remit">
            <header class="rp-card-head">
                <div>
                    <h2>Cash remittance</h2>
                    <small>End-of-shift turnover summary</small>
                </div>
            </header>
            <dl class="rp-ledger">
                <div><dt>Gross receipts</dt><dd><?= $peso($s['gross']) ?></dd></div>
                <div class="is-minus"><dt>Less: voids / refunds</dt><dd>(<?= $peso($s['refunds']) ?>)</dd></div>
                <div class="is-total"><dt>Net collections</dt><dd><?= $peso($s['net']) ?></dd></div>
                <div class="is-sep"><dt>Cash</dt><dd><?= $peso($s['cash']) ?></dd></div>
                <div><dt>Non-cash (card, e-wallet, HMO)</dt><dd><?= $peso($s['noncash']) ?></dd></div>
                <div class="is-muted"><dt>Change given out</dt><dd><?= $peso($s['change']) ?></dd></div>
                <div class="is-drawer"><dt>Expected cash for turnover</dt><dd><?= $peso($s['cash']) ?></dd></div>
            </dl>
            <div class="rp-count-line">
                <span>Actual cash counted</span>
                <input type="number" step="0.01" min="0" inputmode="decimal" id="rpCashCount" placeholder="0.00"
                       data-expected="<?= number_format($s['cash'], 2, '.', '') ?>" aria-label="Actual cash counted">
            </div>
            <div class="rp-variance" id="rpVariance" hidden></div>
        </section>
    </div>

    <!-- ===== BREAKDOWNS ===== -->
    <div class="rp-grid rp-grid--3">
        <?php
            $breakdowns = [
                ['By payment method', 'bi-wallet2',      $s['methods'],  $methodLabels],
                ['By department',     'bi-diagram-3',    $s['types'],    $typeLabels],
                ['By cashier',        'bi-person-badge', $s['cashiers'], []],
            ];
        ?>
        <?php foreach ($breakdowns as [$title, $icon, $rows, $labels]): ?>
            <section class="rp-card">
                <header class="rp-card-head">
                    <div><h2><?= $title ?></h2></div>
                </header>
                <?php if ($rows): ?>
                    <ul class="rp-bars">
                        <?php foreach ($rows as $key => $row):
                            $share = $s['net'] > 0 ? $row['amount'] / $s['net'] * 100 : 0;
                        ?>
                            <li>
                                <div class="rp-bar-row">
                                    <span class="rp-bar-name"><?= esc($labels[$key] ?? $key) ?></span>
                                    <span class="rp-bar-amt"><?= $peso($row['amount']) ?></span>
                                </div>
                                <div class="rp-bar-track"><span style="width: <?= round($share, 1) ?>%"></span></div>
                                <div class="rp-bar-meta"><?= $row['count'] ?> txn · <?= number_format($share, 1) ?>%</div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="rp-none">No payments recorded.</p>
                <?php endif; ?>
            </section>
        <?php endforeach; ?>
    </div>

    <!-- ===== FRONT-DESK ACTIVITY ===== -->
    <section class="rp-card">
        <header class="rp-card-head">
            <div>
                <h2>Front-desk activity</h2>
                <small>Appointments scheduled, patients registered and requests created in this period</small>
            </div>
        </header>
        <div class="rp-activity">
            <div class="rp-act-block">
                <div class="rp-act-head">
                    <span>Appointments</span><strong><?= $apptTotal ?></strong>
                </div>
                <div class="rp-stack" aria-hidden="true">
                    <?php foreach ($apptRows as $key => [$label, $tone]):
                        if (empty($appt[$key]) || $apptTotal === 0) continue; ?>
                        <span class="tone-<?= $tone ?>" style="flex: <?= $appt[$key] ?>"></span>
                    <?php endforeach; ?>
                </div>
                <ul class="rp-legend">
                    <?php foreach ($apptRows as $key => [$label, $tone]): ?>
                        <li><i class="tone-<?= $tone ?>"></i><?= $label ?><b><?= (int) ($appt[$key] ?? 0) ?></b></li>
                    <?php endforeach; ?>
                </ul>
                <?php $noShow = (int) ($appt['cancelled'] ?? 0) + (int) ($appt['late'] ?? 0); ?>
                <p class="rp-act-note">
                    <?= $apptTotal > 0 ? number_format($noShow / $apptTotal * 100, 1) . '% cancelled or late' : 'No appointments scheduled' ?>
                </p>
            </div>

            <div class="rp-act-block">
                <div class="rp-act-head">
                    <span>New patients</span><strong><?= $activity['patients']['total'] ?></strong>
                </div>
                <?php $pt = max(1, $activity['patients']['total']); ?>
                <div class="rp-stack" aria-hidden="true">
                    <?php if ($activity['patients']['walkin']): ?><span class="tone-gold" style="flex: <?= $activity['patients']['walkin'] ?>"></span><?php endif; ?>
                    <?php if ($activity['patients']['online']): ?><span class="tone-blue" style="flex: <?= $activity['patients']['online'] ?>"></span><?php endif; ?>
                </div>
                <ul class="rp-legend">
                    <li><i class="tone-gold"></i>Walk-in<b><?= $activity['patients']['walkin'] ?></b></li>
                    <li><i class="tone-blue"></i>Online<b><?= $activity['patients']['online'] ?></b></li>
                </ul>
                <p class="rp-act-note">
                    <?= $activity['patients']['total'] > 0 ? number_format($activity['patients']['walkin'] / $pt * 100, 0) . '% registered at the counter' : 'No new registrations' ?>
                </p>
            </div>

            <?php foreach (['lab' => 'Laboratory requests', 'xray' => 'X-Ray requests'] as $t => $label):
                $r = $reqTotals[$t]; ?>
                <div class="rp-act-block">
                    <div class="rp-act-head">
                        <span><?= $label ?></span><strong><?= $r['total'] ?></strong>
                    </div>
                    <div class="rp-stack" aria-hidden="true">
                        <?php if ($r['released']): ?><span class="tone-teal" style="flex: <?= $r['released'] ?>"></span><?php endif; ?>
                        <?php if ($r['total'] - $r['released'] - $r['open'] > 0): ?><span class="tone-green" style="flex: <?= $r['total'] - $r['released'] - $r['open'] ?>"></span><?php endif; ?>
                        <?php if ($r['open']): ?><span class="tone-amber" style="flex: <?= $r['open'] ?>"></span><?php endif; ?>
                    </div>
                    <ul class="rp-legend">
                        <li><i class="tone-teal"></i>Released<b><?= $r['released'] ?></b></li>
                        <li><i class="tone-green"></i>Completed / other<b><?= max(0, $r['total'] - $r['released'] - $r['open']) ?></b></li>
                        <li><i class="tone-amber"></i>In queue<b><?= $r['open'] ?></b></li>
                    </ul>
                    <p class="rp-act-note">
                        <?= $r['total'] > 0 ? number_format($r['released'] / $r['total'] * 100, 0) . '% results released' : 'No requests' ?>
                    </p>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- ===== SERVICES + UNPAID ===== -->
    <div class="rp-grid rp-grid--2">
        <section class="rp-card">
            <header class="rp-card-head">
                <div>
                    <h2>Top services</h2>
                    <small>Most-availed services · billed charges (not amount received)</small>
                </div>
            </header>
            <?php if ($topServices): ?>
                <table class="rp-table rp-table--compact">
                    <thead><tr><th>Service</th><th class="num">Qty</th><th class="num">Billed</th></tr></thead>
                    <tbody>
                        <?php foreach ($topServices as $name => $svc): ?>
                            <tr>
                                <td>
                                    <span class="rp-svc-name"><?= esc($name) ?></span>
                                    <span class="rp-svc-bar"><span style="width: <?= round($svc['count'] / $maxService * 100) ?>%"></span></span>
                                </td>
                                <td class="num"><?= $svc['count'] ?></td>
                                <td class="num"><?= $peso($svc['charge']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="rp-none">No services billed in this period.</p>
            <?php endif; ?>
        </section>

        <section class="rp-card">
            <header class="rp-card-head">
                <div>
                    <h2>Unpaid bills</h2>
                    <small>Open balances as of <?= date('M j, Y g:i A') ?> — not limited to the period</small>
                </div>
                <a class="rp-link" href="<?= base_url('receptionist/billing?status=unpaid') ?>">Billing →</a>
            </header>
            <div class="rp-aging">
                <div><span>0–7 days</span><strong><?= $peso($unpaid['aging']['0-7']) ?></strong></div>
                <div class="is-warn"><span>8–30 days</span><strong><?= $peso($unpaid['aging']['8-30']) ?></strong></div>
                <div class="is-bad"><span>Over 30 days</span><strong><?= $peso($unpaid['aging']['31+']) ?></strong></div>
            </div>
            <?php if ($unpaid['rows']): ?>
                <table class="rp-table rp-table--compact">
                    <thead><tr><th>Invoice</th><th>Patient</th><th class="num">Age</th><th class="num">Balance</th></tr></thead>
                    <tbody>
                        <?php foreach (array_slice($unpaid['rows'], 0, 8) as $inv): ?>
                            <tr>
                                <td><a class="rp-mono" href="<?= base_url('receptionist/billing/view/' . (int) $inv['id']) ?>"><?= esc($inv['invoice_no']) ?></a></td>
                                <td>
                                    <?= esc($inv['patient_name']) ?>
                                    <?php if ($inv['status'] === 'partial'): ?><span class="rp-pill rp-pill--blue">Partial</span><?php endif; ?>
                                </td>
                                <td class="num <?= $inv['age'] > 30 ? 'is-bad' : ($inv['age'] > 7 ? 'is-warn' : '') ?>"><?= $inv['age'] ?>d</td>
                                <td class="num"><strong><?= $peso($inv['balance']) ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if (count($unpaid['rows']) > 8): ?>
                    <p class="rp-none">+ <?= count($unpaid['rows']) - 8 ?> more in Billing</p>
                <?php endif; ?>
            <?php else: ?>
                <div class="rp-empty rp-empty--sm">
                    <i class="bi bi-check2-circle" aria-hidden="true"></i>
                    <p>All bills are settled</p>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <!-- ===== TRANSACTION LOG ===== -->
    <section class="rp-card rp-log">
        <header class="rp-card-head">
            <div>
                <h2>Transaction log</h2>
                <small><?= count($payments) ?> entr<?= count($payments) === 1 ? 'y' : 'ies' ?> · voided entries are struck through and excluded from net</small>
            </div>
            <label class="rp-search">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input type="search" id="rpLogSearch" placeholder="Search patient, reference…" aria-label="Search transactions">
            </label>
        </header>
        <div class="rp-table-wrap">
            <table class="rp-table" id="rpLogTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th><?= $singleDay ? 'Time' : 'Date / time' ?></th>
                        <th>Reference</th>
                        <th>Patient</th>
                        <th>Dept.</th>
                        <th>Method</th>
                        <th class="num">Billed</th>
                        <th class="num">Paid</th>
                        <th>Cashier</th>
                        <th class="rp-noprint"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($payments): ?>
                        <?php foreach (array_reverse($payments) as $i => $row):
                            $void = ($row['payment_status'] ?? '') === 'refunded';
                            $ts   = strtotime((string) $row['payment_date']);
                            $ref  = ($row['reference_number'] ?? '') ?: ($row['reference_no'] ?? '') ?: '—';
                            $type = strtolower((string) ($row['request_type'] ?? ''));
                        ?>
                            <tr class="<?= $void ? 'is-void' : '' ?>">
                                <td class="rp-faint"><?= $i + 1 ?></td>
                                <td class="rp-nowrap"><?= $singleDay ? date('g:i A', $ts) : date('M j · g:i A', $ts) ?></td>
                                <td><span class="rp-mono"><?= esc($ref) ?></span></td>
                                <td>
                                    <span class="rp-strong"><?= esc($row['patient_name'] ?? '—') ?></span>
                                    <?php if (!empty($row['patient_code']) && strtoupper($row['patient_code']) !== 'N/A'): ?>
                                        <small class="rp-faint d-block"><?= esc($row['patient_code']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><span class="rp-pill rp-pill--<?= $type === 'xray' ? 'violet' : 'blue' ?>"><?= $type === 'xray' ? 'X-Ray' : ($type === 'lab' ? 'Lab' : '—') ?></span></td>
                                <td><?= esc($methodLabels[strtolower((string) $row['payment_method'])] ?? ucfirst((string) $row['payment_method'])) ?></td>
                                <td class="num rp-faint"><?= $peso($row['total_amount'] ?? 0) ?></td>
                                <td class="num">
                                    <span class="rp-amt"><?= $peso($row['amount_paid'] ?? 0) ?></span>
                                    <?php if ($void): ?><span class="rp-pill rp-pill--red">Voided</span><?php endif; ?>
                                </td>
                                <td class="rp-nowrap"><?= esc($row['received_by'] ?? '—') ?></td>
                                <td class="rp-noprint">
                                    <a class="rp-icon-btn" href="<?= base_url('receptionist/billing/receipt/' . (int) $row['id']) ?>" target="_blank" title="View receipt" aria-label="View receipt">
                                        <i class="bi bi-receipt" aria-hidden="true"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="10">
                            <div class="rp-empty">
                                <i class="bi bi-journal" aria-hidden="true"></i>
                                <p>No transactions in this period</p>
                            </div>
                        </td></tr>
                    <?php endif; ?>
                </tbody>
                <?php if ($payments): ?>
                    <tfoot>
                        <tr>
                            <td colspan="7">Net total (<?= $s['count'] ?> paid<?= $s['refund_count'] ? ', ' . $s['refund_count'] . ' voided' : '' ?>)</td>
                            <td class="num"><?= $peso($s['net']) ?></td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </section>

    <!-- ===== VOID RECORDS ===== -->
    <?php if ($refunds): ?>
        <section class="rp-card">
            <header class="rp-card-head">
                <div>
                    <h2>Void &amp; refund records</h2>
                    <small>Authorized by an administrator</small>
                </div>
            </header>
            <div class="rp-table-wrap">
                <table class="rp-table rp-table--compact">
                    <thead><tr><th>Reference</th><th>Patient</th><th class="num">Amount</th><th>Reason</th><th>Voided by</th><th>Voided at</th></tr></thead>
                    <tbody>
                        <?php foreach ($refunds as $row): ?>
                            <tr>
                                <td><span class="rp-mono"><?= esc(($row['reference_number'] ?? '') ?: ($row['reference_no'] ?? '') ?: '—') ?></span></td>
                                <td><?= esc($row['patient_name'] ?? '—') ?></td>
                                <td class="num"><?= $peso($row['amount_paid']) ?></td>
                                <td><?= esc($row['void_reason'] ?: '—') ?></td>
                                <td><?= esc($row['voided_by'] ?: '—') ?></td>
                                <td class="rp-nowrap"><?= !empty($row['voided_at']) ? date('M j, Y g:i A', strtotime($row['voided_at'])) : '—' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endif; ?>

    <!-- ===== SIGN-OFF (print only) ===== -->
    <div class="rp-signoff">
        <div><span class="rp-sig-line"><?= esc($preparedBy) ?></span><small>Prepared by (Cashier)</small></div>
        <div><span class="rp-sig-line">&nbsp;</span><small>Checked by (Supervisor)</small></div>
        <div><span class="rp-sig-line">&nbsp;</span><small>Cash received by (Accounting)</small></div>
    </div>
    <p class="rp-generated">Generated <?= date('M j, Y g:i A') ?> by <?= esc($preparedBy) ?> · <?= esc($clinic['name']) ?></p>
</div>

<style>
/* =========================================================
   REPORTS — Receptionist
   Namespaced under .rp — tokens match the Payments page (.pm).
   ========================================================= */
.rp {
    --rp-ink:var(--pm-text,#10202E); --rp-text:var(--pm-text-soft,#3B4C5E); --rp-muted:var(--pm-text-muted,#5D6E80); --rp-faint:var(--pm-text-muted);
    --rp-line:var(--pm-line,#DDE4EC); --rp-line-soft:var(--pm-line);
    --rp-surface:var(--pm-surface); --rp-subtle:var(--pm-subtle);
    --rp-accent:var(--pm-accent); --rp-accent-dark:var(--pm-accent-text); --rp-accent-soft:var(--pm-accent-soft);
    --rp-blue:#10404B; --rp-blue-soft:var(--pm-accent-soft);
    --rp-green:#067647; --rp-green-soft:var(--pm-tint-green);
    --rp-red:#B42318; --rp-red-soft:var(--pm-tint-red);
    --rp-slate:var(--pm-text-soft); --rp-slate-soft:var(--pm-subtle);
    --rp-amber:#93370D; --rp-amber-soft:var(--pm-tint-amber);
    --rp-mono: var(--pm-mono, ui-monospace, monospace);
    color:var(--rp-text); font-size:.875rem;
}
.rp *:focus-visible { outline:2px solid var(--rp-accent); outline-offset:2px; }
.rp-letterhead, .rp-signoff, .rp-generated { display:none; }

/* Header */
.rp-head { display:flex; align-items:flex-end; justify-content:space-between; gap:1rem; flex-wrap:wrap; margin-bottom:1rem; }
.rp-title { margin:0 0 .2rem; font-size:1.25rem; font-weight:650; letter-spacing:-.015em; color:var(--rp-ink); }
.rp-sub { margin:0; font-size:.8125rem; color:var(--rp-muted); }
.rp-sub i { color:var(--rp-faint); margin-right:.15rem; }
.rp-dot { margin:0 .35rem; color:var(--rp-faint); }
.rp-head-actions { display:flex; gap:.5rem; flex-wrap:wrap; }
.rp-btn { display:inline-flex; align-items:center; justify-content:center; gap:.4rem; height:36px; padding:0 .9rem;
    font-size:.8125rem; font-weight:600; line-height:1; color:var(--rp-text); background:var(--rp-surface);
    border:1px solid var(--rp-line); border-radius:6px;
    white-space:nowrap; cursor:pointer; text-decoration:none; transition:background .15s, border-color .15s, color .15s; }
.rp-btn:hover { background:var(--rp-subtle); border-color:var(--pm-line); color:var(--rp-ink); }
.rp-btn--primary { background:var(--rp-accent); border-color:var(--rp-accent); color:#fff; }
.rp-btn--primary:hover { background:var(--rp-accent-dark); border-color:var(--rp-accent-dark); color:#fff; }

/* Filters */
.rp-filters { display:flex; justify-content:space-between; align-items:flex-end; gap:.75rem 1rem; flex-wrap:wrap;
    padding:.75rem .9rem; margin-bottom:1.1rem; background:var(--rp-surface); border:1px solid var(--rp-line); border-radius:8px; }
.rp-presets { display:inline-flex; padding:3px; background:var(--rp-line-soft); border-radius:9px; flex-wrap:wrap; }
.rp-preset { height:30px; padding:0 .8rem; font-size:.78rem; font-weight:600; color:var(--rp-muted);
    background:transparent; border:0; border-radius:7px; cursor:pointer; transition:background .15s, color .15s; }
.rp-preset:hover { color:var(--rp-ink); }
.rp-preset.is-active { background:var(--rp-surface); color:var(--rp-ink); box-shadow:0 1px 2px rgba(15,23,42,.08); }
.rp-filter-fields { display:flex; align-items:flex-end; gap:.6rem; flex-wrap:wrap; }
.rp-field { display:flex; flex-direction:column; gap:.2rem; margin:0; }
.rp-field span { font-size:.68rem; font-weight:600; text-transform:uppercase; letter-spacing:.05em; color:var(--rp-faint); }
.rp-field input, .rp-field select { height:36px; padding:0 .6rem; font-size:.8125rem; color:var(--rp-ink);
    background:var(--rp-surface); border:1px solid var(--rp-line); border-radius:7px; min-width:0; }
.rp-field select { min-width:160px; }
.rp-field input:focus, .rp-field select:focus { outline:none; border-color:var(--rp-accent); box-shadow:0 0 0 3px rgba(23, 85, 99,.18); }

/* KPIs */
.rp-kpis { display:grid; grid-template-columns:repeat(5,minmax(0,1fr)); margin-bottom:1.1rem; overflow:hidden;
    background:var(--rp-surface); border:1px solid var(--rp-line); border-radius:8px; }
.rp-kpi { padding:.85rem 1.1rem; min-width:0; box-shadow:-1px -1px 0 0 var(--rp-line-soft); }
.rp-kpi-label { display:block; margin:0 0 .3rem; font-size:.76rem; font-weight:500; color:var(--rp-muted); }
.rp-kpi-value { font-size:1.35rem; font-weight:600; letter-spacing:-.02em; color:var(--rp-ink);
    font-variant-numeric:tabular-nums; overflow-wrap:anywhere; line-height:1.2; }
.rp-kpi-foot { display:flex; align-items:center; gap:.45rem; margin-top:.35rem; flex-wrap:wrap; }
.rp-kpi-sub { font-size:.72rem; color:var(--rp-muted); }
.rp-delta { font-size:.72rem; font-weight:600; color:var(--rp-muted); font-variant-numeric:tabular-nums; white-space:nowrap; }
.rp-delta.is-good { color:var(--pm-dk-fg-green, #067647); }
.rp-delta.is-bad  { color:var(--pm-dk-fg-red, #B42318); }

/* Cards & grids */
.rp-grid { display:grid; gap:.9rem; margin-bottom:.9rem; }
.rp-grid--chart { grid-template-columns:minmax(0,1.7fr) minmax(0,1fr); }
.rp-grid--3 { grid-template-columns:repeat(3,minmax(0,1fr)); }
.rp-grid--2 { grid-template-columns:repeat(2,minmax(0,1fr)); }
.rp-grid > .rp-card { margin-bottom:0; }
.rp-card { background:var(--rp-surface); border:1px solid var(--rp-line); border-radius:8px; padding:1.1rem 1.2rem;
    margin-bottom:.9rem; min-width:0; }
.rp-card-head { display:flex; justify-content:space-between; align-items:flex-start; gap:.75rem; margin-bottom:.9rem; flex-wrap:wrap; }
.rp-card-head h2 { margin:0; font-size:.95rem; font-weight:600; color:var(--rp-ink); }
.rp-card-head small { display:block; margin-top:.15rem; font-size:.75rem; color:var(--rp-muted); }
.rp-link { font-size:.78rem; font-weight:600; color:var(--rp-accent); text-decoration:none; white-space:nowrap; }
.rp-link:hover { text-decoration:underline; }
.rp-chart { position:relative; height:260px; }
.rp-none { margin:.4rem 0 0; font-size:.8rem; color:var(--rp-faint); }
.rp-empty { text-align:center; padding:2.2rem 1rem; color:var(--rp-muted); }
.rp-empty i { display:block; font-size:2rem; color:var(--rp-faint); margin-bottom:.5rem; }
.rp-empty p { margin:0; font-weight:600; color:var(--rp-text); }
.rp-empty--sm { padding:1.2rem 1rem; }
.rp-empty--sm i { color:#10b981; font-size:1.6rem; }

/* Remittance ledger */
.rp-ledger { margin:0; }
.rp-ledger > div { display:flex; justify-content:space-between; gap:1rem; padding:.45rem 0; border-bottom:1px solid var(--rp-line-soft); }
.rp-ledger dt { font-weight:500; color:var(--rp-text); }
.rp-ledger dd { margin:0; font-weight:600; color:var(--rp-ink); font-variant-numeric:tabular-nums; white-space:nowrap; }
.rp-ledger .is-minus dd { color:var(--rp-red); }
.rp-ledger .is-total { border-bottom:2px solid var(--rp-ink); }
.rp-ledger .is-total dt, .rp-ledger .is-total dd { font-weight:700; color:var(--rp-ink); }
.rp-ledger .is-sep { margin-top:.35rem; }
.rp-ledger .is-muted dt, .rp-ledger .is-muted dd { color:var(--rp-faint); font-weight:500; }
.rp-ledger .is-drawer { margin-top:.5rem; padding:.6rem .75rem; border:1px solid var(--rp-line); border-radius:6px; background:var(--rp-subtle); }
.rp-ledger .is-drawer dt, .rp-ledger .is-drawer dd { color:var(--rp-ink); font-weight:600; }
.rp-count-line { display:flex; justify-content:space-between; align-items:center; gap:1rem; margin-top:.75rem; font-weight:500; }
.rp-count-line input { width:140px; height:34px; padding:0 .6rem; text-align:right; font-variant-numeric:tabular-nums;
    border:1px solid var(--rp-line); border-radius:7px; }
.rp-count-line input:focus { outline:none; border-color:var(--rp-accent); box-shadow:0 0 0 3px rgba(23, 85, 99,.18); }
.rp-variance { margin-top:.5rem; padding:.5rem .75rem; border-radius:6px; font-size:.8rem; font-weight:600; }
.rp-variance.is-ok    { background:var(--rp-green-soft); color:var(--rp-green); }
.rp-variance.is-short { background:var(--rp-red-soft);   color:var(--rp-red); }
.rp-variance.is-over  { background:var(--rp-amber-soft); color:var(--rp-amber); }

/* Breakdown bars */
.rp-bars { list-style:none; margin:0; padding:0; display:flex; flex-direction:column; gap:.8rem; }
.rp-bar-row { display:flex; justify-content:space-between; gap:.5rem; font-size:.8125rem; }
.rp-bar-name { font-weight:600; color:var(--rp-ink); overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.rp-bar-amt { font-weight:600; font-variant-numeric:tabular-nums; white-space:nowrap; }
.rp-bar-track { height:6px; margin:.3rem 0 .2rem; border-radius:3px; background:var(--rp-line-soft); overflow:hidden; }
.rp-bar-track span { display:block; height:100%; border-radius:3px; background:var(--rp-accent); }
.rp-bar-meta { font-size:.7rem; color:var(--rp-faint); }

/* Activity */
.rp-activity { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:1.25rem; }
.rp-act-block { min-width:0; }
.rp-act-head { display:flex; justify-content:space-between; align-items:baseline; margin-bottom:.45rem; }
.rp-act-head span { font-size:.8rem; font-weight:600; color:var(--rp-text); }
.rp-act-head strong { font-size:1.2rem; font-weight:600; color:var(--rp-ink); font-variant-numeric:tabular-nums; }
.rp-stack { display:flex; gap:2px; height:8px; border-radius:4px; overflow:hidden; background:var(--rp-line-soft); }
.rp-legend { list-style:none; margin:.6rem 0 0; padding:0; display:flex; flex-direction:column; gap:.25rem; }
.rp-legend li { display:flex; align-items:center; gap:.45rem; font-size:.78rem; color:var(--rp-muted); }
.rp-legend li i { width:8px; height:8px; border-radius:2px; flex-shrink:0; }
.rp-legend li b { margin-left:auto; font-weight:600; color:var(--rp-ink); font-variant-numeric:tabular-nums; }
.rp-act-note { margin:.55rem 0 0; font-size:.72rem; color:var(--rp-faint); }
.tone-amber { background:#DC6803; }
.tone-blue  { background:#175563; }
.tone-green { background:#17B26A; }
.tone-teal  { background:#009A85; }
.tone-red   { background:#F04438; }
.tone-slate { background:#98A2B3; }
.tone-gold  { background:#D29A1E; }

/* Aging */
.rp-aging { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:.5rem; margin-bottom:.8rem; }
.rp-aging > div { padding:.55rem .7rem; border-radius:8px; background:var(--rp-subtle); border:1px solid var(--rp-line-soft); }
.rp-aging span { display:block; font-size:.7rem; color:var(--rp-muted); }
.rp-aging strong { font-size:.9rem; color:var(--rp-ink); font-variant-numeric:tabular-nums; }
.rp-aging .is-warn strong { color:var(--rp-amber); }
.rp-aging .is-bad strong { color:var(--rp-red); }

/* Tables */
.rp-table-wrap { overflow-x:auto; -webkit-overflow-scrolling:touch; }
.rp-table { width:100%; border-collapse:collapse; font-size:.8125rem; }
.rp-table th { padding:.55rem .6rem; font-size:.68rem; font-weight:600; text-transform:uppercase; letter-spacing:.05em;
    color:var(--rp-faint); text-align:left; border-bottom:1px solid var(--rp-line); white-space:nowrap; background:var(--rp-subtle); }
.rp-table td { padding:.6rem; border-bottom:1px solid var(--rp-line-soft); vertical-align:middle; color:var(--rp-text); }
.rp-table tbody tr:hover td { background:var(--rp-subtle); }
.rp-table .num { text-align:right; font-variant-numeric:tabular-nums; white-space:nowrap; }
.rp-table td.is-warn { color:var(--rp-amber); font-weight:600; }
.rp-table td.is-bad  { color:var(--rp-red); font-weight:600; }
.rp-table--compact td { padding:.45rem .6rem; }
.rp-table tfoot td { padding:.7rem .6rem; font-weight:700; color:var(--rp-ink); border-top:2px solid var(--rp-ink); background:var(--rp-subtle); }
.rp-table tr.is-void .rp-amt { text-decoration:line-through; color:var(--rp-faint); }
.rp-table tr.is-void td { background:var(--rp-subtle); }
.rp-mono { font-family:var(--rp-mono); font-size:.75rem; color:var(--rp-ink); text-decoration:none; white-space:nowrap; }
a.rp-mono { color:var(--rp-accent); }
a.rp-mono:hover { text-decoration:underline; }
.rp-strong { font-weight:600; color:var(--rp-ink); }
.rp-faint { color:var(--rp-faint); }
.rp-nowrap { white-space:nowrap; }
.rp-pill { display:inline-block; margin-left:.3rem; padding:.1rem .45rem; font-size:.68rem; font-weight:600; border-radius:4px; vertical-align:middle; white-space:nowrap; }
td > .rp-pill:first-child { margin-left:0; }
.rp-pill--blue   { background:var(--rp-blue-soft);   color:var(--rp-blue); }
.rp-pill--slate  { background:var(--rp-slate-soft); color:var(--rp-slate); }
.rp-pill--red    { background:var(--rp-red-soft);    color:var(--rp-red); }
.rp-icon-btn { display:inline-grid; place-items:center; width:30px; height:30px; border-radius:6px; color:var(--rp-muted);
    border:1px solid var(--rp-line); background:var(--rp-surface); text-decoration:none; }
.rp-icon-btn:hover { color:var(--rp-accent); border-color:var(--rp-accent); }
.rp-svc-name { display:block; color:var(--rp-ink); font-weight:500; }
.rp-svc-bar { display:block; height:4px; margin-top:.3rem; border-radius:2px; background:var(--rp-line-soft); }
.rp-svc-bar span { display:block; height:100%; border-radius:2px; background:#4C8D9B; }
.rp-search { position:relative; margin:0; }
.rp-search i { position:absolute; left:.65rem; top:50%; transform:translateY(-50%); color:var(--rp-faint); font-size:.8rem; }
.rp-search input { height:34px; width:240px; max-width:100%; padding:0 .6rem 0 1.9rem; font-size:.8125rem;
    border:1px solid var(--rp-line); border-radius:7px; }
.rp-search input:focus { outline:none; border-color:var(--rp-accent); box-shadow:0 0 0 3px rgba(23, 85, 99,.18); }

/* Responsive */
@media (max-width: 1400px) {
    .rp-kpis { grid-template-columns:repeat(3,minmax(0,1fr)); }
}
@media (max-width: 1100px) {
    .rp-grid--chart, .rp-grid--3, .rp-grid--2 { grid-template-columns:1fr; }
    .rp-activity { grid-template-columns:repeat(2,minmax(0,1fr)); }
}
@media (max-width: 768px) {
    .rp-kpis { grid-template-columns:repeat(2,minmax(0,1fr)); }
    .rp-filters { flex-direction:column; align-items:stretch; }
    .rp-filter-fields .rp-field { flex:1 1 140px; }
    .rp-field select { min-width:0; }
    .rp-search, .rp-search input { width:100%; }
    .rp-card-head { flex-direction:column; }
}
@media (max-width: 480px) {
    .rp-kpis, .rp-activity, .rp-aging { grid-template-columns:1fr; }
    .rp-head-actions { width:100%; }
    .rp-head-actions .rp-btn { flex:1; }
}

/* Print — A4 portrait, letterhead + sign-off, no app chrome */
@media print {
    @page { size:A4 portrait; margin:12mm; }
    .admin-sidebar, .admin-header, .sidebar-backdrop,
    .rp-head, .rp-filters, .rp-noprint, .rp-search, .rp-count-line, .rp-variance, .rp-link { display:none !important; }
    .admin-main { margin:0 !important; width:100% !important; background:var(--pm-surface) !important; }
    .admin-content { padding:0 !important; }
    body { background:var(--pm-surface) !important; }
    .rp { font-size:9.5pt; color:#000; }
    .rp-letterhead { display:flex; justify-content:space-between; gap:1rem; padding-bottom:.5rem; margin-bottom:.8rem; border-bottom:2px solid #000; }
    .rp-lh-name { font-size:13pt; font-weight:800; }
    .rp-lh-title { font-size:11pt; font-weight:700; text-align:right; }
    .rp-lh-sub { font-size:8.5pt; color:var(--pm-text-soft); }
    .rp-lh-right .rp-lh-sub { text-align:right; }
    .rp-kpis { grid-template-columns:repeat(5,1fr); gap:.4rem; }
    .rp-grid--chart, .rp-grid--3, .rp-grid--2 { grid-template-columns:1fr 1fr; gap:.5rem; }
    .rp-grid--3 { grid-template-columns:repeat(3,1fr); }
    .rp-activity { grid-template-columns:repeat(4,1fr); }
    .rp-card, .rp-kpi { box-shadow:none; border-color:var(--pm-dk-edge-neutral, #bbb); padding:.55rem .7rem; break-inside:avoid; }
    .rp-log { break-inside:auto; }
    .rp-kpi-value { font-size:11pt; }
    .rp-chart { height:180px; }
    .rp-table th { background:#eee !important; color:#000; }
    .rp-table tr { break-inside:avoid; }
    .rp-table tbody tr[hidden] { display:table-row !important; }
    * { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
    .rp-signoff { display:grid; grid-template-columns:repeat(3,1fr); gap:2rem; margin-top:2.2rem; break-inside:avoid; }
    .rp-sig-line { display:block; padding-top:1.6rem; border-bottom:1px solid #000; text-align:center; font-weight:600; }
    .rp-signoff small { display:block; text-align:center; margin-top:.2rem; color:var(--pm-text-soft); }
    .rp-generated { display:block; margin-top:1rem; font-size:7.5pt; color:var(--pm-text-muted); text-align:center; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Editing a date means "custom range": make Enter in a date field submit as custom.
    var form = document.getElementById('rpFilters');
    if (form) {
        form.querySelectorAll('input[type=date], select').forEach(function (el) {
            el.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') { e.preventDefault(); form.querySelector('button[value=custom]').click(); }
            });
        });
    }

    // Cash count variance against expected cash turnover.
    var count = document.getElementById('rpCashCount');
    var out   = document.getElementById('rpVariance');
    if (count && out) {
        var peso = function (v) { return '₱' + Math.abs(v).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); };
        count.addEventListener('input', function () {
            if (count.value === '') { out.hidden = true; return; }
            var diff = Math.round((parseFloat(count.value) - parseFloat(count.dataset.expected)) * 100) / 100;
            out.hidden = false;
            out.className = 'rp-variance ' + (diff === 0 ? 'is-ok' : diff < 0 ? 'is-short' : 'is-over');
            out.textContent = diff === 0 ? 'Balanced — drawer matches expected cash.'
                : (diff < 0 ? 'Short by ' : 'Over by ') + peso(diff);
        });
    }

    // Live filter for the transaction log.
    var search = document.getElementById('rpLogSearch');
    var table  = document.getElementById('rpLogTable');
    if (search && table) {
        search.addEventListener('input', function () {
            var q = search.value.trim().toLowerCase();
            table.querySelectorAll('tbody tr').forEach(function (tr) {
                tr.hidden = q !== '' && tr.textContent.toLowerCase().indexOf(q) === -1;
            });
        });
    }

    // Collections chart (Chart.js is loaded by the layout).
    var canvas = document.getElementById('rpCollectionsChart');
    if (canvas && window.Chart) {
        var buckets = <?= json_encode($daily['buckets'], JSON_HEX_TAG) ?>;
        new Chart(canvas, {
            type: 'bar',
            data: {
                labels: buckets.map(function (b) { return b.label; }),
                datasets: [{
                    label: 'Net collected',
                    data: buckets.map(function (b) { return b.amount; }),
                    backgroundColor: '#175563',
                    hoverBackgroundColor: '#10404B',
                    borderRadius: { topLeft: 4, topRight: 4 },
                    borderSkipped: 'bottom',
                    maxBarThickness: 36
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: window.matchMedia('print').matches ? false : undefined,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#FFFFFF',
                        titleColor: '#10202E',
                        bodyColor: '#5D6E80',
                        borderColor: '#DDE4EC',
                        borderWidth: 1,
                        padding: 10,
                        cornerRadius: 6,
                        displayColors: false,
                        callbacks: {
                            label: function (ctx) {
                                var b = buckets[ctx.dataIndex];
                                return '₱' + b.amount.toLocaleString('en-PH', { minimumFractionDigits: 2 }) + ' · ' + b.count + ' txn';
                            }
                        }
                    }
                },
                scales: {
                    x: { grid: { display: false }, ticks: { color: '#64748b', font: { size: 11 }, maxRotation: 0, autoSkip: true } },
                    y: {
                        beginAtZero: true,
                        grid: { color: '#f1f5f9' },
                        border: { display: false },
                        ticks: {
                            color: '#94a3b8', font: { size: 11 },
                            callback: function (v) { return '₱' + (v >= 1000 ? (v / 1000) + 'k' : v); }
                        }
                    }
                }
            }
        });
        // Resize the canvas to the print layout before printing.
        window.addEventListener('beforeprint', function () { var c = Chart.getChart(canvas); if (c) c.resize(); });
    }
});
</script>

<?= $this->endSection() ?>
