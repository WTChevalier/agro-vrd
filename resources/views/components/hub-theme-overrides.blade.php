{{--
    Hub Theme Overrides — Sprint 139Y (2026-04-28)
    Compartido entre TODAS las apps Laravel del ecosistema Gurztac.
    Aplica CSS overrides de contraste y polish visual sin requerir DB.

    Diferencia con la versión del Hub: esta NO lee tema_preferencias del user
    (porque cada app tenant tiene su propia tabla users sin esa columna).
    Solo aplica los overrides estructurales (bordes, headings, badges, hover).
--}}
<style>
:root {
    --hub-accent: #f59e0b;
    --hub-border-strong: rgba(15, 23, 42, 0.12);
    --hub-text-strong: #0f172a;
    --hub-text-muted: #475569;
}
.dark {
    --hub-border-strong: rgba(248, 250, 252, 0.18);
    --hub-text-strong: #f8fafc;
    --hub-text-muted: #cbd5e1;
}

.fi-section {
    border: 1px solid var(--hub-border-strong) !important;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 1px 2px rgba(0,0,0,0.06) !important;
}
.fi-section:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.08), 0 2px 4px rgba(0,0,0,0.04) !important;
    transition: box-shadow 200ms ease;
}
.fi-section-header-heading {
    font-weight: 700 !important;
    color: var(--hub-text-strong) !important;
    letter-spacing: -0.01em;
}
.fi-section-header-description { color: var(--hub-text-muted) !important; }

.fi-wi-stats-overview-stat { border: 1px solid var(--hub-border-strong) !important; transition: transform 200ms ease, box-shadow 200ms ease; }
.fi-wi-stats-overview-stat:hover { transform: translateY(-2px); box-shadow: 0 8px 16px rgba(0,0,0,0.08) !important; }
.fi-wi-stats-overview-stat-value { font-weight: 800 !important; color: var(--hub-text-strong) !important; }
.fi-wi-stats-overview-stat-label { color: var(--hub-text-muted) !important; font-weight: 600 !important; }

.fi-ta-header-cell-label { font-weight: 700 !important; color: var(--hub-text-strong) !important; text-transform: uppercase; font-size: 0.72rem; letter-spacing: 0.04em; }
.fi-ta-row:hover { background: rgba(245,158,11,0.05) !important; }
.dark .fi-ta-row:hover { background: rgba(245,158,11,0.08) !important; }

.fi-badge { border: 1px solid currentColor !important; font-weight: 600 !important; }

.fi-fo-field-wrp-label, label.fi-fo-field-wrp-label { color: var(--hub-text-strong) !important; font-weight: 600 !important; }
.fi-fo-field-wrp-hint { color: var(--hub-text-muted) !important; }

.fi-sidebar-item-button { font-weight: 500 !important; }
.fi-sidebar-item-active .fi-sidebar-item-button { font-weight: 700 !important; background: linear-gradient(90deg, rgba(245,158,11,0.12) 0%, rgba(245,158,11,0.04) 100%) !important; border-left: 3px solid var(--hub-accent) !important; }
.fi-sidebar-group-label { color: var(--hub-text-strong) !important; font-weight: 700 !important; text-transform: uppercase; letter-spacing: 0.06em; font-size: 0.7rem !important; }

.fi-header-heading { font-weight: 800 !important; color: var(--hub-text-strong) !important; letter-spacing: -0.02em; }
.fi-header-subheading { color: var(--hub-text-muted) !important; }

.fi-btn-color-primary { box-shadow: 0 2px 4px rgba(245,158,11,0.2) !important; }
.fi-btn-color-primary:hover { box-shadow: 0 4px 8px rgba(245,158,11,0.3) !important; transform: translateY(-1px); transition: all 150ms ease; }

.fi-tabs-item-active { border-bottom: 3px solid var(--hub-accent) !important; font-weight: 700 !important; }

.fi-no-notification[data-color="success"] { border-left: 4px solid #10b981 !important; }
.fi-no-notification[data-color="warning"] { border-left: 4px solid #f97316 !important; }
.fi-no-notification[data-color="danger"]  { border-left: 4px solid #ef4444 !important; }
.fi-no-notification[data-color="info"]    { border-left: 4px solid #0ea5e9 !important; }

.fi-modal-heading { font-weight: 700 !important; color: var(--hub-text-strong) !important; }
.fi-section-content a:not(.fi-btn), .fi-prose a { color: #b45309 !important; text-decoration: underline; text-underline-offset: 2px; text-decoration-thickness: 1px; }
.fi-section-content a:not(.fi-btn):hover, .fi-prose a:hover { text-decoration-thickness: 2px; }
.fi-input, .fi-select-input { color: var(--hub-text-strong) !important; }
.fi-topbar-database-notifications-badge { background: #ef4444 !important; color: white !important; font-weight: 700 !important; }
</style>
