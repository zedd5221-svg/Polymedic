<?php
/*
 * Certification + three signature lines + system endnote, kept on one page.
 * Needs $paper['cert'] (trusted HTML — escape values before building it),
 * $paper['signs'] = [[name or '', 'Prepared by', 'Front Desk / Cashier'], ...],
 * $paper['no'] and $generatedAt (from _paper_top.php).
 */
?>
    <div class="signoff">
        <div class="cert"><?= $paper['cert'] ?></div>

        <div class="signs">
            <?php foreach ($paper['signs'] as [$name, $role, $title]): ?>
                <div class="sign">
                    <div class="sign-name"><?= $name !== '' ? esc($name) : '&nbsp;' ?></div>
                    <div class="sign-role"><?= esc($role) ?></div>
                    <div><?= esc($title) ?></div>
                    <div class="sign-date">Date <span></span></div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="endnote">
            <span>System-generated report · <?= esc($paper['no']) ?> · <?= esc($generatedAt) ?></span>
            <span><?= esc($paper['endnote'] ?? 'Not valid as an official receipt') ?></span>
        </div>
    </div>
