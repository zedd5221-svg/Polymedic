<?= $this->extend('layouts/ReceptionistLayout') ?>

<?= $this->section('pageTitle') ?>Patient Management<?= $this->endSection() ?>

<?= $this->section('receptionistContent') ?>

<div class="table-card">
    <div class="page-header">
        <div>
            <h4 class="page-title">Patient Management</h4>
            <p class="page-subtitle">View all approved patients ready for consultation</p>
        </div>
        <button class="btn btn-primary-custom" onclick="window.location.href='<?= base_url('appointment/book') ?>'">
            <i class="bi bi-person-plus me-2"></i>Register Patient
        </button>
    </div>

    <!-- Stats Cards -->
    <div class="stats-row">
        <div class="stat-mini">
            <span class="stat-label">Total Approved Patients</span>
            <span class="stat-number"><?= $total ?? 0 ?></span>
        </div>
        <div class="stat-mini">
            <span class="stat-label">With Appointments</span>
            <span class="stat-number"><?= count($patients ?? []) ?></span>
        </div>
    </div>

    <div class="table-toolbar">
        <div class="search-wrapper">
            <i class="bi bi-search"></i>
            <input type="text" class="form-control" placeholder="Search patients..." id="searchPatients">
        </div>
        <button class="btn btn-outline-secondary btn-sm" onclick="window.location.href='<?= base_url('receptionist/appointments') ?>'">
            <i class="bi bi-calendar3 me-1"></i>View All Appointments
        </button>
    </div>
    
    <div class="table-responsive">
        <table class="table admin-table" id="patientsTable">
            <thead>
                <tr>
                    <th>Patient Name</th>
                    <th>Gender</th>
                    <th>Age</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Service</th>
                    <th>Appointment Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (isset($patients) && count($patients) > 0): ?>
                    <?php foreach ($patients as $patient): ?>
                        <tr>
                            <td>
                                <div class="user-cell">
                                    <span><strong><?= esc($patient['full_name']) ?></strong></span>
                                </div>
                            </td>
                            <td><?= esc($patient['gender']) ?></td>
                            <td><?= esc($patient['age']) ?></td>
                            <td><?= esc($patient['email']) ?></td>
                            <td><?= esc($patient['phone']) ?></td>
                            <td><?= ucfirst(esc($patient['service_type'] ?? 'N/A')) ?></td>
                            <td><?= date('M d, Y', strtotime($patient['appointment_date'])) ?></td>
                            <td>
                                <span class="status-badge <?= $patient['status'] ?>">
                                    <?= ucfirst($patient['status']) ?>
                                </span>
                            </td>
                            <td>
                                <button class="action-icon" title="View Appointment" 
                                        onclick="window.location.href='<?= base_url('receptionist/appointment/view/' . $patient['id']) ?>'">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <button class="action-icon" title="Check In" 
                                        onclick="checkIn(<?= $patient['id'] ?>, '<?= esc($patient['full_name']) ?>')">
                                    <i class="bi bi-check2-circle" style="color: #28a745;"></i>
                                </button>
                                <button class="action-icon" title="Email">
                                    <i class="bi bi-envelope"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" class="text-center text-muted">
                            <div class="empty-state">
                                <i class="bi bi-people" style="font-size: 2rem; color: #94a3b8;"></i>
                                <p>No approved patients yet.</p>
                                <small>Patients will appear here once their appointments are approved.</small>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <div class="table-footer">
        <span>Showing <?= count($patients ?? []) ?> of <?= $total ?? 0 ?> approved patients</span>
    </div>
</div>

<style>
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
    gap: 1rem;
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

.stats-row {
    display: flex;
    gap: 1rem;
    margin-bottom: 1.25rem;
    flex-wrap: wrap;
}

.stat-mini {
    background: #f8faff;
    border-radius: 10px;
    padding: 0.75rem 1.25rem;
    border: 1px solid #eef2f7;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.stat-mini .stat-label {
    font-size: 0.8rem;
    color: #64748b;
}

.stat-mini .stat-number {
    font-size: 1.2rem;
    font-weight: 700;
    color: #0a2b4e;
}

.table-card {
    background: #ffffff;
    border-radius: 16px;
    padding: 1.5rem;
    box-shadow: 0 2px 12px rgba(10, 43, 78, 0.06);
    border: 1px solid rgba(1, 72, 202, 0.04);
}

.table-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.25rem;
    flex-wrap: wrap;
    gap: 0.75rem;
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
    flex: 1;
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

.status-badge {
    padding: 0.2rem 0.6rem;
    border-radius: 30px;
    font-size: 0.7rem;
    font-weight: 600;
}

.status-badge.approved {
    background: #e3f2fd;
    color: #0148ca;
}

.status-badge.pending {
    background: #fff3e0;
    color: #ff6b00;
}

.status-badge.completed {
    background: #e8f5e9;
    color: #28a745;
}

.status-badge.cancelled {
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

.empty-state {
    padding: 2rem;
    text-align: center;
}

.empty-state p {
    margin: 0.5rem 0 0.25rem;
    color: #0a2b4e;
    font-weight: 500;
}

.empty-state small {
    color: #94a3b8;
}

.table-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 1.25rem;
    padding-top: 1rem;
    border-top: 1px solid #f0f4ff;
    flex-wrap: wrap;
    gap: 0.75rem;
}

.table-footer span {
    font-size: 0.85rem;
    color: #64748b;
}

@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        align-items: stretch;
    }
    
    .table-toolbar {
        flex-direction: column;
        align-items: stretch;
    }
    
    .search-wrapper {
        max-width: 100%;
    }
    
    .table-footer {
        flex-direction: column;
        align-items: center;
    }
    
    .admin-table {
        font-size: 0.75rem;
    }
    
    .admin-table thead th,
    .admin-table tbody td {
        padding: 0.4rem 0.25rem;
    }
    
    .stats-row {
        flex-direction: column;
        gap: 0.5rem;
    }
}
</style>

<script>
// Search functionality
document.getElementById('searchPatients').addEventListener('keyup', function() {
    const searchTerm = this.value.toLowerCase();
    const rows = document.querySelectorAll('#patientsTable tbody tr');
    
    rows.forEach(row => {
        if (row.querySelector('td')) {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchTerm) ? '' : 'none';
        }
    });
});

// Check in patient
function checkIn(id, name) {
    if (confirm(`Check in ${name}? This will mark them as arrived.`)) {
        alert('Check in functionality coming soon for patient ID: ' + id);
        // You can add AJAX call here to update status
    }
}
</script>

<?= $this->endSection() ?>