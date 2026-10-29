<?= $this->extend('Layouts/RadiologistLayout') ?>

<?= $this->section('pageTitle') ?>Reports<?= $this->endSection() ?>

<?= $this->section('radiologistContent') ?>

<?php
    $s         = $summary;
    $p         = $prev;
    $peso      = static fn($v) => '₱' . number_format((float) $v, 2);
    $singleDay = $from === $to;

    // 0.75 -> "45 min", 5.2 -> "5.2 h", 80 -> "3.3 d"
    $dur = static function (?float $h): string {
        if ($h === null) return '—';
        if ($h < 1)  return max(1, (int) round($h * 60)) . ' min';
        if ($h < 48) return number_format($h, 1) . ' h';
        return number_format($h / 24, 1) . ' d';
    };

    $periodLabel = $singleDay
        ? date('l, F j, Y', strtotime($from))
        : date('M j, Y', strtotime($from)) . ' – ' . date('M j, Y', strtotime($to));
    $prevLabel = $prevFrom === $prevTo
        ? date('M j', strtotime($prevFrom))
        : date('M j', strtotime($prevFrom)) . ' – ' . date('M j', strtotime($prevTo));

    $delta = static function (?float $now, ?float $before): ?float {
        if ($now === null || !$before) return null;
        return ($now - $before) / $before * 100;
    };

    $presets = [
        'today'      => 'Today',
        'yesterday'  => 'Yesterday',
        'week'       => 'This week',
        'month'      => 'This month',
        'last_month' => 'Last month',
    ];

    $statusMeta = [
        'pending'     => ['Awaiting reading', 'amber'],
        'in_progress' => ['Reading',          'blue'],
        'draft'       => ['Draft saved',      'violet'],
        'completed'   => ['Awaiting release', 'teal'],
        'released'    => ['Released',         'green'],
    ];

    $query = static function (array $extra = []) use ($range, $from, $to, $reader): string {
        return http_build_query(array_filter([
            'range' => $range, 'from' => $from, 'to' => $to, 'radiologist' => $reader,
        ] + $extra, fn($v) => $v !== ''));
    };

    $topExams  = array_slice($s['exams'], 0, 8, true);
    $maxExam   = $topExams ? max($topExams) : 0;
    $maxBucket = max(1, max($s['tat_buckets']));

    $kpis = [
        [
            'label' => 'Studies received', 'icon' => 'bi-inboxes', 'tone' => 'blue',
            'value' => number_format($s['received']), 'delta' => $delta($s['received'], $p['received']), 'good' => 'up',
            'sub'   => $s['exposures'] . ' exams · ' . $s['patients'] . ' patients',
        ],
        [
            'label' => 'Reports released', 'icon' => 'bi-file-earmark-check', 'tone' => 'green',
            'value' => number_format($s['released']), 'delta' => $delta($s['released'], $p['released']), 'good' => 'up',
            'sub'   => $s['release_rate'] === null ? 'No studies this period' : number_format($s['release_rate'], 0) . '% of this period\'s studies',
        ],
        [
            'label' => 'Median turnaround', 'icon' => 'bi-stopwatch', 'tone' => 'violet',
            'value' => $dur($s['tat_median']), 'delta' => $delta($s['tat_median'], $p['tat_median']), 'good' => 'down',
            'sub'   => 'Average ' . $dur($s['tat_avg']) . ' · paid → released',
        ],
        [
            'label' => 'Within target', 'icon' => 'bi-bullseye', 'tone' => 'teal',
            'value' => $s['within_target'] === null ? '—' : number_format($s['within_target'], 0) . '%',
            'delta' => null, 'good' => 'up',
            'sub'   => 'Routine ' . $targets['routine'] . ' h · STAT ' . $targets['stat'] . ' h',
        ],
        [
            'label' => 'Backlog now', 'icon' => 'bi-hourglass-split', 'tone' => 'amber',
            'value' => number_format(count($backlog['rows'])), 'delta' => null, 'good' => 'down',
            'sub'   => $backlog['overdue'] . ' past target · as of now',
        ],
        [
            'label' => 'X-ray revenue', 'icon' => 'bi-cash-stack', 'tone' => 'slate',
            'value' => $peso($revenue), 'delta' => $delta($revenue, $prevRevenue), 'good' => 'up',
            'sub'   => 'Department-wide collections',
        ],
    ];
?>

<div class="rp">

    <!-- ===== PRINT LETTERHEAD (print only) ===== -->
    <div class="rp-letterhead">
        <div>
            <div class="rp-lh-name"><?= esc($clinic['name']) ?></div>
            <div class="rp-lh-sub">Department of Radiology</div>
            <div class="rp-lh-sub"><?= esc($clinic['address']) ?> · <?= esc($clinic['phone']) ?></div>
        </div>
        <div class="rp-lh-right">
            <div class="rp-lh-title">Radiology Workload &amp; Turnaround Report</div>
            <div class="rp-lh-sub"><?= esc($periodLabel) ?></div>
            <div class="rp-lh-sub">Radiologist: <?= esc($reader !== '' ? $reader : 'All') ?></div>
        </div>
    </div>

    <!-- ===== HEADER ===== -->
    <div class="rp-head">
        <div>
            <h1 class="rp-title">Radiology Workload &amp; Turnaround Report</h1>
            <p class="rp-sub">
                <i class="bi bi-calendar3" aria-hidden="true"></i> <?= esc($periodLabel) ?>
                <span class="rp-dot">·</span>
                <?= esc($reader !== '' ? $reader : 'All radiologists') ?>
                <span class="rp-dot">·</span>
                compared with <?= esc($prevLabel) ?>
            </p>
        </div>
        <div class="rp-head-actions">
            <a class="rp-btn" href="<?= base_url('radiologist/reports') . '?' . $query(['export' => 'csv']) ?>">
                <i class="bi bi-filetype-csv" aria-hidden="true"></i> Export CSV
            </a>
            <button type="button" class="rp-btn rp-btn--primary" onclick="window.print()">
                <i class="bi bi-printer" aria-hidden="true"></i> Print report
            </button>
        </div>
    </div>

    <!-- ===== FILTERS ===== -->
    <form class="rp-filters" method="get" action="<?= base_url('radiologist/reports') ?>" id="rpFilters">
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
                <input type="date" name="to" value="<?= esc($to) ?>">
            </label>
            <label class="rp-field">
                <span>Radiologist</span>
                <select name="radiologist">
                    <option value="">All radiologists</option>
                    <?php foreach ($readers as $r): ?>
                        <option value="<?= esc($r) ?>" <?= $r === $reader ? 'selected' : '' ?>><?= esc($r) ?><?= $r === $preparedBy ? ' (me)' : '' ?></option>
                    <?php endforeach; ?>
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
            <div class="rp-kpi rp-kpi--<?= $k['tone'] ?>">
                <div class="rp-kpi-top">
                    <span class="rp-kpi-icon"><i class="bi <?= $k['icon'] ?>" aria-hidden="true"></i></span>
                    <span class="rp-kpi-label"><?= $k['label'] ?></span>
                </div>
                <div class="rp-kpi-value"><?= $k['value'] ?></div>
                <div class="rp-kpi-foot">
                    <?php if ($k['delta'] !== null):
                        $up   = $k['delta'] >= 0;
                        $good = ($k['good'] === 'up') === $up;
                    ?>
                        <span class="rp-delta <?= abs($k['delta']) < 0.05 ? '' : ($good ? 'is-good' : 'is-bad') ?>"
                              title="vs <?= esc($prevLabel) ?>">
                            <i class="bi <?= $up ? 'bi-arrow-up-right' : 'bi-arrow-down-right' ?>" aria-hidden="true"></i>
                            <?= number_format(abs($k['delta']), 1) ?>%
                        </span>
                    <?php endif; ?>
                    <span class="rp-kpi-sub"><?= esc($k['sub']) ?></span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- ===== VOLUME CHART + STATUS ===== -->
    <div class="rp-grid rp-grid--chart">
        <section class="rp-card">
            <header class="rp-card-head">
                <div>
                    <h2><?= $daily['hourly'] ? 'Volume by hour' : 'Daily volume' ?></h2>
                    <small>Studies received (by <?= $daily['hourly'] ? 'time paid' : 'exam date' ?>) vs reports released</small>
                </div>
                <ul class="rp-chart-key" aria-hidden="true">
                    <li><i style="background:#93c5fd"></i>Received</li>
                    <li><i style="background:#047857"></i>Released</li>
                </ul>
            </header>
            <?php if ($s['received'] > 0 || $s['released'] > 0): ?>
                <div class="rp-chart"><canvas id="rpVolumeChart" aria-label="Studies received and released" role="img"></canvas></div>
            <?php else: ?>
                <div class="rp-empty">
                    <i class="bi bi-bar-chart" aria-hidden="true"></i>
                    <p>No studies in this period</p>
                    <small>Try a wider date range or another radiologist.</small>
                </div>
            <?php endif; ?>
        </section>

        <section class="rp-card">
            <header class="rp-card-head">
                <div>
                    <h2>Where this period's studies stand</h2>
                    <small><?= $s['received'] ?> stud<?= $s['received'] === 1 ? 'y' : 'ies' ?> with an exam date in the period</small>
                </div>
            </header>
            <div class="rp-stack rp-stack--lg" aria-hidden="true">
                <?php foreach ($statusMeta as $key => [$label, $tone]):
                    if (empty($s['status'][$key])) continue; ?>
                    <span class="tone-<?= $tone ?>" style="flex: <?= $s['status'][$key] ?>"></span>
                <?php endforeach; ?>
            </div>
            <ul class="rp-legend rp-legend--lg">
                <?php foreach ($statusMeta as $key => [$label, $tone]):
                    $n = $s['status'][$key]; ?>
                    <li>
                        <i class="tone-<?= $tone ?>"></i><?= $label ?>
                        <b><?= $n ?></b>
                        <em><?= $s['received'] ? number_format($n / $s['received'] * 100, 0) : 0 ?>%</em>
                    </li>
                <?php endforeach; ?>
            </ul>
            <div class="rp-mini-stats">
                <div><span>STAT / urgent</span><strong><?= $s['stat'] ?></strong></div>
                <div><span>Exams per study</span><strong><?= $s['received'] ? number_format($s['exposures'] / $s['received'], 1) : '—' ?></strong></div>
            </div>
        </section>
    </div>

    <!-- ===== TAT / WORKLOAD / EXAMS ===== -->
    <div class="rp-grid rp-grid--3">
        <section class="rp-card">
            <header class="rp-card-head">
                <div>
                    <h2><i class="bi bi-stopwatch" aria-hidden="true"></i> Turnaround distribution</h2>
                    <small>Reports released in the period, paid → released</small>
                </div>
            </header>
            <?php if ($s['released'] > 0): ?>
                <ul class="rp-bars">
                    <?php foreach ($s['tat_buckets'] as $label => $n): ?>
                        <li class="rp-bar-inline">
                            <span class="rp-bar-name"><?= $label ?></span>
                            <span class="rp-bar-track"><span style="width: <?= round($n / $maxBucket * 100) ?>%"
                                class="<?= in_array($label, ['1–3 days', '> 3 days'], true) ? 'is-slow' : '' ?>"></span></span>
                            <span class="rp-bar-amt"><?= $n ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="rp-none">No reports released in this period.</p>
            <?php endif; ?>
        </section>

        <section class="rp-card">
            <header class="rp-card-head">
                <div>
                    <h2><i class="bi bi-person-badge" aria-hidden="true"></i> Radiologist workload</h2>
                    <small>Reports released in the period</small>
                </div>
            </header>
            <?php if ($s['readers']): ?>
                <table class="rp-table rp-table--compact">
                    <thead><tr><th>Radiologist</th><th class="num">Reports</th><th class="num">Exams</th><th class="num">Avg TAT</th></tr></thead>
                    <tbody>
                        <?php foreach ($s['readers'] as $name => $rd): ?>
                            <tr>
                                <td class="rp-strong"><?= esc($name) ?></td>
                                <td class="num"><?= $rd['count'] ?></td>
                                <td class="num"><?= $rd['exams'] ?></td>
                                <td class="num"><?= $dur($rd['tat_avg']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="rp-none">No reports released in this period.</p>
            <?php endif; ?>
        </section>

        <section class="rp-card">
            <header class="rp-card-head">
                <div>
                    <h2><i class="bi bi-list-check" aria-hidden="true"></i> Top examinations</h2>
                    <small>Most-requested views this period</small>
                </div>
            </header>
            <?php if ($topExams): ?>
                <ul class="rp-bars">
                    <?php foreach ($topExams as $name => $n): ?>
                        <li class="rp-bar-inline">
                            <span class="rp-bar-name" title="<?= esc($name) ?>"><?= esc($name) ?></span>
                            <span class="rp-bar-track"><span style="width: <?= round($n / $maxExam * 100) ?>%"></span></span>
                            <span class="rp-bar-amt"><?= $n ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="rp-none">No examinations in this period.</p>
            <?php endif; ?>
        </section>
    </div>

    <!-- ===== BACKLOG ===== -->
    <section class="rp-card">
        <header class="rp-card-head">
            <div>
                <h2><i class="bi bi-exclamation-diamond" aria-hidden="true"></i> Unreleased backlog</h2>
                <small>Every paid study not yet released, as of <?= date('M j, Y g:i A') ?> — not limited to the period</small>
            </div>
            <a class="rp-link" href="<?= base_url('radiologist/examinations') ?>">Worklist <i class="bi bi-arrow-right"></i></a>
        </header>
        <div class="rp-aging">
            <div><span>Waiting &lt; 24 h</span><strong><?= $backlog['aging']['< 24 h'] ?></strong></div>
            <div class="is-warn"><span>1–3 days</span><strong><?= $backlog['aging']['1–3 days'] ?></strong></div>
            <div class="is-bad"><span>Over 3 days</span><strong><?= $backlog['aging']['> 3 days'] ?></strong></div>
        </div>
        <?php if ($backlog['rows']): ?>
            <div class="rp-table-wrap">
                <table class="rp-table rp-table--compact">
                    <thead>
                        <tr><th>Reference</th><th>Patient</th><th>Examinations</th><th>Priority</th><th>Status</th><th>Radiologist</th><th class="num">Waiting</th><th class="rp-noprint"></th></tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($backlog['rows'], 0, 12) as $r):
                            [$sLabel, $sTone] = $statusMeta[$r['status']] ?? [ucfirst($r['status']), 'blue']; ?>
                            <tr>
                                <td><span class="rp-mono"><?= esc($r['reference_number']) ?></span></td>
                                <td class="rp-strong"><?= esc($r['patient_name']) ?></td>
                                <td class="rp-exams"><?= esc(implode(', ', $r['exams'])) ?></td>
                                <td><span class="rp-pill rp-pill--<?= $r['priority_key'] === 'routine' ? 'slate' : 'red' ?>"><?= $r['priority_key'] === 'routine' ? 'Routine' : strtoupper($r['priority_key']) ?></span></td>
                                <td><span class="rp-pill rp-pill--<?= $sTone ?>"><?= $sLabel ?></span></td>
                                <td class="rp-nowrap"><?= esc($r['radiologist_name'] ?: '—') ?></td>
                                <td class="num <?= $r['overdue'] ? 'is-bad' : '' ?>"><?= $dur($r['age_hours']) ?></td>
                                <td class="rp-noprint">
                                    <a class="rp-icon-btn" href="<?= base_url('radiologist/examination/view/' . (int) $r['id']) ?>" title="Open study" aria-label="Open study">
                                        <i class="bi bi-arrow-up-right-square" aria-hidden="true"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if (count($backlog['rows']) > 12): ?>
                <p class="rp-none">+ <?= count($backlog['rows']) - 12 ?> more in the worklist</p>
            <?php endif; ?>
        <?php else: ?>
            <div class="rp-empty rp-empty--sm">
                <i class="bi bi-check2-circle" aria-hidden="true"></i>
                <p>No backlog — every paid study is released</p>
            </div>
        <?php endif; ?>
    </section>

    <!-- ===== STUDY LOG ===== -->
    <section class="rp-card rp-log">
        <header class="rp-card-head">
            <div>
                <h2><i class="bi bi-journal-medical" aria-hidden="true"></i> Study log</h2>
                <small><?= count($studies) ?> stud<?= count($studies) === 1 ? 'y' : 'ies' ?> with an exam date in the period</small>
            </div>
            <label class="rp-search">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input type="search" id="rpLogSearch" placeholder="Search patient, exam, reference…" aria-label="Search studies">
            </label>
        </header>
        <div class="rp-table-wrap">
            <table class="rp-table" id="rpLogTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Exam date</th>
                        <th>Reference</th>
                        <th>Patient</th>
                        <th>Examinations</th>
                        <th>Status</th>
                        <th>Radiologist</th>
                        <th class="num">Images</th>
                        <th class="num">TAT</th>
                        <th class="rp-noprint"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($studies): ?>
                        <?php foreach (array_reverse($studies) as $i => $r):
                            [$sLabel, $sTone] = $statusMeta[$r['status']] ?? [ucfirst($r['status']), 'blue'];
                            $sex = strtoupper(mb_substr((string) ($r['gender'] ?? ''), 0, 1));
                        ?>
                            <tr>
                                <td class="rp-faint"><?= $i + 1 ?></td>
                                <td class="rp-nowrap"><?= date('M j, Y', strtotime($r['request_date'])) ?></td>
                                <td><span class="rp-mono"><?= esc($r['reference_number']) ?></span></td>
                                <td>
                                    <span class="rp-strong"><?= esc($r['patient_name']) ?></span>
                                    <small class="rp-faint d-block">
                                        <?= esc(trim(($r['age'] ? $r['age'] . ' y' : '') . ($sex ? ' / ' . $sex : ''), ' /')) ?: '—' ?>
                                        <?php if ($r['priority_key'] !== 'routine'): ?><span class="rp-pill rp-pill--red"><?= strtoupper($r['priority_key']) ?></span><?php endif; ?>
                                    </small>
                                </td>
                                <td class="rp-exams"><?= esc(implode(', ', $r['exams'])) ?></td>
                                <td><span class="rp-pill rp-pill--<?= $sTone ?>"><?= $sLabel ?></span></td>
                                <td class="rp-nowrap"><?= esc($r['radiologist_name'] ?: '—') ?></td>
                                <td class="num"><?= $r['image_count'] ?: '<span class="rp-faint">0</span>' ?></td>
                                <td class="num">
                                    <?php if ($r['tat_hours'] !== null): ?>
                                        <span class="<?= $r['within_target'] ? 'rp-ok' : 'rp-late' ?>"><?= $dur($r['tat_hours']) ?></span>
                                    <?php else: ?>
                                        <span class="rp-faint">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="rp-noprint rp-nowrap">
                                    <a class="rp-icon-btn" href="<?= base_url('radiologist/examination/view/' . (int) $r['id']) ?>" title="Open study" aria-label="Open study">
                                        <i class="bi bi-eye" aria-hidden="true"></i>
                                    </a>
                                    <?php if ($r['status'] === 'released'): ?>
                                        <a class="rp-icon-btn" href="<?= base_url('radiologist/examination/print/' . (int) $r['id']) ?>" target="_blank" title="Print result" aria-label="Print result">
                                            <i class="bi bi-printer" aria-hidden="true"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="10">
                            <div class="rp-empty">
                                <i class="bi bi-journal" aria-hidden="true"></i>
                                <p>No studies in this period</p>
                            </div>
                        </td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <p class="rp-footnote">
            TAT is measured from payment (when the study reaches Radiology) to release.
            <span class="rp-ok">Green</span> is within target, <span class="rp-late">red</span> is over —
            routine <?= $targets['routine'] ?> h, urgent <?= $targets['urgent'] ?> h, STAT <?= $targets['stat'] ?> h.
        </p>
    </section>

    <!-- ===== SIGN-OFF (print only) ===== -->
    <div class="rp-signoff">
        <div><span class="rp-sig-line"><?= esc($preparedBy) ?></span><small>Prepared by (Radiologist)</small></div>
        <div><span class="rp-sig-line">&nbsp;</span><small>Reviewed by (Chief Radiologist)</small></div>
        <div><span class="rp-sig-line">&nbsp;</span><small>Noted by (Medical Director)</small></div>
    </div>
    <p class="rp-generated">Generated <?= date('M j, Y g:i A') ?> by <?= esc($preparedBy) ?> · <?= esc($clinic['name']) ?></p>
</div>

<style>
/* =========================================================
   REPORTS — Radiologist
   Namespaced under .rp — same tokens as the Receptionist report.
   ========================================================= */
.rp {
    --rp-ink:#0f172a; --rp-text:#334155; --rp-muted:#64748b; --rp-faint:#94a3b8;
    --rp-line:#e2e8f0; --rp-line-soft:#f1f5f9;
    --rp-surface:#ffffff; --rp-subtle:#f8fafc;
    --rp-accent:#1976d2; --rp-accent-dark:#1565c0;
    --rp-blue:#1d4ed8; --rp-blue-soft:#eaf2fe;
    --rp-green:#047857; --rp-green-soft:#ecfdf5;
    --rp-red:#b91c1c; --rp-red-soft:#fef2f2;
    --rp-violet:#6d28d9; --rp-violet-soft:#f3e8ff;
    --rp-amber:#b45309; --rp-amber-soft:#fff4e5;
    --rp-teal:#0f766e; --rp-teal-soft:#e6f6f4;
    --rp-mono: ui-monospace, SFMono-Regular, "SF Mono", Menlo, Consolas, monospace;
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
    border:1px solid var(--rp-line); border-radius:7px; box-shadow:0 1px 1px rgba(15,23,42,.03);
    white-space:nowrap; cursor:pointer; text-decoration:none; transition:background .15s, border-color .15s, color .15s; }
.rp-btn:hover { background:var(--rp-subtle); border-color:#cbd5e1; color:var(--rp-ink); }
.rp-btn--primary { background:var(--rp-accent); border-color:var(--rp-accent); color:#fff; }
.rp-btn--primary:hover { background:var(--rp-accent-dark); border-color:var(--rp-accent-dark); color:#fff; }

/* Filters */
.rp-filters { display:flex; justify-content:space-between; align-items:flex-end; gap:.75rem 1rem; flex-wrap:wrap;
    padding:.75rem .9rem; margin-bottom:1.1rem; background:var(--rp-surface); border:1px solid var(--rp-line); border-radius:12px; }
.rp-presets { display:inline-flex; padding:3px; background:var(--rp-line-soft); border-radius:9px; flex-wrap:wrap; }
.rp-preset { height:30px; padding:0 .8rem; font-size:.78rem; font-weight:600; color:var(--rp-muted);
    background:transparent; border:0; border-radius:7px; cursor:pointer; transition:background .15s, color .15s; }
.rp-preset:hover { color:var(--rp-ink); }
.rp-preset.is-active { background:var(--rp-surface); color:var(--rp-accent); box-shadow:0 1px 2px rgba(15,23,42,.08); }
.rp-filter-fields { display:flex; align-items:flex-end; gap:.6rem; flex-wrap:wrap; }
.rp-field { display:flex; flex-direction:column; gap:.2rem; margin:0; }
.rp-field span { font-size:.68rem; font-weight:600; text-transform:uppercase; letter-spacing:.05em; color:var(--rp-faint); }
.rp-field input, .rp-field select { height:36px; padding:0 .6rem; font-size:.8125rem; color:var(--rp-ink);
    background:var(--rp-surface); border:1px solid var(--rp-line); border-radius:7px; min-width:0; }
.rp-field select { min-width:170px; }
.rp-field input:focus, .rp-field select:focus { outline:none; border-color:var(--rp-accent); box-shadow:0 0 0 3px rgba(25,118,210,.18); }

/* KPIs */
.rp-kpis { display:grid; grid-template-columns:repeat(6,minmax(0,1fr)); gap:.9rem; margin-bottom:1.1rem; }
.rp-kpi { padding:1rem 1.1rem; background:var(--rp-surface); border:1px solid var(--rp-line); border-radius:12px;
    box-shadow:0 1px 2px rgba(15,23,42,.04); min-width:0; }
.rp-kpi--blue   { --tone:#1976d2; }
.rp-kpi--green  { --tone:#10b981; }
.rp-kpi--violet { --tone:#7c3aed; }
.rp-kpi--teal   { --tone:#0d9488; }
.rp-kpi--slate  { --tone:#475569; }
.rp-kpi--amber  { --tone:#f59e0b; background:linear-gradient(180deg,#fffbeb,var(--rp-surface)); }
.rp-kpi-top { display:flex; align-items:center; gap:.55rem; margin-bottom:.6rem; }
.rp-kpi-icon { display:grid; place-items:center; width:30px; height:30px; border-radius:8px; font-size:.9rem;
    color:var(--tone); background:color-mix(in srgb, var(--tone) 12%, transparent); flex-shrink:0; }
.rp-kpi-label { font-size:.8rem; font-weight:600; color:var(--rp-text); }
.rp-kpi-value { font-size:1.3rem; font-weight:700; letter-spacing:-.02em; color:var(--rp-ink);
    font-variant-numeric:tabular-nums; overflow-wrap:anywhere; line-height:1.2; }
.rp-kpi-foot { display:flex; align-items:center; gap:.45rem; margin-top:.35rem; flex-wrap:wrap; }
.rp-kpi-sub { font-size:.72rem; color:var(--rp-muted); }
.rp-delta { display:inline-flex; align-items:center; gap:.15rem; padding:.05rem .4rem; font-size:.68rem; font-weight:700;
    border-radius:999px; color:var(--rp-muted); background:var(--rp-line-soft); font-variant-numeric:tabular-nums; }
.rp-delta.is-good { color:#15803d; background:#dcfce7; }
.rp-delta.is-bad  { color:#b91c1c; background:#fee2e2; }

/* Cards & grids */
.rp-grid { display:grid; gap:.9rem; margin-bottom:.9rem; }
.rp-grid--chart { grid-template-columns:minmax(0,1.7fr) minmax(0,1fr); }
.rp-grid--3 { grid-template-columns:repeat(3,minmax(0,1fr)); }
.rp-grid > .rp-card { margin-bottom:0; }
.rp-card { background:var(--rp-surface); border:1px solid var(--rp-line); border-radius:12px; padding:1.1rem 1.2rem;
    box-shadow:0 1px 2px rgba(15,23,42,.04); margin-bottom:.9rem; min-width:0; }
.rp-card-head { display:flex; justify-content:space-between; align-items:flex-start; gap:.75rem; margin-bottom:.9rem; flex-wrap:wrap; }
.rp-card-head h2 { margin:0; font-size:.95rem; font-weight:650; color:var(--rp-ink); }
.rp-card-head h2 i { color:var(--rp-accent); margin-right:.3rem; }
.rp-card-head small { display:block; margin-top:.15rem; font-size:.75rem; color:var(--rp-muted); }
.rp-link { font-size:.78rem; font-weight:600; color:var(--rp-accent); text-decoration:none; white-space:nowrap; }
.rp-link:hover { text-decoration:underline; }
.rp-chart { position:relative; height:260px; }
.rp-chart-key { display:flex; gap:.9rem; list-style:none; margin:0; padding:0; font-size:.75rem; color:var(--rp-muted); }
.rp-chart-key li { display:flex; align-items:center; gap:.35rem; }
.rp-chart-key i { width:10px; height:10px; border-radius:3px; }
.rp-none { margin:.4rem 0 0; font-size:.8rem; color:var(--rp-faint); }
.rp-empty { text-align:center; padding:2.2rem 1rem; color:var(--rp-muted); }
.rp-empty i { display:block; font-size:2rem; color:var(--rp-faint); margin-bottom:.5rem; }
.rp-empty p { margin:0; font-weight:600; color:var(--rp-text); }
.rp-empty--sm { padding:1.2rem 1rem; }
.rp-empty--sm i { color:#10b981; font-size:1.6rem; }
.rp-footnote { margin:.8rem 0 0; font-size:.72rem; color:var(--rp-faint); }

/* Status stack + legend */
.rp-stack { display:flex; gap:2px; height:8px; border-radius:99px; overflow:hidden; background:var(--rp-line-soft); }
.rp-stack--lg { height:14px; border-radius:6px; }
.rp-legend { list-style:none; margin:.6rem 0 0; padding:0; display:flex; flex-direction:column; gap:.25rem; }
.rp-legend li { display:flex; align-items:center; gap:.45rem; font-size:.78rem; color:var(--rp-muted); }
.rp-legend li i { width:8px; height:8px; border-radius:2px; flex-shrink:0; }
.rp-legend li b { margin-left:auto; font-weight:600; color:var(--rp-ink); font-variant-numeric:tabular-nums; }
.rp-legend li em { width:3rem; text-align:right; font-style:normal; color:var(--rp-faint); font-variant-numeric:tabular-nums; }
.rp-legend--lg { margin-top:.9rem; gap:.5rem; }
.rp-legend--lg li { font-size:.8125rem; }
.rp-mini-stats { display:grid; grid-template-columns:1fr 1fr; gap:.5rem; margin-top:1rem; padding-top:.9rem; border-top:1px dashed var(--rp-line); }
.rp-mini-stats span { display:block; font-size:.7rem; color:var(--rp-muted); }
.rp-mini-stats strong { font-size:1rem; color:var(--rp-ink); font-variant-numeric:tabular-nums; }
.tone-green  { background:#10b981; }
.tone-blue   { background:#1976d2; }
.tone-violet { background:#7c3aed; }
.tone-amber  { background:#f59e0b; }
.tone-teal   { background:#14b8a6; }

/* Inline bars */
.rp-bars { list-style:none; margin:0; padding:0; display:flex; flex-direction:column; gap:.6rem; }
.rp-bar-inline { display:grid; grid-template-columns:minmax(0,1.2fr) minmax(0,1fr) 2rem; align-items:center; gap:.6rem; font-size:.8125rem; }
.rp-bar-name { font-weight:500; color:var(--rp-ink); overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.rp-bar-amt { font-weight:600; text-align:right; font-variant-numeric:tabular-nums; }
.rp-bar-track { height:8px; border-radius:99px; background:var(--rp-line-soft); overflow:hidden; }
.rp-bar-track span { display:block; height:100%; border-radius:99px; background:var(--rp-accent); }
.rp-bar-track span.is-slow { background:#f59e0b; }

/* Aging */
.rp-aging { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:.5rem; margin-bottom:.8rem; max-width:560px; }
.rp-aging > div { padding:.55rem .7rem; border-radius:8px; background:var(--rp-subtle); border:1px solid var(--rp-line-soft); }
.rp-aging span { display:block; font-size:.7rem; color:var(--rp-muted); }
.rp-aging strong { font-size:.95rem; color:var(--rp-ink); font-variant-numeric:tabular-nums; }
.rp-aging .is-warn { background:var(--rp-amber-soft); border-color:#fed7aa; }
.rp-aging .is-warn strong { color:var(--rp-amber); }
.rp-aging .is-bad  { background:var(--rp-red-soft); border-color:#fecaca; }
.rp-aging .is-bad strong { color:var(--rp-red); }

/* Tables */
.rp-table-wrap { overflow-x:auto; -webkit-overflow-scrolling:touch; }
.rp-table { width:100%; border-collapse:collapse; font-size:.8125rem; }
.rp-table th { padding:.55rem .6rem; font-size:.68rem; font-weight:600; text-transform:uppercase; letter-spacing:.05em;
    color:var(--rp-faint); text-align:left; border-bottom:1px solid var(--rp-line); white-space:nowrap; background:var(--rp-subtle); }
.rp-table td { padding:.6rem; border-bottom:1px solid var(--rp-line-soft); vertical-align:middle; color:var(--rp-text); }
.rp-table tbody tr:hover td { background:var(--rp-subtle); }
.rp-table .num { text-align:right; font-variant-numeric:tabular-nums; white-space:nowrap; }
.rp-table td.is-bad { color:var(--rp-red); font-weight:600; }
.rp-table--compact td { padding:.45rem .6rem; }
.rp-exams { min-width:180px; max-width:320px; font-size:.78rem; }
.rp-mono { font-family:var(--rp-mono); font-size:.75rem; color:var(--rp-ink); white-space:nowrap; }
.rp-strong { font-weight:600; color:var(--rp-ink); }
.rp-faint { color:var(--rp-faint); }
.rp-nowrap { white-space:nowrap; }
.rp-ok   { color:var(--rp-green); font-weight:600; }
.rp-late { color:var(--rp-red); font-weight:600; }
.rp-pill { display:inline-block; padding:.12rem .5rem; font-size:.66rem; font-weight:700; border-radius:999px;
    vertical-align:middle; white-space:nowrap; }
small .rp-pill { margin-left:.3rem; }
.rp-pill--blue   { background:var(--rp-blue-soft);   color:var(--rp-blue); }
.rp-pill--violet { background:var(--rp-violet-soft); color:var(--rp-violet); }
.rp-pill--red    { background:var(--rp-red-soft);    color:var(--rp-red); }
.rp-pill--green  { background:var(--rp-green-soft);  color:var(--rp-green); }
.rp-pill--amber  { background:var(--rp-amber-soft);  color:var(--rp-amber); }
.rp-pill--teal   { background:var(--rp-teal-soft);   color:var(--rp-teal); }
.rp-pill--slate  { background:var(--rp-line-soft);   color:var(--rp-muted); }
.rp-icon-btn { display:inline-grid; place-items:center; width:30px; height:30px; border-radius:7px; color:var(--rp-muted);
    border:1px solid var(--rp-line); background:var(--rp-surface); text-decoration:none; }
.rp-icon-btn:hover { color:var(--rp-accent); border-color:var(--rp-accent); }
.rp-search { position:relative; margin:0; }
.rp-search i { position:absolute; left:.65rem; top:50%; transform:translateY(-50%); color:var(--rp-faint); font-size:.8rem; }
.rp-search input { height:34px; width:260px; max-width:100%; padding:0 .6rem 0 1.9rem; font-size:.8125rem;
    border:1px solid var(--rp-line); border-radius:7px; }
.rp-search input:focus { outline:none; border-color:var(--rp-accent); box-shadow:0 0 0 3px rgba(25,118,210,.18); }

/* Responsive */
@media (max-width: 1500px) {
    .rp-kpis { grid-template-columns:repeat(3,minmax(0,1fr)); }
}
@media (max-width: 1100px) {
    .rp-grid--chart, .rp-grid--3 { grid-template-columns:1fr; }
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
    .rp-kpis, .rp-aging { grid-template-columns:1fr; }
    .rp-head-actions { width:100%; }
    .rp-head-actions .rp-btn { flex:1; }
}

/* Print — A4 portrait, letterhead + sign-off, no app chrome */
@media print {
    @page { size:A4 portrait; margin:12mm; }
    .admin-sidebar, .admin-header, .sidebar-backdrop,
    .rp-head, .rp-filters, .rp-noprint, .rp-search, .rp-link { display:none !important; }
    .admin-main { margin:0 !important; width:100% !important; background:#fff !important; }
    .admin-content { padding:0 !important; }
    body { background:#fff !important; }
    .rp { font-size:9.5pt; color:#000; }
    .rp-letterhead { display:flex; justify-content:space-between; gap:1rem; padding-bottom:.5rem; margin-bottom:.8rem; border-bottom:2px solid #000; }
    .rp-lh-name { font-size:13pt; font-weight:800; }
    .rp-lh-title { font-size:11pt; font-weight:700; text-align:right; }
    .rp-lh-sub { font-size:8.5pt; color:#444; }
    .rp-lh-right .rp-lh-sub { text-align:right; }
    .rp-kpis { grid-template-columns:repeat(3,1fr); gap:.4rem; }
    .rp-grid--chart { grid-template-columns:1.5fr 1fr; gap:.5rem; }
    .rp-grid--3 { grid-template-columns:repeat(3,1fr); gap:.5rem; }
    .rp-card, .rp-kpi { box-shadow:none; border-color:#bbb; padding:.55rem .7rem; break-inside:avoid; }
    .rp-log { break-inside:auto; }
    .rp-kpi-value { font-size:11pt; }
    .rp-chart { height:180px; }
    .rp-table-wrap { overflow:visible; }
    .rp-table { font-size:7.5pt; }
    .rp-table th { background:#eee !important; color:#000; padding:.3rem .3rem; font-size:6.5pt; letter-spacing:0; }
    .rp-table td, .rp-table--compact td { padding:.3rem .3rem; }
    .rp-table .rp-faint.d-block, .rp-mono { font-size:7pt; }
    .rp-exams { min-width:0; font-size:7.5pt; }
    .rp-pill { font-size:6.5pt; padding:.05rem .35rem; }
    .rp-table tr { break-inside:avoid; }
    .rp-table tbody tr[hidden] { display:table-row !important; }
    .rp-exams { max-width:none; }
    * { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
    .rp-signoff { display:grid; grid-template-columns:repeat(3,1fr); gap:2rem; margin-top:2.2rem; break-inside:avoid; }
    .rp-sig-line { display:block; padding-top:1.6rem; border-bottom:1px solid #000; text-align:center; font-weight:600; }
    .rp-signoff small { display:block; text-align:center; margin-top:.2rem; color:#444; }
    .rp-generated { display:block; margin-top:1rem; font-size:7.5pt; color:#666; text-align:center; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Enter in a date field / select submits as a custom range.
    var form = document.getElementById('rpFilters');
    if (form) {
        form.querySelectorAll('input[type=date], select').forEach(function (el) {
            el.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') { e.preventDefault(); form.querySelector('button[value=custom]').click(); }
            });
        });
    }

    // Live filter for the study log.
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

    // Received vs released chart (Chart.js is loaded by the layout).
    var canvas = document.getElementById('rpVolumeChart');
    if (canvas && window.Chart) {
        var buckets = <?= json_encode($daily['buckets'], JSON_HEX_TAG) ?>;
        new Chart(canvas, {
            type: 'bar',
            data: {
                labels: buckets.map(function (b) { return b.label; }),
                datasets: [
                    { label: 'Received', data: buckets.map(function (b) { return b.received; }),
                      backgroundColor: '#93c5fd', borderRadius: 4, maxBarThickness: 22 },
                    { label: 'Released', data: buckets.map(function (b) { return b.released; }),
                      backgroundColor: '#047857', borderRadius: 4, maxBarThickness: 22 }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false }, ticks: { color: '#64748b', font: { size: 11 }, maxRotation: 0, autoSkip: true } },
                    y: {
                        beginAtZero: true,
                        grid: { color: '#f1f5f9' },
                        border: { display: false },
                        ticks: { color: '#94a3b8', font: { size: 11 }, precision: 0 }
                    }
                }
            }
        });
        window.addEventListener('beforeprint', function () { var c = Chart.getChart(canvas); if (c) c.resize(); });
    }
});
</script>

<?= $this->endSection() ?>
