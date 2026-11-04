<?= $this->extend($layout) ?>

<?= $this->section('pageTitle') ?>My Profile<?= $this->endSection() ?>

<?= $this->section($section) ?>

<?php
$fmtDate = static function ($value, string $format = 'M j, Y') {
    return $value ? date($format, strtotime($value)) : '—';
};

$age = null;
if (! empty($user['birthdate'])) {
    $age = (new DateTime($user['birthdate']))->diff(new DateTime('today'))->y;
}

$initials = '';
foreach (preg_split('/\s+/', trim($user['full_name'])) as $part) {
    if ($part !== '' && ctype_alpha($part[0])) {
        $initials .= strtoupper($part[0]);
    }
}
$initials = substr($initials, 0, 2) ?: 'U';

$hasAvatar = ! empty($user['avatar']) && is_file(FCPATH . $user['avatar']);

// Profile completeness — the self-service fields only
$completeFields = ['email', 'contact_number', 'gender', 'birthdate', 'address', 'avatar'];
if ($needsPrc) {
    $completeFields[] = 'prc_license';
}
$filled = 0;
foreach ($completeFields as $f) {
    if (! empty($user[$f])) {
        $filled++;
    }
}
$completeness = (int) round($filled / count($completeFields) * 100);

$activeTab = session()->getFlashdata('profile_tab') ?: 'info';
$old = static fn (string $key) => old($key, $user[$key] ?? '');
?>

<style>
/* =========================================================
   MY PROFILE
   One header, a details column, and the editable forms.
   Hairline panels, label-over-value details, no decoration.
   ========================================================= */

.pf {
    --pf-ink:    var(--pm-text, #10202E);
    --pf-text:   var(--pm-text-soft, #3B4C5E);
    --pf-muted:  var(--pm-text-muted, #5D6E80);
    --pf-faint:  #7A8B9D;
    --pf-line:   var(--pm-line, #DDE4EC);
    --pf-strong: var(--pm-line-strong, #C3CFDB);
    --pf-soft:   #EBF0F5;
    --pf-subtle: #F8FAFC;
    --pf-accent: var(--pm-accent, #0B67B2);
    --pf-accent-dark: var(--pm-accent-hover, #08528F);

    max-width: 1120px;
    margin: 0 auto;
    color: var(--pf-text);
}

.pf-card {
    background: #FFFFFF;
    border: 1px solid var(--pf-line);
    border-radius: 8px;
}

.pf-alert { border-radius: 8px; font-size: 0.8125rem; }

/* ---------- Header ---------- */

.pf-head {
    display: flex;
    align-items: center;
    gap: 1.1rem;
    padding: 1.25rem 1.35rem;
    margin-bottom: 1rem;
}

.pf-avatar {
    width: 72px;
    height: 72px;
    flex-shrink: 0;
    display: grid;
    place-items: center;
    border-radius: 50%;
    object-fit: cover;
    font-size: 1.35rem;
    font-weight: 600;
    letter-spacing: 0.02em;
    color: var(--pf-ink);
    background: #E3EAF2;
    box-shadow: 0 0 0 1px var(--pf-line);
}

.pf-identity { flex: 1; min-width: 0; }

.pf-name {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
    letter-spacing: -0.015em;
    line-height: 1.25;
    color: var(--pf-ink);
}

.pf-meta {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.25rem 0.5rem;
    margin: 0.3rem 0 0;
    font-size: 0.8125rem;
    color: var(--pf-muted);
}

.pf-meta-role { font-weight: 500; color: var(--pf-text); }
.pf-meta-sep { color: var(--pf-strong); }
.pf-mono { font-family: var(--pm-mono, ui-monospace, monospace); font-size: 0.78rem; }

.pf-status {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    font-weight: 500;
    color: var(--pf-text);
}

.pf-status::before {
    content: "";
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: var(--dot, #98A2B3);
}

.pf-status--active { --dot: #17B26A; }

.pf-head-actions {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    flex-shrink: 0;
}

.pf-head-actions form { margin: 0; }

.pf-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    height: 34px;
    padding: 0 0.8rem;
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--pf-ink);
    background: #FFFFFF;
    border: 1px solid var(--pf-line);
    border-radius: 6px;
    cursor: pointer;
    transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
}

.pf-btn:hover { background: var(--pf-subtle); border-color: var(--pf-strong); }
.pf-btn:focus-within,
.pf-btn:focus-visible { outline: 2px solid var(--pf-accent); outline-offset: 2px; }

.pf-btn--quiet { color: var(--pf-muted); border-color: transparent; background: transparent; }
.pf-btn--quiet:hover { color: #B42318; background: #FEF3F2; border-color: transparent; }

/* ---------- Layout ---------- */

.pf-grid {
    display: grid;
    grid-template-columns: 300px minmax(0, 1fr);
    gap: 1rem;
    align-items: start;
}

.pf-main { min-width: 0; }

/* ---------- Details column ---------- */

.pf-section { padding: 1.1rem 1.25rem; }
.pf-section + .pf-section { border-top: 1px solid var(--pf-soft); }

.pf-section-title {
    margin: 0 0 0.75rem;
    font-size: 0.7rem;
    font-weight: 600;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    color: var(--pf-muted);
}

.pf-complete-head {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: 0.75rem;
    margin-bottom: 0.5rem;
}

.pf-complete-head .pf-section-title { margin: 0; }

.pf-complete-val {
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--pf-ink);
    font-variant-numeric: tabular-nums;
}

.pf-progress {
    height: 4px;
    overflow: hidden;
    background: var(--pf-soft);
    border-radius: 2px;
}

.pf-progress span {
    display: block;
    height: 100%;
    background: var(--pf-accent);
    border-radius: 2px;
}

.pf-about {
    margin: 0;
    font-size: 0.8125rem;
    line-height: 1.55;
    color: var(--pf-text);
    white-space: pre-line;
}

.pf-dl { display: grid; gap: 0.7rem; margin: 0; }

.pf-dl dt {
    font-size: 0.74rem;
    font-weight: 500;
    color: var(--pf-muted);
}

.pf-dl dd {
    margin: 0.05rem 0 0;
    font-size: 0.84rem;
    color: var(--pf-ink);
    overflow-wrap: anywhere;
}

.pf-dl .pf-empty { color: var(--pf-faint); }

/* ---------- Edit panel ---------- */

.pf-tabs {
    display: flex;
    gap: 0.15rem;
    padding: 0 0.75rem;
    border-bottom: 1px solid var(--pf-line);
    overflow-x: auto;
    scrollbar-width: none;
}

.pf-tab {
    position: relative;
    padding: 0.85rem 0.6rem;
    font-size: 0.8125rem;
    font-weight: 600;
    color: var(--pf-muted);
    white-space: nowrap;
    background: none;
    border: 0;
    cursor: pointer;
    transition: color 0.15s ease;
}

.pf-tab::after {
    content: "";
    position: absolute;
    left: 0.4rem;
    right: 0.4rem;
    bottom: -1px;
    height: 2px;
    background: transparent;
}

.pf-tab:hover { color: var(--pf-ink); }
.pf-tab.is-active { color: var(--pf-ink); }
.pf-tab.is-active::after { background: var(--pf-accent); }

.pf-pane { display: none; padding: 1.35rem; }
.pf-pane.is-active { display: block; }

.pf-lock {
    display: flex;
    align-items: flex-start;
    gap: 0.6rem;
    margin-bottom: 1.25rem;
    padding: 0.7rem 0.85rem;
    font-size: 0.78rem;
    line-height: 1.5;
    color: var(--pf-muted);
    background: var(--pf-subtle);
    border: 1px solid var(--pf-soft);
    border-radius: 6px;
}

.pf-lock i { margin-top: 0.1rem; color: var(--pf-faint); }

.pf .form-label {
    margin-bottom: 0.3rem;
    font-size: 0.78rem;
    font-weight: 500;
    color: var(--pf-text);
}

.pf .form-control,
.pf .form-select {
    font-size: 0.84rem;
    border-color: var(--pf-strong);
    border-radius: 6px;
}

.pf .form-control:focus,
.pf .form-select:focus {
    border-color: var(--pf-accent);
    box-shadow: 0 0 0 3px rgba(11, 103, 178, 0.15);
}

.pf .form-control[readonly] {
    color: var(--pf-muted);
    background: var(--pf-subtle);
    border-color: var(--pf-line);
}

.pf-req { color: #B42318; }
.pf-hint { margin-top: 0.3rem; font-size: 0.74rem; color: var(--pf-muted); }
.pf-hint.is-error { color: #B42318; }

.pf-pw { position: relative; }
.pf-pw .form-control { padding-right: 2.6rem; }

.pf-pw button {
    position: absolute;
    right: 0.35rem;
    top: 50%;
    transform: translateY(-50%);
    padding: 0.25rem 0.4rem;
    color: var(--pf-muted);
    background: none;
    border: 0;
    border-radius: 4px;
}

.pf-pw button:hover { color: var(--pf-ink); }

.pf-strength { display: flex; gap: 4px; margin-top: 0.45rem; }

.pf-strength span {
    flex: 1;
    height: 3px;
    background: var(--pf-soft);
    border-radius: 2px;
    transition: background-color 0.2s ease;
}

.pf-form-foot {
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
    margin-top: 1.5rem;
    padding-top: 1rem;
    border-top: 1px solid var(--pf-soft);
}

.pf-form-foot--start { justify-content: flex-start; }

.pf .btn { font-size: 0.8125rem; font-weight: 600; border-radius: 6px; }
.pf .btn-light { color: var(--pf-text); background: #FFFFFF; border-color: var(--pf-line); }
.pf .btn-light:hover { background: var(--pf-subtle); border-color: var(--pf-strong); }

/* ---------- Responsive ---------- */

@media (max-width: 992px) {
    .pf-grid { grid-template-columns: minmax(0, 1fr); }
}

@media (max-width: 640px) {
    .pf-head { flex-wrap: wrap; padding: 1rem; }
    .pf-avatar { width: 60px; height: 60px; font-size: 1.15rem; }
    .pf-head-actions { width: 100%; }
    .pf-head-actions .pf-btn { flex: 1; justify-content: center; }
    .pf-head-actions form { flex: 1; display: flex; }
    .pf-pane { padding: 1rem; }
}
</style>

<div class="pf">

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show pf-alert" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i><?= esc(session()->getFlashdata('success')) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show pf-alert" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= esc(session()->getFlashdata('error')) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- =====================================================
         HEADER
         ===================================================== -->
    <section class="pf-card pf-head" aria-label="Profile">
        <?php if ($hasAvatar): ?>
            <img src="<?= esc($avatarUrl) ?>" alt="Profile photo" class="pf-avatar" id="pfAvatarImg">
        <?php else: ?>
            <div class="pf-avatar" id="pfAvatarImg" aria-label="<?= esc($user['full_name']) ?>"><?= esc($initials) ?></div>
        <?php endif; ?>

        <div class="pf-identity">
            <h2 class="pf-name"><?= esc($user['full_name']) ?></h2>
            <p class="pf-meta">
                <span class="pf-meta-role"><?= esc($roleLabel) ?></span>
                <span class="pf-meta-sep" aria-hidden="true">·</span>
                <span class="pf-mono">@<?= esc($user['username']) ?></span>
                <span class="pf-meta-sep" aria-hidden="true">·</span>
                <span>Member since <?= $fmtDate($user['created_at']) ?></span>
                <span class="pf-meta-sep" aria-hidden="true">·</span>
                <span class="pf-status pf-status--<?= $user['status'] === 'active' ? 'active' : 'inactive' ?>"><?= esc(ucfirst($user['status'])) ?></span>
            </p>
        </div>

        <div class="pf-head-actions">
            <form action="<?= base_url($prefix . '/profile/avatar') ?>" method="post"
                  enctype="multipart/form-data" id="pfAvatarForm">
                <?= csrf_field() ?>
                <label class="pf-btn">
                    <i class="bi bi-camera" aria-hidden="true"></i>
                    <span><?= $hasAvatar ? 'Change photo' : 'Add photo' ?></span>
                    <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp,image/gif" hidden
                           onchange="if (this.files.length) { if (this.files[0].size > 2097152) { alert('Please choose an image no larger than 2 MB.'); this.value=''; return; } this.form.submit(); }">
                </label>
            </form>

            <?php if ($hasAvatar): ?>
                <form action="<?= base_url($prefix . '/profile/avatar/remove') ?>" method="post"
                      onsubmit="return confirm('Remove your profile photo?');">
                    <?= csrf_field() ?>
                    <button type="submit" class="pf-btn pf-btn--quiet">Remove</button>
                </form>
            <?php endif; ?>
        </div>
    </section>

    <div class="pf-grid">

        <!-- =================================================
             DETAILS
             ================================================= -->
        <aside class="pf-card pf-side">

            <div class="pf-section">
                <div class="pf-complete-head">
                    <h3 class="pf-section-title">Profile completeness</h3>
                    <span class="pf-complete-val"><?= $completeness ?>%</span>
                </div>
                <div class="pf-progress" role="img" aria-label="<?= $filled ?> of <?= count($completeFields) ?> details filled in">
                    <span style="width: <?= $completeness ?>%"></span>
                </div>
                <?php if ($completeness < 100): ?>
                    <p class="pf-hint mb-0 mt-2"><?= $filled ?> of <?= count($completeFields) ?> details filled in. Complete them so colleagues and the clinic can reach you.</p>
                <?php endif; ?>
            </div>

            <?php if (! empty($user['bio'])): ?>
                <div class="pf-section">
                    <h3 class="pf-section-title">About</h3>
                    <p class="pf-about"><?= esc($user['bio']) ?></p>
                </div>
            <?php endif; ?>

            <div class="pf-section">
                <h3 class="pf-section-title">Contact</h3>
                <dl class="pf-dl">
                    <div><dt>Email</dt><dd><?= esc($user['email']) ?></dd></div>
                    <div><dt>Contact number</dt>
                        <dd class="<?= empty($user['contact_number']) ? 'pf-empty' : '' ?>"><?= esc($user['contact_number'] ?: 'Not set') ?></dd></div>
                    <div><dt>Address</dt>
                        <dd class="<?= empty($user['address']) ? 'pf-empty' : '' ?>"><?= esc($user['address'] ?: 'Not set') ?></dd></div>
                </dl>
            </div>

            <div class="pf-section">
                <h3 class="pf-section-title">Personal</h3>
                <dl class="pf-dl">
                    <div><dt>Gender</dt>
                        <dd class="<?= empty($user['gender']) ? 'pf-empty' : '' ?>"><?= esc($user['gender'] ?: 'Not set') ?></dd></div>
                    <div><dt>Birthdate</dt>
                        <dd class="<?= empty($user['birthdate']) ? 'pf-empty' : '' ?>">
                            <?= empty($user['birthdate']) ? 'Not set' : $fmtDate($user['birthdate'], 'F j, Y') . ' · ' . $age . ' yrs' ?>
                        </dd></div>
                </dl>
            </div>

            <div class="pf-section">
                <h3 class="pf-section-title">Account</h3>
                <dl class="pf-dl">
                    <?php if ($needsPrc || ! empty($user['prc_license'])): ?>
                        <div><dt>PRC license no.</dt>
                            <dd class="<?= empty($user['prc_license']) ? 'pf-empty' : 'pf-mono' ?>"><?= esc($user['prc_license'] ?: 'Not on file — ask an administrator') ?></dd></div>
                    <?php endif; ?>
                    <div><dt>Last login</dt><dd><?= $fmtDate($user['last_login_at'] ?? null, 'M j, Y · g:i A') ?></dd></div>
                    <div><dt>Password last changed</dt><dd><?= $fmtDate($user['password_changed_at'] ?? null) ?></dd></div>
                    <div><dt>Profile updated</dt><dd><?= $fmtDate($user['updated_at'], 'M j, Y · g:i A') ?></dd></div>
                </dl>
            </div>
        </aside>

        <!-- =================================================
             ACTIVITY + EDIT
             ================================================= -->
        <div class="pf-main">

            <?php if (! empty($stats)): ?>
                <?php
                $pfCards = [];
                foreach (array_values($stats) as $i => $s) {
                    $pfCards[] = [
                        'key'     => 'stat' . $i,
                        'label'   => (string) $s['label'],
                        'display' => (string) $s['value'],
                        'context' => (string) ($s['hint'] ?? ''),
                    ];
                }
                ?>
                <?= view('partials/kpi_strip', [
                    'id'      => 'profile',
                    'title'   => 'Activity',
                    'note'    => 'As of ' . date('M j, g:i A'),
                    'columns' => min(4, count($pfCards)),
                    'cards'   => $pfCards,
                    'trends'  => [],
                ]) ?>
            <?php endif; ?>

            <div class="pf-card">
                <div class="pf-tabs" role="tablist">
                    <button type="button" class="pf-tab <?= $activeTab === 'info' ? 'is-active' : '' ?>" data-pf-tab="info" role="tab">
                        Personal information
                    </button>
                    <button type="button" class="pf-tab <?= $activeTab === 'security' ? 'is-active' : '' ?>" data-pf-tab="security" role="tab">
                        Security
                    </button>
                </div>

                <!-- PERSONAL INFORMATION -->
                <div class="pf-pane <?= $activeTab === 'info' ? 'is-active' : '' ?>" data-pf-pane="info" role="tabpanel">

                    <div class="pf-lock">
                        <i class="bi bi-lock" aria-hidden="true"></i>
                        <span>Your name, username, role and PRC license are managed by the administrator, since lab and X-ray results are signed under them. Contact an administrator to change these.</span>
                    </div>

                    <form action="<?= base_url($prefix . '/profile/update') ?>" method="post" novalidate>
                        <?= csrf_field() ?>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Full name</label>
                                <input type="text" class="form-control" value="<?= esc($user['full_name']) ?>" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Username</label>
                                <input type="text" class="form-control" value="<?= esc($user['username']) ?>" readonly>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="pfEmail">Email address <span class="pf-req">*</span></label>
                                <input type="email" class="form-control" id="pfEmail" name="email" maxlength="100" required
                                       value="<?= esc($old('email')) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="pfContact">Contact number</label>
                                <input type="tel" class="form-control" id="pfContact" name="contact_number" maxlength="20"
                                       placeholder="e.g. 0917 123 4567" pattern="[0-9+\-\s()]+"
                                       value="<?= esc($old('contact_number')) ?>">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="pfGender">Gender</label>
                                <select class="form-select" id="pfGender" name="gender">
                                    <option value="">Prefer not to say</option>
                                    <?php foreach (['Male', 'Female', 'Other'] as $g): ?>
                                        <option value="<?= $g ?>" <?= $old('gender') === $g ? 'selected' : '' ?>><?= $g ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="pfBirth">Birthdate</label>
                                <input type="date" class="form-control" id="pfBirth" name="birthdate" max="<?= date('Y-m-d') ?>"
                                       value="<?= esc($old('birthdate')) ?>">
                            </div>

                            <div class="col-12">
                                <label class="form-label" for="pfAddress">Address</label>
                                <input type="text" class="form-control" id="pfAddress" name="address" maxlength="255"
                                       placeholder="House no., street, barangay, city"
                                       value="<?= esc($old('address')) ?>">
                            </div>

                            <div class="col-12">
                                <label class="form-label" for="pfBio">About me</label>
                                <textarea class="form-control" id="pfBio" name="bio" rows="3" maxlength="500"
                                          placeholder="Specialization, schedule, or a short note for colleagues"><?= esc($old('bio')) ?></textarea>
                                <div class="pf-hint">Up to 500 characters.</div>
                            </div>
                        </div>

                        <div class="pf-form-foot">
                            <button type="reset" class="btn btn-light">Reset</button>
                            <button type="submit" class="btn btn-primary">Save changes</button>
                        </div>
                    </form>
                </div>

                <!-- SECURITY -->
                <div class="pf-pane <?= $activeTab === 'security' ? 'is-active' : '' ?>" data-pf-pane="security" role="tabpanel">

                    <form action="<?= base_url($prefix . '/profile/password') ?>" method="post" autocomplete="off" style="max-width:480px">
                        <?= csrf_field() ?>

                        <div class="mb-3">
                            <label class="form-label" for="pfCurPw">Current password</label>
                            <div class="pf-pw">
                                <input type="password" class="form-control" id="pfCurPw" name="current_password" required autocomplete="current-password">
                                <button type="button" data-pf-reveal aria-label="Show password"><i class="bi bi-eye"></i></button>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="pfNewPw">New password</label>
                            <div class="pf-pw">
                                <input type="password" class="form-control" id="pfNewPw" name="new_password" required minlength="8" autocomplete="new-password">
                                <button type="button" data-pf-reveal aria-label="Show password"><i class="bi bi-eye"></i></button>
                            </div>
                            <div class="pf-strength" id="pfStrength"><span></span><span></span><span></span><span></span></div>
                            <div class="pf-hint">At least 8 characters, with both letters and numbers.</div>
                        </div>

                        <div class="mb-1">
                            <label class="form-label" for="pfConfirmPw">Confirm new password</label>
                            <div class="pf-pw">
                                <input type="password" class="form-control" id="pfConfirmPw" name="confirm_password" required autocomplete="new-password">
                                <button type="button" data-pf-reveal aria-label="Show password"><i class="bi bi-eye"></i></button>
                            </div>
                            <div class="pf-hint is-error" id="pfMismatch" hidden>Passwords do not match.</div>
                        </div>

                        <div class="pf-form-foot pf-form-foot--start">
                            <button type="submit" class="btn btn-primary">Update password</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var root = document.querySelector('.pf');
    if (!root || root.dataset.bound) return;
    root.dataset.bound = '1';

    // Tabs
    root.addEventListener('click', function (e) {
        var tab = e.target.closest('[data-pf-tab]');
        if (tab) {
            var name = tab.getAttribute('data-pf-tab');
            root.querySelectorAll('[data-pf-tab]').forEach(function (t) { t.classList.toggle('is-active', t === tab); });
            root.querySelectorAll('[data-pf-pane]').forEach(function (p) { p.classList.toggle('is-active', p.getAttribute('data-pf-pane') === name); });
            return;
        }

        // Show / hide password
        var eye = e.target.closest('[data-pf-reveal]');
        if (eye) {
            var input = eye.parentNode.querySelector('input');
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            eye.querySelector('i').className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
        }
    });

    // Password strength + match
    var newPw = root.querySelector('#pfNewPw');
    var confirmPw = root.querySelector('#pfConfirmPw');
    var bars = root.querySelectorAll('#pfStrength span');
    var mismatch = root.querySelector('#pfMismatch');
    var colors = ['#B42318', '#DC6803', '#0B67B2', '#17B26A'];

    function checkMatch() {
        mismatch.hidden = !confirmPw.value || confirmPw.value === newPw.value;
    }

    newPw.addEventListener('input', function () {
        var v = newPw.value, score = 0;
        if (v.length >= 8) score++;
        if (/[a-z]/.test(v) && /[A-Z]/.test(v)) score++;
        if (/\d/.test(v)) score++;
        if (/[^A-Za-z0-9]/.test(v) || v.length >= 12) score++;
        bars.forEach(function (b, i) { b.style.background = i < score ? colors[score - 1] : '#EBF0F5'; });
        checkMatch();
    });
    confirmPw.addEventListener('input', checkMatch);
})();
</script>

<?= $this->endSection() ?>
