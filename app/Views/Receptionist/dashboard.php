<?= $this->extend('layouts/ReceptionistLayout') ?>

<?= $this->section('pageTitle') ?>Dashboard<?= $this->endSection() ?>

<?= $this->section('receptionistContent') ?>

<div class="dashboard-container">

    <!-- Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon teal">
                <i class="bi bi-calendar-check"></i>
            </div>
            <div class="stat-info">
                <h3><?= isset($today_appointments) ? count($today_appointments) : 0 ?></h3>
                <p>Today's Appointments</p>
                <span class="trend up"><i class="bi bi-arrow-up"></i> 8.5%</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon orange">
                <i class="bi bi-clock-history"></i>
            </div>
            <div class="stat-info">
                <h3><?= $pending_appointments ?? 0 ?></h3>
                <p>Pending Appointments</p>
                <span class="trend down"><i class="bi bi-arrow-down"></i> 4.2%</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green">
                <i class="bi bi-person-check"></i>
            </div>
            <div class="stat-info">
                <h3><?= $today_completed ?? 0 ?></h3>
                <p>Completed Today</p>
                <span class="trend up"><i class="bi bi-arrow-up"></i> 6.8%</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon purple">
                <i class="bi bi-file-medical"></i>
            </div>
            <div class="stat-info">
                <h3><?= $pending_diagnostic ?? 0 ?></h3>
                <p>Pending Diagnostics</p>
                <span class="trend up"><i class="bi bi-arrow-up"></i> 2.1%</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon danger">
                <i class="bi bi-exclamation-triangle"></i>
            </div>
            <div class="stat-info">
                <h3><?= $unpaid_bills ?? 0 ?></h3>
                <p>Unpaid Bills</p>
                <span class="trend down"><i class="bi bi-arrow-down"></i> 5.3%</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon success">
                <i class="bi bi-cash-stack"></i>
            </div>
            <div class="stat-info">
                <h3>₱<?= number_format($today_collections ?? 0, 2) ?></h3>
                <p>Today's Collections</p>
                <span class="trend up"><i class="bi bi-arrow-up"></i> 12.5%</span>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions">
        <div class="quick-actions-header">
            <h5><i class="bi bi-lightning-fill"></i> Quick Actions</h5>
            <span class="badge-year">Receptionist</span>
        </div>
        <div class="action-grid">
            <a href="<?= base_url('receptionist/appointments') ?>" class="action-btn">
                <i class="bi bi-plus-circle"></i>
                <span>New Appointment</span>
                <small>Schedule a patient</small>
            </a>
            <a href="#" class="action-btn" onclick="alert('Patient Registration form coming soon!')">
                <i class="bi bi-person-plus"></i>
                <span>Register Patient</span>
                <small>Add new patient</small>
            </a>
            <a href="<?= base_url('receptionist/patients') ?>" class="action-btn">
                <i class="bi bi-people"></i>
                <span>Patient List</span>
                <small>View all patients</small>
            </a>
            <a href="<?= base_url('receptionist/billing') ?>" class="action-btn">
                <i class="bi bi-receipt"></i>
                <span>Billing</span>
                <small>Manage bills</small>
            </a>
        </div>
    </div>

    <!-- Today's Appointments -->
    <div class="appointments-card">
        <div class="card-header-custom">
            <h5><i class="bi bi-calendar3"></i> Today's Appointments</h5>
            <div class="header-right-group">
                <span class="badge-custom"><?= date('F d, Y') ?></span>
                <a href="<?= base_url('receptionist/appointments') ?>" class="btn-view-all">
                    View All <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table receptionist-table" id="todayAppointmentsTable">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Patient</th>
                        <th>Service</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (isset($today_appointments) && count($today_appointments) > 0): ?>
                        <?php foreach ($today_appointments as $appointment): ?>
                            <tr>
                                <td><strong><?= esc($appointment['appointment_time']) ?></strong></td>
                                <td>
                                    <div class="patient-cell">
                                        <span><?= esc($appointment['full_name']) ?></span>
                                        <small><?= esc($appointment['age']) ?> yrs · <?= esc($appointment['gender']) ?></small>
                                    </div>
                                </td>
                                <td><?= ucfirst(esc($appointment['service_type'])) ?></td>
                                <td>
                                    <span class="status-badge <?= $appointment['status'] ?>">
                                        <i class="bi bi-circle-fill"></i>
                                        <?= ucfirst($appointment['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="<?= base_url('receptionist/appointment/view/' . $appointment['id']) ?>" 
                                           class="btn-action view" title="View Details">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <?php if ($appointment['status'] == 'pending'): ?>
                                            <a href="<?= base_url('receptionist/appointment/approve/' . $appointment['id']) ?>" 
                                               class="btn-action approve" title="Approve"
                                               onclick="return confirm('Approve this appointment?')">
                                                <i class="bi bi-check2"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if ($appointment['status'] == 'approved'): ?>
                                            <a href="<?= base_url('receptionist/appointment/complete/' . $appointment['id']) ?>" 
                                               class="btn-action complete" title="Complete"
                                               onclick="return confirm('Mark this appointment as completed?')">
                                                <i class="bi bi-check-all"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if ($appointment['status'] == 'pending' || $appointment['status'] == 'approved'): ?>
                                            <a href="<?= base_url('receptionist/appointment/cancel/' . $appointment['id']) ?>" 
                                               class="btn-action cancel" title="Cancel"
                                               onclick="return confirm('Cancel this appointment?')">
                                                <i class="bi bi-x"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5">
                                <div class="empty-state">
                                    <i class="bi bi-calendar-x"></i>
                                    <p>No appointments scheduled for today</p>
                                    <small>Check back later or schedule a new appointment</small>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<style>
/* ===== DASHBOARD CONTAINER ===== */
.dashboard-container {
    padding: 0;
}

/* ===== WELCOME BANNER ===== */
.welcome-banner {
    background: linear-gradient(135deg, #0d9488 0%, #0f766e 50%, #0d9488 100%);
    border-radius: 16px;
    padding: 1.75rem 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
    gap: 1rem;
    box-shadow: 0 4px 20px rgba(13, 148, 136, 0.25);
}

.welcome-text h1 {
    color: #fff;
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0;
}

.welcome-text p {
    color: rgba(255,255,255,0.85);
    margin: 0;
    font-size: 0.95rem;
}

.welcome-date {
    color: rgba(255,255,255,0.95);
    font-weight: 500;
    font-size: 0.95rem;
    background: rgba(255,255,255,0.15);
    padding: 0.5rem 1.25rem;
    border-radius: 30px;
    border: 1px solid rgba(255,255,255,0.15);
    white-space: nowrap;
}

.welcome-date i {
    margin-right: 0.5rem;
}

/* ===== STATS GRID ===== */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.stat-card {
    background: #ffffff;
    border-radius: 14px;
    padding: 1.25rem 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    box-shadow: 0 2px 12px rgba(13, 148, 136, 0.06);
    border: 1px solid rgba(13, 148, 136, 0.06);
    transition: all 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(13, 148, 136, 0.12);
}

.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    flex-shrink: 0;
}

.stat-icon.teal { background: #ccfbf1; color: #0d9488; }
.stat-icon.orange { background: #fff3e0; color: #ff6b00; }
.stat-icon.green { background: #e8f5e9; color: #28a745; }
.stat-icon.purple { background: #f3e5f5; color: #800080; }
.stat-icon.danger { background: #fce4ec; color: #dc3545; }
.stat-icon.success { background: #e8f5e9; color: #0d9488; }

.stat-info h3 {
    font-size: 1.4rem;
    font-weight: 700;
    color: #0a2b4e;
    margin: 0;
    line-height: 1.2;
}

.stat-info p {
    color: #64748b;
    font-size: 0.8rem;
    margin: 0;
    font-weight: 500;
}

.trend {
    font-size: 0.65rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 0.2rem;
    margin-top: 0.15rem;
}

.trend.up { color: #28a745; }
.trend.down { color: #dc3545; }

/* ===== QUICK ACTIONS ===== */
.quick-actions {
    background: #ffffff;
    border-radius: 14px;
    padding: 1.25rem 1.5rem;
    box-shadow: 0 2px 12px rgba(13, 148, 136, 0.06);
    border: 1px solid rgba(13, 148, 136, 0.06);
    margin-bottom: 1.5rem;
}

.quick-actions-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.quick-actions h5 {
    font-weight: 600;
    color: #0a2b4e;
    margin: 0;
    font-size: 0.95rem;
}

.quick-actions h5 i {
    color: #0d9488;
    margin-right: 0.5rem;
}

.badge-year {
    background: #ccfbf1;
    color: #0d9488;
    font-size: 0.7rem;
    font-weight: 600;
    padding: 0.25rem 0.75rem;
    border-radius: 30px;
    white-space: nowrap;
}

.action-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
}

.action-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 1.25rem;
    border-radius: 12px;
    background: #f8faff;
    border: 2px solid #e8edf5;
    color: #0a2b4e;
    text-decoration: none;
    transition: all 0.3s ease;
    gap: 0.3rem;
}

.action-btn:hover {
    border-color: #0d9488;
    background: #f0fdfa;
    color: #0d9488;
    text-decoration: none;
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(13, 148, 136, 0.1);
}

.action-btn i {
    font-size: 1.8rem;
    color: #0d9488;
}

.action-btn span {
    font-size: 0.85rem;
    font-weight: 600;
}

.action-btn small {
    font-size: 0.65rem;
    color: #94a3b8;
    font-weight: 400;
}

/* ===== APPOINTMENTS CARD ===== */
.appointments-card {
    background: #ffffff;
    border-radius: 14px;
    padding: 1.25rem 1.5rem;
    box-shadow: 0 2px 12px rgba(13, 148, 136, 0.06);
    border: 1px solid rgba(13, 148, 136, 0.06);
}

.card-header-custom {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.card-header-custom h5 {
    font-weight: 600;
    color: #0a2b4e;
    margin: 0;
    font-size: 0.95rem;
}

.card-header-custom h5 i {
    color: #0d9488;
    margin-right: 0.5rem;
}

.header-right-group {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.badge-custom {
    background: #ccfbf1;
    color: #0d9488;
    font-size: 0.7rem;
    font-weight: 600;
    padding: 0.25rem 0.75rem;
    border-radius: 30px;
}

.btn-view-all {
    font-size: 0.75rem;
    color: #0d9488;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-view-all:hover {
    color: #0f766e;
    text-decoration: underline;
}

/* ===== TABLE ===== */
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

.patient-cell {
    display: flex;
    flex-direction: column;
}

.patient-cell span {
    font-weight: 500;
}

.patient-cell small {
    font-size: 0.7rem;
    color: #94a3b8;
}

.status-badge {
    padding: 0.25rem 0.75rem;
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

.action-buttons {
    display: flex;
    gap: 0.3rem;
}

.btn-action {
    width: 28px;
    height: 28px;
    border-radius: 6px;
    border: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    transition: all 0.2s ease;
    font-size: 0.8rem;
}

.btn-action.view {
    background: #e6f0fa;
    color: #0148ca;
}

.btn-action.view:hover {
    background: #0148ca;
    color: #fff;
}

.btn-action.approve {
    background: #e8f5e9;
    color: #28a745;
}

.btn-action.approve:hover {
    background: #28a745;
    color: #fff;
}

.btn-action.complete {
    background: #ccfbf1;
    color: #0d9488;
}

.btn-action.complete:hover {
    background: #0d9488;
    color: #fff;
}

.btn-action.cancel {
    background: #fce4ec;
    color: #dc3545;
}

.btn-action.cancel:hover {
    background: #dc3545;
    color: #fff;
}

/* ===== EMPTY STATE ===== */
.empty-state {
    text-align: center;
    padding: 2rem;
}

.empty-state i {
    font-size: 2.5rem;
    color: #ccfbf1;
    display: block;
    margin-bottom: 0.5rem;
}

.empty-state p {
    color: #0a2b4e;
    font-weight: 500;
    margin: 0;
}

.empty-state small {
    color: #94a3b8;
}

/* ============================================
   RESPONSIVE
   ============================================ */

@media (max-width: 1200px) {
    .stats-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 992px) {
    .action-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 0.75rem;
    }
    
    .welcome-banner {
        flex-direction: column;
        align-items: flex-start;
        padding: 1.25rem;
    }
    
    .welcome-text h1 {
        font-size: 1.2rem;
    }
    
    .welcome-text p {
        font-size: 0.85rem;
    }
    
    .welcome-date {
        font-size: 0.8rem;
        padding: 0.4rem 1rem;
        white-space: normal;
    }
    
    .stat-card {
        padding: 1rem;
    }
    
    .stat-icon {
        width: 40px;
        height: 40px;
        font-size: 1rem;
    }
    
    .stat-info h3 {
        font-size: 1.1rem;
    }
    
    .action-grid {
        grid-template-columns: 1fr 1fr;
        gap: 0.75rem;
    }
    
    .action-btn {
        padding: 0.75rem;
    }
    
    .action-btn i {
        font-size: 1.4rem;
    }
    
    .action-btn span {
        font-size: 0.75rem;
    }
    
    .action-btn small {
        display: none;
    }
    
    .card-header-custom {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
    
    .header-right-group {
        width: 100%;
        justify-content: space-between;
    }
}

@media (max-width: 576px) {
    .stats-grid {
        grid-template-columns: 1fr 1fr;
        gap: 0.5rem;
    }
    
    .stat-card {
        padding: 0.75rem;
        flex-direction: column;
        text-align: center;
        gap: 0.5rem;
    }
    
    .stat-icon {
        width: 36px;
        height: 36px;
        font-size: 0.9rem;
    }
    
    .stat-info h3 {
        font-size: 1rem;
    }
    
    .stat-info p {
        font-size: 0.7rem;
    }
    
    .trend {
        font-size: 0.6rem;
    }
    
    .action-grid {
        grid-template-columns: 1fr 1fr;
        gap: 0.5rem;
    }
    
    .action-btn {
        padding: 0.6rem;
    }
    
    .action-btn i {
        font-size: 1.2rem;
    }
    
    .action-btn span {
        font-size: 0.7rem;
    }
    
    .receptionist-table {
        font-size: 0.75rem;
    }
    
    .receptionist-table thead th,
    .receptionist-table tbody td {
        padding: 0.4rem 0.25rem;
    }
}

@media (max-width: 400px) {
    .stats-grid {
        grid-template-columns: 1fr 1fr;
        gap: 0.4rem;
    }
    
    .stat-card {
        padding: 0.5rem;
    }
    
    .stat-icon {
        width: 30px;
        height: 30px;
        font-size: 0.7rem;
        border-radius: 8px;
    }
    
    .stat-info h3 {
        font-size: 0.85rem;
    }
    
    .stat-info p {
        font-size: 0.6rem;
    }
    
    .welcome-text h1 {
        font-size: 1rem;
    }
    
    .welcome-text p {
        font-size: 0.75rem;
    }
    
    .welcome-date {
        font-size: 0.65rem;
    }
    
    .action-grid {
        grid-template-columns: 1fr 1fr;
        gap: 0.4rem;
    }
    
    .action-btn {
        padding: 0.4rem;
    }
    
    .action-btn i {
        font-size: 1rem;
    }
    
    .action-btn span {
        font-size: 0.6rem;
    }
}
</style>

<?= $this->endSection() ?>