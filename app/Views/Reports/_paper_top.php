<?php
/*
 * Printable A4 report — opening chrome shared by every report paper.
 * Plain-PHP partial: include it from the report view, which must set
 *   $clinic  config('Clinic')->toArray()
 *   $paper   ['kind', 'title', 'no', 'meta' => [label => value],
 *             'conf', 'backUrl', 'annex' => bool|null, 'annexLabel', 'pageTitle']
 * and then continue with <section>s, _paper_signoff.php, the optional
 * annex (<section class="annex long" id="annex">) and _paper_end.php.
 *
 * Defines formatting helpers for the report body:
 *   $amt($v, $peso = false)  accounting style: 1,234.00 / (1,234.00) / –
 *   $peso($v)                ₱1,234.00
 *   $pct($now, $before)      +12.3% / −4.0% / n/a / –
 *   $share($part, $whole)    45.0% / –
 *   $num($n)                 1,234 / (12) / –
 */
$amt = static function ($v, bool $peso = false): string {
    $v = round((float) $v, 2);
    if ($v == 0.0) return '–';
    $out = number_format(abs($v), 2);
    if ($peso) $out = '₱' . $out;
    return $v < 0 ? '(' . $out . ')' : $out;
};
$peso = static fn($v) => '₱' . number_format((float) $v, 2);
$pct  = static function (?float $now, ?float $before): string {
    if ($now === null || $before === null) return '–';
    if ($before == 0.0) return $now == 0.0 ? '–' : 'n/a';
    $d = ($now - $before) / $before * 100;
    return ($d > 0 ? '+' : ($d < 0 ? '−' : '')) . number_format(abs($d), 1) . '%';
};
$share = static fn(float $part, float $whole) => $whole > 0 ? number_format($part / $whole * 100, 1) . '%' : '–';
$num   = static function ($n): string {
    $n = (int) $n;
    if ($n === 0) return '–';
    return $n < 0 ? '(' . number_format(-$n) . ')' : number_format($n);
};
$generatedAt = date('F j, Y · g:i A');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= esc($paper['pageTitle'] ?? ($paper['title'] . ' ' . $paper['no'])) ?> · <?= esc($clinic['name']) ?></title>
<style>
    :root {
        --ink:#111827; --ink-2:#374151; --muted:#6b7280; --faint:#9ca3af;
        --rule:#d1d5db; --rule-soft:#e5e7eb; --band:#f3f4f6;
        --accent:#0f766e; --accent-dark:#115e59; --accent-soft:#ccfbf1;
        --bad:#b91c1c; --good:#047857;
        --serif: Georgia, "Times New Roman", Times, serif;
        --sans: "Segoe UI", system-ui, -apple-system, Roboto, Arial, sans-serif;
    }
    * { box-sizing:border-box; }
    html { background:#e5e7eb; }
    body { margin:0; padding:24px 16px 48px; font-family:var(--sans); font-size:10.5pt; color:var(--ink); background:#e5e7eb; -webkit-font-smoothing:antialiased; }

    /* ---- Screen toolbar ---- */
    .toolbar { max-width:210mm; margin:0 auto 14px; display:flex; flex-wrap:wrap; align-items:center; gap:10px; }
    .toolbar .spacer { flex:1; }
    .toolbar a, .toolbar button { height:36px; padding:0 14px; border-radius:6px; border:1px solid var(--rule); background:#fff; color:var(--ink); font:600 13px var(--sans); text-decoration:none; display:inline-flex; align-items:center; gap:6px; cursor:pointer; }
    .toolbar button.primary { background:var(--accent); border-color:var(--accent); color:#fff; }
    .toolbar label { font-size:13px; color:var(--ink-2); display:inline-flex; align-items:center; gap:6px; cursor:pointer; }

    /* ---- Paper ---- */
    .sheet { max-width:210mm; margin:0 auto; background:#fff; padding:16mm 15mm 14mm; box-shadow:0 1px 3px rgba(0,0,0,.08), 0 12px 32px rgba(0,0,0,.10); }

    /* Letterhead */
    .lh { display:flex; align-items:flex-start; gap:14px; padding-bottom:10px; border-bottom:2.5px solid var(--ink); }
    .lh img { width:54px; height:54px; object-fit:contain; flex-shrink:0; }
    .lh-clinic { flex:1; min-width:0; }
    .lh-clinic h1 { margin:0; font:700 15pt/1.15 var(--serif); letter-spacing:.02em; }
    .lh-clinic p { margin:2px 0 0; font-size:8.5pt; color:var(--muted); line-height:1.4; }
    .lh-doc { text-align:right; flex-shrink:0; }
    .lh-doc .kind { font:700 8pt var(--sans); letter-spacing:.18em; text-transform:uppercase; color:var(--accent); }
    .lh-doc .title { margin-top:2px; font:700 17pt/1.1 var(--serif); }
    .lh-doc .no { margin-top:4px; font:600 9pt ui-monospace, Consolas, monospace; color:var(--ink-2); }
    .rule-thin { height:0; border-top:1px solid var(--ink); margin-top:2px; }

    .meta { display:grid; grid-template-columns:repeat(4, minmax(0,1fr)); gap:8px 16px; margin:10px 0 4px; padding:8px 0 10px; border-bottom:1px solid var(--rule); }
    .meta dt { font-size:7pt; font-weight:700; letter-spacing:.12em; text-transform:uppercase; color:var(--muted); }
    .meta dd { margin:2px 0 0; font-size:9pt; font-weight:600; }
    .conf { margin:6px 0 0; font-size:7.5pt; letter-spacing:.14em; text-transform:uppercase; color:var(--muted); text-align:right; }

    /* Sections */
    section { margin-top:18px; }
    h2 { margin:0 0 8px; font:700 11.5pt var(--serif); display:flex; align-items:baseline; gap:8px; padding-bottom:4px; border-bottom:1px solid var(--rule); break-after:avoid; }
    h2 .num { color:var(--accent); font-family:var(--sans); font-size:9.5pt; }
    h2 small { margin-left:auto; font:400 8pt var(--sans); color:var(--muted); }
    p.lead { margin:0; font:10.5pt/1.6 var(--serif); font-variant-numeric:lining-nums; color:var(--ink-2); text-align:justify; hyphens:auto; }
    .note { margin:6px 0 0; font-size:8pt; color:var(--muted); }
    ol.notes { margin:0; padding-left:16px; line-height:1.6; font-size:8.5pt; color:var(--ink-2); }

    /* Key figures */
    .figs { display:grid; grid-template-columns:repeat(4, minmax(0,1fr)); margin-top:12px; border-top:1px solid var(--ink); border-bottom:1px solid var(--ink); }
    .fig { padding:8px 10px; border-left:1px solid var(--rule-soft); }
    .fig:first-child { border-left:0; padding-left:0; }
    .fig dt { font-size:7pt; font-weight:700; letter-spacing:.12em; text-transform:uppercase; color:var(--muted); }
    .fig dd { margin:3px 0 0; font:700 14pt var(--sans); font-variant-numeric:tabular-nums lining-nums; letter-spacing:-.01em; white-space:nowrap; }
    .fig .vs { display:block; margin-top:1px; font:400 8pt var(--sans); color:var(--muted); }

    /* Tables */
    table { width:100%; border-collapse:collapse; font-variant-numeric:tabular-nums; }
    th, td { padding:4px 6px; text-align:left; vertical-align:baseline; }
    th { font-size:7.5pt; font-weight:700; letter-spacing:.06em; text-transform:uppercase; color:var(--ink-2); border-top:1px solid var(--ink); border-bottom:1px solid var(--ink); }
    td { font-size:9pt; border-bottom:1px solid var(--rule-soft); }
    th:first-child, td:first-child { padding-left:0; }
    th:last-child, td:last-child { padding-right:0; }
    .r { text-align:right; white-space:nowrap; }
    .c { text-align:center; }
    .muted { color:var(--muted); }
    .neg { color:var(--bad); }
    .pos { color:var(--good); }
    .mono { font-family:ui-monospace, Consolas, monospace; font-size:8pt; white-space:nowrap; }
    .nowrap { white-space:nowrap; }
    tr.group td { padding-top:8px; font-weight:700; border-bottom:0; }
    tr.indent td:first-child { padding-left:14px; }
    tr.sub td { font-weight:600; border-top:1px solid var(--ink-2); border-bottom:0; }
    tr.total td { font-weight:700; font-size:9.5pt; border-top:1px solid var(--ink); border-bottom:3px double var(--ink); padding-top:5px; padding-bottom:5px; }
    tr.memo td { font-size:8.5pt; color:var(--ink-2); border-bottom:0; }
    tfoot td { font-weight:700; border-top:1px solid var(--ink); border-bottom:3px double var(--ink); }
    table.stmt td:first-child { width:36%; }
    table.dense td { padding-top:2px; padding-bottom:2px; font-size:8.5pt; }
    tr { break-inside:avoid; }
    thead { display:table-header-group; }
    tfoot { display:table-row-group; }   /* totals print once, at the end, not on every page */

    .two { display:grid; grid-template-columns:minmax(0,1.25fr) minmax(0,1fr); gap:22px; align-items:start; }
    .box { border:1px solid var(--ink); padding:8px 10px; }
    .box h3 { margin:0 0 6px; font-size:7.5pt; letter-spacing:.12em; text-transform:uppercase; }
    .box dl { margin:0; }
    .box dl div { display:flex; justify-content:space-between; gap:10px; padding:2px 0; font-size:9pt; font-variant-numeric:tabular-nums; }
    .box dl div.t { border-top:1px solid var(--ink); margin-top:3px; padding-top:4px; font-weight:700; }
    .box dl dd { margin:0; }
    .box .blank { flex:0 0 42%; border-bottom:1px solid var(--ink-2); }

    /* Chart */
    figure { margin:0 0 10px; }
    figure svg { display:block; width:100%; height:auto; }
    figcaption { font-size:8pt; color:var(--muted); margin-top:3px; }
    .bar { fill:var(--accent); }
    .bar:hover { fill:var(--accent-dark); }
    .grid { stroke:var(--rule-soft); stroke-width:1; }
    .base { stroke:var(--ink-2); stroke-width:1; }
    .tick { font:9px var(--sans); fill:var(--muted); }

    /* Certification + signatures */
    .signoff { break-inside:avoid; page-break-inside:avoid; }
    .cert { margin-top:22px; padding:10px 12px; background:var(--band); font:9.5pt/1.55 var(--serif); color:var(--ink-2); }
    .cert b { font-family:var(--sans); }
    /* Three equal fluid columns on any paper. The columns share one set of rows
       (subgrid), so if any name or title wraps, every line and date stays level. */
    .signs { display:grid; grid-template-columns:repeat(3, minmax(0,1fr)); grid-template-rows:repeat(4, auto); column-gap:5%; margin-top:1.2em; font-size:8pt; line-height:1.35; }
    .sign { display:grid; grid-row:span 4; grid-template-rows:subgrid; min-width:0; color:var(--muted); text-align:center; overflow-wrap:anywhere; }
    @supports not (grid-template-rows:subgrid) {
        .sign { grid-template-rows:3.2em auto 2.8em 2em; }
    }
    .sign-name { display:flex; align-items:flex-end; justify-content:center; min-height:3.2em; padding-bottom:.25em; border-bottom:1px solid var(--ink); font-size:9.5pt; font-weight:700; color:var(--ink); }
    .sign-role { margin-top:.35em; font-weight:700; letter-spacing:.1em; text-transform:uppercase; color:var(--ink-2); }
    .sign-date { display:flex; align-items:flex-end; gap:.5em; min-height:2em; text-align:left; }
    .sign-date span { flex:1; min-width:0; border-bottom:1px solid var(--ink-2); }

    .endnote { margin-top:16px; padding-top:6px; border-top:1px solid var(--rule); display:flex; justify-content:space-between; gap:12px; font-size:7.5pt; color:var(--muted); }
    .annex { break-before:page; }
    .annex.is-off { display:none; }
    .tag { display:inline-block; padding:0 5px; border:1px solid currentColor; border-radius:3px; font-size:7pt; font-weight:700; letter-spacing:.06em; text-transform:uppercase; }

    /* Screen only: narrow paper must not pick up the phone layout when printing. */
    @media screen and (max-width: 720px) {
        .sheet { padding:20px 16px; }
        .lh { flex-wrap:wrap; }
        .lh-doc { text-align:left; width:100%; }
        .meta, .figs { grid-template-columns:repeat(2, minmax(0,1fr)); }
        .fig:nth-child(3) { border-left:0; padding-left:0; }
        .two { grid-template-columns:1fr; }
        .scroll { overflow-x:auto; }
    }

    /* ---- Print: portrait on the paper chosen in the print dialog, real margins,
            running footer with page numbers ---- */
    @page {
        size: portrait;
        margin: 12mm 13mm 15mm;
        @bottom-left  { content: "<?= esc($clinic['name'], 'css') ?> · <?= esc($paper['title'], 'css') ?> <?= esc($paper['no'], 'css') ?>"; font: 7pt "Segoe UI", Arial, sans-serif; color: #6b7280; }
        @bottom-right { content: "Page " counter(page) " of " counter(pages); font: 7pt "Segoe UI", Arial, sans-serif; color: #6b7280; }
    }
    @media print {
        html, body { background:#fff; padding:0; }
        .toolbar { display:none; }
        .sheet { max-width:none; padding:0; box-shadow:none; }
        * { -webkit-print-color-adjust:exact; print-color-adjust:exact; }

        /* Tighter than the screen preview, so sections fit instead of jumping a page. */
        section { margin-top:12px; }
        h2 { margin-bottom:5px; padding-bottom:3px; }
        p.lead { font-size:10pt; line-height:1.45; }
        .figs { margin-top:8px; }
        .fig { padding-top:5px; padding-bottom:5px; }
        th, td { padding-top:3px; padding-bottom:3px; }
        table.dense td { padding-top:1px; padding-bottom:1px; line-height:1.25; }
        tr.group td { padding-top:5px; }
        figure { margin-bottom:6px; }
        .cert { margin-top:14px; padding:7px 10px; font-size:9pt; line-height:1.45; }
        .endnote { margin-top:10px; }

        /* Pagination: small blocks stay whole, long tables flow across pages. */
        section, .two > *, figure, .box, .figs, .signoff, .keep { break-inside:avoid; }
        section.long, .long .two > * { break-inside:auto; }
        p, li { orphans:3; widows:3; }

        /* Headers repeat on each page; rows never split. */
        thead { display:table-header-group; break-inside:avoid; }
        tr, td, th { break-inside:avoid; }

        /* No heading or header row alone at a page bottom, no total alone at a page top. */
        h2, thead, tbody tr:first-child { break-after:avoid; }
        tfoot, tfoot tr { break-before:avoid; break-inside:avoid; }
        tbody tr:nth-last-child(2) { break-after:avoid; }

        /* The annex always starts on its own sheet. */
        .annex { break-before:page; margin-top:0; }
    }
</style>
</head>
<body>

<div class="toolbar">
    <a href="<?= esc($paper['backUrl'], 'attr') ?>">← Back to reports</a>
    <span class="spacer"></span>
    <?php if (isset($paper['annex'])): ?>
        <label><input type="checkbox" id="annexToggle" <?= $paper['annex'] ? 'checked' : '' ?>> <?= esc($paper['annexLabel'] ?? 'Include annex') ?></label>
    <?php endif; ?>
    <button type="button" class="primary" onclick="window.print()">Print</button>
    <?= view('partials/pdf_tools', ['pdf' => [
        // Internal report: no default recipient.
        'target'   => '.sheet',
        'filename' => $paper['title'] . ' ' . $paper['no'],
        'label'    => strtolower($paper['title']),
        'subject'  => $paper['title'] . ' ' . $paper['no'] . ' · ' . $clinic['name'],
        'message'  => "Good day,\n\nAttached is the " . $paper['title'] . ' (No. ' . $paper['no'] . ").\n\nThank you.",
    ]]) ?>
</div>

<main class="sheet">

    <!-- ===== LETTERHEAD ===== -->
    <header class="lh">
        <img src="<?= base_url('assets/images/logo4.png') ?>" alt="">
        <div class="lh-clinic">
            <h1><?= esc($clinic['name']) ?></h1>
            <p><?= esc($paper['unit'] ?? $clinic['tagline']) ?></p>
            <p><?= esc($clinic['address']) ?></p>
            <p><?= esc($clinic['phone']) ?> · <?= esc($clinic['email']) ?> · TIN <?= esc($clinic['tin']) ?></p>
        </div>
        <div class="lh-doc">
            <div class="kind"><?= esc($paper['kind']) ?></div>
            <div class="title"><?= esc($paper['title']) ?></div>
            <div class="no">No. <?= esc($paper['no']) ?></div>
        </div>
    </header>
    <div class="rule-thin"></div>

    <dl class="meta">
        <?php foreach ($paper['meta'] as $label => $value): ?>
            <div><dt><?= esc($label) ?></dt><dd><?= esc($value) ?></dd></div>
        <?php endforeach; ?>
    </dl>
    <p class="conf"><?= esc($paper['conf'] ?? 'Confidential · For internal use only') ?></p>
