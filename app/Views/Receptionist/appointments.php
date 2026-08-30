<?= $this->extend('layouts/ReceptionistLayout') ?>

<?= $this->section('pageTitle') ?>Appointments<?= $this->endSection() ?>

<?= $this->section('receptionistContent') ?>

<div class="page-header">
    <div>
        <h4 class="page-title">Appointment Management</h4>
        <p class="page-subtitle">Manage all patient appointments</p>
    </div>
    <button class="btn-primary" onclick="window.location.href='<?= base_url('appointment/book') ?>'">
        <i class="bi bi-plus-circle me-2"></i>New Appointment
    </button>
</div>

<!-- Stats Cards -->
<div class="stats-row">
    <div class="stat-card">
        <div class="stat-icon teal">
            <i class="bi bi-calendar3"></i>
        </div>
        <div class="stat-info">
            <h3><?= $total ?? 0 ?></h3>
            <p>Total</p>
            <small>All appointments</small>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange">
            <i class="bi bi-clock-history"></i>
        </div>
        <div class="stat-info">
            <h3><?= $pending ?? 0 ?></h3>
            <p>Pending</p>
            <small>Awaiting approval</small>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue">
            <i class="bi bi-check-circle"></i>
        </div>
        <div class="stat-info">
            <h3><?= $approved ?? 0 ?></h3>
            <p>Approved</p>
            <small>Confirmed</small>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">
            <i class="bi bi-check-all"></i>
        </div>
        <div class="stat-info">
            <h3><?= $completed ?? 0 ?></h3>
            <p>Completed</p>
            <small>Visits done</small>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red">
            <i class="bi bi-x-circle"></i>
        </div>
        <div class="stat-info">
            <h3><?= $cancelled ?? 0 ?></h3>
            <p>Cancelled</p>
            <small>Cancelled visits</small>
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
        <table class="table appointments-table" id="appointmentsTable">
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
                            <td class="ref-cell"><code><?= esc($appointment['reference_number']) ?></code></td>
                            <td>
                                <div class="patient-cell">
                                    <span class="patient-avatar">
                                        <?php 
                                            $nameParts = preg_split('/\s+/', trim($appointment['full_name']));
                                            $initials = strtoupper(mb_substr($nameParts[0] ?? '', 0, 1) . mb_substr($nameParts[count($nameParts) - 1] ?? '', 0, 1));
                                            echo esc($initials);
                                        ?>
                                    </span>
                                    <div class="patient-meta">
                                        <span class="patient-name"><?= esc($appointment['full_name']) ?></span>
                                        <small><?= date('M d, Y', strtotime($appointment['appointment_date'])) ?></small>
                                    </div>
                                </div>
                            </td>
                            <td><?= date('M d, Y', strtotime($appointment['appointment_date'])) ?></td>
                            <td><?= esc($appointment['appointment_time']) ?></td>
                            <td><span class="service-tag"><?= ucfirst(esc($appointment['service_type'])) ?></span></td>
                            <td>
                                <span class="status-badge <?= $appointment['status'] ?>">
                                    <i class="bi bi-circle-fill"></i>
                                    <?= ucfirst($appointment['status']) ?>
                                </span>
                            </td>
                            <td class="action-cell">
                                <a href="<?= base_url('receptionist/appointment/view/' . $appointment['id']) ?>" 
                                   class="action-icon-btn view" title="View Details">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <?php if ($appointment['status'] == 'pending'): ?>
                                    <a href="<?= base_url('receptionist/appointment/approve/' . $appointment['id']) ?>" 
                                       class="action-icon-btn approve" title="Approve"
                                       onclick="return confirm('Approve this appointment?')">
                                        <i class="bi bi-check2"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if ($appointment['status'] == 'approved'): ?>
                                    <a href="<?= base_url('receptionist/appointment/complete/' . $appointment['id']) ?>" 
                                       class="action-icon-btn complete" title="Complete"
                                       onclick="return confirm('Mark this appointment as completed?')">
                                        <i class="bi bi-check-all"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if (!in_array($appointment['status'], ['completed', 'cancelled'])): ?>
                                    <a href="<?= base_url('receptionist/appointment/cancel/' . $appointment['id']) ?>" 
                                       class="action-icon-btn cancel" title="Cancel"
                                       onclick="return confirm('Cancel this appointment?')">
                                        <i class="bi bi-x"></i>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7">
                            <div class="empty-state">
                                <i class="bi bi-calendar-x"></i>
                                <p>No appointments found</p>
                                <small>Try adjusting your search or filters</small>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
/* ============================================
   APPOINTMENTS - CONSISTENT UI
   ============================================ */

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    gap: 1rem;
    flex-wrap: wrap;
}

.page-title {
    font-weight: 800;
    color: var(--ink, #101828);
    margin: 0;
    font-size: 1.4rem;
    letter-spacing: -0.02em;
}

.page-subtitle {
    color: var(--ink-soft, #64748B);
    font-size: 0.85rem;
    margin: 0.15rem 0 0;
    font-weight: 400;
}

.btn-primary {
    background: #0d9488;
    color: white;
    border: none;
    padding: 0.6rem 1.2rem;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-primary:hover {
    background: #0f766e;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(13, 148, 136, 0.2);
}

/* Stats Row */
.stats-row {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.stat-card {
    background: #ffffff;
    border-radius: 14px;
    padding: 1.25rem;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 0.5rem;
    box-shadow: 0 1px 3px rgba(16, 24, 40, 0.06);
    border: 1px solid #E5E9ED;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    min-height: 120px;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(16, 24, 40, 0.08);
}

.stat-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
}

.stat-icon.teal { background: #E0F2F4; color: #0d9488; }
.stat-icon.orange { background: #FFF1E6; color: #C2410C; }
.stat-icon.blue { background: #E8EFFE; color: #1D4ED8; }
.stat-icon.green { background: #E7F6EC; color: #15803D; }
.stat-icon.red { background: #FEF2F2; color: #dc2626; }

.stat-info h3 {
    font-size: 1.5rem;
    font-weight: 800;
    color: #101828;
    margin: 0;
    line-height: 1.1;
}

.stat-info p {
    font-size: 0.85rem;
    font-weight: 600;
    color: #101828;
    margin: 0.2rem 0 0;
}

.stat-info small {
    font-size: 0.7rem;
    color: #64748B;
    font-weight: 400;
}

/* Table Card */
.table-card {
    background: #ffffff;
    border-radius: 16px;
    padding: 1.5rem;
    box-shadow: 0 1px 3px rgba(16, 24, 40, 0.06);
    border: 1px solid #E5E9ED;
}

.table-toolbar {
    display: flex;
    gap: 1rem;
    margin-bottom: 1.25rem;
    flex-wrap: wrap;
    align-items: center;
}

.search-wrapper {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    border: 1px solid #E5E9ED;
    border-radius: 10px;
    padding: 0.45rem 0.85rem;
    flex: 1;
    min-width: 200px;
    transition: all 0.2s ease;
    background: #F8FAFB;
}

.search-wrapper:focus-within {
    border-color: #0d9488;
    box-shadow: 0 0 0 4px rgba(13, 148, 136, 0.08);
}

.search-wrapper i {
    color: #94A3B8;
}

.search-wrapper .form-control {
    border: none;
    padding: 0;
    font-size: 0.85rem;
    background: transparent;
    color: #101828;
}

.search-wrapper .form-control::placeholder {
    color: #94A3B8;
}

.search-wrapper .form-control:focus {
    box-shadow: none;
}

.filter-wrapper {
    min-width: 150px;
}

.filter-wrapper .form-select {
    border: 1px solid #E5E9ED;
    border-radius: 10px;
    font-size: 0.85rem;
    padding: 0.45rem 0.75rem;
    color: #101828;
    background-color: #F8FAFB;
    height: 42px;
}

.filter-wrapper .form-select:focus {
    border-color: #0d9488;
    box-shadow: 0 0 0 4px rgba(13, 148, 136, 0.08);
}

/* Table */
.appointments-table {
    margin: 0;
    border-collapse: separate;
    border-spacing: 0 8px;
}

.appointments-table thead th {
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #94A3B8;
    font-weight: 600;
    border: none;
    padding: 0.75rem 1rem;
    background: transparent;
    white-space: nowrap;
}

.appointments-table tbody tr {
    background: #ffffff;
    border-radius: 12px;
    transition: all 0.2s ease;
    box-shadow: 0 1px 3px rgba(0,0,0,0.03);
    border: 1px solid #E5E9ED;
}

.appointments-table tbody tr:hover {
    background: #F8FAFB;
    box-shadow: 0 4px 12px rgba(16, 24, 40, 0.08);
    transform: translateY(-1px);
}

.appointments-table tbody td {
    padding: 0.85rem 1rem;
    vertical-align: middle;
    font-size: 0.85rem;
    color: #101828;
    border: none;
    white-space: nowrap;
    background: transparent;
}

.appointments-table tbody td:first-child {
    border-radius: 12px 0 0 12px;
}

.appointments-table tbody td:last-child {
    border-radius: 0 12px 12px 0;
}

/* Ref Cell */
.ref-cell code {
    background: #F8FAFB;
    padding: 0.2rem 0.5rem;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 600;
    color: #0d9488;
}

/* Patient Cell */
.patient-cell {
    display: flex;
    align-items: center;
    gap: 0.6rem;
}

.patient-avatar {
    width: 32px;
    height: 32px;
    border-radius: 10px;
    background: #E0F2F4;
    color: #0d9488;
    font-size: 0.65rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.patient-meta {
    display: flex;
    flex-direction: column;
    min-width: 0;
}

.patient-name {
    font-weight: 600;
    color: #101828;
}

.patient-meta small {
    font-size: 0.7rem;
    color: #64748B;
    margin-top: 1px;
}

/* Service Tag */
.service-tag {
    background: #E8EFFE;
    color: #1D4ED8;
    padding: 0.2rem 0.6rem;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 600;
}

/* Status Badge */
.status-badge {
    padding: 0.3rem 0.8rem;
    border-radius: 30px;
    font-size: 0.7rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
}

.status-badge .bi-circle-fill {
    font-size: 0.4rem;
}

.status-badge.pending {
    background: #FFF1E6;
    color: #C2410C;
}

.status-badge.approved {
    background: #E8EFFE;
    color: #1D4ED8;
}

.status-badge.completed {
    background: #E7F6EC;
    color: #15803D;
}

.status-badge.cancelled {
    background: #FEF2F2;
    color: #dc2626;
}

/* Action Buttons */
.action-cell {
    display: flex;
    gap: 0.4rem;
    align-items: center;
}

.action-icon-btn {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
    text-decoration: none;
    transition: all 0.2s ease;
    border: 1px solid transparent;
}

.action-icon-btn.view {
    background: #E8EFFE;
    color: #1D4ED8;
    border-color: #bfdbfe;
}

.action-icon-btn.view:hover {
    background: #1D4ED8;
    color: #ffffff;
    border-color: #1D4ED8;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(29, 78, 216, 0.2);
}

.action-icon-btn.approve {
    background: #E7F6EC;
    color: #15803D;
    border-color: #bbf7d0;
}

.action-icon-btn.approve:hover {
    background: #15803D;
    color: #ffffff;
    border-color: #15803D;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(22, 163, 74, 0.2);
}

.action-icon-btn.complete {
    background: #E0F2F4;
    color: #0d9488;
    border-color: #99f6e4;
}

.action-icon-btn.complete:hover {
    background: #0d9488;
    color: #ffffff;
    border-color: #0d9488;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(13, 148, 136, 0.2);
}

.action-icon-btn.cancel {
    background: #FEF2F2;
    color: #dc2626;
    border-color: #fecaca;
}

.action-icon-btn.cancel:hover {
    background: #dc2626;
    color: #ffffff;
    border-color: #dc2626;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(220, 38, 38, 0.2);
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 2.5rem 1rem;
}

.empty-state i {
    font-size: 2.5rem;
    color: #94A3B8;
    display: block;
    margin-bottom: 0.75rem;
}

.empty-state p {
    color: #101828;
    font-weight: 600;
    margin: 0;
}

.empty-state small {
    color: #64748B;
}

/* Responsive */
@media (max-width: 1400px) {
    .stats-row {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 992px) {
    .stats-row {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        gap: 0.75rem;
        align-items: stretch;
    }
    
    .stats-row {
        grid-template-columns: repeat(2, 1fr);
        gap: 0.75rem;
    }
    
    .table-toolbar {
        flex-direction: column;
    }
    
    .search-wrapper,
    .filter-wrapper {
        min-width: 100%;
    }
    
    .appointments-table {
        border-spacing: 0 6px;
    }
}

@media (max-width: 576px) {
    .stats-row {
        grid-template-columns: 1fr;
    }
    
    .appointments-table {
        font-size: 0.75rem;
    }
    
    .appointments-table thead th,
    .appointments-table tbody td {
        padding: 0.5rem;
        font-size: 0.75rem;
    }
    
    .patient-avatar {
        width: 28px;
        height: 28px;
        font-size: 0.55rem;
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