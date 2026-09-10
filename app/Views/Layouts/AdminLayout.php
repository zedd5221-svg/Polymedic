<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>PolyMedic - Admin Dashboard</title>

    <!-- Main CSS -->
    <link href="/polymedic/public/assets/css/AppointmentStyle.css" rel="stylesheet">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- AOS -->
    <script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>

</head>

<body>

<script>
    AOS.init({
        duration: 800,
        once: true
    });
</script>

<div class="admin-wrapper">

    <!-- =====================================================
         SIDEBAR
         ===================================================== -->

    <aside class="admin-sidebar" id="adminSidebar">

        <!-- SIDEBAR HEADER -->
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <img src="/polymedic/public/assets/images/logo4.png" alt="PolyMedic">
                <span>
                    PolyMedic
                    <small>Diagnostic System</small>
                </span>
            </div>
        </div>

        <!-- =================================================
             NAVIGATION
             ================================================= -->

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

                <!-- =========================================
                     MAIN
                     ========================================= -->

                <li class="nav-section">Main</li>

                <!-- DASHBOARD -->
                <li class="menu-item <?= current_url() == base_url('admin/dashboard') ? 'active' : '' ?>">
                    <a href="/polymedic/public/admin/dashboard" class="menu-btn" data-tooltip="Dashboard">
                        <i class="bi bi-grid-1x2-fill menu-icon"></i>
                        <span>Dashboard</span>
                        <?php if (current_url() == base_url('admin/dashboard')): ?>
                            <i class="bi bi-chevron-right menu-arrow"></i>
                        <?php endif; ?>
                    </a>
                </li>

                <!-- APPOINTMENTS -->
                <li class="menu-item <?= current_url() == base_url('admin/appointments') ? 'active' : '' ?>">
                    <a href="/polymedic/public/admin/appointments" class="menu-btn" data-tooltip="Appointments">
                        <i class="bi bi-calendar-check menu-icon"></i>
                        <span>Appointments</span>
                        <?php if (current_url() == base_url('admin/appointments')): ?>
                            <i class="bi bi-chevron-right menu-arrow"></i>
                        <?php endif; ?>
                    </a>
                </li>

                <!-- PATIENTS -->
                <li class="menu-item <?= current_url() == base_url('admin/patients') ? 'active' : '' ?>">
                    <a href="/polymedic/public/admin/patients" class="menu-btn" data-tooltip="Patients">
                        <i class="bi bi-people-fill menu-icon"></i>
                        <span>Patients</span>
                        <?php if (current_url() == base_url('admin/patients')): ?>
                            <i class="bi bi-chevron-right menu-arrow"></i>
                        <?php endif; ?>
                    </a>
                </li>

                <!-- SERVICES -->
                <li class="menu-item <?= current_url() == base_url('admin/services') ? 'active' : '' ?>">
                    <a href="/polymedic/public/admin/services" class="menu-btn" data-tooltip="Services">
                        <i class="bi bi-grid-3x3-gap-fill menu-icon"></i>
                        <span>Services</span>
                        <?php if (current_url() == base_url('admin/services')): ?>
                            <i class="bi bi-chevron-right menu-arrow"></i>
                        <?php endif; ?>
                    </a>
                </li>

                <!-- =========================================
                     ADMIN
                     ========================================= -->

                <li class="nav-section">Admin</li>

                <!-- USER MANAGEMENT -->
                <li class="menu-item <?= current_url() == base_url('admin/users') ? 'active' : '' ?>">
                    <a href="/polymedic/public/admin/users" class="menu-btn" data-tooltip="User Management">
                        <i class="bi bi-person-gear menu-icon"></i>
                        <span>User Management</span>
                        <?php if (current_url() == base_url('admin/users')): ?>
                            <i class="bi bi-chevron-right menu-arrow"></i>
                        <?php endif; ?>
                    </a>
                </li>

                <!-- NOTIFICATIONS -->
                <li class="menu-item <?= current_url() == base_url('admin/notifications') ? 'active' : '' ?>">
                    <a href="/polymedic/public/admin/notifications" class="menu-btn" data-tooltip="Notifications">
                        <i class="bi bi-bell-fill menu-icon"></i>
                        <span>Notifications</span>

                        <?php
                        $unreadCount = (new \App\Models\NotificationModel())->getUnreadCount();
                        if ($unreadCount > 0):
                        ?>
                            <span class="badge-notif"><?= $unreadCount ?></span>
                        <?php endif; ?>

                        <?php if (current_url() == base_url('admin/notifications')): ?>
                            <i class="bi bi-chevron-right menu-arrow"></i>
                        <?php endif; ?>
                    </a>
                </li>

                <!-- DIVIDER -->
                <li class="nav-divider"></li>

                <!-- LOGOUT -->
                <li class="menu-item logout-item">
                    <a href="/polymedic/public/logout" class="menu-btn" data-tooltip="Logout">
                        <i class="bi bi-box-arrow-right menu-icon"></i>
                        <span>Logout</span>
                    </a>
                </li>

            </ul>
        </nav>

    </aside>

    <!-- =====================================================
         MAIN
         ===================================================== -->

    <main class="admin-main" id="adminMain">

        <!-- =================================================
             HEADER
             ================================================= -->

        <header class="admin-header">

            <!-- HEADER LEFT -->
            <div class="header-left">

                <!-- PAGE TITLE -->
                <div class="header-title-group">

                    <?php
                    $pageTitle = $this->renderSection('pageTitle') ?: 'Dashboard';

                    $iconMap = [
                        'Dashboard' => 'statisctics.png',
                        'Appointments' => 'appointment1.png',
                        'Patients' => 'sick-patient.png',
                        'Diagnostic Requests' => 'stethoscope.png',
                        'Laboratory Findings' => 'lab-icon.png',
                        'User Management' => 'user-management-icon.png',
                        'Notifications' => 'appointment1.png',
                        'Radiologist Dashboard' => 'xray-icon.png',
                        'X-Ray Examinations' => 'xray-icon.png',
                        'View X-Ray Examination' => 'xray-icon.png'
                    ];

                    if (strpos($pageTitle, 'Radiologist') !== false) {
                        $iconFile = 'xray-icon.png';
                    } elseif (strpos($pageTitle, 'X-Ray') !== false) {
                        $iconFile = 'xray-icon.png';
                    } else {
                        $iconFile = $iconMap[$pageTitle] ?? 'statisctics.png';
                    }
                    ?>

                    <img src="/polymedic/public/assets/images/<?= $iconFile ?>"
                         alt="<?= esc($pageTitle) ?>"
                         class="header-title-icon">

                    <h4 class="page-title-header"><?= esc($pageTitle) ?></h4>

                </div>

            </div>

            <!-- =================================================
                 HEADER RIGHT
                 ================================================= -->

            <div class="header-right">

                <div class="header-info-group">

                    <!-- DATE/TIME -->
                    <div class="header-datetime">
                        <i class="bi bi-clock"></i>
                        <span><?= date('D, M j · h:i:s A') ?></span>
                    </div>

                    <span class="divider-icon">|</span>

                    <!-- =================================================
                         NOTIFICATIONS
                         ================================================= -->

                    <div class="dropdown notif-dropdown-wrapper">

                        <button class="notif-btn"
                                type="button"
                                id="notifDropdownBtn"
                                data-bs-toggle="dropdown"
                                aria-expanded="false">
                            <i class="bi bi-bell-fill"></i>
                            <span class="notif-badge" id="notifBadge" style="display:none;">0</span>
                        </button>

                        <div class="dropdown-menu dropdown-menu-end notif-dropdown-menu shadow-lg border-0"
                             aria-labelledby="notifDropdownBtn">

                            <!-- NOTIFICATION HEADER -->
                            <div class="notif-dropdown-header d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-bell text-primary"></i>
                                    <span class="fw-bold text-dark fs-6">Notifications</span>
                                </div>
                                <button type="button"
                                        class="btn btn-link btn-sm p-0 text-primary text-decoration-none small"
                                        onclick="markAllNotificationsRead(event)">
                                    Mark all read
                                </button>
                            </div>

                            <!-- NOTIFICATION BODY -->
                            <div class="notif-dropdown-body" id="notifDropdownList">
                                <div class="p-3 text-center text-muted small">
                                    <div class="spinner-border spinner-border-sm text-primary me-1" role="status"></div>
                                    Loading notifications...
                                </div>
                            </div>

                            <!-- NOTIFICATION FOOTER -->
                            <div class="notif-dropdown-footer text-center">
                                <a href="/polymedic/public/admin/notifications"
                                   class="text-primary fw-semibold small text-decoration-none">
                                    View All Notifications
                                    <i class="bi bi-arrow-right ms-1"></i>
                                </a>
                            </div>

                        </div>

                    </div>

                    <span class="divider-icon">|</span>

                    <!-- USER -->
                    <div class="header-user">

                        <div class="avatar-small">
                            <i class="bi bi-person-fill"></i>
                        </div>

                        <div class="user-details">
                            <span class="user-name-header">Admin User</span>
                            <span class="user-role-header">Administrator</span>
                        </div>

                    </div>

                </div>

            </div>

        </header>

        <!-- =================================================
             PAGE CONTENT
             ================================================= -->

        <div class="admin-content">
            <?= $this->renderSection('adminContent') ?>
        </div>

    </main>

</div>

<!-- =========================================================
     SIDEBAR + HEADER CSS
     ========================================================= -->

<style>
/* =========================================================
   VARIABLES
   ========================================================= */

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

    /* Single shared timing so every collapse-related property
       animates in lockstep — this is what makes clicking a menu
       item while collapsed/expanded look smooth instead of
       having the label text snap in/out abruptly. */
    --sidebar-ease: cubic-bezier(0.4, 0, 0.2, 1);
    --sidebar-speed: 0.32s;
}

/* =========================================================
   GLOBAL
   ========================================================= */

* {
    box-sizing: border-box;
}

/* =========================================================
   SIDEBAR
   ========================================================= */

.admin-sidebar {
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
    box-shadow: 2px 0 20px rgba(0, 0, 0, 0.06);
    border-right: 1px solid #e5e7eb !important;
    transition: width var(--sidebar-speed) var(--sidebar-ease);
}

.admin-sidebar::-webkit-scrollbar {
    width: 4px;
}

.admin-sidebar::-webkit-scrollbar-thumb {
    background: #e5e7eb;
    border-radius: 10px;
}

/* =========================================================
   SIDEBAR HEADER
   ========================================================= */

.sidebar-header {
    height: 82px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 1.35rem;
    border-bottom: 1px solid #f3f4f6 !important;
    transition: padding var(--sidebar-speed) var(--sidebar-ease),
                justify-content var(--sidebar-speed) var(--sidebar-ease);
}

.sidebar-logo {
    display: flex;
    align-items: center;
    gap: 0.7rem;
    min-width: 0;
    transition: gap var(--sidebar-speed) var(--sidebar-ease);
}

.sidebar-logo img {
    width: 42px;
    height: 42px;
    object-fit: contain;
    flex-shrink: 0;
    transition: width var(--sidebar-speed) var(--sidebar-ease),
                height var(--sidebar-speed) var(--sidebar-ease);
}

.sidebar-logo span {
    font-size: 1.08rem;
    font-weight: 700;
    color: #111827 !important;
    letter-spacing: 0.3px;
    line-height: 1.15;
    white-space: nowrap;
    opacity: 1;
    max-width: 200px;
    overflow: hidden;
    display: inline-block;
    transition: opacity calc(var(--sidebar-speed) * 0.6) var(--sidebar-ease),
                max-width var(--sidebar-speed) var(--sidebar-ease);
}

.sidebar-logo span small {
    display: block;
    margin-top: 3px;
    font-size: 0.62rem;
    font-weight: 400;
    color: #6b7280 !important;
}

/* =========================================================
   SIDEBAR NAV
   ========================================================= */

.sidebar-nav {
    padding: 0.9rem 0.75rem 1.5rem;
}

.sidebar-nav ul {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 0.22rem;
}

/* =========================================================
   COLLAPSE BUTTON
   ========================================================= */

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

/* =========================================================
   NAV SECTION
   ========================================================= */

.sidebar-nav .nav-section {
    font-size: 0.64rem;
    text-transform: uppercase;
    letter-spacing: 1.2px;
    color: #9ca3af !important;
    padding: 1rem 0.75rem 0.35rem;
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

/* =========================================================
   MENU BUTTON
   ========================================================= */

.menu-btn {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    width: 100%;
    min-height: 44px;
    padding: 0.7rem 0.95rem;
    border-radius: 9px !important;
    text-decoration: none;
    font-size: 0.85rem;
    font-weight: 600;
    background: transparent;
    cursor: pointer;
    position: relative;
    transition: background 0.2s ease,
                color 0.2s ease,
                box-shadow 0.2s ease,
                gap var(--sidebar-speed) var(--sidebar-ease),
                padding var(--sidebar-speed) var(--sidebar-ease),
                justify-content var(--sidebar-speed) var(--sidebar-ease);
}

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

/* Menu label — animates on width/opacity together with the
   sidebar collapse, instead of the old instant display:none. */
.menu-btn > span {
    white-space: nowrap;
    opacity: 1;
    max-width: 200px;
    overflow: hidden;
    display: inline-block;
    transition: opacity calc(var(--sidebar-speed) * 0.6) var(--sidebar-ease),
                max-width var(--sidebar-speed) var(--sidebar-ease);
}

/* =========================================================
   NORMAL MENU COLORS
   ========================================================= */

.admin-sidebar .sidebar-nav ul li.menu-item:not(.active) a.menu-btn,
.admin-sidebar .sidebar-nav ul li.menu-item:not(.active) a.menu-btn span {
    color: var(--text-blue) !important;
}

.admin-sidebar .sidebar-nav ul li.menu-item:not(.active) a.menu-btn .menu-icon {
    color: var(--icon-gray) !important;
}

/* =========================================================
   HOVER
   ========================================================= */

.menu-btn:hover {
    background: var(--active-blue-light) !important;
    color: var(--active-blue) !important;
}

.menu-btn:hover .menu-icon {
    color: var(--active-blue) !important;
    transform: scale(1.05);
}

/* =========================================================
   ACTIVE
   ========================================================= */

.menu-item.active .menu-btn {
    background: var(--active-blue) !important;
    color: #ffffff !important;
    box-shadow: 0 4px 12px rgba(25, 118, 210, 0.28) !important;
}

.menu-item.active .menu-btn span {
    color: #ffffff !important;
}

.menu-item.active .menu-icon {
    color: #ffffff !important;
}

.menu-arrow {
    font-size: 0.85rem;
    color: #ffffff !important;
    margin-left: auto;
    flex-shrink: 0;
    opacity: 1;
    transition: opacity calc(var(--sidebar-speed) * 0.6) var(--sidebar-ease);
}

/* =========================================================
   REMOVE OLD BORDERS
   ========================================================= */

.admin-sidebar .sidebar-nav ul li a,
.admin-sidebar .sidebar-nav ul li.active a {
    border: none !important;
    outline: none !important;
}

/* =========================================================
   NOTIFICATION BADGE
   ========================================================= */

.badge-notif {
    margin-left: auto;
    background: #ffffff !important;
    color: var(--text-blue) !important;
    font-size: 0.62rem;
    font-weight: 700;
    padding: 0.15rem 0.45rem;
    border-radius: 30px;
    min-width: 20px;
    height: 20px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    flex-shrink: 0;
    opacity: 1;
    transition: opacity calc(var(--sidebar-speed) * 0.6) var(--sidebar-ease),
                transform var(--sidebar-speed) var(--sidebar-ease);
}

.menu-item.active .badge-notif {
    display: none !important;
}

/* =========================================================
   DIVIDER
   ========================================================= */

.nav-divider {
    height: 1px;
    background: #f3f4f6 !important;
    margin: 0.55rem 0;
}

/* =========================================================
   LOGOUT
   ========================================================= */

.logout-item {
    margin-top: 0.2rem;
}

.logout-item .menu-btn:hover {
    background: #fee2e2 !important;
    color: #dc2626 !important;
}

.logout-item .menu-btn:hover .menu-icon {
    color: #dc2626 !important;
}

/* =========================================================
   COLLAPSED SIDEBAR (DESKTOP ONLY)
   All the label/element hiding below uses the SAME
   --sidebar-speed/--sidebar-ease as the base rules above,
   and animates opacity + max-width instead of snapping with
   width:0/display:none, so clicking any menu item mid-collapse
   or mid-expand doesn't cause a jump — it finishes the tween.
   ========================================================= */

@media (min-width: 993px) {

    .admin-sidebar.collapsed {
        width: var(--sidebar-collapsed-width);
    }

    .admin-sidebar.collapsed .sidebar-header {
        justify-content: center;
        padding: 1rem 0.5rem;
    }

    .admin-sidebar.collapsed .sidebar-logo {
        gap: 0;
        justify-content: center;
        width: 100%;
    }

    .admin-sidebar.collapsed .sidebar-logo img {
        width: 40px;
        height: 40px;
    }

    .admin-sidebar.collapsed .sidebar-logo span {
        opacity: 0;
        max-width: 0;
    }

    .admin-sidebar.collapsed .sidebar-nav {
        padding: 0.9rem 0.6rem;
    }

    /* Toggle button - centered */
    .admin-sidebar.collapsed .sidebar-toggle-btn {
        justify-content: center;
        padding: 0.65rem 0;
        gap: 0;
        background: #f1f5f9;
        border-color: #e2e8f0;
        display: flex;
        align-items: center;
    }

    .admin-sidebar.collapsed .sidebar-toggle-btn span {
        opacity: 0;
        max-width: 0;
        display: none;
    }

    .admin-sidebar.collapsed .sidebar-toggle-btn i {
        margin: 0;
        transform: rotate(180deg);
        font-size: 1.1rem;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 24px;
    }

    .admin-sidebar.collapsed .nav-section {
        font-size: 0.64rem;
        height: 13px;
        padding: 0;
        margin: 0.55rem 0;
        opacity: 0;
    }

    /* Menu buttons - centered icons */
    .admin-sidebar.collapsed .menu-btn {
        justify-content: center;
        width: 100%;
        min-height: 44px;
        padding: 0.7rem 0;
        gap: 0;
        display: flex;
        align-items: center;
    }

    .admin-sidebar.collapsed .menu-btn > span {
        opacity: 0;
        max-width: 0;
        display: none;
    }

    /* Center the icon properly */
    .admin-sidebar.collapsed .menu-icon {
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

    .admin-sidebar.collapsed .menu-arrow {
        opacity: 0;
        max-width: 0;
        overflow: hidden;
        display: none;
    }

    .admin-sidebar.collapsed .menu-item {
        position: relative;
    }

    .admin-sidebar.collapsed .badge-notif {
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
    .admin-sidebar.collapsed .menu-btn:hover::after {
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

    .admin-sidebar.collapsed .sidebar-toggle-btn:hover::after {
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

/* =========================================================
   MOBILE RESPONSIVE - SIDEBAR ALWAYS EXPANDED
   ========================================================= */

@media (max-width: 992px) {

    .admin-sidebar {
        width: var(--sidebar-width);
        position: fixed;
        left: 0;
        top: 0;
        bottom: 0;
        z-index: 1000;
    }

    /* Hide collapse button on mobile */
    .sidebar-toggle-item {
        display: none !important;
    }

    /* Force sidebar expanded on mobile */
    .admin-sidebar.collapsed {
        width: var(--sidebar-width) !important;
    }

    .admin-sidebar.collapsed .sidebar-header {
        justify-content: space-between !important;
        padding: 1rem 1.35rem !important;
    }

    .admin-sidebar.collapsed .sidebar-logo {
        gap: 0.7rem !important;
        justify-content: flex-start !important;
    }

    .admin-sidebar.collapsed .sidebar-logo span {
        opacity: 1 !important;
        max-width: 200px !important;
    }

    .admin-sidebar.collapsed .nav-section {
        font-size: 0.64rem !important;
        height: auto !important;
        padding: 1rem 0.75rem 0.35rem !important;
        margin: 0 !important;
        opacity: 1 !important;
    }

    .admin-sidebar.collapsed .menu-btn {
        justify-content: flex-start !important;
        padding: 0.7rem 0.95rem !important;
        gap: 0.75rem !important;
    }

    .admin-sidebar.collapsed .menu-btn > span {
        opacity: 1 !important;
        max-width: 200px !important;
    }

    .admin-sidebar.collapsed .menu-arrow {
        opacity: 1 !important;
        max-width: 20px !important;
        display: inline-block !important;
    }

    .admin-sidebar.collapsed .sidebar-toggle-btn {
        display: none !important;
    }

    /* Main content takes full width */
    .admin-main {
        margin-left: 0 !important;
        width: 100% !important;
    }

    .admin-main.sidebar-collapsed {
        margin-left: 0 !important;
        width: 100% !important;
    }

    .admin-header {
        padding: 0.75rem 1rem;
    }

    .admin-content {
        padding: 1rem;
    }

    .header-info-group .divider-icon {
        display: none;
    }

}

/* =========================================================
   MAIN CONTENT
   ========================================================= */

.admin-main {
    margin-left: var(--sidebar-width);
    width: calc(100% - var(--sidebar-width));
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    background: var(--bg-light);
    transition: margin-left var(--sidebar-speed) var(--sidebar-ease),
                width var(--sidebar-speed) var(--sidebar-ease);
}

.admin-main.sidebar-collapsed {
    margin-left: var(--sidebar-collapsed-width);
    width: calc(100% - var(--sidebar-collapsed-width));
}

/* =========================================================
   HEADER
   ========================================================= */

.admin-header {
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
}

.header-left {
    display: flex;
    align-items: center;
    gap: 1rem;
    min-width: 0;
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

/* =========================================================
   HEADER RIGHT
   ========================================================= */

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
}

.divider-icon {
    color: #d1d5db !important;
    font-size: 0.8rem;
}

/* =========================================================
   NOTIFICATION BUTTON
   ========================================================= */

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
    color: #ffffff !important;
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
}

/* =========================================================
   NOTIFICATION DROPDOWN
   ========================================================= */

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
}

.notif-item:hover {
    background: #f8fafc !important;
}

.notif-item.unread {
    background: #f8fafc !important;
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
    flex-shrink: 0;
}

.notif-icon-box.appointment {
    background: #e3f2fd !important;
    color: #1976d2 !important;
}

.notif-icon-box.xray {
    background: #e3f2fd !important;
    color: #1976d2 !important;
}

.notif-icon-box.system {
    background: #fef3c7 !important;
    color: #d97706 !important;
}

.notif-icon-box.lab {
    background: #e8f5e9 !important;
    color: #28a745 !important;
}

.notif-icon-box.billing {
    background: #fff3e0 !important;
    color: #ff6b00 !important;
}

.notif-icon-box.payment {
    background: #ccfbf1 !important;
    color: #0d9488 !important;
}

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
}

.notif-time {
    font-size: 0.68rem;
    color: #9ca3af !important;
}

/* =========================================================
   USER
   ========================================================= */

.header-user {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.1rem 0.3rem;
    border-radius: 8px;
}

.avatar-small {
    background: var(--active-blue-light) !important;
    color: var(--active-blue) !important;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
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
}

.user-role-header {
    font-size: 0.58rem;
    color: #6b7280 !important;
}

/* =========================================================
   CONTENT
   ========================================================= */

.admin-content {
    flex: 1;
    padding: 1.5rem 2rem 2rem;
    width: 100%;
    overflow-x: hidden;
}

/* =========================================================
   SMALLER SCREENS
   ========================================================= */

@media (max-width: 768px) {

    .admin-header {
        padding: 0.5rem 0.75rem;
        min-height: 52px;
    }

    .header-title-group .header-title-icon {
        width: 20px;
        height: 20px;
    }

    .page-title-header {
        font-size: 0.85rem;
        max-width: 180px;
    }

    .header-datetime {
        display: none;
    }

    .user-details {
        display: none;
    }

    .avatar-small {
        width: 30px;
        height: 30px;
    }

    .admin-content {
        padding: 0.75rem;
    }

    .notif-dropdown-menu {
        width: 300px;
    }

}

@media (max-width: 576px) {

    .admin-header {
        padding: 0.4rem 0.6rem;
        min-height: 48px;
    }

    .header-title-group {
        gap: 0.35rem;
    }

    .header-title-group .header-title-icon {
        width: 18px;
        height: 18px;
    }

    .page-title-header {
        font-size: 0.72rem;
        max-width: 130px;
    }

    .notif-btn {
        font-size: 1rem;
        padding: 0.25rem 0.4rem;
    }

    .avatar-small {
        width: 28px;
        height: 28px;
    }

    .notif-dropdown-menu {
        width: 270px;
    }

    .admin-content {
        padding: 0.6rem;
    }

}

@media (max-width: 400px) {

    .page-title-header {
        font-size: 0.67rem;
        max-width: 105px;
    }

    .header-title-group .header-title-icon {
        width: 17px;
        height: 17px;
    }

    .header-user {
        display: none;
    }

    .notif-dropdown-menu {
        width: 250px;
    }

}

</style>

<!-- =========================================================
     BOOTSTRAP
     ========================================================= -->

<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.min.js"></script>

<script>
(function() {
    'use strict';

    // =========================================================
    // SIDEBAR PERSISTENCE
    // =========================================================

    function getSidebarElements() {
        var sidebar = document.getElementById('adminSidebar');
        var adminMain = document.getElementById('adminMain');
        var collapseBtn = document.getElementById('sidebarCollapseBtn');
        return { sidebar: sidebar, adminMain: adminMain, collapseBtn: collapseBtn };
    }

    // Apply saved state instantly (no transition) on first paint,
    // so the page never "animates" into its initial state on load.
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

        var savedState = localStorage.getItem('polymedicSidebarCollapsed');

        if (savedState === null) {
            localStorage.setItem('polymedicSidebarCollapsed', '0');
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

    // =========================================================
    // UPDATE COLLAPSE BUTTON
    // =========================================================

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

    // =========================================================
    // APPLY ON PAGE LOAD
    // =========================================================

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

    // =========================================================
    // TOGGLE FUNCTION
    // =========================================================

    window.toggleSidebarCollapse = function() {
        var elements = getSidebarElements();
        var sidebar = elements.sidebar;
        var adminMain = elements.adminMain;

        if (!sidebar || !adminMain) return;
        if (window.innerWidth <= 992) return;

        if (sidebar.classList.contains('collapsed')) {
            sidebar.classList.remove('collapsed');
            adminMain.classList.remove('sidebar-collapsed');
            localStorage.setItem('polymedicSidebarCollapsed', '0');
        } else {
            sidebar.classList.add('collapsed');
            adminMain.classList.add('sidebar-collapsed');
            localStorage.setItem('polymedicSidebarCollapsed', '1');
        }

        updateCollapseButton();
    };

    // =========================================================
    // RESIZE HANDLER
    // =========================================================

    window.addEventListener('resize', function() {
        var elements = getSidebarElements();
        var sidebar = elements.sidebar;
        var adminMain = elements.adminMain;

        if (!sidebar || !adminMain) return;

        if (window.innerWidth > 992) {
            var savedState = localStorage.getItem('polymedicSidebarCollapsed');
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

    // =========================================================
    // NOTIFICATIONS
    // =========================================================

    function fetchNotifications() {
        fetch('/polymedic/public/admin/notifications/fetch', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(function(response) {
            return response.json();
        })
        .then(function(data) {
            if (data.status === 'success') {
                updateNotificationBadge(data.unread_count);
                renderNotificationDropdown(data.notifications);
            }
        })
        .catch(function(error) {
            console.error('Error fetching notifications:', error);
        });
    }

    function updateNotificationBadge(count) {
        var badge = document.getElementById('notifBadge');
        if (!badge) return;

        if (count > 0) {
            badge.textContent = count > 99 ? '99+' : count;
            badge.style.display = 'flex';
        } else {
            badge.style.display = 'none';
        }
    }

    function renderNotificationDropdown(notifications) {
        var listContainer = document.getElementById('notifDropdownList');
        if (!listContainer) return;

        if (!notifications || notifications.length === 0) {
            listContainer.innerHTML = `
                <div class="p-3 text-center text-muted small">
                    <i class="bi bi-bell-slash d-block fs-4 mb-1"></i>
                    No notifications yet
                </div>
            `;
            return;
        }

        var html = '';

        notifications.forEach(function(item) {
            var unreadClass = item.is_read == 0 ? 'unread' : '';

            var iconClass = 'bi-bell-fill';
            var colorClass = 'system';

            if (item.type === 'appointment') {
                iconClass = 'bi-calendar-check';
                colorClass = 'appointment';
            } else if (item.type === 'xray') {
                iconClass = 'bi-x-ray';
                colorClass = 'xray';
            } else if (item.type === 'lab') {
                iconClass = 'bi-flask';
                colorClass = 'lab';
            } else if (item.type === 'billing') {
                iconClass = 'bi-receipt';
                colorClass = 'billing';
            } else if (item.type === 'payment') {
                iconClass = 'bi-credit-card';
                colorClass = 'payment';
            }

            var link = item.link || '#';

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

    window.markNotificationRead = function(id, event) {
        fetch('/polymedic/public/admin/notifications/mark-read/' + id, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(function(response) {
            return response.json();
        })
        .then(function(data) {
            if (data.status === 'success') {
                updateNotificationBadge(data.unread_count);
            }
        })
        .catch(function(error) {
            console.error('Error marking notification read:', error);
        });
    };

    window.markAllNotificationsRead = function(event) {
        if (event) {
            event.stopPropagation();
        }

        fetch('/polymedic/public/admin/notifications/mark-all-read', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(function(response) {
            return response.json();
        })
        .then(function(data) {
            if (data.status === 'success') {
                updateNotificationBadge(0);
                fetchNotifications();
            }
        })
        .catch(function(error) {
            console.error('Error marking notifications read:', error);
        });
    };

    function escapeHtml(text) {
        if (!text) return '';
        return text
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            fetchNotifications();
            setInterval(fetchNotifications, 15000);
        });
    } else {
        fetchNotifications();
        setInterval(fetchNotifications, 15000);
    }

})();
</script>

<style>
/* Disable transitions only during the initial state application
   on page load, so restoring a saved collapsed/expanded state
   never itself animates — only user clicks do. */
.admin-sidebar.no-transition,
.admin-sidebar.no-transition * ,
.admin-main.no-transition {
    transition: none !important;
}
</style>

</body>

</html>