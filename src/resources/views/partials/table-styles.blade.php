<style>
    #laravelusers .lu-list-title { display: inline-flex; align-items: center; gap: 8px; }
    #laravelusers .lu-page-heading { box-sizing: border-box; display: flex; align-items: center; justify-content: space-between; flex-wrap: nowrap; gap: 16px; height: 74px; padding: 16px 24px; }
    #laravelusers .lu-page-heading-content { display: flex; align-items: center; justify-content: space-between; gap: 16px; width: 100%; min-width: 0; }
    #laravelusers .lu-page-heading .lu-list-title { flex: 1; min-width: 0; margin: 0; font-size: 20px; line-height: 25px; font-weight: 600; }
    #laravelusers .lu-page-heading .lu-list-title > .lu-icon { flex-shrink: 0; margin: 0; }
    #laravelusers .lu-page-heading .lu-title-text { display: block; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    #laravelusers .lu-page-heading :is(.lu-actions, .lu-header-actions, .pull-right) { flex-shrink: 0; flex-wrap: nowrap; }
    #laravelusers .lu-page-heading :is(.lu-button, .btn) { box-sizing: border-box; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; height: 40px; min-height: 40px; }
    #laravelusers .lu-page-heading .lu-settings-button { width: 40px; padding: 0; }
    @media (max-width: 640px) {
        #laravelusers .lu-page-heading { padding: 16px; }
        #laravelusers .lu-page-heading .lu-list-title { font-size: 16px; line-height: 20px; }
        #laravelusers .lu-page-heading .lu-title-text { display: -webkit-box; -webkit-box-orient: vertical; -webkit-line-clamp: 2; white-space: normal; }
    }
    #laravelusers .lu-header-actions { display: flex; align-items: center; gap: 8px; flex-wrap: nowrap; }
    #laravelusers .lu-header-actions .btn { color: inherit; border-color: var(--lu-border, #ced4da); white-space: nowrap; }
    @media (max-width: 640px) {
        #laravelusers[data-lu-responsive-buttons="true"] .lu-header-actions .btn { display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; padding: 0; }
        #laravelusers[data-lu-responsive-buttons="true"] .lu-header-actions .btn span { display: none; }
    }
    @php($profileColors = \jeremykenedy\laravelusers\Support\Frontend::profileColors())
    #laravelusers { --lu-profile-color: {{ $profileColors['base'] }}; --lu-profile-text: {{ $profileColors['text'] }}; --lu-profile-shade: {{ $profileColors['shade'] }}; --lu-profile-glow: {{ $profileColors['highlight'] }}; --lu-profile-image: {{ config('laravelusers.profileCardGradient', true) ? 'initial' : 'none' }}; }
    @php($darkProfileColors = \jeremykenedy\laravelusers\Support\Frontend::profileColors('profileCardColor', '#2458b7', true))
    #laravelusers { --lu-profile-dark-color: {{ $darkProfileColors['base'] }}; --lu-profile-dark-text: {{ $darkProfileColors['text'] }}; --lu-profile-dark-shade: {{ $darkProfileColors['shade'] }}; --lu-profile-dark-glow: {{ $darkProfileColors['highlight'] }}; --lu-profile-dark-image: {{ (config('laravelusers.profileCardDarkGradient') ?? config('laravelusers.profileCardGradient', true)) ? 'initial' : 'none' }}; }
    #laravelusers:not([data-lu-table-view="cards"]) [data-lu-table] tbody td:has(.lu-avatar) { padding-right: 10px; }
    #laravelusers [data-lu-table] th, #laravelusers [data-lu-table] td { vertical-align: middle; white-space: nowrap; }
    #laravelusers .lu-table-text { display: block; min-width: 0; }
    #laravelusers:not([data-lu-table-view="cards"]) .lu-table-text { max-width: {{ max(0, min(1000, (int) config('laravelusers.tableTextMaxWidth', 240))) }}px; overflow-x: auto; overflow-y: hidden; white-space: nowrap; scrollbar-width: thin; }
    #laravelusers[data-lu-table-view="cards"] .lu-table-text { text-align: right; white-space: normal; overflow-wrap: anywhere; }
    #laravelusers [data-lu-table] { font-size: .875rem; }
    #laravelusers [data-lu-table] .lu-online { padding: 1px 6px; font-size: .6875rem; line-height: 1.3; }
    #laravelusers [data-lu-table] th { padding: 8px 10px; }
    #laravelusers [data-lu-table] tbody td { padding: 6px 10px; background: transparent; }
    #laravelusers [data-lu-table] tbody tr { background: var(--lu-bg, #fff); }
    #laravelusers [data-lu-table] tbody tr:nth-of-type(odd) { background: var(--lu-table-stripe, #fcfcfd); }
    #laravelusers[data-lu-theme="dark"] { --lu-table-stripe: #1f293b; }
    #laravelusers[data-lu-table-buttons-icon-only="true"] [data-lu-table] .btn, #laravelusers[data-lu-table-buttons-icon-only="true"] [data-lu-table] .lu-button { display: inline-flex; align-items: center; justify-content: center; gap: 0; font-size: 0; line-height: 1; width: 34px; height: 34px; min-height: 34px; padding: 0; }
    #laravelusers[data-lu-table-buttons-icon-only="true"] [data-lu-table] .btn span { display: none !important; }
    #laravelusers[data-lu-table-buttons-icon-only="true"] [data-lu-table] .btn i { font-size: 16px; }
    #laravelusers #confirmDelete .modal-header, #laravelusers .lu-modal[data-lu-delete="true"] .lu-card-heading { background: #b42332; color: #fff; }
    #laravelusers #confirmDelete .close, #laravelusers .lu-modal[data-lu-delete="true"] .lu-card-heading button { color: #fff; }
    #laravelusers #confirmDelete #confirm, #laravelusers .lu-modal[data-lu-delete="true"] #lu-confirm-submit { background: #b42332; color: #fff; border-color: #b42332; }
    #laravelusers .lu-modal[data-lu-delete="true"] .lu-card-heading button { background: transparent; border-color: #ffffff66; }
    #laravelusers .lu-login-details { display: block; max-width: 190px; font-size: .6rem; line-height: 1.4; }
    #laravelusers .lu-login-details > span { display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    #laravelusers [data-lu-table] .lu-actions { display: flex; align-items: center; justify-content: flex-end; flex-wrap: nowrap; gap: 6px; }
    #laravelusers [data-lu-table] .lu-actions > form { display: flex; margin: 0; }
    #laravelusers [data-lu-table] .lu-actions .lu-button { min-width: 64px; }
    #laravelusers[data-lu-table-buttons-icon-only="true"] [data-lu-table] .lu-actions .lu-button { min-width: 34px; }
    #laravelusers .lu-card-heading .lu-button { min-height: 34px; padding: 6px 12px; border-radius: 5px; }
    #laravelusers .lu-date { display: inline-block; font-size: .7rem; line-height: 1.4; white-space: nowrap; }
    #laravelusers .lu-select-all-label { display: inline-flex; align-items: center; gap: 6px; margin: 0; }
    #laravelusers #lu-bulk { gap: 12px; flex-wrap: wrap; padding-top: 8px; padding-bottom: 8px; }
    #laravelusers #lu-bulk select { width: auto; min-width: 160px; padding-right: 36px; appearance: none; background-image: linear-gradient(45deg, transparent 50%, currentColor 50%), linear-gradient(135deg, currentColor 50%, transparent 50%); background-position: right 18px center, right 12px center; background-size: 6px 6px; background-repeat: no-repeat; }
    #laravelusers [data-lu-selected-count] { white-space: nowrap; }
    #laravelusers .lu-search { padding-bottom: 8px; }
    #laravelusers .lu-columns { position: relative; display: block; width: fit-content; max-width: calc(100% - 40px); margin: 8px 20px 24px; }
    #laravelusers .lu-columns summary { display: flex; align-items: center; gap: 8px; list-style: none; cursor: pointer; padding: 6px 12px; border: 1px solid var(--lu-border, #ced4da); border-radius: 5px; }
    #laravelusers .lu-columns summary::-webkit-details-marker { display: none; }
    #laravelusers .lu-columns summary::before { content: ''; flex-shrink: 0; border-top: 4px solid transparent; border-bottom: 4px solid transparent; border-left: 5px solid currentColor; }
    #laravelusers .lu-columns[open] summary::before { transform: rotate(90deg); }
    #laravelusers .lu-column-options { position: absolute; left: 0; z-index: 10; width: max-content; min-width: 180px; padding: 12px; border: 1px solid var(--lu-border, #ced4da); border-radius: 5px; background: var(--lu-bg, #fff); box-shadow: 0 6px 20px #0002; }
    #laravelusers .lu-column-options label { display: block; margin: 4px 0; white-space: nowrap; }
    #laravelusers .lu-column-options input { margin-right: 6px; }
    #laravelusers .lu-column-filter { font-size: .7rem; }
    #laravelusers .lu-mobile-sort, #laravelusers .lu-mobile-filters, #laravelusers .lu-mobile-only { display: none; }
    #laravelusers .lu-button:disabled { opacity: .55; cursor: not-allowed; }
    #laravelusers button:disabled { cursor: not-allowed; pointer-events: auto; }
    #laravelusers [hidden] { display: none !important; }
    @media (min-width: 641px) {
        #laravelusers [data-lu-table] td:has(.lu-avatar), #laravelusers [data-lu-table] td:has([data-lu-select]) { width: 1%; padding-left: 4px; padding-right: 4px; }
        #laravelusers [data-lu-table] td:has(.lu-avatar), #laravelusers [data-lu-table] th[data-lu-avatar-label] { padding-left: 12px; }
    }
    @media (max-width: 1024px) {
        #laravelusers[data-lu-responsive-buttons="true"] [data-lu-table] .btn, #laravelusers[data-lu-responsive-buttons="true"] [data-lu-table] .lu-button { display: inline-flex; align-items: center; justify-content: center; gap: 0; width: 40px; min-width: 40px; height: 40px; min-height: 40px; padding: 0; font-size: 0; line-height: 1; }
        #laravelusers[data-lu-responsive-buttons="true"] [data-lu-table] .btn { width: 40px !important; }
        #laravelusers[data-lu-responsive-buttons="true"] [data-lu-table] .btn span { display: none !important; }
        #laravelusers[data-lu-responsive-buttons="true"] [data-lu-table] .btn i { font-size: 16px; }
    }
    #laravelusers .lu-table-toolbar > .lu-button { display: inline-flex; align-items: center; justify-content: center; gap: 6px; min-height: 34px; border: 1px solid var(--lu-border, #ced4da); border-radius: 5px; padding: 6px 10px; font-size: .75rem; font-weight: 600; line-height: 1.6; white-space: nowrap; flex-shrink: 0; cursor: pointer; background: var(--lu-bg, #fff); color: var(--lu-text, #172033); }
    #laravelusers .lu-table-toolbar [aria-pressed="true"] { background: var(--lu-accent, #286090); color: #fff; }
    #laravelusers[data-lu-theme="dark"] .lu-table-toolbar [aria-pressed="true"] { background: #94b9ff; color: #172033; }
    #laravelusers[data-lu-theme="dark"] [data-lu-table] a:not(.btn):not(.lu-button) { color: #94b9ff; }
    #laravelusers .lu-mobile-sort select { width: auto; min-width: 160px; padding-right: 36px; appearance: none; background-image: linear-gradient(45deg, transparent 50%, currentColor 50%), linear-gradient(135deg, currentColor 50%, transparent 50%); background-position: right 18px center, right 12px center; background-size: 6px 6px; background-repeat: no-repeat; }
    #laravelusers #search_users .input-group-append .btn { display: inline-flex; align-items: center; justify-content: center; min-width: 38px; }
    #laravelusers #search_users .input-group-append .btn:has(.sr-only) { width: 38px; padding: 0; }
    #laravelusers .lu-table-toolbar { display: flex; align-items: flex-start; gap: 6px; margin: 16px 20px 20px; }
    #laravelusers .lu-table-toolbar .lu-columns, #laravelusers .lu-table-toolbar .lu-mobile-filters { display: block; position: relative; width: auto; margin: 0; padding: 0; max-width: none; }
    #laravelusers[data-lu-table-view="table"] .lu-table-toolbar .lu-mobile-filters { display: none; }
    #laravelusers .lu-table-toolbar summary { display: inline-flex; align-items: center; justify-content: center; gap: 7px; min-height: 34px; padding: 6px 10px; border: 1px solid var(--lu-border, #ced4da); border-radius: 5px; white-space: nowrap; padding-right: 14px; color: var(--lu-text, #172033); background: var(--lu-bg, #fff); font-size: .75rem; line-height: 1.6; cursor: pointer; list-style: none; }
    #laravelusers .lu-table-toolbar summary::-webkit-details-marker { display: none; }
    #laravelusers .lu-table-toolbar summary::before { content: none; }
    #laravelusers .lu-table-toolbar summary::after { content: ''; margin-left: 3px; border-top: 5px solid currentColor; border-left: 4px solid transparent; border-right: 4px solid transparent; }
    #laravelusers .lu-table-toolbar .lu-mobile-filter-fields { position: absolute; right: 0; z-index: 10; width: min(360px, calc(100vw - 68px)); padding: 12px; border: 1px solid var(--lu-border, #ced4da); border-radius: 5px; background: var(--lu-bg, #fff); box-shadow: 0 6px 20px #0002; }
    #laravelusers .lu-table-toolbar .lu-mobile-sort { position: relative; display: inline-flex; gap: 0; align-items: stretch; min-height: 34px; padding: 0; border: 1px solid var(--lu-border, #ced4da); border-radius: 5px; }
    #laravelusers .lu-table-toolbar .lu-mobile-sort label { display: inline-flex; align-items: center; gap: 7px; margin: 0; padding: 6px 8px; border-right: 1px solid var(--lu-border, #ced4da); border-radius: 4px 0 0 4px; background: var(--lu-soft, #f4f6fa); font-size: .75rem; line-height: 1.6; font-weight: 500; white-space: nowrap; }
    #laravelusers .lu-table-toolbar .lu-sort-value { display: flex; align-items: center; padding: 6px 28px 6px 8px; border-radius: 0 4px 4px 0; background: var(--lu-bg, #fff); font-size: .75rem; line-height: 1.6; white-space: nowrap; }
    #laravelusers .lu-table-toolbar .lu-mobile-sort::after { content: ''; position: absolute; right: 10px; top: calc(50% - 2px); border-top: 5px solid currentColor; border-left: 4px solid transparent; border-right: 4px solid transparent; pointer-events: none; }
    #laravelusers .lu-table-toolbar .lu-mobile-sort select { position: absolute; inset: 0; z-index: 1; width: 100%; min-width: 0; height: 100%; opacity: 0; cursor: pointer; }
    #laravelusers .lu-table-toolbar .lu-mobile-sort:has(select:focus-visible) { outline: 3px solid var(--lu-accent, #2456c2); outline-offset: 3px; }
    #laravelusers[data-lu-table-view="cards"] [data-lu-table] a:not(.btn):not(.lu-button) { color: var(--lu-accent, #2456c2); }
    #laravelusers[data-lu-table-view="cards"] [data-lu-table] .badge-success { background: #087748; color: #fff; }
    #laravelusers .lu-table-toolbar .lu-icon { width: 16px; height: 16px; flex-shrink: 0; }
    #laravelusers .lu-card-label { display: none; }
    #laravelusers .lu-login-details .lu-icon { width: 12px; height: 12px; margin-right: 5px; vertical-align: middle; }
    #laravelusers[data-lu-table-view="cards"] .lu-scroll, #laravelusers[data-lu-table-view="cards"] .table-responsive { overflow: visible; }
    #laravelusers[data-lu-table-view="cards"] [data-lu-table], #laravelusers[data-lu-table-view="cards"] [data-lu-table] tbody { display: block; width: 100%; }
    #laravelusers[data-lu-table-view="cards"] [data-lu-table] tbody { display: grid; grid-template-columns: repeat({{ max(1, min(12, (int) config('laravelusers.cardColumns.mobile', 1))) }}, minmax(0, 1fr)); gap: 16px; padding: 16px; }
    #laravelusers[data-lu-table-view="cards"] [data-lu-table] thead { display: block; }
    #laravelusers[data-lu-table-view="cards"] [data-lu-table] thead tr:first-child { display: none; }
    #laravelusers[data-lu-table-view="cards"] [data-lu-table] thead tr:not(:first-child) { display: none; }
    #laravelusers[data-lu-table-view="cards"] [data-lu-table] thead td:empty { display: none; }
    #laravelusers[data-lu-table-view="cards"] [data-lu-table] tbody td.hidden-xs:not([hidden]), #laravelusers[data-lu-table-view="cards"] [data-lu-table] tbody td.hidden-sm:not([hidden]) { display: flex !important; }
    #laravelusers[data-lu-table-view="cards"] [data-lu-table] tbody td:has(.btn) { display: inline-flex; width: auto; }
    #laravelusers[data-lu-table-view="cards"] [data-lu-table] thead td { padding: 0; border: 0; }
    #laravelusers[data-lu-table-view="cards"] [data-lu-table] tbody tr { position: relative; display: block; margin: 0; padding: 16px 20px 76px; box-shadow: 0 2px 6px #00000006; border: 1px solid var(--lu-border, #ced4da); border-radius: 8px; background: var(--lu-bg, #fff); }
    #laravelusers[data-lu-theme="dark"][data-lu-table-view="cards"] [data-lu-table] tbody tr { background: #253145; }
    #laravelusers[data-lu-table-view="cards"] [data-lu-table] a[href$="/edit"], #laravelusers .lu-profile-actions a[href$="/edit"] { background: #d6b96a; color: #332b18; border-color: #c7aa60; }
    #laravelusers[data-lu-table-view="cards"] [data-lu-table] a[href$="/edit"]:hover, #laravelusers .lu-profile-actions a[href$="/edit"]:hover { background: #c7aa60; color: #332b18; }
    #laravelusers[data-lu-table-view="cards"] [data-lu-table] tbody td { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 9px 0; border: 0; border-bottom: 1px solid var(--lu-border, #e8eef5); background: none; min-width: 0; white-space: normal; overflow-wrap: anywhere; }
    #laravelusers[data-lu-table-view="cards"] [data-lu-table] tbody td::before { content: none; font-size: .8rem; color: var(--lu-muted, #526077); font-weight: 600; flex-shrink: 0; }
    #laravelusers[data-lu-table-view="cards"] [data-lu-table] td.lu-card-last-field { border-bottom: 0; }
    #laravelusers[data-lu-table-view="cards"] .lu-card-label { display: inline-flex; align-items: center; gap: 7px; font-size: .75rem; color: var(--lu-muted, #526077); font-weight: 500; white-space: nowrap; flex-shrink: 0; }
    #laravelusers[data-lu-table-view="cards"] .lu-card-label::after { content: attr(data-lu-label); }
    #laravelusers[data-lu-table-view="cards"] .lu-card-label .lu-icon { width: 14px; height: 14px; }
    #laravelusers[data-lu-table-view="cards"] [data-lu-table] tbody td:has(.lu-avatar) { width: auto; justify-content: center; margin: -16px -20px 16px; padding: 24px; border: 0; border-radius: 7px 7px 0 0; background: var(--lu-soft, #f8fafc); }
    #laravelusers[data-lu-table-view="cards"] [data-lu-table] tbody td:has(.lu-avatar) { background-color: var(--lu-profile-color); background-image: var(--lu-profile-image, radial-gradient(ellipse at var(--lu-profile-highlight, 50% 40%), var(--lu-profile-glow), transparent 75%), linear-gradient(145deg, transparent, var(--lu-profile-shade))); }
    #laravelusers[data-lu-theme="dark"][data-lu-table-view="cards"] [data-lu-table] tbody td:has(.lu-avatar) { background-color: var(--lu-profile-dark-color); background-image: var(--lu-profile-dark-image, radial-gradient(ellipse at var(--lu-profile-highlight, 50% 40%), var(--lu-profile-dark-glow), transparent 75%), linear-gradient(145deg, transparent, var(--lu-profile-dark-shade))); }
    #laravelusers[data-lu-table-view="cards"] [data-lu-table] tbody tr:has([data-lu-select]):not(:has(td:not([hidden]) .lu-avatar)) { padding-top: 40px; }
    #laravelusers[data-lu-table-view="cards"] [data-lu-table] .lu-avatar { width: 72px !important; height: 72px !important; font-size: 24px; }
    #laravelusers[data-lu-table-view="cards"] [data-lu-table] tbody td[data-lu-selection-cell], #laravelusers[data-lu-table-view="cards"] [data-lu-table] tbody td:has([data-lu-select]) { position: absolute; right: 20px; top: 16px; padding: 0; border: 0; }
    #laravelusers[data-lu-table-view="cards"] [data-lu-table] tbody td:has(.lu-actions), #laravelusers[data-lu-table-view="cards"] [data-lu-table] tbody td:has(.btn) { position: absolute; bottom: 16px; border: 0; padding: 0; }
    #laravelusers[data-lu-table-view="cards"] [data-lu-table] tbody td:has(.lu-actions)::before, #laravelusers[data-lu-table-view="cards"] [data-lu-table] tbody td:has(.btn)::before { content: none; }
    #laravelusers[data-lu-table-view="cards"] [data-lu-table] tbody td:has(.lu-actions) { left: 20px; right: 20px; width: auto; }
    #laravelusers[data-lu-table-view="cards"] [data-lu-table] .lu-actions { display: grid; grid-auto-flow: column; grid-auto-columns: 1fr; width: 100%; justify-content: center; }
    #laravelusers[data-lu-table-view="cards"] [data-lu-table] .lu-actions > a, #laravelusers[data-lu-table-view="cards"] [data-lu-table] .lu-actions > form { flex: 1; min-width: 0; }
    #laravelusers[data-lu-table-view="cards"] [data-lu-table] .lu-actions .lu-button { width: 100%; min-width: 0; white-space: nowrap; overflow-wrap: normal; }
    #laravelusers[data-lu-table-view="cards"] [data-lu-table] .lu-actions .btn { display: inline-flex; align-items: center; justify-content: center; gap: 4px; width: 100%; min-height: 34px; white-space: nowrap; overflow-wrap: normal; }
    #laravelusers[data-lu-table-view="cards"][data-lu-table-buttons-icon-only="true"] [data-lu-table] .lu-actions { display: flex; }
    #laravelusers[data-lu-table-view="cards"][data-lu-table-buttons-icon-only="true"] [data-lu-table] .lu-actions > a, #laravelusers[data-lu-table-view="cards"][data-lu-table-buttons-icon-only="true"] [data-lu-table] .lu-actions > form { flex: 0 0 auto; }
    #laravelusers[data-lu-table-view="cards"][data-lu-table-buttons-icon-only="true"] [data-lu-table] .lu-actions .lu-button, #laravelusers[data-lu-table-view="cards"][data-lu-table-buttons-icon-only="true"] [data-lu-table] .lu-actions .btn { width: 34px; }
    #laravelusers[data-lu-table-view="cards"] [data-lu-table] td[data-lu-empty-action] { display: none; }
    #laravelusers[data-lu-table-view="cards"] [data-lu-table] tbody td:has(.btn) { width: calc((100% - 52px) / 3); }
    #laravelusers[data-lu-table-view="cards"] [data-lu-table] tbody td:has(.btn):nth-last-child(3) { left: 20px; }
    #laravelusers[data-lu-table-view="cards"] [data-lu-table] tbody td:has(.btn):nth-last-child(2) { left: 50%; transform: translateX(-50%); }
    #laravelusers[data-lu-table-view="cards"] [data-lu-table] tbody td:has(.btn):last-child { right: 20px; }
    #laravelusers[data-lu-table-view="cards"] [data-lu-table] tbody td:has(.lu-actions):has(.btn) { left: 20px; right: 20px; width: auto; transform: none; }
    #laravelusers[data-lu-table-view="cards"] [data-lu-table] caption { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0,0,0,0); }
    #laravelusers[data-lu-table-view="cards"] .lu-mobile-only { display: flex; gap: 8px; align-items: center; margin: 12px 20px; font-size: .8rem; }
        #laravelusers .lu-mobile-filter-fields { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; padding-top: 12px; }
        #laravelusers[data-lu-responsive-buttons="true"] [data-lu-table] .lu-actions .lu-button { min-width: 40px; }
    #laravelusers[data-lu-table-view="cards"] .lu-login-details { max-width: none; overflow: visible; white-space: normal; text-align: right; }
    #laravelusers[data-lu-table-view="cards"] .lu-date { text-align: right; }
    @media (max-width: 1024px) {
        #laravelusers[data-lu-responsive-buttons="true"] .lu-table-toolbar { display: grid; grid-auto-flow: column; grid-auto-columns: 1fr; gap: 8px; }
        #laravelusers[data-lu-responsive-buttons="true"] .lu-table-toolbar > .lu-button, #laravelusers[data-lu-responsive-buttons="true"] .lu-table-toolbar summary { width: 100%; height: 40px; min-height: 40px; padding: 0; gap: 0; font-size: 0; line-height: 1; }
        #laravelusers[data-lu-responsive-buttons="true"] .lu-table-toolbar .lu-mobile-sort { height: 40px; justify-content: center; font-size: 0; }
        #laravelusers[data-lu-responsive-buttons="true"] .lu-table-toolbar .lu-mobile-sort label { border: 0; padding: 0; background: var(--lu-bg, #fff); font-size: 0; gap: 0; }
        #laravelusers[data-lu-responsive-buttons="true"] .lu-table-toolbar .lu-sort-value, #laravelusers[data-lu-responsive-buttons="true"] .lu-table-toolbar .lu-mobile-sort::after { display: none; }
        #laravelusers[data-lu-responsive-buttons="true"] .lu-table-toolbar summary::after { content: none; }
        #laravelusers .lu-table-toolbar .lu-column-options { left: auto; right: 0; }
    }
    @media (max-width: 640px) {
        #laravelusers[data-lu-responsive-buttons="true"] .lu-button, #laravelusers[data-lu-responsive-buttons="true"] .lu-table-toolbar > .lu-button { gap: 0; justify-content: center; width: 40px; height: 40px; min-height: 40px; padding: 0; font-size: 0; line-height: 1; }
        #laravelusers[data-lu-responsive-buttons="true"] .lu-table-toolbar > .lu-button { width: 100%; }
    }
    @media (min-width: 768px) { #laravelusers[data-lu-table-view="cards"] [data-lu-table] tbody { grid-template-columns: repeat({{ max(1, min(12, (int) config('laravelusers.cardColumns.tablet', 2))) }}, minmax(0, 1fr)); } }
    @media (min-width: 992px) { #laravelusers[data-lu-table-view="cards"] [data-lu-table] tbody { grid-template-columns: repeat({{ max(1, min(12, (int) config('laravelusers.cardColumns.desktop', 3))) }}, minmax(0, 1fr)); } }
    @media (min-width: 1400px) { #laravelusers[data-lu-table-view="cards"] [data-lu-table] tbody { grid-template-columns: repeat({{ max(1, min(12, (int) config('laravelusers.cardColumns.wide', 4))) }}, minmax(0, 1fr)); } }
    @media (min-width: 1025px) {
        #laravelusers[data-lu-table-view="cards"][data-lu-table-buttons-icon-only="false"] [data-lu-table] .btn { font-size: .75rem; padding: 6px; white-space: nowrap; overflow-wrap: normal; }
        #laravelusers[data-lu-table-view="cards"][data-lu-table-buttons-icon-only="false"] [data-lu-table] .lu-actions .lu-button { padding: 6px 4px; gap: 4px; font-size: .75rem; }
        #laravelusers[data-lu-table-view="cards"][data-lu-table-buttons-icon-only="false"] [data-lu-table] .lu-actions .lu-icon { width: 14px; height: 14px; }
    }
    @media (max-width: 1024px) {
        #laravelusers[data-lu-table-view="cards"][data-lu-responsive-buttons="true"] [data-lu-table] .lu-actions { display: flex; }
        #laravelusers[data-lu-table-view="cards"][data-lu-responsive-buttons="true"] [data-lu-table] .lu-actions > a, #laravelusers[data-lu-table-view="cards"][data-lu-responsive-buttons="true"] [data-lu-table] .lu-actions > form { flex: 0 0 auto; }
        #laravelusers[data-lu-table-view="cards"][data-lu-responsive-buttons="true"] [data-lu-table] .lu-actions .lu-button { width: 40px; }
    }
    #laravelusers .lu-table-toolbar > button:disabled { cursor: not-allowed; pointer-events: auto; }
</style>
