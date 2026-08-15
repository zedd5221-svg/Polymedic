<?= $this->extend('layouts/ReceptionistLayout') ?>

<?= $this->section('pageTitle') ?>Appointments<?= $this->endSection() ?>

<?= $this->section('receptionistContent') ?>

<div class="page-header">
    <div>
        <h4 class="page-title">Appointment Management</h4>
        <p class="page-subtitle">Manage all patient appointments</p>
    </div>
    <button class="btn btn-primary" onclick="window.location.href='<?= base_url('appointment/book') ?>'">
        <i class="bi bi-plus-circle me-2"></i>New Appointment
    </button>
</div>

<!-- Stats Cards -->
<div class="stats-grid-mini">
    <div class="stat-card-mini">
        <div class="stat-icon-mini blue">
            <i class="bi bi-calendar3"></i>
        </div>
        <div>
            <h3><?= $total ?? 0 ?></h3>
            <p>Total</p>
        </div>
    </div>
    <div class="stat-card-mini">
        <div class="stat-icon-mini orange">
            <i class="bi bi-clock-history"></i>
        </div>
        <div>
            <h3><?= $pending ?? 0 ?></h3>
            <p>Pending</p>
        </div>
    </div>
    <div class="stat-card-mini">
        <div class="stat-icon-mini green">
            <i class="bi bi-check-circle"></i>
        </div>
        <div>
            <h3><?= $approved ?? 0 ?></h3>
            <p>Approved</p>
        </div>
    </div>
    <div class="stat-card-mini">
        <div class="stat-icon-mini teal">
            <i class="bi bi-check-all"></i>
        </div>
        <div>
            <h3><?= $completed ?? 0 ?></h3>
            <p>Completed</p>
        </div>
    </div>
    <div class="stat-card-mini">
        <div class="stat-icon-mini danger">
            <i class="bi bi-x-circle"></i>
        </div>
        <div>
            <h3><?= $cancelled ?? 0 ?></h3>
            <p>Cancelled</p>
        </div>
    </div>
</div>

<!-- Appointments Table -->
<div class="table-card">
    <div class="table-toolbar">
        <div class="search-wrapper">
            <i class="bi bi-search"></i>
            <input type="text" class="form-control" placeholder="Search appointments..." id="searchAppointments">
        </div>
        <div class="filter-wrapper">
            <select class="form-select" id="filterStatus">
                <option value="">All Status</option>
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="completed">Completed</option>
                <option value="cancelled">Cancelled</option>
            </select>
        </div>
    </div>
    
    <div class="table-responsive">
        <table class="table receptionist-table" id="appointmentsTable">
            <thead>
                <tr>
                    <th>Ref #</th>
                    <th>Patient</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Service</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (isset($appointments) && count($appointments) > 0): ?>
                    <?php foreach ($appointments as $appointment): ?>
                        <tr>
                            <td><code><?= esc($appointment['reference_number']) ?></code></td>
                            <td><?= esc($appointment['full_name']) ?></td>
                            <td><?= date('M d, Y', strtotime($appointment['appointment_date'])) ?></td>
                            <td><?= esc($appointment['appointment_time']) ?></td>
                            <td><?= ucfirst(esc($appointment['service_type'])) ?></td>
                            <td>
                                <span class="status-badge <?= $appointment['status'] ?>">
                                    <?= ucfirst($appointment['status']) ?>
                                </span>
                            </td>
                            <td>
                                <a href="<?= base_url('receptionist/appointment/view/' . $appointment['id']) ?>" 
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <?php if ($appointment['status'] == 'pending'): ?>
                                    <a href="<?= base_url('receptionist/appointment/approve/' . $appointment['id']) ?>" 
                                       class="btn btn-sm btn-outline-success"
                                       onclick="return confirm('Approve this appointment?')">
                                        <i class="bi bi-check2"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if ($appointment['status'] == 'approved'): ?>
                                    <a href="<?= base_url('receptionist/appointment/complete/' . $appointment['id']) ?>" 
                                       class="btn btn-sm btn-outline-info"
                                       onclick="return confirm('Mark this appointment as completed?')">
                                        <i class="bi bi-check-all"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if (!in_array($appointment['status'], ['completed', 'cancelled'])): ?>
                                    <a href="<?= base_url('receptionist/appointment/cancel/' . $appointment['id']) ?>" 
                                       class="btn btn-sm btn-outline-danger"
                                       onclick="return confirm('Cancel this appointment?')">
                                        <i class="bi bi-x"></i>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted">No appointments found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
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

.stats-grid-mini {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 0.75rem;
    margin-bottom: 1.5rem;
}

.stat-card-mini {
    background: #ffffff;
    border-radius: 12px;
    padding: 0.75rem 1rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    box-shadow: 0 2px 8px rgba(10, 43, 78, 0.06);
    border: 1px solid rgba(1, 72, 202, 0.04);
}

.stat-icon-mini {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
}

.stat-icon-mini.blue { background: #e6f0fa; color: #0148ca; }
.stat-icon-mini.orange { background: #fff3e0; color: #ff6b00; }
.stat-icon-mini.green { background: #e8f5e9; color: #28a745; }
.stat-icon-mini.teal { background: #e0f7fa; color: #17a2b8; }
.stat-icon-mini.danger { background: #fce4ec; color: #dc3545; }

.stat-card-mini h3 {
    font-size: 1.1rem;
    font-weight: 700;
    color: #0a2b4e;
    margin: 0;
    line-height: 1.2;
}

.stat-card-mini p {
    color: #64748b;
    font-size: 0.7rem;
    margin: 0;
    font-weight: 500;
}

.table-card {
    background: #ffffff;
    border-radius: 14px;
    padding: 1.25rem 1.5rem;
    box-shadow: 0 2px 12px rgba(10, 43, 78, 0.06);
    border: 1px solid rgba(1, 72, 202, 0.04);
}

.table-toolbar {
    display: flex;
    gap: 1rem;
    margin-bottom: 1.25rem;
    flex-wrap: wrap;
}

.search-wrapper {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    padding: 0.25rem 0.75rem;
    flex: 1;
    min-width: 200px;
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

.filter-wrapper {
    min-width: 150px;
}

.filter-wrapper .form-select {
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    font-size: 0.9rem;
    padding: 0.5rem 0.75rem;
}

.receptionist-table {
    margin: 0;
}

.receptionist-table thead th {
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #64748b;
    font-weight: 600;
    border-bottom: 2px solid #f0f4ff;
    padding: 0.75rem 0.5rem;
}

.receptionist-table tbody td {
    padding: 0.75rem 0.5rem;
    vertical-align: middle;
    font-size: 0.85rem;
    color: #0a2b4e;
    border-bottom: 1px solid #f0f4ff;
}

.receptionist-table tbody tr:hover {
    background: #f8faff;
}

.status-badge {
    padding: 0.2rem 0.6rem;
    border-radius: 30px;
    font-size: 0.7rem;
    font-weight: 600;
}

.status-badge.pending {
    background: #fff3e0;
    color: #ff6b00;
}

.status-badge.approved {
    background: #e3f2fd;
    color: #0148ca;
}

.status-badge.completed {
    background: #e8f5e9;
    color: #28a745;
}

.status-badge.cancelled {
    background: #fce4ec;
    color: #dc3545;
}

.status-badge.late {
    background: #f5f5f5;
    color: #757575;
}

.btn-sm {
    padding: 0.2rem 0.4rem;
    font-size: 0.75rem;
    border-radius: 6px;
}

@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        gap: 0.75rem;
        align-items: stretch;
    }
    
    .stats-grid-mini {
        grid-template-columns: repeat(3, 1fr);
    }
    
    .table-toolbar {
        flex-direction: column;
    }
    
    .search-wrapper {
        min-width: 100%;
    }
    
    .filter-wrapper {
        min-width: 100%;
    }
}

@media (max-width: 576px) {
    .stats-grid-mini {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>

<script>
// Search functionality
document.getElementById('searchAppointments').addEventListener('keyup', function() {
    const searchTerm = this.value.toLowerCase();
    const rows = document.querySelectorAll('#appointmentsTable tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
});

// Filter by status
document.getElementById('filterStatus').addEventListener('change', function() {
    const status = this.value.toLowerCase();
    const rows = document.querySelectorAll('#appointmentsTable tbody tr');
    
    rows.forEach(row => {
        if (!status) {
            row.style.display = '';
            return;
        }
        const statusCell = row.querySelector('.status-badge');
        if (statusCell) {
            const rowStatus = statusCell.textContent.toLowerCase().trim();
            row.style.display = rowStatus === status ? '' : 'none';
        }
    });
});
</script>

<?= $this->endSection() ?>