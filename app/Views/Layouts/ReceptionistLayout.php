<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
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
                    <!-- =========================================
                         SIDEBAR COLLAPSE BUTTON (added, matches admin)
                         ========================================= -->
                    <li class="sidebar-toggle-item">
                        <button type="button"
                                class="sidebar-toggle-btn"
                                id="sidebarCollapseBtn"
                                onclick="toggleSidebarCollapse()"
                                title="Collapse Sidebar">
                            <i class="bi bi-layout-sidebar-inset"></i>
                            <span>Collapse Sidebar</span>
                        </button>
                    </li>

                    <li class="nav-section">MAIN</li>
                    <li class="menu-item <?= current_url() == base_url('receptionist/dashboard') ? 'active' : '' ?>">
                        <a href="/polymedic/public/receptionist/dashboard" class="menu-btn" data-tooltip="Dashboard">
                            <i class="bi bi-grid-1x2-fill menu-icon"></i>
                            <span>Dashboard</span>
                            <?php if (current_url() == base_url('receptionist/dashboard')): ?>
                                <i class="bi bi-chevron-right menu-arrow"></i>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="menu-item <?= strpos(current_url(), 'receptionist/appointment') !== false ? 'active' : '' ?>">
                        <a href="/polymedic/public/receptionist/appointments" class="menu-btn" data-tooltip="Appointments">
                            <i class="bi bi-calendar-check menu-icon"></i>
                            <span>Appointments</span>
                            <?php if (strpos(current_url(), 'receptionist/appointment') !== false): ?>
                                <i class="bi bi-chevron-right menu-arrow"></i>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="menu-item <?= current_url() == base_url('receptionist/patients') ? 'active' : '' ?>">
                        <a href="/polymedic/public/receptionist/patients" class="menu-btn" data-tooltip="Patients">
                            <i class="bi bi-people-fill menu-icon"></i>
                            <span>Patients</span>
                            <?php if (current_url() == base_url('receptionist/patients')): ?>
                                <i class="bi bi-chevron-right menu-arrow"></i>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="menu-item <?= current_url() == base_url('receptionist/diagnostic-requests') ? 'active' : '' ?>">
                        <a href="/polymedic/public/receptionist/diagnostic-requests" class="menu-btn" data-tooltip="Diagnostic Requests">
                            <i class="bi bi-file-earmark-medical menu-icon"></i>
                            <span>Diagnostic Requests</span>
                            <?php 
                            // Count pending diagnostic requests
                            $labModel = new \App\Models\LabRequestModel();
                            $xrayModel = new \App\Models\XrayExaminationModel();
                            $pendingCount = $labModel->where('status', 'pending')->countAllResults() + $xrayModel->where('status', 'pending')->countAllResults();
                            if ($pendingCount > 0): 
                            ?>
                                <span class="badge-notif"><?= $pendingCount ?></span>
                            <?php endif; ?>
                            <?php if (current_url() == base_url('receptionist/diagnostic-requests')): ?>
                                <i class="bi bi-chevron-right menu-arrow"></i>
                            <?php endif; ?>
                        </a>
                    </li>
                    
                    <li class="nav-section">FINANCIAL</li>
                    <li class="menu-item <?= current_url() == base_url('receptionist/billing') ? 'active' : '' ?>">
                        <a href="/polymedic/public/receptionist/billing" class="menu-btn" data-tooltip="Billing">
                            <i class="bi bi-receipt menu-icon"></i>
                            <span>Billing</span>
                            <?php if (current_url() == base_url('receptionist/billing')): ?>
                                <i class="bi bi-chevron-right menu-arrow"></i>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="menu-item <?= current_url() == base_url('receptionist/payments') ? 'active' : '' ?>">
                        <a href="/polymedic/public/receptionist/payments" class="menu-btn" data-tooltip="Payments">
                            <i class="bi bi-credit-card menu-icon"></i>
                            <span>Payments</span>
                            <?php if (current_url() == base_url('receptionist/payments')): ?>
                                <i class="bi bi-chevron-right menu-arrow"></i>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="menu-item <?= current_url() == base_url('receptionist/reports') ? 'active' : '' ?>">
                        <a href="/polymedic/public/receptionist/reports" class="menu-btn" data-tooltip="Reports">
                            <i class="bi bi-bar-chart-fill menu-icon"></i>
                            <span>Reports</span>
                            <?php if (current_url() == base_url('receptionist/reports')): ?>
                                <i class="bi bi-chevron-right menu-arrow"></i>
                            <?php endif; ?>
                        </a>
                    </li>

                    <li class="menu-item <?= current_url() == base_url('receptionist/notifications') ? 'active' : '' ?>">
                        <a href="<?= base_url('receptionist/notifications') ?>" class="menu-btn" data-tooltip="Notifications">
                            <i class="bi bi-bell-fill menu-icon"></i>
                            <span>Notifications</span>
                            <?php 
                            $unreadCount = (new \App\Models\NotificationModel())->getUnreadCount();
                            if ($unreadCount > 0): 
                            ?>
                                <span class="badge-notif"><?= $unreadCount ?></span>
                            <?php endif; ?>
                            <?php if (current_url() == base_url('receptionist/notifications')): ?>
                                <i class="bi bi-chevron-right menu-arrow"></i>
                            <?php endif; ?>
                        </a>
                    </li>
                    
                    <li class="nav-divider"></li>
                    
                    <li class="menu-item logout-item">
                        <a href="/polymedic/public/logout" class="menu-btn" data-tooltip="Logout">
                            <i class="bi bi-box-arrow-right menu-icon"></i>
                            <span>Logout</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>
        
        <!-- Mobile Overlay -->
        <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
        
        <!-- Main Content -->
        <main class="admin-main" id="adminMain">
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
                                'Reports' => 'reports-icon.png',
                                'Diagnostic Requests' => 'stethoscope.png'
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
                        
                        <!-- NOTIFICATION DROPDOWN -->
                        <div class="dropdown notif-dropdown-wrapper">
                            <button class="notif-btn" type="button" id="notifDropdownBtn" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-bell-fill"></i>
                                <span class="notif-badge" id="notifBadge" style="display: none;">0</span>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end notif-dropdown-menu shadow-lg border-0" aria-labelledby="notifDropdownBtn">
                                <div class="notif-dropdown-header d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="bi bi-bell text-primary"></i>
                                        <span class="fw-bold text-dark fs-6">Notifications</span>
                                    </div>
                                    <button type="button" class="btn btn-link btn-sm p-0 text-primary text-decoration-none small" onclick="markAllNotificationsRead(event)">
                                        Mark all read
                                    </button>
                                </div>
                                <div class="notif-dropdown-body" id="notifDropdownList">
                                    <div class="p-3 text-center text-muted small">
                                        <div class="spinner-border spinner-border-sm text-primary me-1" role="status"></div>
                                        Loading notifications...
                                    </div>
                                </div>
                                <div class="notif-dropdown-footer text-center">
                                    <a href="<?= base_url('receptionist/notifications') ?>" class="text-primary fw-semibold small text-decoration-none">
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
    /* ============================================
       RECEPTIONIST SIDEBAR - FULLY RESPONSIVE
       ============================================ */
    :root {
        --sidebar-width: 280px;
        --sidebar-collapsed-width: 78px;
        --header-height: 64px;
        --active-blue: #1976d2;
        --active-blue-dark: #1565c0;
        --active-blue-light: #e3f2fd;
        --text-blue: #1e40af;
        --icon-gray: #9ca3af;
        --bg-light: #f8fafc;
        --sidebar-ease: cubic-bezier(0.4, 0, 0.2, 1);
        --sidebar-speed: 0.32s;
    }

    /* ===== SIDEBAR ===== */
    .receptionist-sidebar {
        width: var(--sidebar-width);
        min-height: 100vh;
        background: #ffffff !important;
        position: fixed;
        top: 0;
        left: 0;
        bottom: 0;
        z-index: 1000;
        overflow-y: auto;
        overflow-x: hidden;
        transition: width var(--sidebar-speed) var(--sidebar-ease), transform 0.3s ease;
        box-shadow: 2px 0 20px rgba(0,0,0,0.06);
        border-right: 1px solid #e5e7eb !important;
    }

    .receptionist-sidebar::-webkit-scrollbar {
        width: 4px;
    }

    .receptionist-sidebar::-webkit-scrollbar-thumb {
        background: #e5e7eb;
        border-radius: 4px;
    }

    /* Sidebar Header */
    .sidebar-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1.5rem 1.5rem;
        border-bottom: 1px solid #f3f4f6 !important;
        transition: padding var(--sidebar-speed) var(--sidebar-ease),
                    justify-content var(--sidebar-speed) var(--sidebar-ease);
    }

    .sidebar-logo {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        min-width: 0;
        transition: gap var(--sidebar-speed) var(--sidebar-ease);
    }

    .sidebar-logo img {
        height: 40px;
        width: 40px;
        object-fit: contain;
        flex-shrink: 0;
        transition: width var(--sidebar-speed) var(--sidebar-ease),
                    height var(--sidebar-speed) var(--sidebar-ease);
    }

    .sidebar-logo span {
        font-size: 1.1rem;
        font-weight: 700;
        color: #111827 !important;
        letter-spacing: 0.5px;
        line-height: 1.2;
        white-space: nowrap;
        opacity: 1;
        max-width: 200px;
        overflow: hidden;
        display: inline-block;
        transition: opacity calc(var(--sidebar-speed) * 0.6) var(--sidebar-ease),
                    max-width var(--sidebar-speed) var(--sidebar-ease);
    }

    .sidebar-logo span small {
        font-weight: 400;
        font-size: 0.65rem;
        color: #6b7280 !important;
        display: block;
        margin-top: 2px;
    }

    .sidebar-close {
        display: none;
        background: transparent;
        border: none;
        color: #6b7280 !important;
        font-size: 1.2rem;
        cursor: pointer;
        padding: 0.25rem;
    }

    .sidebar-close:hover {
        color: #111827 !important;
    }

    /* Sidebar Navigation */
    .sidebar-nav {
        padding: 1rem 0.75rem 1.5rem;
    }

    .sidebar-nav ul {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }

    .sidebar-nav .nav-section {
        font-size: 0.65rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: #9ca3af !important;
        padding: 1rem 0.75rem 0.25rem;
        font-weight: 700;
        white-space: nowrap;
        opacity: 1;
        height: auto;
        overflow: hidden;
        transition: opacity calc(var(--sidebar-speed) * 0.6) var(--sidebar-ease),
                    height var(--sidebar-speed) var(--sidebar-ease),
                    padding var(--sidebar-speed) var(--sidebar-ease),
                    margin var(--sidebar-speed) var(--sidebar-ease);
    }

    /* ===== COLLAPSE BUTTON (added, matches admin) ===== */
    .sidebar-toggle-item {
        margin-bottom: 0.35rem;
    }

    .sidebar-toggle-btn {
        width: 100%;
        min-height: 43px;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.65rem 0.95rem;
        border: 1px solid #e5e7eb;
        border-radius: 9px;
        background: #f8fafc;
        color: #64748b;
        font-size: 0.8rem;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.2s ease,
                    color 0.2s ease,
                    border-color 0.2s ease,
                    transform 0.2s ease,
                    justify-content var(--sidebar-speed) var(--sidebar-ease),
                    gap var(--sidebar-speed) var(--sidebar-ease),
                    padding var(--sidebar-speed) var(--sidebar-ease);
    }

    .sidebar-toggle-btn i {
        width: 24px;
        min-width: 24px;
        text-align: center;
        font-size: 1.05rem;
        transition: transform var(--sidebar-speed) var(--sidebar-ease);
    }

    .sidebar-toggle-btn span {
        white-space: nowrap;
        opacity: 1;
        max-width: 200px;
        overflow: hidden;
        display: inline-block;
        transition: opacity calc(var(--sidebar-speed) * 0.6) var(--sidebar-ease),
                    max-width var(--sidebar-speed) var(--sidebar-ease);
    }

    .sidebar-toggle-btn:hover {
        background: #e3f2fd;
        border-color: #bfdbfe;
        color: #1976d2;
    }

    .sidebar-toggle-btn:active {
        transform: scale(0.98);
    }

    /* ===== NON-ACTIVE MENU ITEMS ===== */
    .receptionist-sidebar .sidebar-nav ul li.menu-item:not(.active) a.menu-btn,
    .receptionist-sidebar .sidebar-nav ul li.menu-item:not(.active) a.menu-btn span {
        color: var(--text-blue) !important;
    }

    .receptionist-sidebar .sidebar-nav ul li.menu-item:not(.active) a.menu-btn .menu-icon {
        color: var(--icon-gray) !important;
    }

    /* ===== ACTIVE MENU ITEM ===== */
    .receptionist-sidebar .sidebar-nav ul li.menu-item.active a.menu-btn,
    .receptionist-sidebar .sidebar-nav ul li.menu-item.active a.menu-btn span {
        color: #ffffff !important;
    }

    .receptionist-sidebar .sidebar-nav ul li.menu-item.active a.menu-btn .menu-icon {
        color: #ffffff !important;
    }

    .receptionist-sidebar .sidebar-nav ul li a,
    .receptionist-sidebar .sidebar-nav ul li.active a {
        border: none !important;
        border-left: none !important;
        border-right: none !important;
        border-top: none !important;
        border-bottom: none !important;
        outline: none !important;
    }

    /* Menu Item Styles */
    .menu-btn {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        width: 100%;
        min-height: 44px;
        padding: 0.75rem 1rem;
        border-radius: 8px !important;
        text-decoration: none;
        font-size: 0.875rem;
        font-weight: 600;
        transition: background 0.2s ease,
                    color 0.2s ease,
                    box-shadow 0.2s ease,
                    gap var(--sidebar-speed) var(--sidebar-ease),
                    padding var(--sidebar-speed) var(--sidebar-ease),
                    justify-content var(--sidebar-speed) var(--sidebar-ease);
        background: transparent;
        cursor: pointer;
        position: relative;
    }

    .menu-btn:hover {
        background: var(--active-blue-light) !important;
        color: var(--active-blue) !important;
        box-shadow: inset 0 1px 3px rgba(25, 118, 210, 0.1) !important;
    }

    .menu-btn:hover .menu-icon {
        color: var(--active-blue) !important;
        transform: scale(1.05);
    }

    .menu-item.active .menu-btn {
        background: var(--active-blue) !important;
        color: #ffffff !important;
        box-shadow: 0 4px 12px rgba(25, 118, 210, 0.3) !important;
    }

    /* Menu Icon */
    .menu-icon {
        font-size: 1.08rem;
        flex-shrink: 0;
        width: 24px;
        text-align: center;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: color 0.2s ease, transform 0.2s ease;
    }

    /* Menu label — animates on width/opacity */
    .menu-btn > span {
        white-space: nowrap;
        opacity: 1;
        max-width: 200px;
        overflow: hidden;
        display: inline-block;
        transition: opacity calc(var(--sidebar-speed) * 0.6) var(--sidebar-ease),
                    max-width var(--sidebar-speed) var(--sidebar-ease);
    }

    /* Menu Arrow (Chevron) */
    .menu-arrow {
        font-size: 0.9rem;
        color: #ffffff !important;
        margin-left: auto;
        flex-shrink: 0;
        opacity: 1;
        transition: opacity calc(var(--sidebar-speed) * 0.6) var(--sidebar-ease);
    }

    /* ===== REMOVE BADGE FROM ACTIVE ITEMS ===== */
    .menu-item.active .badge-notif {
        display: none !important;
    }

    /* Badge - only shows on non-active items */
    .badge-notif {
        margin-left: auto;
        background: #ffffff !important;
        color: var(--text-blue) !important;
        font-size: 0.65rem;
        font-weight: 700;
        padding: 0.15rem 0.5rem;
        border-radius: 30px;
        min-width: 20px;
        height: 20px;
        text-align: center;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        flex-shrink: 0;
        line-height: 1;
        opacity: 1;
        transition: opacity calc(var(--sidebar-speed) * 0.6) var(--sidebar-ease),
                    transform var(--sidebar-speed) var(--sidebar-ease);
    }

    /* Logout Item */
    .logout-item {
        margin-top: 0.5rem;
        border-top: 1px solid #f3f4f6 !important;
        padding-top: 0.5rem;
    }

    .logout-item .menu-btn:hover {
        background: #fee2e2 !important;
        color: #dc2626 !important;
    }

    .logout-item .menu-btn:hover .menu-icon {
        color: #dc2626 !important;
    }

    /* Nav Divider */
    .nav-divider {
        height: 1px;
        background: #f3f4f6 !important;
        margin: 0.5rem 0;
    }

    /* ===== SIDEBAR OVERLAY ===== */
    .sidebar-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.4);
        z-index: 999;
        display: none;
    }

    .sidebar-overlay.active {
        display: block;
    }

    /* ===== MAIN CONTENT ===== */
    .admin-main {
        flex: 1;
        margin-left: var(--sidebar-width);
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        background: var(--bg-light);
        width: calc(100% - var(--sidebar-width));
        max-width: 100%;
        transition: margin-left var(--sidebar-speed) var(--sidebar-ease),
                    width var(--sidebar-speed) var(--sidebar-ease);
    }

    .admin-main.sidebar-collapsed {
        margin-left: var(--sidebar-collapsed-width);
        width: calc(100% - var(--sidebar-collapsed-width));
    }

    /* ===== HEADER - FIXED FOR MOBILE ===== */
    .receptionist-header {
        background: #ffffff !important;
        padding: 0.75rem 2rem;
        border-bottom: 1px solid #e5e7eb !important;
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: sticky;
        top: 0;
        z-index: 100;
        min-height: var(--header-height);
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        width: 100%;
        box-sizing: border-box;
    }

    .header-left {
        display: flex;
        align-items: center;
        gap: 1rem;
        min-width: 0;
    }

    .hamburger-btn {
        display: none;
        background: transparent;
        border: none;
        font-size: 1.5rem;
        color: #111827 !important;
        cursor: pointer;
        padding: 0.25rem;
        flex-shrink: 0;
    }

    .header-title-group {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        min-width: 0;
    }

    .header-title-icon {
        width: 24px;
        height: 24px;
        object-fit: contain;
        flex-shrink: 0;
    }

    .page-title-header {
        font-size: 1rem;
        font-weight: 600;
        color: #111827 !important;
        margin: 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .header-right {
        display: flex;
        align-items: center;
        gap: 1rem;
        flex-shrink: 0;
    }

    .header-info-group {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .header-datetime {
        display: flex;
        align-items: center;
        gap: 0.3rem;
        color: #6b7280 !important;
        font-size: 0.78rem;
        font-weight: 500;
        white-space: nowrap;
    }

    .header-datetime i {
        color: var(--active-blue) !important;
        font-size: 0.8rem;
    }

    .divider-icon {
        display: flex;
        align-items: center;
        color: #d1d5db !important;
        font-size: 0.8rem;
        font-weight: 300;
        padding: 0 0.1rem;
    }

    /* Notification Button */
    .notif-btn {
        position: relative;
        background: transparent;
        border: none;
        font-size: 1.2rem;
        color: #6b7280 !important;
        cursor: pointer;
        padding: 0.35rem 0.6rem;
        border-radius: 50%;
        transition: all 0.2s ease;
    }

    .notif-btn:hover {
        color: var(--active-blue) !important;
        background: var(--active-blue-light) !important;
    }

    .notif-badge {
        position: absolute;
        top: 2px;
        right: 2px;
        background: #dc2626 !important;
        color: white !important;
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
        box-shadow: 0 2px 6px rgba(220, 38, 38, 0.4);
    }

    .notif-badge.has-unread {
        animation: pulse-badge 1.8s infinite;
    }

    @keyframes pulse-badge {
        0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(220, 38, 38, 0.7); }
        70% { transform: scale(1.15); box-shadow: 0 0 0 6px rgba(220, 38, 38, 0); }
        100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(220, 38, 38, 0); }
    }

    /* Notification Dropdown */
    .notif-dropdown-menu {
        width: 340px;
        max-width: 90vw;
        border-radius: 14px !important;
        padding: 0;
        margin-top: 10px !important;
        overflow: hidden;
        border: 1px solid #e5e7eb !important;
    }

    .notif-dropdown-header {
        padding: 0.85rem 1rem;
        background: #f8fafc !important;
        border-bottom: 1px solid #e5e7eb !important;
    }

    .notif-dropdown-body {
        max-height: 320px;
        overflow-y: auto;
    }

    .notif-dropdown-footer {
        padding: 0.75rem 1rem;
        background: #f8fafc !important;
        border-top: 1px solid #e5e7eb !important;
    }

    .notif-item {
        display: flex;
        gap: 0.75rem;
        padding: 0.85rem 1rem;
        border-bottom: 1px solid #f3f4f6 !important;
        text-decoration: none !important;
        color: #374151 !important;
        transition: background 0.15s ease;
        position: relative;
        cursor: pointer;
    }

    .notif-item:hover {
        background: #f8fafc !important;
    }

    .notif-item.unread {
        background: #f8fafc !important;
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
        background: var(--active-blue) !important;
        border-radius: 50%;
    }

    .notif-icon-box {
        width: 34px;
        height: 34px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        flex-shrink: 0;
    }

    .notif-icon-box.appointment { background: #e3f2fd !important; color: #1976d2 !important; }
    .notif-icon-box.system { background: #fef3c7 !important; color: #d97706 !important; }
    .notif-icon-box.billing { background: #fff3e0 !important; color: #ff6b00 !important; }
    .notif-icon-box.payment { background: #ccfbf1 !important; color: #0d9488 !important; }
    .notif-icon-box.lab { background: #e3f2fd !important; color: #1976d2 !important; }
    .notif-icon-box.xray { background: #f3e5f5 !important; color: #7b1fa2 !important; }

    .notif-content {
        flex: 1;
        min-width: 0;
    }

    .notif-title {
        font-size: 0.82rem;
        font-weight: 600;
        color: #111827 !important;
        margin-bottom: 2px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .notif-msg {
        font-size: 0.75rem;
        color: #6b7280 !important;
        margin-bottom: 4px;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .notif-time {
        font-size: 0.68rem;
        color: #9ca3af !important;
    }

    .text-primary {
        color: var(--active-blue) !important;
    }

    .text-primary:hover {
        color: var(--active-blue-dark) !important;
    }

    /* Header User */
    .header-user {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        cursor: pointer;
        padding: 0.1rem 0.3rem;
        border-radius: 8px;
        transition: all 0.3s ease;
    }

    .header-user:hover {
        background: #f8fafc !important;
    }

    .receptionist-avatar {
        background: var(--active-blue-light) !important;
        color: var(--active-blue) !important;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
        flex-shrink: 0;
    }

    .user-details {
        display: flex;
        flex-direction: column;
        line-height: 1.15;
    }

    .user-name-header {
        font-size: 0.78rem;
        font-weight: 600;
        color: #111827 !important;
        white-space: nowrap;
    }

    .user-role-header {
        font-size: 0.58rem;
        color: #6b7280 !important;
        font-weight: 500;
        white-space: nowrap;
    }

    .receptionist-role {
        color: var(--active-blue) !important;
    }

    /* ===== CONTENT AREA ===== */
    .admin-content {
        flex: 1;
        padding: 1.5rem 2rem 2rem;
        width: 100%;
        max-width: 100%;
        box-sizing: border-box;
        overflow-x: hidden;
    }

    /* ===== RESPONSIVE GRID FIX FOR RECEPTIONIST PAGES ===== */
    .stats-row,
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
        width: 100%;
    }

    .stat-card {
        background: #ffffff;
        border-radius: 14px;
        padding: 1.25rem 1.5rem;
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 0.75rem;
        box-shadow: 0 2px 12px rgba(10, 43, 78, 0.06);
        border: 1px solid rgba(1, 72, 202, 0.04);
        transition: all 0.3s ease;
        width: 100%;
        min-width: 0;
        box-sizing: border-box;
    }

    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(10, 43, 78, 0.1);
    }

    .stat-icon {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        flex-shrink: 0;
    }

    .stat-info {
        min-width: 0;
        width: 100%;
    }

    .stat-info h3 {
        font-size: 1.4rem;
        font-weight: 700;
        color: #0a2b4e;
        margin: 0;
        line-height: 1.2;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .stat-info p {
        color: #64748b;
        font-size: 0.8rem;
        margin: 0;
        font-weight: 500;
    }

    .stat-info small {
        font-size: 0.7rem;
        color: #94a3b8;
        margin-top: 2px;
        display: block;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .charts-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
        margin-bottom: 1.5rem;
        width: 100%;
    }

    .chart-card,
    .reports-card,
    .summary-card,
    .table-card,
    .queue-card,
    .tat-card,
    .appointments-card {
        background: #ffffff;
        border-radius: 16px;
        padding: 1.25rem 1.5rem;
        box-shadow: 0 2px 12px rgba(10, 43, 78, 0.06);
        border: 1px solid rgba(1, 72, 202, 0.04);
        min-width: 0;
        width: 100%;
        box-sizing: border-box;
    }

    .requests-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
        width: 100%;
    }

    .request-card {
        background: #ffffff;
        border-radius: 12px;
        padding: 1.25rem;
        border: 1px solid #eef2f7;
        transition: all 0.3s ease;
        width: 100%;
        min-width: 0;
        box-sizing: border-box;
    }

    /* ===== RESPONSIVE TABLE ===== */
    .table-responsive {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .receptionist-table,
    .appointments-table,
    .patients-table {
        margin: 0;
        width: 100%;
        border-collapse: separate;
        border-spacing: 0 8px;
    }

    .receptionist-table thead th,
    .appointments-table thead th,
    .patients-table thead th {
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

    .receptionist-table tbody tr,
    .appointments-table tbody tr,
    .patients-table tbody tr {
        background: #ffffff;
        border-radius: 12px;
        transition: all 0.2s ease;
        box-shadow: 0 1px 3px rgba(0,0,0,0.03);
        border: 1px solid #E5E9ED;
    }

    .receptionist-table tbody tr:hover,
    .appointments-table tbody tr:hover,
    .patients-table tbody tr:hover {
        background: #F8FAFB;
        box-shadow: 0 4px 12px rgba(16, 24, 40, 0.08);
        transform: translateY(-1px);
    }

    .receptionist-table tbody td,
    .appointments-table tbody td,
    .patients-table tbody td {
        padding: 0.85rem 1rem;
        vertical-align: middle;
        font-size: 0.85rem;
        color: #101828;
        border: none;
        white-space: nowrap;
        background: transparent;
    }

    .receptionist-table tbody td:first-child,
    .appointments-table tbody td:first-child,
    .patients-table tbody td:first-child {
        border-radius: 12px 0 0 12px;
    }

    .receptionist-table tbody td:last-child,
    .appointments-table tbody td:last-child,
    .patients-table tbody td:last-child {
        border-radius: 0 12px 12px 0;
    }

    /* ============================================
       COLLAPSED SIDEBAR (DESKTOP ONLY) — added, matches admin
       ============================================ */

    @media (min-width: 993px) {

        .receptionist-sidebar.collapsed {
            width: var(--sidebar-collapsed-width);
        }

        .receptionist-sidebar.collapsed .sidebar-header {
            justify-content: center;
            padding: 1rem 0.5rem;
        }

        .receptionist-sidebar.collapsed .sidebar-logo {
            gap: 0;
            justify-content: center;
            width: 100%;
        }

        .receptionist-sidebar.collapsed .sidebar-logo img {
            width: 40px;
            height: 40px;
        }

        .receptionist-sidebar.collapsed .sidebar-logo span {
            opacity: 0;
            max-width: 0;
        }

        .receptionist-sidebar.collapsed .sidebar-nav {
            padding: 0.9rem 0.6rem;
        }

        /* Toggle button - centered */
        .receptionist-sidebar.collapsed .sidebar-toggle-btn {
            justify-content: center;
            padding: 0.65rem 0;
            gap: 0;
            background: #f1f5f9;
            border-color: #e2e8f0;
            display: flex;
            align-items: center;
        }

        .receptionist-sidebar.collapsed .sidebar-toggle-btn span {
            opacity: 0;
            max-width: 0;
            display: none;
        }

        .receptionist-sidebar.collapsed .sidebar-toggle-btn i {
            margin: 0;
            transform: rotate(180deg);
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 24px;
        }

        .receptionist-sidebar.collapsed .nav-section {
            font-size: 0.64rem;
            height: 13px;
            padding: 0;
            margin: 0.55rem 0;
            opacity: 0;
        }

        /* Menu buttons - centered icons */
        .receptionist-sidebar.collapsed .menu-btn {
            justify-content: center;
            width: 100%;
            min-height: 44px;
            padding: 0.7rem 0;
            gap: 0;
            display: flex;
            align-items: center;
        }

        .receptionist-sidebar.collapsed .menu-btn > span {
            opacity: 0;
            max-width: 0;
            display: none;
        }

        /* Center the icon properly */
        .receptionist-sidebar.collapsed .menu-icon {
            width: 24px;
            height: 24px;
            margin: 0;
            font-size: 1.15rem;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            text-align: center;
            line-height: 1;
        }

        .receptionist-sidebar.collapsed .menu-arrow {
            opacity: 0;
            max-width: 0;
            overflow: hidden;
            display: none;
        }

        .receptionist-sidebar.collapsed .menu-item {
            position: relative;
        }

        .receptionist-sidebar.collapsed .badge-notif {
            position: absolute;
            top: 2px;
            right: 2px;
            margin: 0;
            min-width: 17px;
            width: 17px;
            height: 17px;
            padding: 0;
            font-size: 0.52rem;
            z-index: 5;
            opacity: 1;
        }

        /* Tooltips */
        .receptionist-sidebar.collapsed .menu-btn:hover::after {
            content: attr(data-tooltip);
            position: absolute;
            left: calc(100% + 12px);
            top: 50%;
            transform: translateY(-50%);
            background: #111827;
            color: #ffffff;
            padding: 0.45rem 0.7rem;
            border-radius: 6px;
            font-size: 0.72rem;
            font-weight: 500;
            white-space: nowrap;
            z-index: 3000;
            pointer-events: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.18);
            animation: tooltipFade 0.15s ease forwards;
        }

        .receptionist-sidebar.collapsed .sidebar-toggle-btn:hover::after {
            content: attr(title);
            position: absolute;
            left: calc(100% + 12px);
            top: 50%;
            transform: translateY(-50%);
            background: #111827;
            color: #ffffff;
            padding: 0.45rem 0.7rem;
            border-radius: 6px;
            font-size: 0.72rem;
            font-weight: 500;
            white-space: nowrap;
            z-index: 3000;
            pointer-events: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.18);
            animation: tooltipFade 0.15s ease forwards;
        }

        @keyframes tooltipFade {
            from {
                opacity: 0;
                transform: translateY(-50%) translateX(-5px);
            }
            to {
                opacity: 1;
                transform: translateY(-50%) translateX(0);
            }
        }
    }

    /* ============================================
       RESPONSIVE BREAKPOINTS
       ============================================ */

    @media (max-width: 1400px) {
        .stats-row,
        .stats-grid {
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 0.85rem;
        }
    }

    @media (max-width: 1200px) {
        .stats-row,
        .stats-grid {
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 0.75rem;
        }

        .charts-row,
        .requests-grid {
            grid-template-columns: 1fr;
        }

        .receptionist-header {
            padding: 0.75rem 1.25rem;
        }

        .admin-content {
            padding: 1.25rem;
        }
    }

    @media (max-width: 992px) {
        .receptionist-sidebar {
            position: fixed;
            top: 0;
            left: -280px;
            width: 280px;
            height: 100%;
            background: #ffffff !important;
            z-index: 1060;
            transition: left 0.3s ease;
            box-shadow: none;
        }

        .receptionist-sidebar.open {
            left: 0;
            box-shadow: 4px 0 30px rgba(0,0,0,0.1) !important;
        }

        /* Force expanded state on mobile */
        .receptionist-sidebar.collapsed {
            width: var(--sidebar-width) !important;
        }

        .receptionist-sidebar.collapsed .sidebar-header {
            justify-content: space-between !important;
            padding: 1.5rem 1.5rem !important;
        }

        .receptionist-sidebar.collapsed .sidebar-logo {
            gap: 0.75rem !important;
            justify-content: flex-start !important;
        }

        .receptionist-sidebar.collapsed .sidebar-logo span {
            opacity: 1 !important;
            max-width: 200px !important;
        }

        .receptionist-sidebar.collapsed .nav-section {
            font-size: 0.65rem !important;
            height: auto !important;
            padding: 1rem 0.75rem 0.25rem !important;
            margin: 0 !important;
            opacity: 1 !important;
        }

        .receptionist-sidebar.collapsed .menu-btn {
            justify-content: flex-start !important;
            padding: 0.75rem 1rem !important;
            gap: 0.75rem !important;
        }

        .receptionist-sidebar.collapsed .menu-btn > span {
            opacity: 1 !important;
            max-width: 200px !important;
        }

        .receptionist-sidebar.collapsed .menu-arrow {
            opacity: 1 !important;
            max-width: 20px !important;
            display: inline-block !important;
        }

        /* Hide collapse button on mobile */
        .sidebar-toggle-item {
            display: none !important;
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

        .admin-main {
            margin-left: 0;
            width: 100%;
        }

        .admin-main.sidebar-collapsed {
            margin-left: 0 !important;
            width: 100% !important;
        }

        .hamburger-btn {
            display: block;
        }

        .receptionist-header {
            padding: 0.75rem 1rem;
        }

        .header-title-group .page-title-header {
            font-size: 0.9rem !important;
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

        .notif-dropdown-menu {
            width: 300px;
        }
    }

    @media (max-width: 768px) {
        .receptionist-header {
            padding: 0.5rem 0.75rem;
            min-height: 50px;
        }

        .header-title-group .header-title-icon {
            width: 20px;
            height: 20px;
        }

        .header-title-group .page-title-header {
            font-size: 0.8rem !important;
            max-width: 150px;
        }

        .header-datetime {
            display: none;
        }

        .header-datetime span {
            font-size: 0.6rem;
        }

        .header-datetime i {
            font-size: 0.65rem;
        }

        .header-info-group .divider-icon {
            display: none;
        }

        .user-details {
            display: none;
        }

        .avatar-small {
            width: 30px;
            height: 30px;
            font-size: 0.75rem;
        }

        .admin-content {
            padding: 0.75rem;
        }

        .notif-dropdown-menu {
            width: 280px;
        }

        .hamburger-btn {
            font-size: 1.2rem;
            padding: 0.2rem 0.4rem;
        }

        .notif-btn {
            font-size: 1rem;
            padding: 0.2rem 0.4rem;
        }

        .menu-btn {
            padding: 0.6rem 0.75rem;
            font-size: 0.8rem;
        }

        .menu-icon {
            font-size: 0.9rem;
            width: 20px;
        }

        .badge-notif {
            font-size: 0.5rem;
            padding: 0.1rem 0.4rem;
            min-width: 16px;
            height: 16px;
        }

        .header-user {
            padding: 0;
        }

        .header-user .avatar-small {
            width: 28px;
            height: 28px;
            font-size: 0.65rem;
        }

        .stats-row,
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 0.6rem;
        }

        .stat-card {
            padding: 1rem;
        }

        .stat-icon {
            width: 36px;
            height: 36px;
            font-size: 1rem;
        }

        .stat-info h3 {
            font-size: 1.25rem;
        }

        .chart-card,
        .reports-card,
        .summary-card,
        .table-card,
        .queue-card,
        .tat-card,
        .appointments-card {
            padding: 1rem;
        }

        .page-header {
            flex-direction: column;
            align-items: stretch;
            gap: 0.75rem;
        }

        .header-actions {
            width: 100%;
            gap: 0.5rem;
        }

        .header-actions .btn {
            flex: 1;
            justify-content: center;
            padding: 0.5rem 1rem;
            font-size: 0.8rem;
        }

        .table-toolbar,
        .search-filter-row {
            flex-direction: column;
            align-items: stretch;
        }

        .search-wrapper {
            max-width: 100%;
        }

        .filter-buttons {
            justify-content: center;
            flex-wrap: wrap;
        }

        .request-header {
            flex-direction: column;
            gap: 0.5rem;
        }

        .request-id-section {
            flex-wrap: wrap;
        }

        .request-footer {
            flex-wrap: wrap;
        }

        .btn-status {
            flex: 1;
            justify-content: center;
        }
    }

    @media (max-width: 576px) {
        .receptionist-header {
            padding: 0.4rem 0.5rem;
            min-height: 44px;
        }

        .header-title-group .header-title-icon {
            width: 18px;
            height: 18px;
        }

        .header-title-group .page-title-header {
            font-size: 0.7rem !important;
            max-width: 120px;
        }

        .header-datetime {
            display: none;
        }

        .header-info-group .divider-icon {
            display: none;
        }

        .notif-btn {
            font-size: 0.9rem;
            padding: 0.15rem 0.3rem;
        }

        .notif-badge {
            width: 14px;
            height: 14px;
            font-size: 0.5rem;
            min-width: 14px;
            top: -1px;
            right: -1px;
        }

        .avatar-small {
            width: 26px;
            height: 26px;
            font-size: 0.65rem;
        }

        .header-user {
            display: none;
        }

        .admin-content {
            padding: 0.5rem;
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

        .menu-btn {
            padding: 0.5rem 0.6rem;
            font-size: 0.8rem;
        }

        .stats-row,
        .stats-grid {
            grid-template-columns: 1fr;
            gap: 0.5rem;
        }

        .stat-card {
            flex-direction: row;
            align-items: center;
            padding: 0.85rem 1rem;
            min-height: auto;
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            font-size: 1.1rem;
            margin-bottom: 0;
        }

        .stat-info {
            flex: 1;
        }

        .stat-info h3 {
            font-size: 1.4rem;
            white-space: normal;
        }

        .stat-info p {
            font-size: 0.75rem;
        }

        .stat-info small {
            font-size: 0.65rem;
            white-space: normal;
        }

        .requests-grid {
            grid-template-columns: 1fr;
        }

        .request-card {
            padding: 0.75rem;
        }

        .filter-btn {
            font-size: 0.6rem;
            padding: 0.2rem 0.5rem;
        }

        .request-meta {
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .receptionist-table,
        .appointments-table,
        .patients-table {
            font-size: 0.75rem;
            border-spacing: 0 6px;
        }

        .receptionist-table thead th,
        .receptionist-table tbody td,
        .appointments-table thead th,
        .appointments-table tbody td,
        .patients-table thead th,
        .patients-table tbody td {
            padding: 0.5rem;
            font-size: 0.75rem;
        }

        .patient-avatar {
            width: 28px;
            height: 28px;
            font-size: 0.55rem;
        }

        .action-icon-btn {
            width: 32px;
            height: 32px;
            font-size: 0.85rem;
            border-radius: 8px;
        }

        .chart-body {
            height: 220px;
        }

        .action-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 400px) {
        .header-title-group .header-title-icon {
            width: 16px;
            height: 16px;
        }

        .header-title-group .page-title-header {
            font-size: 0.65rem !important;
            max-width: 100px;
        }

        .notif-dropdown-menu {
            width: 250px;
        }

        .sidebar-nav {
            padding: 0.75rem 0.5rem;
        }

        .menu-btn {
            padding: 0.5rem 0.6rem;
            gap: 0.5rem;
        }

        .notif-btn {
            font-size: 0.85rem;
        }

        .notif-badge {
            width: 12px;
            height: 12px;
            font-size: 0.4rem;
        }

        .chart-body {
            height: 180px;
        }
    }

    /* Disable transitions only during the initial state application
       on page load, so restoring a saved collapsed/expanded state
       never itself animates — only user clicks do. */
    .receptionist-sidebar.no-transition,
    .receptionist-sidebar.no-transition *,
    .admin-main.no-transition {
        transition: none !important;
    }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.min.js"></script>
    <script>
        // ============================================================
        // SIDEBAR TOGGLE (mobile hamburger)
        // ============================================================

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
        // SIDEBAR COLLAPSE / EXPAND (desktop) — added, matches admin
        // ============================================================

        (function() {
            'use strict';

            function getSidebarElements() {
                var sidebar = document.getElementById('adminSidebar');
                var adminMain = document.getElementById('adminMain');
                var collapseBtn = document.getElementById('sidebarCollapseBtn');
                return { sidebar: sidebar, adminMain: adminMain, collapseBtn: collapseBtn };
            }

            function applySidebarState(skipTransition) {
                var elements = getSidebarElements();
                var sidebar = elements.sidebar;
                var adminMain = elements.adminMain;

                if (!sidebar || !adminMain) {
                    setTimeout(function() { applySidebarState(skipTransition); }, 50);
                    return;
                }

                if (skipTransition) {
                    sidebar.classList.add('no-transition');
                    adminMain.classList.add('no-transition');
                }

                if (window.innerWidth <= 992) {
                    sidebar.classList.remove('collapsed');
                    adminMain.classList.remove('sidebar-collapsed');
                    updateCollapseButton();
                    if (skipTransition) requestAnimationFrame(removeNoTransition);
                    return;
                }

                var savedState = localStorage.getItem('polymedicReceptionistSidebarCollapsed');

                if (savedState === null) {
                    localStorage.setItem('polymedicReceptionistSidebarCollapsed', '0');
                    savedState = '0';
                }

                if (savedState === '1') {
                    sidebar.classList.add('collapsed');
                    adminMain.classList.add('sidebar-collapsed');
                } else {
                    sidebar.classList.remove('collapsed');
                    adminMain.classList.remove('sidebar-collapsed');
                }

                updateCollapseButton();

                if (skipTransition) requestAnimationFrame(removeNoTransition);
            }

            function removeNoTransition() {
                var elements = getSidebarElements();
                if (elements.sidebar) elements.sidebar.classList.remove('no-transition');
                if (elements.adminMain) elements.adminMain.classList.remove('no-transition');
            }

            function updateCollapseButton() {
                var elements = getSidebarElements();
                var sidebar = elements.sidebar;
                var collapseBtn = elements.collapseBtn;

                if (!collapseBtn || !sidebar) return;

                var icon = collapseBtn.querySelector('i');
                var text = collapseBtn.querySelector('span');
                var isCollapsed = sidebar.classList.contains('collapsed');

                if (isCollapsed) {
                    if (icon) icon.className = 'bi bi-layout-sidebar-inset-reverse';
                    collapseBtn.title = 'Expand Sidebar';
                    if (text) text.textContent = 'Expand Sidebar';
                } else {
                    if (icon) icon.className = 'bi bi-layout-sidebar-inset';
                    collapseBtn.title = 'Collapse Sidebar';
                    if (text) text.textContent = 'Collapse Sidebar';
                }
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function() {
                    applySidebarState(true);
                });
            } else {
                applySidebarState(true);
            }

            window.addEventListener('load', function() {
                applySidebarState(true);
            });

            window.addEventListener('pageshow', function(e) {
                if (e.persisted) {
                    setTimeout(function() { applySidebarState(true); }, 50);
                }
            });

            window.toggleSidebarCollapse = function() {
                var elements = getSidebarElements();
                var sidebar = elements.sidebar;
                var adminMain = elements.adminMain;

                if (!sidebar || !adminMain) return;
                if (window.innerWidth <= 992) return;

                if (sidebar.classList.contains('collapsed')) {
                    sidebar.classList.remove('collapsed');
                    adminMain.classList.remove('sidebar-collapsed');
                    localStorage.setItem('polymedicReceptionistSidebarCollapsed', '0');
                } else {
                    sidebar.classList.add('collapsed');
                    adminMain.classList.add('sidebar-collapsed');
                    localStorage.setItem('polymedicReceptionistSidebarCollapsed', '1');
                }

                updateCollapseButton();
            };

            window.addEventListener('resize', function() {
                var elements = getSidebarElements();
                var sidebar = elements.sidebar;
                var adminMain = elements.adminMain;

                if (!sidebar || !adminMain) return;

                if (window.innerWidth > 992) {
                    var savedState = localStorage.getItem('polymedicReceptionistSidebarCollapsed');
                    if (savedState === '1') {
                        sidebar.classList.add('collapsed');
                        adminMain.classList.add('sidebar-collapsed');
                    } else {
                        sidebar.classList.remove('collapsed');
                        adminMain.classList.remove('sidebar-collapsed');
                    }
                } else {
                    sidebar.classList.remove('collapsed');
                    adminMain.classList.remove('sidebar-collapsed');
                }
                updateCollapseButton();
            });
        })();

        // ============================================================
        // NOTIFICATION FUNCTIONS
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
                let iconClass = 'bi-bell-fill';
                let colorClass = 'system';
                
                if (item.type === 'appointment') {
                    iconClass = 'bi-calendar-check';
                    colorClass = 'appointment';
                } else if (item.type === 'lab') {
                    iconClass = 'bi-flask';
                    colorClass = 'lab';
                } else if (item.type === 'xray') {
                    iconClass = 'bi-x-ray';
                    colorClass = 'xray';
                } else if (item.type === 'billing') {
                    iconClass = 'bi-receipt';
                    colorClass = 'billing';
                } else if (item.type === 'payment') {
                    iconClass = 'bi-credit-card';
                    colorClass = 'payment';
                }
                
                const link = item.link || '#';
                
                html += `
                    <a href="${link}" class="notif-item ${unreadClass}" onclick="markNotificationRead(${item.id}, event)">
                        <div class="notif-icon-box ${colorClass}">
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
            fetch('/polymedic/public/receptionist/notifications/mark-read/' + id, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    updateNotificationBadge(data.unread_count);
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

        document.addEventListener('DOMContentLoaded', function() {
            fetchNotifications();
            setInterval(fetchNotifications, 15000);
        });
    </script>
</body>
</html>