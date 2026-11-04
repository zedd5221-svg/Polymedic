<?= $this->extend('Layouts/MedTechLayout') ?>

<?= $this->section('pageTitle') ?>Laboratory Findings<?= $this->endSection() ?>

<?= $this->section('medtechContent') ?>

<?php
/* ------------------------------------------------------------------
   Laboratory request: one summary card (who + request facts), then
   the results panel with the entry progress ring.
   ------------------------------------------------------------------ */
$isReleased = ($request['status'] === 'released');
$status     = (string) $request['status'];

$statusLabel = [
    'pending'     => 'Pending',
    'in_progress' => 'In progress',
    'draft'       => 'Draft',
    'completed'   => 'Completed',
    'released'    => 'Released',
][$status] ?? ucfirst($status);

/* Same tones as the Lab requests list */
$statusTone = [
    'pending'     => 'pending',
    'in_progress' => 'progress',
    'draft'       => 'draft',
    'completed'   => 'completed',
    'released'    => 'released',
][$status] ?? 'draft';

$refNo = 'LAB-' . date('y') . '-' . str_pad((string) $request['id'], 4, '0', STR_PAD_LEFT);

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

/* The technologist on the request; the signed-in user until one is stamped. */
$technologist = trim((string) ($request['med_tech_name'] ?? '')) ?: trim((string) (session()->get('full_name') ?? ''));

/* Payment state passed by the controller. */
$isPaid = !empty($isPaid ?? false);

$nameParts = preg_split('/\s+/', trim((string) ($request['patient_name'] ?? '')));
$initials  = mb_strtoupper(mb_substr($nameParts[0] ?? '', 0, 1) . (count($nameParts) > 1 ? mb_substr(end($nameParts), 0, 1) : '')) ?: '?';

$demo = implode(' · ', array_filter([
    ($request['age'] ?? '') !== '' ? $request['age'] . ' y' : '',
    trim((string) ($request['gender'] ?? '')),
]));

$labDate = static function ($value, string $format = 'M j, Y') {
    $ts = empty($value) ? false : strtotime((string) $value);
    return $ts ? date($format, $ts) : null;
};
$releasedOn = $labDate($request['released_at'] ?? null, 'M j, Y · g:i A');
?>

<div class="lab<?= $isReleased ? ' lab--locked' : '' ?>">

    <a href="<?= base_url('medtech/requests') ?>" class="lab-back">
        <i class="bi bi-arrow-left" aria-hidden="true"></i>Lab requests
    </a>

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

    <?php if (!$isPaid && !$isReleased): ?>
        <div class="lab-msg lab-msg--warn" role="alert">
            <i class="bi bi-lock" aria-hidden="true"></i>
            <div>
                <strong>Not paid yet</strong>
                <p>The front desk must press <b>Start</b> on this request before results can be saved. You can still look it over.</p>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($assignedTo) && !$isReleased): ?>
        <div class="lab-msg lab-msg--warn" role="alert">
            <i class="bi bi-person-lock" aria-hidden="true"></i>
            <div>
                <strong>Assigned to <?= esc($assignedTo) ?></strong>
                <p>The front desk sent this patient to another med tech, so saving is turned off for you.</p>
            </div>
        </div>
    <?php endif; ?>


    <!-- ===== SUMMARY: patient + request facts ===== -->
    <section class="lab-card lab-summary" aria-label="Request summary">
        <header class="lab-head">
            <div class="lab-head-main">
                <span class="lab-avatar" aria-hidden="true"><?= esc($initials) ?></span>
                <div class="lab-head-text">
                    <h1 class="lab-name"><?= esc($request['patient_name']) ?></h1>
                    <p class="lab-meta">
                        <?php if ($demo !== ''): ?>
                            <span><?= esc($demo) ?></span>
                            <span class="lab-sep" aria-hidden="true">·</span>
                        <?php endif; ?>
                        <span class="lab-mono"><?= esc($refNo) ?></span>
                    </p>
                </div>
            </div>

            <div class="lab-head-side">
                <span class="lab-status lab-status--<?= esc($statusTone, 'attr') ?>"><?= esc($statusLabel) ?></span>
                <a href="<?= base_url('medtech/request/print/' . (int) $request['id']) ?>"
                   target="_blank" rel="noopener" class="lab-btn">
                    <i class="bi bi-printer" aria-hidden="true"></i><span>Print</span>
                </a>
                <?php if (!$isReleased): ?>
                    <button type="button" class="lab-btn lab-btn--go" onclick="releaseResult(<?= (int) $request['id'] ?>)">
                        <i class="bi bi-check2-all" aria-hidden="true"></i><span>Release result</span>
                    </button>
                <?php endif; ?>
            </div>
        </header>

        <dl class="lab-facts">
            <div class="lab-fact">
                <dt>Collected</dt>
                <dd><?= esc($labDate($request['request_date'] ?? null) ?? '—') ?></dd>
            </div>
            <div class="lab-fact">
                <dt>Requesting physician</dt>
                <dd><?= trim((string) ($request['doctor_name'] ?? '')) !== '' ? esc($request['doctor_name']) : '<span class="lab-faint">Not given</span>' ?></dd>
            </div>
            <div class="lab-fact">
                <dt>Medical technologist</dt>
                <dd><?= $technologist !== '' ? esc($technologist) : '<span class="lab-faint">Unassigned</span>' ?></dd>
            </div>
            <div class="lab-fact">
                <?php if ($isReleased): ?>
                    <dt>Released</dt>
                    <dd><?= esc($releasedOn ?? '—') ?></dd>
                <?php else: ?>
                    <dt>Payment</dt>
                    <dd class="lab-pay lab-pay--<?= $isPaid ? 'ok' : 'due' ?>"><?= $isPaid ? 'Paid' : 'Not paid' ?></dd>
                <?php endif; ?>
            </div>
        </dl>
    </section>


    <!-- ===== RESULTS ===== -->
    <div class="lab-card lab-panel">

        <div class="lab-panel-head">
            <div class="lab-panel-head-text">
                <h2 class="lab-panel-title">Results</h2>
                <span class="lab-panel-count">
                    <?= number_format($totalTests) ?> <?= $totalTests === 1 ? 'test' : 'tests' ?>
                    <span class="lab-sep" aria-hidden="true">·</span>
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
                <div class="lab-progress-wrap">
                    <span class="lab-progress-label">Entered</span>
                    <div class="lab-progress<?= $totalTests > 0 && $filledTests === $totalTests ? ' is-done' : '' ?>" id="labProgress"
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
                            <span class="lab-progress-of">/<?= (int) $totalTests ?></span>
                        </div>
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
                    <div class="lab-sign-name"><?= esc($technologist !== '' ? $technologist : 'Medical Technologist') ?></div>
                    <div class="lab-sign-role">Medical Technologist<?= $releasedOn ? ' · Released ' . esc($releasedOn) : '' ?></div>
                </div>
            </div>

        <?php else: ?>

            <!-- ================= EDIT ================= -->
            <form action="<?= base_url('medtech/request/release-directly/' . (int) $request['id']) ?>"
                  method="POST"
                  id="mainResultForm"
                  novalidate
                  <?= !$isPaid ? 'data-unpaid="1"' : '' ?>>

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

                                    $existingResult = trim((string) ($result['result'] ?? ''));
                                    $looksNumeric   = $existingResult !== '' && is_numeric($existingResult);

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
   LABORATORY REQUEST (theme tokens from pm-theme.css)
   ========================================================= */

.lab {
    --lab-ink:         var(--pm-text, #10202E);
    --lab-text:        var(--pm-text-soft, #3B4C5E);
    --lab-muted:       var(--pm-text-muted, #5D6E80);
    --lab-faint:       #7A8B9D;
    --lab-line:        var(--pm-line, #DDE4EC);
    --lab-strong:      var(--pm-line-strong, #C3CFDB);
    --lab-line-soft:   #EBF0F5;
    --lab-surface:     #FFFFFF;
    --lab-canvas:      #F8FAFC;

    --lab-accent:      var(--pm-accent, #0B67B2);
    --lab-accent-dark: var(--pm-accent-hover, #08528F);
    --lab-accent-soft: #E8F1FA;

    --lab-danger:      #B42318;
    --lab-danger-soft: #FEF3F2;
    --lab-amber:       #93370D;
    --lab-amber-soft:  #FEF6E7;
    --lab-green:       #067647;
    --lab-green-soft:  #ECFDF3;
    --lab-blue:        #08528F;
    --lab-blue-soft:   #E8F1FA;

    --lab-radius:      10px;
    --lab-radius-sm:   6px;
    --lab-mono:        var(--pm-mono, ui-monospace, SFMono-Regular, Menlo, Consolas, monospace);

    max-width: 1180px;
    margin: 0 auto;
    color: var(--lab-text);
    font-size: 0.875rem;
    min-width: 0;
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

.lab-mono  { font-family: var(--lab-mono); font-size: 0.78rem; }
.lab-faint { color: var(--lab-faint); }
.lab-sep   { color: var(--lab-strong); }

.lab-back {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    margin-bottom: 0.75rem;
    font-size: 0.8rem;
    font-weight: 500;
    color: var(--lab-muted);
    text-decoration: none;
}

.lab-back:hover { color: var(--lab-ink); }

.lab-card {
    background: var(--lab-surface);
    border: 1px solid var(--lab-line);
    border-radius: var(--lab-radius);
}

/* ---------- Messages ---------- */

.lab-msg {
    display: flex;
    align-items: flex-start;
    gap: 0.65rem;
    padding: 0.75rem 0.95rem;
    margin-bottom: 0.85rem;
    font-size: 0.82rem;
    line-height: 1.5;
    border: 1px solid;
    border-radius: var(--lab-radius-sm);
}

.lab-msg > i:first-child { margin-top: 0.1rem; }
.lab-msg > div { min-width: 0; }
.lab-msg strong { display: block; font-weight: 600; }
.lab-msg p { margin: 0.15rem 0 0; }
.lab-msg ul { margin: 0.3rem 0 0; padding-left: 1.1rem; }

.lab-msg--ok   { color: var(--lab-green);  background: var(--lab-green-soft);  border-color: #ABEFC6; }
.lab-msg--err  { color: var(--lab-danger); background: var(--lab-danger-soft); border-color: #FECDCA; }
.lab-msg--warn { color: var(--lab-amber);  background: var(--lab-amber-soft);  border-color: #FEDF89; }

/* ---------- Summary ---------- */

.lab-summary { margin-bottom: 1rem; overflow: hidden; }

.lab-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
    padding: 1.1rem 1.25rem;
}

.lab-head-main { display: flex; align-items: center; gap: 0.9rem; min-width: 0; }
.lab-head-text { min-width: 0; }

.lab-avatar {
    display: grid;
    place-items: center;
    flex-shrink: 0;
    width: 48px;
    height: 48px;
    font-size: 0.9rem;
    font-weight: 600;
    letter-spacing: 0.03em;
    color: var(--lab-ink);
    background: #E3EAF2;
    border-radius: 50%;
}

.lab-name {
    margin: 0;
    font-size: 1.3rem;
    font-weight: 600;
    letter-spacing: -0.015em;
    line-height: 1.25;
    color: var(--lab-ink);
    overflow-wrap: anywhere;
}

.lab-meta {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.25rem 0.45rem;
    margin: 0.3rem 0 0;
    font-size: 0.8125rem;
    color: var(--lab-muted);
}

.lab-meta .lab-mono { color: var(--lab-ink); font-weight: 500; }

.lab-head-side { display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; }

.lab-status {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    margin-right: 0.25rem;
    padding: 0.2rem 0.55rem;
    font-size: 0.74rem;
    font-weight: 600;
    white-space: nowrap;
    color: var(--fg);
    background: var(--bg);
    border-radius: 4px;
}

.lab-status::before { content: ""; width: 6px; height: 6px; border-radius: 50%; background: var(--dot); }

.lab-status--pending   { --bg: #FEF6E7; --fg: #93370D; --dot: #DC6803; }
.lab-status--progress  { --bg: #E8F1FA; --fg: #08528F; --dot: #0B67B2; }
.lab-status--draft     { --bg: #F2F4F7; --fg: #475467; --dot: #98A2B3; }
.lab-status--completed { --bg: #ECFDF3; --fg: #067647; --dot: #17B26A; }
.lab-status--released  { --bg: #E6F6F3; --fg: #00715F; --dot: #009A85; }

/* Request facts: one hairline row */
.lab-facts {
    display: flex;
    flex-wrap: wrap;
    margin: 0;
    overflow: hidden;
    border-top: 1px solid var(--lab-line-soft);
}

.lab-fact {
    flex: 1 1 170px;
    min-width: 0;
    margin: 0 0 0 -1px;
    padding: 0.7rem 1.25rem 0.75rem;
    border-left: 1px solid var(--lab-line-soft);
    box-shadow: 0 -1px 0 0 var(--lab-line-soft);
}

.lab-fact dt {
    margin: 0;
    font-size: 0.66rem;
    font-weight: 600;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: var(--lab-faint);
}

.lab-fact dd {
    margin: 0.2rem 0 0;
    font-size: 0.85rem;
    font-weight: 500;
    color: var(--lab-ink);
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.lab-pay { display: flex; align-items: center; gap: 0.4rem; }
.lab-pay::before { content: ""; width: 7px; height: 7px; border-radius: 50%; }
.lab-pay--ok::before  { background: #17B26A; }
.lab-pay--due         { color: var(--lab-amber) !important; }
.lab-pay--due::before { background: #DC6803; }

/* ---------- Buttons ---------- */

.lab-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.4rem;
    height: 34px;
    padding: 0 0.85rem;
    font-size: 0.8rem;
    font-weight: 600;
    line-height: 1;
    color: var(--lab-ink);
    background: var(--lab-surface);
    border: 1px solid var(--lab-line);
    border-radius: var(--lab-radius-sm);
    white-space: nowrap;
    text-decoration: none;
    cursor: pointer;
    transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
}

.lab-btn:hover { background: var(--lab-canvas); border-color: var(--lab-strong); color: var(--lab-ink); }
.lab-btn:disabled { opacity: 0.5; cursor: not-allowed; }
.lab-btn i { font-size: 0.9em; }

.lab-btn--ghost { color: var(--lab-muted); }
.lab-btn--ghost:hover { color: var(--lab-ink); }

.lab-btn--go { color: #FFFFFF; background: var(--lab-accent); border-color: var(--lab-accent); }
.lab-btn--go:hover { color: #FFFFFF; background: var(--lab-accent-dark); border-color: var(--lab-accent-dark); }

/* ---------- Results panel ---------- */

.lab-panel { overflow: hidden; }

.lab-panel-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 0.9rem 1.25rem;
    border-bottom: 1px solid var(--lab-line);
}

.lab-panel-head-text { min-width: 0; }

.lab-panel-title {
    margin: 0 0 0.15rem;
    font-size: 0.95rem;
    font-weight: 600;
    color: var(--lab-ink);
}

.lab-panel-count {
    display: inline-flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.4rem;
    font-size: 0.78rem;
    color: var(--lab-muted);
    font-variant-numeric: tabular-nums;
}

.lab-abn-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    margin-left: 0.2rem;
    padding: 0.1rem 0.45rem;
    font-size: 0.7rem;
    font-weight: 600;
    color: var(--lab-danger);
    background: var(--lab-danger-soft);
    border-radius: 4px;
}

/* ---------- Progress ring ---------- */

.lab-progress-wrap { display: flex; align-items: center; gap: 0.6rem; flex-shrink: 0; }

.lab-progress-label {
    font-size: 0.66rem;
    font-weight: 600;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: var(--lab-faint);
}

.lab-progress {
    position: relative;
    flex-shrink: 0;
    width: 50px;
    height: 50px;
}

.lab-ring { width: 100%; height: 100%; transform: rotate(-90deg); }

.lab-ring circle {
    fill: none;
    stroke-width: 4;
    stroke-linecap: round;
}

.lab-ring-bg { stroke: var(--lab-line-soft); }

.lab-ring-fg {
    stroke: var(--lab-accent);
    stroke-dasharray: 113.1;
    stroke-dashoffset: calc(113.1 - (113.1 * var(--pct, 0) / 100));
    transition: stroke-dashoffset 0.4s ease, stroke 0.2s ease;
}

.lab-progress.is-done .lab-ring-fg { stroke: #17B26A; }

.lab-progress-text {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.62rem;
    font-variant-numeric: tabular-nums;
    color: var(--lab-faint);
}

.lab-progress-num { font-size: 0.8rem; font-weight: 600; color: var(--lab-ink); }

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
    padding: 0.6rem 0.9rem;
    font-size: 0.66rem;
    font-weight: 600;
    letter-spacing: 0.06em;
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

.lab-table thead th.lab-right { text-align: right; }

.lab-w-test   { width: 24%; }
.lab-w-result { width: 16%; }
.lab-w-unit   { width: 14%; }
.lab-w-range  { width: 20%; }
.lab-w-flag   { width: 16%; }
.lab-w-action { width: 56px; }

.lab-cell-name { font-weight: 500; color: var(--lab-ink); }
.lab-cell-soft { color: var(--lab-muted); font-size: 0.8rem; }

.lab-table td.lab-right { font-variant-numeric: tabular-nums; }
.lab-bad { font-weight: 600; color: var(--lab-danger); }
.lab-row.is-bad td { background: #FFFBFA; }

.lab-req { color: var(--lab-danger); font-weight: 600; }

/* ---------- Service groups ---------- */

.lab-group-head td {
    position: sticky;
    top: 33px;
    z-index: 2;
    padding: 0.55rem 0.9rem !important;
    background: var(--lab-surface);
    border-top: 1px solid var(--lab-line);
    border-bottom: 1px solid var(--lab-line-soft);
}

.lab-group:first-of-type .lab-group-head td { border-top: 0; }

/* Lab dot, the same marker the worklists use for laboratory */
.lab-group-bar {
    display: inline-block;
    width: 8px;
    height: 8px;
    margin-right: 0.5rem;
    vertical-align: 1px;
    background: #0B67B2;
    border-radius: 2px;
}

.lab-group-name {
    font-size: 0.82rem;
    font-weight: 600;
    color: var(--lab-ink);
}

.lab-group-count {
    margin-left: 0.5rem;
    font-size: 0.72rem;
    color: var(--lab-faint);
    font-variant-numeric: tabular-nums;
}

.lab-group-progress {
    float: right;
    font-size: 0.72rem;
    font-weight: 500;
    color: var(--lab-faint);
    font-variant-numeric: tabular-nums;
}

.lab-group-progress.is-done { color: var(--lab-green); }
.lab-group-progress.is-done::before { content: "\2713\00a0"; }

.lab-group-toggle {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 20px;
    height: 20px;
    margin-right: 0.25rem;
    vertical-align: -4px;
    font-size: 0.7rem;
    color: var(--lab-muted);
    background: none;
    border: 0;
    border-radius: 4px;
    cursor: pointer;
    transition: transform 0.18s ease, background-color 0.15s ease;
}

.lab-group-toggle:hover { background: var(--lab-line-soft); }
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
.lab-field textarea::placeholder { color: #A3B1C0; }

.lab-input:hover,
.lab-select:hover,
.lab-field textarea:hover { border-color: var(--lab-strong); }

.lab-input:focus,
.lab-select:focus,
.lab-field textarea:focus {
    outline: none;
    border-color: var(--lab-accent);
    box-shadow: 0 0 0 3px rgba(11, 103, 178, 0.14);
}

.lab-input--num { text-align: right; font-variant-numeric: tabular-nums; font-weight: 600; }

.lab-input.is-invalid,
.lab-field textarea.is-invalid {
    border-color: #F04438;
    background: var(--lab-danger-soft);
}

.lab-select {
    appearance: none;
    padding-right: 1.8rem;
    font-weight: 500;
    cursor: pointer;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%235D6E80'%3E%3Cpath d='M4.5 6.5 8 10l3.5-3.5z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 0.45rem center;
    background-size: 14px;
}

.lab-select--normal   { color: var(--lab-muted); }
.lab-select--high     { color: var(--lab-danger); background-color: var(--lab-danger-soft); border-color: #FECDCA; font-weight: 600; }
.lab-select--low      { color: var(--lab-blue);   background-color: var(--lab-blue-soft);   border-color: #B9D6F2; font-weight: 600; }
.lab-select--critical {
    color: #FFFFFF;
    font-weight: 600;
    background-color: var(--lab-danger);
    border-color: var(--lab-danger);
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23ffffff'%3E%3Cpath d='M4.5 6.5 8 10l3.5-3.5z'/%3E%3C/svg%3E");
}

.lab-row.is-flag-critical td { background: #FFFBFA; }

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
    border: 0;
    border-radius: var(--lab-radius-sm);
    cursor: pointer;
    transition: background-color 0.15s ease, color 0.15s ease;
}

.lab-trash:hover { color: var(--lab-danger); background: var(--lab-danger-soft); }

/* ---------- Empty ---------- */

.lab-empty {
    padding: 2.75rem 1rem;
    text-align: center;
    color: var(--lab-muted);
}

.lab-empty i { display: block; margin-bottom: 0.5rem; font-size: 1.4rem; color: var(--lab-faint); }
.lab-empty p { margin: 0; font-size: 0.82rem; }

/* ---------- Add row ---------- */

.lab-add-row {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.7rem 1rem;
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

.lab-note { display: flex; flex-direction: column; gap: 0.35rem; min-width: 0; }

.lab-note-key {
    font-size: 0.66rem;
    font-weight: 600;
    letter-spacing: 0.06em;
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

.lab-field label { font-size: 0.78rem; font-weight: 600; color: var(--lab-ink); }

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
    padding: 0.12rem 0.45rem;
    font-size: 0.71rem;
    font-weight: 600;
    border-radius: 4px;
    white-space: nowrap;
}

.lab-flag--ok       { color: var(--lab-faint); background: none; font-weight: 500; }
.lab-flag--high     { background: var(--lab-danger-soft); color: var(--lab-danger); }
.lab-flag--low      { background: var(--lab-blue-soft);   color: var(--lab-blue); }
.lab-flag--critical { background: var(--lab-danger);      color: #FFFFFF; }

/* ---------- Sign-off (medical technologist) ---------- */

.lab-sign {
    display: flex;
    justify-content: flex-end;
    padding: 2.25rem 1.25rem 1.4rem;
    border-top: 1px solid var(--lab-line);
}

.lab-sign-col { width: min(300px, 100%); text-align: center; }
.lab-sign-line { height: 1px; margin-bottom: 0.5rem; background: var(--lab-strong); }
.lab-sign-name { font-size: 0.85rem; font-weight: 600; color: var(--lab-ink); }
.lab-sign-role { margin-top: 0.1rem; font-size: 0.74rem; color: var(--lab-muted); }

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
    padding: 0.8rem 1.25rem;
    border-top: 1px solid var(--lab-line);
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(6px);
    -webkit-backdrop-filter: blur(6px);
}

.lab-actions-status {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.8rem;
    color: var(--lab-muted);
}

.lab-actions-status.is-warn  { color: var(--lab-amber); }
.lab-actions-status.is-ready { color: var(--lab-green); }

.lab-actions-btns { display: flex; gap: 0.5rem; flex-wrap: wrap; }

/* ---------- Responsive ---------- */

@media (max-width: 900px) {
    .lab-head-side { width: 100%; }
    .lab-head-side .lab-btn--go { flex: 1; }
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
    }

    .lab-row {
        margin: 0.6rem 0.75rem;
        padding: 0.5rem;
        border: 1px solid var(--lab-line);
        border-radius: var(--lab-radius-sm);
        background: var(--lab-surface);
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
        font-weight: 600;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: var(--lab-faint);
    }

    .lab-input, .lab-select { max-width: 60%; }

    .lab-head { padding: 1rem; }
    .lab-fact { padding: 0.65rem 1rem; }
    .lab-panel-head { align-items: flex-start; padding: 0.9rem 1rem; }
    .lab-progress-label { display: none; }

    .lab-actions { flex-direction: column; align-items: stretch; }
    .lab-actions-btns { width: 100%; }
    .lab-actions-btns .lab-btn { flex: 1; }
}

@media (max-width: 480px) {
    .lab-name { font-size: 1.1rem; }
    .lab-avatar { width: 40px; height: 40px; font-size: 0.8rem; }
    .lab-input, .lab-select { max-width: 55%; }
    .lab-notes, .lab-notes-edit { padding: 1rem; }
}

@media print {
    .lab-back,
    .lab-head-side,
    .lab-actions,
    .lab-add-row,
    .lab-progress-wrap,
    .lab-group-toggle,
    .lab-trash,
    .lab-msg,
    .lab-w-action,
    td[data-label="Action"] { display: none !important; }

    .lab-card { border: 0; }

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
</style>


<script>
(function () {
    'use strict';

    var form = document.getElementById('mainResultForm');
    if (!form) { return; }

    /* =====================================================
       PAYMENT GUARD
       If the controller flagged this request as unpaid,
       block every write path and disable the form controls.
       The server refuses the save anyway; this just saves
       the tech from typing results they cannot submit.
       ===================================================== */
    if (form.dataset.unpaid === '1') {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            alert('This request has not been paid yet. Ask the receptionist to click Start first.');
        });

        form.querySelectorAll('input, textarea, select, button').forEach(function (el) {
            el.disabled = true;
        });

        return;
    }

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
            progress.classList.toggle('is-done', total > 0 && filled === total);
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
        window.pmPost('<?= base_url('medtech/request/release/') ?>' + id);
    }
}
</script>

<?= $this->endSection() ?>