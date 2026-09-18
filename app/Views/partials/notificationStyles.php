<style>
/* =========================================================
   NOTIFICATIONS
   Scoped under .nc so the layout's global card, button, and
   table rules cannot leak in. Shared by Admin, Receptionist,
   Radiologist, and MedTech.
   ========================================================= */

.nc {
    --nc-ink:         #0f172a;
    --nc-text:        #334155;
    --nc-muted:       #64748b;
    --nc-faint:       #94a3b8;
    --nc-line:        #e5e7eb;
    --nc-line-soft:   #f1f5f9;
    --nc-surface:     #ffffff;
    --nc-canvas:      #f8fafc;
    --nc-rail:        #f9fafb;

    --nc-accent:      #0d9488;
    --nc-accent-dark: #0f766e;
    --nc-accent-soft: #e6fbf6;

    --nc-danger:      #dc2626;
    --nc-danger-soft: #fef2f2;
    --nc-male-soft:   #eaf2fe;
    --nc-male-ink:    #1d4ed8;
    --nc-female-soft: #fce9ee;
    --nc-female-ink:  #b32e50;

    --nc-radius-lg:   14px;
    --nc-radius-md:   10px;
    --nc-radius-sm:   7px;

    color: var(--nc-text);
    -webkit-font-smoothing: antialiased;
}

.nc *,
.nc *::before,
.nc *::after { box-sizing: border-box; }

.nc *:focus-visible {
    outline: 2px solid var(--nc-accent);
    outline-offset: 2px;
    border-radius: 4px;
}

/* ---------- Page header ---------- */

.nc-head {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
    margin-bottom: 1.25rem;
}

.nc-head-text { min-width: 0; }

.nc-title {
    margin: 0 0 0.2rem;
    font-size: 1.25rem;
    font-weight: 700;
    letter-spacing: -0.015em;
    color: var(--nc-ink);
}

.nc-lede {
    margin: 0;
    font-size: 0.8125rem;
    color: var(--nc-muted);
}

.nc-lede strong { color: var(--nc-ink); font-weight: 600; }

.nc-head-actions { display: flex; gap: 0.5rem; }

/* ---------- Alerts ---------- */

.nc-alert {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    padding: 0.75rem 1rem;
    margin-bottom: 1rem;
    border: 1px solid transparent;
    border-radius: var(--nc-radius-md);
    font-size: 0.8125rem;
}

.nc-alert > span { flex: 1; min-width: 0; }
.nc-alert.alert-dismissible { padding-right: 0.75rem; }
.nc-alert .btn-close { position: static; padding: 0.5rem; margin-left: auto; font-size: 0.7rem; }
.nc-alert--success { background: #f0fdf4; border-color: #bbf7d0; color: #166534; }
.nc-alert--error   { background: #fef2f2; border-color: #fecaca; color: #991b1b; }

/* ---------- Buttons ---------- */

.nc-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.45rem;
    height: 36px;
    padding: 0 0.9rem;
    font-size: 0.8125rem;
    font-weight: 600;
    line-height: 1;
    color: var(--nc-ink);
    background: var(--nc-surface);
    border: 1px solid var(--nc-line);
    border-radius: var(--nc-radius-sm);
    box-shadow: 0 1px 1px rgba(15, 23, 42, 0.03);
    white-space: nowrap;
    text-decoration: none;
    cursor: pointer;
    transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
}

.nc-btn:hover {
    background: var(--nc-rail);
    border-color: #cbd5e1;
    color: var(--nc-ink);
}

.nc-btn i { font-size: 0.9em; }

.nc-btn--sm {
    height: 30px;
    padding: 0 0.7rem;
    font-size: 0.775rem;
    font-weight: 600;
    color: var(--nc-accent-dark);
    background: var(--nc-accent-soft);
    border-color: transparent;
    box-shadow: none;
}

.nc-btn--sm:hover {
    background: var(--nc-accent);
    border-color: var(--nc-accent);
    color: #ffffff;
}

.nc-link {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    height: 30px;
    padding: 0 0.55rem;
    font-size: 0.775rem;
    font-weight: 500;
    color: var(--nc-muted);
    text-decoration: none;
    border-radius: var(--nc-radius-sm);
    transition: background-color 0.15s ease, color 0.15s ease;
}

.nc-link:hover { background: var(--nc-line-soft); color: var(--nc-ink); }

.nc-link--danger { color: var(--nc-danger); margin-left: auto; }
.nc-link--danger:hover { background: var(--nc-danger-soft); color: #b91c1c; }

/* ---------- Panel ---------- */

.nc-panel {
    background: var(--nc-surface);
    border: 1px solid var(--nc-line);
    border-radius: var(--nc-radius-lg);
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    overflow: hidden;
}

/* ---------- Toolbar: tabs + search ---------- */

.nc-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding-right: 0.85rem;
    border-bottom: 1px solid var(--nc-line);
    background: var(--nc-surface);
}

/* ---------- Tabs ---------- */

.nc-tabs {
    display: flex;
    gap: 0.15rem;
    padding: 0 0.85rem;
    overflow-x: auto;
    scrollbar-width: none;
}

.nc-tabs::-webkit-scrollbar { display: none; }

.nc-tab {
    position: relative;
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    padding: 0.9rem 0.7rem;
    font-size: 0.8125rem;
    font-weight: 600;
    color: var(--nc-muted);
    background: none;
    border: 0;
    white-space: nowrap;
    cursor: pointer;
    transition: color 0.15s ease;
}

.nc-tab::after {
    content: "";
    position: absolute;
    left: 0.5rem;
    right: 0.5rem;
    bottom: -1px;
    height: 2px;
    border-radius: 2px 2px 0 0;
    background: transparent;
    transform: scaleX(0.4);
    opacity: 0;
    transition: transform 0.18s ease, opacity 0.18s ease, background-color 0.15s ease;
}

.nc-tab:hover { color: var(--nc-ink); }
.nc-tab.is-active { color: var(--nc-ink); }

.nc-tab.is-active::after {
    background: var(--nc-accent);
    transform: scaleX(1);
    opacity: 1;
}

.nc-tab-count {
    font-size: 0.72rem;
    font-weight: 500;
    color: var(--nc-faint);
    font-variant-numeric: tabular-nums;
    transition: color 0.15s ease;
}

.nc-tab.is-active .nc-tab-count { color: var(--nc-accent-dark); font-weight: 600; }

/* ---------- Search ---------- */

.nc-search {
    position: relative;
    display: flex;
    align-items: center;
    flex-shrink: 0;
    width: 15rem;
    max-width: 40vw;
}

.nc-search > .bi-search {
    position: absolute;
    left: 0.6rem;
    font-size: 0.8rem;
    color: var(--nc-faint);
    pointer-events: none;
}

.nc-search-input {
    width: 100%;
    height: 32px;
    padding: 0 1.9rem 0 1.9rem;
    font-size: 0.8rem;
    color: var(--nc-ink);
    background: var(--nc-canvas);
    border: 1px solid var(--nc-line);
    border-radius: 999px;
    outline: none;
    transition: border-color 0.15s ease, background-color 0.15s ease;
}

.nc-search-input::placeholder { color: var(--nc-faint); }

.nc-search-input:focus {
    background: var(--nc-surface);
    border-color: var(--nc-accent);
}

.nc-search-input::-webkit-search-cancel-button { display: none; }

.nc-search-clear {
    position: absolute;
    right: 0.35rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 22px;
    height: 22px;
    font-size: 0.65rem;
    color: var(--nc-muted);
    background: none;
    border: 0;
    border-radius: 50%;
    cursor: pointer;
}

.nc-search-clear:hover { background: var(--nc-line-soft); color: var(--nc-ink); }

/* ---------- Type chips ---------- */

.nc-chips {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.7rem 0.85rem;
    background: var(--nc-surface);
    border-bottom: 1px solid var(--nc-line);
    overflow-x: auto;
    scrollbar-width: none;
}

.nc-chips::-webkit-scrollbar { display: none; }

.nc-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    height: 28px;
    padding: 0 0.7rem;
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--nc-muted);
    background: var(--nc-canvas);
    border: 1px solid var(--nc-line);
    border-radius: 999px;
    white-space: nowrap;
    cursor: pointer;
    transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
}

.nc-chip:hover { color: var(--nc-ink); border-color: #cbd5e1; }

.nc-chip.is-active {
    color: var(--nc-accent-dark);
    background: var(--nc-accent-soft);
    border-color: rgba(13, 148, 136, 0.28);
}

.nc-chip-count {
    font-size: 0.68rem;
    font-weight: 500;
    color: var(--nc-faint);
    font-variant-numeric: tabular-nums;
}

.nc-chip.is-active .nc-chip-count { color: var(--nc-accent-dark); }

.nc-chip-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    flex-shrink: 0;
}

.nc-chip-dot--appointment { background: #0d9488; }
.nc-chip-dot--xray        { background: #6d28d9; }
.nc-chip-dot--lab         { background: #15803d; }
.nc-chip-dot--billing,
.nc-chip-dot--payment     { background: #be123c; }
.nc-chip-dot--system      { background: #b45309; }

/* ---------- List ---------- */

.nc-list {
    padding: 0.35rem 1rem 1rem;
    background: var(--nc-canvas);
}

/* ---------- Date groups ---------- */

.nc-group.is-hidden { display: none; }

.nc-group-head {
    position: sticky;
    top: 0;
    z-index: 2;
    display: flex;
    align-items: center;
    margin: 0;
    padding: 0.75rem 0 0.5rem;
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: var(--nc-faint);
    background: linear-gradient(var(--nc-canvas) 78%, rgba(248, 250, 252, 0));
}

.nc-group-body {
    display: flex;
    flex-direction: column;
    gap: 0.55rem;
}

/* ---------- Card ---------- */

.nc-card {
    position: relative;
    display: flex;
    align-items: flex-start;
    gap: 0.95rem;
    padding: 1rem 1.1rem 1rem 1rem;
    background: var(--nc-surface);
    border: 1px solid #e2e8f0;
    border-radius: var(--nc-radius-md);
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
    transition: border-color 0.15s ease, box-shadow 0.15s ease, transform 0.15s ease;
    animation: nc-rise 0.32s cubic-bezier(0.2, 0.7, 0.3, 1) both;
    animation-delay: calc(var(--nc-i, 0) * 22ms);
}

@keyframes nc-rise {
    from { opacity: 0; transform: translateY(6px); }
    to   { opacity: 1; transform: none; }
}

.nc-card:hover {
    border-color: #cbd5e1;
    box-shadow: 0 6px 16px -8px rgba(15, 23, 42, 0.18),
                0 1px 2px rgba(15, 23, 42, 0.04);
    transform: translateY(-1px);
}

.nc-card.is-hidden { display: none; }
.nc-card.is-unread { border-color: #d1e7de; }

.nc-card.is-unread::before {
    content: "";
    position: absolute;
    top: 0.75rem;
    bottom: 0.75rem;
    left: 0;
    width: 3px;
    border-radius: 0 3px 3px 0;
    background: var(--nc-accent);
}

.nc-card mark {
    padding: 0 0.1em;
    color: inherit;
    background: #fef08a;
    border-radius: 2px;
}

/* ---------- Leading visual ---------- */

.nc-lead {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
}

.nc-avatar {
    position: relative;
    width: 52px;
    height: 52px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    overflow: hidden;
    font-size: 0.9rem;
    font-weight: 700;
    letter-spacing: 0.02em;
    background: var(--nc-line-soft);
    color: var(--nc-muted);
    box-shadow: inset 0 0 0 1px rgba(15, 23, 42, 0.05);
}

.nc-avatar--male {
    background: var(--nc-male-soft);
    color: var(--nc-male-ink);
    box-shadow: inset 0 0 0 1px rgba(29, 78, 216, 0.12);
}

.nc-avatar--female {
    background: var(--nc-female-soft);
    color: var(--nc-female-ink);
    box-shadow: inset 0 0 0 1px rgba(179, 46, 80, 0.12);
}

.nc-avatar--initials {
    background: var(--nc-line-soft);
    color: var(--nc-text);
    font-size: 0.92rem;
}

.nc-avatar-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.nc-tile {
    width: 44px;
    height: 44px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: var(--nc-radius-md);
    font-size: 1.1rem;
}

.nc-tile--appointment { background: #ccfbf1; color: #0d9488; }
.nc-tile--xray        { background: #ede9fe; color: #6d28d9; }
.nc-tile--lab         { background: #dcfce7; color: #15803d; }
.nc-tile--billing,
.nc-tile--payment     { background: #ffe4e6; color: #be123c; }
.nc-tile--system      { background: #fef3c7; color: #b45309; }

/* ---------- Body ---------- */

.nc-body { flex: 1; min-width: 0; }

.nc-body-head {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: 0.75rem;
    margin-bottom: 0.3rem;
    flex-wrap: wrap;
}

.nc-card-title {
    margin: 0;
    font-size: 0.9rem;
    font-weight: 700;
    letter-spacing: -0.01em;
    color: var(--nc-ink);
    overflow-wrap: anywhere;
    min-width: 0;
}

.nc-time {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    font-size: 0.735rem;
    color: var(--nc-faint);
    white-space: nowrap;
    font-variant-numeric: tabular-nums;
    cursor: help;
}

.nc-card-message {
    margin: 0 0 0.75rem;
    font-size: 0.8125rem;
    line-height: 1.55;
    color: var(--nc-text);
    overflow-wrap: anywhere;
}

.nc-body-foot {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    flex-wrap: wrap;
}

.nc-locked {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    height: 30px;
    padding: 0 0.55rem;
    font-size: 0.775rem;
    color: var(--nc-faint);
    font-style: italic;
}

/* ---------- Empty states ---------- */

.nc-empty {
    padding: 3.5rem 1rem;
    text-align: center;
    background: var(--nc-canvas);
}

.nc-empty-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 48px;
    height: 48px;
    margin: 0 auto 0.85rem;
    font-size: 1.3rem;
    color: var(--nc-faint);
    background: var(--nc-line-soft);
    border-radius: var(--nc-radius-md);
}

.nc-empty h3 {
    margin: 0 0 0.25rem;
    font-size: 0.95rem;
    font-weight: 700;
    color: var(--nc-ink);
}

.nc-empty p {
    max-width: 26rem;
    margin: 0 auto 1rem;
    font-size: 0.8125rem;
    color: var(--nc-muted);
}

/* ---------- Responsive ---------- */

@media (max-width: 768px) {
    .nc-head { flex-direction: column; align-items: stretch; }
    .nc-head-actions { width: 100%; }
    .nc-head-actions .nc-btn { width: 100%; }

    .nc-toolbar {
        flex-direction: column;
        align-items: stretch;
        gap: 0;
        padding-right: 0;
    }

    .nc-search {
        width: auto;
        max-width: none;
        margin: 0 0.85rem 0.75rem;
    }

    .nc-list { padding: 0.25rem 0.75rem 0.75rem; }
    .nc-group-body { gap: 0.5rem; }

    .nc-card { padding: 0.9rem 0.95rem; gap: 0.75rem; }

    .nc-avatar { width: 46px; height: 46px; font-size: 0.85rem; }
    .nc-tile   { width: 40px; height: 40px; font-size: 1rem; }

    .nc-body-head {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.15rem;
    }

    .nc-link--danger { margin-left: 0; }
}

@media (max-width: 480px) {
    .nc-list { padding: 0.25rem 0.5rem 0.5rem; }
    .nc-card { padding: 0.8rem; gap: 0.65rem; }

    .nc-avatar { width: 42px; height: 42px; font-size: 0.8rem; }
    .nc-tile   { width: 38px; height: 38px; font-size: 0.95rem; border-radius: 9px; }

    .nc-card-title   { font-size: 0.86rem; }
    .nc-card-message { font-size: 0.78rem; }
    .nc-time         { font-size: 0.7rem; }

    .nc-body-foot { width: 100%; }
    .nc-btn--sm { flex: 1; justify-content: center; }
}

@media (prefers-reduced-motion: reduce) {
    .nc *, .nc *::before, .nc *::after {
        transition-duration: 0.01ms !important;
        animation-duration: 0.01ms !important;
    }
}
</style>