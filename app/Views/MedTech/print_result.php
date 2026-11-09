<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Laboratory Result · <?= esc($request['patient_name']) ?></title>
<style>
@page {
    size: A4 portrait;
    margin: 12mm 12mm 16mm;
}

*, *::before, *::after { box-sizing: border-box; }

html, body {
    margin: 0;
    padding: 0;
    background: #ffffff;
    color: #000;
    font-family: "Times New Roman", Times, Georgia, serif;
    font-size: 10pt;
    line-height: 1.3;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}

.sheet {
    position: relative;
    width: 100%;
    max-width: 186mm;
    margin: 0 auto;
}

/* ================= Screen-only controls ================= */

.controls {
    position: fixed;
    top: 12px;
    right: 12px;
    display: flex;
    gap: 8px;
    z-index: 20;
    font-family: system-ui, -apple-system, "Segoe UI", sans-serif;
}

.controls button,
.controls a {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    font-size: 13px;
    font-weight: 600;
    color: #fff;
    background: #0d9488;
    border: 0;
    border-radius: 6px;
    text-decoration: none;
    cursor: pointer;
    box-shadow: 0 1px 3px rgba(0,0,0,0.2);
}

.controls a.secondary {
    background: #fff;
    color: #0f172a;
    border: 1px solid #cbd5e1;
}

/* ================= Letterhead ================= */

.letterhead {
    display: flex;
    align-items: center;
    gap: 6mm;
    padding-bottom: 3mm;
    border-bottom: 2pt solid #000;
}

.letterhead-logo {
    flex: 0 0 auto;
    width: 20mm;
    height: 20mm;
    object-fit: contain;
}

.letterhead-text { flex: 1 1 auto; min-width: 0; }

.clinic-name {
    margin: 0;
    font-size: 16pt;
    font-weight: 700;
    letter-spacing: 0.6pt;
    text-transform: uppercase;
    line-height: 1.1;
}

.clinic-sub {
    margin: 0.6mm 0 0;
    font-size: 8.5pt;
    line-height: 1.35;
}

.clinic-accred {
    margin: 0.8mm 0 0;
    font-size: 7.5pt;
    font-style: italic;
    color: #333;
}

/* ================= Document title ================= */

.doc-title {
    margin: 0;
    padding: 2.4mm 0 2mm;
    font-size: 12pt;
    font-weight: 700;
    letter-spacing: 2pt;
    text-transform: uppercase;
    text-align: center;
    border-bottom: 0.8pt solid #000;
}

/* ================= Patient block ================= */

.patient-box {
    margin-top: 3.5mm;
    border: 0.8pt solid #000;
}

.patient-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
}

.patient-cell {
    display: flex;
    align-items: baseline;
    gap: 2mm;
    padding: 1.5mm 3mm;
    font-size: 9.5pt;
    border-bottom: 0.4pt solid #b0b0b0;
}

.patient-cell:nth-child(odd) { border-right: 0.4pt solid #b0b0b0; }
.patient-grid .patient-cell:nth-last-child(-n+2) { border-bottom: 0; }

.p-label {
    flex: 0 0 30mm;
    font-weight: 700;
    font-size: 8.5pt;
    text-transform: uppercase;
    letter-spacing: 0.2pt;
}

.p-value {
    flex: 1 1 auto;
    word-break: break-word;
}

.p-value.strong { font-weight: 700; }

/* ================= Results table ================= */

/* Analyzer-style results: sans-serif, no grid, flags in their own column */
table.result {
    width: 100%;
    margin-top: 4mm;
    border-collapse: collapse;
    table-layout: fixed;
    font-family: Arial, "Helvetica Neue", Helvetica, sans-serif;
}
col.c-test   { width: 34%; }
col.c-result { width: 17%; }
col.c-flag   { width: 9%; }
col.c-unit   { width: 14%; }
col.c-range  { width: 26%; }

table.result thead th {
    padding: 1.4mm 1.5mm;
    font-size: 8.5pt;
    font-weight: 700;
    text-align: left;
    vertical-align: bottom;
    border-top: 0.8pt solid #000;
    border-bottom: 0.8pt solid #000;
}
table.result thead th.c-unit,
table.result thead th.c-range { text-align: right; }

table.result tbody td {
    padding: 0.55mm 1.5mm;
    font-size: 9pt;
    line-height: 1.3;
    vertical-align: top;
}
table.result tbody tr:first-child td { padding-top: 1.4mm; }

.c-test   { text-align: left; word-break: break-word; }
.c-result { text-align: left; font-variant-numeric: tabular-nums; }
.c-flag   { text-align: left; white-space: nowrap; }
.c-unit   { text-align: right; white-space: nowrap; }
.c-range  { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }

/* Differential counts sit under WBC, as on the analyzer print-out */
tr.is-sub .c-test { padding-left: 4.5mm; }

/* Out of range: value and flag in red (flag letters still read on mono printers) */
tr.is-abn .c-result,
tr.is-abn .c-flag { color: #C8102E; font-weight: 700; }

table.result tr.group-head td {
    padding: 2.4mm 1.5mm 0.6mm;
    font-size: 8.5pt;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.3pt;
    border-bottom: 0.4pt solid #888;
}
table.result tr.group-head:first-child td { padding-top: 1.4mm; }
.group-section { float: right; font-weight: 400; font-style: italic; text-transform: none; letter-spacing: 0; color: #444; }

.table-close { border-top: 0.8pt solid #000; }

/* ================= Legend + interpretation ================= */

.legend {
    margin-top: 2mm;
    font-size: 8pt;
    color: #333;
}

.legend strong { font-weight: 700; }

.narrative {
    margin-top: 5mm;
    page-break-inside: avoid;
}

.narrative-block { margin-bottom: 3.5mm; }

.narrative-key {
    margin: 0 0 1mm;
    font-size: 8.5pt;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.4pt;
    border-bottom: 0.4pt solid #000;
    padding-bottom: 0.6mm;
}

.narrative-body {
    margin: 0;
    font-size: 9.5pt;
    line-height: 1.45;
    text-align: justify;
    word-break: break-word;
}

/* ================= Critical notice ================= */

.critical-notice {
    margin-top: 4mm;
    padding: 2mm 3mm;
    font-size: 8.5pt;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.3pt;
    border: 1pt solid #000;
    page-break-inside: avoid;
}

/* ================= Signature (identical to Radiologist layout) ================= */

.sig-row {
    display: flex;
    justify-content: center;
    margin-top: 22mm;
    page-break-inside: avoid;
}

.sig-col {
    text-align: center;
    font-size: 9pt;
    width: 100%;
    max-width: 70mm;
}

.sig-line {
    width: 100%;
    margin: 0 auto 1.4mm;
    border-top: 0.75pt solid #000;
}

.sig-name {
    font-weight: 700;
    font-size: 9.5pt;
    text-transform: uppercase;
    min-height: 4mm;
    word-break: break-word;
}

/* Matches the Radiologist template exactly:
   plain sentence-case text, no bold, no uppercase, no letter-spacing. */
.sig-sub {
    margin-top: 0.4mm;
    font-size: 8pt;
}

.sig-lic {
    margin-top: 0.3mm;
    font-size: 7.5pt;
    color: #333;
}

.sig-footnote {
    margin-top: 4mm;
    text-align: center;
    font-size: 8pt;
    font-style: italic;
}

.end-mark {
    margin-top: 6mm;
    font-size: 9pt;
    font-weight: 700;
    letter-spacing: 1pt;
    text-align: center;
    text-transform: uppercase;
}

.doc-footer {
    display: flex;
    justify-content: space-between;
    gap: 4mm;
    margin-top: 4mm;
    padding-top: 1.5mm;
    font-size: 7.5pt;
    color: #333;
    border-top: 0.4pt solid #999;
}

/* ================= Draft watermark ================= */

.watermark {
    position: fixed;
    top: 45%;
    left: 50%;
    transform: translate(-50%, -50%) rotate(-30deg);
    font-size: 54pt;
    font-weight: 700;
    letter-spacing: 6pt;
    color: rgba(0, 0, 0, 0.10);
    white-space: nowrap;
    pointer-events: none;
    z-index: 0;
}

.draft-banner {
    margin-bottom: 3mm;
    padding: 2mm 3mm;
    font-size: 9pt;
    font-weight: 700;
    text-align: center;
    text-transform: uppercase;
    letter-spacing: 0.5pt;
    border: 1.2pt solid #000;
}

/* ================= Screen preview ================= */

@media screen {
    body { background: #eef1f4; padding: 24px 16px 40px; }
    .sheet {
        background: #fff;
        padding: 12mm;
        box-shadow: 0 4px 24px rgba(15, 23, 42, 0.14);
        max-width: 210mm;
    }
}

@media print {
    .controls { display: none !important; }
    .sheet { padding: 0; box-shadow: none; max-width: none; }

    thead { display: table-header-group; }
    tfoot { display: table-footer-group; }

    table.result tbody tr { page-break-inside: avoid; }
    table.result tr.group-head { page-break-after: avoid; }

    .sig-row, .narrative, .critical-notice { page-break-inside: avoid; }
}

@media screen and (max-width: 720px) {
    .sheet { padding: 8mm 6mm; }
    .letterhead { flex-direction: column; text-align: center; gap: 3mm; }
    .patient-grid { grid-template-columns: 1fr; }
    .patient-cell:nth-child(odd) { border-right: 0; }
}
</style>
</head>
<body>

<div class="controls">
    <?php /* Admin / Receptionist reprints pass $backUrl so Back returns to their own list. */ ?>
    <a class="secondary" href="<?= esc($backUrl ?? base_url('medtech/request/view/' . (int) $request['id']), 'attr') ?>">
        <?= esc($backLabel ?? 'Back to report') ?>
    </a>
    <button type="button" onclick="window.print()">Print</button>
    <?php $pdfRef = 'LAB-' . date('Y', strtotime($request['request_date'])) . '-' . str_pad((string) $request['id'], 4, '0', STR_PAD_LEFT); ?>
    <?= view('partials/pdf_tools', ['pdf' => [
        'target'   => '.sheet',
        'filename' => $pdfRef . ' ' . ($request['patient_name'] ?? ''),
        'label'    => 'laboratory result',
        'emailTo'  => (string) ($request['email'] ?? ''),
        'subject'  => 'Laboratory result ' . $pdfRef . ' · ' . config('Clinic')->name,
    ]]) ?>
</div>

<?php
/* -------- Derived values -------- */
$refNo = 'LAB-' . date('Y', strtotime($request['request_date'])) . '-'
       . str_pad((string) $request['id'], 4, '0', STR_PAD_LEFT);

$sexDisplay = strtoupper(trim((string) ($request['gender'] ?? '')));
if ($sexDisplay === '' || $sexDisplay === 'N/A') { $sexDisplay = '—'; }

$doctorDisplay = trim((string) ($request['doctor_name'] ?? ''));
$doctorDisplay = $doctorDisplay !== '' ? strtoupper($doctorDisplay) : '—';

$patientDisplay = strtoupper(trim((string) $request['patient_name']));

$isReleased = (($request['status'] ?? '') === 'released');

/*
 * Group rows by their service name. Rows arriving without a
 * service_name are collected under a final "Additional Tests"
 * heading, which matches the on-screen grouping.
 */
$grouped = [];
$groupSection = [];
foreach ($results as $row) {
    $svc = trim((string) ($row['service_name'] ?? ''));
    if ($svc === '') { $svc = 'Additional Tests'; }
    if (!isset($grouped[$svc])) { $grouped[$svc] = []; }
    if (!isset($groupSection[$svc]) && !empty($row['section'])) { $groupSection[$svc] = (string) $row['section']; }
    $grouped[$svc][] = $row;
}

/*
 * Analyzer-style layout: the CBC prints WBC first with its differential
 * indented under it, then platelets, then the red cell line. Other services
 * keep their own order. Indented rows print with a leading "- ".
 */
$printOrder = [
    'White Blood Cell (WBC)', 'Neutrophils', 'Lymphocytes', 'Monocytes', 'Eosinophils', 'Basophils',
    'Platelet Count', 'Red Blood Cell (RBC)', 'Hemoglobin (Hgb)', 'Hematocrit (Hct)',
];
$subTests = ['Neutrophils', 'Lymphocytes', 'Monocytes', 'Eosinophils', 'Basophils'];
$orderOf  = array_flip($printOrder);
foreach ($grouped as $svc => $rows) {
    $pos = [];
    foreach ($rows as $i => $r) { $pos[$i] = $orderOf[(string) $r['test_name']] ?? (100 + $i); }
    uksort($rows, static fn ($a, $b) => $pos[$a] <=> $pos[$b]);
    $grouped[$svc] = array_values($rows);
}
$hasWbc = in_array('White Blood Cell (WBC)', array_column($results, 'test_name'), true);

/* Abnormal / critical tallies drive the legend and the notice. */
$hasAbnormal = false;
$criticalTests = [];
foreach ($results as $row) {
    $f = (string) ($row['flag'] ?? 'normal');
    if (in_array($f, ['high', 'low', 'critical'], true)) { $hasAbnormal = true; }
    if ($f === 'critical') { $criticalTests[] = (string) $row['test_name']; }
}

/* The section title is the first service name in uppercase. If there
   are several, it falls back to a generic heading. */
$firstService = $grouped ? array_key_first($grouped) : '';
$sectionTitle = (count($grouped) === 1 && strcasecmp($firstService, 'Additional Tests') !== 0)
    ? strtoupper($firstService) . ' RESULT'
    : 'LABORATORY TEST RESULT';

/*
 * Signature data.
 *
 * $technologistUser is passed by the controller:
 *   - MedTech::printResult() passes the logged-in medtech
 *   - Admin::printResult()  passes the resolved technologist
 *     for the request (NOT the logged-in admin).
 *
 * The controller is the single source of truth. We deliberately do
 * NOT fall back to session()->get('full_name') here — that fallback
 * was the source of the bug where an admin printing a lab result
 * would see their own name signed as the Medical Technologist.
 *
 * If no technologist can be resolved, we print a bare placeholder
 * label rather than any real name.
 */
$techName = trim((string) ($technologistUser['full_name'] ?? ''));
$techPrc  = trim((string) ($technologistUser['prc_license'] ?? ''));
?>

<?php if (!$isReleased): ?>
    <div class="watermark" aria-hidden="true">NOT VALIDATED</div>
<?php endif; ?>

<div class="sheet">

    <!-- ================= LETTERHEAD ================= -->
    <div class="letterhead">
        <img class="letterhead-logo"
             src="<?= base_url('assets/images/logo4.png') ?>"
             alt=""
             onerror="this.style.display='none'">
        <div class="letterhead-text">
            <h1 class="clinic-name">PolyMedic Diagnostic Center</h1>
            <p class="clinic-sub">
                Cotabato City Maguindanao, Philippines<br>
                Tel. (082) 000-0000 &nbsp;·&nbsp; polymedic@example.com
            </p>
            <p class="clinic-accred">Clinical Laboratory &nbsp;·&nbsp; DOH License No. 0000-00-0000</p>
        </div>
    </div>

    <h2 class="doc-title"><?= esc($sectionTitle) ?></h2>

    <?php if (!$isReleased): ?>
        <div class="draft-banner">
            Preliminary copy — not yet validated or released. Not for clinical use.
        </div>
    <?php endif; ?>

    <!-- ================= PATIENT / SPECIMEN ================= -->
    <div class="patient-box">
        <div class="patient-grid">
            <div class="patient-cell">
                <span class="p-label">Patient Name</span>
                <span class="p-value strong"><?= esc($patientDisplay) ?></span>
            </div>
            <div class="patient-cell">
                <span class="p-label">Lab No.</span>
                <span class="p-value strong"><?= esc($refNo) ?></span>
            </div>

            <div class="patient-cell">
                <span class="p-label">Age / Sex</span>
                <span class="p-value"><?= esc($request['age']) ?> / <?= esc($sexDisplay) ?></span>
            </div>
            <div class="patient-cell">
                <span class="p-label">Date Received</span>
                <span class="p-value"><?= date('M j, Y', strtotime($request['request_date'])) ?></span>
            </div>

            <div class="patient-cell">
                <span class="p-label">Requesting MD</span>
                <span class="p-value"><?= esc($doctorDisplay) ?></span>
            </div>
            <div class="patient-cell">
                <span class="p-label">Date Released</span>
                <span class="p-value">
                    <?= !empty($request['released_at'])
                        ? date('M j, Y', strtotime($request['released_at']))
                        : '—' ?>
                </span>
            </div>
        </div>
    </div>

    <!-- ================= RESULTS TABLE ================= -->
    <table class="result">
        <colgroup>
            <col class="c-test"><col class="c-result"><col class="c-flag"><col class="c-unit"><col class="c-range">
        </colgroup>
        <thead>
            <tr>
                <th class="c-test">Test</th>
                <th class="c-result">Result</th>
                <th class="c-flag">Flag</th>
                <th class="c-unit">Units</th>
                <th class="c-range">Ref. Range</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($grouped)): ?>
            <tr>
                <td colspan="5" style="text-align:center; padding:10mm 0;">
                    No tests recorded for this report.
                </td>
            </tr>
        <?php else: ?>
            <?php foreach ($grouped as $svcName => $rows): ?>
                <?php if (count($grouped) > 1): ?>
                    <tr class="group-head">
                        <td colspan="5"><?= esc($svcName) ?><?php if (!empty($groupSection[$svcName])): ?><span class="group-section"><?= esc($groupSection[$svcName]) ?></span><?php endif; ?></td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($rows as $row):
                    $flag   = (string) ($row['flag'] ?? 'normal');
                    $result = trim((string) ($row['result'] ?? ''));
                    $unit   = trim((string) ($row['unit'] ?? ''));
                    $range  = trim((string) ($row['reference_range'] ?? ''));
                    $name   = (string) $row['test_name'];
                    $isSub  = $hasWbc && in_array($name, $subTests, true);

                    $isHigh     = $flag === 'high';
                    $isLow      = $flag === 'low';
                    $isCritical = $flag === 'critical';
                    $isAbnormal = $isHigh || $isLow || $isCritical;

                    $letter = $isCritical ? '(C)' : ($isHigh ? '(H)' : ($isLow ? '(L)' : ''));
                ?>
                    <tr class="<?= $isAbnormal ? 'is-abn' : '' ?><?= $isSub ? ' is-sub' : '' ?>">
                        <td class="c-test"><?= $isSub ? '- ' : '' ?><?= esc($name) ?></td>
                        <td class="c-result"><?= esc($result !== '' ? $result : '-') ?></td>
                        <td class="c-flag"><?= esc($letter) ?></td>
                        <td class="c-unit"><?= esc($unit) ?></td>
                        <td class="c-range"><?= $range !== '' ? '(' . esc($range) . ')' : '' ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
        <tfoot>
            <tr><td colspan="5" class="table-close" style="padding:0; height:0;"></td></tr>
        </tfoot>
    </table>

    <?php if ($hasAbnormal): ?>
        <p class="legend">
            <strong>Legend:</strong>
            (H) above reference range &nbsp;·&nbsp;
            (L) below reference range &nbsp;·&nbsp;
            (C) critical value
        </p>
    <?php endif; ?>

    <?php if (!empty($criticalTests)): ?>
        <div class="critical-notice">
            Critical value(s) reported:
            <?= esc(implode(', ', $criticalTests)) ?>.
            Attending physician to be notified immediately.
        </div>
    <?php endif; ?>

    <!-- ================= INTERPRETATION ================= -->
    <?php if (!empty($request['findings']) || !empty($request['remarks'])): ?>
        <div class="narrative">
            <?php if (!empty($request['findings'])): ?>
                <div class="narrative-block">
                    <p class="narrative-key">Findings / Interpretation</p>
                    <p class="narrative-body"><?= nl2br(esc($request['findings'])) ?></p>
                </div>
            <?php endif; ?>
            <?php if (!empty($request['remarks'])): ?>
                <div class="narrative-block">
                    <p class="narrative-key">Remarks</p>
                    <p class="narrative-body"><?= nl2br(esc($request['remarks'])) ?></p>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- ================= SIGNATURE (matches Radiologist layout exactly) ================= -->
    <div class="sig-row">
        <div class="sig-col">
            <div class="sig-line"></div>
            <div class="sig-name">
                <?= esc(strtoupper($techName !== '' ? $techName : 'Medical Technologist')) ?>
            </div>
            <div class="sig-sub">Medical Technologist</div>
            <div class="sig-lic">
                Lic. No. <?= esc($techPrc !== '' ? $techPrc : '____________') ?>
            </div>
        </div>
    </div>

    <p class="end-mark">
        <?= $isReleased
            ? '*** End of Report ***'
            : '*** Preliminary — Not Validated ***' ?>
    </p>

    <div class="doc-footer">
        <span><?= esc($refNo) ?> &nbsp;·&nbsp; <?= esc($patientDisplay) ?></span>
        <span>Printed <?= date('M j, Y g:i A') ?> &nbsp;·&nbsp; MS-PAT-LB-F-08 Rev.2</span>
    </div>

</div>

</body>
</html>