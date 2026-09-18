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

table.result {
    width: 100%;
    margin-top: 4mm;
    border-collapse: collapse;
    table-layout: fixed;
}

table.result thead th {
    padding: 1.8mm 2mm;
    font-size: 8.5pt;
    font-weight: 700;
    text-align: left;
    text-transform: uppercase;
    letter-spacing: 0.3pt;
    vertical-align: bottom;
    border-top: 1pt solid #000;
    border-bottom: 1pt solid #000;
}

table.result thead th.c-test   { width: 36%; }
table.result thead th.c-result { width: 16%; text-align: right; }
table.result thead th.c-unit   { width: 14%; }
table.result thead th.c-range  { width: 34%; text-align: right; }

table.result tbody td {
    padding: 1.3mm 2mm;
    font-size: 9.5pt;
    vertical-align: top;
    border-bottom: 0.4pt solid #d5d5d5;
}

.c-test   { text-align: left; word-break: break-word; }
.c-result { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
.c-unit   { text-align: left; white-space: nowrap; }
.c-range  { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }

table.result tr.group-head td {
    padding: 2mm 2mm 1mm;
    font-size: 8.5pt;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.4pt;
    background: #ececec;
    border-top: 0.6pt solid #000;
    border-bottom: 0.6pt solid #000;
}

.flag {
    display: inline-block;
    min-width: 4mm;
    margin-right: 1mm;
    font-weight: 700;
    text-align: left;
}

.value-abnormal { font-weight: 700; }

.table-close { border-top: 1pt solid #000; }

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

/* ================= Signatures ================= */

.sig-row {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 6mm;
    margin-top: 16mm;
    page-break-inside: avoid;
}

.sig-col {
    text-align: center;
    font-size: 9pt;
}

.sig-name {
    font-weight: 700;
    font-size: 9pt;
    text-transform: uppercase;
    line-height: 1.25;
    min-height: 4.2mm;
    word-break: break-word;
}

.sig-lic {
    margin-top: 0.6mm;
    font-size: 8pt;
    letter-spacing: 0.1pt;
}

.sig-line {
    width: 100%;
    max-width: 54mm;
    margin: 1.4mm auto 1.2mm;
    border-top: 0.75pt solid #000;
}

.sig-sub {
    font-size: 8pt;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.4pt;
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
    .sig-row { grid-template-columns: 1fr; gap: 12mm; }
}
</style>
</head>
<body>

<div class="controls">
    <a class="secondary" href="<?= base_url('medtech/request/view/' . (int) $request['id']) ?>">
        Back to report
    </a>
    <button type="button" onclick="window.print()">Print</button>
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
foreach ($results as $row) {
    $svc = trim((string) ($row['service_name'] ?? ''));
    if ($svc === '') { $svc = 'Additional Tests'; }
    if (!isset($grouped[$svc])) { $grouped[$svc] = []; }
    $grouped[$svc][] = $row;
}

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
$sectionTitle = count($grouped) === 1
    ? strtoupper($firstService) . ' RESULT'
    : 'LABORATORY REPORT';

/*
 * Signature row data.
 *
 * $technologistUser is passed by MedTech::printResult() and carries
 * the currently logged-in user's record, including prc_license.
 * $pathologistUser is null for now because there is no pathologist
 * role in the users table. The third column falls back to the
 * requesting doctor's name and leaves the PRC line blank.
 */
$techName = trim((string) ($technologistUser['full_name'] ?? session()->get('full_name') ?? ''));
$techPrc  = trim((string) ($technologistUser['prc_license'] ?? ''));

$pathName = trim((string) ($pathologistUser['full_name'] ?? $doctorDisplay));
$pathPrc  = trim((string) ($pathologistUser['prc_license'] ?? ''));
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
                Davao City, Davao del Sur, Philippines<br>
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
        <thead>
            <tr>
                <th class="c-test">Test Name</th>
                <th class="c-result">Result</th>
                <th class="c-unit">Unit</th>
                <th class="c-range">Reference Range</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($grouped)): ?>
            <tr>
                <td colspan="4" style="text-align:center; padding:10mm 0;">
                    No tests recorded for this report.
                </td>
            </tr>
        <?php else: ?>
            <?php foreach ($grouped as $svcName => $rows): ?>
                <?php if (count($grouped) > 1): ?>
                    <tr class="group-head">
                        <td colspan="4"><?= esc(strtoupper($svcName)) ?></td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($rows as $row):
                    $flag   = (string) ($row['flag'] ?? 'normal');
                    $result = trim((string) ($row['result'] ?? ''));
                    $unit   = trim((string) ($row['unit'] ?? ''));
                    $range  = trim((string) ($row['reference_range'] ?? ''));

                    $isHigh     = $flag === 'high';
                    $isLow      = $flag === 'low';
                    $isCritical = $flag === 'critical';
                    $isAbnormal = $isHigh || $isLow || $isCritical;

                    $letter = $isCritical ? '**' : ($isHigh ? 'H' : ($isLow ? 'L' : ''));
                ?>
                    <tr>
                        <td class="c-test"><?= esc($row['test_name']) ?></td>
                        <td class="c-result<?= $isAbnormal ? ' value-abnormal' : '' ?>">
                            <?php if ($isAbnormal): ?><span class="flag"><?= esc($letter) ?></span><?php endif; ?><?= esc($result !== '' ? $result : '—') ?>
                        </td>
                        <td class="c-unit"><?= esc($unit !== '' ? $unit : '—') ?></td>
                        <td class="c-range"><?= esc($range !== '' ? $range : '—') ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
        <tfoot>
            <tr><td colspan="4" class="table-close" style="padding:0; height:0;"></td></tr>
        </tfoot>
    </table>

    <?php if ($hasAbnormal): ?>
        <p class="legend">
            <strong>Legend:</strong>
            H = above reference range &nbsp;·&nbsp;
            L = below reference range &nbsp;·&nbsp;
            ** = critical value
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

    <!-- ================= SIGNATURES ================= -->
    <div class="sig-row">
        <div class="sig-col">
            <div class="sig-name">
                <?= esc(strtoupper($techName !== '' ? $techName : 'Medical Technologist')) ?>
            </div>
            <div class="sig-lic">
                PRC LIC # <?= esc($techPrc !== '' ? $techPrc : '____________') ?>
            </div>
            <div class="sig-line"></div>
            <div class="sig-sub">Medical Technologist</div>
        </div>

        <div class="sig-col">
            <div class="sig-name">&nbsp;</div>
            <div class="sig-lic">PRC LIC # ____________</div>
            <div class="sig-line"></div>
            <div class="sig-sub">Medical Technologist</div>
        </div>

        <div class="sig-col">
            <div class="sig-name">
                <?= esc(strtoupper($pathName !== '' ? $pathName : 'Pathologist')) ?>
            </div>
            <div class="sig-lic">
                PRC LIC # <?= esc($pathPrc !== '' ? $pathPrc : '____________') ?>
            </div>
            <div class="sig-line"></div>
            <div class="sig-sub">Pathologist</div>
        </div>
    </div>

    <p class="sig-footnote">
        <?= $isReleased ? '*** Results Electronically Signed ***' : '' ?>
    </p>

    <p class="end-mark">
        <?= $isReleased
            ? '*** End of Report — Electronically Signed ***'
            : '*** Preliminary — Not Validated ***' ?>
    </p>

    <div class="doc-footer">
        <span><?= esc($refNo) ?> &nbsp;·&nbsp; <?= esc($patientDisplay) ?></span>
        <span>Printed <?= date('M j, Y g:i A') ?> &nbsp;·&nbsp; MS-PAT-LB-F-08 Rev.2</span>
    </div>

</div>

</body>
</html>