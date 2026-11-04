<?= $this->extend('Layouts/AdminLayout') ?>

<?= $this->section('pageTitle') ?>User Management<?= $this->endSection() ?>

<?= $this->section('adminContent') ?>

<?php
/* ------------------------------------------------------------------
   View-local helpers only. No controller or query logic is changed.
   ------------------------------------------------------------------ */
$userList     = (isset($users) && is_array($users)) ? $users : [];
$totalUsers   = $total ?? count($userList);
$currentUser  = session()->get('user_id');

/* Full role labels, matching the options in the Add/Edit selects. */
$roleLabels = [
    'admin'        => 'Administrator',
    'receptionist' => 'Receptionist',
    'med_tech'     => 'Medical Technologist',
    'radiologist'  => 'Radiologist',
];

/* Reduce any value to a safe CSS class fragment. */
$slug = static function ($value, $fallback = '') {
    $value = strtolower(trim((string) $value));
    $value = preg_replace('/[^a-z0-9_-]/', '', $value);
    return $value !== '' ? $value : $fallback;
};

/* Initials for users without an uploaded profile photo. */
$initialsOf = static function ($name) {
    $parts = preg_split('/\s+/', trim((string) $name));
    $first = mb_substr($parts[0] ?? '', 0, 1);
    $last  = count($parts) > 1 ? mb_substr(end($parts), 0, 1) : '';
    $out   = mb_strtoupper($first . $last);
    return $out !== '' ? $out : '?';
};

/* "5 mins ago", "Yesterday", then a date. */
$relativeTime = static function (int $ts): string {
    $diff = time() - $ts;
    if ($diff < 60)     { return 'Just now'; }
    if ($diff < 3600)   { $n = (int) floor($diff / 60);   return $n . ($n === 1 ? ' min ago' : ' mins ago'); }
    if ($diff < 86400)  { $n = (int) floor($diff / 3600); return $n . ($n === 1 ? ' hour ago' : ' hours ago'); }
    if ($diff < 172800) { return 'Yesterday'; }
    if ($diff < 604800) { return (int) floor($diff / 86400) . ' days ago'; }
    return date('M j, Y', $ts);
};
?>

<div class="table-card">
    <div class="page-header">
        <div class="page-header-text">
            <h4 class="page-title">User Management</h4>
            <p class="page-subtitle">Manage system users and their roles</p>
        </div>
        <button type="button" class="btn btn-primary btn-add-user" data-bs-toggle="modal" data-bs-target="#addUserModal">
            <i class="bi bi-person-plus" aria-hidden="true"></i>
            <span>Add User</span>
        </button>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show admin-alert" role="alert">
            <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
            <span><?= esc(session()->getFlashdata('success')) ?></span>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Dismiss message"></button>
        </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show admin-alert" role="alert">
            <i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i>
            <span><?= esc(session()->getFlashdata('error')) ?></span>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Dismiss message"></button>
        </div>
    <?php endif; ?>

    <div class="table-toolbar">
        <div class="search-wrapper">
            <i class="bi bi-search" aria-hidden="true"></i>
            <label class="visually-hidden" for="searchUsers">Search users</label>
            <input type="search" class="form-control" placeholder="Search users..." id="searchUsers" autocomplete="off">
        </div>
    </div>

    <div class="table-responsive">
        <table class="table admin-table" id="usersTable">
            <thead>
                <tr>
                    <th scope="col">User</th>
                    <th scope="col">Username</th>
                    <th scope="col">Role</th>
                    <th scope="col">PRC License</th>
                    <th scope="col">Last sign-in</th>
                    <th scope="col">Status</th>
                    <th scope="col" class="col-actions"><span class="visually-hidden">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($userList) > 0): ?>
                    <?php foreach ($userList as $user): ?>
                        <?php
                        $roleKey    = $slug($user['role'] ?? '', 'unknown');
                        $roleLabel  = $roleLabels[$roleKey] ?? ucfirst(str_replace('_', ' ', (string) ($user['role'] ?? 'Unknown')));
                        $statusKey  = $slug($user['status'] ?? 'active', 'active');
                        $statusText = ucfirst($statusKey);
                        $isSelf     = ((string) ($user['id'] ?? '')) === ((string) $currentUser);
                        ?>
                        <tr data-user-row data-search="<?= esc($roleKey . ' ' . str_replace('_', ' ', $roleKey) . ' ' . $statusKey, 'attr') ?>">
                            <?php
                            $fullName  = (string) ($user['full_name'] ?? '');
                            $avatarRel = ltrim(trim((string) ($user['avatar'] ?? '')), '/');
                            $hasPhoto  = $avatarRel !== '' && strpos($avatarRel, '..') === false && is_file(FCPATH . $avatarRel);
                            $lastSeen  = !empty($user['last_login_at']) ? strtotime($user['last_login_at']) : false;
                            ?>
                            <td data-label="User">
                                <div class="user-cell">
                                    <span class="user-avatar<?= $hasPhoto ? ' has-photo' : '' ?>" aria-hidden="true">
                                        <?php if ($hasPhoto): ?>
                                            <img src="<?= esc(base_url($avatarRel), 'attr') ?>"
                                                 alt=""
                                                 class="user-avatar-img"
                                                 loading="lazy"
                                                 decoding="async">
                                        <?php else: ?>
                                            <?= esc($initialsOf($fullName)) ?>
                                        <?php endif; ?>
                                    </span>
                                    <div class="user-body">
                                        <span class="user-name-line">
                                            <span class="user-name"><?= esc($fullName) ?></span>
                                            <?php if ($isSelf): ?>
                                                <span class="self-chip">You</span>
                                            <?php endif; ?>
                                        </span>
                                        <span class="user-email"><?= esc($user['email'] ?? '') ?></span>
                                    </div>
                                </div>
                            </td>
                            <td data-label="Username"><span class="cell-mono"><?= esc($user['username'] ?? '') ?></span></td>
                            <td data-label="Role">
                                <span class="role-label role-label--<?= esc($roleKey, 'attr') ?>"><?= esc($roleLabel) ?></span>
                            </td>
                            <td data-label="PRC License">
                                <?php if (!empty($user['prc_license'])): ?>
                                    <span class="cell-mono"><?= esc($user['prc_license']) ?></span>
                                <?php else: ?>
                                    <span class="cell-empty">&mdash;</span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Last sign-in" class="cell-date">
                                <?php if ($lastSeen): ?>
                                    <time datetime="<?= esc(date('c', $lastSeen), 'attr') ?>"
                                          title="<?= esc(date('M j, Y · g:i A', $lastSeen), 'attr') ?>"><?= esc($relativeTime($lastSeen)) ?></time>
                                <?php else: ?>
                                    <span class="cell-empty">Never</span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Status">
                                <span class="status-text status-text--<?= esc($statusKey, 'attr') ?>"><?= esc($statusText) ?></span>
                            </td>
                            <td data-label="Actions">
                                <div class="action-group">
                                    <button type="button" class="action-icon edit-user"
                                            title="Edit user"
                                            aria-label="Edit <?= esc($user['full_name'] ?? 'user', 'attr') ?>"
                                            data-id="<?= esc($user['id'] ?? '', 'attr') ?>"
                                            data-username="<?= esc($user['username'] ?? '', 'attr') ?>"
                                            data-fullname="<?= esc($user['full_name'] ?? '', 'attr') ?>"
                                            data-email="<?= esc($user['email'] ?? '', 'attr') ?>"
                                            data-role="<?= esc($user['role'] ?? '', 'attr') ?>"
                                            data-prc="<?= esc($user['prc_license'] ?? '', 'attr') ?>"
                                            data-status="<?= esc($statusKey, 'attr') ?>"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editUserModal">
                                        <i class="bi bi-pencil" aria-hidden="true"></i>
                                    </button>

                                    <?php if (! $isSelf): ?>
                                        <button type="button" class="action-icon toggle-status"
                                                title="<?= $statusKey === 'active' ? 'Deactivate user' : 'Activate user' ?>"
                                                aria-label="<?= $statusKey === 'active' ? 'Deactivate' : 'Activate' ?> <?= esc($user['full_name'] ?? 'user', 'attr') ?>"
                                                data-id="<?= esc($user['id'] ?? '', 'attr') ?>"
                                                data-name="<?= esc($user['full_name'] ?? '', 'attr') ?>"
                                                data-status="<?= esc($statusKey, 'attr') ?>">
                                            <i class="bi bi-<?= $statusKey === 'active' ? 'lock' : 'unlock' ?>" aria-hidden="true"></i>
                                        </button>
                                        <button type="button" class="action-icon delete-user"
                                                title="Delete user"
                                                aria-label="Delete <?= esc($user['full_name'] ?? 'user', 'attr') ?>"
                                                data-id="<?= esc($user['id'] ?? '', 'attr') ?>"
                                                data-name="<?= esc($user['full_name'] ?? '', 'attr') ?>"
                                                data-bs-toggle="modal"
                                                data-bs-target="#deleteUserModal">
                                            <i class="bi bi-trash3" aria-hidden="true"></i>
                                        </button>
                                    <?php else: ?>
                                        <span class="action-note">Current account</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr data-empty-row>
                        <td colspan="7">
                            <div class="empty-state">
                                <i class="bi bi-people" aria-hidden="true"></i>
                                <p class="empty-title">No users found</p>
                                <p class="empty-hint">Add your first system user to get started.</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>

                <!-- Shown only while a search filters every row out -->
                <tr data-noresults-row hidden>
                    <td colspan="7">
                        <div class="empty-state">
                            <i class="bi bi-search" aria-hidden="true"></i>
                            <p class="empty-title">No matching users</p>
                            <p class="empty-hint">Try a different name, username, email or role.</p>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="table-footer">
        <span>Showing <?= count($userList) ?> of <?= esc($totalUsers) ?> users</span>
        <span id="filterCount" class="filter-count" hidden></span>
    </div>
</div>

<!-- ===== ADD USER MODAL (REDESIGNED) ===== -->
<div class="modal fade admin-modal add-user-modal" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <div class="modal-content">

            <div class="modal-header add-user-header">
                <div class="add-user-heading">
                    <span class="add-user-heading-icon" aria-hidden="true">
                        <i class="bi bi-person-plus-fill"></i>
                    </span>
                    <div class="add-user-heading-text">
                        <h5 class="modal-title" id="addUserModalLabel">Add new user</h5>
                        <p class="add-user-sub">Create an account for a new member of the staff.</p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="<?= base_url('admin/users/create') ?>" method="POST" data-guard-submit>
                <?= csrf_field() ?>

                <div class="modal-body add-user-body">

                    <!-- ================= ACCOUNT ================= -->
                    <section class="add-user-section">
                        <h6 class="add-user-section-title">Account</h6>

                        <div class="add-user-grid">
                            <div class="add-user-field">
                                <label class="form-label" for="add_full_name">
                                    Full name <span class="req" aria-hidden="true">*</span>
                                </label>
                                <input type="text"
                                       class="form-control"
                                       id="add_full_name"
                                       name="full_name"
                                       autocomplete="name"
                                       placeholder="e.g. Juan Dela Cruz"
                                       required>
                            </div>

                            <div class="add-user-field">
                                <label class="form-label" for="add_username">
                                    Username <span class="req" aria-hidden="true">*</span>
                                </label>
                                <input type="text"
                                       class="form-control"
                                       id="add_username"
                                       name="username"
                                       autocomplete="off"
                                       spellcheck="false"
                                       placeholder="e.g. jdelacruz"
                                       required>
                                <small class="field-hint">Cannot be changed after creation.</small>
                            </div>

                            <div class="add-user-field">
                                <label class="form-label" for="add_email">
                                    Email <span class="req" aria-hidden="true">*</span>
                                </label>
                                <input type="email"
                                       class="form-control"
                                       id="add_email"
                                       name="email"
                                       autocomplete="email"
                                       placeholder="user@polymedic.example"
                                       required>
                            </div>

                            <div class="add-user-field">
                                <label class="form-label" for="add_password">
                                    Password <span class="req" aria-hidden="true">*</span>
                                </label>
                                <div class="password-field">
                                    <input type="password"
                                           class="form-control"
                                           id="add_password" minlength="8"
                                           name="password"
                                           autocomplete="new-password"
                                           placeholder="Minimum 8 characters"
                                           required>
                                    <button type="button"
                                            class="password-toggle"
                                            data-toggle-password="add_password"
                                            aria-label="Show password"
                                            title="Show password">
                                        <i class="bi bi-eye" aria-hidden="true"></i>
                                    </button>
                                </div>
                                <small class="field-hint">Stored as a bcrypt hash. Share it with the user securely.</small>
                            </div>
                        </div>
                    </section>

                    <!-- ================= ACCESS ================= -->
                    <section class="add-user-section">
                        <h6 class="add-user-section-title">Access</h6>

                        <div class="add-user-grid">
                            <div class="add-user-field">
                                <label class="form-label" for="add_role">
                                    Role <span class="req" aria-hidden="true">*</span>
                                </label>
                                <select class="form-select" id="add_role" name="role" required>
                                    <option value="">Select a role</option>
                                    <option value="admin">Administrator</option>
                                    <option value="receptionist">Receptionist</option>
                                    <option value="med_tech">Medical Technologist</option>
                                    <option value="radiologist">Radiologist</option>
                                </select>
                                <small class="field-hint">Determines what the user can see and do.</small>
                            </div>

                            <div class="add-user-field">
                                <label class="form-label" for="add_status">Status</label>
                                <select class="form-select" id="add_status" name="status">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                                <small class="field-hint">Inactive accounts cannot sign in.</small>
                            </div>

                            <div class="add-user-field add-user-field--full" id="add_prc_wrap" hidden>
                                <label class="form-label" for="add_prc_license">
                                    PRC License No. <span class="req" aria-hidden="true">*</span>
                                </label>
                                <input type="text"
                                       class="form-control"
                                       id="add_prc_license"
                                       name="prc_license"
                                       autocomplete="off"
                                       maxlength="30"
                                       placeholder="e.g. 0123456">
                                <small class="field-hint">Required for Medical Technologist and Radiologist accounts.</small>
                            </div>
                        </div>
                    </section>

                </div>

                <div class="modal-footer add-user-footer">
                    <span class="add-user-footer-note">
                        <i class="bi bi-shield-lock" aria-hidden="true"></i>
                        The user can change their password after signing in.
                    </span>
                    <div class="add-user-footer-actions">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check2" aria-hidden="true"></i>
                            Create user
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ===== EDIT USER MODAL (REDESIGNED) ===== -->
<div class="modal fade admin-modal edit-user-modal" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <div class="modal-content">

            <div class="modal-header edit-user-header">
                <div class="edit-user-heading">
                    <span class="edit-user-heading-icon" aria-hidden="true">
                        <i class="bi bi-pencil-square"></i>
                    </span>
                    <div class="edit-user-heading-text">
                        <h5 class="modal-title" id="editUserModalLabel">Edit user</h5>
                        <p class="edit-user-sub">Update account details, role, and access status.</p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form id="editUserForm" action="" method="POST" data-guard-submit>
                <?= csrf_field() ?>
                <input type="hidden" name="user_id" id="edit_user_id">

                <div class="modal-body edit-user-body">

                    <!-- ================= IDENTITY ================= -->
                    <section class="edit-user-section">
                        <h6 class="edit-user-section-title">Identity</h6>

                        <div class="edit-user-grid">
                            <div class="edit-user-field">
                                <label class="form-label" for="edit_username_display">Username</label>
                                <input type="text"
                                       class="form-control"
                                       id="edit_username_display"
                                       disabled
                                       autocomplete="off"
                                       spellcheck="false">
                                <small class="field-hint">Usernames cannot be changed after creation.</small>
                            </div>

                            <div class="edit-user-field">
                                <label class="form-label" for="edit_full_name">
                                    Full name <span class="req" aria-hidden="true">*</span>
                                </label>
                                <input type="text"
                                       class="form-control"
                                       name="full_name"
                                       id="edit_full_name"
                                       autocomplete="name"
                                       placeholder="e.g. Juan Dela Cruz"
                                       required>
                            </div>

                            <div class="edit-user-field edit-user-field--full">
                                <label class="form-label" for="edit_email">
                                    Email <span class="req" aria-hidden="true">*</span>
                                </label>
                                <input type="email"
                                       class="form-control"
                                       name="email"
                                       id="edit_email"
                                       autocomplete="email"
                                       placeholder="user@polymedic.example"
                                       required>
                            </div>
                        </div>
                    </section>

                    <!-- ================= SECURITY ================= -->
                    <section class="edit-user-section">
                        <h6 class="edit-user-section-title">Security</h6>

                        <div class="edit-user-grid">
                            <div class="edit-user-field edit-user-field--full">
                                <label class="form-label" for="edit_password">New password</label>
                                <div class="password-field">
                                    <input type="password"
                                           class="form-control"
                                           name="password"
                                           id="edit_password" minlength="8"
                                           autocomplete="new-password"
                                           placeholder="Leave blank to keep current password">
                                    <button type="button"
                                            class="password-toggle"
                                            data-toggle-password="edit_password"
                                            aria-label="Show password"
                                            title="Show password">
                                        <i class="bi bi-eye" aria-hidden="true"></i>
                                    </button>
                                </div>
                                <small class="field-hint">Fill this only if you want to change the user's password.</small>
                            </div>
                        </div>
                    </section>

                    <!-- ================= ACCESS ================= -->
                    <section class="edit-user-section">
                        <h6 class="edit-user-section-title">Access</h6>

                        <div class="edit-user-grid">
                            <div class="edit-user-field">
                                <label class="form-label" for="edit_role">
                                    Role <span class="req" aria-hidden="true">*</span>
                                </label>
                                <select class="form-select" name="role" id="edit_role" required>
                                    <option value="admin">Administrator</option>
                                    <option value="receptionist">Receptionist</option>
                                    <option value="med_tech">Medical Technologist</option>
                                    <option value="radiologist">Radiologist</option>
                                </select>
                                <small class="field-hint">Determines what the user can see and do.</small>
                            </div>

                            <div class="edit-user-field">
                                <label class="form-label" for="edit_status">Status</label>
                                <select class="form-select" name="status" id="edit_status">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                                <small class="field-hint">Inactive accounts cannot sign in.</small>
                            </div>

                            <div class="edit-user-field edit-user-field--full" id="edit_prc_wrap" hidden>
                                <label class="form-label" for="edit_prc_license">
                                    PRC License No. <span class="req" aria-hidden="true">*</span>
                                </label>
                                <input type="text"
                                       class="form-control"
                                       name="prc_license"
                                       id="edit_prc_license"
                                       autocomplete="off"
                                       maxlength="30"
                                       placeholder="e.g. 0123456">
                                <small class="field-hint">Required for Medical Technologist and Radiologist accounts.</small>
                            </div>
                        </div>
                    </section>

                </div>

                <div class="modal-footer edit-user-footer">
                    <span class="edit-user-footer-note">
                        <i class="bi bi-info-circle" aria-hidden="true"></i>
                        Changes take effect immediately.
                    </span>
                    <div class="edit-user-footer-actions">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check2" aria-hidden="true"></i>
                            Update user
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ===== TOGGLE STATUS MODAL ===== -->
<div class="modal fade admin-modal" id="toggleStatusModal" tabindex="-1" aria-labelledby="toggleStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="toggleStatusModalLabel"><i class="bi bi-shield-lock me-2" aria-hidden="true"></i>Change Account Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="modal-lead">Are you sure you want to <strong id="toggle_user_action">deactivate</strong> <strong id="toggle_user_name"></strong>?</p>
                <p class="modal-note" id="toggle_user_note">A deactivated user will no longer be able to sign in.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="#" data-post id="toggle_user_link" class="btn btn-primary" role="button">Confirm</a>
            </div>
        </div>
    </div>
</div>

<!-- ===== DELETE USER MODAL ===== -->
<div class="modal fade admin-modal" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger" id="deleteUserModalLabel"><i class="bi bi-exclamation-triangle me-2" aria-hidden="true"></i>Delete User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="modal-lead">Are you sure you want to delete <strong id="delete_user_name"></strong>?</p>
                <p class="modal-note danger">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="#" data-post id="delete_user_link" class="btn btn-danger" role="button">Delete User</a>
            </div>
        </div>
    </div>
</div>

<style>
/* ============================================
   USER MANAGEMENT
   ============================================ */

.table-card,
.admin-modal {
    --um-accent:        var(--pm-accent, #0B67B2);
    --um-accent-soft:   var(--pm-accent-soft, #E8F1FA);
    --um-ink:           var(--pm-text, #10202E);
    --um-text:          var(--pm-text-soft, #3B4C5E);
    --um-muted:         var(--pm-text-muted, #5D6E80);
    --um-faint:         #7A8B9D;
    --um-line:          var(--pm-line, #DDE4EC);
    --um-line-soft:     #EBF0F5;
    --um-surface:       #FFFFFF;
    --um-canvas:        #F8FAFC;
    --um-row-hover:     #F5F8FB;
    --um-avatar:        #E3EAF2;
    --um-danger:        #B42318;
    --um-radius:        8px;
    --um-radius-sm:     8px;
    --um-radius-xs:     6px;
}

/* ===== PAGE HEADER ===== */
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    gap: 1rem;
    flex-wrap: wrap;
    margin-bottom: 1.25rem;
}

.page-header-text { min-width: 0; }

.page-title {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
    letter-spacing: -0.015em;
    color: var(--um-ink);
}

.page-subtitle {
    margin: 0.2rem 0 0;
    font-size: 0.8125rem;
    color: var(--um-muted);
}

.btn-add-user {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    height: 36px;
    padding: 0 0.9rem;
    font-size: 0.8125rem;
    font-weight: 600;
    border-radius: var(--um-radius-xs);
    white-space: nowrap;
}

/* ===== FRAME ===== */
/* The card is only a scope now; search, table and footer form one panel. */
.table-card { padding: 0; background: transparent; border: 0; box-shadow: none; }

.table-card :focus-visible {
    outline: 2px solid var(--um-accent);
    outline-offset: 2px;
    border-radius: var(--um-radius-xs);
}

/* ===== ALERTS ===== */
.admin-alert {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    margin-bottom: 1rem;
    padding: 0.75rem 2.5rem 0.75rem 0.9rem;
    font-size: 0.8125rem;
    border-width: 1px;
    border-radius: var(--um-radius);
}

.admin-alert i { font-size: 1rem; line-height: 1; flex-shrink: 0; }
.admin-alert span { flex: 1; min-width: 0; }

/* ===== TOOLBAR ===== */
.table-toolbar {
    margin: 0;
    padding: 0.75rem 1rem;
    background: var(--um-surface);
    border: 1px solid var(--um-line);
    border-bottom: 0;
    border-radius: var(--um-radius) var(--um-radius) 0 0;
}

.search-wrapper {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    max-width: 320px;
    height: 34px;
    padding: 0 0.7rem;
    background: var(--um-surface);
    border: 1px solid var(--um-line);
    border-radius: var(--um-radius-xs);
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

.search-wrapper:focus-within {
    border-color: var(--um-accent);
    box-shadow: 0 0 0 3px rgba(11, 103, 178, 0.15);
}

.search-wrapper i { color: var(--um-faint); font-size: 0.8rem; }

.search-wrapper .form-control {
    height: 100%;
    padding: 0;
    font-size: 0.8125rem;
    color: var(--um-ink);
    background: transparent;
    border: 0;
}

.search-wrapper .form-control:focus { box-shadow: none; border: 0; }
.search-wrapper .form-control::-webkit-search-cancel-button { cursor: pointer; }

/* ===== TABLE ===== */
.table-card .table-responsive {
    background: var(--um-surface);
    border-left: 1px solid var(--um-line);
    border-right: 1px solid var(--um-line);
}

.admin-table {
    margin: 0;
    border-collapse: collapse;
}

.admin-table thead th {
    padding: 0.6rem 1rem;
    font-size: 0.7rem;
    font-weight: 600;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    white-space: nowrap;
    color: var(--um-muted);
    background: var(--um-canvas);
    border-top: 1px solid var(--um-line);
    border-bottom: 1px solid var(--um-line);
}

.admin-table tbody td {
    padding: 0.7rem 1rem;
    vertical-align: middle;
    font-size: 0.8125rem;
    color: var(--um-text);
    background: transparent;
    border-bottom: 1px solid var(--um-line-soft);
}

.admin-table tbody tr:last-child td { border-bottom: 0; }

.admin-table tbody tr[data-user-row]:hover td { background: var(--um-row-hover); }

.col-actions { text-align: right; }

/* ===== USER CELL ===== */
.user-cell {
    display: flex;
    align-items: center;
    gap: 0.7rem;
    min-width: 0;
}

/* Uploaded photo when there is one; otherwise initials, never a stock silhouette */
.user-avatar {
    display: grid;
    place-items: center;
    flex-shrink: 0;
    width: 36px;
    height: 36px;
    overflow: hidden;
    font-size: 0.72rem;
    font-weight: 600;
    letter-spacing: 0.02em;
    color: var(--um-ink);
    background: var(--um-avatar);
    border-radius: 50%;
    box-shadow: inset 0 0 0 1px rgba(16, 32, 46, 0.06);
}

.user-avatar.has-photo {
    background: var(--um-line-soft);
    box-shadow: 0 0 0 1px var(--um-line);
}

.user-avatar-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.user-body {
    display: flex;
    flex-direction: column;
    min-width: 0;
    line-height: 1.35;
}

.user-name-line {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    min-width: 0;
}

.user-name {
    min-width: 0;
    font-weight: 600;
    color: var(--um-ink);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.self-chip {
    flex-shrink: 0;
    padding: 0 0.3rem;
    font-size: 0.64rem;
    font-weight: 600;
    line-height: 1.6;
    color: var(--um-accent);
    background: var(--um-accent-soft);
    border-radius: 3px;
}

.user-email {
    max-width: 260px;
    font-size: 0.75rem;
    color: var(--um-muted);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.cell-mono {
    font-family: var(--pm-mono, ui-monospace, monospace);
    font-size: 0.78rem;
    color: var(--um-text);
}

.cell-empty { color: var(--um-faint); }

.cell-date {
    white-space: nowrap;
    font-variant-numeric: tabular-nums;
}

.cell-date time { cursor: help; }

/* ===== ROLE + STATUS: dot labels, not tinted badges ===== */
.role-label,
.status-text {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    font-size: 0.8rem;
    font-weight: 500;
    color: var(--um-text);
    white-space: nowrap;
}

.role-label::before {
    content: "";
    width: 7px;
    height: 7px;
    border-radius: 2px;
    background: var(--role, var(--um-faint));
    flex-shrink: 0;
}

/* Clinical roles use their department colour; desk roles stay neutral */
.role-label--admin        { --role: var(--pm-ink, #0C2D48); }
.role-label--receptionist { --role: #7C8DA0; }
.role-label--med_tech     { --role: #0B67B2; }
.role-label--radiologist  { --role: #0E8A7E; }

.status-text::before {
    content: "";
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: var(--dot, #98A2B3);
    flex-shrink: 0;
}

.status-text--active   { --dot: #17B26A; }
.status-text--inactive { --dot: #98A2B3; color: var(--um-muted); }

/* ===== ACTIONS ===== */
.action-group {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 0.15rem;
}

.action-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 30px;
    height: 30px;
    font-size: 0.9rem;
    line-height: 1;
    color: var(--um-muted);
    background: transparent;
    border: 1px solid transparent;
    border-radius: var(--um-radius-xs);
    cursor: pointer;
    transition: color 0.15s ease, background-color 0.15s ease;
}

.action-icon:hover { color: var(--um-ink); background: var(--um-line-soft); }
.action-icon.delete-user:hover { color: var(--um-danger); background: #FEF3F2; }

.action-note {
    font-size: 0.74rem;
    color: var(--um-faint);
}

/* ===== EMPTY STATES ===== */
.empty-state {
    padding: 2.5rem 1rem;
    text-align: center;
    color: var(--um-muted);
}

.empty-state i {
    display: block;
    margin-bottom: 0.5rem;
    font-size: 1.5rem;
    color: var(--um-faint);
}

.empty-title {
    margin: 0 0 0.15rem;
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--um-ink);
}

.empty-hint {
    margin: 0;
    font-size: 0.8rem;
    color: var(--um-muted);
}

/* ===== FOOTER ===== */
.table-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 0.75rem;
    flex-wrap: wrap;
    margin: 0;
    padding: 0.7rem 1rem;
    background: var(--um-surface);
    border: 1px solid var(--um-line);
    border-radius: 0 0 var(--um-radius) var(--um-radius);
}

.table-footer span {
    font-size: 0.78rem;
    color: var(--um-muted);
}

.filter-count { color: var(--um-accent); font-weight: 600; }

/* Modal heading icons were decoration only */
.add-user-heading-icon,
.edit-user-heading-icon { display: none !important; }

/* ============================================
   MODALS (shared)
   ============================================ */

.admin-modal .modal-content {
    border: 1px solid var(--um-line);
    border-radius: var(--um-radius);
    box-shadow: 0 16px 40px rgba(10, 43, 78, 0.16);
}

.admin-modal .modal-header {
    border-bottom: 1px solid var(--um-line-soft);
    padding: 1rem 1.25rem;
}

.admin-modal .modal-title {
    font-size: 1rem;
    font-weight: 600;
    color: var(--um-ink);
    display: flex;
    align-items: center;
}

.admin-modal .modal-body { padding: 1.25rem; }

.admin-modal .modal-footer {
    border-top: 1px solid var(--um-line-soft);
    padding: 0.85rem 1.25rem;
    gap: 0.5rem;
}

.admin-modal .modal-footer .btn {
    border-radius: var(--um-radius-xs);
    font-size: 0.875rem;
    font-weight: 600;
    padding: 0.5rem 1rem;
}

.admin-modal .form-label {
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--um-text);
    margin-bottom: 0.35rem;
}

.admin-modal .req { color: #b91c1c; font-weight: 700; }

.admin-modal .form-control,
.admin-modal .form-select {
    border: 1px solid var(--um-line);
    border-radius: var(--um-radius-xs);
    font-size: 0.875rem;
    padding: 0.55rem 0.75rem;
    color: var(--um-ink);
}

.admin-modal .form-control:focus,
.admin-modal .form-select:focus {
    border-color: var(--um-accent);
    box-shadow: 0 0 0 3px rgba(1, 72, 202, 0.10);
}

.admin-modal .form-control:disabled {
    background: var(--um-canvas);
    color: var(--um-muted);
}

.field-hint {
    display: block;
    margin-top: 0.3rem;
    font-size: 0.74rem;
    color: var(--um-faint);
}

.password-field { position: relative; }

.password-field .form-control { padding-right: 2.5rem; }

.password-toggle {
    position: absolute;
    top: 50%;
    right: 0.35rem;
    transform: translateY(-50%);
    background: transparent;
    border: none;
    color: var(--um-faint);
    width: 30px;
    height: 30px;
    border-radius: var(--um-radius-xs);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 0.85rem;
}

.password-toggle:hover { color: var(--um-accent); }

.modal-lead {
    font-size: 0.9rem;
    color: var(--um-text);
    margin: 0 0 0.4rem;
    line-height: 1.55;
}

.modal-note {
    font-size: 0.8rem;
    color: var(--um-muted);
    margin: 0;
}

.modal-note.danger { color: var(--um-danger); }

/* ============================================
   ADD USER MODAL
   ============================================ */

/* ---- header ---- */

.add-user-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    padding: 1.15rem 1.35rem;
}

.add-user-heading {
    display: flex;
    align-items: center;
    gap: 0.85rem;
    min-width: 0;
}

.add-user-heading-icon {
    display: grid;
    place-items: center;
    flex-shrink: 0;
    width: 40px;
    height: 40px;
    font-size: 1rem;
    color: var(--um-accent);
    background: #eef4fd;
    border: 1px solid #d6e4fa;
    border-radius: 10px;
}

.add-user-heading-text { min-width: 0; }

.add-user-heading-text .modal-title {
    font-size: 1.02rem;
    font-weight: 650;
    line-height: 1.25;
    letter-spacing: -0.01em;
    display: block;
}

.add-user-sub {
    margin: 0.15rem 0 0;
    font-size: 0.8rem;
    color: var(--um-muted);
    line-height: 1.4;
}

/* ---- body ---- */

.add-user-body {
    padding: 0.5rem 1.35rem 1rem;
}

.add-user-section {
    padding: 1rem 0;
    border-top: 1px solid var(--um-line-soft);
}

.add-user-section:first-child {
    border-top: 0;
    padding-top: 0.6rem;
}

.add-user-section-title {
    margin: 0 0 0.75rem;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: var(--um-faint);
}

.add-user-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1rem 1.25rem;
}

.add-user-field {
    min-width: 0;
}

.add-user-field--full {
    grid-column: 1 / -1;
}

.add-user-field--full[hidden] { display: none !important; }

.add-user-field--full:not([hidden]) {
    animation: addUserFieldIn 0.22s ease;
}

@keyframes addUserFieldIn {
    from { opacity: 0; transform: translateY(-4px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* ---- footer ---- */

.add-user-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.85rem;
    flex-wrap: wrap;
    padding: 0.85rem 1.35rem;
    background: var(--um-canvas);
}

.add-user-footer-note {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.74rem;
    color: var(--um-muted);
}

.add-user-footer-note i { font-size: 0.82rem; color: var(--um-faint); }

.add-user-footer-actions {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    margin-left: auto;
}

.add-user-footer-actions .btn { margin: 0; }

/* ============================================
   EDIT USER MODAL (mirrors Add User)
   ============================================ */

.edit-user-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    padding: 1.15rem 1.35rem;
}

.edit-user-heading {
    display: flex;
    align-items: center;
    gap: 0.85rem;
    min-width: 0;
}

.edit-user-heading-icon {
    display: grid;
    place-items: center;
    flex-shrink: 0;
    width: 40px;
    height: 40px;
    font-size: 1rem;
    color: var(--um-accent);
    background: #eef4fd;
    border: 1px solid #d6e4fa;
    border-radius: 10px;
}

.edit-user-heading-text { min-width: 0; }

.edit-user-heading-text .modal-title {
    font-size: 1.02rem;
    font-weight: 650;
    line-height: 1.25;
    letter-spacing: -0.01em;
    display: block;
}

.edit-user-sub {
    margin: 0.15rem 0 0;
    font-size: 0.8rem;
    color: var(--um-muted);
    line-height: 1.4;
}

.edit-user-body {
    padding: 0.5rem 1.35rem 1rem;
}

.edit-user-section {
    padding: 1rem 0;
    border-top: 1px solid var(--um-line-soft);
}

.edit-user-section:first-child {
    border-top: 0;
    padding-top: 0.6rem;
}

.edit-user-section-title {
    margin: 0 0 0.75rem;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: var(--um-faint);
}

.edit-user-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1rem 1.25rem;
}

.edit-user-field {
    min-width: 0;
}

.edit-user-field--full {
    grid-column: 1 / -1;
}

.edit-user-field--full[hidden] { display: none !important; }

.edit-user-field--full:not([hidden]) {
    animation: editUserFieldIn 0.22s ease;
}

@keyframes editUserFieldIn {
    from { opacity: 0; transform: translateY(-4px); }
    to   { opacity: 1; transform: translateY(0); }
}

.edit-user-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.85rem;
    flex-wrap: wrap;
    padding: 0.85rem 1.35rem;
    background: var(--um-canvas);
}

.edit-user-footer-note {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.74rem;
    color: var(--um-muted);
}

.edit-user-footer-note i { font-size: 0.82rem; color: var(--um-faint); }

.edit-user-footer-actions {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    margin-left: auto;
}

.edit-user-footer-actions .btn { margin: 0; }

/* ===== HIDDEN HELPER ===== */
[hidden] { display: none !important; }

/* ============================================
   RESPONSIVE
   ============================================ */

@media (max-width: 576px) {
    .add-user-grid,
    .edit-user-grid { grid-template-columns: minmax(0, 1fr); }

    .add-user-header,
    .edit-user-header { padding: 1rem; }
    .add-user-body,
    .edit-user-body { padding: 0.35rem 1rem 0.85rem; }
    .add-user-footer,
    .edit-user-footer { padding: 0.75rem 1rem; }

    .add-user-footer-note,
    .edit-user-footer-note { width: 100%; }
    .add-user-footer-actions,
    .edit-user-footer-actions { width: 100%; }
    .add-user-footer-actions .btn,
    .edit-user-footer-actions .btn { flex: 1; }
}

@media (max-width: 991px) {
    .search-wrapper { max-width: 100%; }
}

@media (max-width: 768px) {

    .page-header {
        flex-direction: column;
        gap: 0.75rem;
        align-items: stretch;
    }

    .btn-add-user { justify-content: center; width: 100%; }

    .table-responsive { overflow: visible; }

    .admin-table thead { display: none; }

    .admin-table,
    .admin-table tbody,
    .admin-table tr,
    .admin-table td {
        display: block;
        width: 100%;
    }

    .admin-table tbody tr[data-user-row] {
        padding: 0.35rem 1rem;
        border-bottom: 1px solid var(--um-line);
        background: var(--um-surface);
    }

    .admin-table tbody tr[data-user-row]:hover td { background: transparent; }

    .admin-table tbody td {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 0.55rem 0;
        border-bottom: 1px solid var(--um-line-soft);
        text-align: right;
    }

    .admin-table tbody tr[data-user-row] td:last-child { border-bottom: none; }

    .admin-table tbody td::before {
        content: attr(data-label);
        font-size: 0.68rem;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        font-weight: 600;
        color: var(--um-muted);
        flex-shrink: 0;
        text-align: left;
    }

    .admin-table tbody td[data-label="User"] {
        justify-content: flex-start;
        text-align: left;
    }

    .admin-table tbody td[data-label="User"]::before { display: none; }

    .admin-table tbody tr[data-empty-row] td,
    .admin-table tbody tr[data-noresults-row] td {
        display: block;
        text-align: center;
    }

    .admin-table tbody tr[data-empty-row] td::before,
    .admin-table tbody tr[data-noresults-row] td::before { display: none; }

    .table-footer {
        flex-direction: column;
        gap: 0.5rem;
        align-items: center;
    }
}

@media (max-width: 480px) {
}

@media (prefers-reduced-motion: reduce) {
    .table-card *,
    .admin-modal * {
        transition-duration: 0.01ms !important;
        animation-duration: 0.01ms !important;
    }
}

@media print {
    .table-card { box-shadow: none; border: none; padding: 0; }
    .table-toolbar,
    .btn-add-user,
    .col-actions,
    .admin-table td[data-label="Actions"] { display: none !important; }
}
</style>

<script>
(function () {
    'use strict';

    var BASE_UPDATE = '<?= base_url('admin/users/update') ?>';
    var BASE_DELETE = '<?= base_url('admin/users/delete') ?>';
    var BASE_TOGGLE = '<?= base_url('admin/users/toggle') ?>';

    /* Roles that must carry a PRC license number. */
    var PRC_ROLES = ['med_tech', 'radiologist'];

    function setValue(id, value) {
        var el = document.getElementById(id);
        if (el) { el.value = value == null ? '' : value; }
    }

    /* Show or hide the PRC license field based on the selected role. */
    function togglePrcVisibility(selectEl, wrapEl) {
        if (!selectEl || !wrapEl) { return; }
        var show = PRC_ROLES.indexOf(selectEl.value) !== -1;
        wrapEl.hidden = !show;
    }

    var addRoleSelect  = document.getElementById('add_role');
    var addPrcWrap     = document.getElementById('add_prc_wrap');
    var editRoleSelect = document.getElementById('edit_role');
    var editPrcWrap    = document.getElementById('edit_prc_wrap');

    if (addRoleSelect && addPrcWrap) {
        addRoleSelect.addEventListener('change', function () {
            togglePrcVisibility(addRoleSelect, addPrcWrap);
        });
        togglePrcVisibility(addRoleSelect, addPrcWrap);
    }

    if (editRoleSelect && editPrcWrap) {
        editRoleSelect.addEventListener('change', function () {
            togglePrcVisibility(editRoleSelect, editPrcWrap);
        });
    }

    // ============================================
    // Edit User
    // ============================================
    document.querySelectorAll('.edit-user').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id = this.dataset.id;

            setValue('edit_user_id', id);
            setValue('edit_username_display', this.dataset.username);
            setValue('edit_full_name', this.dataset.fullname);
            setValue('edit_email', this.dataset.email);
            setValue('edit_role', this.dataset.role);
            setValue('edit_prc_license', this.dataset.prc);
            setValue('edit_status', this.dataset.status);
            setValue('edit_password', '');

            togglePrcVisibility(editRoleSelect, editPrcWrap);

            var form = document.getElementById('editUserForm');
            if (form) { form.action = BASE_UPDATE + '/' + encodeURIComponent(id); }
        });
    });

    // ============================================
    // Delete User
    // ============================================
    document.querySelectorAll('.delete-user').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id = this.dataset.id;
            var name = this.dataset.name || 'this user';

            var nameEl = document.getElementById('delete_user_name');
            if (nameEl) { nameEl.textContent = name; }

            var link = document.getElementById('delete_user_link');
            if (link) { link.href = BASE_DELETE + '/' + encodeURIComponent(id); }
        });
    });

    // ============================================
    // Toggle Status
    // ============================================
    document.querySelectorAll('.toggle-status').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id = this.dataset.id;
            var name = this.dataset.name || 'this user';
            var isActive = this.dataset.status === 'active';
            var verb = isActive ? 'deactivate' : 'activate';
            var target = BASE_TOGGLE + '/' + encodeURIComponent(id);

            var modalEl = document.getElementById('toggleStatusModal');
            var hasBootstrap = typeof window.bootstrap !== 'undefined' && window.bootstrap.Modal;

            if (!modalEl || !hasBootstrap) {
                if (window.confirm('Are you sure you want to ' + verb + ' this user?')) {
                    window.pmPost(target);
                }
                return;
            }

            var actionEl = document.getElementById('toggle_user_action');
            var nameEl = document.getElementById('toggle_user_name');
            var noteEl = document.getElementById('toggle_user_note');
            var linkEl = document.getElementById('toggle_user_link');

            if (actionEl) { actionEl.textContent = verb; }
            if (nameEl) { nameEl.textContent = name; }
            if (noteEl) {
                noteEl.textContent = isActive
                    ? 'A deactivated user will no longer be able to sign in.'
                    : 'This user will regain access to the system.';
            }
            if (linkEl) {
                linkEl.href = target;
                linkEl.textContent = isActive ? 'Deactivate' : 'Activate';
                linkEl.className = isActive ? 'btn btn-warning' : 'btn btn-primary';
            }

            window.bootstrap.Modal.getOrCreateInstance(modalEl).show();
        });
    });

    // ============================================
    // Password visibility toggles
    // ============================================
    document.querySelectorAll('[data-toggle-password]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var input = document.getElementById(this.dataset.togglePassword);
            if (!input) { return; }

            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            this.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            this.setAttribute('title', show ? 'Hide password' : 'Show password');

            var icon = this.querySelector('i');
            if (icon) { icon.className = show ? 'bi bi-eye-slash' : 'bi bi-eye'; }
        });
    });

    // ============================================
    // Search
    // ============================================
    var searchInput = document.getElementById('searchUsers');
    var table = document.getElementById('usersTable');

    if (searchInput && table) {
        var userRows = table.querySelectorAll('tbody tr[data-user-row]');
        var noResultsRow = table.querySelector('tbody tr[data-noresults-row]');
        var emptyRow = table.querySelector('tbody tr[data-empty-row]');
        var filterCount = document.getElementById('filterCount');

        var runFilter = function () {
            var term = searchInput.value.toLowerCase().trim();
            var visible = 0;

            userRows.forEach(function (row) {
                var haystack = (row.textContent + ' ' + (row.dataset.search || '')).toLowerCase();
                var match = term === '' || haystack.indexOf(term) !== -1;
                row.style.display = match ? '' : 'none';
                if (match) { visible++; }
            });

            if (noResultsRow) {
                noResultsRow.hidden = !(term !== '' && visible === 0 && userRows.length > 0);
            }
            if (emptyRow) {
                emptyRow.style.display = term !== '' ? 'none' : '';
            }
            if (filterCount) {
                if (term !== '' && userRows.length > 0) {
                    filterCount.hidden = false;
                    filterCount.textContent = visible + ' match' + (visible === 1 ? '' : 'es');
                } else {
                    filterCount.hidden = true;
                    filterCount.textContent = '';
                }
            }
        };

        searchInput.addEventListener('input', runFilter);
        searchInput.addEventListener('search', runFilter);
    }

    // ============================================
    // Prevent duplicate submissions
    // ============================================
    document.querySelectorAll('form[data-guard-submit]').forEach(function (form) {
        form.addEventListener('submit', function () {
            if (typeof form.checkValidity === 'function' && !form.checkValidity()) {
                return;
            }
            var submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                var original = submitBtn.innerHTML;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>Saving…';

                setTimeout(function () {
                    submitBtn.innerHTML = original;
                    submitBtn.disabled = false;
                }, 8000);
            }
        });
    });

    document.querySelectorAll('.admin-modal').forEach(function (modalEl) {
        modalEl.addEventListener('show.bs.modal', function () {
            var submitBtn = modalEl.querySelector('button[type="submit"]');
            if (submitBtn && submitBtn.disabled) {
                submitBtn.disabled = false;
                submitBtn.textContent = modalEl.id === 'addUserModal'
                    ? 'Create user'
                    : 'Update user';
            }
        });
    });

    var addModal = document.getElementById('addUserModal');
    if (addModal) {
        addModal.addEventListener('shown.bs.modal', function () {
            var first = document.getElementById('add_full_name');
            if (first) { first.focus(); }
        });
    }

    var editModal = document.getElementById('editUserModal');
    if (editModal) {
        editModal.addEventListener('shown.bs.modal', function () {
            var first = document.getElementById('edit_full_name');
            if (first) { first.focus(); }
        });
    }
})();
</script>

<?= $this->endSection() ?>