<style>
/* =========================================================
   NOTIFICATIONS
   Scoped under .nc so the layout's global card, button, and
   table rules cannot leak in. Shared by Admin, Receptionist,
   Radiologist, and MedTech.

   An inbox, not a card wall: one panel, hairline-separated
   rows, a blue dot for unread, and quiet row actions.
   ========================================================= */

.nc {
    --nc-ink:         var(--pm-text, #10202E);
    --nc-text:        var(--pm-text-soft, #3B4C5E);
    --nc-muted:       var(--pm-text-muted, #5D6E80);
    --nc-faint:       #7A8B9D;
    --nc-line:        var(--pm-line, #DDE4EC);
    --nc-line-soft:   #EBF0F5;
    --nc-surface:     #FFFFFF;
    --nc-canvas:      #F8FAFC;
    --nc-rail:        #F5F8FB;
    --nc-unread:      #F7FAFE;

    --nc-accent:      var(--pm-accent, #0B67B2);
    --nc-accent-dark: var(--pm-accent-hover, #08528F);
    --nc-accent-soft: var(--pm-accent-soft, #E8F1FA);

    --nc-danger:      #B42318;
    --nc-danger-soft: #FEF3F2;

    /* Notification types: same colours as the worklists */
    --nc-t-appointment: #5D6E80;
    --nc-t-lab:         #0B67B2;
    --nc-t-xray:        #0E8A7E;
    --nc-t-billing:     #D0654A;
    --nc-t-system:      #D29A1E;

    --nc-radius-lg:   8px;
    --nc-radius-md:   8px;
    --nc-radius-sm:   6px;

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
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
    letter-spacing: -0.015em;
    color: var(--nc-ink);
}

.nc-lede {
    margin: 0.2rem 0 0;
    font-size: 0.8125rem;
    color: var(--nc-muted);
}

.nc-lede:empty { display: none; }
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
.nc-alert--success { background: #ECFDF3; border-color: #ABEFC6; color: #067647; }
.nc-alert--error   { background: #FEF3F2; border-color: #FECDCA; color: #B42318; }

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
    white-space: nowrap;
    text-decoration: none;
    cursor: pointer;
    transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
}

.nc-btn:hover {
    background: var(--nc-rail);
    border-color: var(--pm-line-strong, #C3CFDB);
    color: var(--nc-ink);
}

.nc-btn i { font-size: 0.9em; }

/* Row action: "View details" — the one accented action in a row */
.nc-btn--sm {
    height: 28px;
    padding: 0 0.65rem;
    font-size: 0.76rem;
    font-weight: 600;
    color: var(--nc-accent);
    background: var(--nc-surface);
    border-color: var(--nc-line);
}

.nc-btn--sm:hover {
    color: var(--nc-accent-dark);
    background: var(--nc-accent-soft);
    border-color: var(--pm-accent-line, #C6DCF1);
}

.nc-link {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    height: 28px;
    padding: 0 0.5rem;
    font-size: 0.76rem;
    font-weight: 500;
    color: var(--nc-muted);
    text-decoration: none;
    border-radius: var(--nc-radius-sm);
    transition: background-color 0.15s ease, color 0.15s ease;
}

.nc-link:hover { background: var(--nc-line-soft); color: var(--nc-ink); }

/* Delete stays quiet until it is pointed at */
.nc-link--danger { margin-left: auto; }
.nc-link--danger:hover { background: var(--nc-danger-soft); color: var(--nc-danger); }

/* ---------- Panel ---------- */

.nc-panel {
    background: var(--nc-surface);
    border: 1px solid var(--nc-line);
    border-radius: var(--nc-radius-lg);
    overflow: hidden;
}

/* ---------- Toolbar: tabs + search ---------- */

.nc-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding-right: 1rem;
    border-bottom: 1px solid var(--nc-line);
    background: var(--nc-surface);
}

/* ---------- Tabs ---------- */

.nc-tabs {
    display: flex;
    gap: 0.15rem;
    padding: 0 0.75rem;
    overflow-x: auto;
    scrollbar-width: none;
}

.nc-tabs::-webkit-scrollbar { display: none; }

.nc-tab {
    position: relative;
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    padding: 0.85rem 0.6rem;
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
    left: 0.4rem;
    right: 0.4rem;
    bottom: -1px;
    height: 2px;
    background: transparent;
}

.nc-tab:hover { color: var(--nc-ink); }
.nc-tab.is-active { color: var(--nc-ink); }
.nc-tab.is-active::after { background: var(--nc-accent); }

.nc-tab-count {
    min-width: 1.4rem;
    padding: 0.05rem 0.4rem;
    font-size: 0.7rem;
    font-weight: 600;
    text-align: center;
    color: var(--nc-muted);
    background: var(--nc-line-soft);
    border-radius: 4px;
    font-variant-numeric: tabular-nums;
}

.nc-tab.is-active .nc-tab-count { color: var(--nc-accent); background: var(--nc-accent-soft); }

/* ---------- Search ---------- */

.nc-search {
    position: relative;
    display: flex;
    align-items: center;
    flex-shrink: 0;
    width: 16rem;
    max-width: 40vw;
}

.nc-search > .bi-search {
    position: absolute;
    left: 0.7rem;
    font-size: 0.8rem;
    color: var(--nc-faint);
    pointer-events: none;
}

.nc-search-input {
    width: 100%;
    height: 34px;
    padding: 0 2rem 0 2rem;
    font-size: 0.8125rem;
    color: var(--nc-ink);
    background: var(--nc-surface);
    border: 1px solid var(--nc-line);
    border-radius: var(--nc-radius-sm);
    outline: none;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

.nc-search-input::placeholder { color: var(--nc-faint); }

.nc-search-input:focus {
    border-color: var(--nc-accent);
    box-shadow: 0 0 0 3px rgba(11, 103, 178, 0.15);
}

.nc-search-input::-webkit-search-cancel-button { display: none; }

.nc-search-clear {
    position: absolute;
    right: 0.35rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    font-size: 0.65rem;
    color: var(--nc-muted);
    background: none;
    border: 0;
    border-radius: 4px;
    cursor: pointer;
}

.nc-search-clear:hover { background: var(--nc-line-soft); color: var(--nc-ink); }

/* ---------- Type filter ---------- */

.nc-chips {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.6rem 1rem;
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
    padding: 0 0.6rem;
    font-size: 0.76rem;
    font-weight: 500;
    color: var(--nc-text);
    background: var(--nc-surface);
    border: 1px solid transparent;
    border-radius: var(--nc-radius-sm);
    white-space: nowrap;
    cursor: pointer;
    transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
}

.nc-chip:hover { background: var(--nc-rail); color: var(--nc-ink); }

.nc-chip.is-active {
    color: var(--nc-ink);
    font-weight: 600;
    background: var(--nc-surface);
    border-color: var(--pm-line-strong, #C3CFDB);
}

.nc-chip-count {
    font-size: 0.7rem;
    font-weight: 500;
    color: var(--nc-faint);
    font-variant-numeric: tabular-nums;
}

.nc-chip-dot {
    width: 7px;
    height: 7px;
    border-radius: 2px;
    flex-shrink: 0;
    background: var(--nc-faint);
}

.nc-chip-dot--appointment { background: var(--nc-t-appointment); }
.nc-chip-dot--lab         { background: var(--nc-t-lab); }
.nc-chip-dot--xray        { background: var(--nc-t-xray); }
.nc-chip-dot--billing,
.nc-chip-dot--payment     { background: var(--nc-t-billing); }
.nc-chip-dot--system      { background: var(--nc-t-system); }

/* ---------- List ---------- */

.nc-list { background: var(--nc-surface); }

/* ---------- Date groups ---------- */

.nc-group.is-hidden { display: none; }

.nc-group-head {
    position: sticky;
    top: 0;
    z-index: 2;
    display: flex;
    align-items: center;
    margin: 0;
    padding: 0.45rem 1rem;
    font-size: 0.7rem;
    font-weight: 600;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    color: var(--nc-muted);
    background: var(--nc-canvas);
    border-bottom: 1px solid var(--nc-line-soft);
}

.nc-group + .nc-group .nc-group-head { border-top: 1px solid var(--nc-line-soft); }

.nc-group-body { display: flex; flex-direction: column; }

/* ---------- Row ---------- */

.nc .nc-card {
    position: relative;
    display: flex;
    align-items: flex-start;
    gap: 0.85rem;
    padding: 0.85rem 1rem 0.75rem 1.75rem;
    background: var(--nc-surface);
    border: 0;
    border-bottom: 1px solid var(--nc-line-soft);
    border-radius: 0;
    box-shadow: none;
    transition: background-color 0.12s ease;
}

.nc .nc-group-body > .nc-card:last-child { border-bottom: 0; }

.nc .nc-card:hover { background: var(--nc-rail); }

.nc-card.is-hidden { display: none; }

/* Unread: a dot and a heavier title. No side bar, no tinted border. */
.nc .nc-card.is-unread { background: var(--nc-unread); }
.nc .nc-card.is-unread:hover { background: var(--nc-rail); }

.nc .nc-card.is-unread::before {
    content: "";
    position: absolute;
    top: 1.45rem;
    left: 0.7rem;
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: var(--nc-accent);
}

.nc-card mark {
    padding: 0 0.1em;
    color: inherit;
    background: #FEF0C7;
    border-radius: 2px;
}

/* ---------- Leading visual ---------- */

.nc-lead {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-top: 0.1rem;
}

.nc-avatar {
    position: relative;
    width: 36px;
    height: 36px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    overflow: hidden;
    font-size: 0.72rem;
    font-weight: 600;
    letter-spacing: 0.02em;
    color: var(--nc-text);
    background: var(--nc-line-soft);
    box-shadow: inset 0 0 0 1px rgba(16, 32, 46, 0.06);
}

.nc-avatar--male,
.nc-avatar--female { background: #F2F4F7; }

.nc-avatar-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.nc-tile {
    width: 36px;
    height: 36px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    border-radius: var(--nc-radius-md);
    font-size: 1rem;
    color: var(--tone, var(--nc-muted));
    background: color-mix(in srgb, var(--tone, var(--nc-muted)) 10%, #FFFFFF);
}

.nc-tile--appointment { --tone: var(--nc-t-appointment); }
.nc-tile--lab         { --tone: var(--nc-t-lab); }
.nc-tile--xray        { --tone: var(--nc-t-xray); }
.nc-tile--billing,
.nc-tile--payment     { --tone: var(--nc-t-billing); }
.nc-tile--system      { --tone: var(--nc-t-system); }

/*
 * Department artwork (white line drawing on a dark grey square).
 * "screen" keeps the white strokes white and lifts the grey square
 * into the department colour, so the artwork stays but matches the theme.
 */
.nc .nc-tile--png {
    padding: 0;
    border: 0;
    background: var(--tone, var(--nc-accent));
    box-shadow: inset 0 0 0 1px rgba(16, 32, 46, 0.08);
}

.nc .nc-tile--png .nc-tile-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    mix-blend-mode: screen;
}

/* ---------- Body ---------- */

.nc-body { flex: 1; min-width: 0; }

.nc-body-head {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: 0.75rem;
    margin-bottom: 0.15rem;
}

.nc-card-title {
    margin: 0;
    min-width: 0;
    font-size: 0.85rem;
    font-weight: 500;
    line-height: 1.35;
    color: var(--nc-text);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.nc-card.is-unread .nc-card-title { font-weight: 600; color: var(--nc-ink); }

.nc-time {
    flex-shrink: 0;
    font-size: 0.74rem;
    color: var(--nc-muted);
    white-space: nowrap;
    font-variant-numeric: tabular-nums;
    cursor: help;
}

.nc-card-message {
    margin: 0 0 0.45rem;
    font-size: 0.8rem;
    line-height: 1.5;
    color: var(--nc-muted);
    overflow-wrap: anywhere;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.nc-card.is-unread .nc-card-message { color: var(--nc-text); }

.nc-body-foot {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    flex-wrap: wrap;
    margin-left: -0.5rem;
}

.nc-body-foot > .nc-btn--sm:first-child { margin-left: 0.5rem; }

.nc-locked {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    height: 28px;
    padding: 0 0.5rem;
    font-size: 0.76rem;
    color: var(--nc-faint);
}

/* ---------- Selection mode (checkbox replaces the unread dot) ---------- */

.nc .nc-check { top: 1.3rem; left: 0.85rem; }

.nc .nc-list.is-selecting .nc-card { padding-left: 2.75rem; cursor: pointer; }
.nc .nc-list.is-selecting .nc-card::before { display: none; }

.nc .nc-list.is-selecting .nc-card.is-selected {
    background: var(--nc-accent-soft);
    box-shadow: none;
}

/* ---------- Empty states ---------- */

.nc-empty {
    padding: 3.5rem 1rem;
    text-align: center;
    background: var(--nc-surface);
}

.nc-empty-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 44px;
    height: 44px;
    margin: 0 auto 0.85rem;
    font-size: 1.2rem;
    color: var(--nc-faint);
    background: var(--nc-line-soft);
    border-radius: var(--nc-radius-md);
}

.nc-empty h3 {
    margin: 0 0 0.25rem;
    font-size: 0.95rem;
    font-weight: 600;
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
    .nc-head-actions .nc-btn { flex: 1; }

    .nc-toolbar {
        flex-direction: column;
        align-items: stretch;
        gap: 0;
        padding-right: 0;
    }

    .nc-search {
        width: auto;
        max-width: none;
        margin: 0 1rem 0.75rem;
    }

    .nc .nc-card { padding: 0.8rem 0.85rem 0.7rem 1.6rem; gap: 0.7rem; }
    .nc .nc-card.is-unread::before { left: 0.6rem; }

    .nc-card-title { white-space: normal; }
}

@media (max-width: 480px) {
    .nc-avatar,
    .nc-tile { width: 32px; height: 32px; }

    .nc-body-head { flex-direction: column; gap: 0.1rem; }
    .nc-link--danger { margin-left: 0; }
}

@media (prefers-reduced-motion: reduce) {
    .nc *, .nc *::before, .nc *::after {
        transition-duration: 0.01ms !important;
        animation-duration: 0.01ms !important;
    }
}
</style>
