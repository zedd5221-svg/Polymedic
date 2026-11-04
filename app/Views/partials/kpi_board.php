<?php
/* ------------------------------------------------------------------
   Shared KPI board used by every role dashboard. List pages use
   the lighter partials/kpi_strip.

   Headline cards (value, change, 14-day bar chart) plus an optional
   status card. Self-contained: it brings its own tokens and CSS, so
   it renders the same inside any page wrapper.

   Data:
     $cards    list of [
                 key, icon (bi-* class or legacy PNG name), label,
                 value (number), money (bool),
                 delta ('dod' | 'window' | 'sum' | null), goodUp (bool, default true),
                 context, unit, link (string|null)
               ]
     $trends   ['dates' => [...], <key> => [14 daily values], ...]
               Without 'dates' the series are taken to end today.
     $pipeline [
                 title, sub, link, split (bool: statuses are mutually
                 exclusive, so draw the share bar and percentages),
                 stages => list of [key, color, label, hint, count, today, link]
               ]
   ------------------------------------------------------------------ */
$cards    = $cards ?? [];
$trends   = $trends ?? [];
$pipeline = $pipeline ?? null;
$kpiDates = $trends['dates'] ?? [];

if (!$kpiDates) {
    $lengths = array_map('count', array_filter($trends, 'is_array'));
    $days    = $lengths ? max($lengths) : 0;
    for ($i = $days - 1; $i >= 0; $i--) {
        $kpiDates[] = date('Y-m-d', strtotime('-' . $i . ' days'));
    }
}

/* Peso amount split into symbol / whole / cents so the cents can be de-emphasised. */
$moneyHtml = static function (float $amount): string {
    $parts = explode('.', number_format($amount, 2));
    return '<span class="kpi-unit">&#8369;</span>' . esc($parts[0])
         . '<span class="kpi-cents">.' . esc($parts[1] ?? '00') . '</span>';
};

/*
 * Change indicator for a series.
 *  'dod'    : today vs yesterday, as a percentage.
 *  'window' : absolute change across the whole series (cumulative series).
 *  'sum'    : total added across the whole series (daily-count series).
 * Returns ['text', 'dir' => up|down|flat, 'tone' => good|bad|neutral] or null.
 */
$kpiDelta = static function (array $vals, string $mode, bool $goodUp, bool $money = false): ?array {
    $n = count($vals);
    if ($n < 2) { return null; }

    if ($mode === 'sum') {
        $added = array_sum($vals);
        if ($added < 0.005) {
            return ['text' => 'None', 'dir' => 'flat', 'tone' => 'neutral'];
        }
        $text = '+' . ($money ? '₱' . number_format($added, 2) : number_format($added));
        return ['text' => $text, 'dir' => 'up', 'tone' => $goodUp ? 'good' : 'bad'];
    }

    $cur  = (float) $vals[$n - 1];
    $prev = $mode === 'window' ? (float) $vals[0] : (float) $vals[$n - 2];
    $diff = $cur - $prev;

    if (abs($diff) < 0.005) {
        return ['text' => 'No change', 'dir' => 'flat', 'tone' => 'neutral'];
    }
    $dir = $diff > 0 ? 'up' : 'down';
    if ($mode === 'window') {
        $text = ($diff > 0 ? '+' : '−') . ($money ? '₱' . number_format(abs($diff), 2) : number_format(abs($diff)));
    } elseif ($prev == 0.0) {
        $text = 'New';
    } else {
        $pct  = abs($diff) / $prev * 100;
        $text = ($diff > 0 ? '+' : '−') . ($pct >= 10 ? number_format($pct) : number_format($pct, 1)) . '%';
    }
    $tone = ($dir === 'up') === $goodUp ? 'good' : 'bad';
    return ['text' => $text, 'dir' => $dir, 'tone' => $tone];
};

$deltaArrow = [
    'up'   => '<path d="M6 2.5 10 8H2z"/>',
    'down' => '<path d="M6 9.5 2 4h8z"/>',
    'flat' => '<rect x="2" y="5.25" width="8" height="1.5" rx=".75"/>',
];
$dayLabel = static fn($i) => isset($kpiDates[$i]) ? date('M j', strtotime($kpiDates[$i])) : '';

/* Line icons replace the legacy clip-art PNGs; an unknown name falls back to the image. */
$iconMap = [
    'people-blue.png'       => 'bi-people',
    'people-check-blue.png' => 'bi-person-check',
    'document.png'          => 'bi-calendar2-check',
    'file (1).png'          => 'bi-file-earmark-text',
    'money-yellow.png'      => 'bi-cash-stack',
    'clock (4).png'         => 'bi-hourglass-split',
    'time.png'              => 'bi-clock-history',
    'analysis.png'          => 'bi-clipboard2-pulse',
];
?>

<section class="kpi-board<?= $pipeline ? '' : ' kpi-board--cards-only' ?>"
         style="--kpi-cols:<?= max(1, count($cards)) ?>"
         aria-labelledby="kpiHeading">
    <h2 id="kpiHeading" class="visually-hidden">Key metrics</h2>

    <?php foreach ($cards as $card): ?>
        <?php
            $isMoney = !empty($card['money']);
            $vals    = array_map('floatval', $trends[$card['key']] ?? []);
            if (!$vals) { $vals = array_fill(0, 14, 0.0); }
            $nVals = count($vals);
            $peak  = max($vals);
            $delta = !empty($card['delta']) && $nVals >= 2
                ? $kpiDelta($vals, $card['delta'], $card['goodUp'] ?? true, $isMoney)
                : null;
            $fmt = $isMoney
                ? static fn($v) => '₱' . number_format($v, 2)
                : static fn($v) => number_format($v);
            $chartLabel = '14-day trend of ' . $card['unit'] . ': ' . $fmt(end($vals)) . ' today, peak ' . $fmt($peak);
            $tag = !empty($card['link']) ? 'a' : 'div';
        ?>
        <?php /* Headline cards share one accent; per-card rainbow colours carried no meaning. */ ?>
        <<?= $tag ?> class="kpi-card"<?= !empty($card['link']) ? ' href="' . esc($card['link'], 'attr') . '"' : '' ?>>
            <div class="kpi-head">
                <?php $iconClass = strpos($card['icon'], 'bi-') === 0 ? $card['icon'] : ($iconMap[$card['icon']] ?? null); ?>
                <?php if ($iconClass !== null): ?>
                    <i class="bi <?= esc($iconClass, 'attr') ?> kpi-icon" aria-hidden="true"></i>
                <?php else: ?>
                    <img class="kpi-icon" src="<?= esc(base_url('assets/images/' . $card['icon']), 'attr') ?>"
                         alt="" loading="lazy" decoding="async">
                <?php endif; ?>
                <h3 class="kpi-label" title="<?= esc($card['label'], 'attr') ?>"><?= esc($card['label']) ?></h3>
                <?php if (!empty($card['link'])): ?>
                    <svg class="kpi-go" width="16" height="16" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round"
                         stroke-linejoin="round" aria-hidden="true">
                        <path d="M7 17 17 7M8 7h9v9"/>
                    </svg>
                <?php endif; ?>
            </div>

            <div class="kpi-value<?= $isMoney ? ' is-money' : '' ?>">
                <?= $isMoney ? $moneyHtml((float) $card['value']) : esc(number_format((float) $card['value'])) ?>
            </div>

            <div class="kpi-meta">
                <?php if ($delta): ?>
                    <span class="kpi-delta is-<?= esc($delta['tone'], 'attr') ?>">
                        <svg width="10" height="10" viewBox="0 0 12 12" fill="currentColor" aria-hidden="true"><?= $deltaArrow[$delta['dir']] ?></svg>
                        <?= esc($delta['text']) ?>
                    </span>
                <?php endif; ?>
                <span class="kpi-context"><?= esc($card['context']) ?></span>
            </div>

            <div class="kpi-chart" role="img" aria-label="<?= esc($chartLabel, 'attr') ?>">
                <div class="kpi-bars" aria-hidden="true">
                    <?php foreach ($vals as $i => $v): ?>
                        <span class="kpi-bar<?= $i === $nVals - 1 ? ' is-today' : '' ?>"
                              style="height:<?= $peak > 0 ? max(4, round($v / $peak * 100, 1)) : 4 ?>%"
                              title="<?= esc(trim($dayLabel($i) . ': ' . $fmt($v), ': '), 'attr') ?>"></span>
                    <?php endforeach; ?>
                </div>
                <div class="kpi-axis" aria-hidden="true">
                    <span><?= esc($dayLabel(0)) ?></span>
                    <span>Today</span>
                </div>
            </div>
        </<?= $tag ?>>
    <?php endforeach; ?>

    <?php if ($pipeline): ?>
        <?php
            $stages = $pipeline['stages'] ?? [];
            $split  = !empty($pipeline['split']);
            $sum    = array_sum(array_column($stages, 'count'));
        ?>
        <article class="pipeline-card" aria-labelledby="pipelineHeading">
            <header class="pipeline-head">
                <div>
                    <h3 id="pipelineHeading" class="pipeline-title"><?= esc($pipeline['title']) ?></h3>
                    <p class="pipeline-sub"><?= esc($pipeline['sub']) ?></p>
                </div>
                <?php if (!empty($pipeline['link'])): ?>
                    <a class="pipeline-link" href="<?= esc($pipeline['link'], 'attr') ?>">
                        View all
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                    </a>
                <?php endif; ?>
            </header>

            <?php if ($split): ?>
                <?php
                    $barLabel = implode(', ', array_map(
                        static fn($s) => $s['label'] . ' ' . number_format($s['count']),
                        $stages
                    ));
                ?>
                <div class="pipeline-bar" role="img" aria-label="<?= esc('Status split: ' . $barLabel, 'attr') ?>">
                    <?php foreach ($stages as $stage): ?>
                        <?php if ($sum > 0 && $stage['count'] > 0): ?>
                            <span style="flex-grow:<?= (int) $stage['count'] ?>;background:<?= esc($stage['color'], 'attr') ?>"></span>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="pipeline-stages" style="--stages:<?= max(1, count($stages)) ?>">
                <?php foreach ($stages as $stage): ?>
                    <?php
                        $series = $trends[$stage['key']] ?? [];
                        $todayN = (int) (count($series) ? end($series) : 0);
                        $share  = $sum > 0 ? round($stage['count'] / $sum * 100) : 0;
                        $sTag   = !empty($stage['link']) ? 'a' : 'div';
                    ?>
                    <<?= $sTag ?> class="stage"<?= !empty($stage['link']) ? ' href="' . esc($stage['link'], 'attr') . '"' : '' ?>
                        style="--kpi:<?= esc($stage['color'], 'attr') ?>" title="<?= esc($stage['hint'], 'attr') ?>">
                        <span class="stage-label"><span class="stage-dot" aria-hidden="true"></span><?= esc($stage['label']) ?></span>
                        <span class="stage-value"><?= esc(number_format($stage['count'])) ?><?php if ($split): ?><small><?= $share ?>%</small><?php endif; ?></span>
                        <span class="stage-note"><strong>+<?= esc(number_format($todayN)) ?></strong> <?= esc($stage['today']) ?></span>
                    </<?= $sTag ?>>
                <?php endforeach; ?>
            </div>
        </article>
    <?php endif; ?>
</section>

<style>
/* ===== KPI BOARD (shared) ===== */

.kpi-board {
    --kb-surface:     var(--pm-surface, #FFFFFF);
    --kb-subtle:      var(--pm-subtle, #EBF0F5);
    --kb-border:      var(--pm-line, #DDE4EC);
    --kb-border-hi:   var(--pm-line-strong, #C3CFDB);
    --kb-text:        var(--pm-text, #10202E);
    --kb-text-soft:   var(--pm-text-soft, #3B4C5E);
    --kb-text-muted:  var(--pm-text-muted, #5D6E80);
    --kb-accent:      var(--pm-accent, #0B67B2);
    --kb-accent-dark: var(--pm-accent-hover, #08528F);
    --kb-radius:      8px;
    --kb-radius-sm:   6px;
    --kb-shadow:      none;
    --kb-shadow-hi:   none;

    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr)) minmax(0, 2.1fr);
    gap: 16px;
    margin-bottom: 20px;
    font-family: var(--pm-font);
    color: var(--kb-text);
}

.kpi-board--cards-only { grid-template-columns: repeat(var(--kpi-cols, 4), minmax(0, 1fr)); }


.kpi-board .kpi-card {
    --kpi: var(--kb-accent);
    position: relative;
    display: flex;
    flex-direction: column;
    min-width: 0;
    padding: 18px;
    background: var(--kb-surface);
    border: 1px solid var(--kb-border);
    border-radius: var(--kb-radius);
    box-shadow: var(--kb-shadow);
    color: inherit;
    text-decoration: none;
    overflow: hidden;
    transition: border-color 0.15s ease;
}

.kpi-board a.kpi-card:hover,
.kpi-board a.kpi-card:focus-visible {
    border-color: var(--kb-border-hi);
    color: inherit;
    text-decoration: none;
}

.kpi-board .kpi-head {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 14px;
    min-width: 0;
}

.kpi-board .kpi-icon {
    flex: 0 0 18px;
    width: 18px;
    height: 18px;
    object-fit: contain;
    display: block;
    font-size: 1rem;
    line-height: 18px;
    color: var(--kb-text-muted);
}

.kpi-board .kpi-label {
    flex: 1;
    min-width: 0;
    margin: 0;
    font-size: 0.8rem;
    font-weight: 500;
    line-height: 1.25;
    color: var(--kb-text-soft);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.kpi-board .kpi-go {
    flex-shrink: 0;
    color: var(--kb-border-hi);
    transition: color 0.2s ease, transform 0.2s ease;
}

.kpi-board .kpi-card:hover .kpi-go,
.kpi-board .kpi-card:focus-visible .kpi-go {
    color: var(--kb-text-soft);
}

.kpi-board .kpi-value {
    font-size: 1.75rem;
    font-weight: 600;
    line-height: 1.1;
    letter-spacing: -0.02em;
    color: var(--kb-text);
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Money is longer: slightly smaller, with symbol and cents de-emphasised. */
.kpi-board .kpi-value.is-money { font-size: 1.45rem; }

.kpi-board .kpi-unit {
    font-size: 0.7em;
    font-weight: 600;
    color: var(--kb-text-muted);
    margin-right: 2px;
    vertical-align: 0.12em;
}

.kpi-board .kpi-cents {
    font-size: 0.62em;
    font-weight: 600;
    color: var(--kb-text-muted);
    letter-spacing: 0;
}

.kpi-board .kpi-meta {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: 8px;
    min-width: 0;
    font-size: 0.75rem;
}

.kpi-board .kpi-delta {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    flex-shrink: 0;
    font-weight: 600;
    font-variant-numeric: tabular-nums;
    line-height: 1.4;
}

/* Coloured text + arrow is enough; filled pills competed with the value. */
.kpi-board .kpi-delta.is-good    { color: #067647; }
.kpi-board .kpi-delta.is-bad     { color: #B42318; }
.kpi-board .kpi-delta.is-neutral { color: var(--kb-text-soft); }

.kpi-board .kpi-context {
    min-width: 0;
    color: var(--kb-text-muted);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* 14-day bar chart; today's bar is solid, earlier days are tinted. */
.kpi-board .kpi-chart {
    margin-top: auto;
    padding-top: 14px;
}

.kpi-board .kpi-bars {
    display: flex;
    align-items: flex-end;
    gap: 3px;
    height: 44px;
    border-bottom: 1px solid var(--kb-border);
}

.kpi-board .kpi-bar {
    flex: 1 1 0;
    min-width: 0;
    border-radius: 1px 1px 0 0;
    background: color-mix(in srgb, var(--kpi) 22%, #FFFFFF);
    transition: background-color 0.15s ease;
}

.kpi-board .kpi-bar:hover,
.kpi-board .kpi-bar.is-today {
    background: var(--kpi);
}

.kpi-board .kpi-axis {
    display: flex;
    justify-content: space-between;
    margin-top: 5px;
    font-size: 0.68rem;
    color: var(--kb-text-muted);
}

/* ----- Pipeline card ----- */

.kpi-board .pipeline-card {
    display: flex;
    flex-direction: column;
    min-width: 0;
    padding: 18px;
    background: var(--kb-surface);
    border: 1px solid var(--kb-border);
    border-radius: var(--kb-radius);
    box-shadow: var(--kb-shadow);
}

.kpi-board .pipeline-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 14px;
}

.kpi-board .pipeline-title {
    margin: 0;
    font-size: 0.95rem;
    font-weight: 600;
    letter-spacing: -0.01em;
    color: var(--kb-text);
}

.kpi-board .pipeline-sub {
    margin: 2px 0 0;
    font-size: 0.75rem;
    color: var(--kb-text-muted);
}

.kpi-board .pipeline-link {
    display: inline-flex;
    align-items: center;
    gap: 2px;
    flex-shrink: 0;
    font-size: 0.78rem;
    font-weight: 600;
    color: var(--kb-accent);
    text-decoration: none;
}

.kpi-board .pipeline-link:hover { color: var(--kb-accent-dark); text-decoration: none; }

/* Status split; segments get a 2px gap so small shares stay visible. */
.kpi-board .pipeline-bar {
    display: flex;
    gap: 2px;
    height: 6px;
    border-radius: 2px;
    overflow: hidden;
    background: var(--kb-subtle);
    margin-bottom: 14px;
}

.kpi-board .pipeline-bar span {
    flex-basis: 0;
    min-width: 6px;
}

.kpi-board .pipeline-stages {
    display: grid;
    grid-template-columns: repeat(var(--stages, 3), minmax(0, 1fr));
    gap: 8px;
    margin-top: auto;
}

.kpi-board .stage {
    display: flex;
    flex-direction: column;
    gap: 4px;
    min-width: 0;
    padding: 10px 12px;
    border-radius: var(--kb-radius-sm);
    background: var(--kb-surface);
    border: 1px solid var(--kb-border);
    color: inherit;
    text-decoration: none;
    transition: border-color 0.15s ease, background-color 0.15s ease;
}

.kpi-board a.stage:hover,
.kpi-board a.stage:focus-visible {
    border-color: var(--kb-border-hi);
    background: #F8FAFC;
    color: inherit;
    text-decoration: none;
}

.kpi-board .stage-label {
    display: flex;
    align-items: center;
    gap: 6px;
    min-width: 0;
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--kb-text-soft);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.kpi-board .stage-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--kpi);
    flex-shrink: 0;
}

.kpi-board .stage-value {
    display: flex;
    align-items: baseline;
    gap: 6px;
    font-size: 1.4rem;
    font-weight: 600;
    line-height: 1.1;
    letter-spacing: -0.02em;
    color: var(--kb-text);
    font-variant-numeric: tabular-nums;
}

.kpi-board .stage-value small {
    font-size: 0.72rem;
    font-weight: 600;
    letter-spacing: 0;
    color: var(--kb-text-muted);
}

.kpi-board .stage-note {
    font-size: 0.72rem;
    color: var(--kb-text-muted);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.kpi-board .stage-note strong {
    color: var(--kb-text-soft);
    font-weight: 600;
}

/* ----- Responsive ----- */

@media (max-width: 1600px) {
    .kpi-board:not(.kpi-board--cards-only) { grid-template-columns: repeat(4, minmax(0, 1fr)); }
    .kpi-board .pipeline-card { grid-column: 1 / -1; }
}

@media (max-width: 992px) {
    .kpi-board,
    .kpi-board--cards-only { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}

@media (max-width: 768px) {
    .kpi-board .kpi-card          { padding: 16px; }
    .kpi-board .kpi-value         { font-size: 1.5rem; }
    .kpi-board .kpi-value.is-money { font-size: 1.3rem; }
}

@media (max-width: 576px) {
    .kpi-board,
    .kpi-board--cards-only { grid-template-columns: minmax(0, 1fr); gap: 12px; }
    .kpi-board .pipeline-stages { grid-template-columns: minmax(0, 1fr); }
}

@media (prefers-reduced-motion: reduce) {
    .kpi-board * { transition-duration: 0.01ms !important; }
}

@media print {
    .kpi-board .kpi-card,
    .kpi-board .pipeline-card { box-shadow: none; break-inside: avoid; page-break-inside: avoid; }
}
</style>
