<?php
/*
 * Printable request slip for a diagnostic request that has not been
 * released yet. Shared by Admin and Receptionist (printRequest()).
 *
 * Expects: $request (diagnostic_requests row), $type ('lab'|'xray'), $clinic
 */
$isXray   = ($type ?? 'lab') === 'xray';
$services = array_values(array_filter(array_map('trim', explode(',', (string) ($request['services'] ?? '')))));
$priority = strtolower((string) ($request['priority'] ?? 'routine')) === 'stat' ? 'STAT' : 'Routine';
$status   = ucwords(str_replace('_', ' ', (string) ($request['status'] ?? 'pending')));
$fmtDate  = static fn ($d, $f = 'M d, Y') => $d ? date($f, strtotime($d)) : '—';
$clinic   = $clinic ?? config('Clinic')->toArray();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Request <?= esc($request['reference_number'] ?? '') ?> · PolyMedic</title>
<style>
    :root { --ink:#0f172a; --muted:#64748b; --line:#d7dfea; --accent:#0d9488; --accent-soft:#eefaf7; }
    * { box-sizing:border-box; }
    body {
        margin:0; padding:24px; background:#eef2f7; color:var(--ink);
        font-family:ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; font-size:12.5px;
    }
    .slip { max-width:680px; margin:0 auto; padding:32px 36px 26px; background:#fff; border-radius:6px; box-shadow:0 10px 30px rgba(15,23,42,.12); }
    .head { display:flex; gap:16px; align-items:flex-start; padding-bottom:16px; border-bottom:2px solid var(--ink); }
    .logo { width:60px; height:60px; border-radius:14px; background:var(--accent-soft); display:grid; place-items:center; flex-shrink:0; }
    .logo img { width:42px; height:42px; object-fit:contain; }
    .clinic { flex:1; min-width:0; }
    .clinic h1 { margin:0; font-size:16px; font-weight:800; letter-spacing:.02em; text-transform:uppercase; }
    .clinic p { margin:2px 0 0; font-size:11px; color:var(--muted); line-height:1.45; }
    .doc { text-align:right; }
    .doc h2 { margin:0; font-size:13px; letter-spacing:.08em; text-transform:uppercase; color:var(--accent); }
    .doc p { margin:4px 0 0; font-weight:700; font-variant-numeric:tabular-nums; }
    .meta { display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:10px 24px; margin:18px 0; }
    .meta div { border-bottom:1px solid var(--line); padding-bottom:6px; }
    .meta dt { font-size:10px; text-transform:uppercase; letter-spacing:.06em; color:var(--muted); }
    .meta dd { margin:2px 0 0; font-weight:600; }
    table { width:100%; border-collapse:collapse; margin-top:6px; }
    th, td { text-align:left; padding:8px 6px; border-bottom:1px solid var(--line); }
    th { font-size:10px; text-transform:uppercase; letter-spacing:.06em; color:var(--muted); }
    .badge { display:inline-block; padding:2px 8px; border-radius:999px; font-size:10.5px; font-weight:700; background:var(--accent-soft); color:var(--accent); }
    .badge.stat { background:#fee2e2; color:#b91c1c; }
    .note { margin-top:18px; padding:10px 12px; background:#f8fafc; border-radius:6px; color:var(--muted); font-size:11.5px; }
    .signs { display:grid; grid-template-columns:1fr 1fr; gap:32px; margin-top:42px; }
    .signs div { border-top:1px solid var(--ink); padding-top:6px; text-align:center; font-size:11px; color:var(--muted); }
    .signs b { display:block; color:var(--ink); font-size:12px; }
    .actions { max-width:680px; margin:16px auto 0; text-align:right; }
    .actions { display:flex; justify-content:flex-end; gap:8px; }
    .actions button, .actions a { border:0; border-radius:8px; background:var(--accent); color:#fff; padding:9px 16px; font-weight:600; font-size:12.5px; cursor:pointer; text-decoration:none; }
    .actions a { background:#fff; color:var(--ink); border:1px solid var(--line); }
    @media (max-width:560px) { body { padding:12px; } .slip { padding:22px 18px; } .meta { grid-template-columns:1fr; } }
    @media print { body { background:#fff; padding:0; } .slip { box-shadow:none; max-width:none; } .actions { display:none; } }
</style>
</head>
<body>
<main class="slip">
    <header class="head">
        <span class="logo"><img src="<?= base_url('assets/images/logo4.png') ?>" alt=""></span>
        <div class="clinic">
            <h1><?= esc($clinic['name']) ?></h1>
            <p><?= esc($clinic['tagline']) ?><br><?= esc($clinic['address']) ?><br><?= esc($clinic['phone']) ?> · <?= esc($clinic['email']) ?></p>
        </div>
        <div class="doc">
            <h2><?= $isXray ? 'X-Ray Request' : 'Laboratory Request' ?></h2>
            <p><?= esc($request['reference_number'] ?? ('#' . (int) ($request['id'] ?? 0))) ?></p>
        </div>
    </header>

    <dl class="meta">
        <div><dt>Patient</dt><dd><?= esc($request['patient_name'] ?? '—') ?></dd></div>
        <div><dt>Patient code</dt><dd><?= esc(($request['patient_code'] ?? '') ?: '—') ?></dd></div>
        <div><dt>Age / Sex</dt><dd><?= esc(($request['age'] ?? '—') . ' / ' . ($request['gender'] ?? '—')) ?></dd></div>
        <div><dt>Request date</dt><dd><?= esc($fmtDate($request['request_date'] ?? $request['created_at'] ?? null)) ?></dd></div>
        <div><dt>Requesting physician</dt><dd><?= esc(($request['doctor_name'] ?? '') !== '' ? $request['doctor_name'] : '—') ?></dd></div>
        <div><dt>Status</dt><dd><?= esc($status) ?> <span class="badge<?= $priority === 'STAT' ? ' stat' : '' ?>"><?= $priority ?></span></dd></div>
    </dl>

    <table>
        <thead><tr><th style="width:40px">#</th><th><?= $isXray ? 'Examination' : 'Test' ?></th></tr></thead>
        <tbody>
        <?php if ($services === []): ?>
            <tr><td colspan="2">No services listed.</td></tr>
        <?php else: foreach ($services as $i => $svc): ?>
            <tr><td><?= $i + 1 ?></td><td><?= esc($svc) ?></td></tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>

    <p class="note">This is a request slip, not a result. The final report can be printed once the result has been released.</p>

    <div class="signs">
        <div><b><?= esc(session()->get('full_name') ?? '') ?></b>Prepared by</div>
        <div><b>&nbsp;</b>Patient signature</div>
    </div>
</main>
<div class="actions">
    <?php if (! empty($backUrl)): ?>
        <a href="<?= esc($backUrl, 'attr') ?>"><?= esc($backLabel ?? 'Back') ?></a>
    <?php endif; ?>
    <button type="button" onclick="window.print()">Print</button>
    <?php $pdfRef = (string) ($request['reference_number'] ?? ('#' . (int) ($request['id'] ?? 0))); ?>
    <?= view('partials/pdf_tools', ['pdf' => [
        'target'   => '.slip',
        'filename' => $pdfRef . ' ' . ($request['patient_name'] ?? ''),
        'label'    => ($isXray ? 'X-ray' : 'laboratory') . ' request slip',
        'emailTo'  => (string) ($request['email'] ?? ''),
        'subject'  => ($isXray ? 'X-ray' : 'Laboratory') . ' request ' . $pdfRef . ' · ' . $clinic['name'],
    ]]) ?>
</div>
</body>
</html>
