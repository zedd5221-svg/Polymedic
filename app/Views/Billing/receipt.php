<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Receipt <?= esc($payment['reference_number'] ?? '') ?> · PolyMedic</title>
<style>
    :root {
        --r-ink:#0f172a; --r-muted:#64748b; --r-faint:#94a3b8;
        --r-line:#d7dfea; --r-line-soft:#eef2f7;
        --r-accent:#0d9488; --r-accent-dark:#0f766e; --r-accent-soft:#eefaf7;
    }
    * { box-sizing:border-box; }
    body {
        margin:0; padding:24px; background:#eef2f7;
        font-family:ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
        font-size:12.5px; color:var(--r-ink);
        -webkit-font-smoothing:antialiased;
    }

    /* ---- PAPER ---- */
    .pm-receipt {
        position:relative;
        width:100%; max-width:680px; margin:0 auto;
        padding:32px 36px 26px;
        background:#fff;
        border-radius:6px;
        box-shadow:0 10px 30px rgba(15,23,42,.12);
    }
    /* Perforated top/bottom edges, screen only */
    .pm-receipt::before,
    .pm-receipt::after {
        content:''; position:absolute; left:0; right:0; height:10px;
        background:
            repeating-linear-gradient(135deg, #eef2f7 0 6px, transparent 6px 12px),
            linear-gradient(#fff,#fff);
    }
    .pm-receipt::before { top:-10px; }
    .pm-receipt::after  { bottom:-10px; }

    /* ---- HEADER ---- */
    .r-head {
        display:flex; align-items:flex-start; gap:16px;
        padding-bottom:16px; border-bottom:2px solid var(--r-ink);
    }
    .r-logo {
        width:60px; height:60px; flex-shrink:0;
        border-radius:14px; background:var(--r-accent-soft);
        display:grid; place-items:center;
    }
    .r-logo img { width:42px; height:42px; object-fit:contain; }
    .r-clinic { flex:1; min-width:0; }
    .r-clinic h1 {
        margin:0;
        font-size:16px; font-weight:800;
        letter-spacing:.02em; text-transform:uppercase;
    }
    .r-clinic p {
        margin:2px 0 0; font-size:11px; color:var(--r-muted); line-height:1.45;
    }
    .r-doc { text-align:right; flex-shrink:0; }
    .r-doc .title {
        font-size:11.5px; font-weight:800;
        letter-spacing:.14em; text-transform:uppercase;
        color:var(--r-accent);
    }
    .r-doc .no {
        margin-top:4px;
        font-family:ui-monospace, "SF Mono", Menlo, monospace;
        font-size:14px; font-weight:700; letter-spacing:.02em;
    }
    .r-doc .date { margin-top:2px; font-size:10.5px; color:var(--r-muted); }

    /* ---- META ---- */
    .r-meta {
        display:grid; grid-template-columns:1fr 1fr; gap:12px 22px;
        padding:16px 0; border-bottom:1px solid var(--r-line);
    }
    .r-meta div { min-width:0; }
    .r-meta dt {
        font-size:9.5px; font-weight:700; letter-spacing:.1em;
        text-transform:uppercase; color:var(--r-muted); margin-bottom:3px;
    }
    .r-meta dd { margin:0; font-size:13px; font-weight:600; word-break:break-word; }

    /* ---- ITEMS ---- */
    .r-items { width:100%; border-collapse:collapse; margin-top:16px; }
    .r-items th {
        font-size:9.5px; font-weight:700; letter-spacing:.1em;
        text-transform:uppercase; color:var(--r-muted);
        text-align:left; padding:0 0 8px;
        border-bottom:1px solid var(--r-line);
    }
    .r-items th:last-child,
    .r-items td:last-child { text-align:right; }
    .r-items th:first-child,
    .r-items td:first-child { width:28px; color:var(--r-muted); }
    .r-items td {
        padding:9px 0; border-bottom:1px dotted var(--r-line);
        font-size:12.5px; vertical-align:top;
    }
    .r-items td:last-child { font-variant-numeric:tabular-nums; white-space:nowrap; }
    .r-items tbody tr:last-child td { border-bottom:1px solid var(--r-line); }

    /* ---- TOTALS ---- */
    .r-totals {
        margin-top:14px; margin-left:auto; width:min(320px,100%);
        font-variant-numeric:tabular-nums;
    }
    .r-totals-row {
        display:flex; justify-content:space-between;
        padding:4px 0; font-size:12.5px;
    }
    .r-totals-row.grand {
        border-top:2px solid var(--r-ink);
        margin-top:6px; padding-top:10px;
        font-size:16px; font-weight:800;
    }
    .r-totals-row.change { color:var(--r-accent); font-weight:700; }

    /* ---- WORDS ---- */
    .r-words {
        margin-top:14px; padding:10px 12px;
        background:#f6f9fa; border-left:3px solid var(--r-accent);
        font-size:11.5px; line-height:1.5;
    }
    .r-words span {
        display:block; font-size:9.5px; font-weight:700;
        letter-spacing:.1em; text-transform:uppercase;
        color:var(--r-muted); margin-bottom:2px;
    }

    /* ---- SIGNATURES ---- */
    .r-signs {
        display:grid; grid-template-columns:1fr 1fr; gap:30px;
        margin-top:34px;
    }
    .r-sign {
        border-top:1px solid var(--r-ink); padding-top:5px;
        font-size:10px; letter-spacing:.08em;
        text-transform:uppercase; color:var(--r-muted); text-align:center;
    }
    .r-sign b {
        display:block; font-size:12px;
        letter-spacing:0; text-transform:none;
        color:var(--r-ink); margin-bottom:3px;
        min-height:16px;
    }

    /* ---- FOOTER ---- */
    .r-foot {
        margin-top:22px; padding-top:12px;
        border-top:1px dashed var(--r-line);
        font-size:10.5px; color:var(--r-muted);
        text-align:center; line-height:1.65;
    }
    .r-foot strong { color:var(--r-ink); }

    /* ---- ACTION BAR (screen only) ---- */
    .actions {
        max-width:680px; margin:14px auto 0;
        display:flex; gap:.5rem; justify-content:flex-end;
    }
    .actions button, .actions a {
        height:38px; padding:0 1.1rem;
        background:var(--r-accent); color:#fff;
        border:none; border-radius:8px;
        font-weight:600; font-size:.85rem;
        cursor:pointer; text-decoration:none;
        display:inline-flex; align-items:center; gap:.4rem;
        transition:background .15s, border-color .15s;
    }
    .actions button:hover { background:var(--r-accent-dark); }
    .actions a.secondary {
        background:#fff; color:#334155;
        border:1px solid var(--r-line);
    }
    .actions a.secondary:hover { background:#f8fafc; }

    /* ---- PRINT ---- */
    @media print {
        @page { size:A4 portrait; margin:14mm; }
        body { background:#fff; padding:0; }
        .actions { display:none !important; }
        .pm-receipt {
            box-shadow:none; padding:0; max-width:none; border-radius:0;
        }
        .pm-receipt::before,
        .pm-receipt::after { display:none; }
    }

    @media (max-width:560px) {
        body { padding:14px; }
        .pm-receipt { padding:22px 20px; }
        .r-head { flex-wrap:wrap; }
        .r-doc { text-align:left; width:100%; }
        .r-meta { grid-template-columns:1fr; }
        .r-signs { grid-template-columns:1fr; gap:20px; }
    }
</style>
</head>
<body>

<?php
$when   = !empty($payment['payment_date']) ? strtotime($payment['payment_date']) : time();
$total  = (float) ($payment['total_amount'] ?? 0);
$paid   = (float) ($payment['amount_paid']  ?? $total);
$change = (float) ($payment['change_amount'] ?? 0);

$amountInWords = function ($amount) {
    $ones = ['', 'One','Two','Three','Four','Five','Six','Seven','Eight','Nine',
             'Ten','Eleven','Twelve','Thirteen','Fourteen','Fifteen','Sixteen',
             'Seventeen','Eighteen','Nineteen'];
    $tens = ['', '', 'Twenty','Thirty','Forty','Fifty','Sixty','Seventy','Eighty','Ninety'];
    $under1000 = function ($n) use ($ones, $tens) {
        $o = '';
        if ($n >= 100) { $o .= $ones[(int) ($n / 100)] . ' Hundred'; $n %= 100; if ($n) $o .= ' '; }
        if ($n >= 20)  { $o .= $tens[(int) ($n / 10)]; if ($n % 10) $o .= '-' . $ones[$n % 10]; }
        else if ($n > 0) { $o .= $ones[$n]; }
        return $o;
    };
    $v = round($amount * 100) / 100;
    $whole = (int) floor($v);
    $cents = (int) round(($v - $whole) * 100);
    $scales = [[1000000000, 'Billion'], [1000000, 'Million'], [1000, 'Thousand']];
    $words = '';
    foreach ($scales as [$scale, $name]) {
        if ($whole >= $scale) { $words .= $under1000((int) ($whole / $scale)) . ' ' . $name . ' '; $whole %= $scale; }
    }
    if ($whole > 0) $words .= $under1000($whole);
    $words = trim($words) ?: 'Zero';
    $text = $words . ' Peso' . ($words === 'One' ? '' : 's');
    if ($cents > 0) { $text .= ' and ' . $under1000($cents) . ' Centavo' . ($cents === 1 ? '' : 's'); }
    return $text . ' Only';
};
?>

<div class="pm-receipt">
    <header class="r-head">
        <span class="r-logo"><img src="<?= base_url('assets/images/logo4.png') ?>" alt=""></span>
        <div class="r-clinic">
            <h1><?= esc($clinic['name']) ?></h1>
            <p><?= esc($clinic['tagline']) ?></p>
            <p><?= esc($clinic['address']) ?></p>
            <p><?= esc($clinic['phone']) ?> · <?= esc($clinic['email']) ?></p>
            <p>TIN: <?= esc($clinic['tin']) ?></p>
        </div>
        <div class="r-doc">
            <div class="title">Official Receipt</div>
            <div class="no"><?= esc($payment['reference_number'] ?? '—') ?></div>
            <div class="date"><?= esc(date('F j, Y · g:i A', $when)) ?></div>
        </div>
    </header>

    <dl class="r-meta">
        <div><dt>Received from</dt><dd><?= esc($payment['patient_name'] ?? 'Unknown') ?></dd></div>
        <div><dt>Patient code</dt><dd><?= esc($payment['patient_code'] ?? 'N/A') ?></dd></div>
        <div><dt>Payment method</dt><dd><?= esc(ucfirst($payment['payment_method'] ?? 'cash')) ?></dd></div>
        <div><dt>Received by</dt><dd><?= esc($payment['received_by'] ?? 'Front desk') ?></dd></div>
    </dl>

    <table class="r-items">
        <thead><tr><th>#</th><th>Service description</th><th>Amount</th></tr></thead>
        <tbody>
            <?php if (empty($items)): ?>
                <tr>
                    <td></td>
                    <td style="font-style:italic;color:#64748b;">No itemised services</td>
                    <td>₱<?= number_format($total, 2) ?></td>
                </tr>
            <?php else: foreach ($items as $i => $it): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= esc($it['name']) ?></td>
                    <td><?= $it['price'] === null ? '—' : '₱' . number_format($it['price'], 2) ?></td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>

    <div class="r-totals">
        <div class="r-totals-row grand"><span>Total due</span><span>₱<?= number_format($total, 2) ?></span></div>
        <div class="r-totals-row"><span>Cash tendered</span><span>₱<?= number_format($paid, 2) ?></span></div>
        <?php if ($change > 0): ?>
            <div class="r-totals-row change">
                <span>Change</span><span>₱<?= number_format($change, 2) ?></span>
            </div>
        <?php endif; ?>
    </div>

    <div class="r-words">
        <span>Amount in words</span>
        <?= esc($amountInWords($total)) ?>
    </div>

    <div class="r-signs">
        <div class="r-sign"><b><?= esc($payment['received_by'] ?? '') ?></b>Cashier / Received by</div>
        <div class="r-sign"><b>&nbsp;</b>Patient signature</div>
    </div>

    <footer class="r-foot">
        <strong>This serves as your official receipt.</strong> Please keep it for your records and present it when claiming results.<br>
        Results are released on the schedule advised at the counter. For enquiries call <?= esc($clinic['phone']) ?>.<br>
        Printed <?= esc(date('Y-m-d H:i:s')) ?>
    </footer>
</div>

<div class="actions">
    <a class="secondary" href="javascript:window.close()">
        <i class="bi bi-x-lg" aria-hidden="true"></i> Close
    </a>
    <button type="button" onclick="window.print()">
        <i class="bi bi-printer" aria-hidden="true"></i> Print receipt
    </button>
</div>

<script>window.addEventListener('load', function () { setTimeout(function(){ window.print(); }, 250); });</script>
</body>
</html>