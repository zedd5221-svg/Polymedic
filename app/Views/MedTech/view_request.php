<?= $this->extend('layouts/MedTechLayout') ?>

<?= $this->section('pageTitle') ?>Laboratory Findings<?= $this->endSection() ?>

<?= $this->section('medtechContent') ?>

<?php
$isReleased = ($request['status'] === 'released');
$status     = (string) $request['status'];

$statusLabel = [
    'pending'     => 'Pending',
    'in_progress' => 'In progress',
    'draft'       => 'Draft',
    'completed'   => 'Completed',
    'released'    => 'Released',
][$status] ?? ucfirst($status);

$statusTone = [
    'pending'     => 'amber',
    'in_progress' => 'blue',
    'draft'       => 'slate',
    'completed'   => 'green',
    'released'    => 'teal',
][$status] ?? 'slate';

$refNo = 'LAB-' . date('Y') . '-' . str_pad((string) $request['id'], 4, '0', STR_PAD_LEFT);

/*
 * Group the merged result rows back under the service they came from.
 */
$grouped = [];
foreach ($results as $row) {
    $svc = trim((string) ($row['service_name'] ?? ''));
    if ($svc === '') {
        $svc = 'Additional tests';
    }
    if (!isset($grouped[$svc])) {
        $grouped[$svc] = [];
    }
    $grouped[$svc][] = $row;
}

/* Completion, so the header can show real progress rather than a count. */
$totalTests = count($results);
$filledTests = 0;
foreach ($results as $row) {
    if (trim((string) ($row['result'] ?? '')) !== '') {
        $filledTests++;
    }
}
$pct = $totalTests > 0 ? (int) round(($filledTests / $totalTests) * 100) : 0;

/* Abnormal count, surfaced at the top instead of buried in the table. */
$abnormalCount = 0;
foreach ($results as $row) {
    if (in_array($row['flag'] ?? 'normal', ['high', 'low', 'critical'], true)) {
        $abnormalCount++;
    }
}

$flagOptions = ['normal' => 'Normal', 'high' => 'High', 'low' => 'Low', 'critical' => 'Critical'];
$technologist = esc(session()->get('full_name') ?: '—');

/*
 * Gender-based avatar for the hero.
 *
 * Avatar PNGs must exist at:
 *   public/assets/images/man-avatar.png
 *   public/assets/images/woman-avatar.png
 */
$genderRaw = strtolower(trim((string) ($request['gender'] ?? '')));
$isMale    = in_array($genderRaw, ['male', 'm', 'man', 'boy'], true);
$isFemale  = in_array($genderRaw, ['female', 'f', 'woman', 'girl'], true);
?>

<div class="lab<?= $isReleased ? ' lab--locked' : '' ?>">

    <?php if (session()->getFlashdata('success')): ?>
        <div class="lab-msg lab-msg--ok">
            <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
            <span><?= esc(session()->getFlashdata('success')) ?></span>
        </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="lab-msg lab-msg--err">
            <i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i>
            <span><?= esc(session()->getFlashdata('error')) ?></span>
        </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('validation_errors')): ?>
        <div class="lab-msg lab-msg--err">
            <i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i>
            <div>
                <strong>Please fix the following before releasing:</strong>
                <ul>
                    <?php foreach (session()->getFlashdata('validation_errors') as $error): ?>
                        <li><?= esc($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    <?php endif; ?>


    <!-- ============================================================
         HERO
         ============================================================ -->
    <div class="lab-hero">
        <div class="lab-hero-main">

            <?php if ($isMale || $isFemale): ?>
                <span class="lab-hero-avatar lab-hero-avatar--<?= $isFemale ? 'female' : 'male' ?>">
                    <img src="<?= esc(base_url('assets/images/' . ($isFemale ? 'woman-avatar.png' : 'man-avatar.png')), 'attr') ?>"
                         alt=""
                         loading="lazy"
                         decoding="async">
                </span>
            <?php else: ?>
                <div class="lab-hero-icon" aria-hidden="true">
                    <i class="bi bi-clipboard2-pulse"></i>
                </div>
            <?php endif; ?>

            <div class="lab-hero-text">
                <div class="lab-hero-titleline">
                    <h1 class="lab-hero-title"><?= esc($request['patient_name']) ?></h1>
                    <span class="lab-pill lab-pill--<?= esc($statusTone, 'attr') ?>">
                        <span class="lab-pill-dot" aria-hidden="true"></span><?= esc($statusLabel) ?>
                    </span>
                </div>
                <p class="lab-hero-sub">
                    <span class="lab-ref"><?= esc($refNo) ?></span>
                    <span class="lab-dot" aria-hidden="true"></span>
                    <?= esc($request['age']) ?> / <?= esc($request['gender']) ?>
                    <span class="lab-dot" aria-hidden="true"></span>
                    Collected <?= date('M j, Y', strtotime($request['request_date'])) ?>
                </p>
            </div>
        </div>

        <div class="lab-hero-actions">
            <a href="<?= base_url('medtech/requests') ?>" class="lab-btn lab-btn--ghost">
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
                <span>Back</span>
            </a>
            <a href="<?= base_url('medtech/request/print/' . (int) $request['id']) ?>"
               target="_blank" rel="noopener"
               class="lab-btn">
                <i class="bi bi-printer" aria-hidden="true"></i>
                <span>Print</span>
            </a>
            <?php if (!$isReleased): ?>
                <button type="button" class="lab-btn lab-btn--go" onclick="releaseResult(<?= (int) $request['id'] ?>)">
                    <i class="bi bi-check2-circle" aria-hidden="true"></i>
                    <span>Release result</span>
                </button>
            <?php endif; ?>
        </div>
    </div>


    <!-- ============================================================
         PATIENT STRIP
         ============================================================ -->
    <div class="lab-strip">
        <div class="lab-strip-cell">
            <span class="lab-strip-label">Patient</span>
            <span class="lab-strip-value"><?= esc($request['patient_name']) ?></span>
        </div>
        <div class="lab-strip-cell">
            <span class="lab-strip-label">Age / Sex</span>
            <span class="lab-strip-value"><?= esc($request['age']) ?> / <?= esc($request['gender']) ?></span>
        </div>
        <div class="lab-strip-cell">
            <span class="lab-strip-label">Date collected</span>
            <span class="lab-strip-value"><?= date('M j, Y', strtotime($request['request_date'])) ?></span>
        </div>
        <div class="lab-strip-cell">
            <span class="lab-strip-label">Technologist</span>
            <span class="lab-strip-value"><?= $technologist ?></span>
        </div>
        <div class="lab-strip-cell">
            <span class="lab-strip-label">Requesting MD</span>
            <span class="lab-strip-value"><?= esc($request['doctor_name'] ?: '—') ?></span>
        </div>
        <div class="lab-strip-cell">
            <span class="lab-strip-label">Reference</span>
            <span class="lab-strip-value lab-mono"><?= esc($refNo) ?></span>
        </div>
    </div>


    <!-- ============================================================
         REPORT PANEL
         ============================================================ -->
    <div class="lab-panel">

        <div class="lab-panel-head">
            <div class="lab-panel-head-text">
                <h2 class="lab-panel-title">Laboratory Results</h2>
                <span class="lab-panel-count">
                    <?= number_format($totalTests) ?> <?= $totalTests === 1 ? 'test' : 'tests' ?>
                    across
                    <?= number_format(count($grouped)) ?> <?= count($grouped) === 1 ? 'service' : 'services' ?>
                    <?php if ($abnormalCount > 0): ?>
                        <span class="lab-abn-pill">
                            <i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i>
                            <?= $abnormalCount ?> abnormal
                        </span>
                    <?php endif; ?>
                </span>
            </div>

            <?php if (!$isReleased): ?>
                <div class="lab-progress" id="labProgress"
                     data-total="<?= (int) $totalTests ?>"
                     data-filled="<?= (int) $filledTests ?>"
                     role="img"
                     aria-label="<?= (int) $filledTests ?> of <?= (int) $totalTests ?> results entered">
                    <svg viewBox="0 0 44 44" class="lab-ring" aria-hidden="true">
                        <circle class="lab-ring-bg" cx="22" cy="22" r="18"></circle>
                        <circle class="lab-ring-fg" cx="22" cy="22" r="18"
                                style="--pct: <?= (int) $pct ?>"></circle>
                    </svg>
                    <div class="lab-progress-text">
                        <span class="lab-progress-num" id="labProgressNum"><?= (int) $filledTests ?></span>
                        <span class="lab-progress-of">/ <?= (int) $totalTests ?></span>
                    </div>
                </div>
            <?php endif; ?>
        </div>


        <?php if ($isReleased): ?>

            <!-- ================= VIEW ONLY ================= -->
            <div class="lab-table-wrap">
                <table class="lab-table">
                    <thead>
                        <tr>
                            <th>Test</th>
                            <th class="lab-right">Result</th>
                            <th>Unit</th>
                            <th>Reference range</th>
                            <th class="lab-center">Flag</th>
                        </tr>
                    </thead>
                    <?php foreach ($grouped as $svcName => $rows): ?>
                        <tbody class="lab-group">
                            <tr class="lab-group-head">
                                <td colspan="5">
                                    <span class="lab-group-bar" aria-hidden="true"></span>
                                    <span class="lab-group-name"><?= esc($svcName) ?></span>
                                    <span class="lab-group-count">
                                        <?= count($rows) ?> <?= count($rows) === 1 ? 'test' : 'tests' ?>
                                    </span>
                                </td>
                            </tr>
                            <?php foreach ($rows as $result): ?>
                                <?php
                                $flag  = (string) ($result['flag'] ?? 'normal');
                                $isBad = in_array($flag, ['high', 'low', 'critical'], true);
                                ?>
                                <tr class="lab-row<?= $isBad ? ' is-bad' : '' ?>">
                                    <td data-label="Test" class="lab-cell-name"><?= esc($result['test_name']) ?></td>
                                    <td data-label="Result" class="lab-right <?= $isBad ? 'lab-bad' : '' ?>"><?= esc($result['result']) ?></td>
                                    <td data-label="Unit" class="lab-cell-soft"><?= esc($result['unit']) ?></td>
                                    <td data-label="Reference range" class="lab-cell-soft"><?= esc($result['reference_range']) ?></td>
                                    <td data-label="Flag" class="lab-center">
                                        <?php if ($flag === 'high'): ?>
                                            <span class="lab-flag lab-flag--high"><i class="bi bi-arrow-up"></i> High</span>
                                        <?php elseif ($flag === 'low'): ?>
                                            <span class="lab-flag lab-flag--low"><i class="bi bi-arrow-down"></i> Low</span>
                                        <?php elseif ($flag === 'critical'): ?>
                                            <span class="lab-flag lab-flag--critical"><i class="bi bi-exclamation-triangle-fill"></i> Critical</span>
                                        <?php elseif ($flag === 'normal'): ?>
                                            <span class="lab-flag lab-flag--ok">Normal</span>
                                        <?php else: ?>
                                            <span class="lab-flag lab-flag--ok">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    <?php endforeach; ?>
                </table>
            </div>

            <?php if (!empty($request['findings']) || !empty($request['remarks'])): ?>
                <div class="lab-notes">
                    <?php if (!empty($request['findings'])): ?>
                        <div class="lab-note">
                            <span class="lab-note-key">Findings</span>
                            <p><?= nl2br(esc($request['findings'])) ?></p>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($request['remarks'])): ?>
                        <div class="lab-note">
                            <span class="lab-note-key">Remarks</span>
                            <p><?= nl2br(esc($request['remarks'])) ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="lab-sign">
                <div class="lab-sign-col">
                    <div class="lab-sign-line"></div>
                    <div class="lab-sign-name"><?= esc(session()->get('full_name') ?: 'Medical Technologist') ?></div>
                    <div class="lab-sign-role">Medical Technologist</div>
                </div>
                <div class="lab-sign-col">
                    <div class="lab-sign-line"></div>
                    <div class="lab-sign-name"><?= esc($request['doctor_name'] ?: 'Pathologist') ?></div>
                    <div class="lab-sign-role">Pathologist</div>
                </div>
            </div>

        <?php else: ?>

            <!-- ================= EDIT ================= -->
            <form action="<?= base_url('medtech/request/release-directly/' . (int) $request['id']) ?>"
                  method="POST"
                  id="mainResultForm"
                  novalidate>

                <?= csrf_field() ?>
                <input type="hidden" name="submit_action" id="submitAction" value="save">

                <div class="lab-table-wrap">
                    <table class="lab-table lab-table--edit" id="resultsTable">
                        <thead>
                            <tr>
                                <th class="lab-w-test">Test</th>
                                <th class="lab-right lab-w-result">Result <span class="lab-req" title="Required">*</span></th>
                                <th class="lab-w-unit">Unit <span class="lab-req" title="Required">*</span></th>
                                <th class="lab-w-range">Reference range <span class="lab-req" title="Required">*</span></th>
                                <th class="lab-w-flag">Flag</th>
                                <th class="lab-right lab-w-action"><span class="visually-hidden">Actions</span></th>
                            </tr>
                        </thead>

                        <?php foreach ($grouped as $svcName => $rows): ?>
                            <tbody class="lab-group" data-service="<?= esc($svcName, 'attr') ?>">
                                <tr class="lab-group-head">
                                    <td colspan="6">
                                        <button type="button" class="lab-group-toggle" data-toggle-group
                                                aria-expanded="true"
                                                aria-label="Collapse <?= esc($svcName, 'attr') ?>">
                                            <i class="bi bi-chevron-down" aria-hidden="true"></i>
                                        </button>
                                        <span class="lab-group-bar" aria-hidden="true"></span>
                                        <span class="lab-group-name"><?= esc($svcName) ?></span>
                                        <span class="lab-group-count">
                                            <span data-count><?= count($rows) ?></span>
                                            <?= count($rows) === 1 ? 'test' : 'tests' ?>
                                        </span>
                                        <span class="lab-group-progress" data-group-progress></span>
                                    </td>
                                </tr>

                                <?php foreach ($rows as $result): ?>
                                    <?php
                                    $flag = (string) ($result['flag'] ?? 'normal');

                                    /*
                                     * Alignment: numeric values are right-aligned,
                                     * text values stay left. Empty fields default
                                     * to left because typing a word is more common
                                     * in an empty field than typing a number.
                                     */
                                    $existingResult = trim((string) ($result['result'] ?? ''));
                                    $looksNumeric   = $existingResult !== '' && is_numeric($existingResult);

                                    /*
                                     * Placeholder: match the shape of the row.
                                     *   has a unit              -> "0.0"
                                     *   has a reference range   -> "e.g. <range>"
                                     *   neither                 -> "Type result"
                                     */
                                    $rowUnit      = trim((string) ($result['unit'] ?? ''));
                                    $rowReference = trim((string) ($result['reference_range'] ?? ''));

                                    if ($rowUnit !== '') {
                                        $resultPlaceholder = '0.0';
                                    } elseif ($rowReference !== '') {
                                        $resultPlaceholder = 'e.g. ' . $rowReference;
                                    } else {
                                        $resultPlaceholder = 'Type result';
                                    }
                                    ?>
                                    <tr class="lab-row is-flag-<?= esc($flag, 'attr') ?>">
                                        <td data-label="Test">
                                            <input type="text" class="lab-input" name="test_name[]"
                                                   value="<?= esc($result['test_name']) ?>"
                                                   data-required autocomplete="off"
                                                   autocorrect="off" autocapitalize="off" spellcheck="false"
                                                   enterkeyhint="next">
                                            <input type="hidden" name="service_name[]"
                                                   value="<?= esc($svcName, 'attr') ?>">
                                        </td>
                                        <td data-label="Result">
                                            <input type="text"
                                                   class="lab-input result-input<?= $looksNumeric ? ' lab-input--num' : '' ?>"
                                                   name="result[]"
                                                   value="<?= esc($result['result']) ?>"
                                                   placeholder="<?= esc($resultPlaceholder, 'attr') ?>"
                                                   data-required autocomplete="off"
                                                   autocorrect="off" autocapitalize="off" spellcheck="false"
                                                   enterkeyhint="next"
                                                   inputmode="text">
                                        </td>
                                        <td data-label="Unit">
                                            <input type="text" class="lab-input" name="unit[]"
                                                   value="<?= esc($result['unit']) ?>"
                                                   data-required autocomplete="off"
                                                   autocorrect="off" autocapitalize="off" spellcheck="false"
                                                   enterkeyhint="next" list="labUnits">
                                        </td>
                                        <td data-label="Reference range">
                                            <input type="text" class="lab-input" name="reference_range[]"
                                                   value="<?= esc($result['reference_range']) ?>"
                                                   data-required autocomplete="off"
                                                   autocorrect="off" autocapitalize="off" spellcheck="false"
                                                   enterkeyhint="next">
                                        </td>
                                        <td data-label="Flag">
                                            <select class="lab-select lab-select--<?= esc($flag, 'attr') ?>" name="flag[]">
                                                <?php foreach ($flagOptions as $val => $lbl): ?>
                                                    <option value="<?= esc($val, 'attr') ?>" <?= $flag === $val ? 'selected' : '' ?>><?= esc($lbl) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td data-label="Action" class="lab-right">
                                            <button type="button" class="lab-trash" data-remove-row
                                                    aria-label="Remove this row">
                                                <i class="bi bi-trash3" aria-hidden="true"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        <?php endforeach; ?>
                    </table>

                    <div class="lab-empty" id="emptyRow" <?= !empty($grouped) ? 'hidden' : '' ?>>
                        <i class="bi bi-clipboard-plus" aria-hidden="true"></i>
                        <p>No tests matched the ordered services. Use <strong>Add row</strong> below to enter them manually.</p>
                    </div>
                </div>

                <div class="lab-add-row">
                    <button type="button" class="lab-btn lab-btn--ghost" id="labAddRow">
                        <i class="bi bi-plus-lg" aria-hidden="true"></i>
                        <span>Add row</span>
                    </button>
                    <span class="lab-hint" id="labHint"></span>
                </div>

                <div class="lab-notes-edit">
                    <div class="lab-field">
                        <label for="vrFindings">Findings / Interpretation <span class="lab-req" title="Required">*</span></label>
                        <textarea id="vrFindings" name="findings" rows="5"
                                  data-required data-autogrow
                                  autocorrect="off" autocapitalize="sentences" spellcheck="true"
                                  placeholder="Enter laboratory findings..."><?= esc($request['findings'] ?? '') ?></textarea>
                    </div>
                    <div class="lab-field">
                        <label for="vrRemarks">Remarks</label>
                        <textarea id="vrRemarks" name="remarks" rows="5"
                                  data-autogrow
                                  autocorrect="off" autocapitalize="sentences" spellcheck="true"
                                  placeholder="Enter any remarks..."><?= esc($request['remarks'] ?? '') ?></textarea>
                    </div>
                </div>

                <div class="lab-actions">
                    <div class="lab-actions-status" id="labStatus">
                        <i class="bi bi-info-circle" aria-hidden="true"></i>
                        <span>Fill in every result, then release or save as draft.</span>
                    </div>
                    <div class="lab-actions-btns">
                        <button type="submit" class="lab-btn lab-btn--ghost" data-action="save">
                            <i class="bi bi-save" aria-hidden="true"></i>
                            <span>Save as draft</span>
                        </button>
                        <button type="submit" class="lab-btn lab-btn--ghost" data-action="complete">
                            <i class="bi bi-check2" aria-hidden="true"></i>
                            <span>Complete</span>
                        </button>
                        <button type="submit" class="lab-btn lab-btn--go" data-action="release">
                            <i class="bi bi-check2-all" aria-hidden="true"></i>
                            <span>Release result</span>
                        </button>
                    </div>
                </div>

            </form>

            <datalist id="labUnits">
                <option value="mg/dL"></option>
                <option value="g/dL"></option>
                <option value="mmol/L"></option>
                <option value="µmol/L"></option>
                <option value="IU/L"></option>
                <option value="U/L"></option>
                <option value="ng/dL"></option>
                <option value="ng/mL"></option>
                <option value="pg/mL"></option>
                <option value="µIU/mL"></option>
                <option value="10^9/L"></option>
                <option value="10^12/L"></option>
                <option value="%"></option>
                <option value="fL"></option>
                <option value="pg"></option>
                <option value="K/uL"></option>
                <option value="M/uL"></option>
                <option value="uIU/mL"></option>
                <option value="ug/dL"></option>
            </datalist>

            <template id="labRowTemplate">
                <tr class="lab-row is-flag-normal">
                    <td data-label="Test">
                        <input type="text" class="lab-input" name="test_name[]"
                               placeholder="Test name" data-required autocomplete="off"
                               autocorrect="off" autocapitalize="off" spellcheck="false"
                               enterkeyhint="next">
                        <input type="hidden" name="service_name[]" value="">
                    </td>
                    <td data-label="Result">
                        <input type="text" class="lab-input result-input" name="result[]"
                               placeholder="Type result"
                               data-required autocomplete="off"
                               autocorrect="off" autocapitalize="off" spellcheck="false"
                               enterkeyhint="next"
                               inputmode="text">
                    </td>
                    <td data-label="Unit">
                        <input type="text" class="lab-input" name="unit[]"
                               data-required autocomplete="off"
                               autocorrect="off" autocapitalize="off" spellcheck="false"
                               enterkeyhint="next" list="labUnits">
                    </td>
                    <td data-label="Reference range">
                        <input type="text" class="lab-input" name="reference_range[]"
                               data-required autocomplete="off"
                               autocorrect="off" autocapitalize="off" spellcheck="false"
                               enterkeyhint="next">
                    </td>
                    <td data-label="Flag">
                        <select class="lab-select lab-select--normal" name="flag[]">
                            <?php foreach ($flagOptions as $val => $lbl): ?>
                                <option value="<?= esc($val, 'attr') ?>"><?= esc($lbl) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td data-label="Action" class="lab-right">
                        <button type="button" class="lab-trash" data-remove-row aria-label="Remove this row">
                            <i class="bi bi-trash3" aria-hidden="true"></i>
                        </button>
                    </td>
                </tr>
            </template>

            <template id="labGroupTemplate">
                <tbody class="lab-group" data-service="Additional tests">
                    <tr class="lab-group-head">
                        <td colspan="6">
                            <button type="button" class="lab-group-toggle" data-toggle-group
                                    aria-expanded="true" aria-label="Collapse group">
                                <i class="bi bi-chevron-down" aria-hidden="true"></i>
                            </button>
                            <span class="lab-group-bar" aria-hidden="true"></span>
                            <span class="lab-group-name">Additional tests</span>
                            <span class="lab-group-count"><span data-count>0</span> tests</span>
                            <span class="lab-group-progress" data-group-progress></span>
                        </td>
                    </tr>
                </tbody>
            </template>

        <?php endif; ?>

    </div>

</div>


<style>
/* =========================================================
   LABORATORY REPORT
   ========================================================= */

.lab {
    --lab-ink:         #0f172a;
    --lab-text:        #334155;
    --lab-muted:       #64748b;
    --lab-faint:       #94a3b8;
    --lab-line:        #e2e8f0;
    --lab-line-soft:   #f1f5f9;
    --lab-surface:     #ffffff;
    --lab-canvas:      #f8fafc;
    --lab-rail:        #f9fafb;

    --lab-accent:      #0d9488;
    --lab-accent-dark: #0f766e;
    --lab-accent-soft: #e6fbf6;

    --lab-danger:      #dc2626;
    --lab-danger-soft: #fef2f2;
    --lab-amber:       #b45309;
    --lab-amber-soft:  #fef3c7;
    --lab-green:       #047857;
    --lab-green-soft:  #ecfdf5;
    --lab-blue:        #1d4ed8;
    --lab-blue-soft:   #eff6ff;

    --lab-radius:      14px;
    --lab-radius-sm:   9px;
    --lab-mono:        ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;

    --lab-shadow:      0 1px 2px rgba(15, 23, 42, 0.04);

    color: var(--lab-text);
    -webkit-font-smoothing: antialiased;
    width: 100%;
    max-width: 100%;
    min-width: 0;
    display: block;
    box-sizing: border-box;
}

.lab *,
.lab *::before,
.lab *::after { box-sizing: border-box; }

.lab *:focus-visible {
    outline: 2px solid var(--lab-accent);
    outline-offset: 2px;
    border-radius: 4px;
}

.lab .visually-hidden {
    position: absolute; width: 1px; height: 1px;
    padding: 0; margin: -1px; overflow: hidden;
    clip: rect(0 0 0 0); white-space: nowrap; border: 0;
}

.lab-mono { font-family: var(--lab-mono); }

/* ---------- Messages ---------- */

.lab-msg {
    display: flex;
    align-items: flex-start;
    gap: 0.65rem;
    padding: 0.8rem 1rem;
    margin-bottom: 1rem;
    font-size: 0.85rem;
    line-height: 1.5;
    border-radius: var(--lab-radius-sm);
}

.lab-msg > i:first-child { margin-top: 0.15rem; }
.lab-msg > div { min-width: 0; }
.lab-msg strong { display: block; margin-bottom: 0.35rem; }
.lab-msg ul { margin: 0.3rem 0 0; padding-left: 1.1rem; }

.lab-msg--ok  { background: var(--lab-green-soft);  color: #166534; border: 1px solid #bbf7d0; }
.lab-msg--err { background: var(--lab-danger-soft); color: #991b1b; border: 1px solid #fecaca; }

/* ---------- Hero ---------- */

.lab-hero {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1.25rem;
    flex-wrap: wrap;
    padding: 1.1rem 1.25rem;
    margin-bottom: 0.85rem;
    background:
        radial-gradient(120% 140% at 0% 0%, #f0fdfa 0%, rgba(240, 253, 250, 0) 55%),
        var(--lab-surface);
    border: 1px solid var(--lab-line);
    border-radius: var(--lab-radius);
    box-shadow: var(--lab-shadow);
}

.lab-hero-main {
    display: flex;
    align-items: center;
    gap: 0.9rem;
    min-width: 0;
}

.lab-hero-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 46px;
    height: 46px;
    flex-shrink: 0;
    font-size: 1.35rem;
    color: var(--lab-accent-dark);
    background: var(--lab-accent-soft);
    border: 1px solid rgba(13, 148, 136, 0.18);
    border-radius: var(--lab-radius-sm);
}

.lab-hero-avatar {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 46px;
    height: 46px;
    flex-shrink: 0;
    overflow: hidden;
    border-radius: 50%;
    box-shadow: inset 0 0 0 1px rgba(15, 23, 42, 0.06);
}

.lab-hero-avatar--male   {
    background: #eaf2fe;
    box-shadow: inset 0 0 0 1px rgba(29, 78, 216, 0.12);
}

.lab-hero-avatar--female {
    background: #fce9ee;
    box-shadow: inset 0 0 0 1px rgba(179, 46, 80, 0.12);
}

.lab-hero-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.lab-hero-text { min-width: 0; }

.lab-hero-titleline {
    display: flex;
    align-items: center;
    gap: 0.55rem;
    flex-wrap: wrap;
}

.lab-hero-title {
    margin: 0;
    font-size: 1.2rem;
    font-weight: 700;
    letter-spacing: -0.02em;
    color: var(--lab-ink);
    overflow-wrap: anywhere;
}

.lab-hero-sub {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.4rem;
    margin: 0.2rem 0 0;
    font-size: 0.8rem;
    color: var(--lab-muted);
}

.lab-dot {
    width: 3px;
    height: 3px;
    border-radius: 50%;
    background: var(--lab-faint);
}

.lab-ref {
    font-family: var(--lab-mono);
    font-size: 0.76rem;
    font-weight: 600;
    color: var(--lab-ink);
}

.lab-hero-actions {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
}

/* ---------- Status pill ---------- */

.lab-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.2rem 0.6rem;
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.02em;
    border-radius: 999px;
    white-space: nowrap;
}

.lab-pill-dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }

.lab-pill--amber { background: var(--lab-amber-soft);  color: var(--lab-amber); }
.lab-pill--blue  { background: var(--lab-blue-soft);   color: var(--lab-blue); }
.lab-pill--green { background: var(--lab-green-soft);  color: var(--lab-green); }
.lab-pill--teal  { background: var(--lab-accent-soft); color: var(--lab-accent-dark); }
.lab-pill--slate { background: var(--lab-line-soft);   color: var(--lab-muted); }

/* ---------- Buttons ---------- */

.lab-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.45rem;
    height: 36px;
    padding: 0 0.9rem;
    font-size: 0.8125rem;
    font-weight: 600;
    line-height: 1;
    color: var(--lab-ink);
    background: var(--lab-surface);
    border: 1px solid var(--lab-line);
    border-radius: var(--lab-radius-sm);
    box-shadow: 0 1px 1px rgba(15, 23, 42, 0.03);
    white-space: nowrap;
    text-decoration: none;
    cursor: pointer;
    transition: background-color 0.15s ease, border-color 0.15s ease,
                color 0.15s ease, transform 0.12s ease;
}

.lab-btn:hover { background: var(--lab-canvas); border-color: #cbd5e1; color: var(--lab-ink); }
.lab-btn:active { transform: translateY(1px); }
.lab-btn:disabled { opacity: 0.55; cursor: not-allowed; transform: none; }
.lab-btn i { font-size: 0.9em; }

.lab-btn--ghost { background: transparent; border-color: var(--lab-line); color: var(--lab-muted); }
.lab-btn--ghost:hover { background: var(--lab-line-soft); color: var(--lab-ink); }

.lab-btn--go {
    color: #ffffff;
    background: var(--lab-accent);
    border-color: var(--lab-accent);
    box-shadow: 0 1px 2px rgba(13, 148, 136, 0.28);
}

.lab-btn--go:hover { background: var(--lab-accent-dark); border-color: var(--lab-accent-dark); color: #ffffff; }

/* ---------- Patient strip ---------- */

.lab-strip {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem 1.25rem;
    padding: 1rem 1.25rem;
    margin-bottom: 0.85rem;
    background: var(--lab-surface);
    border: 1px solid var(--lab-line);
    border-radius: var(--lab-radius);
    box-shadow: var(--lab-shadow);
}

.lab-strip-cell { display: flex; flex-direction: column; gap: 0.2rem; min-width: 0; }

.lab-strip-label {
    font-size: 0.66rem;
    font-weight: 700;
    letter-spacing: 0.07em;
    text-transform: uppercase;
    color: var(--lab-faint);
}

.lab-strip-value {
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--lab-ink);
    overflow-wrap: anywhere;
}

/* ---------- Panel ---------- */

.lab-panel {
    background: var(--lab-surface);
    border: 1px solid var(--lab-line);
    border-radius: var(--lab-radius);
    box-shadow: var(--lab-shadow);
    overflow: hidden;
}

.lab-panel-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 1.1rem 1.25rem;
    border-bottom: 1px solid var(--lab-line);
}

.lab-panel-head-text { min-width: 0; }

.lab-panel-title {
    margin: 0 0 0.15rem;
    font-size: 1rem;
    font-weight: 700;
    letter-spacing: -0.01em;
    color: var(--lab-ink);
}

.lab-panel-count {
    display: inline-flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.45rem;
    font-size: 0.78rem;
    color: var(--lab-muted);
}

.lab-abn-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    padding: 0.12rem 0.5rem;
    font-size: 0.7rem;
    font-weight: 700;
    color: var(--lab-danger);
    background: var(--lab-danger-soft);
    border: 1px solid #fecaca;
    border-radius: 999px;
}

/* ---------- Progress ring ---------- */

.lab-progress {
    position: relative;
    flex-shrink: 0;
    width: 52px;
    height: 52px;
}

.lab-ring { width: 100%; height: 100%; transform: rotate(-90deg); }

.lab-ring circle {
    fill: none;
    stroke-width: 4;
    stroke-linecap: round;
}

.lab-ring-bg { stroke: var(--lab-line); }

.lab-ring-fg {
    stroke: var(--lab-accent);
    stroke-dasharray: 113.1;
    stroke-dashoffset: calc(113.1 - (113.1 * var(--pct, 0) / 100));
    transition: stroke-dashoffset 0.4s ease;
}

.lab-progress-text {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1px;
    font-size: 0.62rem;
    font-variant-numeric: tabular-nums;
    color: var(--lab-muted);
}

.lab-progress-num { font-size: 0.8rem; font-weight: 700; color: var(--lab-ink); }

/* ---------- Table ---------- */

.lab-table-wrap { width: 100%; min-width: 0; overflow-x: auto; }

.lab-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.85rem;
}

.lab-table thead th {
    position: sticky;
    top: 0;
    z-index: 3;
    padding: 0.65rem 0.9rem;
    font-size: 0.66rem;
    font-weight: 700;
    letter-spacing: 0.07em;
    text-transform: uppercase;
    text-align: left;
    color: var(--lab-faint);
    background: var(--lab-canvas);
    border-bottom: 1px solid var(--lab-line);
    white-space: nowrap;
}

.lab-table td {
    padding: 0.55rem 0.9rem;
    border-bottom: 1px solid var(--lab-line-soft);
    vertical-align: middle;
}

.lab-table--edit td { padding: 0.4rem 0.6rem; }

.lab-right  { text-align: right; }
.lab-center { text-align: center; }

.lab-w-test   { width: 24%; }
.lab-w-result { width: 16%; }
.lab-w-unit   { width: 14%; }
.lab-w-range  { width: 20%; }
.lab-w-flag   { width: 16%; }
.lab-w-action { width: 56px; }

.lab-cell-name { font-weight: 600; color: var(--lab-ink); }
.lab-cell-soft { color: var(--lab-muted); font-size: 0.8rem; }

.lab-bad { font-weight: 700; color: var(--lab-danger); }
.lab-row.is-bad { background: #fffbfb; }

.lab-req { color: var(--lab-danger); font-weight: 700; }

/* ---------- Service groups ---------- */

.lab-group-head td {
    position: sticky;
    top: 33px;
    z-index: 2;
    padding: 0.55rem 0.9rem !important;
    background: linear-gradient(90deg, #f6fdfb 0%, var(--lab-canvas) 60%);
    border-top: 1px solid var(--lab-line);
    border-bottom: 1px solid var(--lab-line);
}

.lab-group:first-of-type .lab-group-head td { border-top: 0; }

.lab-group-bar {
    display: inline-block;
    width: 3px;
    height: 13px;
    margin-right: 0.5rem;
    vertical-align: -2px;
    background: var(--lab-accent);
    border-radius: 2px;
}

.lab-group-name {
    font-size: 0.76rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: var(--lab-accent-dark);
}

.lab-group-count {
    margin-left: 0.5rem;
    font-size: 0.7rem;
    color: var(--lab-faint);
    font-variant-numeric: tabular-nums;
}

.lab-group-progress {
    margin-left: 0.5rem;
    font-size: 0.7rem;
    font-weight: 600;
    color: var(--lab-faint);
}

.lab-group-progress.is-done { color: var(--lab-green); }

.lab-group-toggle {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 20px;
    height: 20px;
    margin-right: 0.15rem;
    vertical-align: -4px;
    font-size: 0.7rem;
    color: var(--lab-muted);
    background: none;
    border: 0;
    border-radius: 4px;
    cursor: pointer;
    transition: transform 0.18s ease, background-color 0.15s ease;
}

.lab-group-toggle:hover { background: rgba(15, 23, 42, 0.06); }
.lab-group.is-collapsed .lab-group-toggle { transform: rotate(-90deg); }
.lab-group.is-collapsed .lab-row { display: none; }

/* ---------- Inputs ---------- */

.lab-input,
.lab-select,
.lab-field textarea {
    width: 100%;
    font-size: 0.82rem;
    color: var(--lab-ink);
    background: var(--lab-surface);
    border: 1px solid var(--lab-line);
    border-radius: var(--lab-radius-sm);
    transition: border-color 0.15s ease, box-shadow 0.15s ease, background-color 0.15s ease;
}

.lab-input,
.lab-select { height: 34px; padding: 0 0.6rem; }

.lab-input::placeholder,
.lab-field textarea::placeholder { color: #cbd5e1; font-style: italic; }

.lab-input:hover,
.lab-select:hover,
.lab-field textarea:hover { border-color: #cbd5e1; }

.lab-input:focus,
.lab-select:focus,
.lab-field textarea:focus {
    outline: none;
    border-color: var(--lab-accent);
    box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.12);
}

/* Numeric values right-aligned; text values stay left-aligned. */
.lab-input--num { text-align: right; font-variant-numeric: tabular-nums; font-weight: 600; }

.lab-input--num.is-filled { background: #fbfffe; border-color: #cdeae4; }

.lab-input.is-invalid,
.lab-field textarea.is-invalid {
    border-color: var(--lab-danger);
    background: var(--lab-danger-soft);
}

.lab-select {
    appearance: none;
    padding-right: 1.8rem;
    font-weight: 600;
    cursor: pointer;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%2364748b'%3E%3Cpath d='M4.5 6.5 8 10l3.5-3.5z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 0.45rem center;
    background-size: 14px;
}

.lab-select--normal   { color: var(--lab-muted); }
.lab-select--high     { color: var(--lab-danger); background-color: var(--lab-danger-soft); border-color: #fecaca; }
.lab-select--low      { color: var(--lab-blue);   background-color: var(--lab-blue-soft);   border-color: #bfdbfe; }
.lab-select--critical {
    color: #ffffff;
    background-color: var(--lab-danger);
    border-color: var(--lab-danger);
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23ffffff'%3E%3Cpath d='M4.5 6.5 8 10l3.5-3.5z'/%3E%3C/svg%3E");
}

.lab-row.is-flag-high td,
.lab-row.is-flag-low  td { background: #fcfdff; }
.lab-row.is-flag-critical td { background: #fff5f5; }

/* ---------- Trash ---------- */

.lab-trash {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 30px;
    height: 30px;
    font-size: 0.8rem;
    color: var(--lab-faint);
    background: none;
    border: 1px solid transparent;
    border-radius: var(--lab-radius-sm);
    cursor: pointer;
    transition: background-color 0.15s ease, color 0.15s ease, border-color 0.15s ease;
}

.lab-trash:hover { color: var(--lab-danger); background: var(--lab-danger-soft); border-color: #fecaca; }

/* ---------- Empty ---------- */

.lab-empty {
    padding: 2.75rem 1rem;
    text-align: center;
    color: var(--lab-muted);
    background: var(--lab-canvas);
}

.lab-empty i { display: block; margin-bottom: 0.5rem; font-size: 1.6rem; color: var(--lab-faint); }
.lab-empty p { margin: 0; font-size: 0.82rem; }

/* ---------- Add row ---------- */

.lab-add-row {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    border-top: 1px solid var(--lab-line-soft);
}

.lab-hint { font-size: 0.75rem; color: var(--lab-faint); }

/* ---------- Notes ---------- */

.lab-notes,
.lab-notes-edit {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 1rem;
    padding: 1.1rem 1.25rem;
    border-top: 1px solid var(--lab-line);
}

.lab-notes-edit { background: var(--lab-canvas); }

.lab-note { display: flex; flex-direction: column; gap: 0.35rem; min-width: 0; }

.lab-note-key {
    font-size: 0.66rem;
    font-weight: 700;
    letter-spacing: 0.07em;
    text-transform: uppercase;
    color: var(--lab-faint);
}

.lab-note p {
    margin: 0;
    font-size: 0.85rem;
    line-height: 1.6;
    color: var(--lab-text);
    overflow-wrap: anywhere;
}

.lab-field { display: flex; flex-direction: column; gap: 0.35rem; min-width: 0; }

.lab-field label { font-size: 0.78rem; font-weight: 600; color: var(--lab-text); }

.lab-field textarea {
    padding: 0.6rem 0.7rem;
    line-height: 1.55;
    resize: vertical;
    min-height: 104px;
}

/* ---------- Flags ---------- */

.lab-flag {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.16rem 0.5rem;
    font-size: 0.71rem;
    font-weight: 700;
    border-radius: 999px;
    white-space: nowrap;
}

.lab-flag--ok       { background: var(--lab-line-soft);   color: var(--lab-muted); }
.lab-flag--high     { background: var(--lab-danger-soft); color: var(--lab-danger); }
.lab-flag--low      { background: var(--lab-blue-soft);   color: var(--lab-blue); }
.lab-flag--critical { background: var(--lab-danger);      color: #ffffff; }

/* ---------- Signatures ---------- */

.lab-sign {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 2rem;
    padding: 2.5rem 1.25rem 1.5rem;
    border-top: 1px solid var(--lab-line);
}

.lab-sign-col { text-align: center; }
.lab-sign-line { height: 1px; margin-bottom: 0.5rem; background: var(--lab-ink); }
.lab-sign-name { font-size: 0.85rem; font-weight: 700; color: var(--lab-ink); }
.lab-sign-role { font-size: 0.72rem; color: var(--lab-muted); }

/* ---------- Action bar ---------- */

.lab-actions {
    position: sticky;
    bottom: 0;
    z-index: 4;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
    padding: 0.85rem 1.25rem;
    border-top: 1px solid var(--lab-line);
    background: rgba(255, 255, 255, 0.92);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
}

.lab-actions-status {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.8rem;
    color: var(--lab-muted);
}

.lab-actions-status.is-warn { color: var(--lab-amber); }
.lab-actions-status.is-ready { color: var(--lab-green); }

.lab-actions-btns { display: flex; gap: 0.5rem; flex-wrap: wrap; }

/* ---------- Responsive ---------- */

@media (max-width: 900px) {
    .lab-hero { flex-direction: column; align-items: stretch; }
    .lab-hero-actions { width: 100%; }
    .lab-hero-actions .lab-btn { flex: 1; }
}

@media (max-width: 768px) {
    .lab-table,
    .lab-table thead,
    .lab-table tbody,
    .lab-table th,
    .lab-table td,
    .lab-table tr { display: block; width: 100%; }

    .lab-table thead { display: none; }

    .lab-group-head td {
        position: static;
        padding: 0.6rem 0.75rem !important;
        border-radius: 0;
    }

    .lab-row {
        margin: 0.6rem 0.75rem;
        padding: 0.5rem;
        border: 1px solid var(--lab-line);
        border-radius: var(--lab-radius-sm);
        background: var(--lab-surface);
        box-shadow: var(--lab-shadow);
    }

    .lab-table td {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        padding: 0.35rem;
        border-bottom: 0;
        text-align: left;
    }

    .lab-table td::before {
        content: attr(data-label);
        flex-shrink: 0;
        font-size: 0.66rem;
        font-weight: 700;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: var(--lab-faint);
    }

    .lab-table td.lab-right { text-align: right; }
    .lab-input, .lab-select { max-width: 60%; }

    .lab-strip { padding: 0.9rem 1rem; }
    .lab-panel-head { flex-direction: row; align-items: flex-start; }

    .lab-actions { flex-direction: column; align-items: stretch; }
    .lab-actions-btns { width: 100%; }
    .lab-actions-btns .lab-btn { flex: 1; }
}

@media (max-width: 480px) {
    .lab-hero-title { font-size: 1.05rem; }
    .lab-hero-icon,
    .lab-hero-avatar { width: 40px; height: 40px; }
    .lab-hero-icon { font-size: 1.15rem; }
    .lab-input, .lab-select { max-width: 55%; }
    .lab-notes, .lab-notes-edit { padding: 1rem; }
}

@media print {
    .lab-hero-actions,
    .lab-actions,
    .lab-add-row,
    .lab-progress,
    .lab-group-toggle,
    .lab-trash,
    .lab-msg,
    .lab-w-action,
    td[data-label="Action"] { display: none !important; }

    .lab-hero,
    .lab-strip,
    .lab-panel { border: 0; box-shadow: none; background: none; }

    .lab-table thead th { position: static; background: none; color: #000; border-bottom: 1px solid #000; }
    .lab-group-head td { position: static; background: none; border-top: 1px solid #000; }
    .lab-table td { border-bottom: 1px solid #ddd; }
    .lab-bad { color: #000; text-decoration: underline; }
}

@media (prefers-reduced-motion: reduce) {
    .lab *, .lab *::before, .lab *::after {
        transition-duration: 0.01ms !important;
        animation-duration: 0.01ms !important;
    }
}

.lab-hero, .lab-hero-text, .lab-strip, .lab-strip-cell,
.lab-panel, .lab-panel-head, .lab-panel-head-text,
.lab-notes, .lab-notes-edit, .lab-field,
.lab-actions, .lab-actions-status, .lab-msg, .lab-msg > div { min-width: 0; }
</style>


<script>
(function () {
    'use strict';

    var form = document.getElementById('mainResultForm');
    if (!form) { return; }

    var table    = document.getElementById('resultsTable');
    var rowTpl   = document.getElementById('labRowTemplate');
    var groupTpl = document.getElementById('labGroupTemplate');
    var addBtn   = document.getElementById('labAddRow');
    var emptyBox = document.getElementById('emptyRow');
    var hintEl   = document.getElementById('labHint');
    var statusEl = document.getElementById('labStatus');
    var progress = document.getElementById('labProgress');
    var progNum  = document.getElementById('labProgressNum');
    var ringFg   = progress ? progress.querySelector('.lab-ring-fg') : null;
    var actionEl = document.getElementById('submitAction');

    var dirty = false;
    var submitting = false;

    /* Toggle the numeric alignment class based on the current value. */
    function syncAlignment(el) {
        var value = el.value.trim();
        var numeric = value !== '' && !isNaN(parseFloat(value)) && isFinite(value);
        el.classList.toggle('lab-input--num', numeric);
    }

    function refresh() {
        var rows = table.querySelectorAll('tr.lab-row');
        var total = rows.length;
        var filled = 0;

        rows.forEach(function (row) {
            var res = row.querySelector('input[name="result[]"]');
            if (res) {
                var has = res.value.trim() !== '';
                res.classList.toggle('is-filled', has);
                if (has) { filled++; }
            }
        });

        table.querySelectorAll('tbody.lab-group').forEach(function (group) {
            var gRows = group.querySelectorAll('tr.lab-row');
            var gFilled = 0;

            gRows.forEach(function (row) {
                var res = row.querySelector('input[name="result[]"]');
                if (res && res.value.trim() !== '') { gFilled++; }
            });

            var counter = group.querySelector('[data-count]');
            if (counter) { counter.textContent = gRows.length; }

            var prog = group.querySelector('[data-group-progress]');
            if (prog) {
                var done = gRows.length > 0 && gFilled === gRows.length;
                prog.textContent = gRows.length ? (gFilled + '/' + gRows.length + ' entered') : '';
                prog.classList.toggle('is-done', done);
            }

            if (gRows.length === 0) { group.remove(); }
        });

        if (emptyBox) { emptyBox.hidden = total > 0; }
        if (hintEl) { hintEl.textContent = total === 0 ? 'Add at least one test before releasing.' : ''; }

        if (progNum) { progNum.textContent = filled; }
        if (progress) {
            progress.dataset.filled = filled;
            progress.dataset.total = total;
            progress.setAttribute('aria-label', filled + ' of ' + total + ' results entered');
        }
        if (ringFg) {
            ringFg.style.setProperty('--pct', total > 0 ? Math.round((filled / total) * 100) : 0);
        }

        if (statusEl) {
            var span = statusEl.querySelector('span');
            statusEl.classList.remove('is-warn', 'is-ready');

            if (total === 0) {
                statusEl.classList.add('is-warn');
                span.textContent = 'No tests yet. Add a row before releasing.';
            } else if (filled < total) {
                statusEl.classList.add('is-warn');
                span.textContent = (total - filled) + ' of ' + total + ' results still empty.';
            } else {
                statusEl.classList.add('is-ready');
                span.textContent = 'All results entered. Ready to release.';
            }
        }
    }

    function addRow() {
        var group = table.querySelector('tbody.lab-group[data-service="Additional tests"]');

        if (!group) {
            group = groupTpl.content.firstElementChild.cloneNode(true);
            table.appendChild(group);
        }

        var service = group.getAttribute('data-service') || 'Additional tests';
        var row = rowTpl.content.firstElementChild.cloneNode(true);

        var hidden = row.querySelector('input[name="service_name[]"]');
        if (hidden) { hidden.value = service; }

        group.appendChild(row);
        group.classList.remove('is-collapsed');

        refresh();
        dirty = true;

        var first = row.querySelector('input[name="test_name[]"]');
        if (first) { first.focus(); }
    }

    if (addBtn) { addBtn.addEventListener('click', addRow); }

    table.addEventListener('click', function (e) {
        var toggle = e.target.closest('[data-toggle-group]');
        if (toggle) {
            var grp = toggle.closest('tbody.lab-group');
            var collapsed = grp.classList.toggle('is-collapsed');
            toggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
            return;
        }

        var del = e.target.closest('[data-remove-row]');
        if (!del) { return; }

        var row = del.closest('tr');
        var name = row.querySelector('input[name="test_name[]"]');

        if (name && name.value.trim() !== '' &&
            !confirm('Remove "' + name.value.trim() + '" from this report?')) {
            return;
        }

        row.remove();
        refresh();
        dirty = true;
    });

    table.addEventListener('change', function (e) {
        var sel = e.target.closest('select[name="flag[]"]');
        if (!sel) { return; }

        sel.className = 'lab-select lab-select--' + sel.value;
        var row = sel.closest('tr');
        if (row) { row.className = 'lab-row is-flag-' + sel.value; }
    });

    /*
     * Live sync of the result alignment. Runs on every input, no debounce
     * because the check is trivial.
     */
    form.addEventListener('input', function (e) {
        dirty = true;

        var el = e.target;

        if (el.name === 'result[]') {
            syncAlignment(el);
            refresh();
        }

        if (el.classList.contains('is-invalid')) {
            el.classList.remove('is-invalid');
        }
    });

    /*
     * On load: ensure any pre-filled numeric result is right-aligned even
     * if the server-side class missed it.
     */
    form.querySelectorAll('.result-input').forEach(syncAlignment);

    form.querySelectorAll('[data-autogrow]').forEach(function (ta) {
        var grow = function () {
            ta.style.height = 'auto';
            ta.style.height = Math.max(ta.scrollHeight, 104) + 'px';
        };
        ta.addEventListener('input', grow);
        grow();
    });

    function validateForRelease() {
        var bad = [];

        form.querySelectorAll('.is-invalid').forEach(function (el) {
            el.classList.remove('is-invalid');
        });

        if (table.querySelectorAll('tr.lab-row').length === 0) {
            alert('Add at least one test row before releasing this report.');
            return false;
        }

        form.querySelectorAll('[data-required]').forEach(function (el) {
            if (el.value.trim() === '') {
                el.classList.add('is-invalid');
                bad.push(el);
            }
        });

        if (bad.length > 0) {
            var grp = bad[0].closest('tbody.lab-group');
            if (grp) { grp.classList.remove('is-collapsed'); }

            alert('Fill in every highlighted field before releasing. ' +
                  bad.length + ' field' + (bad.length === 1 ? ' is' : 's are') + ' still empty.');
            bad[0].focus();
            bad[0].scrollIntoView({ block: 'center', behavior: 'smooth' });
            return false;
        }

        return true;
    }

    /*
     * Submit handler.
     *
     * Do not disable the button that triggered the submit inside the
     * same click handler; the browser cancels the form submit if the
     * triggering element is disabled when the default action runs.
     * Defer the disable to the next tick.
     */
    form.querySelectorAll('button[type="submit"][data-action]').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            var action = btn.dataset.action;

            if (action === 'release') {
                if (!validateForRelease()) { e.preventDefault(); return; }
                if (!confirm('Release this result? It becomes final and visible to the receptionist.')) {
                    e.preventDefault();
                    return;
                }
            }

            actionEl.value = action;
            submitting = true;

            setTimeout(function () {
                form.querySelectorAll('button[type="submit"]').forEach(function (b) {
                    if (b !== btn) { b.disabled = true; }
                });
            }, 0);
        });
    });

    window.addEventListener('beforeunload', function (e) {
        if (!dirty || submitting) { return; }
        e.preventDefault();
        e.returnValue = '';
    });

    refresh();

    window.addResultRow = addRow;
    window.removeRow = function (btn) {
        var row = btn.closest('tr');
        if (row) { row.remove(); refresh(); dirty = true; }
    };
})();

function releaseResult(id) {
    if (confirm('Release this result? This will make it available for printing.')) {
        window.location.href = '<?= base_url('medtech/request/release/') ?>' + id;
    }
}
</script>

<?= $this->endSection() ?>