<?php
/*
 * Bills with nothing paid yet, listed on the Payments page (Admin and
 * Receptionist) as "Unpaid" rows beside the real payments.
 *
 * They are not payments: no receipt, no refund. The one action is
 * "Collect payment", which opens the invoice. data-amount is 0 so the
 * "… total" under the table stays money actually collected.
 *
 * Expects: $invoices (from unpaidInvoices()), $prefix ('admin' | 'receptionist'),
 *          $initialsOf (callable from the page).
 */
$invoices = $invoices ?? [];
foreach ($invoices as $inv):
    $invId    = (int) $inv['id'];
    $invNo    = (string) ($inv['invoice_no'] ?? '');
    $name     = (string) ($inv['patient_name'] ?? 'Unknown');
    $code     = trim((string) ($inv['patient_code'] ?? ''));
    $due      = (float) ($inv['balance'] ?? 0);
    $services = $inv['services'] ?? [];
    $shown    = array_slice($services, 0, 3);
    $extra    = count($services) - count($shown);
    $dateTs   = !empty($inv['created_at']) ? strtotime($inv['created_at']) : false;
    $openUrl  = base_url($prefix . '/billing/view/' . $invId);
?>
    <tr class="pm-row pm-row--unpaid"
        data-id="inv-<?= $invId ?>"
        data-method=""
        data-status="unpaid"
        data-tags="unpaid"
        data-refund=""
        data-date="<?= $dateTs ? esc(date('Y-m-d', $dateTs), 'attr') : '' ?>"
        data-amount="0">

        <td data-label="Reference" class="pm-c-ref">
            <span class="pm-ref"><?= esc($invNo) ?></span>
            <span class="pm-sub">Invoice</span>
        </td>

        <td data-label="Patient">
            <div class="pm-patient">
                <span class="pm-avatar pm-avatar--neutral" aria-hidden="true"><?= esc($initialsOf($name)) ?></span>
                <div class="pm-patient-body">
                    <span class="pm-name"><?= esc($name) ?></span>
                    <span class="pm-sub"><?= esc($code !== '' ? $code : 'N/A') ?></span>
                </div>
            </div>
        </td>

        <td data-label="Services">
            <?php if ($services): ?>
                <div class="pm-services" title="<?= esc(implode(', ', $services), 'attr') ?>">
                    <span class="pm-services-text"><?= esc(implode(', ', $shown)) ?></span>
                    <?php if ($extra > 0): ?><span class="pm-more">+<?= $extra ?></span><?php endif; ?>
                </div>
            <?php else: ?>
                <span class="pm-muted">—</span>
            <?php endif; ?>
        </td>

        <td data-label="Amount paid" class="pm-num">
            <strong class="pm-amount pm-amount--none">&#8369;0.00</strong>
            <small class="pm-due">&#8369;<?= number_format($due, 2) ?> due</small>
        </td>

        <td data-label="Method"><span class="pm-muted">—</span></td>

        <td data-label="Date" class="pm-c-date">
            <?php if ($dateTs): ?>
                <span class="pm-date"><?= esc(date('M j, Y', $dateTs)) ?></span>
                <span class="pm-sub">Billed <?= esc(date('g:i A', $dateTs)) ?></span>
            <?php else: ?>
                <span class="pm-muted">—</span>
            <?php endif; ?>
        </td>

        <td data-label="Status">
            <span class="pm-status pm-status--unpaid">Unpaid</span>
        </td>

        <td data-label="Actions" class="pm-c-actions">
            <div class="pm-actions">
                <a class="pm-icon-btn pm-icon-btn--collect" href="<?= esc($openUrl, 'attr') ?>"
                   title="Collect payment" aria-label="Collect payment for <?= esc($invNo, 'attr') ?>">
                    <i class="bi bi-cash-coin" aria-hidden="true"></i>
                </a>
            </div>
        </td>
    </tr>
<?php endforeach; ?>
