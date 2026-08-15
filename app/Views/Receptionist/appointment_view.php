<?= $this->extend('layouts/ReceptionistLayout') ?>

<?= $this->section('pageTitle') ?>Appointment Details<?= $this->endSection() ?>

<?= $this->section('receptionistContent') ?>

<div class="page-header">
    <div class="header-actions">
        <a href="<?= base_url('receptionist/appointments') ?>" class="btn-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to List
        </a>
        <a href="<?= base_url('receptionist/dashboard') ?>" class="btn-secondary">
            <i class="bi bi-grid-1x2 me-1"></i>Dashboard
        </a>
    </div>
</div>

<?php if (isset($appointment)): ?>

<!-- Status Banner -->
<div class="status-banner status-<?= $appointment['status'] ?>">
    <div class="status-banner-icon">
        <?php if ($appointment['status'] == 'pending'): ?>
            <i class="bi bi-clock-history"></i>
        <?php elseif ($appointment['status'] == 'approved'): ?>
            <i class="bi bi-check-circle"></i>
        <?php elseif ($appointment['status'] == 'completed'): ?>
            <i class="bi bi-check2-circle"></i>
        <?php elseif ($appointment['status'] == 'cancelled'): ?>
            <i class="bi bi-x-circle"></i>
        <?php elseif ($appointment['status'] == 'late'): ?>
            <i class="bi bi-exclamation-triangle"></i>
        <?php endif; ?>
    </div>
    <div>
        <div class="status-banner-title"><?= ucfirst($appointment['status']) ?></div>
        <div class="status-banner-subtitle">
            <?php if ($appointment['status'] == 'pending'): ?>
                This appointment is waiting for approval
            <?php elseif ($appointment['status'] == 'approved'): ?>
                This appointment has been approved and confirmed
            <?php elseif ($appointment['status'] == 'completed'): ?>
                This appointment has been completed successfully
            <?php elseif ($appointment['status'] == 'cancelled'): ?>
                This appointment has been cancelled
            <?php elseif ($appointment['status'] == 'late'): ?>
                Patient is 1+ hour late for their appointment
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Main Appointment Info -->
    <div class="col-lg-8">
        <!-- Patient Profile Card -->
        <div class="chart-card">
            <div class="chart-header">
                <h5><i class="bi bi-person-circle me-2"></i>Patient Information</h5>
                <span class="badge-year">Ref: <?= $appointment['reference_number'] ?></span>
            </div>
            <div class="chart-body">
                <div class="profile-row">
                    <div class="profile-avatar">
                        <?php 
                            $initials = '';
                            $nameParts = explode(' ', $appointment['full_name']);
                            foreach ($nameParts as $part) {
                                $initials .= strtoupper(substr($part, 0, 1));
                            }
                            $initials = substr($initials, 0, 2);
                        ?>
                        <span><?= $initials ?></span>
                    </div>
                    <div class="profile-info">
                        <h3><?= esc($appointment['full_name']) ?></h3>
                        <div class="profile-meta">
                            <span><i class="bi bi-gender-ambiguous"></i> <?= esc($appointment['gender']) ?></span>
                            <span><i class="bi bi-cake2"></i> <?= esc($appointment['age']) ?> years old</span>
                            <span><i class="bi bi-calendar3"></i> <?= date('M d, Y', strtotime($appointment['appointment_date'])) ?></span>
                            <span><i class="bi bi-clock"></i> <?= esc($appointment['appointment_time']) ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label"><i class="bi bi-envelope"></i> Email Address</span>
                        <span class="info-value"><?= esc($appointment['email']) ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label"><i class="bi bi-phone"></i> Phone Number</span>
                        <span class="info-value"><?= esc($appointment['phone']) ?></span>
                    </div>
                    <?php if ($appointment['arrival_time']): ?>
                    <div class="info-item">
                        <span class="info-label"><i class="bi bi-check-circle-fill text-success"></i> Arrival Time</span>
                        <span class="info-value"><?= date('M d, Y h:i A', strtotime($appointment['arrival_time'])) ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="info-item">
                        <span class="info-label"><i class="bi bi-calendar-plus"></i> Created At</span>
                        <span class="info-value"><?= date('M d, Y h:i A', strtotime($appointment['created_at'])) ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Services Card -->
        <div class="chart-card mt-3">
            <div class="chart-header">
                <h5><i class="bi bi-clipboard2-pulse me-2"></i>Selected Services</h5>
                <span class="badge-year"><?= ucfirst($appointment['service_type']) ?></span>
            </div>
            <div class="chart-body">
                <?php if (!empty($lab_services) || !empty($xray_services)): ?>
                    <div class="row g-2">
                        <?php if (!empty($lab_services)): ?>
                        <div class="col-md-6">
                            <h6 class="fw-bold text-teal"><i class="bi bi-droplet me-1"></i> Laboratory Tests</h6>
                            <ul class="service-list">
                                <?php foreach ($lab_services as $service): ?>
                                    <li><i class="bi bi-check-circle-fill text-teal me-1"></i> <?= esc($service) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($xray_services)): ?>
                        <div class="col-md-6">
                            <h6 class="fw-bold" style="color: #0a2b4e;"><i class="bi bi-x-ray me-1"></i> X-Ray Services</h6>
                            <ul class="service-list">
                                <?php foreach ($xray_services as $service): ?>
                                    <li><i class="bi bi-check-circle-fill text-teal me-1"></i> <?= esc($service) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-3">
                        <i class="bi bi-inbox text-muted" style="font-size: 2rem; display: block;"></i>
                        <p class="text-muted mt-2">No services selected</p>
                    </div>
                <?php endif; ?>
                
                <?php if ($appointment['other_requests']): ?>
                    <div class="mt-3 p-3 bg-light rounded-3">
                        <h6 class="fw-bold"><i class="bi bi-chat-quote me-1"></i> Other Requests</h6>
                        <p class="mb-0 text-muted"><?= esc($appointment['other_requests']) ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Actions Sidebar -->
    <div class="col-lg-4">
        <div class="chart-card">
            <div class="chart-header">
                <h5><i class="bi bi-gear me-2"></i>Actions</h5>
            </div>
            <div class="chart-body">
                <div class="action-buttons">
                    <?php if ($appointment['status'] == 'pending' || $appointment['status'] == 'late'): ?>
                        <a href="<?= base_url('receptionist/appointment/approve/' . $appointment['id']) ?>" 
                           class="btn btn-success btn-action w-100 mb-2" 
                           onclick="return confirm('Approve this appointment?')">
                            <i class="bi bi-check-circle me-2"></i>Approve
                        </a>
                        <a href="<?= base_url('receptionist/appointment/cancel/' . $appointment['id']) ?>" 
                           class="btn btn-danger btn-action w-100 mb-2" 
                           onclick="return confirm('Cancel this appointment?')">
                            <i class="bi bi-x-circle me-2"></i>Cancel
                        </a>
                    <?php elseif ($appointment['status'] == 'approved'): ?>
                        <a href="<?= base_url('receptionist/appointment/complete/' . $appointment['id']) ?>" 
                           class="btn btn-info btn-action w-100 mb-2" 
                           onclick="return confirm('Mark this appointment as completed?')">
                            <i class="bi bi-check2-circle me-2"></i>Complete
                        </a>
                        <a href="<?= base_url('receptionist/appointment/cancel/' . $appointment['id']) ?>" 
                           class="btn btn-danger btn-action w-100 mb-2" 
                           onclick="return confirm('Cancel this appointment?')">
                            <i class="bi bi-x-circle me-2"></i>Cancel
                        </a>
                    <?php elseif ($appointment['status'] == 'completed'): ?>
                        <div class="alert alert-success text-center">
                            <i class="bi bi-check-circle-fill me-2"></i>
                            This appointment has been completed
                        </div>
                    <?php elseif ($appointment['status'] == 'cancelled'): ?>
                        <div class="alert alert-danger text-center">
                            <i class="bi bi-x-circle-fill me-2"></i>
                            This appointment has been cancelled
                        </div>
                    <?php endif; ?>
                    
                    <hr>
                    
                    <div class="mt-2">
                        <h6 class="fw-bold"><i class="bi bi-clock-history me-1"></i> Timeline</h6>
                        <ul class="timeline">
                            <li>
                                <span class="timeline-dot bg-teal"></span>
                                <div>
                                    <strong>Created</strong>
                                    <span class="timeline-time"><?= date('M d, Y h:i A', strtotime($appointment['created_at'])) ?></span>
                                </div>
                            </li>
                            <?php if ($appointment['status'] == 'approved' || $appointment['status'] == 'completed'): ?>
                            <li>
                                <span class="timeline-dot bg-success"></span>
                                <div>
                                    <strong>Approved</strong>
                                    <span class="timeline-time"><?= $appointment['arrival_time'] ? date('M d, Y h:i A', strtotime($appointment['arrival_time'])) : 'N/A' ?></span>
                                </div>
                            </li>
                            <?php endif; ?>
                            <?php if ($appointment['status'] == 'completed'): ?>
                            <li>
                                <span class="timeline-dot bg-info"></span>
                                <div>
                                    <strong>Completed</strong>
                                    <span class="timeline-time"><?= date('M d, Y h:i A', strtotime($appointment['updated_at'])) ?></span>
                                </div>
                            </li>
                            <?php endif; ?>
                            <?php if ($appointment['status'] == 'cancelled'): ?>
                            <li>
                                <span class="timeline-dot bg-danger"></span>
                                <div>
                                    <strong>Cancelled</strong>
                                    <span class="timeline-time"><?= date('M d, Y h:i A', strtotime($appointment['updated_at'])) ?></span>
                                </div>
                            </li>
                            <?php endif; ?>
                            <?php if ($appointment['status'] == 'late'): ?>
                            <li>
                                <span class="timeline-dot bg-warning"></span>
                                <div>
                                    <strong>Marked as Late</strong>
                                    <span class="timeline-time"><?= date('M d, Y h:i A', strtotime($appointment['updated_at'])) ?></span>
                                </div>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="chart-card mt-3">
            <div class="chart-header">
                <h5><i class="bi bi-lightning-charge me-2"></i>Quick Actions</h5>
            </div>
            <div class="chart-body">
                <div class="quick-actions">
                    <a href="mailto:<?= esc($appointment['email']) ?>" class="action-btn">
                        <i class="bi bi-envelope"></i>
                        Send Email
                    </a>
                    <a href="tel:<?= esc($appointment['phone']) ?>" class="action-btn">
                        <i class="bi bi-telephone"></i>
                        Call Patient
                    </a>
                    <button onclick="window.print()" class="action-btn">
                        <i class="bi bi-printer"></i>
                        Print Details
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* ===== PAGE HEADER ===== */
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.header-actions {
    display: flex;
    gap: 0.75rem;
    align-items: center;
    flex-wrap: wrap;
}

/* ===== BACK BUTTON ===== */
.btn-secondary {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    color: #0a2b4e;
    padding: 0.5rem 1.2rem;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.85rem;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    text-decoration: none;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
}

.btn-secondary:hover {
    background: #f8faff;
    border-color: #0d9488;
    color: #0d9488;
    transform: translateX(-2px);
    box-shadow: 0 4px 12px rgba(13, 148, 136, 0.1);
    text-decoration: none;
}

.btn-secondary i {
    transition: transform 0.3s ease;
}

.btn-secondary:hover i {
    transform: translateX(-3px);
}

/* ===== STATUS BANNER ===== */
.status-banner {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem 1.5rem;
    border-radius: 12px;
    margin-bottom: 1.5rem;
    border-left: 4px solid;
    background: #ffffff;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}

.status-banner.status-pending {
    border-left-color: #ffc107;
    background: #fff8e1;
}

.status-banner.status-approved {
    border-left-color: #0d9488;
    background: #f0fdfa;
}

.status-banner.status-completed {
    border-left-color: #28a745;
    background: #e8f5e9;
}

.status-banner.status-cancelled {
    border-left-color: #dc3545;
    background: #fce4ec;
}

.status-banner.status-late {
    border-left-color: #ff6b00;
    background: #fff3e0;
}

.status-banner-icon {
    font-size: 1.8rem;
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: rgba(255,255,255,0.8);
}

.status-banner-title {
    font-size: 1rem;
    font-weight: 700;
    color: #0a2b4e;
}

.status-banner-subtitle {
    font-size: 0.85rem;
    color: #64748b;
}

/* ===== PROFILE ROW ===== */
.profile-row {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #f0f4ff;
    margin-bottom: 1rem;
}

.profile-avatar {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: linear-gradient(135deg, #0d9488, #0f766e);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    font-weight: 700;
    flex-shrink: 0;
}

.profile-info h3 {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 700;
    color: #0a2b4e;
}

.profile-meta {
    display: flex;
    gap: 1rem;
    font-size: 0.8rem;
    color: #64748b;
    margin-top: 0.2rem;
    flex-wrap: wrap;
}

.profile-meta i {
    color: #0d9488;
}

/* ===== INFO GRID ===== */
.info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.5rem;
}

.info-item {
    display: flex;
    flex-direction: column;
    padding: 0.5rem 0.75rem;
    background: #f8faff;
    border-radius: 8px;
    border: 1px solid #f0f4ff;
}

.info-label {
    font-size: 0.6rem;
    text-transform: uppercase;
    color: #94a3b8;
    font-weight: 600;
    letter-spacing: 0.5px;
}

.info-label i {
    margin-right: 0.3rem;
}

.info-value {
    font-size: 0.85rem;
    font-weight: 500;
    color: #0a2b4e;
    margin-top: 0.1rem;
}

/* ===== CHART CARD ===== */
.chart-card {
    background: #ffffff;
    border-radius: 14px;
    padding: 1.25rem 1.5rem;
    box-shadow: 0 2px 12px rgba(13, 148, 136, 0.06);
    border: 1px solid rgba(13, 148, 136, 0.06);
}

.chart-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.chart-header h5 {
    font-weight: 600;
    color: #0a2b4e;
    margin: 0;
    font-size: 0.95rem;
}

.chart-header h5 i {
    color: #0d9488;
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

/* ===== SERVICE LIST ===== */
.text-teal {
    color: #0d9488;
}

.service-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.service-list li {
    padding: 0.25rem 0;
    font-size: 0.9rem;
    color: #0a2b4e;
    display: flex;
    align-items: center;
    gap: 0.4rem;
}

.service-list li i.text-teal {
    color: #0d9488;
}

/* ===== ACTION BUTTONS ===== */
.btn-action {
    padding: 0.6rem 1rem;
    font-size: 0.85rem;
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.btn-action:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.btn-success {
    background: #0d9488;
    border: none;
    color: white;
}

.btn-success:hover {
    background: #0f766e;
    color: white;
}

.btn-danger {
    background: #dc3545;
    border: none;
    color: white;
}

.btn-danger:hover {
    background: #c82333;
    color: white;
}

.btn-info {
    background: #0d9488;
    border: none;
    color: white;
}

.btn-info:hover {
    background: #0f766e;
    color: white;
}

/* ===== TIMELINE ===== */
.timeline {
    list-style: none;
    padding: 0;
    margin: 0;
}

.timeline li {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.4rem 0;
    border-bottom: 1px solid #f0f4ff;
}

.timeline li:last-child {
    border-bottom: none;
}

.timeline-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    flex-shrink: 0;
    border: 2px solid rgba(255,255,255,0.8);
}

.timeline-dot.bg-teal { background: #0d9488; }
.timeline-dot.bg-success { background: #28a745; }
.timeline-dot.bg-info { background: #17a2b8; }
.timeline-dot.bg-danger { background: #dc3545; }
.timeline-dot.bg-warning { background: #ffc107; }

.timeline li div {
    display: flex;
    flex-direction: column;
    flex: 1;
}

.timeline li strong {
    font-size: 0.85rem;
    color: #0a2b4e;
}

.timeline-time {
    font-size: 0.7rem;
    color: #94a3b8;
}

/* ===== QUICK ACTIONS ===== */
.quick-actions {
    display: flex;
    flex-direction: column;
    gap: 0.4rem;
}

.action-btn {
    padding: 0.5rem 0.75rem;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    background: #fafcff;
    color: #0a2b4e;
    font-weight: 500;
    font-size: 0.85rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
    width: 100%;
    text-align: left;
    cursor: pointer;
    text-decoration: none;
}

.action-btn:hover {
    background: #f0fdfa;
    border-color: #0d9488;
    transform: translateX(4px);
    text-decoration: none;
    color: #0a2b4e;
}

.action-btn i {
    font-size: 1rem;
    color: #0d9488;
    width: 20px;
}

/* ===== ALERT ===== */
.alert {
    border-radius: 10px;
    padding: 0.75rem 1rem;
    font-weight: 500;
}

.alert-success {
    background: #e8f5e9;
    color: #28a745;
    border: 1px solid #c8e6c9;
}

.alert-danger {
    background: #fce4ec;
    color: #dc3545;
    border: 1px solid #f8d7da;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        align-items: stretch;
        gap: 0.75rem;
    }
    
    .header-actions {
        flex-direction: column;
    }
    
    .header-actions .btn {
        width: 100%;
        justify-content: center;
    }
    
    .info-grid {
        grid-template-columns: 1fr;
    }
    
    .status-banner {
        flex-direction: column;
        text-align: center;
        padding: 1rem;
    }
    
    .profile-row {
        flex-direction: column;
        text-align: center;
    }
    
    .profile-meta {
        justify-content: center;
        flex-wrap: wrap;
    }
}

@media (max-width: 480px) {
    .profile-avatar {
        width: 44px;
        height: 44px;
        font-size: 1rem;
    }
    
    .profile-info h3 {
        font-size: 1rem;
    }
    
    .page-title {
        font-size: 1.1rem;
    }
    
    .chart-card {
        padding: 0.75rem 1rem;
    }
}
</style>

<?php endif; ?>

<?= $this->endSection() ?>