<style>
    #laravelusers .lu-icon { vertical-align: middle; transform-origin: center; transition: transform 160ms ease; }
    #laravelusers .lu-icon-label { display: inline-flex; align-items: center; gap: 8px; }
    #laravelusers .lu-icon-label .lu-icon { width: 18px; height: 18px; flex-shrink: 0; }
    #laravelusers .lu-control-title { gap: 10px; font-weight: 600; }
    #laravelusers .lu-title-icon { display: inline-flex; align-items: center; justify-content: center; flex: 0 0 34px; width: 34px; height: 34px; border: 1px solid var(--lu-border, #d5deeb); border-radius: 9px; background: var(--lu-soft, #f3f6fb); color: var(--lu-accent, #2458b7); }
    #laravelusers .lu-title-icon .lu-icon { width: 18px; height: 18px; }
    #laravelusers .lu-title-heading { display: flex; align-items: center; gap: 10px; }
    #laravelusers .lu-title-icon-avatar { color: #2458b7; background: #edf4ff; }
    #laravelusers .lu-title-icon-palette { color: #7048a8; background: #f4effb; }
    #laravelusers .lu-title-icon-appearance { color: #a15c00; background: #fff5df; }
    #laravelusers .lu-title-icon-notifications { color: #087e8b; background: #e9f8f8; }
    #laravelusers .lu-title-icon-danger { color: #b42332; background: #fff0f1; }
    #laravelusers[data-lu-theme="dark"] .lu-title-icon { border-color: #45536a; background: #263449; color: #bdd1f4; }
    #laravelusers .lu-settings-choice > .lu-control-title { min-height: 38px; margin-bottom: 14px; }
    #laravelusers :is(a, button):not(:disabled):hover .lu-icon[data-lu-icon="install"] { transform: translateY(1px); }
    #laravelusers :is(a, button):not(:disabled):hover .lu-icon[data-lu-icon="edit"] { transform: rotate(-6deg); }
    #laravelusers :is(a, button):not(:disabled):hover .lu-icon[data-lu-icon="restore"] { transform: rotate(-18deg); }
    #laravelusers :is(a, button):not(:disabled):hover .lu-icon[data-lu-icon="settings"] { transform: rotate(18deg); }
    #laravelusers :is(a, button):not(:disabled):hover .lu-icon[data-lu-icon="show"] { transform: scale(1.08); }
    #laravelusers :is(a, button):not(:disabled):hover .lu-icon[data-lu-icon="check"] { transform: scale(1.08); }
    @media (prefers-reduced-motion: reduce) { #laravelusers .lu-icon { transition: none; } #laravelusers :is(a, button):hover .lu-icon { transform: none; } }
    #laravelusers [data-lu-edit-panel][hidden] { display: none !important; }
    #laravelusers .lu-edit-card { max-width: 1120px; }
    #laravelusers .lu-edit-card .lu-profile-body { display: grid; grid-template-columns: 240px minmax(0, 1fr); }
    #laravelusers .lu-edit-card .lu-profile-identity { flex-direction: column; justify-content: flex-start; padding: 48px 24px; text-align: center; }
    #laravelusers .lu-edit-card .lu-form { min-width: 0; padding: 24px; }
    #laravelusers .lu-edit-card .lu-edit-tabs { padding: 0 0 16px; margin-bottom: 24px; gap: 4px; }
    #laravelusers .lu-edit-tabs button { padding: 9px 10px; font-size: .75rem; }
    #laravelusers .lu-edit-card .lu-form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 20px; }
    #laravelusers .lu-edit-card .lu-field { min-width: 0; grid-template-columns: 1fr; gap: 8px; margin-bottom: 20px; }
    #laravelusers .lu-edit-card .lu-field > label { text-align: left; padding: 0; font-size: .8rem; font-weight: 600; }
    #laravelusers .lu-edit-card .lu-muted { font-size: .75rem; line-height: 1.5; margin: 8px 0 0; }
    #laravelusers .lu-edit-card .lu-form-actions { padding-top: 20px; border-top: 1px solid var(--lu-border, #ced4da); margin-top: 12px; }
    #laravelusers .lu-edit-card .lu-user-appearance { border: 0; padding: 0; margin: 0; }
    #laravelusers .lu-edit-card .lu-user-appearance > legend { position: absolute; width: 1px; height: 1px; padding: 0; overflow: hidden; clip-path: inset(50%); }
    #laravelusers .lu-edit-card .lu-appearance-modes { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 24px; }
    #laravelusers .lu-edit-card .lu-appearance-mode { min-width: 0; padding: 18px; border: 1px solid var(--lu-border, #ced4da); border-radius: 7px; }
    #laravelusers .lu-edit-card .lu-appearance-mode h2 { font-size: .875rem; font-weight: 600; margin: 0 0 20px; }
    #laravelusers .lu-edit-card .lu-appearance-mode .lu-control { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
    #laravelusers .lu-edit-card .lu-appearance-mode .lu-appearance-inherit { flex-basis: 100%; margin: 4px 0 0; font-size: .75rem; }
    #laravelusers .lu-edit-card .lu-appearance-mode select { width: auto; min-width: 0; flex: 1; font-size: .8rem; }
    #laravelusers .lu-edit-card .lu-range-controls, #laravelusers .lu-edit-card .lu-range-labels { width: 100%; }
    #laravelusers .lu-edit-card [data-lu-edit-panel="account"] { max-width: 540px; }
    #laravelusers .lu-edit-card select[multiple] { min-height: 144px; background-image: none; }
    @media (max-width: 1000px) { #laravelusers .lu-edit-card .lu-profile-body { grid-template-columns: 200px minmax(0, 1fr); } #laravelusers .lu-edit-card .lu-appearance-modes { grid-template-columns: 1fr; gap: 20px; } }
    @media (max-width: 760px) { #laravelusers .lu-edit-card .lu-profile-body { grid-template-columns: 1fr; } #laravelusers .lu-edit-card .lu-profile-identity { flex-direction: row; align-items: center; padding: 24px; text-align: left; } }
    @media (max-width: 520px) { #laravelusers .lu-edit-card .lu-form { padding: 18px; } #laravelusers .lu-edit-card .lu-form-grid { grid-template-columns: 1fr; gap: 0; } #laravelusers .lu-edit-card .lu-profile-identity { padding: 20px 18px; } }
    #laravelusers [data-lu-settings-panel][hidden], #laravelusers [data-lu-settings-form][hidden] { display: none !important; }
    #laravelusers .lu-settings-panel { max-width: 1160px; margin: 0 auto; }
    #laravelusers .lu-settings-tabs { display: flex; gap: 6px; padding: 16px 24px; border-bottom: 1px solid var(--lu-border, #ced4da); overflow-x: auto; max-width: 100%; }
    #laravelusers .lu-settings-tabs button { display: inline-flex; align-items: center; justify-content: center; gap: 8px; flex-shrink: 0; padding: 9px 12px; border: 1px solid transparent; border-radius: 6px; background: transparent; color: var(--lu-muted, #526077); font-size: .8rem; font-weight: 600; white-space: nowrap; cursor: pointer; }
    #laravelusers .lu-settings-tabs button[aria-selected="true"] { background: #2458b7; color: #fff; }
    #laravelusers .lu-settings-tabs .lu-icon { width: 16px; height: 16px; }
    #laravelusers .lu-settings-tabs button:focus-visible { outline: 2px solid var(--lu-accent, #2458b7); outline-offset: 2px; }
    #laravelusers .lu-package-state { margin: 6px 0 12px; }
    #laravelusers .lu-package-badge { display: inline-flex; align-items: center; min-height: 20px; padding: 2px 8px; border: 1px solid var(--lu-border, #ced4da); border-radius: 999px; font-size: .68rem; font-weight: 600; line-height: 1.2; }
    #laravelusers .lu-package-badge-installed { border-color: #a8d9bf; background: #e8f5ed; color: #17623b; }
    #laravelusers .lu-package-badge-available { background: var(--lu-soft, #f4f6fa); color: var(--lu-muted, #526077); }
    #laravelusers[data-lu-theme="dark"] .lu-package-badge-installed { border-color: #35684f; background: #193a2b; color: #a7e0bc; }
    @media (max-width: 740px) {
        #laravelusers .lu-settings-tabs:not(.lu-edit-tabs):not(.lu-account-tabs) { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); overflow: visible; }
        #laravelusers .lu-settings-tabs button { min-width: 0; white-space: normal; text-align: center; }
    }
    @media (max-width: 640px) {
        #laravelusers .lu-edit-tabs { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); overflow: visible; }
        #laravelusers .lu-edit-tabs button { min-width: 0; white-space: normal; text-align: center; }
    }
    @media (max-width: 620px) {
        #laravelusers .lu-account-tabs { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); overflow: visible; }
        #laravelusers .lu-account-tabs button { min-width: 0; white-space: normal; text-align: center; }
    }
    #laravelusers [data-lu-settings-panel] .lu-package-settings { border-top: 0; }
    #laravelusers .lu-account-settings-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 20px; }
    #laravelusers #lu-account-access-form fieldset { min-width: 0; border: 0; padding: 0; margin: 0; }
    #laravelusers #lu-account-access-form .lu-email-check { display: flex; align-items: center; gap: 8px; margin: 16px 0; font-size: .875rem; }
    #laravelusers .lu-account-card { max-width: 1120px; }
    #laravelusers .lu-account-body { display: grid; grid-template-columns: 260px minmax(0, 1fr); }
    #laravelusers .lu-account-identity { min-width: 0; background: var(--lu-soft, #f8fafc); }
    #laravelusers .lu-account-identity .lu-profile-identity { flex-direction: column; justify-content: center; text-align: center; min-height: 260px; padding: 32px 20px; }
    #laravelusers .lu-account-summary { padding: 24px; overflow-wrap: anywhere; }
    #laravelusers .lu-account-summary h2 { font-size: 1rem; }
    #laravelusers .lu-account-summary p { font-size: .8rem; color: var(--lu-muted, #526077); }
    #laravelusers .lu-account-content { min-width: 0; padding: 8px 28px; }
    #laravelusers .lu-account-tabs { padding: 0 0 14px; margin: 8px 0 0; }
    #laravelusers .lu-account-panels { min-width: 0; }
    #laravelusers .lu-account-panels > [role="tabpanel"] { min-width: 0; }
    #laravelusers .lu-account-card .lu-account-section > .lu-button { margin-top: 12px; }
    #laravelusers .lu-account-section { padding: 24px 0; border-bottom: 1px solid var(--lu-border, #ced4da); }
    #laravelusers .lu-account-section:last-child { border-bottom: 0; }
    #laravelusers .lu-account-section h2 { display: flex; align-items: center; gap: 10px; font-size: 1rem; margin: 0 0 20px; }
    #laravelusers .lu-account-section p { font-size: .8rem; }
    #laravelusers .lu-account-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 20px; }
    #laravelusers .lu-account-field { min-width: 0; margin-bottom: 18px; }
    #laravelusers .lu-account-field > label { display: block; font-size: .8rem; font-weight: 500; margin-bottom: 8px; }
    #laravelusers .lu-account-field input { min-width: 0; }
    #laravelusers .lu-account-card .lu-field { grid-template-columns: 1fr; gap: 8px; }
    #laravelusers .lu-account-card .lu-field > label { text-align: left; padding: 0; }
    #laravelusers .lu-account-card .lu-user-appearance .lu-control { display: flex; flex-wrap: wrap; align-items: center; gap: 12px; }
    #laravelusers .lu-account-card .lu-user-appearance .lu-control > select { flex: 1 1 220px; width: auto; min-width: min(220px, 100%); }
    #laravelusers .lu-account-card .lu-user-appearance .lu-range-controls, #laravelusers .lu-account-card .lu-user-appearance .lu-range-labels { flex: 0 0 100%; width: 100%; }
    #laravelusers .lu-account-card .lu-user-appearance input[type="color"] { height: 44px; }
    #laravelusers .lu-account-card .lu-user-appearance .lu-range-controls input[type="range"] { height: 44px; }
    #laravelusers .lu-account-card .lu-user-appearance .lu-appearance-reset { min-height: 44px; padding: 8px 10px; font-size: .8rem; }
    #laravelusers .lu-account-card .lu-user-appearance .lu-appearance-inherit { flex: 0 0 100%; margin: 4px 0 0; }
    #laravelusers .lu-account-card .lu-appearance-mode { min-width: 0; margin-top: 16px; padding: 18px; border: 1px solid var(--lu-border, #ced4da); border-radius: 7px; }
    #laravelusers .lu-account-card .lu-appearance-mode h2 { margin: 0 0 16px; font-size: .875rem; }
    #laravelusers .lu-account-card .lu-user-appearance > .lu-muted { margin-top: 16px; font-size: .75rem; }
    #laravelusers .lu-account-danger h2 { color: #b42332; }
    #laravelusers .lu-account-danger { margin-top: 20px; padding: 20px; border: 1px solid color-mix(in srgb, #b42332 28%, var(--lu-border, #ced4da)); border-radius: 8px; background: color-mix(in srgb, #b42332 4%, var(--lu-bg, #fff)); }
    #laravelusers [data-lu-account-panel="admin"] { padding: 16px 0 24px; }
    #laravelusers [data-lu-account-panel="admin"] .lu-account-danger { margin: 0; padding: 24px; border-bottom: 1px solid color-mix(in srgb, #b42332 28%, var(--lu-border, #ced4da)); }
    #laravelusers [data-lu-account-panel="admin"] .lu-account-danger h2 { margin-bottom: 12px; }
    #laravelusers [data-lu-account-panel="admin"] .lu-account-danger p { margin-bottom: 20px; }
    #laravelusers [data-lu-account-panel="admin"] .lu-account-danger .lu-button { margin-top: 0; }
    #laravelusers #lu-account-delete-dialog .lu-email-heading { background: #b42332; color: #fff; }
    #laravelusers #lu-account-delete-dialog .lu-email-close { color: #fff; }
    #laravelusers #lu-account-delete-dialog .lu-danger { background: #b42332; color: #fff; }
    #laravelusers #lu-account-delete-dialog { width: min(480px, calc(100% - 32px)); }
    #laravelusers #lu-account-delete-dialog form { display: flex; flex-direction: column; margin: 0; }
    #laravelusers #lu-account-delete-dialog .lu-email-body { padding: 20px 24px 8px; }
    #laravelusers #lu-account-delete-dialog .lu-email-body > p { margin: 0 0 18px; line-height: 1.5; }
    #laravelusers #lu-account-delete-dialog .lu-account-field { margin-bottom: 16px; }
    #laravelusers #lu-account-delete-dialog .lu-email-footer { padding: 14px 24px 20px; }
    #laravelusers #lu-account-delete-dialog .lu-button { min-width: 112px; }
    #laravelusers .lu-account-confirm { max-width: 680px; margin: 0 auto; }
    #laravelusers .lu-account-confirm > a { margin-top: 24px; }
    @media (max-width: 760px) { #laravelusers .lu-account-body, #laravelusers .lu-account-settings-grid { grid-template-columns: 1fr; } #laravelusers .lu-account-identity .lu-profile-identity { min-height: 0; flex-direction: row; text-align: left; padding: 24px 20px; } #laravelusers .lu-account-summary { padding: 16px 20px; } #laravelusers .lu-account-content { padding: 0 20px; } }
    @media (max-width: 520px) { #laravelusers .lu-account-grid { grid-template-columns: 1fr; gap: 0; } #laravelusers .lu-settings-tabs { padding: 12px 18px; } }
    #laravelusers .lu-user-appearance { min-width: 0; margin: 20px 0; padding: 16px; border: 1px solid var(--lu-border, #ced4da); border-radius: 6px; }
    #laravelusers .lu-user-appearance legend { float: none; width: auto; padding: 0 6px; font-size: .875rem; }
    #laravelusers input[type="color"] { width: 72px; height: 38px; padding: 3px; border: 1px solid var(--lu-border, #ced4da); border-radius: 5px; background: var(--lu-bg, #fff); vertical-align: middle; cursor: pointer; }
    #laravelusers .lu-appearance-inherit { display: inline-flex; align-items: center; gap: 8px; margin-left: 12px; font-size: .875rem; }
    #laravelusers .lu-appearance-inherit input { margin: 0; }
    #laravelusers .lu-heading .lu-settings-button, #laravelusers .card-header .lu-settings-button { display: inline-flex; align-items: center; justify-content: center; width: 34px; height: 34px; min-width: 34px; padding: 0; gap: 0; flex: 0 0 34px; }
    #laravelusers .lu-settings-button .lu-icon { width: 18px; height: 18px; flex-shrink: 0; }
    #laravelusers .lu-settings-form { padding: 24px; }
    #laravelusers .lu-settings-form > fieldset { border: 0; margin: 0; padding: 0; min-width: 0; }
    #laravelusers .lu-settings-form legend:where(:not(.lu-sr-only):not(.sr-only)) { float: none; display: block; width: 100%; margin-bottom: 14px; font-size: 1rem; font-weight: 600; }
    #laravelusers .lu-settings-form h2 { margin: 28px 0 8px; font-size: 1.1rem; }
    #laravelusers .lu-settings-appearance { border: 0; padding: 0; min-width: 0; margin: 0; }
    #laravelusers .lu-settings-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; }
    @media (min-width: 701px) { #laravelusers .lu-settings-grid.lu-settings-dark-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } #laravelusers .lu-settings-dark-grid > :first-child { grid-column: auto; } }
    #laravelusers .lu-settings-choice { min-width: 0; padding: 16px; border: 1px solid var(--lu-border, #ced4da); border-radius: 7px; }
    #laravelusers .lu-goodbye-settings > summary { cursor: pointer; font-size: .875rem; font-weight: 600; }
    #laravelusers .lu-goodbye-settings[open] > summary { margin-bottom: 12px; }
    #laravelusers .lu-settings-form label { display: block; margin-bottom: 6px; font-size: .875rem; }
    #laravelusers .lu-settings-form .lu-icon-label, #laravelusers .lu-settings-form .lu-title-heading { display: flex; align-items: center; gap: 10px; }
    #laravelusers .lu-title-heading > span:not(.lu-title-icon), #laravelusers .lu-icon-label > span:not(.lu-title-icon) { min-width: 0; }
    #laravelusers .lu-settings-form input[type="color"] { display: block; width: 72px; height: 38px; padding: 3px; border: 1px solid var(--lu-border, #ced4da); border-radius: 5px; background: var(--lu-bg, #fff); }
    #laravelusers .lu-settings-form p { margin: 8px 0 16px; font-size: .8rem; }
    #laravelusers .lu-access-rule { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; min-width: 0; margin: 20px 0; padding: 16px; border: 1px solid var(--lu-border, #ced4da); border-radius: 6px; }
    #laravelusers .lu-access-choices { max-height: 150px; overflow-y: auto; padding-top: 6px; }
    #laravelusers .lu-access-choices label { display: flex; align-items: center; gap: 8px; }
    #laravelusers .lu-access-choices input[type="checkbox"] { margin-right: 0; }
    #laravelusers .lu-settings-notifications { min-width: 0; margin: 24px 0 0; border: 0; border-top: 1px solid var(--lu-border, #ced4da); padding: 20px 0 0; }
    #laravelusers .lu-settings-form .lu-settings-check { display: flex; align-items: center; gap: 8px; margin: 0; font-weight: 400; }
    #laravelusers .lu-settings-check input[type="checkbox"] { margin: 0; }
    #laravelusers .lu-settings-footer { display: flex; justify-content: flex-end; padding-top: 12px; }
    @media (max-width: 700px) { #laravelusers .lu-settings-grid, #laravelusers .lu-access-rule { grid-template-columns: 1fr; } #laravelusers .lu-settings-form { padding: 18px; } }
    #laravelusers .lu-color-controls, #laravelusers .lu-range-controls, #laravelusers .lu-gradient-toggle { display: flex; align-items: center; gap: 10px; }
    #laravelusers .lu-gradient-toggle { margin: 16px 0; justify-content: space-between; }
    #laravelusers .lu-range-controls input[type="range"] { flex: 1; min-width: 0; width: 100%; accent-color: var(--lu-accent, #2458b7); }
    #laravelusers .lu-range-controls output { min-width: 36px; font-size: .75rem; text-align: right; }
    #laravelusers .lu-range-labels { display: flex; justify-content: space-between; font-size: .7rem; margin-top: 3px; }
    #laravelusers .lu-appearance-reset { display: inline-flex; gap: 4px; align-items: center; justify-content: center; flex-shrink: 0; padding: 4px 7px; border: 1px solid var(--lu-border, #ced4da); border-radius: 5px; background: var(--lu-bg, #fff); color: var(--lu-text, #212529); font-size: .75rem; cursor: pointer; }
    #laravelusers .lu-appearance-reset .lu-icon { width: 14px; height: 14px; }
    #laravelusers .lu-settings-preview { display: flex; align-items: center; justify-content: center; min-height: 76px; height: auto; padding: 18px; margin-top: 16px; border-radius: 5px; }
    #laravelusers .lu-settings-preview > span { display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: 50%; background: #e7eef8; color: #344760; }
    #laravelusers .lu-settings-preview .lu-icon { width: 20px; height: 20px; }
    #laravelusers .lu-settings-form .lu-access-rule legend { width: auto; padding: 0 6px; margin: 0; }
    @media (max-width: 900px) and (min-width: 701px) { #laravelusers .lu-settings-grid { grid-template-columns: 1fr 1fr; } #laravelusers .lu-settings-grid:not(.lu-settings-dark-grid) > :first-child { grid-column: 1 / -1; } }
    #laravelusers .lu-package-settings { border-top: 1px solid var(--lu-border, #ced4da); margin: 0 24px; padding: 24px 0; }
    #laravelusers .lu-package-settings h2 { margin: 0 0 8px; font-size: 1rem; font-weight: 600; }
    #laravelusers .lu-package-settings h3 { margin: 0; font-size: .875rem; font-weight: 600; }
    #laravelusers .lu-package-preferred { color: var(--lu-muted, #667085); font-size: .75rem; font-weight: 500; }
    #laravelusers .lu-package-settings p { font-size: .8rem; }
    #laravelusers .lu-package-status:not([hidden]) { display: flex; align-items: center; gap: 10px; padding: 12px 14px; border: 1px solid var(--lu-border, #d5deeb); border-radius: 6px; background: var(--lu-soft, #f3f6fb); }
    #laravelusers .lu-package-status > span:not([hidden]):not([data-lu-package-status-message]) { display: inline-flex; align-items: center; justify-content: center; flex: 0 0 20px; }
    #laravelusers .lu-package-status .lu-icon { width: 18px; height: 18px; }
    #laravelusers .lu-package-status[data-state="failed"] { border-color: #b42332; }
    #laravelusers [data-lu-package-status-icon="failed"] { color: #b42332; }
    #laravelusers[data-lu-theme="dark"] [data-lu-package-status-icon="failed"] { color: #ffadba; }
    #laravelusers .lu-package-spinner .lu-icon { animation: lu-package-spin 1s linear infinite; }
    @keyframes lu-package-spin { to { transform: rotate(360deg); } }
    @media (prefers-reduced-motion: reduce) { #laravelusers .lu-package-spinner .lu-icon { animation: none; } }
    #laravelusers [data-lu-package-status-verified] { flex-shrink: 0; color: #087f5b; }
    #laravelusers [data-lu-package-status-message] { min-width: 0; }
    #laravelusers[data-lu-theme="dark"] [data-lu-package-status-verified] { color: #7de1ab; }
    #laravelusers .lu-package-setup-completed { display: flex; align-items: center; gap: 8px; margin: 12px 0; }
    #laravelusers .lu-package-setup-completed .lu-icon { flex: 0 0 18px; width: 18px; height: 18px; color: #087f5b; }
    #laravelusers[data-lu-theme="dark"] .lu-package-setup-completed .lu-icon { color: #7de1ab; }
    #laravelusers .lu-package-requirement-actions { display: flex; align-items: center; gap: 10px; margin: 0 0 12px; }
    #laravelusers .lu-package-requirement-actions button { justify-content: center; margin: 0; }
    #laravelusers .lu-package-settings code { display: block; overflow-wrap: anywhere; font-size: .75rem; }
    #laravelusers .lu-package-instructions { margin-top: 12px; font-size: .8rem; }
    #laravelusers .lu-package-warning { padding: 12px; border: 1px solid #b42332; border-radius: 5px; }
    #laravelusers .lu-cleanup-warning { display: grid; grid-template-columns: 40px minmax(0, 1fr); align-items: center; gap: 14px; margin: 16px 0; padding: 16px 18px; border: 1px solid #a51d2d; border-radius: 7px; background: #b42332; color: #fff; }
    #laravelusers .lu-cleanup-warning-icon { display: flex; align-items: center; justify-content: center; }
    #laravelusers .lu-cleanup-warning-icon .lu-icon { width: 32px; height: 32px; stroke-width: 2.2; }
    #laravelusers .lu-cleanup-warning p { margin: 0; color: #fff; font-size: .875rem; font-weight: 600; line-height: 1.5; }
    #laravelusers #lu-package-dialog[data-lu-remove="true"] .lu-email-heading { background: #b42332; color: #fff; }
    #laravelusers #lu-package-dialog[data-lu-remove="true"] .lu-email-close { color: #fff; }
    #laravelusers #lu-cleanup-dialog .lu-email-heading { background: #b42332; color: #fff; }
    #laravelusers #lu-cleanup-dialog .lu-email-close { color: #fff; }
    #laravelusers #lu-cleanup-dialog .lu-button.lu-danger { background: #b42332; color: #fff; }
    #laravelusers #lu-cleanup-form fieldset { border: 0; margin: 0; padding: 0; min-width: 0; }
    #laravelusers #lu-cleanup-form .lu-email-check { display: flex; align-items: center; gap: 8px; margin-bottom: 16px; }
    #laravelusers #lu-cleanup-form .lu-reset-duration-row { max-width: 360px; }
    #laravelusers #lu-email-templates-form fieldset { border: 0; margin: 0; padding: 0; min-width: 0; }
    #laravelusers #lu-email-templates-form .lu-email-check { display: flex; align-items: center; gap: 8px; margin-bottom: 12px; }
    #laravelusers .lu-email-template { margin-top: 16px; border: 1px solid var(--lu-border, #ced4da); padding: 12px; border-radius: 6px; }
    #laravelusers .lu-email-template summary { display: flex; align-items: center; gap: 12px; cursor: pointer; font-size: .875rem; font-weight: 600; list-style: none; }
    #laravelusers .lu-email-template summary::-webkit-details-marker { display: none; }
    #laravelusers .lu-email-template summary::before { content: ""; flex: 0 0 7px; width: 7px; height: 7px; margin-right: 6px; border-right: 1.5px solid currentColor; border-bottom: 1.5px solid currentColor; transform: rotate(-45deg); }
    #laravelusers .lu-email-template[open] summary::before { transform: rotate(45deg); }
    #laravelusers .lu-email-template summary span { min-width: 0; white-space: nowrap; }
    #laravelusers #lu-email-templates-title { margin-bottom: 20px; }
    #laravelusers #lu-email-templates-form label:has([name="goodbye_on_delete"]) { margin-bottom: 24px; }
    @media (max-width: 480px) { #laravelusers .lu-email-template { padding: 10px; } #laravelusers .lu-email-template summary { gap: 6px; font-size: .75rem; } }
    #laravelusers .lu-email-template[open] summary { margin-bottom: 12px; }
    #laravelusers [data-lu-goodbye-options] .lu-email-check { display: flex; align-items: center; gap: 8px; margin-top: 12px; }
    #laravelusers [data-lu-goodbye-fields] { border: 0; margin: 12px 0 0; padding: 0; min-width: 0; }
    #laravelusers [data-lu-goodbye-editor] summary { cursor: pointer; }
    #laravelusers #lu-confirmation { max-height: calc(100vh - 32px); overflow-y: auto; }
    #laravelusers #lu-package-dialog .lu-button.lu-danger { background: #b42332; color: #fff; }
    @media (max-width: 700px) {
        #laravelusers .lu-package-settings { margin: 0 18px; }
        #laravelusers[data-lu-responsive-buttons="true"] .lu-package-settings :is(.lu-button, .btn) { width: auto; height: auto; min-height: 40px; padding: 8px 12px; gap: 6px; font-size: .8rem; line-height: 1.3; }
        #laravelusers .lu-package-requirement-actions { align-items: stretch; flex-direction: column; }
        #laravelusers .lu-package-requirement-actions button { width: 100% !important; }
    }
    #laravelusers select.lu-input, #laravelusers select.form-control, #laravelusers select.custom-select, #laravelusers select.lu-email-input { appearance: none; padding-right: 36px; background-image: linear-gradient(45deg, transparent 50%, currentColor 50%), linear-gradient(135deg, currentColor 50%, transparent 50%); background-position: right 18px center, right 12px center; background-size: 6px 6px; background-repeat: no-repeat; }
</style>
