<?= $this->extend('layouts/AdminLayout') ?>

<?= $this->section('pageTitle') ?>Notifications<?= $this->endSection() ?>

<?= $this->section('adminContent') ?>

<div class="notifications-wrapper">
    <!-- Header Stats Card -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div>
            <h4 class="fw-bold text-dark mb-1">Notification Center</h4>
            <p class="text-muted small mb-0">Manage system notifications and appointment alerts</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= base_url('admin/notifications/mark-all-read') ?>" class="btn btn-outline-primary btn-sm d-flex align-items-center gap-2">
                <i class="bi bi-check2-all"></i> Mark All as Read
            </a>
        </div>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show mb-4">
            <i class="bi bi-check-circle me-2"></i><?= session()->getFlashdata('success') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show mb-4">
            <i class="bi bi-exclamation-circle me-2"></i><?= session()->getFlashdata('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Filter Pills -->
    <div class="filter-bar mb-4">
        <div class="filter-pills d-flex gap-2 flex-wrap">
            <button class="filter-pill active" data-filter="all">All (<?= $total_count ?>)</button>
            <button class="filter-pill" data-filter="unread">Unread (<?= $unread_count ?>)</button>
            <button class="filter-pill" data-filter="read">Read (<?= $total_count - $unread_count ?>)</button>
        </div>
    </div>

    <!-- Notification List Card -->
    <div class="table-card">
        <?php if (!empty($notifications)): ?>
            <div class="notification-list">
                <?php foreach ($notifications as $notif): ?>
                    <div class="notif-card <?= $notif['is_read'] == 0 ? 'unread' : 'read' ?>" data-read-status="<?= $notif['is_read'] == 0 ? 'unread' : 'read' ?>">
                        <div class="notif-card-icon <?= $notif['type'] ?>">
                            <i class="bi <?= $notif['type'] == 'appointment' ? 'bi-calendar-check' : 'bi-bell-fill' ?>"></i>
                        </div>
                        <div class="notif-card-content">
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <h6 class="notif-card-title mb-0"><?= esc($notif['title']) ?></h6>
                                <span class="notif-card-time"><i class="bi bi-clock me-1"></i><?= date('M d, Y · h:i A', strtotime($notif['created_at'])) ?></span>
                            </div>
                            <p class="notif-card-desc mb-2"><?= esc($notif['message']) ?></p>
                            <div class="notif-card-actions d-flex align-items-center gap-3">
                                <?php if ($notif['link']): ?>
                                    <a href="<?= base_url('admin/notifications/mark-read/' . $notif['id']) ?>" class="btn btn-sm btn-primary-light">
                                        <i class="bi bi-arrow-right-circle me-1"></i> View Details
                                    </a>
                                <?php endif; ?>
                                <?php if ($notif['is_read'] == 0): ?>
                                    <a href="<?= base_url('admin/notifications/mark-read/' . $notif['id']) ?>" class="text-secondary small text-decoration-none">
                                        <i class="bi bi-check-lg me-1"></i> Mark Read
                                    </a>
                                <?php endif; ?>
                                <a href="<?= base_url('admin/notifications/delete/' . $notif['id']) ?>" class="text-danger small text-decoration-none ms-auto" onclick="return confirm('Delete this notification?')">
                                    <i class="bi bi-trash me-1"></i> Delete
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-bell-slash text-muted" style="font-size: 3rem;"></i>
                <h5 class="mt-3 text-secondary">No Notifications Found</h5>
                <p class="text-muted small">New system alerts and appointment requests will appear here.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* ===== ADMIN THEME COLORS ===== */
:root {
    --admin-primary: #0148ca;
    --admin-primary-light: #e0edff;
    --admin-primary-bg: #f0f7ff;
}

.table-card {
    background: #ffffff;
    border-radius: 14px;
    padding: 1.5rem;
    box-shadow: 0 2px 12px rgba(1, 72, 202, 0.06);
    border: 1px solid rgba(1, 72, 202, 0.06);
}

/* ===== FILTER PILLS ===== */
.filter-pill {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    padding: 0.4rem 1rem;
    border-radius: 30px;
    font-size: 0.82rem;
    color: #64748b;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
}

.filter-pill.active {
    background: #0148ca;
    color: white;
    border-color: #0148ca;
}

.filter-pill:hover:not(.active) {
    background: #f0f7ff;
    border-color: #0148ca;
    color: #0148ca;
}

/* ===== NOTIFICATION LIST ===== */
.notification-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.notif-card {
    display: flex;
    gap: 1rem;
    padding: 1.2rem;
    border-radius: 12px;
    border: 1px solid #eef2f7;
    background: #ffffff;
    transition: all 0.2s ease;
}

.notif-card.unread {
    background: #f8fafc;
    border-left: 4px solid #0148ca;
}

.notif-card.read {
    opacity: 0.85;
}

.notif-card:hover {
    box-shadow: 0 4px 12px rgba(1, 72, 202, 0.08);
}

/* ===== NOTIFICATION ICON ===== */
.notif-card-icon {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    flex-shrink: 0;
}

.notif-card-icon.appointment {
    background: #e0edff;
    color: #0148ca;
}

.notif-card-icon.system {
    background: #fef3c7;
    color: #d97706;
}

/* ===== NOTIFICATION CONTENT ===== */
.notif-card-content {
    flex: 1;
    min-width: 0;
}

.notif-card-title {
    font-size: 0.95rem;
    font-weight: 700;
    color: #0f172a;
}

.notif-card-time {
    font-size: 0.75rem;
    color: #94a3b8;
    white-space: nowrap;
}

.notif-card-desc {
    font-size: 0.85rem;
    color: #475569;
}

/* ===== BUTTONS ===== */
.btn-outline-primary {
    background: transparent;
    border: 1px solid #0148ca;
    color: #0148ca;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-outline-primary:hover {
    background: #0148ca;
    color: white;
}

.btn-primary-light {
    background: #e0edff;
    color: #0148ca;
    font-weight: 600;
    border: none;
    font-size: 0.78rem;
    padding: 0.35rem 0.85rem;
    border-radius: 6px;
    transition: all 0.3s ease;
}

.btn-primary-light:hover {
    background: #0148ca;
    color: white;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 768px) {
    .d-flex.justify-content-between {
        flex-direction: column;
        align-items: stretch !important;
        gap: 0.75rem;
    }
    
    .d-flex.justify-content-between .d-flex.gap-2 {
        justify-content: stretch;
    }
    
    .d-flex.justify-content-between .d-flex.gap-2 a {
        flex: 1;
        text-align: center;
    }
    
    .notif-card {
        flex-direction: column;
        align-items: flex-start;
        padding: 1rem;
    }
    
    .notif-card-icon {
        width: 36px;
        height: 36px;
        font-size: 1rem;
    }
    
    .notif-card-title {
        font-size: 0.85rem;
    }
    
    .notif-card-time {
        font-size: 0.65rem;
        white-space: normal;
    }
    
    .notif-card-desc {
        font-size: 0.8rem;
    }
    
    .notif-card-actions {
        flex-wrap: wrap;
        gap: 0.5rem;
        width: 100%;
    }
    
    .notif-card-actions .ms-auto {
        margin-left: 0 !important;
    }
    
    .notif-card-actions a {
        font-size: 0.7rem !important;
        padding: 0.25rem 0.6rem !important;
    }
    
    .filter-pills {
        gap: 0.5rem;
    }
    
    .filter-pill {
        font-size: 0.7rem;
        padding: 0.25rem 0.6rem;
    }
}

@media (max-width: 480px) {
    .table-card {
        padding: 0.75rem;
    }
    
    .notif-card {
        padding: 0.75rem;
    }
    
    .notif-card-content .d-flex {
        flex-direction: column;
        align-items: flex-start !important;
        gap: 0.25rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Filter functionality
    document.querySelectorAll('.filter-pill').forEach(pill => {
        pill.addEventListener('click', function() {
            document.querySelectorAll('.filter-pill').forEach(p => p.classList.remove('active'));
            this.classList.add('active');
            const filter = this.dataset.filter;

            document.querySelectorAll('.notif-card').forEach(card => {
                if (filter === 'all' || card.dataset.readStatus === filter) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });
});
</script>

<?= $this->endSection() ?>