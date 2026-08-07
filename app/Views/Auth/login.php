<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PolyMedic - Staff Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
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
            background: linear-gradient(135deg, #f0f7ff 0%, #ffffff 50%, #e8f7ff 100%);
            padding: 1.5rem;
        }

        .login-wrapper {
            width: 100%;
            max-width: 460px;
            animation: fadeUp 0.6s ease;
        }

        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-card {
            background: #ffffff;
            border-radius: 28px;
            padding: 2.5rem 2.5rem 2rem;
            box-shadow: 0 20px 60px rgba(10, 43, 78, 0.12);
            border: 1px solid rgba(1, 72, 202, 0.06);
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-header .logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            margin-bottom: 0.5rem;
        }

        .login-header .logo img {
            height: 48px;
            width: auto;
            object-fit: contain;
        }

        .login-header .logo h1 {
            font-size: 1.5rem;
            font-weight: 800;
            color: #0a2b4e;
            margin: 0;
        }

        .login-header .logo h1 span {
            color: #0148ca;
        }

        .login-header p {
            color: #64748b;
            font-size: 0.9rem;
            margin: 0;
        }

        .login-header .badge-staff {
            display: inline-block;
            background: #e6f0fa;
            color: #0148ca;
            font-size: 0.7rem;
            font-weight: 600;
            padding: 0.25rem 1rem;
            border-radius: 30px;
            margin-top: 0.5rem;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-group label {
            font-weight: 600;
            color: #0a2b4e;
            font-size: 0.85rem;
            display: block;
            margin-bottom: 0.4rem;
        }

        .form-group label .required {
            color: #dc3545;
            margin-left: 2px;
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
            font-size: 1.1rem;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.8rem;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background: #fafcff;
            color: #0a2b4e;
            font-family: 'Inter', sans-serif;
        }

        .form-control:focus {
            border-color: #0148ca;
            box-shadow: 0 0 0 4px rgba(1, 72, 202, 0.12);
            background: #ffffff;
            outline: none;
        }

        .form-control::placeholder {
            color: #94a3b8;
        }

        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: transparent;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            padding: 0.25rem;
            font-size: 1.1rem;
            transition: color 0.3s ease;
        }

        .password-toggle:hover {
            color: #0a2b4e;
        }

        .role-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.5rem;
            margin-top: 0.25rem;
        }

        .role-option {
            padding: 0.6rem 0.75rem;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #fafcff;
            position: relative;
        }

        .role-option:hover {
            border-color: #94a3b8;
            transform: translateY(-2px);
        }

        .role-option.active {
            border-color: #0148ca;
            background: #f0f7ff;
            box-shadow: 0 4px 12px rgba(1, 72, 202, 0.12);
        }

        .role-option .role-icon {
            font-size: 1.3rem;
            display: block;
            margin-bottom: 0.15rem;
        }

        .role-option .role-name {
            font-size: 0.7rem;
            font-weight: 600;
            color: #0a2b4e;
            display: block;
        }

        .role-option .check-mark {
            position: absolute;
            top: -6px;
            right: -6px;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #0148ca;
            color: white;
            font-size: 0.6rem;
            display: none;
            align-items: center;
            justify-content: center;
        }

        .role-option.active .check-mark {
            display: flex;
        }

        .btn-login {
            width: 100%;
            padding: 0.8rem;
            border-radius: 12px;
            background: linear-gradient(135deg, #0148ca, #0037a0);
            border: none;
            color: white;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            margin-top: 0.5rem;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(1, 72, 202, 0.3);
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

        .alert-custom {
            border-radius: 12px;
            padding: 0.75rem 1rem;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            display: none;
        }

        .alert-custom.show {
            display: block;
            animation: shake 0.5s ease;
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
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }

        .login-footer {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid #f0f4ff;
        }

        .login-footer a {
            color: #64748b;
            text-decoration: none;
            font-size: 0.85rem;
            transition: color 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }

        .login-footer a:hover {
            color: #0148ca;
        }

        .login-footer .demo-note {
            font-size: 0.7rem;
            color: #94a3b8;
            margin-top: 0.5rem;
        }

        @media (max-width: 480px) {
            .login-card {
                padding: 1.5rem;
                border-radius: 20px;
            }

            .login-header .logo h1 {
                font-size: 1.2rem;
            }

            .login-header .logo img {
                height: 36px;
            }

            .role-grid {
                grid-template-columns: 1fr 1fr;
                gap: 0.4rem;
            }

            .role-option {
                padding: 0.4rem 0.5rem;
            }

            .role-option .role-icon {
                font-size: 1rem;
            }

            .role-option .role-name {
                font-size: 0.6rem;
            }

            .form-control {
                font-size: 0.85rem;
                padding: 0.6rem 0.8rem 0.6rem 2.4rem;
            }

            .btn-login {
                font-size: 0.9rem;
                padding: 0.7rem;
            }
        }

        @media (max-width: 360px) {
            .role-grid {
                grid-template-columns: 1fr 1fr;
            }

            .login-card {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>

<div class="login-wrapper">
    <div class="login-card">
        <!-- Header -->
        <div class="login-header">
            <div class="logo">
                <img src="/polymedic/public/assets/images/logo4.png" alt="PolyMedic">
                <h1>Poly<span>Medic</span></h1>
            </div>
            <p>Diagnostic & Laboratory Center</p>
            <span class="badge-staff"><i class="bi bi-person-lock me-1"></i>Staff Portal</span>
        </div>

        <!-- Alert Messages -->
        <div id="loginAlert" class="alert-custom">
            <i class="bi bi-exclamation-circle me-2"></i>
            <span id="alertMessage">Invalid credentials. Please try again.</span>
        </div>

        <!-- Login Form - FIXED ACTION -->
        <form action="/polymedic/public/index.php/auth/authenticate" method="POST" id="loginForm">
            <?= csrf_field() ?>

            <!-- Username -->
            <div class="form-group">
                <label for="username">Username or Email <span class="required">*</span></label>
                <div class="input-wrapper">
                    <span class="input-icon"><i class="bi bi-person"></i></span>
                    <input type="text" class="form-control" id="username" name="username" placeholder="Enter your username" value="admin" required autofocus>
                </div>
            </div>

            <!-- Password -->
            <div class="form-group">
                <label for="password">Password <span class="required">*</span></label>
                <div class="input-wrapper">
                    <span class="input-icon"><i class="bi bi-lock"></i></span>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" value="password" required>
                    <button type="button" class="password-toggle" onclick="togglePassword()" aria-label="Toggle password visibility">
                        <i class="bi bi-eye" id="passwordIcon"></i>
                    </button>
                </div>
            </div>

            <!-- Role Selection -->
            <div class="form-group">
                <label>Select Your Role <span class="required">*</span></label>
                <div class="role-grid">
                    <div class="role-option active" data-role="administrator" onclick="selectRole(this)">
                        <span class="check-mark"><i class="bi bi-check"></i></span>
                        <span class="role-icon"><i class="bi bi-shield-lock" style="color: #0148ca;"></i></span>
                        <span class="role-name">Administrator</span>
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
                    <div class="role-option" data-role="receptionist" onclick="selectRole(this)">
                        <span class="check-mark"><i class="bi bi-check"></i></span>
                        <span class="role-icon"><i class="bi bi-person-check" style="color: #17a2b8;"></i></span>
                        <span class="role-name">Receptionist</span>
                    </div>
                </div>
                <input type="hidden" name="role" id="selectedRole" value="administrator">
            </div>

            <!-- Login Button -->
            <button type="submit" class="btn-login" id="loginBtn">
                <span class="spinner"></span>
                <span class="btn-text"><i class="bi bi-box-arrow-in-right me-2"></i>Sign In</span>
            </button>
        </form>

        <!-- Footer -->
        <div class="login-footer">
            <a href="/polymedic/public/">
                <i class="bi bi-arrow-left"></i> Back to Patient Portal
            </a>
            <div class="demo-note">
                <i class="bi bi-info-circle me-1"></i> Demo: Any username/password works
            </div>
        </div>
    </div>
</div>

<script>
    // Role Selection
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

    // Form Submit with Loading State
    document.getElementById('loginForm').addEventListener('submit', function(e) {
        const username = document.getElementById('username').value.trim();
        const password = document.getElementById('password').value.trim();
        const alertDiv = document.getElementById('loginAlert');
        const alertMessage = document.getElementById('alertMessage');
        const loginBtn = document.getElementById('loginBtn');

        // Validate
        if (!username || !password) {
            e.preventDefault();
            alertDiv.className = 'alert-custom show danger';
            alertMessage.textContent = 'Please enter both username and password.';
            return;
        }

        // Show loading state
        loginBtn.classList.add('loading');
        loginBtn.disabled = true;
        alertDiv.className = 'alert-custom';
        alertDiv.style.display = 'none';
    });
</script>

</body>
</html>