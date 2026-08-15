<?= $this->extend('layouts/AdminLayout') ?>

<?= $this->section('pageTitle') ?>User Management<?= $this->endSection() ?>

<?= $this->section('adminContent') ?>

<div class="table-card">
    <div class="page-header">
        <div>
            <h4 class="page-title">User Management</h4>
            <p class="page-subtitle">Manage system users and their roles</p>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
            <i class="bi bi-person-plus me-2"></i>Add User
        </button>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= session()->getFlashdata('success') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= session()->getFlashdata('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="table-toolbar">
        <div class="search-wrapper">
            <i class="bi bi-search"></i>
            <input type="text" class="form-control" placeholder="Search users..." id="searchUsers">
        </div>
    </div>
    
    <div class="table-responsive">
        <table class="table admin-table" id="usersTable">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (isset($users) && count($users) > 0): ?>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td>
                                <div class="user-cell">
                                    <span><?= esc($user['full_name']) ?></span>
                                </div>
                            </td>
                            <td><?= esc($user['username']) ?></td>
                            <td><?= esc($user['email']) ?></td>
                            <td>
                                <span class="role-badge <?= $user['role'] ?>">
                                    <?= ucfirst(str_replace('_', ' ', $user['role'])) ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge <?= $user['status'] ?? 'active' ?>">
                                    <?= ucfirst($user['status'] ?? 'Active') ?>
                                </span>
                            </td>
                            <td>
                                <button class="action-icon edit-user" 
                                        data-id="<?= $user['id'] ?>"
                                        data-username="<?= esc($user['username']) ?>"
                                        data-fullname="<?= esc($user['full_name']) ?>"
                                        data-email="<?= esc($user['email']) ?>"
                                        data-role="<?= esc($user['role']) ?>"
                                        data-status="<?= esc($user['status'] ?? 'active') ?>"
                                        data-bs-toggle="modal" 
                                        data-bs-target="#editUserModal">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                
                                <?php if ($user['id'] != session()->get('user_id')): ?>
                                    <button class="action-icon toggle-status" 
                                            data-id="<?= $user['id'] ?>"
                                            data-status="<?= $user['status'] ?? 'active' ?>">
                                        <i class="bi bi-<?= ($user['status'] ?? 'active') === 'active' ? 'lock' : 'unlock' ?>"></i>
                                    </button>
                                    <button class="action-icon delete-user" 
                                            data-id="<?= $user['id'] ?>"
                                            data-name="<?= esc($user['full_name']) ?>"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#deleteUserModal">
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted">No users found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <div class="table-footer">
        <span>Showing <?= count($users ?? []) ?> of <?= $total ?? 0 ?> users</span>
    </div>
</div>

<!-- ===== ADD USER MODAL ===== -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-person-plus me-2"></i>Add New User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= base_url('admin/users/create') ?>" method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Full Name *</label>
                        <input type="text" class="form-control" name="full_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Username *</label>
                        <input type="text" class="form-control" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email *</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password *</label>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role *</label>
                        <select class="form-select" name="role" required>
                            <option value="">Select Role</option>
                            <option value="admin">Administrator</option>
                            <option value="receptionist">Receptionist</option>
                            <option value="med_tech">Medical Technologist</option>
                            <option value="radiologist">Radiologist</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
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
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Edit User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editUserForm" action="" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="user_id" id="edit_user_id">
                    <div class="mb-3">
                        <label class="form-label">Full Name *</label>
                        <input type="text" class="form-control" name="full_name" id="edit_full_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email *</label>
                        <input type="email" class="form-control" name="email" id="edit_email" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password (leave blank to keep current)</label>
                        <input type="password" class="form-control" name="password">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role *</label>
                        <select class="form-select" name="role" id="edit_role" required>
                            <option value="admin">Administrator</option>
                            <option value="receptionist">Receptionist</option>
                            <option value="med_tech">Medical Technologist</option>
                            <option value="radiologist">Radiologist</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
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

<!-- ===== DELETE USER MODAL ===== -->
<div class="modal fade" id="deleteUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger"><i class="bi bi-exclamation-triangle me-2"></i>Delete User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete <strong id="delete_user_name"></strong>?</p>
                <p class="text-danger small">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="delete_user_link" class="btn btn-danger">Delete User</a>
            </div>
        </div>
    </div>
</div>

<style>
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.page-title {
    font-weight: 700;
    color: #0a2b4e;
    margin: 0;
    font-size: 1.3rem;
}

.page-subtitle {
    color: #64748b;
    font-size: 0.85rem;
    margin: 0;
}

.table-card {
    background: #ffffff;
    border-radius: 16px;
    padding: 1.5rem;
    box-shadow: 0 2px 12px rgba(10, 43, 78, 0.06);
    border: 1px solid rgba(1, 72, 202, 0.04);
}

.table-toolbar {
    margin-bottom: 1.25rem;
}

.search-wrapper {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    padding: 0.25rem 0.75rem;
    max-width: 300px;
    transition: all 0.3s ease;
}

.search-wrapper:focus-within {
    border-color: #0148ca;
    box-shadow: 0 0 0 4px rgba(1, 72, 202, 0.08);
}

.search-wrapper i {
    color: #94a3b8;
}

.search-wrapper .form-control {
    border: none;
    padding: 0.5rem 0;
    font-size: 0.9rem;
    background: transparent;
}

.search-wrapper .form-control:focus {
    box-shadow: none;
}

.admin-table {
    margin: 0;
}

.admin-table thead th {
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #64748b;
    font-weight: 600;
    border-bottom: 2px solid #f0f4ff;
    padding: 0.75rem 0.5rem;
}

.admin-table tbody td {
    padding: 0.75rem 0.5rem;
    vertical-align: middle;
    font-size: 0.85rem;
    color: #0a2b4e;
    border-bottom: 1px solid #f0f4ff;
}

.admin-table tbody tr:hover {
    background: #f8faff;
}

.user-cell {
    display: flex;
    align-items: center;
    gap: 0.6rem;
}

.role-badge {
    padding: 0.2rem 0.6rem;
    border-radius: 30px;
    font-size: 0.7rem;
    font-weight: 600;
    white-space: nowrap;
}

.role-badge.admin {
    background: #e6f0fa;
    color: #0148ca;
}

.role-badge.receptionist {
    background: #e0f7fa;
    color: #17a2b8;
}

.role-badge.med_tech {
    background: #f3e5f5;
    color: #800080;
}

.role-badge.radiologist {
    background: #fff3e0;
    color: #ff6b00;
}

.status-badge {
    padding: 0.2rem 0.6rem;
    border-radius: 30px;
    font-size: 0.7rem;
    font-weight: 600;
}

.status-badge.active {
    background: #e8f5e9;
    color: #28a745;
}

.status-badge.inactive {
    background: #fce4ec;
    color: #dc3545;
}

.action-icon {
    background: transparent;
    border: none;
    color: #94a3b8;
    padding: 0.2rem 0.4rem;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 0.9rem;
}

.action-icon:hover {
    color: #0148ca;
}

.action-icon.delete-user:hover {
    color: #dc3545;
}

.action-icon.toggle-status:hover {
    color: #ff6b00;
}

.table-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 1.25rem;
    padding-top: 1rem;
    border-top: 1px solid #f0f4ff;
}

.table-footer span {
    font-size: 0.85rem;
    color: #64748b;
}

@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        gap: 0.75rem;
        align-items: stretch;
    }
    
    .search-wrapper {
        max-width: 100%;
    }
    
    .table-footer {
        flex-direction: column;
        gap: 0.75rem;
        align-items: center;
    }
}
</style>

<script>
// Edit User - populate modal
document.querySelectorAll('.edit-user').forEach(btn => {
    btn.addEventListener('click', function() {
        const id = this.dataset.id;
        const fullname = this.dataset.fullname;
        const email = this.dataset.email;
        const role = this.dataset.role;
        const status = this.dataset.status;
        
        document.getElementById('edit_user_id').value = id;
        document.getElementById('edit_full_name').value = fullname;
        document.getElementById('edit_email').value = email;
        document.getElementById('edit_role').value = role;
        document.getElementById('edit_status').value = status;
        
        document.getElementById('editUserForm').action = `<?= base_url('admin/users/update') ?>/${id}`;
    });
});

// Delete User - populate modal
document.querySelectorAll('.delete-user').forEach(btn => {
    btn.addEventListener('click', function() {
        const id = this.dataset.id;
        const name = this.dataset.name;
        
        document.getElementById('delete_user_name').textContent = name;
        document.getElementById('delete_user_link').href = `<?= base_url('admin/users/delete') ?>/${id}`;
    });
});

// Toggle Status
document.querySelectorAll('.toggle-status').forEach(btn => {
    btn.addEventListener('click', function() {
        const id = this.dataset.id;
        const status = this.dataset.status;
        const newStatus = status === 'active' ? 'inactive' : 'active';
        const confirmMsg = `Are you sure you want to ${newStatus === 'active' ? 'activate' : 'deactivate'} this user?`;
        
        if (confirm(confirmMsg)) {
            window.location.href = `<?= base_url('admin/users/toggle') ?>/${id}`;
        }
    });
});

// Search functionality
document.getElementById('searchUsers').addEventListener('keyup', function() {
    const searchTerm = this.value.toLowerCase();
    const rows = document.querySelectorAll('#usersTable tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
});
</script>

<?= $this->endSection() ?>