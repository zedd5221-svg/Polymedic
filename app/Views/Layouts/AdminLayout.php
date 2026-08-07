<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PolyMedic - Admin Dashboard</title>

    <!-- Main CSS -->
    <link href="/polymedic/public/assets/css/AppointmentStyle.css" rel="stylesheet">
    <link href="/polymedic/public/assets/css/admin.css" rel="stylesheet">
    
    <!-- Bootstrap & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="admin-wrapper">
        <!-- Sidebar -->
        <aside class="admin-sidebar" id="adminSidebar">
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
                    <li class="<?= current_url() == base_url('admin/dashboard') ? 'active' : '' ?>">
                        <a href="/polymedic/public/admin/dashboard">
                            <i class="bi bi-grid-1x2-fill"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="<?= current_url() == base_url('admin/patients') ? 'active' : '' ?>">
                        <a href="/polymedic/public/admin/patients">
                            <i class="bi bi-people-fill"></i>
                            <span>Patients</span>
                        </a>
                    </li>
                    <li class="<?= current_url() == base_url('admin/visits') ? 'active' : '' ?>">
                        <a href="/polymedic/public/admin/visits">
                            <i class="bi bi-clipboard2-pulse-fill"></i>
                            <span>Patient Visits</span>
                        </a>
                    </li>
                    <li class="<?= current_url() == base_url('admin/requests') ? 'active' : '' ?>">
                        <a href="/polymedic/public/admin/requests">
                            <i class="bi bi-file-earmark-medical-fill"></i>
                            <span>Diagnostic Requests</span>
                        </a>
                    </li>
                  
                    
                    <li class="nav-section">ADMIN</li>
                    <li class="<?= current_url() == base_url('admin/users') ? 'active' : '' ?>">
                        <a href="/polymedic/public/admin/users">
                            <i class="bi bi-person-gear"></i>
                            <span>User Management</span>
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
            <header class="admin-header">
                <div class="header-left">
                    <button class="hamburger-btn" onclick="toggleSidebar()">
                        <i class="bi bi-list"></i>
                    </button>
                    <!-- Header Icon + Page Title with dynamic icon -->
                    <div class="header-title-group">
                        <?php 
                            $pageTitle = $this->renderSection('pageTitle') ?: 'Dashboard';
                            $iconMap = [
                                'Dashboard' => 'statisctics.png',
                                'Patient Management' => 'sick-patient.png',
                                'Patient Visits' => 'patient.png',
                                'Diagnostic Requests' => 'stethoscope.png',
                                'Laboratory Findings' => 'stethoscope.png',
                                'User Management' => 'profile.png'
                            ];
                            $iconFile = $iconMap[$pageTitle] ?? 'statisctics.png';
                        ?>
                        <img src="/polymedic/public/assets/images/<?= $iconFile ?>" alt="<?= $pageTitle ?>" class="header-title-icon">
                        <span class="page-title-header"><?= $pageTitle ?></span>
                    </div>
                </div>
                <div class="header-right">
                    <div class="header-info-group">
                        <div class="header-datetime">
                            <i class="bi bi-clock"></i>
                            <span><?= date('D, M j · h:i:s A') ?></span>
                        </div>
                        <span class="divider-icon">|</span>
                        <button class="notif-btn">
                            <i class="bi bi-bell-fill"></i>
                            <span class="notif-badge">3</span>
                        </button>
                        <span class="divider-icon">|</span>
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
            
            <!-- Page Content -->
            <div class="admin-content">
                <?php echo $this->renderSection('adminContent'); ?>
            </div>
        </main>
    </div>

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
    </script>
</body>
</html>