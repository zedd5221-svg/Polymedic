<?= $this->extend('layouts/AdminLayout') ?>

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

/* Initials for the avatar, taken from the real stored name. */
$initials = static function ($name) {
    $name = trim((string) $name);
    if ($name === '') {
        return '?';
    }
    $parts = preg_split('/\s+/', $name);
    $first = function_exists('mb_substr') ? mb_substr($parts[0], 0, 1) : substr($parts[0], 0, 1);
    $last  = '';
    if (count($parts) > 1) {
        $lastPart = end($parts);
        $last = function_exists('mb_substr') ? mb_substr($lastPart, 0, 1) : substr($lastPart, 0, 1);
    }
    $out = $first . $last;
    return function_exists('mb_strtoupper') ? mb_strtoupper($out) : strtoupper($out);
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
                    <th scope="col">Email</th>
                    <th scope="col">Role</th>
                    <th scope="col">Status</th>
                    <th scope="col" class="col-actions">Actions</th>
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
                            <td data-label="User">
                                <div class="user-cell">
                                    <span class="user-avatar avatar-<?= esc($roleKey, 'attr') ?>" aria-hidden="true"><?= esc($initials($user['full_name'] ?? '')) ?></span>
                                    <span class="user-name"><?= esc($user['full_name'] ?? '') ?></span>
                                    <?php if ($isSelf): ?>
                                        <span class="self-chip">You</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td data-label="Username"><span class="cell-mono"><?= esc($user['username'] ?? '') ?></span></td>
                            <td data-label="Email"><span class="cell-email"><?= esc($user['email'] ?? '') ?></span></td>
                            <td data-label="Role">
                                <span class="role-badge <?= esc($roleKey, 'attr') ?>">
                                    <?= esc($roleLabel) ?>
                                </span>
                            </td>
                            <td data-label="Status">
                                <span class="status-badge <?= esc($statusKey, 'attr') ?>">
                                    <span class="status-dot" aria-hidden="true"></span><?= esc($statusText) ?>
                                </span>
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
                        <td colspan="6">
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
                    <td colspan="6">
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

<!-- ===== ADD USER MODAL ===== -->
<div class="modal fade admin-modal" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addUserModalLabel"><i class="bi bi-person-plus me-2" aria-hidden="true"></i>Add New User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= base_url('admin/users/create') ?>" method="POST" data-guard-submit>
                <?= csrf_field() ?>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" for="add_full_name">Full Name <span class="req">*</span></label>
                        <input type="text" class="form-control" id="add_full_name" name="full_name" autocomplete="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="add_username">Username <span class="req">*</span></label>
                        <input type="text" class="form-control" id="add_username" name="username" autocomplete="off" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="add_email">Email <span class="req">*</span></label>
                        <input type="email" class="form-control" id="add_email" name="email" autocomplete="email" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="add_password">Password <span class="req">*</span></label>
                        <div class="password-field">
                            <input type="password" class="form-control" id="add_password" name="password" autocomplete="new-password" required>
                            <button type="button" class="password-toggle" data-toggle-password="add_password" aria-label="Show password" title="Show password">
                                <i class="bi bi-eye" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="add_role">Role <span class="req">*</span></label>
                        <select class="form-select" id="add_role" name="role" required>
                            <option value="">Select Role</option>
                            <option value="admin">Administrator</option>
                            <option value="receptionist">Receptionist</option>
                            <option value="med_tech">Medical Technologist</option>
                            <option value="radiologist">Radiologist</option>
                        </select>
                    </div>
                    <div class="mb-0">
                        <label class="form-label" for="add_status">Status</label>
                        <select class="form-select" id="add_status" name="status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ===== EDIT USER MODAL ===== -->
<div class="modal fade admin-modal" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editUserModalLabel"><i class="bi bi-pencil me-2" aria-hidden="true"></i>Edit User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editUserForm" action="" method="POST" data-guard-submit>
                <?= csrf_field() ?>
                <div class="modal-body">
                    <input type="hidden" name="user_id" id="edit_user_id">

                    <div class="mb-3">
                        <label class="form-label" for="edit_username_display">Username</label>
                        <input type="text" class="form-control" id="edit_username_display" disabled>
                        <small class="field-hint">Usernames cannot be changed after creation.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="edit_full_name">Full Name <span class="req">*</span></label>
                        <input type="text" class="form-control" name="full_name" id="edit_full_name" autocomplete="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="edit_email">Email <span class="req">*</span></label>
                        <input type="email" class="form-control" name="email" id="edit_email" autocomplete="email" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="edit_password">New Password</label>
                        <div class="password-field">
                            <input type="password" class="form-control" name="password" id="edit_password" autocomplete="new-password">
                            <button type="button" class="password-toggle" data-toggle-password="edit_password" aria-label="Show password" title="Show password">
                                <i class="bi bi-eye" aria-hidden="true"></i>
                            </button>
                        </div>
                        <small class="field-hint">Leave blank to keep the current password.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="edit_role">Role <span class="req">*</span></label>
                        <select class="form-select" name="role" id="edit_role" required>
                            <option value="admin">Administrator</option>
                            <option value="receptionist">Receptionist</option>
                            <option value="med_tech">Medical Technologist</option>
                            <option value="radiologist">Radiologist</option>
                        </select>
                    </div>
                    <div class="mb-0">
                        <label class="form-label" for="edit_status">Status</label>
                        <select class="form-select" name="status" id="edit_status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update User</button>
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
                <a href="#" id="toggle_user_link" class="btn btn-primary" role="button">Confirm</a>
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
                <a href="#" id="delete_user_link" class="btn btn-danger" role="button">Delete User</a>
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
    --um-accent:        #0148ca;
    --um-ink:           #0a2b4e;
    --um-text:          #334155;
    --um-muted:         #64748b;
    --um-faint:         #94a3b8;
    --um-line:          #e2e8f0;
    --um-line-soft:     #eef2f7;
    --um-surface:       #ffffff;
    --um-canvas:        #f8fafc;
    --um-danger:        #b91c1c;
    --um-radius:        12px;
    --um-radius-sm:     8px;
    --um-radius-xs:     6px;
}

/* ===== PAGE HEADER ===== */
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
    margin-bottom: 1.25rem;
    padding-bottom: 1.25rem;
    border-bottom: 1px solid var(--um-line-soft);
}

.page-header-text { min-width: 0; }

.page-title {
    font-weight: 700;
    color: var(--um-ink);
    margin: 0 0 2px;
    font-size: 1.25rem;
    letter-spacing: -0.01em;
}

.page-subtitle {
    color: var(--um-muted);
    font-size: 0.85rem;
    margin: 0;
}

.btn-add-user {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    border-radius: var(--um-radius-xs);
    font-size: 0.875rem;
    font-weight: 600;
    padding: 0.55rem 1rem;
    white-space: nowrap;
}

/* ===== CARD ===== */
.table-card {
    background: var(--um-surface);
    border-radius: var(--um-radius);
    padding: 1.5rem;
    box-shadow: 0 1px 2px rgba(10, 43, 78, 0.05);
    border: 1px solid var(--um-line);
}

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
    border-radius: var(--um-radius-sm);
    font-size: 0.875rem;
    padding: 0.75rem 2.5rem 0.75rem 0.9rem;
    border-width: 1px;
}

.admin-alert i { font-size: 1rem; line-height: 1; flex-shrink: 0; }
.admin-alert span { flex: 1; min-width: 0; }

/* ===== TOOLBAR ===== */
.table-toolbar {
    margin-bottom: 1rem;
}

.search-wrapper {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    border: 1px solid var(--um-line);
    border-radius: var(--um-radius-xs);
    padding: 0.1rem 0.75rem;
    max-width: 320px;
    background: var(--um-surface);
    transition: border-color 0.18s ease, box-shadow 0.18s ease;
}

.search-wrapper:focus-within {
    border-color: var(--um-accent);
    box-shadow: 0 0 0 3px rgba(1, 72, 202, 0.10);
}

.search-wrapper i { color: var(--um-faint); font-size: 0.9rem; }

.search-wrapper .form-control {
    border: none;
    padding: 0.5rem 0;
    font-size: 0.875rem;
    background: transparent;
    color: var(--um-ink);
}

.search-wrapper .form-control:focus {
    box-shadow: none;
    border: none;
}

.search-wrapper .form-control::-webkit-search-cancel-button { cursor: pointer; }

/* ===== TABLE ===== */
.admin-table {
    margin: 0;
    border-collapse: separate;
    border-spacing: 0;
}

.admin-table thead th {
    font-size: 0.68rem;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--um-muted);
    font-weight: 600;
    background: var(--um-canvas);
    border-bottom: 1px solid var(--um-line);
    border-top: 1px solid var(--um-line);
    padding: 0.7rem 0.75rem;
    white-space: nowrap;
}

.admin-table thead th:first-child {
    border-left: 1px solid var(--um-line);
    border-top-left-radius: var(--um-radius-xs);
    border-bottom-left-radius: 0;
}

.admin-table thead th:last-child {
    border-right: 1px solid var(--um-line);
    border-top-right-radius: var(--um-radius-xs);
}

.admin-table tbody td {
    padding: 0.7rem 0.75rem;
    vertical-align: middle;
    font-size: 0.85rem;
    color: var(--um-text);
    border-bottom: 1px solid var(--um-line-soft);
    background: transparent;
}

.admin-table tbody tr:last-child td { border-bottom: none; }

.admin-table tbody tr[data-user-row]:hover td { background: var(--um-canvas); }

.col-actions { text-align: right; }

/* ===== USER CELL ===== */
.user-cell {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    min-width: 0;
}

.user-avatar {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.02em;
    flex-shrink: 0;
    background: #eef2f7;
    color: #475569;
}

.avatar-admin        { background: #e6f0fa; color: #0148ca; }
.avatar-receptionist { background: #e0f2f4; color: #0e7490; }
.avatar-med_tech     { background: #e7f6ee; color: #047857; }
.avatar-radiologist  { background: #fdf0e2; color: #b45309; }

.user-name {
    font-weight: 600;
    color: var(--um-ink);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    min-width: 0;
}

.self-chip {
    font-size: 0.62rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--um-muted);
    background: var(--um-line-soft);
    border: 1px solid var(--um-line);
    border-radius: 4px;
    padding: 0.1rem 0.35rem;
    flex-shrink: 0;
}

.cell-mono {
    font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
    font-size: 0.8rem;
    color: var(--um-text);
}

.cell-email {
    color: var(--um-text);
    overflow-wrap: anywhere;
}

/* ===== BADGES ===== */
.role-badge,
.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.22rem 0.55rem;
    border-radius: var(--um-radius-xs);
    font-size: 0.72rem;
    font-weight: 600;
    white-space: nowrap;
    border: 1px solid transparent;
}

.role-badge.admin        { background: #e6f0fa; color: #0b3fa8; border-color: #cfe0f5; }
.role-badge.receptionist { background: #e0f2f4; color: #0e7490; border-color: #c7e6ea; }
.role-badge.med_tech     { background: #e7f6ee; color: #047857; border-color: #cbeada; }
.role-badge.radiologist  { background: #fdf0e2; color: #b45309; border-color: #f6ddbf; }
.role-badge.unknown      { background: var(--um-line-soft); color: var(--um-muted); border-color: var(--um-line); }

.status-badge.active   { background: #e7f6ee; color: #15803d; border-color: #cbeada; }
.status-badge.inactive { background: #fdeaea; color: #b91c1c; border-color: #f6d2d2; }

.status-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: currentColor;
    flex-shrink: 0;
}

/* ===== ACTIONS ===== */
.action-group {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 0.25rem;
}

.action-icon {
    background: transparent;
    border: 1px solid transparent;
    color: var(--um-faint);
    width: 30px;
    height: 30px;
    border-radius: var(--um-radius-xs);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: color 0.18s ease, background-color 0.18s ease, border-color 0.18s ease;
    font-size: 0.9rem;
    line-height: 1;
}

.action-icon:hover {
    color: var(--um-accent);
    background: #eef4fd;
    border-color: #d6e4fa;
}

.action-icon.delete-user:hover {
    color: var(--um-danger);
    background: #fdeaea;
    border-color: #f6d2d2;
}

.action-icon.toggle-status:hover {
    color: #b45309;
    background: #fdf0e2;
    border-color: #f6ddbf;
}

.action-note {
    font-size: 0.72rem;
    color: var(--um-faint);
    font-style: normal;
}

/* ===== EMPTY STATES ===== */
.empty-state {
    text-align: center;
    padding: 2rem 1rem;
    color: var(--um-muted);
}

.empty-state i {
    font-size: 1.6rem;
    color: #cbd5e1;
    display: block;
    margin-bottom: 0.5rem;
}

.empty-title {
    margin: 0 0 0.15rem;
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--um-text);
}

.empty-hint {
    margin: 0;
    font-size: 0.8rem;
    color: var(--um-faint);
}

/* ===== FOOTER ===== */
.table-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 0.75rem;
    flex-wrap: wrap;
    margin-top: 1.25rem;
    padding-top: 1rem;
    border-top: 1px solid var(--um-line-soft);
}

.table-footer span {
    font-size: 0.82rem;
    color: var(--um-muted);
}

.filter-count { color: var(--um-accent); font-weight: 600; }

/* ===== MODALS ===== */
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
   RESPONSIVE
   ============================================ */

@media (max-width: 991px) {
    .search-wrapper { max-width: 100%; }
}

@media (max-width: 768px) {
    .table-card { padding: 1.1rem; }

    .page-header {
        flex-direction: column;
        gap: 0.75rem;
        align-items: stretch;
    }

    .btn-add-user { justify-content: center; width: 100%; }

    /* Stacked card layout so the table never needs sideways scrolling */
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
        border: 1px solid var(--um-line);
        border-radius: var(--um-radius-sm);
        padding: 0.35rem 0.85rem;
        margin-bottom: 0.75rem;
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
    .table-card { padding: 0.9rem; }
    .cell-email { font-size: 0.8rem; }
}

/* ===== MOTION PREFERENCES ===== */
@media (prefers-reduced-motion: reduce) {
    .table-card *,
    .admin-modal * {
        transition-duration: 0.01ms !important;
        animation-duration: 0.01ms !important;
    }
}

/* ===== PRINT ===== */
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

    function setValue(id, value) {
        var el = document.getElementById(id);
        if (el) { el.value = value == null ? '' : value; }
    }

    // ============================================
    // Edit User - populate modal
    // ============================================
    document.querySelectorAll('.edit-user').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id = this.dataset.id;

            setValue('edit_user_id', id);
            setValue('edit_username_display', this.dataset.username);
            setValue('edit_full_name', this.dataset.fullname);
            setValue('edit_email', this.dataset.email);
            setValue('edit_role', this.dataset.role);
            setValue('edit_status', this.dataset.status);
            setValue('edit_password', '');

            var form = document.getElementById('editUserForm');
            if (form) { form.action = BASE_UPDATE + '/' + encodeURIComponent(id); }
        });
    });

    // ============================================
    // Delete User - populate modal
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
    // Toggle Status - confirm in a modal, falling back
    // to the native prompt if Bootstrap is unavailable.
    // Destination URL is unchanged.
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
                    window.location.href = target;
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
    // Search - filters user rows only, never the
    // empty or no-results placeholder rows
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
    // Prevent duplicate submissions on valid forms
    // ============================================
    document.querySelectorAll('form[data-guard-submit]').forEach(function (form) {
        form.addEventListener('submit', function () {
            if (typeof form.checkValidity === 'function' && !form.checkValidity()) {
                return;
            }
            var submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Saving...';
            }
        });
    });

    // Re-enable the submit button if a modal is closed and reopened
    document.querySelectorAll('.admin-modal').forEach(function (modalEl) {
        modalEl.addEventListener('show.bs.modal', function () {
            var submitBtn = modalEl.querySelector('button[type="submit"]');
            if (submitBtn && submitBtn.disabled) {
                submitBtn.disabled = false;
                submitBtn.textContent = modalEl.id === 'addUserModal' ? 'Create User' : 'Update User';
            }
        });
    });

    // Focus the first field when the Add modal opens
    var addModal = document.getElementById('addUserModal');
    if (addModal) {
        addModal.addEventListener('shown.bs.modal', function () {
            var first = document.getElementById('add_full_name');
            if (first) { first.focus(); }
        });
    }
})();
</script>

<?= $this->endSection() ?>