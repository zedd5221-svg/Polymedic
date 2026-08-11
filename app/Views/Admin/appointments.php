<?= $this->extend('layouts/AdminLayout') ?>

<?= $this->section('pageTitle') ?>Appointments<?= $this->endSection() ?>

<?= $this->section('adminContent') ?>

<!-- Stats Cards -->
<div class="stats-grid" style="margin-bottom: 1.5rem;">
    <div class="stat-card">
        <div class="stat-icon" style="background: #e6f0fa; color: #0148ca;">
            <i class="bi bi-calendar-check"></i>
        </div>
        <div class="stat-info">
            <h3><?= $total ?? 0 ?></h3>
            <p>Total</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background: #fff3e0; color: #ff6b00;">
            <i class="bi bi-clock-history"></i>
        </div>
        <div class="stat-info">
            <h3><?= $pending ?? 0 ?></h3>
            <p>Pending</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background: #e3f2fd; color: #0148ca;">
            <i class="bi bi-check-circle"></i>
        </div>
        <div class="stat-info">
            <h3><?= $approved ?? 0 ?></h3>
            <p>Approved</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background: #e8f5e9; color: #28a745;">
            <i class="bi bi-check2-circle"></i>
        </div>
        <div class="stat-info">
            <h3><?= $completed ?? 0 ?></h3>
            <p>Completed</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background: #fce4ec; color: #dc3545;">
            <i class="bi bi-x-circle"></i>
        </div>
        <div class="stat-info">
            <h3><?= $cancelled ?? 0 ?></h3>
            <p>Cancelled</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background: #f5f5f5; color: #6c757d;">
            <i class="bi bi-clock"></i>
        </div>
        <div class="stat-info">
            <h3><?= $late ?? 0 ?></h3>
            <p>Late</p>
        </div>
    </div>
</div>

<div class="table-card">
    <!-- Search Bar -->
    <div class="search-container">
        <div class="search-wrapper">
            <i class="bi bi-search"></i>
            <input type="text" class="form-control" placeholder="Search appointments..." id="searchAppointments">
        </div>
    </div>

    <!-- Status Filter Buttons -->
    <div class="filter-container">
        <div class="filter-buttons" id="statusFilters">
            <button class="filter-btn active" data-status="all">All</button>
            <button class="filter-btn" data-status="pending">Pending</button>
            <button class="filter-btn" data-status="approved">Approved</button>
            <button class="filter-btn" data-status="completed">Completed</button>
            <button class="filter-btn" data-status="cancelled">Cancelled</button>
            <button class="filter-btn" data-status="late">Late</button>
        </div>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle me-2"></i><?= session()->getFlashdata('success') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-circle me-2"></i><?= session()->getFlashdata('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <!-- Appointments Grid -->
    <div class="appointments-grid" id="appointmentsGrid">
        <?php if (!empty($appointments)): ?>
            <?php foreach ($appointments as $appt): ?>
                <div class="appointment-card" data-status="<?= $appt['status'] ?>">
                    <div class="appointment-header">
                        <div class="appointment-id-section">
                            <span class="appointment-id"><?= $appt['reference_number'] ?></span>
                            <?php
                                $statusClass = [
                                    'pending' => 'pending',
                                    'approved' => 'approved',
                                    'completed' => 'completed',
                                    'cancelled' => 'cancelled',
                                    'late' => 'late'
                                ];
                                $statusText = [
                                    'pending' => 'Pending',
                                    'approved' => 'Approved',
                                    'completed' => 'Completed',
                                    'cancelled' => 'Cancelled',
                                    'late' => 'Late'
                                ];
                            ?>
                            <span class="status-badge <?= $statusClass[$appt['status']] ?? 'pending' ?>">
                                <?= $statusText[$appt['status']] ?? ucfirst($appt['status']) ?>
                            </span>
                            <?php if ($appt['status'] == 'pending'): ?>
                                <span class="priority-badge stat">Waiting</span>
                            <?php elseif ($appt['status'] == 'late'): ?>
                                <span class="priority-badge late">Late</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="appointment-body">
                        <div class="appointment-patient">
                            <strong><?= $appt['full_name'] ?></strong>
                            <span><i class="bi bi-person"></i> <?= $appt['age'] ?> yrs, <?= $appt['gender'] ?></span>
                        </div>
                        <div class="appointment-date">
                            <i class="bi bi-calendar3"></i> <?= date('M d, Y', strtotime($appt['appointment_date'])) ?> at <?= date('h:i A', strtotime($appt['appointment_time'])) ?>
                        </div>
                        <div class="appointment-contact">
                            <span><i class="bi bi-envelope"></i> <?= $appt['email'] ?></span>
                            <span><i class="bi bi-phone"></i> <?= $appt['phone'] ?></span>
                        </div>
                        <div class="appointment-services">
                            <span class="service-tag"><?= ucfirst($appt['service_type'] ?? 'N/A') ?></span>
                            <?php 
                                $labServices = json_decode($appt['lab_services'], true) ?? [];
                                $xrayServices = json_decode($appt['xray_services'], true) ?? [];
                                $allServices = array_merge($labServices, $xrayServices);
                                if (!empty($allServices)):
                                    foreach (array_slice($allServices, 0, 2) as $service): 
                            ?>
                                <span class="service-tag"><?= $service ?></span>
                            <?php 
                                    endforeach; 
                                    if (count($allServices) > 2):
                            ?>
                                <span class="service-tag more">+<?= count($allServices) - 2 ?> more</span>
                            <?php 
                                    endif; 
                                endif; 
                            ?>
                        </div>
                    </div>
                    <div class="appointment-footer">
                        <!-- View Details Button (Always visible) -->
                        <a href="/polymedic/public/admin/appointment/view/<?= $appt['id'] ?>" 
                           class="btn-view">
                            <i class="bi bi-eye"></i> View
                        </a>
                        
                        <!-- Approve Button (Pending or Late only) -->
                        <?php if ($appt['status'] == 'pending' || $appt['status'] == 'late'): ?>
                            <a href="/polymedic/public/admin/appointment/approve/<?= $appt['id'] ?>" 
                               class="btn-approve" 
                               onclick="return confirm('Approve this appointment?')">
                                <i class="bi bi-check-circle"></i> Approve
                            </a>
                        <?php endif; ?>
                        
                        <!-- Complete Button (Approved only) -->
                        <?php if ($appt['status'] == 'approved'): ?>
                            <a href="/polymedic/public/admin/appointment/complete/<?= $appt['id'] ?>" 
                               class="btn-complete" 
                               onclick="return confirm('Mark this appointment as completed?')">
                                <i class="bi bi-check2-circle"></i> Complete
                            </a>
                        <?php endif; ?>
                        
                        <!-- Cancel Button (Pending, Late, or Approved only) -->
                        <?php if ($appt['status'] != 'completed' && $appt['status'] != 'cancelled'): ?>
                            <a href="/polymedic/public/admin/appointment/cancel/<?= $appt['id'] ?>" 
                               class="btn-cancel" 
                               onclick="return confirm('Cancel this appointment?')">
                                <i class="bi bi-x-circle"></i> Cancel
                            </a>
                        <?php endif; ?>
                        
                        <!-- Delete Button (Always visible for all statuses) -->
                        <a href="/polymedic/public/admin/appointment/delete/<?= $appt['id'] ?>" 
                           class="btn-delete" 
                           onclick="return confirmDelete('<?= $appt['reference_number'] ?>', '<?= addslashes($appt['full_name']) ?>')">
                            <i class="bi bi-trash3"></i> Delete
                        </a>
                        
                        <!-- Status Message (Completed or Cancelled only) -->
                        <?php if ($appt['status'] == 'completed'): ?>
                            <span class="status-message completed">
                                <i class="bi bi-check-circle-fill"></i> Completed
                            </span>
                        <?php elseif ($appt['status'] == 'cancelled'): ?>
                            <span class="status-message cancelled">
                                <i class="bi bi-x-circle-fill"></i> Cancelled
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-results">
                <i class="bi bi-inbox" style="font-size: 3rem; display: block; color: #ccc;"></i>
                <p class="text-muted mt-3">No appointments found</p>
                <p class="text-muted small">Book a test appointment at <a href="/polymedic/public/appointment/book">/appointment/book</a></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* ===== STATS GRID ===== */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.stat-card {
    background: #ffffff;
    border-radius: 12px;
    padding: 1rem 1.25rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    box-shadow: 0 2px 12px rgba(10, 43, 78, 0.06);
    border: 1px solid rgba(1, 72, 202, 0.04);
    transition: all 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(10, 43, 78, 0.1);
}

.stat-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
}

.stat-info h3 {
    font-size: 1.2rem;
    font-weight: 700;
    color: #0a2b4e;
    margin: 0;
    line-height: 1.2;
}

.stat-info p {
    color: #64748b;
    font-size: 0.7rem;
    margin: 0;
    font-weight: 500;
}

/* ===== TABLE CARD ===== */
.table-card {
    background: #ffffff;
    border-radius: 14px;
    padding: 1.5rem;
    box-shadow: 0 2px 12px rgba(10, 43, 78, 0.06);
    border: 1px solid rgba(1, 72, 202, 0.04);
}

/* ===== SEARCH ===== */
.search-container {
    margin-bottom: 1rem;
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

/* ===== FILTERS ===== */
.filter-container {
    margin-bottom: 1.5rem;
}

.filter-buttons {
    display: flex;
    gap: 0.25rem;
    flex-wrap: wrap;
}

.filter-btn {
    padding: 0.2rem 0.7rem;
    border: 1px solid #e2e8f0;
    border-radius: 20px;
    background: transparent;
    color: #64748b;
    font-size: 0.7rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.filter-btn:hover {
    border-color: #0148ca;
    color: #0148ca;
}

.filter-btn.active {
    background: #0148ca;
    border-color: #0148ca;
    color: white;
}

/* ===== APPOINTMENTS GRID ===== */
.appointments-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.appointment-card {
    background: #ffffff;
    border-radius: 12px;
    padding: 1.25rem;
    border: 1px solid #eef2f7;
    transition: all 0.3s ease;
}

.appointment-card.hidden {
    display: none !important;
}

.appointment-card:hover {
    box-shadow: 0 4px 12px rgba(10, 43, 78, 0.08);
}

/* ===== APPOINTMENT HEADER ===== */
.appointment-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 0.75rem;
}

.appointment-id-section {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    flex-wrap: wrap;
}

.appointment-id {
    font-weight: 700;
    color: #0a2b4e;
    font-size: 0.9rem;
}

.status-badge {
    padding: 0.15rem 0.6rem;
    border-radius: 30px;
    font-size: 0.6rem;
    font-weight: 600;
}

.status-badge.pending { background: #fff3e0; color: #ff6b00; }
.status-badge.approved { background: #e3f2fd; color: #0148ca; }
.status-badge.completed { background: #e8f5e9; color: #28a745; }
.status-badge.cancelled { background: #fce4ec; color: #dc3545; }
.status-badge.late { background: #f5f5f5; color: #6c757d; }

.priority-badge {
    padding: 0.1rem 0.5rem;
    border-radius: 30px;
    font-size: 0.55rem;
    font-weight: 700;
    text-transform: uppercase;
}

.priority-badge.stat { background: #fce4ec; color: #dc3545; }
.priority-badge.late { background: #fff3e0; color: #ff6b00; }

/* ===== APPOINTMENT BODY ===== */
.appointment-body {
    padding: 0.25rem 0;
}

.appointment-patient {
    display: flex;
    flex-direction: column;
}

.appointment-patient strong {
    font-size: 0.95rem;
    color: #0a2b4e;
}

.appointment-patient span {
    font-size: 0.75rem;
    color: #64748b;
}

.appointment-date {
    font-size: 0.8rem;
    color: #64748b;
    margin: 0.25rem 0;
}

.appointment-date i {
    margin-right: 0.3rem;
}

.appointment-contact {
    display: flex;
    gap: 1rem;
    font-size: 0.75rem;
    color: #64748b;
    margin: 0.25rem 0;
}

.appointment-contact i {
    margin-right: 0.2rem;
}

.appointment-services {
    display: flex;
    gap: 0.4rem;
    flex-wrap: wrap;
    margin-top: 0.5rem;
}

.service-tag {
    background: #f0f7ff;
    border: 1px solid #dbeafe;
    padding: 0.15rem 0.6rem;
    border-radius: 30px;
    font-size: 0.7rem;
    color: #0148ca;
    font-weight: 500;
}

.service-tag.more {
    background: #f5f5f5;
    border-color: #e2e8f0;
    color: #64748b;
}

/* ===== APPOINTMENT FOOTER ===== */
.appointment-footer {
    display: flex;
    gap: 0.5rem;
    margin-top: 0.75rem;
    padding-top: 0.75rem;
    border-top: 1px solid #f0f4ff;
    flex-wrap: wrap;
    align-items: center;
}

.appointment-footer .btn-view,
.appointment-footer .btn-approve,
.appointment-footer .btn-complete,
.appointment-footer .btn-cancel,
.appointment-footer .btn-delete,
.appointment-footer .btn-print {
    background: transparent;
    border: 1px solid #e2e8f0;
    padding: 0.3rem 1rem;
    border-radius: 6px;
    font-size: 0.75rem;
    cursor: pointer;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    color: #64748b;
    text-decoration: none;
}

.appointment-footer .btn-view:hover { border-color: #0148ca; color: #0148ca; background: #f0f7ff; }
.appointment-footer .btn-approve:hover { border-color: #28a745; color: #28a745; background: #f0fdf4; }
.appointment-footer .btn-complete:hover { border-color: #17a2b8; color: #17a2b8; background: #f0f7ff; }
.appointment-footer .btn-cancel:hover { border-color: #dc3545; color: #dc3545; background: #fef2f2; }
.appointment-footer .btn-delete:hover { border-color: #dc3545; color: #dc3545; background: #fef2f2; }
.appointment-footer .btn-print:hover { border-color: #0148ca; color: #0148ca; background: #f0f7ff; }

.btn-delete {
    border-color: #fce4ec !important;
    color: #dc3545 !important;
}

.btn-delete:hover {
    background: #fce4ec !important;
    border-color: #dc3545 !important;
}

.status-message {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.8rem;
    font-weight: 600;
    padding: 0.3rem 1rem;
    border-radius: 6px;
}

.status-message.completed {
    color: #28a745;
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
}

.status-message.cancelled {
    color: #dc3545;
    background: #fef2f2;
    border: 1px solid #fecaca;
}

/* ===== NO RESULTS ===== */
.no-results {
    text-align: center;
    padding: 3rem 0;
    grid-column: 1 / -1;
}

/* ===== ALERT ===== */
.alert {
    border-radius: 10px;
    margin-bottom: 1rem;
    padding: 0.75rem 1rem;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 1200px) {
    .stats-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 992px) {
    .appointments-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .search-wrapper {
        max-width: 100%;
    }
    
    .filter-buttons {
        justify-content: center;
    }
    
    .appointment-header {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .appointment-contact {
        flex-direction: column;
        gap: 0.25rem;
    }
    
    .appointment-footer {
        flex-direction: column;
    }
    
    .appointment-footer .btn-view,
    .appointment-footer .btn-approve,
    .appointment-footer .btn-complete,
    .appointment-footer .btn-cancel,
    .appointment-footer .btn-delete,
    .appointment-footer .btn-print {
        width: 100%;
        justify-content: center;
    }
}

@media (max-width: 480px) {
    .stats-grid {
        grid-template-columns: 1fr 1fr;
        gap: 0.5rem;
    }
    
    .stat-card {
        padding: 0.5rem 0.75rem;
        flex-direction: column;
        text-align: center;
    }
    
    .stat-icon {
        width: 30px;
        height: 30px;
        font-size: 0.8rem;
    }
    
    .stat-info h3 {
        font-size: 0.9rem;
    }
    
    .stat-info p {
        font-size: 0.6rem;
    }
    
    .appointment-card {
        padding: 0.75rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Search functionality
    document.getElementById('searchAppointments').addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const cards = document.querySelectorAll('.appointment-card');
        
        cards.forEach(card => {
            const text = card.textContent.toLowerCase();
            card.classList.toggle('hidden', !text.includes(searchTerm));
        });
    });
    
    // Filter functionality
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            const status = this.dataset.status;
            const cards = document.querySelectorAll('.appointment-card');
            
            cards.forEach(card => {
                if (status === 'all' || card.dataset.status === status) {
                    card.classList.remove('hidden');
                } else {
                    card.classList.add('hidden');
                }
            });
        });
    });
});

// Delete confirmation with details
function confirmDelete(reference, patientName) {
    return confirm(
        '⚠️ PERMANENT DELETE\n\n' +
        'Are you sure you want to permanently delete this appointment?\n\n' +
        'Reference: ' + reference + '\n' +
        'Patient: ' + patientName + '\n\n' +
        'This action CANNOT be undone!'
    );
}
</script>

<?= $this->endSection() ?>