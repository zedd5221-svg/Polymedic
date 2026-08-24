<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PolyMedic · Login</title>
    <!-- Bootstrap + Icons + Inter font -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f2f6fc;
            padding: 1.5rem;
        }

        /* ---- main card (split-screen feel) ---- */
        .login-wrapper {
            width: 100%;
            max-width: 1120px;
            animation: fadeUp 0.6s ease;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(24px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .login-card {
            background: #ffffff;
            border-radius: 24px;
            box-shadow: 0 40px 80px rgba(0, 20, 50, 0.10), 0 12px 32px rgba(0, 30, 80, 0.04);
            display: grid;
            grid-template-columns: 1fr 1fr;
            overflow: hidden;
            border: none;
            min-height: 600px;
        }

        /* ===== LEFT PANEL (brand / feature) - BLUE BACKGROUND ===== */
        .left-panel {
            background: linear-gradient(135deg, #1976d2 0%, #1565c0 50%, #0d47a1 100%);
            padding: 3rem 2.5rem 2.5rem;
            display: flex;
            flex-direction: column;
            color: white;
            position: relative;
            overflow: hidden;
        }

        /* Decorative circles */
        .left-panel::before {
            content: '';
            position: absolute;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: rgba(255,255,255,0.05);
            top: -100px;
            right: -100px;
        }

        .left-panel::after {
            content: '';
            position: absolute;
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: rgba(255,255,255,0.03);
            bottom: -50px;
            left: -50px;
        }

        .brand-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 2.5rem;
            position: relative;
            z-index: 1;
        }

        .brand-header img {
            height: 40px;
            width: auto;
            object-fit: contain;
            filter: brightness(0) invert(1); /* Make logo white */
        }

        .brand-header h1 {
            font-size: 1.5rem;
            font-weight: 800;
            color: #ffffff;
            margin: 0;
            letter-spacing: -0.4px;
        }

        .brand-header h1 span {
            color: #90caf9;
        }

        .brand-header .badge-sub {
            display: none;
        }

        .left-panel .big-tagline {
            font-size: 1.8rem;
            font-weight: 700;
            color: #ffffff;
            line-height: 1.3;
            margin: 0 0 1rem;
            letter-spacing: -0.3px;
            position: relative;
            z-index: 1;
        }

        .left-panel .big-tagline span {
            color: #90caf9;
        }

        .left-panel .description {
            color: rgba(255,255,255,0.8);
            font-size: 0.9rem;
            line-height: 1.6;
            max-width: 85%;
            margin-bottom: 2.5rem;
            position: relative;
            z-index: 1;
        }

        /* feature pills (left) - Glassmorphism style */
        .feature-pills {
            display: flex;
            flex-direction: column;
            gap: 0.8rem;
            margin-bottom: auto;
            position: relative;
            z-index: 1;
        }

        .feature-pills .pill-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1.25rem;
            border-radius: 12px;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.15);
            backdrop-filter: blur(10px);
            font-size: 0.85rem;
            font-weight: 500;
            color: #ffffff;
        }

        .feature-pills .pill-item i {
            color: #90caf9;
            font-size: 1rem;
            width: 24px;
            text-align: center;
        }

        /* Remove old testimonial styles - not in reference */
        .testimonial {
            display: none;
        }

        /* ===== RIGHT PANEL (form) - WHITE BACKGROUND ===== */
        .right-panel {
            padding: 3rem 2.8rem 2.5rem;
            background: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        /* Logo centered at top of right panel */
        .right-logo {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 2rem;
            text-align: center;
        }

        .right-logo img {
            height: 48px;
            width: auto;
            object-fit: contain;
            margin-bottom: 0.5rem;
        }

        .right-logo h2 {
            font-size: 1.2rem;
            font-weight: 700;
            color: #0a2b4e;
            margin: 0 0 0.15rem;
            letter-spacing: -0.2px;
        }

        .right-logo p {
            color: #94a3b8;
            font-size: 0.8rem;
            margin: 0;
        }

        .welcome-head {
            margin-bottom: 1.5rem;
        }

        .welcome-head h2 {
            font-size: 1.3rem;
            font-weight: 700;
            color: #0a2b4e;
            margin: 0 0 0.15rem;
            letter-spacing: -0.3px;
        }

        .welcome-head p {
            color: #94a3b8;
            font-size: 0.85rem;
            margin: 0;
        }

        /* form elements */
        .form-group {
            margin-bottom: 0.9rem;
        }

        .form-group label {
            font-weight: 600;
            color: #0a2b4e;
            font-size: 0.8rem;
            display: block;
            margin-bottom: 0.3rem;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 1rem;
        }

        .form-control {
            width: 100%;
            padding: 0.7rem 1rem 0.7rem 2.8rem;
            border: 1px solid #e8edf5;
            border-radius: 10px;
            font-size: 0.9rem;
            transition: all 0.25s ease;
            background: #ffffff;
            color: #0a2b4e;
            font-family: 'Inter', sans-serif;
            height: 48px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        }

        .form-control:focus {
            border-color: #1976d2;
            box-shadow: 0 0 0 4px rgba(25, 118, 210, 0.1);
            background: #ffffff;
            outline: none;
        }

        .password-toggle {
            position: absolute;
            right: 0.8rem;
            top: 50%;
            transform: translateY(-50%);
            background: transparent;
            border: none;
            color: #94a3b8;
            font-size: 1.1rem;
            padding: 0.2rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .password-toggle:hover {
            color: #1976d2;
        }

        /* role selection */
        .role-section {
            margin-bottom: 0.9rem;
        }

        .role-section label {
            font-weight: 600;
            color: #0a2b4e;
            font-size: 0.8rem;
            display: block;
            margin-bottom: 0.3rem;
        }

        .role-select-wrapper {
            position: relative;
        }

        .role-select-wrapper .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 1rem;
            pointer-events: none;
            z-index: 2;
        }

        .role-select-wrapper .form-select {
            width: 100%;
            padding: 0.7rem 2.5rem 0.7rem 2.8rem;
            border: 1px solid #e8edf5;
            border-radius: 10px;
            font-size: 0.9rem;
            transition: all 0.25s ease;
            background-color: #ffffff;
            color: #0a2b4e;
            font-family: 'Inter', sans-serif;
            height: 48px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
            appearance: none;
            -webkit-appearance: none;
            cursor: pointer;
        }

        .role-select-wrapper .form-select:focus {
            border-color: #1976d2;
            box-shadow: 0 0 0 4px rgba(25, 118, 210, 0.1);
            outline: none;
        }

        .role-select-wrapper .select-arrow {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 0.8rem;
            pointer-events: none;
        }

        /* options row */
        .options-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 0.5rem 0 1.25rem;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.8rem;
            color: #475569;
            cursor: pointer;
        }

        .remember-me input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: #1976d2;
            cursor: pointer;
            margin: 0;
            border-radius: 4px;
        }

        .forgot-link {
            color: #1976d2;
            text-decoration: none;
            font-size: 0.8rem;
            font-weight: 500;
            transition: color 0.2s;
        }

        .forgot-link:hover {
            color: #1565c0;
            text-decoration: underline;
        }

        /* button */
        .btn-login {
            width: 100%;
            padding: 0.85rem;
            border-radius: 10px;
            background: #1976d2;
            border: none;
            color: white;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            height: 50px;
            letter-spacing: 0.2px;
        }

        .btn-login:hover {
            background: #1565c0;
            box-shadow: 0 4px 16px rgba(25, 118, 210, 0.3);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .btn-login:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
        }

        .btn-login .spinner {
            display: none;
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        .btn-login.loading .spinner {
            display: inline-block;
        }

        .btn-login.loading .btn-text {
            display: none;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* alerts */
        .alert-custom {
            border-radius: 10px;
            padding: 0.7rem 1rem;
            margin-bottom: 1rem;
            font-size: 0.85rem;
            display: none;
        }

        .alert-custom.show {
            display: block;
            animation: shake 0.4s ease;
        }

        .alert-custom.danger {
            background: #fce4ec;
            color: #dc3545;
            border: 1px solid #f8d7da;
        }

        .alert-custom.success {
            background: #e8f5e9;
            color: #28a745;
            border: 1px solid #c8e6c9;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        /* footer */
        .right-footer {
            margin-top: 1.8rem;
            text-align: center;
            font-size: 0.7rem;
            color: #94a3b8;
            letter-spacing: 0.2px;
        }

        /* ===== responsive ===== */
        @media (max-width: 820px) {
            .login-card {
                grid-template-columns: 1fr;
                border-radius: 16px;
            }
            .left-panel {
                padding: 2rem 1.8rem;
                min-height: auto;
            }
            .left-panel .description {
                max-width: 100%;
            }
            .right-panel {
                padding: 2rem 1.8rem;
            }
            .left-panel .big-tagline {
                font-size: 1.5rem;
            }
        }

        @media (max-width: 480px) {
            .login-card {
                border-radius: 12px;
            }
            .left-panel, .right-panel {
                padding: 1.5rem;
            }
            .left-panel .big-tagline {
                font-size: 1.3rem;
            }
            .form-control {
                height: 44px;
                padding-left: 2.4rem;
                font-size: 0.85rem;
            }
            .role-select-wrapper .form-select {
                height: 44px;
                padding-left: 2.4rem;
                font-size: 0.85rem;
            }
            .btn-login {
                height: 46px;
                font-size: 0.9rem;
            }
            .options-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.4rem;
            }
        }
    </style>
</head>
<body>

<div class="login-wrapper">
    <div class="login-card">

        <!-- LEFT PANEL : Blue background with brand + features -->
        <div class="left-panel">
            <div class="brand-header">
                <img src="/polymedic/public/assets/images/logo4.png" alt="PolyMedic">
                <h1>Poly<span>Medic</span></h1>
            </div>

            <div class="big-tagline">
                Precision Diagnostics,<br><span>Seamless Care</span>
            </div>
            <p class="description">
                A comprehensive platform for managing patient records, diagnostic requests, laboratory findings, and billing — all in one place.
            </p>

            <!-- feature pills (matching reference) -->
            <div class="feature-pills">
                <div class="pill-item">
                    <i class="bi bi-flask"></i>
                    Laboratory &amp; Radiology Findings
                </div>
                <div class="pill-item">
                    <i class="bi bi-people"></i>
                    Integrated Patient Management
                </div>
                <div class="pill-item">
                    <i class="bi bi-credit-card-2-front"></i>
                    Automated Billing &amp; Payments
                </div>
            </div>
        </div>

        <!-- RIGHT PANEL : White background with login form -->
        <div class="right-panel">
            <!-- Centered Logo -->
            <div class="right-logo">
                <img src="/polymedic/public/assets/images/logo4.png" alt="PolyMedic">
                <h2>PolyMedic</h2>
                <p>Diagnostic Information System</p>
            </div>

            <div class="welcome-head">
                <h2>Welcome back</h2>
                <p>Sign in to access the system</p>
            </div>

            <!-- flash / alert messages -->
            <?php if (session()->getFlashdata('error')): ?>
                <div class="alert-custom show danger">
                    <i class="bi bi-exclamation-circle me-2"></i>
                    <?= session()->getFlashdata('error') ?>
                </div>
            <?php endif; ?>

            <?php if (session()->getFlashdata('success')): ?>
                <div class="alert-custom show success">
                    <i class="bi bi-check-circle me-2"></i>
                    <?= session()->getFlashdata('success') ?>
                </div>
            <?php endif; ?>

            <div id="loginAlert" class="alert-custom">
                <i class="bi bi-exclamation-circle me-2"></i>
                <span id="alertMessage">Invalid credentials. Please try again.</span>
            </div>

            <!-- form -->
            <form action="<?= base_url('auth/authenticate') ?>" method="POST" id="loginForm">
                <?= csrf_field() ?>

                <!-- username -->
                <div class="form-group">
                    <label for="username">Username</label>
                    <div class="input-wrapper">
                        <span class="input-icon"><i class="bi bi-person"></i></span>
                        <input type="text" class="form-control" id="username" name="username" placeholder="Enter your username" value="admin" required autofocus>
                    </div>
                </div>

                <!-- password -->
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-wrapper">
                        <span class="input-icon"><i class="bi bi-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" value="admin123" required>
                        <button type="button" class="password-toggle" onclick="togglePassword()" aria-label="Toggle password visibility">
                            <i class="bi bi-eye" id="passwordIcon"></i>
                        </button>
                    </div>
                </div>

                <!-- role selection (converted to dropdown to match reference) -->
                <div class="role-section">
                    <label>Role</label>
                    <div class="role-select-wrapper">
                        <span class="input-icon"><i class="bi bi-person-badge"></i></span>
                        <select class="form-select" name="role" id="selectedRole">
                            <option value="administrator" selected>Administrator</option>
                            <option value="receptionist">Receptionist</option>
                            <option value="technologist">Med Tech</option>
                            <option value="radiologist">Radiologist</option>
                        </select>
                        <span class="select-arrow"><i class="bi bi-chevron-down"></i></span>
                    </div>
                </div>

                <!-- options row -->
                <div class="options-row">
                    <label class="remember-me">
                        <input type="checkbox" id="rememberMe" checked>
                        Remember me
                    </label>
                    <a href="#" class="forgot-link">Forgot password?</a>
                </div>

                <!-- sign in button -->
                <button type="submit" class="btn-login" id="loginBtn">
                    <span class="spinner"></span>
                    <span class="btn-text">Sign In</span>
                </button>
            </form>

            <!-- footer -->
            <div class="right-footer">
                PolyMedic v2.4.1 · © 2026 PolyMedic Corp.
            </div>
        </div>
    </div>
</div>

<script>
    // Role Selection
    document.getElementById('selectedRole').addEventListener('change', function() {
        // Value updates automatically with select
    });

    // Toggle Password
    function togglePassword() {
        const password = document.getElementById('password');
        const icon = document.getElementById('passwordIcon');
        if (password.type === 'password') {
            password.type = 'text';
            icon.className = 'bi bi-eye-slash';
        } else {
            password.type = 'password';
            icon.className = 'bi bi-eye';
        }
    }

    // Form submit with loading + validation (preserved)
    document.getElementById('loginForm').addEventListener('submit', function(e) {
        const username = document.getElementById('username').value.trim();
        const password = document.getElementById('password').value.trim();
        const alertDiv = document.getElementById('loginAlert');
        const alertMessage = document.getElementById('alertMessage');
        const loginBtn = document.getElementById('loginBtn');

        if (!username || !password) {
            e.preventDefault();
            alertDiv.className = 'alert-custom show danger';
            alertMessage.textContent = 'Please enter both username and password.';
            return;
        }

        loginBtn.classList.add('loading');
        loginBtn.disabled = true;
        alertDiv.className = 'alert-custom';
        alertDiv.style.display = 'none';
    });

    // Auto-hide flash messages after 5s
    document.addEventListener('DOMContentLoaded', function() {
        const alerts = document.querySelectorAll('.alert-custom.show');
        alerts.forEach(function(alert) {
            setTimeout(function() {
                alert.classList.remove('show');
                alert.style.display = 'none';
            }, 5000);
        });
    });
</script>

</body>
</html>