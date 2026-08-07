<?= $this->extend('layouts/AdminLayout') ?>

<?= $this->section('pageTitle') ?>User Management<?= $this->endSection() ?>

<?= $this->section('adminContent') ?>

<div class="page-header">
    <div>
        <h4 class="page-title">User Management</h4>
        <p class="page-subtitle">Manage system users and their roles</p>
    </div>
    <button class="btn btn-primary-custom">
        <i class="bi bi-person-plus me-2"></i>Add User
    </button>
</div>

<div class="table-card">
    <div class="table-toolbar">
        <div class="search-wrapper">
            <i class="bi bi-search"></i>
            <input type="text" class="form-control" placeholder="Search users..." id="searchUsers">
        </div>
    </div>
    
    <div class="table-responsive">
        <table class="table admin-table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Department</th>
                    <th>Status</th>
                    <th>Last Login</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <div class="user-cell">
                            <img src="/polymedic/public/assets/images/avatar-placeholder.png" alt="User" class="avatar-sm">
                            <span>Dr. Ana Cruz</span>
                        </div>
                    </td>
                    <td>acruz</td>
                    <td><span class="role-badge physician">Physician</span></td>
                    <td>Internal Medicine</td>
                    <td><span class="status-badge active">Active</span></td>
                    <td>Jul 15, 2026 09:14 AM</td>
                    <td>
                        <button class="action-icon"><i class="bi bi-pencil"></i></button>
                        <button class="action-icon"><i class="bi bi-lock"></i></button>
                        <button class="action-icon"><i class="bi bi-trash3"></i></button>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="user-cell">
                            <img src="/polymedic/public/assets/images/avatar-placeholder.png" alt="User" class="avatar-sm">
                            <span>Dr. Mark Rivera</span>
                        </div>
                    </td>
                    <td>mrivera</td>
                    <td><span class="role-badge physician">Physician</span></td>
                    <td>Cardiology</td>
                    <td><span class="status-badge active">Active</span></td>
                    <td>Jul 15, 2026 08:52 AM</td>
                    <td>
                        <button class="action-icon"><i class="bi bi-pencil"></i></button>
                        <button class="action-icon"><i class="bi bi-lock"></i></button>
                        <button class="action-icon"><i class="bi bi-trash3"></i></button>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="user-cell">
                            <img src="/polymedic/public/assets/images/avatar-placeholder.png" alt="User" class="avatar-sm">
                            <span>Maria Santos</span>
                        </div>
                    </td>
                    <td>msantos</td>
                    <td><span class="role-badge technologist">Med Tech</span></td>
                    <td>Laboratory</td>
                    <td><span class="status-badge active">Active</span></td>
                    <td>Jul 15, 2026 07:30 AM</td>
                    <td>
                        <button class="action-icon"><i class="bi bi-pencil"></i></button>
                        <button class="action-icon"><i class="bi bi-lock"></i></button>
                        <button class="action-icon"><i class="bi bi-trash3"></i></button>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="user-cell">
                            <img src="/polymedic/public/assets/images/avatar-placeholder.png" alt="User" class="avatar-sm">
                            <span>Jose Reyes</span>
                        </div>
                    </td>
                    <td>jreyes</td>
                    <td><span class="role-badge radiologist">Radiologist</span></td>
                    <td>Radiology</td>
                    <td><span class="status-badge active">Active</span></td>
                    <td>Jul 14, 2026 05:15 PM</td>
                    <td>
                        <button class="action-icon"><i class="bi bi-pencil"></i></button>
                        <button class="action-icon"><i class="bi bi-lock"></i></button>
                        <button class="action-icon"><i class="bi bi-trash3"></i></button>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="user-cell">
                            <img src="/polymedic/public/assets/images/avatar-placeholder.png" alt="User" class="avatar-sm">
                            <span>Clara Bautista</span>
                        </div>
                    </td>
                    <td>cbautista</td>
                    <td><span class="role-badge receptionist">Receptionist</span></td>
                    <td>Finance</td>
                    <td><span class="status-badge inactive">Inactive</span></td>
                    <td>Jul 10, 2026 02:00 PM</td>
                    <td>
                        <button class="action-icon"><i class="bi bi-pencil"></i></button>
                        <button class="action-icon"><i class="bi bi-lock"></i></button>
                        <button class="action-icon"><i class="bi bi-trash3"></i></button>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="user-cell">
                            <img src="/polymedic/public/assets/images/avatar-placeholder.png" alt="User" class="avatar-sm">
                            <span>Admin User</span>
                        </div>
                    </td>
                    <td>admin</td>
                    <td><span class="role-badge admin">Administrator</span></td>
                    <td>IT</td>
                    <td><span class="status-badge active">Active</span></td>
                    <td>Jul 15, 2026 06:00 AM</td>
                    <td>
                        <button class="action-icon"><i class="bi bi-pencil"></i></button>
                        <button class="action-icon"><i class="bi bi-lock"></i></button>
                        <button class="action-icon"><i class="bi bi-trash3"></i></button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <div class="table-footer">
        <span>Showing 6 of 6 users</span>
        <div class="pagination-wrapper">
            <button class="page-btn"><i class="bi bi-chevron-left"></i></button>
            <button class="page-btn active">1</button>
            <button class="page-btn">2</button>
            <button class="page-btn">3</button>
            <button class="page-btn"><i class="bi bi-chevron-right"></i></button>
        </div>
    </div>
</div>

<style>
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
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

.btn-primary-custom {
    background: linear-gradient(135deg, #0148ca, #0037a0);
    border: none;
    color: white;
    padding: 0.6rem 1.5rem;
    border-radius: 10px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-primary-custom:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(1, 72, 202, 0.3);
    color: white;
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

.avatar-sm {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #eef2f7;
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

.role-badge.physician {
    background: #e8f5e9;
    color: #28a745;
}

.role-badge.technologist {
    background: #f3e5f5;
    color: #800080;
}

.role-badge.radiologist {
    background: #fff3e0;
    color: #ff6b00;
}

.role-badge.receptionist {
    background: #e0f7fa;
    color: #17a2b8;
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

.action-icon:last-child:hover {
    color: #dc3545;
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

.pagination-wrapper {
    display: flex;
    gap: 0.25rem;
}

.page-btn {
    width: 32px;
    height: 32px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    background: transparent;
    color: #64748b;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
}

.page-btn:hover {
    border-color: #0148ca;
    color: #0148ca;
}

.page-btn.active {
    background: #0148ca;
    border-color: #0148ca;
    color: white;
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
    
    .admin-table {
        font-size: 0.8rem;
    }
    
    .admin-table thead th,
    .admin-table tbody td {
        padding: 0.5rem 0.3rem;
    }
}
</style>

<?= $this->endSection() ?>