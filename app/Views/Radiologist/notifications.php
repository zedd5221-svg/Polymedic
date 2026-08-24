<?= $this->extend('layouts/RadiologistLayout') ?>

<?= $this->section('pageTitle') ?>Notifications<?= $this->endSection() ?>

<?= $this->section('radiologistContent') ?>

<div class="notifications-container">
    <div class="page-header">
        <div>
            <h4 class="page-title">Notifications</h4>
            <p class="page-subtitle">View all your notifications</p>
        </div>
        <div>
            <?php if ($unreadCount > 0): ?>
                <a href="<?= base_url('radiologist/notifications/mark-all-read') ?>" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-check-all"></i> Mark all read
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="notifications-list">
        <?php if (!empty($notifications)): ?>
            <?php foreach ($notifications as $notification): ?>
                <div class="notification-item <?= $notification['is_read'] == 0 ? 'unread' : '' ?>">
                    <div class="notification-icon">
                        <i class="bi <?= $notification['type'] === 'xray' ? 'bi-x-ray' : 'bi-bell-fill' ?>"></i>
                    </div>
                    <div class="notification-content">
                        <h6><?= esc($notification['title']) ?></h6>
                        <p><?= esc($notification['message']) ?></p>
                        <small><?= date('M d, Y h:i A', strtotime($notification['created_at'])) ?></small>
                    </div>
                    <?php if ($notification['is_read'] == 0): ?>
                        <a href="<?= base_url('radiologist/notifications/mark-read/' . $notification['id']) ?>" class="btn btn-sm btn-outline-secondary">
                            Mark as read
                        </a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="bi bi-bell-slash"></i>
                <p>No notifications</p>
                <small>You're all caught up!</small>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.notifications-container {
    padding: 0;
}

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

.notifications-list {
    background: #ffffff;
    border-radius: 14px;
    box-shadow: 0 2px 12px rgba(10, 43, 78, 0.06);
    border: 1px solid rgba(1, 72, 202, 0.04);
    overflow: hidden;
}

.notification-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #f0f4ff;
    transition: background 0.2s ease;
}

.notification-item:last-child {
    border-bottom: none;
}

.notification-item:hover {
    background: #f8faff;
}

.notification-item.unread {
    background: #f5f3ff;
    border-left: 3px solid #7c3aed;
}

.notification-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: #ede9fe;
    color: #7c3aed;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    flex-shrink: 0;
}

.notification-content {
    flex: 1;
}

.notification-content h6 {
    margin: 0;
    font-size: 0.9rem;
    font-weight: 600;
    color: #0a2b4e;
}

.notification-content p {
    margin: 0.2rem 0 0.3rem;
    font-size: 0.85rem;
    color: #64748b;
}

.notification-content small {
    font-size: 0.7rem;
    color: #94a3b8;
}

.empty-state {
    text-align: center;
    padding: 3rem;
}

.empty-state i {
    font-size: 3rem;
    color: #ede9fe;
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

@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.75rem;
    }
    
    .notification-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
        padding: 0.75rem 1rem;
    }
    
    .notification-item .btn {
        width: 100%;
    }
}
</style>

<?= $this->endSection() ?>