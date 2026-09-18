<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>X-Ray Result · <?= esc($examination['patient_name']) ?></title>
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
    line-height: 1.35;
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
    background: #6d28d9;
    border: 0;
    border-radius: 6px;
    text-decoration: none;
    cursor: pointer;
    box-shadow: 0 1px 3px rgba(0,0,0,0.2);
}

.controls a.secondary,
.controls button.secondary {
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

.p-value { flex: 1 1 auto; word-break: break-word; }
.p-value.strong { font-weight: 700; }

.priority-stat {
    display: inline-block;
    padding: 0.3mm 1.6mm;
    font-weight: 700;
    text-transform: uppercase;
    border: 0.8pt solid #000;
}

/* ================= Report sections ================= */

.section { margin-top: 5mm; page-break-inside: avoid; }

.section-key {
    margin: 0 0 1.4mm;
    padding-bottom: 0.8mm;
    font-size: 9pt;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.6pt;
    border-bottom: 0.8pt solid #000;
}

.section-body {
    margin: 0;
    font-size: 10pt;
    line-height: 1.55;
    text-align: justify;
    word-break: break-word;
}

.section-body.placeholder { color: #555; font-style: italic; }

.impression {
    margin-top: 5mm;
    padding: 3mm 3.5mm;
    border: 1.2pt solid #000;
    page-break-inside: avoid;
}

.impression .section-key {
    border-bottom: 0.4pt solid #000;
}

.impression .section-body { font-weight: 600; }

/* ================= Image plate ================= */

/*
 * Each plate fills one printed page. The frame takes the full
 * height available on the sheet minus the small header block
 * above it, and the image scales to fit inside that frame while
 * preserving aspect ratio. Nothing is cropped; nothing overflows.
 */
.plate {
    margin-top: 5mm;
    page-break-inside: avoid;
    page-break-before: always;
    text-align: center;
}

.plate-head {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    gap: 4mm;
    padding-bottom: 2mm;
    margin-bottom: 3mm;
    font-size: 9pt;
    border-bottom: 0.6pt solid #000;
}

.plate-head-left  { font-weight: 700; }
.plate-head-right { color: #333; white-space: nowrap; }

.plate-frame {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 230mm;
    padding: 2mm;
    border: 0.6pt solid #000;
    background: #000;
}

.plate-img {
    display: block;
    max-width: 100%;
    max-height: 100%;
    width: auto;
    height: auto;
    object-fit: contain;
}

.plate-caption {
    margin: 1.4mm 0 0;
    font-size: 8pt;
    font-style: italic;
    color: #333;
}

/* ================= Signatures ================= */

.sig-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10mm;
    margin-top: 18mm;
    page-break-inside: avoid;
}

.sig-col { text-align: center; font-size: 9pt; }

.sig-line {
    width: 100%;
    max-width: 62mm;
    margin: 0 auto 1.4mm;
    border-top: 0.75pt solid #000;
}

.sig-name {
    font-weight: 700;
    font-size: 9.5pt;
    text-transform: uppercase;
    min-height: 4mm;
}

.sig-sub { margin-top: 0.4mm; font-size: 8pt; }
.sig-lic { margin-top: 0.3mm; font-size: 7.5pt; color: #333; }

.end-mark {
    margin-top: 6mm;
    font-size: 9pt;
    font-weight: 700;
    letter-spacing: 1pt;
    text-align: center;
    text-transform: uppercase;
}

.disclaimer {
    margin-top: 3mm;
    font-size: 7.5pt;
    line-height: 1.4;
    text-align: center;
    color: #333;
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

/* ================= Unreleased safeguard ================= */

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
    .plate-frame { height: 180mm; }
}

@media print {
    .controls { display: none !important; }
    .sheet { padding: 0; box-shadow: none; max-width: none; }

    .section, .impression, .sig-row, .plate { page-break-inside: avoid; }

    .plate { page-break-before: always; }
    .plate-frame { height: 235mm; }
}

@media screen and (max-width: 720px) {
    .sheet { padding: 8mm 6mm; }
    .letterhead { flex-direction: column; text-align: center; gap: 3mm; }
    .patient-grid { grid-template-columns: 1fr; }
    .patient-cell:nth-child(odd) { border-right: 0; }
    .sig-row { grid-template-columns: 1fr; gap: 12mm; }
    .plate-frame { height: 120mm; }
}
</style>
</head>
<body>

<div class="controls">
    <a class="secondary" href="<?= base_url('radiologist/examination/view/' . (int) $examination['id']) ?>">
        Back to report
    </a>
    <button type="button" onclick="window.print()">Print</button>
    <button type="button" class="secondary" onclick="window.close()">Close</button>
</div>

<?php
/* -------- Derived values -------- */
$refNo = 'XR-' . date('Y', strtotime($examination['exam_date'])) . '-'
       . str_pad((string) $examination['id'], 4, '0', STR_PAD_LEFT);

$isReleased = (($examination['status'] ?? '') === 'released');

$patientDisplay = strtoupper(trim((string) $examination['patient_name']));

$sexDisplay = strtoupper(trim((string) ($examination['gender'] ?? '')));
if ($sexDisplay === '' || $sexDisplay === 'N/A') { $sexDisplay = '—'; }

$radiologist = trim((string) ($examination['radiologist_name'] ?? ''));
$radiologist = $radiologist !== '' ? strtoupper($radiologist) : '—';

$referringMd = trim((string) ($examination['doctor_name'] ?? ''));
$referringMd = $referringMd !== '' ? strtoupper($referringMd) : '—';

$patientRef = trim((string) ($appointment['reference_number'] ?? ''));
$patientRef = $patientRef !== '' ? $patientRef : '—';

$priority   = trim((string) ($examination['priority'] ?? ''));
$isUrgent   = in_array(strtolower($priority), ['stat', 'urgent', 'emergency'], true);

$releasedOn = !empty($examination['released_at'])
    ? date('M j, Y g:i A', strtotime($examination['released_at']))
    : '—';

$findings       = trim((string) ($examination['findings'] ?? ''));
$interpretation = trim((string) ($examination['interpretation'] ?? ''));

/*
 * -------- PRC license numbers --------
 *
 * $radiologist_license is supplied by the controller when it can
 * match the signing radiologist to a users row. It is empty when
 * no match is found; the view falls back to a blank underline in
 * that case so a wrong number is never printed.
 *
 * The technologist licence is left as the blank underline for now.
 * Filling it requires a policy decision about whose number should
 * appear (the session user, or a dedicated technologist assigned to
 * the study), so it is left deliberately blank until that is decided.
 */
$radiologistLicence = trim((string) ($radiologist_license ?? ''));

/*
 * -------- Build the full list of image URLs --------
 *
 * Preference order:
 *   1. image_paths (JSON array of paths, one per uploaded image)
 *   2. image_path  (legacy single-column, still used by old rows)
 */
$imageList = [];

if (!empty($examination['image_paths'])) {
    $decoded = json_decode($examination['image_paths'], true);
    if (is_array($decoded)) {
        foreach ($decoded as $p) {
            if (is_string($p) && trim($p) !== '') {
                $imageList[] = trim($p);
            }
        }
    }
}

if (empty($imageList) && !empty($examination['image_path'])) {
    $imageList[] = trim((string) $examination['image_path']);
}

$imageList = array_values(array_unique($imageList));

$imageUrls = [];
foreach ($imageList as $path) {
    if (preg_match('#^https?://#i', $path)) {
        $imageUrls[] = $path;
    } elseif (strpos($path, 'uploads/') !== false) {
        $imageUrls[] = base_url(ltrim(substr($path, strpos($path, 'uploads/')), '/'));
    } else {
        $imageUrls[] = base_url('uploads/xray/' . ltrim($path, '/'));
    }
}

$imageCount = count($imageUrls);
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
                Gov. Gutierrez Ave, Cotabato City 9600<br>
                Tel. (064) 123-4567 &nbsp;·&nbsp; polymedic@example.com
            </p>
            <p class="clinic-accred">Department of Radiology &nbsp;·&nbsp; DOH License No. 0000-00-0000</p>
        </div>
    </div>

    <h2 class="doc-title">Radiologic Examination Report</h2>

    <?php if (!$isReleased): ?>
        <div class="draft-banner">
            Preliminary report — not yet validated or released. Not for clinical use.
        </div>
    <?php endif; ?>

    <!-- ================= PATIENT / STUDY ================= -->
    <div class="patient-box">
        <div class="patient-grid">
            <div class="patient-cell">
                <span class="p-label">Patient Name</span>
                <span class="p-value strong"><?= esc($patientDisplay) ?></span>
            </div>
            <div class="patient-cell">
                <span class="p-label">Accession No.</span>
                <span class="p-value strong"><?= esc($refNo) ?></span>
            </div>

            <div class="patient-cell">
                <span class="p-label">Age / Sex</span>
                <span class="p-value"><?= esc($examination['age']) ?> / <?= esc($sexDisplay) ?></span>
            </div>
            <div class="patient-cell">
                <span class="p-label">Patient Ref.</span>
                <span class="p-value"><?= esc($patientRef) ?></span>
            </div>

            <div class="patient-cell">
                <span class="p-label">Examination</span>
                <span class="p-value strong"><?= esc($examination['exam_type']) ?></span>
            </div>
            <div class="patient-cell">
                <span class="p-label">Date of Exam</span>
                <span class="p-value"><?= date('M j, Y', strtotime($examination['exam_date'])) ?></span>
            </div>

            <div class="patient-cell">
                <span class="p-label">Referring MD</span>
                <span class="p-value"><?= esc($referringMd) ?></span>
            </div>
            <div class="patient-cell">
                <span class="p-label">Priority</span>
                <span class="p-value">
                    <?php if ($isUrgent): ?>
                        <span class="priority-stat"><?= esc($priority) ?></span>
                    <?php else: ?>
                        <?= esc($priority !== '' ? $priority : 'Routine') ?>
                    <?php endif; ?>
                </span>
            </div>

            <div class="patient-cell">
                <span class="p-label">Radiologist</span>
                <span class="p-value"><?= esc($radiologist) ?></span>
            </div>
            <div class="patient-cell">
                <span class="p-label">Date Released</span>
                <span class="p-value"><?= esc($releasedOn) ?></span>
            </div>
        </div>
    </div>

    <!-- ================= CLINICAL INDICATION ================= -->
    <div class="section">
        <p class="section-key">Clinical Indication</p>
        <p class="section-body<?= $referringMd === '—' ? ' placeholder' : '' ?>">
            <?= esc($examination['exam_type']) ?> requested
            <?= $referringMd !== '—' ? 'by ' . esc($referringMd) : 'for diagnostic evaluation' ?>.
        </p>
    </div>

    <!-- ================= TECHNIQUE ================= -->
    <div class="section">
        <p class="section-key">Technique</p>
        <p class="section-body">
            Standard radiographic projections of the
            <?= esc($examination['exam_type']) ?> were obtained on
            <?= date('M j, Y', strtotime($examination['exam_date'])) ?>.
        </p>
    </div>

    <!-- ================= COMPARISON ================= -->
    <div class="section">
        <p class="section-key">Comparison</p>
        <p class="section-body placeholder">No prior studies available for comparison.</p>
    </div>

    <!-- ================= FINDINGS ================= -->
    <div class="section">
        <p class="section-key">Findings</p>
        <?php if ($findings !== ''): ?>
            <p class="section-body"><?= nl2br(esc($findings)) ?></p>
        <?php else: ?>
            <p class="section-body placeholder">No findings recorded.</p>
        <?php endif; ?>
    </div>

    <!-- ================= IMPRESSION ================= -->
    <div class="impression">
        <p class="section-key">Impression</p>
        <?php if ($interpretation !== ''): ?>
            <p class="section-body"><?= nl2br(esc($interpretation)) ?></p>
        <?php else: ?>
            <p class="section-body placeholder">No impression recorded.</p>
        <?php endif; ?>
    </div>

    <!-- ================= SIGNATURES ================= -->
    <div class="sig-row">
        <div class="sig-col">
            <div class="sig-line"></div>
            <div class="sig-name"><?= esc(strtoupper(session()->get('full_name') ?: 'Radiologic Technologist')) ?></div>
            <div class="sig-sub">Radiologic Technologist</div>
            <div class="sig-lic">Lic. No. ____________</div>
        </div>
        <div class="sig-col">
            <div class="sig-line"></div>
            <div class="sig-name"><?= esc($radiologist) ?></div>
            <div class="sig-sub">Radiologist</div>
            <div class="sig-lic">
                Lic. No.
                <?= $radiologistLicence !== ''
                    ? esc($radiologistLicence)
                    : '____________' ?>
            </div>
        </div>
    </div>

    <p class="end-mark">
        <?= $isReleased
            ? '*** End of Report — Electronically Signed ***'
            : '*** Preliminary — Not Validated ***' ?>
    </p>

    <p class="disclaimer">
        This report pertains only to the study identified above and should be
        interpreted together with the patient's clinical findings and history.
    </p>

    <div class="doc-footer">
        <span><?= esc($refNo) ?> &nbsp;·&nbsp; <?= esc($patientDisplay) ?></span>
        <span>Printed <?= date('M j, Y g:i A') ?> &nbsp;·&nbsp; MS-RAD-XR-F-01 Rev.1</span>
    </div>

</div>

<?php if ($imageCount > 0): ?>
    <?php foreach ($imageUrls as $index => $url): ?>
        <div class="sheet">
            <div class="plate-head">
                <span class="plate-head-left">
                    <?= esc($patientDisplay) ?>
                    &nbsp;·&nbsp;
                    <?= esc($refNo) ?>
                </span>
                <span class="plate-head-right">
                    Image <?= (int) ($index + 1) ?> of <?= (int) $imageCount ?>
                </span>
            </div>

            <div class="plate">
                <div class="plate-frame">
                    <img class="plate-img"
                         src="<?= esc($url, 'attr') ?>"
                         alt="Radiographic image <?= (int) ($index + 1) ?> for <?= esc($patientDisplay, 'attr') ?>"
                         onerror="this.closest('.plate').style.display='none'">
                </div>
                <p class="plate-caption">
                    <?= esc($examination['exam_type']) ?> &nbsp;·&nbsp; <?= esc($refNo) ?>
                    &nbsp;·&nbsp; Reference image only; not for primary diagnostic interpretation.
                </p>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

</body>
</html>