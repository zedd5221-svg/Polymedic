<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>PolyMedic - Medical Technologist Dashboard</title>

    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="preconnect" href="https://unpkg.com" crossorigin>

    <link href="<?= base_url() ?>assets/css/AppointmentStyle.css" rel="stylesheet">
    <link href="<?= base_url() ?>assets/css/admin.css" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <link rel="stylesheet" href="https://unpkg.com/aos@2.3.4/dist/aos.css">
    <script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>

    <style>
        .dropdown-menu:not(.show) { display: none !important; }

        .header-title-icon { width: 24px; height: 24px; object-fit: contain; flex-shrink: 0; }
        .logo-badge        { width: 44px; height: 44px; flex-shrink: 0; }
        .logo-badge img    { width: 30px; height: 30px; object-fit: contain; }

        .pm-stat-img, .av-avatar-img, .pm-avatar-img { width: 100%; height: 100%; object-fit: contain; }
        .pm-stat-icon { width: 42px; height: 42px; flex-shrink: 0; }
        .pm-avatar    { width: 34px; height: 34px; flex-shrink: 0; }
        .av-avatar    { width: 72px; height: 72px; flex-shrink: 0; }

        .av-avatar-img, .pm-avatar-img { object-fit: cover; }

        .admin-header img, .admin-sidebar img { max-width: 100%; }

        .header-title-group { display: flex; align-items: center; gap: 0.6rem; }
    </style>

    <style>
    :root {
        --sidebar-width: 282px;
        --sidebar-collapsed-width: 84px;
        --header-height: 64px;

        --active-blue: #1976d2;
        --active-blue-dark: #1565c0;
        --active-blue-light: #e3f2fd;
        --text-blue: #1e40af;
        --icon-gray: #9ca3af;
        --bg-light: #f4f7fb;

        --sb-bg-top: #123566;
        --sb-bg-bottom: #0a1f3d;
        --sb-text: #cddcef;
        --sb-text-dim: #8aa4c4;
        --sb-heading: #6e8bb0;
        --sb-hover-bg: rgba(255, 255, 255, 0.08);
        --sb-hover-text: #ffffff;
        --sb-border: rgba(255, 255, 255, 0.10);

        --sb-active-from: #1976d2;
        --sb-active-to: #43a3f5;
        --sb-active-glow: rgba(25, 118, 210, 0.45);

        --sidebar-ease: cubic-bezier(0.4, 0, 0.2, 1);
        --sidebar-spring: cubic-bezier(0.22, 1, 0.36, 1);
        --sidebar-speed: 0.32s;
    }

    * { box-sizing: border-box; }

    .admin-sidebar {
        width: var(--sidebar-width);
        position: fixed;
        top: 0; left: 0; bottom: 0;
        z-index: 1040;
        display: flex;
        flex-direction: column;
        background: linear-gradient(170deg, var(--sb-bg-top) 0%, var(--sb-bg-bottom) 100%) !important;
        border-right: 1px solid var(--sb-border) !important;
        box-shadow: 4px 0 24px rgba(8, 20, 40, 0.16);
        overflow: visible;
        transition: width var(--sidebar-speed) var(--sidebar-ease),
                    transform var(--sidebar-speed) var(--sidebar-ease);
    }

    .admin-sidebar::before {
        content: '';
        position: absolute;
        top: -90px; left: -60px;
        width: 240px; height: 240px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(67, 163, 245, 0.22), transparent 70%);
        pointer-events: none;
    }

    .sidebar-header {
        position: relative;
        height: 82px;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1rem 1.25rem;
        border-bottom: 1px solid var(--sb-border) !important;
        transition: padding var(--sidebar-speed) var(--sidebar-ease);
    }

    .sidebar-logo {
        display: flex;
        align-items: center;
        gap: 0.7rem;
        min-width: 0;
        text-decoration: none;
        transition: gap var(--sidebar-speed) var(--sidebar-ease);
    }

    .logo-badge {
        width: 44px; height: 44px;
        flex-shrink: 0;
        border-radius: 13px;
        background: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 6px 16px rgba(4, 16, 35, 0.35);
        transition: transform 0.35s var(--sidebar-spring),
                    box-shadow 0.3s ease;
    }

    .sidebar-logo:hover .logo-badge {
        transform: translateY(-2px) rotate(-4deg);
        box-shadow: 0 10px 22px rgba(4, 16, 35, 0.45);
    }

    .logo-badge img { width: 30px; height: 30px; object-fit: contain; }

    .logo-text {
        font-size: 1.05rem;
        font-weight: 700;
        color: #ffffff !important;
        letter-spacing: 0.3px;
        line-height: 1.15;
        white-space: nowrap;
        opacity: 1;
        max-width: 190px;
        overflow: hidden;
        display: inline-block;
        transition: opacity calc(var(--sidebar-speed) * 0.55) var(--sidebar-ease),
                    max-width var(--sidebar-speed) var(--sidebar-ease);
    }

    .logo-text small {
        display: block;
        margin-top: 3px;
        font-size: 0.62rem;
        font-weight: 500;
        letter-spacing: 0.4px;
        color: var(--sb-text-dim) !important;
    }

    .sidebar-close {
        display: none;
        width: 34px; height: 34px;
        flex-shrink: 0;
        align-items: center;
        justify-content: center;
        border: none;
        border-radius: 9px;
        background: rgba(255, 255, 255, 0.10);
        color: #ffffff;
        font-size: 0.85rem;
        cursor: pointer;
        transition: background 0.2s ease, transform 0.2s ease;
    }

    .sidebar-close:hover {
        background: rgba(255, 255, 255, 0.2);
        transform: rotate(90deg);
    }

    .sidebar-nav {
        flex: 1 1 auto;
        min-height: 0;
        overflow-y: auto;
        overflow-x: hidden;
        padding: 0.85rem 0.75rem 1rem;
        transition: padding var(--sidebar-speed) var(--sidebar-ease);
    }

    .sidebar-nav ul,
    .sidebar-footer ul {
        list-style: none;
        padding: 0; margin: 0;
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }

    .sidebar-nav::-webkit-scrollbar { width: 5px; }
    .sidebar-nav::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.16); border-radius: 10px; }
    .sidebar-nav::-webkit-scrollbar-thumb:hover { background: rgba(255, 255, 255, 0.28); }
    .sidebar-nav { scrollbar-width: thin; scrollbar-color: rgba(255, 255, 255, 0.18) transparent; }

    .sidebar-footer {
        flex-shrink: 0;
        padding: 0.7rem 0.75rem 1.1rem;
        border-top: 1px solid var(--sb-border);
        transition: padding var(--sidebar-speed) var(--sidebar-ease);
    }

    .sidebar-toggle-item { margin-bottom: 0.4rem; }

    .sidebar-toggle-btn {
        width: 100%;
        min-height: 42px;
        display: flex;
        align-items: center;
        gap: 0.7rem;
        padding: 0.6rem 0.8rem;
        border: 1px dashed rgba(255, 255, 255, 0.18);
        border-radius: 11px;
        background: rgba(255, 255, 255, 0.04);
        color: var(--sb-text-dim);
        font-size: 0.78rem;
        font-weight: 600;
        letter-spacing: 0.2px;
        cursor: pointer;
        transition: background 0.22s ease,
                    color 0.22s ease,
                    border-color 0.22s ease,
                    transform 0.15s ease,
                    gap var(--sidebar-speed) var(--sidebar-ease),
                    padding var(--sidebar-speed) var(--sidebar-ease);
    }

    .sidebar-toggle-btn i {
        width: 24px; min-width: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.95rem;
        transition: transform 0.35s var(--sidebar-spring);
    }

    .sidebar-toggle-btn span {
        white-space: nowrap;
        opacity: 1;
        max-width: 180px;
        overflow: hidden;
        display: inline-block;
        transition: opacity calc(var(--sidebar-speed) * 0.55) var(--sidebar-ease),
                    max-width var(--sidebar-speed) var(--sidebar-ease);
    }

    .sidebar-toggle-btn:hover {
        background: rgba(255, 255, 255, 0.10);
        border-color: rgba(255, 255, 255, 0.32);
        color: #ffffff;
    }

    .sidebar-toggle-btn:hover i { transform: translateX(-3px); }
    .sidebar-toggle-btn:active { transform: scale(0.97); }

    .sidebar-toggle-btn:focus-visible,
    .menu-btn:focus-visible,
    .sidebar-close:focus-visible,
    .mobile-menu-btn:focus-visible {
        outline: 2px solid var(--sb-active-to);
        outline-offset: 2px;
    }

    .sidebar-nav .nav-section {
        font-size: 0.63rem;
        text-transform: uppercase;
        letter-spacing: 1.4px;
        color: var(--sb-heading) !important;
        padding: 0.95rem 0.8rem 0.3rem;
        font-weight: 700;
        white-space: nowrap;
        position: relative;
        transition: padding var(--sidebar-speed) var(--sidebar-ease);
    }

    .sidebar-nav .nav-section span {
        opacity: 1;
        display: inline-block;
        transition: opacity calc(var(--sidebar-speed) * 0.5) var(--sidebar-ease);
    }

    .menu-item {
        position: relative;
        animation: navFadeIn 0.45s var(--sidebar-spring) both;
        animation-delay: calc(var(--i, 0) * 45ms);
    }

    @keyframes navFadeIn {
        from { opacity: 0; transform: translateX(-14px); }
        to   { opacity: 1; transform: none; }
    }

    .menu-btn {
        position: relative;
        display: flex;
        align-items: center;
        gap: 0.8rem;
        width: 100%;
        min-height: 46px;
        padding: 0.68rem 0.85rem;
        border-radius: 12px !important;
        text-decoration: none;
        font-size: 0.845rem;
        font-weight: 600;
        letter-spacing: 0.1px;
        background: transparent;
        color: var(--sb-text) !important;
        cursor: pointer;
        overflow: hidden;
        transition: background 0.24s var(--sidebar-ease),
                    color 0.24s var(--sidebar-ease),
                    transform 0.24s var(--sidebar-spring),
                    box-shadow 0.28s var(--sidebar-ease),
                    gap var(--sidebar-speed) var(--sidebar-ease),
                    padding var(--sidebar-speed) var(--sidebar-ease);
    }

    .menu-btn::before {
        content: '';
        position: absolute;
        left: 0;
        top: 50%;
        width: 3px;
        height: 0;
        border-radius: 0 4px 4px 0;
        background: var(--sb-active-to);
        transform: translateY(-50%);
        transition: height 0.28s var(--sidebar-spring);
    }

    .menu-icon {
        font-size: 1.05rem;
        flex-shrink: 0;
        width: 24px;
        height: 24px;
        text-align: center;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: var(--sb-text-dim) !important;
        transition: color 0.24s ease,
                    transform 0.3s var(--sidebar-spring);
    }

    .menu-btn > span {
        white-space: nowrap;
        opacity: 1;
        max-width: 180px;
        overflow: hidden;
        display: inline-block;
        color: inherit !important;
        transition: opacity calc(var(--sidebar-speed) * 0.55) var(--sidebar-ease),
                    max-width var(--sidebar-speed) var(--sidebar-ease);
    }

    .menu-item:not(.active) .menu-btn:hover {
        background: var(--sb-hover-bg) !important;
        color: var(--sb-hover-text) !important;
        transform: translateX(4px);
    }

    .menu-item:not(.active) .menu-btn:hover::before { height: 22px; }

    .menu-item:not(.active) .menu-btn:hover .menu-icon {
        color: var(--sb-active-to) !important;
        transform: scale(1.14) translateY(-1px);
    }

    .menu-btn:active { transform: translateX(4px) scale(0.985); }

    .menu-item.active .menu-btn {
        background: linear-gradient(100deg, var(--sb-active-from), var(--sb-active-to)) !important;
        color: #ffffff !important;
        box-shadow: 0 8px 20px var(--sb-active-glow) !important;
    }

    .menu-item.active .menu-btn::before {
        height: 60%;
        background: #ffffff;
        opacity: 0.9;
    }

    .menu-item.active .menu-icon,
    .menu-item.active .menu-btn span { color: #ffffff !important; }

    .menu-item.active .menu-btn::after {
        content: '';
        position: absolute;
        top: 0;
        left: -60%;
        width: 45%;
        height: 100%;
        background: linear-gradient(100deg, transparent, rgba(255, 255, 255, 0.28), transparent);
        transform: skewX(-18deg);
        animation: activeShine 4.5s ease-in-out 1.2s infinite;
    }

    @keyframes activeShine {
        0%   { left: -60%; }
        45%  { left: 130%; }
        100% { left: 130%; }
    }

    /* =========================================================
       SPA NAVIGATION FEEDBACK
       ========================================================= */

    .menu-item.is-navigating .menu-btn {
        animation: menuNavPulse 0.45s cubic-bezier(0.22, 1, 0.36, 1);
    }

    @keyframes menuNavPulse {
        0%   { transform: translateX(4px) scale(1); }
        35%  { transform: translateX(6px) scale(1.025); }
        100% { transform: translateX(4px) scale(1); }
    }

    .menu-item.is-navigating .menu-btn::before {
        height: 26px;
        background: var(--sb-active-to);
    }

    .admin-content {
        transition: opacity 0.14s ease, transform 0.14s ease;
    }

    .admin-content.is-leaving {
        opacity: 0;
        transform: translateY(6px);
    }

    .admin-content.is-entering {
        animation: contentEnter 0.3s cubic-bezier(0.22, 1, 0.36, 1);
    }

    @keyframes contentEnter {
        from { opacity: 0; transform: translateY(10px); }
        to   { opacity: 1; transform: none; }
    }

    .admin-main { position: relative; }

    .admin-content.is-loading::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        height: 2px;
        width: 100%;
        background: linear-gradient(90deg, transparent, var(--active-blue), transparent);
        background-size: 40% 100%;
        background-repeat: no-repeat;
        animation: contentProgress 1s linear infinite;
        z-index: 1;
    }

    @keyframes contentProgress {
        0%   { background-position: -40% 0; }
        100% { background-position: 140% 0; }
    }

    /* ========================================================= */

    .badge-notif {
        margin-left: auto;
        background: #ef4444 !important;
        color: #ffffff !important;
        font-size: 0.62rem;
        font-weight: 700;
        padding: 0.1rem 0.4rem;
        border-radius: 30px;
        min-width: 20px;
        height: 20px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 2px 8px rgba(239, 68, 68, 0.5);
        flex-shrink: 0;
        animation: badgePulse 2.4s ease-in-out infinite;
        transition: opacity calc(var(--sidebar-speed) * 0.55) var(--sidebar-ease);
    }

    @keyframes badgePulse {
        0%, 100% { box-shadow: 0 2px 8px rgba(239, 68, 68, 0.5); }
        50%      { box-shadow: 0 2px 14px rgba(239, 68, 68, 0.85); }
    }

    .menu-item.active .badge-notif {
        background: #ef4444 !important;
        color: #ffffff !important;
        box-shadow: none;
        animation: none;
    }

    .logout-item .menu-btn:hover {
        background: rgba(239, 68, 68, 0.16) !important;
        color: #fecaca !important;
    }

    .logout-item .menu-btn:hover::before { background: #f87171; }

    .logout-item .menu-btn:hover .menu-icon {
        color: #f87171 !important;
        transform: translateX(2px) scale(1.1);
    }

    .sb-tooltip {
        position: fixed;
        z-index: 4000;
        pointer-events: none;
        background: #102a4c;
        color: #ffffff;
        padding: 0.45rem 0.72rem;
        border-radius: 8px;
        font-size: 0.74rem;
        font-weight: 600;
        white-space: nowrap;
        box-shadow: 0 10px 24px rgba(4, 16, 35, 0.35);
        opacity: 0;
        transform: translateX(-6px);
        transition: opacity 0.16s ease, transform 0.16s ease;
    }

    .sb-tooltip::before {
        content: '';
        position: absolute;
        left: -4px;
        top: 50%;
        width: 8px;
        height: 8px;
        background: inherit;
        transform: translateY(-50%) rotate(45deg);
        border-radius: 1px;
    }

    .sb-tooltip.show { opacity: 1; transform: translateX(0); }

    @media (min-width: 993px) {

        .admin-sidebar.collapsed { width: var(--sidebar-collapsed-width); }

        .admin-sidebar.collapsed .sidebar-header { justify-content: center; padding: 1rem 0.5rem; }
        .admin-sidebar.collapsed .sidebar-logo { gap: 0; justify-content: center; width: 100%; }
        .admin-sidebar.collapsed .logo-text { opacity: 0; max-width: 0; }

        .admin-sidebar.collapsed .sidebar-nav,
        .admin-sidebar.collapsed .sidebar-footer {
            padding-left: 0.6rem;
            padding-right: 0.6rem;
        }

        .admin-sidebar.collapsed .sidebar-toggle-btn {
            justify-content: center;
            gap: 0;
            padding: 0.6rem 0;
        }

        .admin-sidebar.collapsed .sidebar-toggle-btn span { opacity: 0; max-width: 0; }
        .admin-sidebar.collapsed .sidebar-toggle-btn:hover i { transform: translateX(3px); }

        .admin-sidebar.collapsed .nav-section {
            padding: 0.7rem 0 0.3rem;
            text-align: center;
        }

        .admin-sidebar.collapsed .nav-section span { opacity: 0; }

        .admin-sidebar.collapsed .nav-section::after {
            content: '';
            position: absolute;
            left: 50%;
            bottom: 6px;
            width: 22px;
            height: 1px;
            background: var(--sb-border);
            transform: translateX(-50%);
        }

        .admin-sidebar.collapsed .menu-btn { justify-content: center; gap: 0; padding: 0.68rem 0; }
        .admin-sidebar.collapsed .menu-btn > span { opacity: 0; max-width: 0; }

        .admin-sidebar.collapsed .menu-item:not(.active) .menu-btn:hover {
            transform: translateX(0) scale(1.06);
        }

        .admin-sidebar.collapsed .badge-notif {
            position: absolute;
            top: 3px;
            right: 8px;
            margin: 0;
            min-width: 17px;
            height: 17px;
            padding: 0 3px;
            font-size: 0.55rem;
        }
    }

    .sidebar-backdrop { display: none; }

    .mobile-menu-btn {
        display: none;
        width: 38px;
        height: 38px;
        align-items: center;
        justify-content: center;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        background: #ffffff;
        color: #1f2937;
        font-size: 1.15rem;
        cursor: pointer;
        transition: background 0.2s ease, color 0.2s ease, transform 0.15s ease;
    }

    .mobile-menu-btn:hover { background: var(--active-blue-light); color: var(--active-blue); }
    .mobile-menu-btn:active { transform: scale(0.94); }

    @media (max-width: 992px) {

        .admin-sidebar {
            width: min(86vw, var(--sidebar-width));
            transform: translateX(-102%);
            box-shadow: none;
        }

        body.sidebar-open .admin-sidebar {
            transform: translateX(0);
            box-shadow: 18px 0 40px rgba(8, 20, 40, 0.28);
        }

        .admin-sidebar.collapsed { width: min(86vw, var(--sidebar-width)); }

        .sidebar-toggle-item { display: none !important; }
        .sidebar-close { display: flex; }
        .mobile-menu-btn { display: inline-flex; }

        .sidebar-backdrop {
            display: block;
            position: fixed;
            inset: 0;
            z-index: 1030;
            background: rgba(8, 20, 40, 0.5);
            backdrop-filter: blur(2px);
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0s linear 0.3s;
        }

        body.sidebar-open .sidebar-backdrop {
            opacity: 1;
            visibility: visible;
            transition: opacity 0.3s ease, visibility 0s;
        }

        body.sidebar-open { overflow: hidden; }

        .admin-main,
        .admin-main.sidebar-collapsed {
            margin-left: 0 !important;
            width: 100% !important;
        }

        .admin-header { padding: 0.75rem 1rem; }
        .admin-content { padding: 1rem; }
        .header-info-group .divider-icon { display: none; }
    }

    @media (prefers-reduced-motion: reduce) {
        .menu-item,
        .menu-item.active .menu-btn::after,
        .badge-notif,
        .menu-item.is-navigating .menu-btn,
        .admin-content.is-entering { animation: none !important; }

        .menu-btn:hover,
        .menu-item:not(.active) .menu-btn:hover { transform: none !important; }
    }

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

    .header-left { display: flex; align-items: center; gap: 1rem; min-width: 0; }
    .header-title-group { display: flex; align-items: center; gap: 0.5rem; min-width: 0; }
    .header-title-icon { width: 24px; height: 24px; object-fit: contain; flex-shrink: 0; }

    .page-title-header {
        font-size: 1rem;
        font-weight: 600;
        color: #111827 !important;
        margin: 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .header-right { display: flex; align-items: center; gap: 1rem; flex-shrink: 0; }
    .header-info-group { display: flex; align-items: center; gap: 0.5rem; }

    .header-datetime {
        display: flex;
        align-items: center;
        gap: 0.3rem;
        color: #6b7280 !important;
        font-size: 0.78rem;
        font-weight: 500;
        white-space: nowrap;
    }

    .header-datetime i { color: var(--active-blue) !important; }
    .divider-icon { color: #d1d5db !important; font-size: 0.8rem; }

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
        top: 2px; right: 2px;
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

    .notif-dropdown-body { max-height: 320px; overflow-y: auto; }

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

    .notif-item:hover { background: #f8fafc !important; }
    .notif-item.unread { background: #f8fafc !important; }

    .notif-item.unread::before {
        content: '';
        position: absolute;
        left: 6px;
        top: 50%;
        transform: translateY(-50%);
        width: 6px; height: 6px;
        background: var(--active-blue) !important;
        border-radius: 50%;
    }

    .notif-icon-box {
        width: 34px; height: 34px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .notif-icon-box.appointment { background: #e3f2fd !important; color: #1976d2 !important; }
    .notif-icon-box.xray        { background: #e3f2fd !important; color: #1976d2 !important; }
    .notif-icon-box.system      { background: #fef3c7 !important; color: #d97706 !important; }
    .notif-icon-box.lab         { background: #e8f5e9 !important; color: #28a745 !important; }
    .notif-icon-box.billing     { background: #fff3e0 !important; color: #ff6b00 !important; }
    .notif-icon-box.payment     { background: #ccfbf1 !important; color: #0d9488 !important; }

    .notif-content { flex: 1; min-width: 0; }

    .notif-title {
        font-size: 0.82rem;
        font-weight: 600;
        color: #111827 !important;
        margin-bottom: 2px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .notif-msg { font-size: 0.75rem; color: #6b7280 !important; margin-bottom: 4px; }
    .notif-time { font-size: 0.68rem; color: #9ca3af !important; }

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
        width: 32px; height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .user-details { display: flex; flex-direction: column; line-height: 1.15; }

    .user-name-header { font-size: 0.78rem; font-weight: 600; color: #111827 !important; }
    .user-role-header { font-size: 0.58rem; color: #6b7280 !important; }

    .admin-content {
        flex: 1;
        padding: 1.5rem 2rem 2rem;
        width: 100%;
        overflow-x: hidden;
    }

    @media (max-width: 768px) {
        .admin-header { padding: 0.5rem 0.75rem; min-height: 52px; }
        .header-title-group .header-title-icon { width: 20px; height: 20px; }
        .page-title-header { font-size: 0.85rem; max-width: 180px; }
        .header-datetime { display: none; }
        .user-details { display: none; }
        .avatar-small { width: 30px; height: 30px; }
        .admin-content { padding: 0.75rem; }
        .notif-dropdown-menu { width: 300px; }
    }

    @media (max-width: 576px) {
        .admin-header { padding: 0.4rem 0.6rem; min-height: 48px; }
        .header-title-group { gap: 0.35rem; }
        .header-title-group .header-title-icon { width: 18px; height: 18px; }
        .page-title-header { font-size: 0.72rem; max-width: 130px; }
        .notif-btn { font-size: 1rem; padding: 0.25rem 0.4rem; }
        .avatar-small { width: 28px; height: 28px; }
        .notif-dropdown-menu { width: 270px; }
        .admin-content { padding: 0.6rem; }
    }

    @media (max-width: 400px) {
        .page-title-header { font-size: 0.67rem; max-width: 105px; }
        .header-title-group .header-title-icon { width: 17px; height: 17px; }
        .header-user { display: none; }
        .notif-dropdown-menu { width: 250px; }
    }
    </style>

    <style>
        .admin-sidebar.no-transition,
        .admin-sidebar.no-transition *,
        .admin-main.no-transition {
            transition: none !important;
        }

        .admin-sidebar.no-transition .menu-item {
            animation: none !important;
        }
    </style>

    <style>
        html.pm-gate .admin-content { visibility: hidden; }
        html.pm-ready .admin-content { visibility: visible; }
    </style>
    <noscript>
        <style>html.pm-gate .admin-content { visibility: visible !important; }</style>
    </noscript>
    <script>
    (function () {
        var root = document.documentElement;
        root.className += ' pm-gate';

        function reveal() {
            root.className = root.className.replace(/\bpm-gate\b/, '') + ' pm-ready';
        }

        if (document.readyState !== 'loading') {
            reveal();
        } else {
            document.addEventListener('DOMContentLoaded', reveal);
        }

        setTimeout(reveal, 1200);
    }());
    </script>

</head>

<body>

<script>
    if (window.AOS && typeof AOS.init === 'function') {
        AOS.init({ duration: 800, once: true });
    }
</script>

<div class="admin-wrapper">

    <?php
    $currentUrl = current_url();

    $isActive = static function (string $segment) use ($currentUrl): bool {
        return strpos(rtrim($currentUrl, '/'), $segment) !== false;
    };

    $pendingLab = 0;
    $unreadCount = 0;

    try {
        // paid requests no med tech has started yet
        $pendingLab = (new \App\Models\DiagnosticRequestModel())->getDepartmentCounts('lab')['pending'];
    } catch (\Throwable $e) {
        $pendingLab = 0;
    }
    if (class_exists('\App\Models\NotificationModel')) {
        try {
            $unreadCount = (int) (new \App\Models\NotificationModel())->getUnreadCount();
        } catch (\Throwable $e) {
            $unreadCount = 0;
        }
    }

    $navGroups = [
        'Laboratory' => [
            ['seg' => 'medtech/dashboard',     'icon' => 'bi-grid-1x2-fill',    'label' => 'Dashboard'],
            ['seg' => 'medtech/requests',      'icon' => 'bi-droplet-half',     'label' => 'Lab Requests', 'badge' => $pendingLab],
        ],
        'Reports' => [
            ['seg' => 'medtech/reports',       'icon' => 'bi-file-earmark-text','label' => 'Reports'],
        ],
        'Account' => [
            ['seg' => 'medtech/notifications', 'icon' => 'bi-bell-fill',        'label' => 'Notifications', 'badge' => $unreadCount],
        ],
    ];

    $navIndex = 0;
    ?>

    <aside class="admin-sidebar" id="adminSidebar" aria-label="Main navigation">

        <div class="sidebar-header">
            <a class="sidebar-logo" href="<?= base_url('medtech/dashboard') ?>">
                <span class="logo-badge">
                    <img src="<?= base_url() ?>assets/images/logo4.png"
                         alt=""
                         width="30"
                         height="30"
                         decoding="async"
                         fetchpriority="high">
                </span>
                <span class="logo-text">
                    PolyMedic
                    <small>Laboratory System</small>
                </span>
            </a>

            <button type="button" class="sidebar-close" id="sidebarCloseBtn" aria-label="Close menu">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <nav class="sidebar-nav">
            <ul>

                <li class="sidebar-toggle-item">
                    <button type="button"
                            class="sidebar-toggle-btn"
                            id="sidebarCollapseBtn"
                            data-tooltip="Expand Sidebar"
                            aria-controls="adminSidebar"
                            aria-expanded="true"
                            aria-label="Collapse sidebar">
                        <i class="bi bi-chevron-double-left" aria-hidden="true"></i>
                        <span>Collapse Sidebar</span>
                    </button>
                </li>

                <?php foreach ($navGroups as $groupLabel => $items): ?>

                    <li class="nav-section"><span><?= esc($groupLabel) ?></span></li>

                    <?php foreach ($items as $item): $active = $isActive($item['seg']); ?>
                        <li class="menu-item<?= $active ? ' active' : '' ?>" style="--i: <?= $navIndex++ ?>">
                            <a href="<?= base_url($item['seg']) ?>"
                               class="menu-btn"
                               data-tooltip="<?= esc($item['label']) ?>"
                               <?= $active ? 'aria-current="page"' : '' ?>>
                                <i class="bi <?= $item['icon'] ?> menu-icon" aria-hidden="true"></i>
                                <span><?= esc($item['label']) ?></span>
                                <?php if (! empty($item['badge'])): ?>
                                    <span class="badge-notif"><?= $item['badge'] > 99 ? '99+' : $item['badge'] ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                    <?php endforeach; ?>

                <?php endforeach; ?>

            </ul>
        </nav>

        <div class="sidebar-footer">
            <ul>
                <li class="menu-item logout-item">
                    <a href="<?= base_url() ?>logout" class="menu-btn" data-tooltip="Logout">
                        <i class="bi bi-box-arrow-right menu-icon" aria-hidden="true"></i>
                        <span>Logout</span>
                    </a>
                </li>
            </ul>
        </div>

    </aside>

    <div class="sidebar-backdrop" id="sidebarBackdrop" hidden></div>

    <main class="admin-main" id="adminMain">

        <header class="admin-header">

            <div class="header-left">

                <button type="button"
                        class="mobile-menu-btn"
                        id="mobileMenuBtn"
                        aria-controls="adminSidebar"
                        aria-expanded="false"
                        aria-label="Open menu">
                    <i class="bi bi-list"></i>
                </button>

                <div class="header-title-group">
                    <?php
                    $pageTitle = $this->renderSection('pageTitle') ?: 'Dashboard';

                    $iconMap = [
                        'My Profile' => 'profile.png',
                        'Dashboard' => 'statisctics.png',
                        'Lab Requests' => 'lab-icon.png',
                        'Laboratory Requests' => 'chemistry-lab-instrument.png',
                        'View Lab Request' => 'lab-icon.png',
                        'Laboratory Findings' => 'lab-icon.png',
                        'Reports' => 'lab-icon.png',
                        'MedTech Dashboard' => 'statisctics.png',
                        'Notifications' => 'notification.png',
                    ];

                    $iconFile = $iconMap[$pageTitle] ?? 'lab-icon.png';
                    ?>

                    <img src="<?= base_url() ?>assets/images/<?= $iconFile ?>"
                         alt=""
                         class="header-title-icon"
                         width="24"
                         height="24"
                         decoding="async"
                         fetchpriority="high">

                    <h4 class="page-title-header"><?= esc($pageTitle) ?></h4>
                </div>

            </div>

            <div class="header-right">
                <div class="header-info-group">

                    <div class="header-datetime">
                        <i class="bi bi-clock"></i>
                        <span><?= date('D, M j · h:i:s A') ?></span>
                    </div>

                    <span class="divider-icon">|</span>

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

                            <div class="notif-dropdown-body" id="notifDropdownList">
                                <div class="p-3 text-center text-muted small">
                                    <div class="spinner-border spinner-border-sm text-primary me-1" role="status"></div>
                                    Loading notifications...
                                </div>
                            </div>

                            <div class="notif-dropdown-footer text-center">
                                <a href="<?= base_url() ?>medtech/notifications"
                                   class="text-primary fw-semibold small text-decoration-none">
                                    View All Notifications
                                    <i class="bi bi-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <span class="divider-icon">|</span>

                    <?= view('partials/headerUser', ['prefix' => 'medtech', 'fallbackName' => 'Med Tech', 'roleLabel' => 'Medical Technologist']) ?>
                </div>
            </div>

        </header>

        <div class="admin-content">
            <?= $this->renderSection('medtechContent') ?>
        </div>

    </main>

</div>

<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.min.js"></script>

<script>
(function() {
    'use strict';

    var STORAGE_KEY = 'polymedicSidebarCollapsed';
    var DESKTOP_MIN = 993;

    var sidebar     = document.getElementById('adminSidebar');
    var adminMain   = document.getElementById('adminMain');
    var collapseBtn = document.getElementById('sidebarCollapseBtn');
    var menuBtn     = document.getElementById('mobileMenuBtn');
    var closeBtn    = document.getElementById('sidebarCloseBtn');
    var backdrop    = document.getElementById('sidebarBackdrop');

    function isDesktop() { return window.innerWidth >= DESKTOP_MIN; }

    function readCollapsed() {
        try { return localStorage.getItem(STORAGE_KEY) === '1'; }
        catch (e) { return false; }
    }

    function writeCollapsed(value) {
        try { localStorage.setItem(STORAGE_KEY, value ? '1' : '0'); }
        catch (e) { /* ignore */ }
    }

    function setCollapsed(collapsed, skipTransition) {
        if (!sidebar || !adminMain) return;

        if (skipTransition) {
            sidebar.classList.add('no-transition');
            adminMain.classList.add('no-transition');
        }

        sidebar.classList.toggle('collapsed', collapsed);
        adminMain.classList.toggle('sidebar-collapsed', collapsed);
        updateCollapseButton(collapsed);

        if (skipTransition) {
            requestAnimationFrame(function() {
                requestAnimationFrame(function() {
                    sidebar.classList.remove('no-transition');
                    adminMain.classList.remove('no-transition');
                });
            });
        }
    }

    function updateCollapseButton(collapsed) {
        if (!collapseBtn) return;

        var icon = collapseBtn.querySelector('i');
        var text = collapseBtn.querySelector('span');
        var label = collapsed ? 'Expand Sidebar' : 'Collapse Sidebar';

        if (icon) {
            icon.className = collapsed
                ? 'bi bi-chevron-double-right'
                : 'bi bi-chevron-double-left';
        }
        if (text) text.textContent = label;

        collapseBtn.setAttribute('aria-label', label);
        collapseBtn.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
        collapseBtn.setAttribute('data-tooltip', label);
        collapseBtn.removeAttribute('title');
    }

    function openDrawer() {
        document.body.classList.add('sidebar-open');
        if (menuBtn) menuBtn.setAttribute('aria-expanded', 'true');
        if (backdrop) backdrop.removeAttribute('hidden');
        if (closeBtn) closeBtn.focus();
    }

    function closeDrawer() {
        document.body.classList.remove('sidebar-open');
        if (menuBtn) menuBtn.setAttribute('aria-expanded', 'false');
    }

    if (menuBtn)  menuBtn.addEventListener('click', openDrawer);
    if (closeBtn) closeBtn.addEventListener('click', closeDrawer);
    if (backdrop) backdrop.addEventListener('click', closeDrawer);

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeDrawer();
    });

    if (sidebar) {
        sidebar.addEventListener('click', function(e) {
            if (!isDesktop() && e.target.closest('.menu-btn')) closeDrawer();
        });
    }

    window.toggleSidebarCollapse = function() {
        if (!isDesktop()) return;
        var next = !sidebar.classList.contains('collapsed');
        setCollapsed(next, false);
        writeCollapsed(next);
        hideTooltip();
    };

    if (collapseBtn) {
        collapseBtn.addEventListener('click', window.toggleSidebarCollapse);
    }

    function applySidebarState() {
        if (!sidebar || !adminMain) return;
        setCollapsed(isDesktop() && readCollapsed(), true);
        if (!isDesktop()) closeDrawer();
    }

    applySidebarState();

    window.addEventListener('pageshow', function(e) {
        if (e.persisted) applySidebarState();
    });

    var resizeTimer = null;
    var wasDesktop = isDesktop();

    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            var nowDesktop = isDesktop();
            if (nowDesktop !== wasDesktop) {
                wasDesktop = nowDesktop;
                applySidebarState();
            }
            if (nowDesktop) closeDrawer();
            hideTooltip();
        }, 120);
    });

    var tooltipEl = null;

    function getTooltipEl() {
        if (!tooltipEl) {
            tooltipEl = document.createElement('div');
            tooltipEl.className = 'sb-tooltip';
            tooltipEl.setAttribute('role', 'tooltip');
            document.body.appendChild(tooltipEl);
        }
        return tooltipEl;
    }

    function showTooltip(target) {
        if (!isDesktop() || !sidebar.classList.contains('collapsed')) return;
        var label = target.getAttribute('data-tooltip');
        if (!label) return;

        var tip = getTooltipEl();
        tip.textContent = label;
        tip.style.visibility = 'hidden';
        tip.classList.add('show');

        var rect = target.getBoundingClientRect();
        var top = rect.top + (rect.height / 2) - (tip.offsetHeight / 2);
        top = Math.max(8, Math.min(top, window.innerHeight - tip.offsetHeight - 8));

        tip.style.left = (rect.right + 12) + 'px';
        tip.style.top = top + 'px';
        tip.style.visibility = 'visible';
    }

    function hideTooltip() {
        if (tooltipEl) tooltipEl.classList.remove('show');
    }

    if (sidebar) {
        sidebar.querySelectorAll('[data-tooltip]').forEach(function(el) {
            el.addEventListener('mouseenter', function() { showTooltip(el); });
            el.addEventListener('focus', function() { showTooltip(el); });
            el.addEventListener('mouseleave', hideTooltip);
            el.addEventListener('blur', hideTooltip);
        });

        sidebar.addEventListener('scroll', hideTooltip, { passive: true });
    }

    function fetchNotifications() {
        fetch('<?= base_url() ?>medtech/notifications/fetch', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.status === 'success') {
                updateNotificationBadge(data.unread_count);
                renderNotificationDropdown(data.notifications);
            }
        })
        .catch(function(err) { console.error('Error fetching notifications:', err); });
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
            listContainer.innerHTML =
                '<div class="p-3 text-center text-muted small">' +
                '<i class="bi bi-bell-slash d-block fs-4 mb-1"></i>' +
                'No notifications yet</div>';
            return;
        }

        var html = '';
        notifications.forEach(function(item) {
            var unreadClass = item.is_read == 0 ? 'unread' : '';
            var iconClass = 'bi-bell-fill';
            var colorClass = 'system';

            if (item.type === 'appointment') { iconClass = 'bi-calendar-check'; colorClass = 'appointment'; }
            else if (item.type === 'xray')   { iconClass = 'bi-x-ray';          colorClass = 'xray'; }
            else if (item.type === 'lab')    { iconClass = 'bi-flask';          colorClass = 'lab'; }
            else if (item.type === 'billing'){ iconClass = 'bi-receipt';        colorClass = 'billing'; }
            else if (item.type === 'payment'){ iconClass = 'bi-credit-card';    colorClass = 'payment'; }

            var link = item.link || '#';

            html +=
                '<a href="' + link + '" class="notif-item ' + unreadClass + '" onclick="markNotificationRead(' + item.id + ', event)">' +
                    '<div class="notif-icon-box ' + colorClass + '"><i class="bi ' + iconClass + '"></i></div>' +
                    '<div class="notif-content">' +
                        '<div class="notif-title">' + escapeHtml(item.title) + '</div>' +
                        '<div class="notif-msg">' + escapeHtml(item.message) + '</div>' +
                        '<div class="notif-time"><i class="bi bi-clock me-1"></i>' + item.time_ago + '</div>' +
                    '</div>' +
                '</a>';
        });

        listContainer.innerHTML = html;
    }

    window.markNotificationRead = function(id, event) {
        fetch('<?= base_url() ?>medtech/notifications/mark-read/' + id, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.status === 'success') updateNotificationBadge(data.unread_count);
        })
        .catch(function(err) { console.error('Error marking notification read:', err); });
    };

    window.markAllNotificationsRead = function(event) {
        if (event) event.stopPropagation();

        fetch('<?= base_url() ?>medtech/notifications/mark-all-read', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.status === 'success') {
                updateNotificationBadge(0);
                fetchNotifications();
            }
        })
        .catch(function(err) { console.error('Error marking notifications read:', err); });
    };

    function escapeHtml(text) {
        if (!text) return '';
        return String(text)
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


    // =========================================================
    // SPA-STYLE NAVIGATION
    // =========================================================

    var contentEl = document.querySelector('.admin-content');
    var cache = Object.create(null);
    var inFlight = false;

    function setActiveItem(url) {
        document.querySelectorAll('.admin-sidebar .menu-item').forEach(function(li) {
            var link = li.querySelector('a.menu-btn');
            var active = link && link.href === url;
            li.classList.toggle('active', active);
            if (link) {
                if (active) link.setAttribute('aria-current', 'page');
                else link.removeAttribute('aria-current');
            }
        });
    }

    function playClickPulse(el) {
        if (!el) return;
        el.classList.remove('is-navigating');
        void el.offsetWidth;
        el.classList.add('is-navigating');
        setTimeout(function() { el.classList.remove('is-navigating'); }, 480);
    }

    function extractContent(html) {
        var doc = new DOMParser().parseFromString(html, 'text/html');
        var content = doc.querySelector('.admin-content');
        if (!content) return null;

        var newTitle = doc.querySelector('.page-title-header');
        var newTitleText = newTitle ? newTitle.textContent : null;

        var newTitleIcon = doc.querySelector('.header-title-icon');
        var newIconSrc = newTitleIcon ? newTitleIcon.getAttribute('src') : null;

        return { html: content.innerHTML, title: newTitleText, iconSrc: newIconSrc };
    }

    // Re-execute the <script> tags of freshly swapped content, in order.
    // - Inline code is wrapped in a block so a page's top-level
    //   const/let can be declared again on the next visit (re-running it
    //   verbatim throws "already declared" and kills the page's charts).
    // - DOMContentLoaded / load already fired, so handlers registered for
    //   them during the re-run are called straight away.
    // - External scripts load one at a time before the code that needs
    //   them; ones already on the page (e.g. Chart.js) are not reloaded.
    // - Data blocks (type="application/json", ...) are left alone.
    var loadedSrc = Object.create(null);
    document.querySelectorAll('script[src]').forEach(function(s) { loadedSrc[s.src] = true; });

    function isJsScript(script) {
        var type = (script.getAttribute('type') || '').trim().toLowerCase();
        return type === '' || type === 'text/javascript' || type === 'application/javascript';
    }

    function runInlineScript(oldScript) {
        var docAdd = document.addEventListener;
        var winAdd = window.addEventListener;
        var immediate = function(add) {
            return function(type, fn, opts) {
                if (type === 'DOMContentLoaded' || type === 'load') {
                    setTimeout(function() {
                        if (typeof fn === 'function') { fn.call(document, new Event(type)); }
                        else if (fn && typeof fn.handleEvent === 'function') { fn.handleEvent(new Event(type)); }
                    }, 0);
                    return;
                }
                return add.call(this, type, fn, opts);
            };
        };

        document.addEventListener = immediate(docAdd);
        window.addEventListener   = immediate(winAdd);
        try {
            var s = document.createElement('script');
            Array.prototype.slice.call(oldScript.attributes).forEach(function(attr) {
                s.setAttribute(attr.name, attr.value);
            });
            s.textContent = '{\n' + oldScript.textContent + '\n}';
            oldScript.parentNode.replaceChild(s, oldScript);
        } finally {
            document.addEventListener = docAdd;
            window.addEventListener   = winAdd;
        }
    }

    function runContentScripts(container) {
        var queue = Array.prototype.slice.call(container.querySelectorAll('script')).filter(isJsScript);

        (function next() {
            var oldScript = queue.shift();
            if (!oldScript) return;

            if (!oldScript.src) {
                runInlineScript(oldScript);
                next();
                return;
            }

            if (loadedSrc[oldScript.src]) { next(); return; }

            var s = document.createElement('script');
            Array.prototype.slice.call(oldScript.attributes).forEach(function(attr) {
                s.setAttribute(attr.name, attr.value);
            });
            s.async = false;
            s.onload = s.onerror = function() { loadedSrc[s.src] = true; next(); };
            oldScript.parentNode.replaceChild(s, oldScript);
        })();
    }

    function swapContent(payload) {
        if (!contentEl) return;
        contentEl.classList.add('is-leaving');

        setTimeout(function() {
            contentEl.innerHTML = payload.html;
            runContentScripts(contentEl);

            if (payload.title) {
                var titleEl = document.querySelector('.page-title-header');
                if (titleEl) titleEl.textContent = payload.title;
            }
            if (payload.iconSrc) {
                var iconEl = document.querySelector('.header-title-icon');
                if (iconEl) iconEl.setAttribute('src', payload.iconSrc);
            }

            contentEl.classList.remove('is-leaving');
            contentEl.classList.add('is-entering');
            setTimeout(function() { contentEl.classList.remove('is-entering'); }, 320);

            window.scrollTo(0, 0);
        }, 120);
    }

    function navigateTo(url, push, clickedEl) {
        if (inFlight) return;
        if (url === window.location.href) { setActiveItem(url); return; }

        playClickPulse(clickedEl);
        setActiveItem(url);

        if (contentEl) contentEl.classList.add('is-loading');

        if (cache[url]) {
            swapContent(cache[url]);
            if (contentEl) contentEl.classList.remove('is-loading');
            if (push) history.pushState({ url: url }, '', url);
            return;
        }

        inFlight = true;

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
        .then(function(res) { return res.text(); })
        .then(function(html) {
            var payload = extractContent(html);
            if (!payload) { window.location.href = url; return; }

            cache[url] = payload;
            swapContent(payload);
            if (push) history.pushState({ url: url }, '', url);
        })
        .catch(function() { window.location.href = url; })
        .then(function() {
            inFlight = false;
            if (contentEl) contentEl.classList.remove('is-loading');
        });
    }

    document.addEventListener('click', function(e) {
        var link = e.target.closest('.admin-sidebar a.menu-btn');
        if (!link) return;
        if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
        if (link.target && link.target !== '_self') return;
        if (link.hasAttribute('download')) return;
        if (link.getAttribute('href') === '<?= base_url() ?>logout') return;

        var url = link.href;
        if (!url || url.indexOf('#') === 0) return;
        if (url.indexOf(window.location.origin) !== 0) return;

        e.preventDefault();
        navigateTo(url, true, link.closest('.menu-item'));
    });

    window.addEventListener('popstate', function(e) {
        var url = (e.state && e.state.url) || window.location.href;
        navigateTo(url, false, null);
    });

})();
</script>

<?= view('partials/postLinks') ?>
</body>
</html>