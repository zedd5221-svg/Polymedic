<?php
/* ------------------------------------------------------------------
   Patient handoff panel for the MedTech / Radiologist worklists.

   $transfer comes from HandlesPatientTransfers::transferPanelData():
     base        route prefix ('medtech' | 'radiologist')
     roleLabel   'med tech' | 'radiologist'
     patients    requests this user can hand off (pending, in progress, draft)
     recipients  same-role colleagues (id, name, workload)
     incoming    pending transfers waiting for this user's answer
     outgoing    pending transfers this user sent
   ------------------------------------------------------------------ */

$t          = is_array($transfer ?? null) ? $transfer : [];
$base       = (string) ($t['base'] ?? '');
$roleLabel  = (string) ($t['roleLabel'] ?? 'colleague');
$patients   = $t['patients'] ?? [];
$recipients = $t['recipients'] ?? [];
$incoming   = $t['incoming'] ?? [];
$outgoing   = $t['outgoing'] ?? [];

$plural = static fn (int $n, string $word) => $n . ' ' . $word . ($n === 1 ? '' : 's');

// Department stage of a transferable request => [label, tag modifier]
$stageTag = [
    'pending'     => ['Pending', ' tx-tag--pending'],
    'in_progress' => ['In progress', ''],
    'draft'       => ['Draft', ' tx-tag--draft'],
];
$when   = static fn ($ts) => $ts ? date('M j · g:i A', strtotime((string) $ts)) : '';
?>

<section class="tx" id="transfers" aria-label="Patient transfers">

    <div class="tx-bar">
        <div class="tx-bar-text">
            <span class="tx-bar-icon" aria-hidden="true"><i class="bi bi-arrow-left-right"></i></span>
            <div>
                <strong>Patient handoff</strong>
                <span>
                    <?php if ($patients): ?>
                        <?= esc($plural(count($patients), 'patient')) ?> you can hand off, pending or in progress.
                        Send some to another <?= esc($roleLabel) ?> if you can't take them.
                    <?php else: ?>
                        You have no pending or active patients to hand off right now.
                    <?php endif; ?>
                </span>
            </div>
        </div>
        <button type="button" class="tx-btn tx-btn--primary"
                data-bs-toggle="modal" data-bs-target="#txModal"
                <?= $patients ? '' : 'disabled' ?>>
            <i class="bi bi-send" aria-hidden="true"></i>
            Transfer patients
        </button>
    </div>

    <?php foreach ($incoming as $tr): ?>
        <article class="tx-card tx-card--in">
            <header class="tx-card-head">
                <span class="tx-tag tx-tag--in">Needs your answer</span>
                <span class="tx-card-who"><strong><?= esc($tr['from_user_name']) ?></strong> wants to transfer <?= esc($plural(count($tr['items']), 'patient')) ?> to you</span>
                <time class="tx-time"><?= esc($when($tr['created_at'])) ?></time>
            </header>
            <ul class="tx-chips">
                <?php foreach ($tr['items'] as $item): ?>
                    <li><?= esc($item['patient_name']) ?><?php if (!empty($item['reference_number'])): ?> <span><?= esc($item['reference_number']) ?></span><?php endif; ?></li>
                <?php endforeach; ?>
            </ul>
            <p class="tx-reason"><span>Reason</span><?= esc($tr['reason']) ?></p>
            <footer class="tx-card-foot">
                <form method="post" action="<?= esc(base_url($base . '/transfer/respond/' . (int) $tr['id']), 'attr') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="decline">
                    <button type="submit" class="tx-btn">Decline</button>
                </form>
                <form method="post" action="<?= esc(base_url($base . '/transfer/respond/' . (int) $tr['id']), 'attr') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="accept">
                    <button type="submit" class="tx-btn tx-btn--primary"><i class="bi bi-check2" aria-hidden="true"></i> Accept</button>
                </form>
            </footer>
        </article>
    <?php endforeach; ?>

    <?php foreach ($outgoing as $tr): ?>
        <article class="tx-card">
            <header class="tx-card-head">
                <span class="tx-tag">Waiting</span>
                <span class="tx-card-who">Sent to <strong><?= esc($tr['to_user_name']) ?></strong> · <?= esc($plural(count($tr['items']), 'patient')) ?></span>
                <time class="tx-time"><?= esc($when($tr['created_at'])) ?></time>
            </header>
            <ul class="tx-chips">
                <?php foreach ($tr['items'] as $item): ?>
                    <li><?= esc($item['patient_name']) ?><?php if (!empty($item['reference_number'])): ?> <span><?= esc($item['reference_number']) ?></span><?php endif; ?></li>
                <?php endforeach; ?>
            </ul>
            <p class="tx-reason"><span>Reason</span><?= esc($tr['reason']) ?></p>
            <footer class="tx-card-foot">
                <span class="tx-hint">These patients stay with you until <?= esc($tr['to_user_name']) ?> accepts.</span>
                <form method="post" action="<?= esc(base_url($base . '/transfer/cancel/' . (int) $tr['id']), 'attr') ?>"
                      onsubmit="return confirm('Withdraw this transfer request?')">
                    <?= csrf_field() ?>
                    <button type="submit" class="tx-btn">Withdraw</button>
                </form>
            </footer>
        </article>
    <?php endforeach; ?>
</section>


<div class="modal fade tx-modal" id="txModal" tabindex="-1" aria-labelledby="txModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <form class="modal-content" method="post" action="<?= esc(base_url($base . '/transfer/create'), 'attr') ?>" id="txForm" novalidate>
            <?= csrf_field() ?>

            <div class="modal-header">
                <div>
                    <h2 class="tx-modal-title" id="txModalTitle">Transfer patients</h2>
                    <p class="tx-modal-sub">Send to another <?= esc($roleLabel) ?>. Nothing moves until they accept.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <fieldset class="tx-field">
                    <legend class="tx-label">
                        Patients
                        <?php if (count($patients) > 1): ?>
                            <button type="button" class="tx-link" id="txSelectAll">Select all</button>
                        <?php endif; ?>
                    </legend>
                    <div class="tx-list">
                        <?php foreach ($patients as $p):
                            $services = array_filter(array_map('trim', explode(',', (string) ($p['services'] ?? ''))));
                            $demo     = implode(' · ', array_filter([is_numeric($p['age'] ?? null) ? (int) $p['age'] . ' y' : '', (string) ($p['gender'] ?? '')]));
                        ?>
                            <label class="tx-opt">
                                <input type="checkbox" name="request_ids[]" value="<?= (int) $p['id'] ?>">
                                <span class="tx-opt-main">
                                    <span class="tx-opt-name"><?= esc($p['patient_name']) ?></span>
                                    <span class="tx-opt-meta"><?= esc(implode(' · ', array_filter([$p['reference_number'] ?? '', $demo, implode(', ', array_slice($services, 0, 2)) . (count($services) > 2 ? ' +' . (count($services) - 2) : '')]))) ?></span>
                                </span>
                                <?php [$tagLabel, $tagClass] = $stageTag[$p['stage'] ?? ''] ?? $stageTag['in_progress']; ?>
                                <span class="tx-tag<?= $tagClass ?>"><?= esc($tagLabel) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </fieldset>

                <div class="tx-field">
                    <label class="tx-label" for="txRecipient">Send to</label>
                    <?php if ($recipients): ?>
                        <select class="tx-input" id="txRecipient" name="to_user_id" required>
                            <option value="">Choose a <?= esc($roleLabel) ?>…</option>
                            <?php foreach ($recipients as $r): ?>
                                <option value="<?= (int) $r['id'] ?>"><?= esc($r['name']) ?> — <?= $r['workload'] ? esc($plural($r['workload'], 'active patient')) : 'free' ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php else: ?>
                        <p class="tx-empty">There is no other active <?= esc($roleLabel) ?> to send patients to.</p>
                    <?php endif; ?>
                </div>

                <div class="tx-field">
                    <label class="tx-label" for="txReason">Reason</label>
                    <textarea class="tx-input" id="txReason" name="reason" rows="3" maxlength="1000" required
                              placeholder="e.g. End of shift — samples collected, results still to encode."></textarea>
                    <p class="tx-help">The receiving <?= esc($roleLabel) ?> sees this with the request.</p>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="tx-btn" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="tx-btn tx-btn--primary" id="txSubmit" disabled>Send request</button>
            </div>
        </form>
    </div>
</div>

<style>
.tx { display: flex; flex-direction: column; gap: 0.6rem; margin-bottom: 1rem; scroll-margin-top: 80px; }

.tx-bar {
    display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap;
    padding: 0.75rem 1rem; background: #fff; border: 1px solid #e2e8f0; border-radius: 10px;
}
.tx-bar-text { display: flex; align-items: center; gap: 0.75rem; min-width: 0; }
.tx-bar-text strong { display: block; font-size: 0.85rem; color: #0f172a; }
.tx-bar-text span { font-size: 0.78rem; color: #64748b; }
.tx-bar-icon {
    flex: none; display: grid; place-items: center; width: 34px; height: 34px;
    border-radius: 8px; background: #e8f1fb; color: #1976d2;
}

.tx-btn {
    display: inline-flex; align-items: center; gap: 0.4rem; height: 34px; padding: 0 0.85rem;
    font-size: 0.8rem; font-weight: 600; color: #334155; background: #fff;
    border: 1px solid #e2e8f0; border-radius: 7px; cursor: pointer; white-space: nowrap;
}
.tx-btn:hover { background: #f8fafc; }
.tx-btn--primary { color: #fff; background: #1976d2; border-color: #1976d2; }
.tx-btn--primary:hover { background: #1565c0; }
.tx-btn:disabled { opacity: 0.55; cursor: not-allowed; }
.tx-btn:focus-visible, .tx-input:focus, .tx-opt:has(input:focus-visible) { outline: none; box-shadow: 0 0 0 3px rgba(25, 118, 210, 0.2); }

.tx-card { padding: 0.85rem 1rem; background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; }
.tx-card--in { border-color: #bcd6f3; background: #f7fbff; }
.tx-card-head { display: flex; align-items: center; gap: 0.6rem; flex-wrap: wrap; font-size: 0.82rem; color: #334155; }
.tx-card-who strong { color: #0f172a; }
.tx-time { margin-left: auto; font-size: 0.72rem; color: #94a3b8; }

.tx-tag {
    flex: none; padding: 0.1rem 0.5rem; font-size: 0.68rem; font-weight: 600;
    color: #92400e; background: #fffbeb; border: 1px solid #fde68a; border-radius: 999px;
}
.tx-tag--in { color: #1565c0; background: #e8f1fb; border-color: #bcd6f3; }
.tx-tag--draft { color: #475569; background: #f1f5f9; border-color: #e2e8f0; }
.tx-tag--pending { color: #475569; background: #fff; border-color: #cbd5e1; }

.tx-chips { display: flex; flex-wrap: wrap; gap: 0.35rem; margin: 0.6rem 0 0; padding: 0; list-style: none; }
.tx-chips li { padding: 0.2rem 0.55rem; font-size: 0.75rem; color: #0f172a; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; }
.tx-chips li span { color: #94a3b8; font-family: ui-monospace, Menlo, Consolas, monospace; font-size: 0.68rem; }

.tx-reason { margin: 0.6rem 0 0; font-size: 0.8rem; line-height: 1.5; color: #334155; white-space: pre-line; }
.tx-reason span { display: block; font-size: 0.68rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; color: #94a3b8; }

.tx-card-foot { display: flex; align-items: center; justify-content: flex-end; gap: 0.5rem; margin-top: 0.75rem; }
.tx-card-foot form { margin: 0; }
.tx-hint { margin-right: auto; font-size: 0.75rem; color: #64748b; }

.tx-modal .modal-content { border: 0; border-radius: 12px; }
.tx-modal .modal-header { align-items: flex-start; padding: 1rem 1.25rem; border-bottom: 1px solid #f1f5f9; }
.tx-modal .modal-body { padding: 1rem 1.25rem; }
.tx-modal .modal-footer { gap: 0.5rem; padding: 0.75rem 1.25rem; border-top: 1px solid #f1f5f9; }
.tx-modal .modal-footer > * { margin: 0; }
.tx-modal-title { margin: 0; font-size: 1rem; font-weight: 700; color: #0f172a; }
.tx-modal-sub { margin: 0.2rem 0 0; font-size: 0.78rem; color: #64748b; }

.tx-field { margin: 0 0 1rem; padding: 0; border: 0; }
.tx-field:last-child { margin-bottom: 0; }
.tx-label { display: flex; align-items: center; justify-content: space-between; width: 100%; margin-bottom: 0.4rem; font-size: 0.78rem; font-weight: 600; color: #334155; float: none; }
.tx-link { padding: 0; font-size: 0.75rem; font-weight: 600; color: #1976d2; background: none; border: 0; cursor: pointer; }

.tx-list { display: flex; flex-direction: column; gap: 0.35rem; max-height: 260px; overflow-y: auto; }
.tx-opt {
    display: flex; align-items: center; gap: 0.65rem; padding: 0.55rem 0.7rem;
    border: 1px solid #e2e8f0; border-radius: 7px; cursor: pointer;
}
.tx-opt:has(input:checked) { border-color: #1976d2; background: #e8f1fb; }
.tx-opt input { flex: none; margin: 0; width: 16px; height: 16px; accent-color: #1976d2; }
.tx-opt-main { flex: 1; min-width: 0; }
.tx-opt-name { display: block; font-size: 0.82rem; font-weight: 600; color: #0f172a; }
.tx-opt-meta { display: block; font-size: 0.72rem; color: #64748b; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

.tx-input {
    display: block; width: 100%; padding: 0.5rem 0.65rem; font-size: 0.82rem; color: #0f172a;
    background: #fff; border: 1px solid #e2e8f0; border-radius: 7px;
}
select.tx-input { height: 38px; }
.tx-help, .tx-empty { margin: 0.35rem 0 0; font-size: 0.75rem; color: #64748b; }
.tx-empty { color: #b45309; }

@media (max-width: 575.98px) {
    .tx-time { margin-left: 0; width: 100%; }
    .tx-card-foot { flex-wrap: wrap; }
    .tx-hint { width: 100%; }
}
</style>

<script>
(function () {
    var form = document.getElementById('txForm');
    if (!form) { return; }

    var submit    = document.getElementById('txSubmit');
    var recipient = document.getElementById('txRecipient');
    var reason    = document.getElementById('txReason');
    var boxes     = form.querySelectorAll('input[name="request_ids[]"]');
    var selectAll = document.getElementById('txSelectAll');

    function anyChecked() {
        return Array.prototype.some.call(boxes, function (b) { return b.checked; });
    }

    function refresh() {
        submit.disabled = !(anyChecked() && recipient && recipient.value && reason.value.trim());
        if (selectAll) {
            selectAll.textContent = Array.prototype.every.call(boxes, function (b) { return b.checked; }) ? 'Clear' : 'Select all';
        }
    }

    form.addEventListener('input', refresh);
    form.addEventListener('change', refresh);

    if (selectAll) {
        selectAll.addEventListener('click', function () {
            var all = Array.prototype.every.call(boxes, function (b) { return b.checked; });
            boxes.forEach(function (b) { b.checked = !all; });
            refresh();
        });
    }

    form.addEventListener('submit', function (e) {
        if (submit.disabled) { e.preventDefault(); return; }
        submit.disabled = true;
        submit.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span> Sending…';
    });
})();
</script>
