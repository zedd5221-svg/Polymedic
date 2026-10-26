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
.pf {
    --pf-blue: #1976d2;
    --pf-blue-dark: #0d47a1;
    --pf-blue-light: #e3f2fd;
    --pf-border: #e5e9f0;
    --pf-muted: #6b7280;
    --pf-text: #111827;
    max-width: 1180px;
    margin: 0 auto;
}

.pf-card {
    background: #fff;
    border: 1px solid var(--pf-border);
    border-radius: 14px;
    box-shadow: 0 1px 2px rgba(16, 24, 40, .04);
}

/* ---------- Identity banner ---------- */

.pf-hero {
    overflow: hidden;
    margin-bottom: 1.25rem;
}

.pf-hero-band {
    height: 96px;
    background: linear-gradient(120deg, #123566 0%, #1976d2 60%, #43a3f5 100%);
}

.pf-hero-body {
    display: flex;
    align-items: flex-end;
    gap: 1.25rem;
    padding: 0 1.5rem 1.25rem;
    margin-top: -48px;
    flex-wrap: wrap;
}

.pf-avatar-wrap {
    position: relative;
    flex-shrink: 0;
}

.pf-avatar {
    width: 112px;
    height: 112px;
    border-radius: 50%;
    border: 4px solid #fff;
    background: var(--pf-blue-light);
    color: var(--pf-blue);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.2rem;
    font-weight: 700;
    letter-spacing: .02em;
    object-fit: cover;
    box-shadow: 0 4px 14px rgba(13, 71, 161, .18);
}

.pf-avatar-btn {
    position: absolute;
    right: 2px;
    bottom: 4px;
    width: 34px;
    height: 34px;
    border-radius: 50%;
    border: 2px solid #fff;
    background: var(--pf-blue);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background .15s;
}
.pf-avatar-btn:hover { background: var(--pf-blue-dark); }

.pf-identity {
    flex: 1;
    min-width: 220px;
    padding-bottom: .25rem;
}

.pf-name {
    font-size: 1.45rem;
    font-weight: 700;
    color: var(--pf-text);
    margin: 0;
    line-height: 1.2;
}

.pf-meta {
    display: flex;
    flex-wrap: wrap;
    gap: .5rem 1rem;
    margin-top: .4rem;
    color: var(--pf-muted);
    font-size: .85rem;
}
.pf-meta i { margin-right: .3rem; }

.pf-chip {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    padding: .2rem .65rem;
    border-radius: 999px;
    font-size: .75rem;
    font-weight: 600;
}
.pf-chip--role   { background: var(--pf-blue-light); color: var(--pf-blue-dark); }
.pf-chip--active { background: #e7f6ec; color: #1e7b3a; }
.pf-chip--inactive { background: #fdecec; color: #b42318; }

.pf-hero-actions {
    display: flex;
    gap: .5rem;
    padding-bottom: .25rem;
}

/* ---------- Layout ---------- */

.pf-grid {
    display: grid;
    grid-template-columns: 320px 1fr;
    gap: 1.25rem;
    align-items: stretch;   /* both columns share one height */
}

/* Left card and right column fill that height, so their bottoms always
   line up — with the KPIs open or closed, on either tab. */
.pf-side {
    display: flex;
    flex-direction: column;
}
.pf-side > .pf-section:last-child { flex: 1 1 auto; }

.pf-main {
    display: flex;
    flex-direction: column;
    min-width: 0;
}
.pf-main > .pf-card:last-child {
    flex: 1 1 auto;
    display: flex;
    flex-direction: column;
}
.pf-main > .pf-card:last-child > .pf-pane.is-active { flex: 1 1 auto; }

.pf-section {
    padding: 1.25rem 1.35rem;
}
.pf-section + .pf-section { border-top: 1px solid var(--pf-border); }

.pf-section-title {
    font-size: .72rem;
    text-transform: uppercase;
    letter-spacing: .07em;
    font-weight: 700;
    color: var(--pf-muted);
    margin-bottom: .9rem;
}

.pf-dl {
    display: grid;
    gap: .75rem;
    margin: 0;
}
.pf-dl div { display: flex; gap: .75rem; align-items: flex-start; }
.pf-dl i {
    width: 30px;
    height: 30px;
    flex-shrink: 0;
    border-radius: 8px;
    background: #f3f6fb;
    color: var(--pf-blue);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: .9rem;
}
.pf-dl dt { font-size: .72rem; color: var(--pf-muted); font-weight: 500; }
.pf-dl dd { margin: 0; font-size: .88rem; color: var(--pf-text); font-weight: 600; word-break: break-word; }
.pf-dl .pf-empty { color: #9ca3af; font-weight: 400; font-style: italic; }

/* completeness */
.pf-progress {
    height: 8px;
    border-radius: 99px;
    background: #eef2f7;
    overflow: hidden;
}
.pf-progress span {
    display: block;
    height: 100%;
    border-radius: 99px;
    background: linear-gradient(90deg, #1976d2, #43a3f5);
}

/* ---------- KPIs (7 per role: 1 featured + 6) ----------
   The grid follows the width of the profile column itself (container
   query), so it adapts to the sidebar card next to it, not the window:
     >= 620px : 4 columns -> featured spans 2, then 2 + 4 = two even rows
     >= 340px : 2 columns -> featured full width, then 3 rows of 2
     narrower : 1 column
*/

.pf-kpi-wrap {
    container-type: inline-size;
    margin-bottom: 1.25rem;
}
.pf-kpi-head {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: .75rem;
    margin: 0 .15rem .65rem;
}
.pf-kpi-head h3 {
    margin: 0;
    font-size: .8rem;
    font-weight: 700;
    letter-spacing: .06em;
    text-transform: uppercase;
    color: var(--pf-muted);
}
.pf-kpi-head span { font-size: .75rem; color: var(--pf-muted); }

.pf-kpis {
    display: grid;
    grid-template-columns: 1fr;
    gap: .75rem;
}
@container (min-width: 340px) {
    .pf-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .pf-kpi--featured { grid-column: 1 / -1; }
}
@container (min-width: 620px) {
    .pf-kpis { grid-template-columns: repeat(4, minmax(0, 1fr)); }
    .pf-kpi--featured { grid-column: span 2; }
}

.pf-kpi {
    --tone: #1976d2;
    --tone-soft: #e3f2fd;
    position: relative;
    display: flex;
    flex-direction: column;
    gap: .55rem;
    min-width: 0;
    padding: .95rem 1rem 1rem;
    overflow: hidden;
    transition: box-shadow .15s ease, transform .15s ease;
}
.pf-kpi::before {
    content: '';
    position: absolute;
    inset: 0 auto 0 0;
    width: 3px;
    background: var(--tone);
    opacity: .85;
}
.pf-kpi:hover { box-shadow: 0 6px 18px rgba(16, 24, 40, .08); transform: translateY(-1px); }

.pf-kpi-top { display: flex; align-items: center; justify-content: space-between; gap: .5rem; }
.pf-kpi-label {
    font-size: .74rem;
    font-weight: 600;
    color: var(--pf-muted);
    line-height: 1.25;
}
.pf-kpi-icon {
    width: 32px;
    height: 32px;
    flex-shrink: 0;
    border-radius: 9px;
    display: grid;
    place-items: center;
    background: var(--tone-soft);
    color: var(--tone);
    font-size: .95rem;
}
.pf-kpi-value {
    font-size: 1.35rem;
    font-weight: 800;
    color: var(--pf-text);
    line-height: 1.1;
    font-variant-numeric: tabular-nums;
    overflow-wrap: anywhere;
}
.pf-kpi-hint {
    margin-top: auto;
    font-size: .72rem;
    color: var(--pf-muted);
    line-height: 1.3;
}

/* Featured: the headline number, wider and tinted */
.pf-kpi--featured {
    background: linear-gradient(135deg, var(--tone-soft) 0%, #fff 70%);
    border-color: color-mix(in srgb, var(--tone) 22%, var(--pf-border));
}
.pf-kpi--featured .pf-kpi-value { font-size: 1.9rem; }
.pf-kpi--featured .pf-kpi-icon { width: 40px; height: 40px; font-size: 1.15rem; background: #fff; }

/* Tones */
.pf-kpi--blue   { --tone: #1976d2; --tone-soft: #e3f2fd; }
.pf-kpi--green  { --tone: #15803d; --tone-soft: #e8f7ee; }
.pf-kpi--teal   { --tone: #0f766e; --tone-soft: #e6f6f4; }
.pf-kpi--violet { --tone: #6d28d9; --tone-soft: #f1ebfe; }
.pf-kpi--amber  { --tone: #b45309; --tone-soft: #fdf3e2; }
.pf-kpi--rose   { --tone: #be123c; --tone-soft: #fdecef; }
.pf-kpi--slate  { --tone: #475569; --tone-soft: #eef2f6; }

/* Show / hide */
.pf-kpi-tools { display: flex; align-items: center; gap: .75rem; }
.pf-kpi-toggle {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    padding: .3rem .7rem;
    border: 1px solid var(--pf-border);
    border-radius: 999px;
    background: #fff;
    color: var(--pf-blue);
    font-size: .75rem;
    font-weight: 600;
    cursor: pointer;
    transition: background .15s ease, border-color .15s ease;
}
.pf-kpi-toggle:hover { background: var(--pf-blue-light); border-color: #bfdbfe; }
.pf-kpi-toggle:focus-visible { outline: 2px solid var(--pf-blue); outline-offset: 2px; }
.pf-kpi-toggle i { transition: transform .2s ease; }
.pf-kpi-wrap.is-collapsed .pf-kpi-toggle i { transform: rotate(-180deg); }

/* Animates height without measuring it: 1fr <-> 0fr */
.pf-kpi-body {
    display: grid;
    grid-template-rows: 1fr;
    transition: grid-template-rows .25s ease, opacity .2s ease;
}
.pf-kpi-body > .pf-kpis { min-height: 0; overflow: hidden; padding: 4px 4px 12px; margin: -4px -4px -12px; } /* room for the hover shadow */
.pf-kpi-wrap.is-collapsed .pf-kpi-body { grid-template-rows: 0fr; opacity: 0; }
.pf-kpi-wrap.is-collapsed .pf-kpi-head { margin-bottom: 0; }
.pf-kpi-wrap.is-collapsed .pf-kpi-asof { display: none; }
.pf-kpi-collapsed-note { display: none; font-size: .75rem; color: var(--pf-muted); }
.pf-kpi-wrap.is-collapsed .pf-kpi-collapsed-note { display: inline; }

@media (prefers-reduced-motion: reduce) {
    .pf-kpi { transition: none; }
    .pf-kpi:hover { transform: none; }
    .pf-kpi-body, .pf-kpi-toggle i { transition: none; }
}

/* ---------- Tabs + forms ---------- */

.pf-tabs {
    display: flex;
    gap: .25rem;
    padding: .5rem .75rem 0;
    border-bottom: 1px solid var(--pf-border);
}
.pf-tab {
    border: 0;
    background: none;
    padding: .7rem .9rem;
    font-size: .88rem;
    font-weight: 600;
    color: var(--pf-muted);
    border-bottom: 2px solid transparent;
    margin-bottom: -1px;
    cursor: pointer;
}
.pf-tab:hover { color: var(--pf-text); }
.pf-tab.is-active { color: var(--pf-blue); border-bottom-color: var(--pf-blue); }
.pf-tab i { margin-right: .35rem; }

.pf-pane { display: none; padding: 1.35rem; }
.pf-pane.is-active { display: block; }

.pf .form-label { font-size: .8rem; font-weight: 600; color: #374151; margin-bottom: .3rem; }
.pf .form-control,
.pf .form-select { font-size: .88rem; border-radius: 9px; border-color: #d6dce5; }
.pf .form-control:focus,
.pf .form-select:focus { border-color: var(--pf-blue); box-shadow: 0 0 0 .2rem rgba(25, 118, 210, .15); }
.pf .form-control[readonly] { background: #f6f8fb; color: var(--pf-muted); }
.pf-hint { font-size: .74rem; color: var(--pf-muted); margin-top: .3rem; }

.pf-lock {
    display: flex;
    gap: .6rem;
    align-items: flex-start;
    background: #f6f8fb;
    border: 1px dashed #cfd8e3;
    border-radius: 10px;
    padding: .7rem .9rem;
    font-size: .8rem;
    color: var(--pf-muted);
    margin-bottom: 1.1rem;
}

.pf-pw { position: relative; }
.pf-pw .form-control { padding-right: 2.6rem; }
.pf-pw button {
    position: absolute;
    right: .4rem;
    top: 50%;
    transform: translateY(-50%);
    border: 0;
    background: none;
    color: var(--pf-muted);
    padding: .25rem .4rem;
}

.pf-strength { display: flex; gap: 4px; margin-top: .45rem; }
.pf-strength span { flex: 1; height: 4px; border-radius: 4px; background: #e5e7eb; transition: background .2s; }

.pf .btn-primary { background: var(--pf-blue); border-color: var(--pf-blue); }
.pf .btn-primary:hover { background: var(--pf-blue-dark); border-color: var(--pf-blue-dark); }
.pf .btn { font-size: .85rem; border-radius: 9px; font-weight: 600; }

.pf-alert { border-radius: 10px; font-size: .88rem; }

/* ---------- Responsive ---------- */

@media (max-width: 992px) {
    .pf-grid { grid-template-columns: 1fr; }
}
@media (max-width: 640px) {
    .pf-hero-body { flex-direction: column; align-items: flex-start; padding: 0 1rem 1rem; }
    .pf-hero-actions { width: 100%; }
    .pf-tabs { overflow-x: auto; }
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
         IDENTITY BANNER
         ===================================================== -->
    <div class="pf-card pf-hero">
        <div class="pf-hero-band"></div>
        <div class="pf-hero-body">

            <div class="pf-avatar-wrap">
                <?php if ($hasAvatar): ?>
                    <img src="<?= esc($avatarUrl) ?>" alt="Profile photo" class="pf-avatar" id="pfAvatarImg">
                <?php else: ?>
                    <div class="pf-avatar" id="pfAvatarImg" aria-label="<?= esc($user['full_name']) ?>"><?= esc($initials) ?></div>
                <?php endif; ?>

                <form action="<?= base_url($prefix . '/profile/avatar') ?>" method="post"
                      enctype="multipart/form-data" id="pfAvatarForm">
                    <?= csrf_field() ?>
                    <label class="pf-avatar-btn" title="Change photo">
                        <i class="bi bi-camera-fill"></i>
                        <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp,image/gif" hidden
                               onchange="if (this.files.length) { if (this.files[0].size > 2097152) { alert('Please choose an image no larger than 2 MB.'); this.value=''; return; } this.form.submit(); }">
                    </label>
                </form>
            </div>

            <div class="pf-identity">
                <h2 class="pf-name"><?= esc($user['full_name']) ?></h2>
                <div class="pf-meta">
                    <span class="pf-chip pf-chip--role"><i class="bi bi-person-badge"></i><?= esc($roleLabel) ?></span>
                    <span class="pf-chip pf-chip--<?= $user['status'] === 'active' ? 'active' : 'inactive' ?>">
                        <i class="bi bi-circle-fill" style="font-size:.45rem"></i><?= esc(ucfirst($user['status'])) ?>
                    </span>
                    <span><i class="bi bi-at"></i><?= esc($user['username']) ?></span>
                    <span><i class="bi bi-calendar3"></i>Member since <?= $fmtDate($user['created_at']) ?></span>
                </div>
            </div>

            <?php if ($hasAvatar): ?>
                <div class="pf-hero-actions">
                    <form action="<?= base_url($prefix . '/profile/avatar/remove') ?>" method="post"
                          onsubmit="return confirm('Remove your profile photo?');">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-trash3 me-1"></i>Remove photo
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="pf-grid">

        <!-- =================================================
             LEFT — summary
             ================================================= -->
        <aside class="pf-card pf-side">

            <div class="pf-section">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="pf-section-title mb-0">Profile completeness</div>
                    <strong style="font-size:.85rem;color:var(--pf-blue)"><?= $completeness ?>%</strong>
                </div>
                <div class="pf-progress"><span style="width: <?= $completeness ?>%"></span></div>
                <?php if ($completeness < 100): ?>
                    <div class="pf-hint mt-2">Fill in your personal details so colleagues and the clinic can reach you.</div>
                <?php endif; ?>
            </div>

            <div class="pf-section">
                <div class="pf-section-title">Contact</div>
                <dl class="pf-dl">
                    <div><i class="bi bi-envelope"></i><span><dt>Email</dt><dd><?= esc($user['email']) ?></dd></span></div>
                    <div><i class="bi bi-telephone"></i><span><dt>Contact number</dt>
                        <dd class="<?= empty($user['contact_number']) ? 'pf-empty' : '' ?>"><?= esc($user['contact_number'] ?: 'Not set') ?></dd></span></div>
                    <div><i class="bi bi-geo-alt"></i><span><dt>Address</dt>
                        <dd class="<?= empty($user['address']) ? 'pf-empty' : '' ?>"><?= esc($user['address'] ?: 'Not set') ?></dd></span></div>
                </dl>
            </div>

            <div class="pf-section">
                <div class="pf-section-title">Personal</div>
                <dl class="pf-dl">
                    <div><i class="bi bi-gender-ambiguous"></i><span><dt>Gender</dt>
                        <dd class="<?= empty($user['gender']) ? 'pf-empty' : '' ?>"><?= esc($user['gender'] ?: 'Not set') ?></dd></span></div>
                    <div><i class="bi bi-cake2"></i><span><dt>Birthdate</dt>
                        <dd class="<?= empty($user['birthdate']) ? 'pf-empty' : '' ?>">
                            <?= empty($user['birthdate']) ? 'Not set' : $fmtDate($user['birthdate'], 'F j, Y') . ' (' . $age . ' yrs)' ?>
                        </dd></span></div>
                </dl>
            </div>

            <div class="pf-section">
                <div class="pf-section-title">Account</div>
                <dl class="pf-dl">
                    <?php if ($needsPrc || ! empty($user['prc_license'])): ?>
                        <div><i class="bi bi-patch-check"></i><span><dt>PRC license no.</dt>
                            <dd class="<?= empty($user['prc_license']) ? 'pf-empty' : '' ?>"><?= esc($user['prc_license'] ?: 'Not on file — ask an administrator') ?></dd></span></div>
                    <?php endif; ?>
                    <div><i class="bi bi-box-arrow-in-right"></i><span><dt>Last login</dt><dd><?= $fmtDate($user['last_login_at'] ?? null, 'M j, Y · g:i A') ?></dd></span></div>
                    <div><i class="bi bi-shield-lock"></i><span><dt>Password last changed</dt><dd><?= $fmtDate($user['password_changed_at'] ?? null) ?></dd></span></div>
                    <div><i class="bi bi-clock-history"></i><span><dt>Profile updated</dt><dd><?= $fmtDate($user['updated_at'], 'M j, Y · g:i A') ?></dd></span></div>
                </dl>
            </div>
        </aside>

        <!-- =================================================
             RIGHT — activity + edit
             ================================================= -->
        <div class="pf-main">

            <?php if (! empty($stats)): ?>
                <section class="pf-kpi-wrap" id="pfKpiWrap" aria-labelledby="pfKpiTitle">
                    <div class="pf-kpi-head">
                        <h3 id="pfKpiTitle">Activity overview</h3>
                        <div class="pf-kpi-tools">
                            <span class="pf-kpi-asof">As of <?= esc(date('M j, Y · g:i A')) ?></span>
                            <span class="pf-kpi-collapsed-note"><?= count($stats) ?> indicators hidden</span>
                            <button type="button" class="pf-kpi-toggle" id="pfKpiToggle"
                                    aria-expanded="true" aria-controls="pfKpiBody">
                                <span class="pf-kpi-toggle-text">Hide</span>
                                <i class="bi bi-chevron-up" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                    <div class="pf-kpi-body" id="pfKpiBody">
                    <div class="pf-kpis">
                        <?php foreach ($stats as $s): ?>
                            <?php
                                $tone     = preg_replace('/[^a-z]/', '', (string) ($s['tone'] ?? 'blue')) ?: 'blue';
                                $featured = ! empty($s['featured']);
                            ?>
                            <article class="pf-card pf-kpi pf-kpi--<?= $tone ?><?= $featured ? ' pf-kpi--featured' : '' ?>">
                                <div class="pf-kpi-top">
                                    <span class="pf-kpi-label"><?= esc($s['label']) ?></span>
                                    <span class="pf-kpi-icon" aria-hidden="true"><i class="bi <?= esc($s['icon'], 'attr') ?>"></i></span>
                                </div>
                                <div class="pf-kpi-value"><?= esc((string) $s['value']) ?></div>
                                <?php if (! empty($s['hint'])): ?>
                                    <div class="pf-kpi-hint"><?= esc($s['hint']) ?></div>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                    </div>
                    </div>
                </section>
                <script>
                // Apply the remembered state before paint so the KPIs don't flash open.
                (function () {
                    var wrap = document.getElementById('pfKpiWrap');
                    var btn  = document.getElementById('pfKpiToggle');
                    if (!wrap || !btn) return;
                    var KEY = 'polymedic.profile.kpiCollapsed';

                    function set(collapsed, remember) {
                        wrap.classList.toggle('is-collapsed', collapsed);
                        btn.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
                        btn.querySelector('.pf-kpi-toggle-text').textContent = collapsed ? 'Show' : 'Hide';
                        document.getElementById('pfKpiBody').inert = collapsed;
                        if (remember) { try { localStorage.setItem(KEY, collapsed ? '1' : '0'); } catch (e) {} }
                    }

                    var saved = null;
                    try { saved = localStorage.getItem(KEY); } catch (e) {}
                    set(saved === '1', false);

                    btn.addEventListener('click', function () {
                        set(!wrap.classList.contains('is-collapsed'), true);
                    });
                })();
                </script>
            <?php endif; ?>

            <?php if (! empty($user['bio'])): ?>
                <div class="pf-card pf-section mb-3">
                    <div class="pf-section-title">About</div>
                    <p class="mb-0" style="font-size:.9rem;color:#374151;white-space:pre-line"><?= esc($user['bio']) ?></p>
                </div>
            <?php endif; ?>

            <div class="pf-card">
                <div class="pf-tabs" role="tablist">
                    <button type="button" class="pf-tab <?= $activeTab === 'info' ? 'is-active' : '' ?>" data-pf-tab="info" role="tab">
                        <i class="bi bi-person-lines-fill"></i>Personal information
                    </button>
                    <button type="button" class="pf-tab <?= $activeTab === 'security' ? 'is-active' : '' ?>" data-pf-tab="security" role="tab">
                        <i class="bi bi-shield-lock"></i>Security
                    </button>
                </div>

                <!-- PERSONAL INFORMATION -->
                <div class="pf-pane <?= $activeTab === 'info' ? 'is-active' : '' ?>" data-pf-pane="info" role="tabpanel">

                    <div class="pf-lock">
                        <i class="bi bi-lock-fill mt-1"></i>
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
                                <label class="form-label" for="pfEmail">Email address <span class="text-danger">*</span></label>
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

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <button type="reset" class="btn btn-light">Reset</button>
                            <button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1"></i>Save changes</button>
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

                        <div class="mb-4">
                            <label class="form-label" for="pfConfirmPw">Confirm new password</label>
                            <div class="pf-pw">
                                <input type="password" class="form-control" id="pfConfirmPw" name="confirm_password" required autocomplete="new-password">
                                <button type="button" data-pf-reveal aria-label="Show password"><i class="bi bi-eye"></i></button>
                            </div>
                            <div class="pf-hint text-danger" id="pfMismatch" hidden>Passwords do not match.</div>
                        </div>

                        <button type="submit" class="btn btn-primary"><i class="bi bi-key me-1"></i>Update password</button>
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
    var colors = ['#ef4444', '#f59e0b', '#3b82f6', '#22c55e'];

    function checkMatch() {
        mismatch.hidden = !confirmPw.value || confirmPw.value === newPw.value;
    }

    newPw.addEventListener('input', function () {
        var v = newPw.value, score = 0;
        if (v.length >= 8) score++;
        if (/[a-z]/.test(v) && /[A-Z]/.test(v)) score++;
        if (/\d/.test(v)) score++;
        if (/[^A-Za-z0-9]/.test(v) || v.length >= 12) score++;
        bars.forEach(function (b, i) { b.style.background = i < score ? colors[score - 1] : '#e5e7eb'; });
        checkMatch();
    });
    confirmPw.addEventListener('input', checkMatch);
})();
</script>

<?= $this->endSection() ?>
