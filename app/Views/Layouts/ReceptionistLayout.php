<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PolyMedic - Receptionist Dashboard</title>

    <!-- Main CSS -->
    <link href="/polymedic/public/assets/css/AppointmentStyle.css" rel="stylesheet">
    <link href="/polymedic/public/assets/css/admin.css" rel="stylesheet">
    
    <!-- Bootstrap & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- AOS for animations -->
    <script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script> 
</head>
<body>
    <!-- Initialize AOS -->
    <script> AOS.init({ duration: 800, once: true }); </script>
    
    <div class="admin-wrapper">
        <!-- Sidebar -->
        <aside class="admin-sidebar receptionist-sidebar" id="adminSidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <img src="/polymedic/public/assets/images/logo4.png" alt="PolyMedic">
                    <span>PolyMedic<small>Diagnostic System</small></span>
                </div>
                <button class="sidebar-close" onclick="toggleSidebar()">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            
            <nav class="sidebar-nav">
                <ul>
                    <li class="nav-section">MAIN</li>
                    <li class="<?= current_url() == base_url('receptionist/dashboard') ? 'active' : '' ?>">
                        <a href="/polymedic/public/receptionist/dashboard">
                            <i class="bi bi-grid-1x2-fill"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="<?= strpos(current_url(), 'receptionist/appointment') !== false ? 'active' : '' ?>">
                        <a href="/polymedic/public/receptionist/appointments">
                            <i class="bi bi-calendar-check"></i>
                            <span>Appointments</span>
                        </a>
                    </li>
                    <li class="<?= current_url() == base_url('receptionist/patients') ? 'active' : '' ?>">
                        <a href="/polymedic/public/receptionist/patients">
                            <i class="bi bi-people-fill"></i>
                            <span>Patients</span>
                        </a>
                    </li>
                    
                    <li class="nav-section">FINANCIAL</li>
                    <li class="<?= current_url() == base_url('receptionist/billing') ? 'active' : '' ?>">
                        <a href="/polymedic/public/receptionist/billing">
                            <i class="bi bi-receipt"></i>
                            <span>Billing</span>
                        </a>
                    </li>
                    <li class="<?= current_url() == base_url('receptionist/payments') ? 'active' : '' ?>">
                        <a href="/polymedic/public/receptionist/payments">
                            <i class="bi bi-credit-card"></i>
                            <span>Payments</span>
                        </a>
                    </li>
                    <li class="<?= current_url() == base_url('receptionist/reports') ? 'active' : '' ?>">
                        <a href="/polymedic/public/receptionist/reports">
                            <i class="bi bi-bar-chart-fill"></i>
                            <span>Reports</span>
                        </a>
                    </li>

                    <li class="<?= current_url() == base_url('receptionist/notifications') ? 'active' : '' ?>">
                        <a href="<?= base_url('receptionist/notifications') ?>">
                            <i class="bi bi-bell-fill"></i>
                            <span>Notifications</span>
                            <?php 
                            $unreadCount = (new \App\Models\NotificationModel())->getUnreadCount();
                            if ($unreadCount > 0): 
                            ?>
                                <span class="badge-notif"><?= $unreadCount ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    
                    <li class="nav-divider"></li>
                    
                    <li class="logout-link">
                        <a href="/polymedic/public/logout">
                            <i class="bi bi-box-arrow-right"></i>
                            <span>Logout</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>
        
        <!-- Mobile Overlay -->
        <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
        
        <!-- Main Content -->
        <main class="admin-main">
            <!-- Top Navbar -->
            <header class="admin-header receptionist-header">
                <div class="header-left">
                    <button class="hamburger-btn" onclick="toggleSidebar()">
                        <i class="bi bi-list"></i>
                    </button>
                    <!-- Page Title with PNG Icon -->
                    <div class="header-title-group">
                        <?php 
                            $pageTitle = $this->renderSection('pageTitle') ?: 'Dashboard';
                            $iconMap = [
                                'Dashboard' => 'statisctics.png',
                                'Appointments' => 'appointment1.png',
                                'Patients' => 'sick-patient.png',
                                'Billing' => 'billing-icon.png',
                                'Payments' => 'payment-icon.png',
                                'Reports' => 'reports-icon.png'
                            ];
                            $iconFile = $iconMap[$pageTitle] ?? 'statisctics.png';
                        ?>
                        <img src="/polymedic/public/assets/images/<?= $iconFile ?>" alt="<?= $pageTitle ?>" class="header-title-icon">
                        <h4 class="page-title-header"><?= $pageTitle ?></h4>
                    </div>
                </div>
                <div class="header-right">
                    <div class="header-info-group">
                        <div class="header-datetime">
                            <i class="bi bi-clock"></i>
                            <span><?= date('D, M j · h:i:s A') ?></span>
                        </div>
                        <span class="divider-icon">|</span>
                        
                        <!-- NOTIFICATION DROPDOWN - UPDATED -->
                        <div class="dropdown notif-dropdown-wrapper">
                            <button class="notif-btn" type="button" id="notifDropdownBtn" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-bell-fill"></i>
                                <span class="notif-badge" id="notifBadge" style="display: none;">0</span>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end notif-dropdown-menu shadow-lg border-0" aria-labelledby="notifDropdownBtn">
                                <div class="notif-dropdown-header d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="bi bi-bell text-teal"></i>
                                        <span class="fw-bold text-dark fs-6">Notifications</span>
                                    </div>
                                    <button type="button" class="btn btn-link btn-sm p-0 text-teal text-decoration-none small" onclick="markAllNotificationsRead(event)">
                                        Mark all read
                                    </button>
                                </div>
                                <div class="notif-dropdown-body" id="notifDropdownList">
                                    <div class="p-3 text-center text-muted small">
                                        <div class="spinner-border spinner-border-sm text-teal me-1" role="status"></div>
                                        Loading notifications...
                                    </div>
                                </div>
                                <div class="notif-dropdown-footer text-center">
                                    <a href="<?= base_url('receptionist/notifications') ?>" class="text-teal fw-semibold small text-decoration-none">
                                        View All Notifications <i class="bi bi-arrow-right ms-1"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <span class="divider-icon">|</span>
                        <div class="header-user">
                            <div class="avatar-small receptionist-avatar">
                                <i class="bi bi-person-fill"></i>
                            </div>
                            <div class="user-details">
                                <span class="user-name-header"><?= session()->get('full_name') ?? 'Receptionist' ?></span>
                                <span class="user-role-header receptionist-role">Receptionist</span>
                            </div>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Page Content -->
            <div class="admin-content">
                <?php echo $this->renderSection('receptionistContent'); ?>
            </div>
        </main>
    </div>

    <style>
    /* ===== RECEPTIONIST THEME COLORS ===== */
    :root {
        --receptionist-primary: #0d9488;
        --receptionist-primary-dark: #0f766e;
        --receptionist-primary-light: #ccfbf1;
        --receptionist-primary-bg: #f0fdfa;
        --receptionist-accent: #14b8a6;
    }

    /* Sidebar */
    .receptionist-sidebar {
        border-right: 1px solid rgba(13, 148, 136, 0.08);
    }
    
    .receptionist-sidebar .sidebar-logo span {
        color: #0d9488;
    }
    
    .receptionist-sidebar .sidebar-nav ul li a:hover {
        background: rgba(13, 148, 136, 0.08);
        color: #0d9488;
    }
    
    .receptionist-sidebar .sidebar-nav ul li.active a {
        background: rgba(13, 148, 136, 0.12);
        color: #0d9488;
        border-right: 3px solid #0d9488;
    }
    
    .receptionist-sidebar .sidebar-nav ul li.logout-link a:hover {
        background: #fce4ec;
        color: #dc3545;
    }

    .receptionist-sidebar .sidebar-nav ul li.nav-section {
        color: #94a3b8;
    }

    /* Header */
    .receptionist-header .header-datetime i {
        color: #0d9488;
    }
    
    .receptionist-header .divider-icon {
        color: #e2e8f0;
    }
    
    .receptionist-header .notif-btn {
        color: #0d9488;
    }
    
    .receptionist-header .notif-btn:hover {
        background: rgba(13, 148, 136, 0.08);
    }
    
    .receptionist-avatar {
        background: #ccfbf1 !important;
        color: #0d9488 !important;
    }
    
    .receptionist-role {
        color: #0d9488 !important;
    }
    
    .receptionist-header .page-title-header {
        color: #0f766e;
    }

    /* Notification Badge */
    .notif-btn {
        position: relative;
        background: transparent;
        border: none;
        font-size: 1.2rem;
        color: #0d9488;
        cursor: pointer;
        padding: 0.35rem 0.6rem;
        border-radius: 50%;
        transition: all 0.2s ease;
    }
    .notif-btn:hover {
        background: rgba(13, 148, 136, 0.08);
    }
    .notif-badge {
        position: absolute;
        top: 2px;
        right: 2px;
        background: #dc3545;
        color: white;
        font-size: 0.65rem;
        font-weight: 700;
        min-width: 18px;
        height: 18px;
        border-radius: 99px;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0 4px;
        border: 2px solid white;
        box-shadow: 0 2px 6px rgba(220, 53, 69, 0.4);
    }
    .notif-badge.has-unread {
        animation: pulse-badge 1.8s infinite;
    }
    @keyframes pulse-badge {
        0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7); }
        70% { transform: scale(1.15); box-shadow: 0 0 0 6px rgba(220, 53, 69, 0); }
        100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(220, 53, 69, 0); }
    }
    .notif-dropdown-menu {
        width: 340px;
        max-width: 90vw;
        border-radius: 14px !important;
        padding: 0;
        margin-top: 10px !important;
        overflow: hidden;
    }
    .notif-dropdown-header {
        padding: 0.85rem 1rem;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
    }
    .notif-dropdown-body {
        max-height: 320px;
        overflow-y: auto;
    }
    .notif-dropdown-footer {
        padding: 0.75rem 1rem;
        background: #f8fafc;
        border-top: 1px solid #e2e8f0;
    }
    .notif-item {
        display: flex;
        gap: 0.75rem;
        padding: 0.85rem 1rem;
        border-bottom: 1px solid #f1f5f9;
        text-decoration: none !important;
        color: #334155;
        transition: background 0.15s ease;
        position: relative;
        cursor: pointer;
    }
    .notif-item:hover {
        background: #f0fdfa;
    }
    .notif-item.unread {
        background: #f8fafc;
        font-weight: 500;
    }
    .notif-item.unread::before {
        content: '';
        position: absolute;
        left: 6px;
        top: 50%;
        transform: translateY(-50%);
        width: 6px;
        height: 6px;
        background: #0d9488;
        border-radius: 50%;
    }
    .notif-icon-box {
        width: 34px;
        height: 34px;
        border-radius: 10px;
        background: #ccfbf1;
        color: #0d9488;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        flex-shrink: 0;
    }
    .notif-icon-box.appointment { background: #ccfbf1; color: #0d9488; }
    .notif-icon-box.system { background: #fef3c7; color: #d97706; }
    .notif-content {
        flex: 1;
        min-width: 0;
    }
    .notif-title {
        font-size: 0.82rem;
        font-weight: 600;
        color: #0f172a;
        margin-bottom: 2px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .notif-msg {
        font-size: 0.75rem;
        color: #64748b;
        margin-bottom: 4px;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .notif-time {
        font-size: 0.68rem;
        color: #94a3b8;
    }
    .text-teal {
        color: #0d9488 !important;
    }
    .text-teal:hover {
        color: #0f766e !important;
    }

    /* ============================================
       RESPONSIVE
    ============================================ */
    @media (max-width: 992px) {
        .admin-sidebar {
            position: fixed;
            top: 0;
            left: -280px;
            width: 280px;
            height: 100%;
            background: white;
            z-index: 1060;
            transition: left 0.3s ease;
            box-shadow: none;
        }
        .admin-sidebar.open {
            left: 0;
            box-shadow: 4px 0 30px rgba(0,0,0,0.08);
        }
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.3);
            z-index: 1050;
            display: none;
        }
        .sidebar-overlay.active {
            display: block;
        }
        .sidebar-close {
            display: flex !important;
        }
        .admin-header {
            padding: 0.75rem 1rem;
        }
        .header-title-group .page-title-header {
            font-size: 1rem !important;
        }
        .header-info-group .divider-icon {
            display: none;
        }
        .header-datetime span {
            font-size: 0.7rem;
        }
        .user-details .user-role-header {
            font-size: 0.6rem !important;
        }
        .admin-content {
            padding: 1rem;
        }
    }

    @media (max-width: 576px) {
        .admin-header {
            padding: 0.5rem 0.75rem;
        }
        .header-title-group .header-title-icon {
            width: 28px;
            height: 28px;
        }
        .header-title-group .page-title-header {
            font-size: 0.85rem !important;
        }
        .header-datetime span {
            font-size: 0.6rem;
        }
        .user-details .user-name-header {
            font-size: 0.7rem !important;
        }
        .user-details .user-role-header {
            font-size: 0.55rem !important;
        }
        .avatar-small {
            width: 28px;
            height: 28px;
            font-size: 0.7rem;
        }
        .admin-content {
            padding: 0.75rem;
        }
        .notif-dropdown-menu {
            width: 300px;
        }
        .hamburger-btn {
            font-size: 1.2rem;
            padding: 0.2rem 0.4rem;
        }
        .notif-btn {
            font-size: 1rem;
            padding: 0.2rem 0.4rem;
        }
    }

    @media (max-width: 400px) {
        .header-datetime span {
            font-size: 0.5rem;
        }
        .header-title-group .header-title-icon {
            width: 22px;
            height: 22px;
        }
        .header-title-group .page-title-header {
            font-size: 0.7rem !important;
        }
        .notif-dropdown-menu {
            width: 260px;
        }
        .notif-item {
            padding: 0.6rem 0.75rem;
        }
        .notif-title {
            font-size: 0.75rem;
        }
        .notif-msg {
            font-size: 0.7rem;
        }
    }

    .sidebar-close {
        display: none;
        background: transparent;
        border: none;
        font-size: 1.2rem;
        color: #64748b;
        padding: 0.25rem 0.5rem;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .sidebar-close:hover {
        background: #f0f4ff;
        color: #0a2b4e;
    }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.min.js"></script>
    <script>
        // Sidebar toggle
        function toggleSidebar() {
            const sidebar = document.getElementById('adminSidebar');
            const overlay = document.getElementById('sidebarOverlay');
            sidebar.classList.toggle('open');
            overlay.classList.toggle('active');
        }

        // Close sidebar on resize to desktop
        window.addEventListener('resize', function() {
            const sidebar = document.getElementById('adminSidebar');
            const overlay = document.getElementById('sidebarOverlay');
            if (window.innerWidth > 992) {
                sidebar.classList.remove('open');
                overlay.classList.remove('active');
            }
        });

        // Active link highlighting
        document.querySelectorAll('.sidebar-nav a').forEach(link => {
            if (link.href === window.location.href) {
                link.closest('li').classList.add('active');
            }
        });

        // ============================================================
        // NOTIFICATION FUNCTIONS - UPDATED FOR RECEPTIONIST
        // ============================================================

        function fetchNotifications() {
            fetch('/polymedic/public/receptionist/notifications/fetch', {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    updateNotificationBadge(data.unread_count);
                    renderNotificationDropdown(data.notifications);
                }
            })
            .catch(err => console.error('Error fetching notifications:', err));
        }

        function updateNotificationBadge(count) {
            const badge = document.getElementById('notifBadge');
            if (count > 0) {
                badge.textContent = count > 99 ? '99+' : count;
                badge.style.display = 'flex';
                badge.classList.add('has-unread');
            } else {
                badge.style.display = 'none';
                badge.classList.remove('has-unread');
            }
        }

        function renderNotificationDropdown(notifications) {
            const listContainer = document.getElementById('notifDropdownList');
            if (!notifications || notifications.length === 0) {
                listContainer.innerHTML = '<div class="p-3 text-center text-muted small"><i class="bi bi-bell-slash d-block fs-4 mb-1"></i>No notifications yet</div>';
                return;
            }

            let html = '';
            notifications.forEach(item => {
                const unreadClass = item.is_read == 0 ? 'unread' : '';
                const iconClass = item.type === 'appointment' ? 'bi-calendar-check' : 'bi-bell-fill';
                
                // Use the actual link from the notification
                const link = item.link || '#';
                
                html += `
                    <a href="${link}" class="notif-item ${unreadClass}" onclick="markNotificationRead(${item.id}, event)">
                        <div class="notif-icon-box ${item.type}">
                            <i class="bi ${iconClass}"></i>
                        </div>
                        <div class="notif-content">
                            <div class="notif-title">${escapeHtml(item.title)}</div>
                            <div class="notif-msg">${escapeHtml(item.message)}</div>
                            <div class="notif-time"><i class="bi bi-clock me-1"></i>${item.time_ago}</div>
                        </div>
                    </a>
                `;
            });
            listContainer.innerHTML = html;
        }

        function markNotificationRead(id, event) {
            // Don't prevent default - let the link navigate
            // Just mark as read in the background
            fetch('/polymedic/public/receptionist/notifications/mark-read/' + id, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    updateNotificationBadge(data.unread_count);
                    // Update the unread count in sidebar if needed
                    const sidebarBadge = document.querySelector('.sidebar-nav .badge-notif');
                    if (sidebarBadge) {
                        const newCount = data.unread_count;
                        if (newCount > 0) {
                            sidebarBadge.textContent = newCount;
                            sidebarBadge.style.display = 'inline';
                        } else {
                            sidebarBadge.style.display = 'none';
                        }
                    }
                }
            })
            .catch(err => console.error('Error marking notification read:', err));
        }

        function markAllNotificationsRead(event) {
            if (event) event.stopPropagation();
            fetch('/polymedic/public/receptionist/notifications/mark-all-read', {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    updateNotificationBadge(0);
                    fetchNotifications();
                    // Update sidebar badge
                    const sidebarBadge = document.querySelector('.sidebar-nav .badge-notif');
                    if (sidebarBadge) {
                        sidebarBadge.style.display = 'none';
                    }
                }
            })
            .catch(err => console.error('Error marking notifications read:', err));
        }

        function escapeHtml(text) {
            if (!text) return '';
            return text
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        // Initialize and poll notifications every 15s
        document.addEventListener('DOMContentLoaded', function() {
            fetchNotifications();
            setInterval(fetchNotifications, 15000);
        });
    </script>
</body>
</html>