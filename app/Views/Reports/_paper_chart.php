<?php
/*
 * Single-series bar chart for report papers — inline SVG, prints
 * without JS, one ink colour so it survives greyscale printing.
 * Include with $chart = [
 *   'rows'    => [['value' => float, 'tick' => string, 'title' => string], ...],
 *   'short'   => fn(float): string   axis label formatter,
 *   'integer' => bool                counts (whole-number gridlines),
 *   'aria'    => string, 'caption' => string,
 * ]
 * Renders nothing when every value is zero.
 */
$cRows  = $chart['rows'];
$cMax   = max(array_merge([0.0], array_column($cRows, 'value')));
if ($cMax > 0):
    if (!empty($chart['integer']) && $cMax <= 4) {
        $cTop = (float) max(2, (int) ceil($cMax / 2) * 2);   // 2 or 4: halves stay whole
    } else {
        $mag  = 10 ** floor(log10($cMax));
        $cTop = ceil($cMax / $mag * 2) / 2 * $mag;           // next 0.5 step of the magnitude
        if (!empty($chart['integer'])) $cTop = (float) (ceil($cTop / 2) * 2);
    }
    $cw = 680; $ch = 150; $padL = 46; $padB = 20; $padT = 8;
    $plotW = $cw - $padL; $plotH = $ch - $padB - $padT;
    $n     = max(1, count($cRows));
    $slot  = $plotW / $n;
    $barW  = max(2, min(28, $slot - 2));
    $every = (int) ceil($n / 12);
    $y0    = $padT + $plotH;
?>
<figure>
    <svg viewBox="0 0 <?= $cw ?> <?= $ch ?>" role="img" aria-label="<?= esc($chart['aria'], 'attr') ?>">
        <?php for ($g = 0; $g <= 2; $g++):
            $gy = $y0 - $plotH * $g / 2; ?>
            <line class="<?= $g ? 'grid' : 'base' ?>" x1="<?= $padL ?>" x2="<?= $cw ?>" y1="<?= round($gy, 1) ?>" y2="<?= round($gy, 1) ?>"/>
            <text class="tick" x="<?= $padL - 6 ?>" y="<?= round($gy + 3, 1) ?>" text-anchor="end"><?= esc(($chart['short'])($cTop * $g / 2)) ?></text>
        <?php endfor; ?>
        <?php foreach ($cRows as $i => $row):
            $cx  = $padL + $slot * $i + $slot / 2;
            $h   = max(0, (float) $row['value']) / $cTop * $plotH;
            $x   = round($cx - $barW / 2, 2);
            $x2  = round($cx + $barW / 2, 2);
            $top = round($y0 - $h, 2);
            $rad = min(3, $barW / 2, $h);
        ?>
            <?php if ($h > 0): ?>
                <path class="bar" d="M<?= $x ?>,<?= $y0 ?> V<?= round($top + $rad, 2) ?> Q<?= $x ?>,<?= $top ?> <?= round($x + $rad, 2) ?>,<?= $top ?> H<?= round($x2 - $rad, 2) ?> Q<?= $x2 ?>,<?= $top ?> <?= $x2 ?>,<?= round($top + $rad, 2) ?> V<?= $y0 ?> Z"><title><?= esc($row['title']) ?></title></path>
            <?php endif; ?>
            <?php if ($i % $every === 0): ?>
                <text class="tick" x="<?= round($cx, 1) ?>" y="<?= $ch - 6 ?>" text-anchor="middle"><?= esc($row['tick']) ?></text>
            <?php endif; ?>
        <?php endforeach; ?>
    </svg>
    <figcaption><?= esc($chart['caption']) ?></figcaption>
</figure>
<?php endif; ?>
