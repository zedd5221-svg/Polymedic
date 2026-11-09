<?php
/* ------------------------------------------------------------------
   Minimal KPI strip for list pages (payments, billing, patients,
   services). One hairline panel, one cell per metric, and a
   Hide / Show toggle remembered per page in this browser.

   Dashboards keep the richer partials/kpi_board.

   Data:
     $id      string: short page key, used for the element id and the
              remembered hide state (e.g. 'payments')
     $title   string: small heading above the strip (default 'Overview')
     $note    string: optional muted text after the heading (e.g. "As of …")
     $columns int: cells per row; more cards wrap onto further rows
              (default: all cards on one row)
     $cards   list of [
                key, label, value (number) or display (preformatted string),
                money (bool), delta ('dod' | 'sum' | null),
                goodUp (bool, default true), context
              ]
     $trends  [<key> => [daily values, oldest first], ...]
   ------------------------------------------------------------------ */
$id      = preg_replace('/[^a-z0-9_-]/', '', strtolower((string) ($id ?? 'page'))) ?: 'page';
$title   = (string) ($title ?? 'Overview');
$note    = (string) ($note ?? '');
$cards   = $cards ?? [];
$trends  = $trends ?? [];
$columns = max(1, (int) ($columns ?? count($cards)));
$wraps   = count($cards) > $columns;

$stripId = 'kpiStrip-' . $id;
$bodyId  = $stripId . '-body';

/* Peso amount with the cents de-emphasised. */
$moneyHtml = static function (float $amount): string {
    $parts = explode('.', number_format($amount, 2));
    return '&#8369;' . esc($parts[0]) . '<span class="kpi-strip-cents">.' . esc($parts[1] ?? '00') . '</span>';
};

/*
 * Change indicator.
 *  'dod' : today vs yesterday, as a percentage.
 *  'sum' : total added across the series (daily-count series).
 * Returns ['text', 'tone' => good|bad|neutral] or null.
 */
$delta = static function (array $vals, ?string $mode, bool $goodUp, bool $money): ?array {
    $n = count($vals);
    if ($mode === null || $n < 2) { return null; }

    if ($mode === 'sum') {
        $added = array_sum($vals);
        if ($added < 0.005) { return ['text' => 'None', 'tone' => 'neutral']; }
        $text = '+' . ($money ? '₱' . number_format($added, 2) : number_format($added));
        return ['text' => $text, 'tone' => $goodUp ? 'good' : 'bad'];
    }

    $cur  = (float) $vals[$n - 1];
    $prev = (float) $vals[$n - 2];
    $diff = $cur - $prev;
    if (abs($diff) < 0.005) { return ['text' => 'No change', 'tone' => 'neutral']; }
    if ($prev == 0.0)       { return ['text' => 'New', 'tone' => 'neutral']; }

    $pct  = abs($diff) / $prev * 100;
    $text = ($diff > 0 ? '▲ ' : '▼ ') . ($pct >= 10 ? number_format($pct) : number_format($pct, 1)) . '%';
    return ['text' => $text, 'tone' => ($diff > 0) === $goodUp ? 'good' : 'bad'];
};
?>

<section class="kpi-strip<?= $wraps ? ' kpi-strip--wrap' : '' ?>" id="<?= esc($stripId, 'attr') ?>" aria-labelledby="<?= esc($stripId, 'attr') ?>-title">
    <div class="kpi-strip-head">
        <h2 class="kpi-strip-title" id="<?= esc($stripId, 'attr') ?>-title">
            <?= esc($title) ?>
            <?php if ($note !== ''): ?><span class="kpi-strip-note"><?= esc($note) ?></span><?php endif; ?>
        </h2>
        <button type="button" class="kpi-strip-toggle"
                aria-expanded="true" aria-controls="<?= esc($bodyId, 'attr') ?>">
            <i class="bi bi-eye-slash" aria-hidden="true"></i>
            <span>Hide</span>
        </button>
    </div>

    <dl class="kpi-strip-body" id="<?= esc($bodyId, 'attr') ?>" style="--cells:<?= $columns ?>">
        <?php foreach ($cards as $card): ?>
            <?php
                $isMoney = !empty($card['money']);
                $vals    = array_map('floatval', $trends[$card['key']] ?? []);
                $change  = $delta($vals, $card['delta'] ?? null, $card['goodUp'] ?? true, $isMoney);
                /* A comparison caption without its number reads as a statement, so drop both. */
                $context = (!empty($card['delta']) && !$change) ? '' : (string) ($card['context'] ?? '');
            ?>
            <div class="kpi-strip-cell">
                <dt class="kpi-strip-label"><?= esc($card['label']) ?></dt>
                <dd class="kpi-strip-value">
                    <?php if (isset($card['display'])): ?>
                        <?= esc((string) $card['display']) ?>
                    <?php else: ?>
                        <?= $isMoney ? $moneyHtml((float) $card['value']) : esc(number_format((float) $card['value'])) ?>
                    <?php endif; ?>
                </dd>
                <dd class="kpi-strip-meta">
                    <?php if ($change): ?>
                        <span class="kpi-strip-delta is-<?= esc($change['tone'], 'attr') ?>"><?= esc($change['text']) ?></span>
                    <?php endif; ?>
                    <?= esc($context) ?>
                </dd>
            </div>
        <?php endforeach; ?>
    </dl>
</section>

<style>
.kpi-strip {
    --ks-line:  var(--pm-line, #DDE4EC);
    --ks-text:  var(--pm-text, #10202E);
    --ks-soft:  var(--pm-text-soft, #3B4C5E);
    --ks-muted: var(--pm-text-muted, #5D6E80);

    margin-bottom: 1rem;
}

.kpi-strip-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    margin-bottom: 0.4rem;
}

.kpi-strip-title {
    margin: 0;
    font-size: 0.72rem;
    font-weight: 600;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    color: var(--ks-muted);
}

.kpi-strip-note {
    margin-left: 0.5rem;
    font-size: 0.72rem;
    font-weight: 400;
    letter-spacing: 0;
    text-transform: none;
}

.kpi-strip.is-collapsed .kpi-strip-note { display: none; }

.kpi-strip-toggle {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    height: 28px;
    padding: 0 0.5rem;
    font-size: 0.75rem;
    font-weight: 500;
    color: var(--ks-muted);
    background: none;
    border: 0;
    border-radius: 6px;
    cursor: pointer;
    transition: background-color 0.15s ease, color 0.15s ease;
}

.kpi-strip-toggle:hover { color: var(--ks-text); background: var(--pm-subtle, #EBF0F5); }

.kpi-strip-body {
    display: grid;
    grid-template-columns: repeat(var(--cells), minmax(0, 1fr));
    margin: 0;
    background: var(--pm-surface, #FFFFFF);
    border: 1px solid var(--ks-line);
    border-radius: 8px;
}

.kpi-strip.is-collapsed .kpi-strip-body { display: none; }

.kpi-strip-cell {
    min-width: 0;
    padding: 0.8rem 1.1rem;
}

.kpi-strip-cell + .kpi-strip-cell { border-left: 1px solid var(--ks-line); }

/* Several rows: each cell draws its own left and top hairline; the ones
   that land on the panel edge sit on the panel border and disappear. */
.kpi-strip--wrap .kpi-strip-body { overflow: hidden; }
.kpi-strip--wrap .kpi-strip-cell,
.kpi-strip--wrap .kpi-strip-cell + .kpi-strip-cell {
    border-left: 0;
    box-shadow: -1px -1px 0 0 var(--ks-line);
}

.kpi-strip-label {
    font-size: 0.76rem;
    font-weight: 500;
    color: var(--ks-muted);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.kpi-strip-value {
    margin: 0.15rem 0 0;
    font-size: 1.35rem;
    font-weight: 600;
    line-height: 1.2;
    letter-spacing: -0.02em;
    color: var(--ks-text);
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.kpi-strip-cents { font-size: 0.7em; color: var(--ks-muted); letter-spacing: 0; }

.kpi-strip-meta {
    margin: 0.1rem 0 0;
    font-size: 0.72rem;
    color: var(--ks-muted);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.kpi-strip-delta {
    margin-right: 0.25rem;
    font-weight: 600;
    font-variant-numeric: tabular-nums;
}

.kpi-strip-delta.is-good    { color: var(--pm-dk-fg-green, #067647); }
.kpi-strip-delta.is-bad     { color: var(--pm-dk-fg-red, #B42318); }
.kpi-strip-delta.is-neutral { color: var(--ks-soft); }

@media (max-width: 768px) {
    .kpi-strip-body { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .kpi-strip-cell + .kpi-strip-cell { border-left: 0; }
    .kpi-strip-cell:nth-child(even) { border-left: 1px solid var(--ks-line); }
    .kpi-strip-cell:nth-child(n + 3) { border-top: 1px solid var(--ks-line); }
    .kpi-strip--wrap .kpi-strip-cell:nth-child(n) { border-left: 0; border-top: 0; }
}

@media print {
    .kpi-strip-toggle { display: none; }
    .kpi-strip.is-collapsed .kpi-strip-body { display: grid; }
}
</style>

<script>
(function () {
    var root = document.getElementById(<?= json_encode($stripId) ?>);
    if (!root) { return; }

    var btn   = root.querySelector('.kpi-strip-toggle');
    var icon  = btn.querySelector('i');
    var label = btn.querySelector('span');
    var key   = 'pm.kpiHidden.' + <?= json_encode($id) ?>;

    function apply(hidden) {
        root.classList.toggle('is-collapsed', hidden);
        btn.setAttribute('aria-expanded', hidden ? 'false' : 'true');
        icon.className = 'bi ' + (hidden ? 'bi-eye' : 'bi-eye-slash');
        label.textContent = hidden ? 'Show' : 'Hide';
    }

    var stored = null;
    try { stored = window.localStorage.getItem(key); } catch (e) {}
    apply(stored === '1');

    btn.addEventListener('click', function () {
        var hidden = !root.classList.contains('is-collapsed');
        apply(hidden);
        try { window.localStorage.setItem(key, hidden ? '1' : '0'); } catch (e) {}
    });
}());
</script>
