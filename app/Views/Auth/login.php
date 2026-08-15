<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PolyMedic · Split Login</title>
    <!-- Bootstrap + Icons + Inter font (same as original) -->
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
            border-radius: 40px;
            box-shadow: 0 40px 80px rgba(0, 20, 50, 0.10), 0 12px 32px rgba(0, 30, 80, 0.04);
            display: grid;
            grid-template-columns: 1fr 1fr;
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.4);
        }

        /* ===== LEFT PANEL (brand / feature) ===== */
        .left-panel {
            background: linear-gradient(145deg, #f8fbff, #f0f6ff);
            padding: 3rem 2.5rem 2.5rem;
            display: flex;
            flex-direction: column;
            border-right: 1px solid rgba(0,40,80,0.04);
        }

        .brand-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }

        .brand-header img {
            height: 44px;
            width: auto;
            object-fit: contain;
        }

        .brand-header h1 {
            font-size: 1.8rem;
            font-weight: 800;
            color: #0a2b4e;
            margin: 0;
            letter-spacing: -0.4px;
        }

        .brand-header h1 span {
            color: #0148ca;
        }

        .brand-header .badge-sub {
            font-size: 0.6rem;
            font-weight: 500;
            color: #94a3b8;
            background: #eef3f9;
            padding: 0.15rem 0.6rem;
            border-radius: 30px;
            margin-left: 0.25rem;
        }

        .left-panel .big-tagline {
            font-size: 2rem;
            font-weight: 700;
            color: #0a2b4e;
            line-height: 1.2;
            margin: 0.25rem 0 0.5rem;
            letter-spacing: -0.5px;
        }

        .left-panel .big-tagline span {
            color: #0148ca;
        }

        .left-panel .description {
            color: #475569;
            font-size: 0.95rem;
            line-height: 1.6;
            max-width: 90%;
            margin-bottom: 2rem;
        }

        /* feature pills (left) */
        .feature-pills {
            display: flex;
            flex-wrap: wrap;
            gap: 0.8rem 1.2rem;
            margin-bottom: 2.5rem;
        }

        .feature-pills span {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.8rem;
            font-weight: 500;
            color: #1e293b;
            background: white;
            padding: 0.3rem 1rem 0.3rem 0.8rem;
            border-radius: 40px;
            border: 1px solid #e4ebf5;
            box-shadow: 0 2px 6px rgba(0,0,0,0.01);
        }

        .feature-pills span i {
            color: #0148ca;
            font-size: 0.9rem;
        }

        /* testimonial (northwind style) */
        .testimonial {
            margin-top: auto;
            background: white;
            border-radius: 24px;
            padding: 1.5rem 1.8rem;
            box-shadow: 0 8px 24px rgba(0,40,80,0.04);
            border: 1px solid #eaf0fa;
        }

        .testimonial .quote {
            font-size: 1rem;
            font-weight: 500;
            color: #0a2b4e;
            margin-bottom: 0.4rem;
            line-height: 1.4;
        }

        .testimonial .quote i {
            color: #0148ca;
            opacity: 0.5;
            margin-right: 0.2rem;
        }

        .testimonial .attribution {
            font-size: 0.8rem;
            color: #64748b;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }

        .testimonial .attribution i {
            color: #fbbf24;
            font-size: 0.75rem;
        }

        /* ===== RIGHT PANEL (form) ===== */
        .right-panel {
            padding: 3rem 2.8rem 2.5rem;
            background: white;
            display: flex;
            flex-direction: column;
        }

        .right-panel .welcome-head {
            margin-bottom: 1.5rem;
        }

        .right-panel .welcome-head h2 {
            font-size: 1.6rem;
            font-weight: 700;
            color: #0a2b4e;
            margin: 0 0 0.15rem;
            letter-spacing: -0.3px;
        }

        .right-panel .welcome-head p {
            color: #94a3b8;
            font-size: 0.9rem;
            margin: 0;
        }

        /* form elements (same structure, refined) */
        .form-group {
            margin-bottom: 0.9rem;
        }

        .form-group label {
            font-weight: 600;
            color: #0a2b4e;
            font-size: 0.8rem;
            display: block;
            margin-bottom: 0.25rem;
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
            border: 2px solid #e8edf5;
            border-radius: 14px;
            font-size: 0.9rem;
            transition: all 0.25s ease;
            background: #f8faff;
            color: #0a2b4e;
            font-family: 'Inter', sans-serif;
            height: 50px;
        }

        .form-control:focus {
            border-color: #0148ca;
            box-shadow: 0 0 0 4px rgba(1,72,202,0.08);
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
            color: #0148ca;
        }

        /* role grid (same as original but preserved) */
        .role-section {
            margin-bottom: 0.9rem;
        }

        .role-section label {
            font-weight: 600;
            color: #0a2b4e;
            font-size: 0.8rem;
            display: block;
            margin-bottom: 0.25rem;
        }

        .role-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 0.5rem;
        }

        .role-option {
            padding: 0.5rem 0.2rem;
            border: 2px solid #e8edf5;
            border-radius: 12px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease;
            background: #f8faff;
            position: relative;
            height: 46px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.3rem;
        }

        .role-option:hover {
            border-color: #b3c5db;
            transform: translateY(-1px);
        }

        .role-option.active {
            border-color: #0148ca;
            background: #f0f7ff;
            box-shadow: 0 4px 12px rgba(1,72,202,0.06);
        }

        .role-option .role-icon {
            font-size: 1rem;
            display: inline-flex;
            align-items: center;
        }

        .role-option .role-name {
            font-size: 0.65rem;
            font-weight: 600;
            color: #0a2b4e;
            white-space: nowrap;
        }

        .role-option .check-mark {
            position: absolute;
            top: -6px;
            right: -6px;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background: #0148ca;
            color: white;
            font-size: 0.55rem;
            display: none;
            align-items: center;
            justify-content: center;
        }

        .role-option.active .check-mark {
            display: flex;
        }

        /* options row */
        .options-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 0.25rem 0 1rem;
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
            accent-color: #0148ca;
            cursor: pointer;
            margin: 0;
        }

        .forgot-link {
            color: #0148ca;
            text-decoration: none;
            font-size: 0.8rem;
            font-weight: 500;
            transition: color 0.2s;
        }

        .forgot-link:hover {
            color: #0037a0;
            text-decoration: underline;
        }

        /* button */
        .btn-login {
            width: 100%;
            padding: 0.85rem;
            border-radius: 14px;
            background: linear-gradient(135deg, #0148ca, #0037a0);
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
            height: 52px;
            letter-spacing: 0.2px;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 28px rgba(1,72,202,0.30);
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
            border-radius: 14px;
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
            border-top: 1px solid #f0f4ff;
            padding-top: 1.2rem;
            font-size: 0.65rem;
            color: #a8b5c9;
            letter-spacing: 0.2px;
        }

        /* ===== responsive ===== */
        @media (max-width: 820px) {
            .login-card {
                grid-template-columns: 1fr;
                border-radius: 28px;
            }
            .left-panel {
                border-right: none;
                border-bottom: 1px solid #eaf0fa;
                padding: 2rem 1.8rem;
            }
            .left-panel .description {
                max-width: 100%;
            }
            .right-panel {
                padding: 2rem 1.8rem;
            }
            .left-panel .big-tagline {
                font-size: 1.6rem;
            }
        }

        @media (max-width: 480px) {
            .login-card {
                border-radius: 20px;
            }
            .left-panel, .right-panel {
                padding: 1.5rem;
            }
            .brand-header h1 {
                font-size: 1.4rem;
            }
            .left-panel .big-tagline {
                font-size: 1.3rem;
            }
            .role-grid {
                grid-template-columns: 1fr 1fr;
                gap: 0.4rem;
            }
            .role-option {
                height: 40px;
                padding: 0.3rem 0.2rem;
            }
            .role-option .role-name {
                font-size: 0.6rem;
            }
            .form-control {
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
            .testimonial {
                padding: 1rem 1.2rem;
            }
        }
    </style>
</head>
<body>

<div class="login-wrapper">
    <div class="login-card">

        <!-- LEFT PANEL : brand + features + testimonial -->
        <div class="left-panel">
            <div class="brand-header">
                <img src="/polymedic/public/assets/images/logo4.png" alt="PolyMedic">
                <h1>Poly<span>Medic</span></h1>
                <span class="badge-sub">v2.4</span>
            </div>

            <div class="big-tagline">
                Start your day with <span>everything</span> in one place.
            </div>
            <p class="description">
                Projects, docs, and conversations — organized so your team can focus on the work that matters.
            </p>

            <!-- feature pills (northwind style) -->
            <div class="feature-pills">
                <span><i class="bi bi-flask"></i> Lab &amp; Radiology</span>
                <span><i class="bi bi-people"></i> Patient management</span>
                <span><i class="bi bi-credit-card"></i> Automated billing</span>
                <span><i class="bi bi-cloud-check"></i> Integrated diagnostics</span>
            </div>

            <!-- testimonial -->
            <div class="testimonial">
                <div class="quote">
                    <i class="bi bi-quote"></i> The cleanest tool we have ever rolled out.
                </div>
                <div class="attribution">
                    <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
                    <span>— Dana K., Ops Lead</span>
                </div>
            </div>
        </div>

        <!-- RIGHT PANEL : login form -->
        <div class="right-panel">
            <div class="welcome-head">
                <h2>Welcome back</h2>
                <p>Sign in to continue to your workspace.</p>
            </div>

            <!-- flash / alert messages (same logic) -->
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
                    <label for="username">Email address / Username</label>
                    <div class="input-wrapper">
                        <span class="input-icon"><i class="bi bi-envelope"></i></span>
                        <input type="text" class="form-control" id="username" name="username" placeholder="you@company.com" value="admin" required autofocus>
                    </div>
                </div>

                <!-- password -->
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-wrapper">
                        <span class="input-icon"><i class="bi bi-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password" placeholder="********" value="admin123" required>
                        <button type="button" class="password-toggle" onclick="togglePassword()" aria-label="Toggle password visibility">
                            <i class="bi bi-eye" id="passwordIcon"></i>
                        </button>
                    </div>
                </div>

                <!-- role selection (preserved) -->
                <div class="role-section">
                    <label>Role</label>
                    <div class="role-grid">
                        <div class="role-option active" data-role="administrator" onclick="selectRole(this)">
                            <span class="check-mark"><i class="bi bi-check"></i></span>
                            <span class="role-icon"><i class="bi bi-shield-lock" style="color: #0148ca;"></i></span>
                            <span class="role-name">Administrator</span>
                        </div>
                        <div class="role-option" data-role="receptionist" onclick="selectRole(this)">
                            <span class="check-mark"><i class="bi bi-check"></i></span>
                            <span class="role-icon"><i class="bi bi-person-check" style="color: #17a2b8;"></i></span>
                            <span class="role-name">Receptionist</span>
                        </div>
                        <div class="role-option" data-role="technologist" onclick="selectRole(this)">
                            <span class="check-mark"><i class="bi bi-check"></i></span>
                            <span class="role-icon"><i class="bi bi-micoscope" style="color: #28a745;"></i></span>
                            <span class="role-name">Med Tech</span>
                        </div>
                        <div class="role-option" data-role="radiologist" onclick="selectRole(this)">
                            <span class="check-mark"><i class="bi bi-check"></i></span>
                            <span class="role-icon"><i class="bi bi-x-ray" style="color: #800080;"></i></span>
                            <span class="role-name">Radiologist</span>
                        </div>
                    </div>
                    <input type="hidden" name="role" id="selectedRole" value="administrator">
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
                    <span class="btn-text">Sign in</span>
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
    // Role Selection (unchanged)
    let selectedRole = 'administrator';
    function selectRole(element) {
        document.querySelectorAll('.role-option').forEach(el => el.classList.remove('active'));
        element.classList.add('active');
        selectedRole = element.dataset.role;
        document.getElementById('selectedRole').value = selectedRole;
    }

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