<?php
/* =============================================================
   PolyMedic — Login view

   SECURITY NOTES:
   - No credentials are pre-filled anymore.
   - All flash data is escaped with esc() before output.
   - The role <select> is a convenience only. NEVER trust it for
     authorisation: look the role up from the user record after
     the password check.
   - A honeypot field and a render timestamp are included for
     simple bot filtering; the controller must check them.
   See the controller checklist at the bottom of this file.
   ============================================================= */

$errorFlash   = session()->getFlashdata('error');
$successFlash = session()->getFlashdata('success');
$oldUsername  = old('username') ?? '';
$oldRole      = old('role') ?? '';

$roles = [
    'administrator' => 'Administrator',
    'receptionist'  => 'Receptionist',
    'technologist'  => 'Med Tech',
    'radiologist'   => 'Radiologist',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">

    <!-- Login pages should never be indexed or cached -->
    <meta name="robots" content="noindex, nofollow">
    <meta name="referrer" content="same-origin">
    <meta http-equiv="Cache-Control" content="no-store">

    <title>Sign in · PolyMedic</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        /* =========================================================
           TOKENS
           ========================================================= */
        :root {
            --blue-700: #0d47a1;
            --blue-600: #1565c0;
            --blue-500: #1976d2;
            --blue-400: #3c9bf0;
            --blue-200: #90caf9;
            --blue-50:  #e8f1fc;

            --ink-900: #0a2b4e;
            --ink-600: #475569;
            --ink-400: #94a3b8;
            --line:    #e5ebf4;

            --danger:  #c62828;
            --danger-bg: #fdecec;
            --success: #1b7f4d;
            --success-bg: #e7f6ee;
            --warn:    #8a5a00;
            --warn-bg: #fff6e0;

            --radius: 26px;
            --field-h: 50px;
            --spring: cubic-bezier(.22, 1, .36, 1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        html, body { height: 100%; }

        body {
            font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', sans-serif;
            min-height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: clamp(1rem, 3vw, 2.25rem);
            padding-top: calc(clamp(1rem, 3vw, 2.25rem) + env(safe-area-inset-top, 0px));
            padding-bottom: calc(clamp(1rem, 3vw, 2.25rem) + env(safe-area-inset-bottom, 0px));
            background: #eef3fa;
            color: var(--ink-900);
            -webkit-font-smoothing: antialiased;
            position: relative;
            overflow-x: hidden;
        }

        /* Soft light behind the card */
        body::before,
        body::after {
            content: '';
            position: fixed;
            border-radius: 50%;
            pointer-events: none;
            z-index: 0;
        }

        body::before {
            width: 640px; height: 640px;
            top: -260px; left: -180px;
            background: radial-gradient(circle, rgba(60, 155, 240, 0.20), transparent 68%);
        }

        body::after {
            width: 560px; height: 560px;
            bottom: -240px; right: -160px;
            background: radial-gradient(circle, rgba(13, 71, 161, 0.16), transparent 68%);
        }

        /* =========================================================
           CARD
           ========================================================= */
        .login-wrapper {
            width: 100%;
            max-width: 1080px;
            position: relative;
            z-index: 1;
        }

        .login-card {
            background: #ffffff;
            border-radius: var(--radius);
            box-shadow:
                0 2px 4px rgba(10, 43, 78, 0.04),
                0 24px 48px rgba(10, 43, 78, 0.10),
                0 48px 96px rgba(10, 43, 78, 0.06);
            display: grid;
            grid-template-columns: 1.05fr 1fr;
            overflow: hidden;
            min-height: 620px;
            position: relative;
        }

        /* =========================================================
           LEFT PANEL
           ========================================================= */
        .left-panel {
            background: linear-gradient(150deg, #1c7ed6 0%, #1565c0 46%, #0b3f8f 100%);
            padding: 2.9rem 2.6rem;
            display: flex;
            flex-direction: column;
            color: #ffffff;
            position: relative;
            overflow: hidden;
        }

        /* Slowly drifting light blobs */
        .left-panel .orb {
            position: absolute;
            border-radius: 50%;
            pointer-events: none;
        }

        .left-panel .orb-1 {
            width: 340px; height: 340px;
            top: -130px; right: -110px;
            background: rgba(255, 255, 255, 0.10);
            animation: drift 14s ease-in-out infinite;
        }

        .left-panel .orb-2 {
            width: 220px; height: 220px;
            bottom: -70px; left: -60px;
            background: rgba(255, 255, 255, 0.07);
            animation: drift 18s ease-in-out infinite reverse;
        }

        .left-panel .orb-3 {
            width: 130px; height: 130px;
            top: 46%; right: 12%;
            background: rgba(144, 202, 249, 0.14);
            animation: drift 11s ease-in-out infinite;
        }

        @keyframes drift {
            0%, 100% { transform: translate(0, 0) scale(1); }
            50%      { transform: translate(-18px, 22px) scale(1.07); }
        }

        .left-panel > *:not(.orb) { position: relative; z-index: 1; }

        .brand-header {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            margin-bottom: 2.6rem;
        }

        .brand-mark {
            width: 48px; height: 48px;
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.16);
            border: 1px solid rgba(255, 255, 255, 0.24);
            backdrop-filter: blur(8px);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .brand-mark img {
            height: 30px; width: auto;
            object-fit: contain;
            filter: brightness(0) invert(1);
        }

        .brand-header h1 {
            font-size: 1.4rem;
            font-weight: 800;
            letter-spacing: -0.4px;
            line-height: 1.1;
        }

        .brand-header h1 span { color: var(--blue-200); }

        .brand-header small {
            display: block;
            font-size: 0.68rem;
            font-weight: 500;
            letter-spacing: 0.5px;
            color: rgba(255, 255, 255, 0.72);
            margin-top: 3px;
        }

        .big-tagline {
            font-size: clamp(1.55rem, 2.6vw, 2rem);
            font-weight: 700;
            line-height: 1.25;
            letter-spacing: -0.5px;
            margin-bottom: 0.9rem;
        }

        .big-tagline span { color: var(--blue-200); }

        .description {
            color: rgba(255, 255, 255, 0.82);
            font-size: 0.9rem;
            line-height: 1.65;
            max-width: 42ch;
            margin-bottom: 2.2rem;
        }

        .feature-pills {
            display: flex;
            flex-direction: column;
            gap: 0.7rem;
            margin-bottom: auto;
        }

        .pill-item {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            padding: 0.8rem 1.1rem;
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.10);
            border: 1px solid rgba(255, 255, 255, 0.16);
            backdrop-filter: blur(10px);
            font-size: 0.85rem;
            font-weight: 500;
            transition: transform 0.35s var(--spring), background 0.3s ease;
        }

        .pill-item:hover {
            transform: translateX(6px);
            background: rgba(255, 255, 255, 0.17);
        }

        .pill-item .pill-icon {
            width: 30px; height: 30px;
            border-radius: 9px;
            background: rgba(255, 255, 255, 0.18);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.92rem;
            color: #ffffff;
            flex-shrink: 0;
        }

        /* Bottom strip */
        .panel-foot {
            margin-top: 2.2rem;
            padding-top: 1.2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.18);
            display: flex;
            align-items: center;
            gap: 0.6rem;
            font-size: 0.74rem;
            color: rgba(255, 255, 255, 0.78);
            line-height: 1.5;
        }

        .panel-foot i { font-size: 0.95rem; color: var(--blue-200); }

        /* =========================================================
           RIGHT PANEL
           ========================================================= */
        .right-panel {
            padding: 2.9rem 2.9rem 2.2rem;
            background: #ffffff;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .form-head { margin-bottom: 1.5rem; }

        .form-head .head-logo {
            width: 52px; height: 52px;
            border-radius: 15px;
            background: var(--blue-50);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
        }

        .form-head .head-logo img { height: 30px; width: auto; object-fit: contain; }

        .form-head h2 {
            font-size: 1.45rem;
            font-weight: 700;
            letter-spacing: -0.5px;
            margin-bottom: 0.3rem;
        }

        .form-head p { color: var(--ink-400); font-size: 0.87rem; }

        /* ---- alerts ---- */
        .alert-custom {
            display: none;
            align-items: flex-start;
            gap: 0.6rem;
            border-radius: 12px;
            padding: 0.8rem 1rem;
            margin-bottom: 1rem;
            font-size: 0.83rem;
            line-height: 1.5;
            border: 1px solid transparent;
        }

        .alert-custom.show { display: flex; animation: shake 0.42s ease; }
        .alert-custom i { font-size: 1rem; margin-top: 1px; flex-shrink: 0; }

        .alert-custom.danger  { background: var(--danger-bg);  color: var(--danger);  border-color: #f6c9c9; }
        .alert-custom.success { background: var(--success-bg); color: var(--success); border-color: #bfe6d1; }
        .alert-custom.warn    { background: var(--warn-bg);    color: var(--warn);    border-color: #f2dda6; }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        /* ---- fields ---- */
        .form-group { margin-bottom: 0.95rem; }

        .form-group label,
        .role-section label {
            font-weight: 600;
            font-size: 0.78rem;
            letter-spacing: 0.1px;
            display: block;
            margin-bottom: 0.4rem;
        }

        .input-wrapper { position: relative; }

        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--ink-400);
            font-size: 1rem;
            pointer-events: none;
            transition: color 0.2s ease;
            z-index: 2;
        }

        .form-control,
        .form-select {
            width: 100%;
            height: var(--field-h);
            padding: 0 1rem 0 2.9rem;
            border: 1.5px solid var(--line);
            border-radius: 13px;
            font-size: 0.9rem;
            font-family: inherit;
            color: var(--ink-900);
            background: #fbfcfe;
            transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
        }

        .form-control::placeholder { color: #b6c2d3; }

        .form-control:hover,
        .form-select:hover { border-color: #cfdbec; }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--blue-500);
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(25, 118, 210, 0.13);
            outline: none;
        }

        .input-wrapper:focus-within .input-icon,
        .role-select-wrapper:focus-within .input-icon { color: var(--blue-500); }

        #password { padding-right: 3rem; }

        .password-toggle {
            position: absolute;
            right: 0.55rem;
            top: 50%;
            transform: translateY(-50%);
            width: 34px; height: 34px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: transparent;
            border: none;
            border-radius: 9px;
            color: var(--ink-400);
            font-size: 1.05rem;
            cursor: pointer;
            transition: color 0.2s ease, background 0.2s ease;
        }

        .password-toggle:hover { color: var(--blue-500); background: var(--blue-50); }

        /* Caps Lock hint */
        .caps-hint {
            display: none;
            align-items: center;
            gap: 0.35rem;
            margin-top: 0.4rem;
            font-size: 0.74rem;
            font-weight: 600;
            color: var(--warn);
        }

        .caps-hint.show { display: flex; }

        /* ---- role select ---- */
        .role-section { margin-bottom: 0.9rem; }
        .role-select-wrapper { position: relative; }

        .form-select {
            appearance: none;
            -webkit-appearance: none;
            padding-right: 2.6rem;
            cursor: pointer;
        }

        .select-arrow {
            position: absolute;
            right: 1.05rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--ink-400);
            font-size: 0.78rem;
            pointer-events: none;
        }

        .field-note {
            font-size: 0.72rem;
            color: var(--ink-400);
            margin-top: 0.35rem;
        }

        /* ---- options row ---- */
        .options-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 0.75rem;
            margin: 0.9rem 0 1.3rem;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.81rem;
            color: var(--ink-600);
            cursor: pointer;
            user-select: none;
        }

        .remember-me input {
            width: 17px; height: 17px;
            accent-color: var(--blue-500);
            cursor: pointer;
        }

        .forgot-link {
            color: var(--blue-500);
            text-decoration: none;
            font-size: 0.81rem;
            font-weight: 600;
            transition: color 0.2s ease;
        }

        .forgot-link:hover { color: var(--blue-600); text-decoration: underline; }

        /* ---- button ---- */
        .btn-login {
            position: relative;
            width: 100%;
            height: 52px;
            border: none;
            border-radius: 13px;
            background: linear-gradient(100deg, var(--blue-500), var(--blue-400));
            color: #ffffff;
            font-family: inherit;
            font-weight: 600;
            font-size: 0.95rem;
            letter-spacing: 0.2px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.55rem;
            cursor: pointer;
            overflow: hidden;
            box-shadow: 0 8px 20px rgba(25, 118, 210, 0.30);
            transition: transform 0.25s var(--spring), box-shadow 0.25s ease, filter 0.2s ease;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 26px rgba(25, 118, 210, 0.38);
            filter: saturate(1.08);
        }

        .btn-login:active { transform: translateY(0) scale(0.99); }

        .btn-login:disabled {
            opacity: 0.75;
            cursor: not-allowed;
            transform: none !important;
            box-shadow: none;
        }

        .btn-login .arrow { transition: transform 0.3s var(--spring); }
        .btn-login:hover .arrow { transform: translateX(4px); }

        .btn-login .spinner {
            display: none;
            width: 19px; height: 19px;
            border: 2px solid rgba(255, 255, 255, 0.35);
            border-top-color: #ffffff;
            border-radius: 50%;
            animation: spin 0.75s linear infinite;
        }

        .btn-login.loading .spinner { display: inline-block; }
        .btn-login.loading .btn-text,
        .btn-login.loading .arrow { display: none; }

        @keyframes spin { to { transform: rotate(360deg); } }

        /* ---- focus visibility ---- */
        .btn-login:focus-visible,
        .password-toggle:focus-visible,
        .forgot-link:focus-visible,
        .remember-me input:focus-visible {
            outline: 2px solid var(--blue-500);
            outline-offset: 2px;
        }

        /* ---- footer ---- */
        .right-footer {
            margin-top: 1.6rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
            text-align: center;
        }

        .secure-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.3rem 0.7rem;
            border-radius: 30px;
            background: var(--success-bg);
            color: var(--success);
            font-size: 0.7rem;
            font-weight: 600;
        }

        .secure-pill.insecure { background: var(--warn-bg); color: var(--warn); }

        .footer-note { font-size: 0.7rem; color: var(--ink-400); letter-spacing: 0.2px; }

        /* Honeypot — hidden from people, visible to naive bots */
        .hp-field {
            position: absolute !important;
            width: 1px; height: 1px;
            overflow: hidden;
            clip: rect(0 0 0 0);
            white-space: nowrap;
        }

        /* =========================================================
           SPLIT ANIMATION
           ========================================================= */
        .left-panel  { animation: joinLeft 0.8s var(--spring) both; }
        .right-panel { animation: joinRight 0.8s var(--spring) both; }

        @keyframes joinLeft  { from { transform: translateX(-48px); opacity: 0; } to { transform: none; opacity: 1; } }
        @keyframes joinRight { from { transform: translateX(48px);  opacity: 0; } to { transform: none; opacity: 1; } }

        .login-card::after {
            content: '';
            position: absolute;
            top: 0; bottom: 0; left: 50%;
            width: 2px;
            margin-left: -1px;
            background: linear-gradient(to bottom, transparent, #ffffff 20%, var(--blue-200) 50%, #ffffff 80%, transparent);
            box-shadow: 0 0 18px 4px rgba(144, 202, 249, 0.8);
            opacity: 0;
            transform: scaleY(0);
            pointer-events: none;
            z-index: 3;
        }

        body.is-splitting { overflow: hidden; }

        body.is-splitting .login-card {
            background: transparent;
            box-shadow: none;
            overflow: visible;
        }

        body.is-splitting .login-card::after { animation: seam 0.55s ease-out forwards; }

        body.is-splitting .left-panel {
            border-radius: var(--radius) 0 0 var(--radius);
            box-shadow: 0 30px 60px rgba(10, 43, 78, 0.14);
            animation: splitLeft 0.9s cubic-bezier(.77, 0, .18, 1) 0.2s forwards;
        }

        body.is-splitting .right-panel {
            border-radius: 0 var(--radius) var(--radius) 0;
            box-shadow: 0 30px 60px rgba(10, 43, 78, 0.14);
            animation: splitRight 0.9s cubic-bezier(.77, 0, .18, 1) 0.2s forwards;
        }

        @keyframes seam {
            0%   { opacity: 0; transform: scaleY(0); }
            40%  { opacity: 1; transform: scaleY(1); }
            100% { opacity: 0; transform: scaleY(1); }
        }
        @keyframes splitLeft  { 0% { transform: none; opacity: 1; } 100% { transform: translateX(-65vw); opacity: 0; } }
        @keyframes splitRight { 0% { transform: none; opacity: 1; } 100% { transform: translateX(65vw);  opacity: 0; } }

        .split-reveal {
            position: fixed;
            inset: 0;
            z-index: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 1.5rem;
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transition: opacity 0.5s ease 0.45s, visibility 0s linear 0.45s;
        }

        body.is-splitting .split-reveal { opacity: 1; visibility: visible; }

        .reveal-mark {
            width: 66px; height: 66px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--blue-500), var(--blue-400));
            color: #ffffff;
            font-size: 1.9rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            box-shadow: 0 0 0 10px rgba(25, 118, 210, 0.10);
            transform: scale(0.4);
        }

        body.is-splitting .reveal-mark { animation: markPop 0.5s cubic-bezier(.34, 1.56, .64, 1) 0.6s forwards; }

        @keyframes markPop { to { transform: scale(1); } }

        .reveal-title { font-size: 1.15rem; font-weight: 700; }
        .reveal-sub   { font-size: 0.86rem; color: var(--ink-600); margin-top: 0.25rem; }

        .reveal-bar {
            width: 150px; height: 3px;
            border-radius: 3px;
            background: #dbe7f6;
            margin-top: 1.1rem;
            overflow: hidden;
            position: relative;
        }

        .reveal-bar::after {
            content: '';
            position: absolute;
            top: 0; left: 0;
            height: 100%; width: 40%;
            border-radius: 3px;
            background: var(--blue-500);
            animation: barSlide 1.1s ease-in-out infinite;
        }

        @keyframes barSlide {
            from { transform: translateX(-100%); }
            to   { transform: translateX(250%); }
        }

        /* =========================================================
           RESPONSIVE
           ========================================================= */
        @media (max-width: 880px) {
            .login-card {
                grid-template-columns: 1fr;
                border-radius: 20px;
                min-height: 0;
            }

            .left-panel { padding: 2rem 1.75rem; }
            .right-panel { padding: 2rem 1.75rem 1.6rem; }
            .description { margin-bottom: 1.6rem; }
            .panel-foot { margin-top: 1.5rem; }

            .left-panel  { animation-name: joinTop; }
            .right-panel { animation-name: joinBottom; }
            .login-card::after { display: none; }

            body.is-splitting .left-panel  { border-radius: 20px 20px 0 0; animation-name: splitUp; }
            body.is-splitting .right-panel { border-radius: 0 0 20px 20px; animation-name: splitDown; }

            @keyframes joinTop    { from { transform: translateY(-40px); opacity: 0; } to { transform: none; opacity: 1; } }
            @keyframes joinBottom { from { transform: translateY(40px);  opacity: 0; } to { transform: none; opacity: 1; } }
            @keyframes splitUp    { 0% { transform: none; opacity: 1; } 100% { transform: translateY(-110vh); opacity: 0; } }
            @keyframes splitDown  { 0% { transform: none; opacity: 1; } 100% { transform: translateY(110vh);  opacity: 0; } }
        }

        @media (max-width: 520px) {
            :root { --field-h: 48px; }

            .login-card { border-radius: 16px; }
            .left-panel, .right-panel { padding: 1.5rem 1.35rem; }
            .feature-pills { gap: 0.55rem; }
            .pill-item { padding: 0.7rem 0.9rem; font-size: 0.8rem; }
            .form-head h2 { font-size: 1.28rem; }

            .options-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.55rem;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .left-panel, .right-panel, .login-card::after,
            .reveal-mark, .reveal-bar::after, .orb {
                animation: none !important;
            }
            .reveal-mark { transform: none; }
            .btn-login:hover, .pill-item:hover { transform: none; }
        }
    </style>
</head>
<body>

<!-- Revealed behind the card when it splits open -->
<div class="split-reveal" role="status" aria-live="polite">
    <div class="reveal-mark"><i class="bi bi-check-lg"></i></div>
    <p class="reveal-title">Signing you in</p>
    <p class="reveal-sub">Verifying your credentials</p>
    <div class="reveal-bar"></div>
</div>

<div class="login-wrapper">
    <div class="login-card">

        <!-- ================= LEFT PANEL ================= -->
        <div class="left-panel">
            <span class="orb orb-1"></span>
            <span class="orb orb-2"></span>
            <span class="orb orb-3"></span>

            <div class="brand-header">
                <span class="brand-mark">
                    <img src="/polymedic/public/assets/images/logo4.png" alt="">
                </span>
                <h1>
                    Poly<span>Medic</span>
                    <small>DIAGNOSTIC INFORMATION SYSTEM</small>
                </h1>
            </div>

            <div class="big-tagline">
                Precision Diagnostics,<br><span>Seamless Care</span>
            </div>

            <p class="description">
                A comprehensive platform for managing patient records, diagnostic
                requests, laboratory findings, and billing — all in one place.
            </p>

            <div class="feature-pills">
                <div class="pill-item">
                    <span class="pill-icon"><i class="bi bi-clipboard2-pulse"></i></span>
                    Laboratory &amp; Radiology Findings
                </div>
                <div class="pill-item">
                    <span class="pill-icon"><i class="bi bi-people"></i></span>
                    Integrated Patient Management
                </div>
                <div class="pill-item">
                    <span class="pill-icon"><i class="bi bi-credit-card-2-front"></i></span>
                    Automated Billing &amp; Payments
                </div>
            </div>

            <div class="panel-foot">
                <i class="bi bi-shield-lock-fill"></i>
                <span>Access is restricted to authorised staff. Sign-in attempts and record access are logged.</span>
            </div>
        </div>

        <!-- ================= RIGHT PANEL ================= -->
        <div class="right-panel">

            <div class="form-head">
                <span class="head-logo">
                    <img src="/polymedic/public/assets/images/logo4.png" alt="PolyMedic">
                </span>
                <h2>Welcome back</h2>
                <p>Sign in to continue to your workspace</p>
            </div>

            <!-- Flash messages (escaped) -->
            <?php if ($errorFlash): ?>
                <div class="alert-custom show danger" role="alert">
                    <i class="bi bi-exclamation-circle-fill"></i>
                    <span><?= esc($errorFlash) ?></span>
                </div>
            <?php endif; ?>

            <?php if ($successFlash): ?>
                <div class="alert-custom show success" role="status">
                    <i class="bi bi-check-circle-fill"></i>
                    <span><?= esc($successFlash) ?></span>
                </div>
            <?php endif; ?>

            <!-- Client-side validation messages -->
            <div id="loginAlert" class="alert-custom danger" role="alert" aria-live="assertive">
                <i class="bi bi-exclamation-circle-fill"></i>
                <span id="alertMessage"></span>
            </div>

            <!-- Shown by JS when the page is not served over HTTPS -->
            <div id="insecureWarning" class="alert-custom warn" role="alert">
                <i class="bi bi-shield-exclamation"></i>
                <span>This page is not using a secure (HTTPS) connection. Credentials sent from here can be read in transit.</span>
            </div>

            <form action="<?= base_url('auth/authenticate') ?>" method="POST" id="loginForm" novalidate autocomplete="on">
                <?= csrf_field() ?>

                <!-- Bot traps: the controller must reject a filled honeypot
                     and a form submitted within ~2 seconds of rendering. -->
                <div class="hp-field" aria-hidden="true">
                    <label for="company_website">Leave this field empty</label>
                    <input type="text" id="company_website" name="company_website" tabindex="-1" autocomplete="off">
                </div>
                <input type="hidden" name="form_rendered_at" value="<?= time() ?>">

                <!-- username -->
                <div class="form-group">
                    <label for="username">Username</label>
                    <div class="input-wrapper">
                        <span class="input-icon"><i class="bi bi-person"></i></span>
                        <input type="text"
                               class="form-control"
                               id="username"
                               name="username"
                               placeholder="Enter your username"
                               value="<?= esc($oldUsername) ?>"
                               maxlength="64"
                               autocomplete="username"
                               autocapitalize="none"
                               autocorrect="off"
                               spellcheck="false"
                               required
                               autofocus>
                    </div>
                </div>

                <!-- password -->
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-wrapper">
                        <span class="input-icon"><i class="bi bi-lock"></i></span>
                        <input type="password"
                               class="form-control"
                               id="password"
                               name="password"
                               placeholder="Enter your password"
                               maxlength="128"
                               autocomplete="current-password"
                               spellcheck="false"
                               required>
                        <button type="button"
                                class="password-toggle"
                                id="passwordToggle"
                                aria-label="Show password"
                                aria-pressed="false"
                                aria-controls="password">
                            <i class="bi bi-eye" id="passwordIcon" aria-hidden="true"></i>
                        </button>
                    </div>
                    <div class="caps-hint" id="capsHint">
                        <i class="bi bi-capslock-fill"></i> Caps Lock is on
                    </div>
                </div>

                <!-- role -->
                <div class="role-section">
                    <label for="selectedRole">Role</label>
                    <div class="role-select-wrapper">
                        <span class="input-icon"><i class="bi bi-person-badge"></i></span>
                        <select class="form-select" name="role" id="selectedRole">
                            <?php foreach ($roles as $value => $label): ?>
                                <option value="<?= esc($value, 'attr') ?>"<?= $oldRole === $value ? ' selected' : '' ?>>
                                    <?= esc($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <span class="select-arrow"><i class="bi bi-chevron-down"></i></span>
                    </div>
                    <p class="field-note">Your actual permissions come from your account, not this selection.</p>
                </div>

                <!-- options -->
                <div class="options-row">
                    <label class="remember-me">
                        <input type="checkbox" id="rememberMe" name="remember" value="1">
                        Keep me signed in
                    </label>
                    <a href="<?= base_url('auth/forgot-password') ?>" class="forgot-link">Forgot password?</a>
                </div>

                <button type="submit" class="btn-login" id="loginBtn">
                    <span class="spinner" aria-hidden="true"></span>
                    <span class="btn-text">Sign In</span>
                    <i class="bi bi-arrow-right arrow" aria-hidden="true"></i>
                </button>
            </form>

            <div class="right-footer">
                <span class="secure-pill" id="securePill">
                    <i class="bi bi-shield-check"></i> Secure connection
                </span>
                <span class="footer-note">PolyMedic v2.4.1 · © <?= date('Y') ?> PolyMedic Corp.</span>
            </div>

        </div>
    </div>
</div>

<script>
(function() {
    'use strict';

    var form       = document.getElementById('loginForm');
    var usernameEl = document.getElementById('username');
    var passwordEl = document.getElementById('password');
    var loginBtn   = document.getElementById('loginBtn');
    var alertDiv   = document.getElementById('loginAlert');
    var alertMsg   = document.getElementById('alertMessage');
    var toggleBtn  = document.getElementById('passwordToggle');
    var passIcon   = document.getElementById('passwordIcon');
    var capsHint   = document.getElementById('capsHint');

    // ---------- password visibility ----------
    function hidePassword() {
        passwordEl.type = 'password';
        passIcon.className = 'bi bi-eye';
        toggleBtn.setAttribute('aria-pressed', 'false');
        toggleBtn.setAttribute('aria-label', 'Show password');
    }

    toggleBtn.addEventListener('click', function() {
        if (passwordEl.type === 'text') {
            hidePassword();
        } else {
            passwordEl.type = 'text';
            passIcon.className = 'bi bi-eye-slash';
            toggleBtn.setAttribute('aria-pressed', 'true');
            toggleBtn.setAttribute('aria-label', 'Hide password');
        }
        passwordEl.focus();
    });

    // Never leave the password readable once focus moves away
    passwordEl.addEventListener('blur', function() {
        if (passwordEl.type === 'text') hidePassword();
        capsHint.classList.remove('show');
    });

    // ---------- Caps Lock hint ----------
    function checkCaps(e) {
        if (typeof e.getModifierState !== 'function') return;
        capsHint.classList.toggle('show', e.getModifierState('CapsLock'));
    }

    passwordEl.addEventListener('keydown', checkCaps);
    passwordEl.addEventListener('keyup', checkCaps);

    // ---------- HTTPS check ----------
    var isLocal = ['localhost', '127.0.0.1', '::1'].indexOf(location.hostname) !== -1;

    if (location.protocol !== 'https:' && location.protocol !== 'file:' && !isLocal) {
        var pill = document.getElementById('securePill');
        document.getElementById('insecureWarning').classList.add('show');
        pill.classList.add('insecure');
        pill.innerHTML = '<i class="bi bi-shield-exclamation"></i> Not secure (HTTP)';
    }

    // ---------- submit ----------
    var submitting = false;

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        if (submitting) return;

        var username = usernameEl.value.trim();
        var password = passwordEl.value;

        if (!username || !password) {
            alertDiv.classList.add('show');
            alertMsg.textContent = 'Please enter both your username and password.';
            (username ? passwordEl : usernameEl).focus();
            return;
        }

        submitting = true;
        usernameEl.value = username;
        hidePassword();

        loginBtn.classList.add('loading');
        loginBtn.disabled = true;
        alertDiv.classList.remove('show');
        capsHint.classList.remove('show');

        function send() {
            HTMLFormElement.prototype.submit.call(form);
        }

        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            document.body.classList.add('is-splitting');
            send();
            return;
        }

        setTimeout(function() {
            document.body.classList.add('is-splitting');
            setTimeout(send, 1000);
        }, 350);
    });

    // ---------- restore state on Back ----------
    window.addEventListener('pageshow', function(e) {
        if (e.persisted) {
            submitting = false;
            document.body.classList.remove('is-splitting');
            loginBtn.classList.remove('loading');
            loginBtn.disabled = false;
            passwordEl.value = '';
        }
    });

    // ---------- auto-hide server flash messages ----------
    document.querySelectorAll('.alert-custom.show').forEach(function(el) {
        if (el.id === 'insecureWarning') return; // this one stays put
        setTimeout(function() { el.classList.remove('show'); }, 8000);
    });
}());
</script>

<?php
/* =============================================================
   CONTROLLER-SIDE CHECKLIST (this view cannot enforce any of it)

   1. Rate limiting: cap attempts per username AND per IP, with a
      growing delay or lockout. CodeIgniter's Throttler works:
        $throttler = service('throttler');
        if ($throttler->check($ip, 5, MINUTE) === false) { ... }

   2. Generic errors only: use one message such as
      "Invalid username or password" for a wrong username, a wrong
      password and a disabled account, so nobody can enumerate
      valid usernames.

   3. Constant-time check: always run password_verify() against a
      dummy hash when the user is not found, so response timing
      does not reveal whether the username exists.

   4. Ignore the posted role for authorisation. Read the role from
      the user record; if it differs from what was posted, just
      continue with the stored one.

   5. Session: call session()->regenerate(true) right after a
      successful login to prevent session fixation.

   6. Honeypot: reject the request if company_website is non-empty,
      or if time() - form_rendered_at < 2.

   7. Cookies: in app/Config/Cookie.php set $secure = true,
      $httponly = true and $samesite = 'Lax' in production, and keep
      CSRF protection enabled in app/Config/Filters.php.

   8. Send security headers (HSTS, X-Frame-Options: DENY,
      X-Content-Type-Options: nosniff, a Content-Security-Policy)
      from a filter or the web server config.

   9. Passwords must be stored with password_hash(PASSWORD_DEFAULT
      or PASSWORD_ARGON2ID) — never md5/sha1.

  10. "Keep me signed in" should issue a random, hashed, expiring
      token stored server-side — never the user id or password.
   ============================================================= */
?>

</body>
</html>