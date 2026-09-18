<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>PolyMedic - Radiologist Dashboard</title>

    <!-- Main CSS -->
    <link href="<?= base_url('assets/css/AppointmentStyle.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/css/admin.css') ?>" rel="stylesheet">

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
        <aside class="admin-sidebar radiologist-sidebar" id="adminSidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <img src="<?= base_url('assets/images/logo4.png') ?>" alt="PolyMedic">
                    <span>PolyMedic<small>Radiology System</small></span>
                </div>
                <button class="sidebar-close" onclick="toggleSidebar()">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <nav class="sidebar-nav">
                <ul>
                    <!-- =========================================
                         SIDEBAR COLLAPSE BUTTON
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

                    <li class="nav-section">RADIOLOGY</li>

                    <li class="menu-item <?= current_url() == base_url('radiologist/dashboard') ? 'active' : '' ?>">
                        <a href="<?= base_url('radiologist/dashboard') ?>" class="menu-btn" data-tooltip="Dashboard">
                            <svg class="menu-icon-svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M14,10V22H4a2,2,0,0,1-2-2V10Z"></path>
                                <path d="M22,10V20a2,2,0,0,1-2,2H16V10Z"></path>
                                <path d="M22,4V8H2V4A2,2,0,0,1,4,2H20A2,2,0,0,1,22,4Z"></path>
                            </svg>
                            <span>Dashboard</span>
                            <?php if (current_url() == base_url('radiologist/dashboard')): ?>
                                <i class="bi bi-chevron-right menu-arrow"></i>
                            <?php endif; ?>
                        </a>
                    </li>

                    <li class="menu-item <?= strpos(current_url(), 'radiologist/examination') !== false ? 'active' : '' ?>">
                        <a href="<?= base_url('radiologist/examinations') ?>" class="menu-btn" data-tooltip="Examinations">
                            <svg class="menu-icon-svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm-1-13h2v6h-2zm0 8h2v2h-2z"/>
                            </svg>
                            <span>Examinations</span>
                            <?php
                            $xrayModel = new \App\Models\XrayExaminationModel();
                            $pendingCount = $xrayModel->where('status', 'pending')->countAllResults();
                            if ($pendingCount > 0):
                            ?>
                                <span class="badge-notif"><?= $pendingCount ?></span>
                            <?php endif; ?>
                            <?php if (strpos(current_url(), 'radiologist/examination') !== false): ?>
                                <i class="bi bi-chevron-right menu-arrow"></i>
                            <?php endif; ?>
                        </a>
                    </li>

                    <!-- =========================================
                         NOTIFICATIONS
                         Points at the full notifications page. The
                         badge is filled by JavaScript from the same
                         fetch the header dropdown uses, so both
                         always show the same count.
                         ========================================= -->
                    <li class="menu-item <?= strpos(current_url(), 'radiologist/notifications') !== false ? 'active' : '' ?>">
                        <a href="<?= base_url('radiologist/notifications') ?>" class="menu-btn" data-tooltip="Notifications">
                            <svg class="menu-icon-svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.63-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5S10.5 3.17 10.5 4v.68C7.64 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/>
                            </svg>
                            <span>Notifications</span>
                            <span class="badge-notif" id="sidebarNotifBadge" style="display: none;">0</span>
                            <?php if (strpos(current_url(), 'radiologist/notifications') !== false): ?>
                                <i class="bi bi-chevron-right menu-arrow"></i>
                            <?php endif; ?>
                        </a>
                    </li>

                    <li class="nav-divider"></li>

                    <li class="menu-item logout-item">
                        <a href="<?= base_url('logout') ?>" class="menu-btn" data-tooltip="Logout">
                            <svg class="menu-icon-svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M17.2929 14.2929C16.9024 14.6834 16.9024 15.3166 17.2929 15.7071C17.6834 16.0976 18.3166 16.0976 18.7071 15.7071L21.6201 12.7941C21.6351 12.7791 21.6497 12.7637 21.6637 12.748C21.87 12.5648 22 12.2976 22 12C22 11.7024 21.87 11.4352 21.6637 11.252C21.6497 11.2363 21.6351 11.2209 21.6201 11.2059L18.7071 8.29289C18.3166 7.90237 17.6834 7.90237 17.2929 8.29289C16.9024 8.68342 16.9024 9.31658 17.2929 9.70711L18.5858 11H13C12.4477 11 12 11.4477 12 12C12 12.5523 12.4477 13 13 13H18.5858L17.2929 14.2929Z"/>
                                <path d="M5 2C3.34315 2 2 3.34315 2 5V19C2 20.6569 3.34315 22 5 22H14.5C15.8807 22 17 20.8807 17 19.5V16.7326C16.8519 16.647 16.7125 16.5409 16.5858 16.4142C15.9314 15.7598 15.8253 14.7649 16.2674 14H13C11.8954 14 11 13.1046 11 12C11 10.8954 11.8954 10 13 10H16.2674C15.8253 9.23514 15.9314 8.24015 16.5858 7.58579C16.7125 7.4591 16.8519 7.35296 17 7.26738V4.5C17 3.11929 15.8807 2 14.5 2H5Z"/>
                            </svg>
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
            <header class="admin-header radiologist-header">
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
                                'Examinations' => 'xray-icon.png',
                                'View X-Ray Examination' => 'xray-icon.png',
                                'Reports' => 'reports-icon.png'
                            ];
                            $iconFile = $iconMap[$pageTitle] ?? 'xray-icon.png';
                        ?>
                        <img src="<?= base_url('assets/images/' . $iconFile) ?>" alt="<?= esc($pageTitle) ?>" class="header-title-icon">
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
                                    <a href="<?= base_url('radiologist/notifications') ?>" class="text-primary fw-semibold small text-decoration-none">
                                        View All Notifications <i class="bi bi-arrow-right ms-1"></i>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <span class="divider-icon">|</span>
                        <div class="header-user">
                            <div class="avatar-small radiologist-avatar">
                                <i class="bi bi-person-fill"></i>
                            </div>
                            <div class="user-details">
                                <span class="user-name-header"><?= session()->get('full_name') ?? 'Radiologist' ?></span>
                                <span class="user-role-header radiologist-role">Radiologist</span>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <div class="admin-content">
                <?php echo $this->renderSection('radiologistContent'); ?>
            </div>
        </main>
    </div>

    <style>
    /* ============================================
       RADIOLOGIST SIDEBAR - FULLY RESPONSIVE
       Mirrors the receptionist sidebar exactly.
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
    .radiologist-sidebar {
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

    .radiologist-sidebar::-webkit-scrollbar {
        width: 4px;
    }

    .radiologist-sidebar::-webkit-scrollbar-thumb {
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

    /* ===== COLLAPSE BUTTON ===== */
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
    .radiologist-sidebar .sidebar-nav ul li.menu-item:not(.active) a.menu-btn,
    .radiologist-sidebar .sidebar-nav ul li.menu-item:not(.active) a.menu-btn span {
        color: var(--text-blue) !important;
    }

    .radiologist-sidebar .sidebar-nav ul li.menu-item:not(.active) a.menu-btn .menu-icon-svg {
        color: var(--icon-gray) !important;
    }

    /* ===== ACTIVE MENU ITEM ===== */
    .radiologist-sidebar .sidebar-nav ul li.menu-item.active a.menu-btn,
    .radiologist-sidebar .sidebar-nav ul li.menu-item.active a.menu-btn span {
        color: #ffffff !important;
    }

    .radiologist-sidebar .sidebar-nav ul li.menu-item.active a.menu-btn .menu-icon-svg {
        color: #ffffff !important;
    }

    .radiologist-sidebar .sidebar-nav ul li a,
    .radiologist-sidebar .sidebar-nav ul li.active a {
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

    .menu-btn:hover .menu-icon-svg {
        color: var(--active-blue) !important;
        transform: scale(1.05);
    }

    .menu-item.active .menu-btn {
        background: var(--active-blue) !important;
        color: #ffffff !important;
        box-shadow: 0 4px 12px rgba(25, 118, 210, 0.3) !important;
    }

    /* SVG Icon Styles */
    .menu-icon-svg {
        width: 24px;
        height: 24px;
        flex-shrink: 0;
        transition: color 0.2s ease, transform 0.2s ease;
    }

    /* Menu label */
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

    .logout-item .menu-btn:hover .menu-icon-svg {
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

    /* ===== HEADER ===== */
    .radiologist-header {
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

    .radiologist-avatar {
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

    .radiologist-role {
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

    /* ============================================
       COLLAPSED SIDEBAR (DESKTOP ONLY)
       ============================================ */

    @media (min-width: 993px) {

        .radiologist-sidebar.collapsed {
            width: var(--sidebar-collapsed-width);
        }

        .radiologist-sidebar.collapsed .sidebar-header {
            justify-content: center;
            padding: 1rem 0.5rem;
        }

        .radiologist-sidebar.collapsed .sidebar-logo {
            gap: 0;
            justify-content: center;
            width: 100%;
        }

        .radiologist-sidebar.collapsed .sidebar-logo img {
            width: 40px;
            height: 40px;
        }

        .radiologist-sidebar.collapsed .sidebar-logo span {
            opacity: 0;
            max-width: 0;
        }

        .radiologist-sidebar.collapsed .sidebar-nav {
            padding: 0.9rem 0.6rem;
        }

        .radiologist-sidebar.collapsed .sidebar-toggle-btn {
            justify-content: center;
            padding: 0.65rem 0;
            gap: 0;
            background: #f1f5f9;
            border-color: #e2e8f0;
            display: flex;
            align-items: center;
        }

        .radiologist-sidebar.collapsed .sidebar-toggle-btn span {
            opacity: 0;
            max-width: 0;
            display: none;
        }

        .radiologist-sidebar.collapsed .sidebar-toggle-btn i {
            margin: 0;
            transform: rotate(180deg);
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 24px;
        }

        .radiologist-sidebar.collapsed .nav-section {
            font-size: 0.64rem;
            height: 13px;
            padding: 0;
            margin: 0.55rem 0;
            opacity: 0;
        }

        .radiologist-sidebar.collapsed .menu-btn {
            justify-content: center;
            width: 100%;
            min-height: 44px;
            padding: 0.7rem 0;
            gap: 0;
            display: flex;
            align-items: center;
        }

        .radiologist-sidebar.collapsed .menu-btn > span {
            opacity: 0;
            max-width: 0;
            display: none;
        }

        .radiologist-sidebar.collapsed .menu-icon-svg {
            width: 24px;
            height: 24px;
            margin: 0;
            display: block;
            flex-shrink: 0;
        }

        .radiologist-sidebar.collapsed .menu-arrow {
            opacity: 0;
            max-width: 0;
            overflow: hidden;
            display: none;
        }

        .radiologist-sidebar.collapsed .menu-item {
            position: relative;
        }

        .radiologist-sidebar.collapsed .badge-notif {
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
        .radiologist-sidebar.collapsed .menu-btn:hover::after {
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

        .radiologist-sidebar.collapsed .sidebar-toggle-btn:hover::after {
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
        .admin-content {
            padding: 1.5rem;
        }
    }

    @media (max-width: 1200px) {
        .radiologist-header {
            padding: 0.75rem 1.25rem;
        }

        .admin-content {
            padding: 1.25rem;
        }
    }

    @media (max-width: 992px) {
        .radiologist-sidebar {
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

        .radiologist-sidebar.open {
            left: 0;
            box-shadow: 4px 0 30px rgba(0,0,0,0.1) !important;
        }

        /* Force expanded state on mobile */
        .radiologist-sidebar.collapsed {
            width: var(--sidebar-width) !important;
        }

        .radiologist-sidebar.collapsed .sidebar-header {
            justify-content: space-between !important;
            padding: 1.5rem 1.5rem !important;
        }

        .radiologist-sidebar.collapsed .sidebar-logo {
            gap: 0.75rem !important;
            justify-content: flex-start !important;
        }

        .radiologist-sidebar.collapsed .sidebar-logo span {
            opacity: 1 !important;
            max-width: 200px !important;
        }

        .radiologist-sidebar.collapsed .nav-section {
            font-size: 0.65rem !important;
            height: auto !important;
            padding: 1rem 0.75rem 0.25rem !important;
            margin: 0 !important;
            opacity: 1 !important;
        }

        .radiologist-sidebar.collapsed .menu-btn {
            justify-content: flex-start !important;
            padding: 0.75rem 1rem !important;
            gap: 0.75rem !important;
        }

        .radiologist-sidebar.collapsed .menu-btn > span {
            opacity: 1 !important;
            max-width: 200px !important;
        }

        .radiologist-sidebar.collapsed .menu-arrow {
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

        .radiologist-header {
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
        .radiologist-header {
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

        .menu-icon-svg {
            width: 20px;
            height: 20px;
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
    }

    @media (max-width: 576px) {
        .radiologist-header {
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
    }

    /* Disable transitions only during the initial state application
       on page load, so restoring a saved collapsed/expanded state
       never itself animates — only user clicks do. */
    .radiologist-sidebar.no-transition,
    .radiologist-sidebar.no-transition *,
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

        // ============================================================
        // SIDEBAR COLLAPSE / EXPAND (desktop)
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

                var savedState = localStorage.getItem('polymedicRadiologistSidebarCollapsed');

                if (savedState === null) {
                    localStorage.setItem('polymedicRadiologistSidebarCollapsed', '0');
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
                    localStorage.setItem('polymedicRadiologistSidebarCollapsed', '0');
                } else {
                    sidebar.classList.add('collapsed');
                    adminMain.classList.add('sidebar-collapsed');
                    localStorage.setItem('polymedicRadiologistSidebarCollapsed', '1');
                }

                updateCollapseButton();
            };

            window.addEventListener('resize', function() {
                var elements = getSidebarElements();
                var sidebar = elements.sidebar;
                var adminMain = elements.adminMain;

                if (!sidebar || !adminMain) return;

                if (window.innerWidth > 992) {
                    var savedState = localStorage.getItem('polymedicRadiologistSidebarCollapsed');
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

        const NOTIF_BASE = '<?= rtrim(base_url('radiologist/notifications'), '/') ?>';

        function fetchNotifications() {
            fetch(NOTIF_BASE + '/fetch', {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(response => {
                if (!response.ok) {
                    console.warn('Notification fetch rejected:', response.status);
                    return null;
                }
                return response.json();
            })
            .then(data => {
                if (data && data.status === 'success') {
                    updateNotificationBadge(data.unread_count);
                    updateSidebarNotificationBadge(data.unread_count);
                    renderNotificationDropdown(data.notifications);
                }
            })
            .catch(err => console.error('Error fetching notifications:', err));
        }

        function updateNotificationBadge(count) {
            const badge = document.getElementById('notifBadge');
            if (!badge) return;

            if (count > 0) {
                badge.textContent = count > 99 ? '99+' : count;
                badge.style.display = 'flex';
                badge.classList.add('has-unread');
            } else {
                badge.style.display = 'none';
                badge.classList.remove('has-unread');
            }
        }

        function updateSidebarNotificationBadge(count) {
            const badge = document.getElementById('sidebarNotifBadge');
            if (!badge) return;

            if (count > 0) {
                badge.textContent = count > 99 ? '99+' : count;
                badge.style.display = 'inline-flex';
            } else {
                badge.style.display = 'none';
            }
        }

        function renderNotificationDropdown(notifications) {
            const listContainer = document.getElementById('notifDropdownList');
            if (!listContainer) return;

            if (!notifications || notifications.length === 0) {
                listContainer.innerHTML = '<div class="p-3 text-center text-muted small">'
                    + '<i class="bi bi-bell-slash d-block fs-4 mb-1"></i>No notifications yet</div>';
                return;
            }

            const icons = {
                appointment: ['bi-calendar-check', 'appointment'],
                xray:        ['bi-x-ray',          'xray'],
                lab:         ['bi-flask',          'lab'],
                billing:     ['bi-receipt',        'billing'],
                payment:     ['bi-credit-card',    'payment']
            };

            let html = '';
            notifications.forEach(item => {
                const unreadClass = item.is_read == 0 ? 'unread' : '';
                const pair        = icons[item.type] || ['bi-bell-fill', 'system'];
                const iconClass   = pair[0];
                const colorClass  = pair[1];

                const openable = !!item.can_open;

                html += `
                    <div class="notif-item ${unreadClass}" onclick="openNotification(${item.id}, ${openable ? 'true' : 'false'}, event)">
                        <div class="notif-icon-box ${colorClass}">
                            <i class="bi ${iconClass}"></i>
                        </div>
                        <div class="notif-content">
                            <div class="notif-title">${escapeHtml(item.title)}</div>
                            <div class="notif-msg">${escapeHtml(item.message)}</div>
                            <div class="notif-time"><i class="bi bi-clock me-1"></i>${escapeHtml(item.time_ago || '')}</div>
                        </div>
                    </div>
                `;
            });

            listContainer.innerHTML = html;
        }

        function openNotification(id, canOpen, event) {
            if (event) {
                event.preventDefault();
                event.stopPropagation();
            }

            fetch(NOTIF_BASE + '/mark-read/' + id, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(response => response.json().then(data => ({
                ok: response.ok, status: response.status, data: data
            })))
            .then(result => {
                if (!result.ok) {
                    console.error('Notification rejected:', result.status, result.data);
                    return;
                }

                updateNotificationBadge(result.data.unread_count);
                updateSidebarNotificationBadge(result.data.unread_count);

                if (canOpen && result.data.can_open && result.data.target_link) {
                    window.location.href = result.data.target_link;
                } else {
                    fetchNotifications();
                }
            })
            .catch(err => console.error('Error opening notification:', err));
        }

        function markAllNotificationsRead(event) {
            if (event) event.stopPropagation();

            fetch(NOTIF_BASE + '/mark-all-read', {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    updateNotificationBadge(0);
                    updateSidebarNotificationBadge(0);
                    fetchNotifications();
                }
            })
            .catch(err => console.error('Error marking notifications read:', err));
        }

        function escapeHtml(text) {
            if (!text) return '';
            return String(text)
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