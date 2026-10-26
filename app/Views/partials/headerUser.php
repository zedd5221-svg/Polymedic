<?php
/*
 * Header user chip — links to My Profile.
 *
 * Expects: $prefix (role URL prefix), $fallbackName, $roleLabel
 */
$headerName   = session()->get('full_name') ?: $fallbackName;
$headerAvatar = session()->get('avatar');
$hasPhoto     = $headerAvatar && is_file(FCPATH . $headerAvatar);
$onProfile    = url_is($prefix . '/profile');
?>
<style>
    a.header-user { text-decoration: none; cursor: pointer; transition: background .15s; }
    a.header-user:hover, a.header-user.is-current { background: #f1f5fb; }
    .avatar-small.has-photo { padding: 0; overflow: hidden; }
    .avatar-small.has-photo img { width: 100%; height: 100%; object-fit: cover; display: block; }
</style>
<a href="<?= base_url($prefix . '/profile') ?>"
   class="header-user<?= $onProfile ? ' is-current' : '' ?>"
   title="My Profile">

    <div class="avatar-small<?= $hasPhoto ? ' has-photo' : '' ?>">
        <?php if ($hasPhoto): ?>
            <img src="<?= base_url($headerAvatar) ?>" alt="">
        <?php else: ?>
            <i class="bi bi-person-fill"></i>
        <?php endif; ?>
    </div>

    <div class="user-details">
        <span class="user-name-header"><?= esc($headerName) ?></span>
        <span class="user-role-header"><?= esc($roleLabel) ?></span>
    </div>

</a>
