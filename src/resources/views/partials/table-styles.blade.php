<style>
    #laravelusers [data-lu-table] th, #laravelusers [data-lu-table] td { vertical-align: middle; white-space: nowrap; }
    #laravelusers [data-lu-table] { font-size: .875rem; }
    #laravelusers [data-lu-table] th { padding: 8px 10px; }
    #laravelusers [data-lu-table] tbody td { padding: 6px 10px; background: transparent; }
    #laravelusers [data-lu-table] tbody tr { background: var(--lu-bg, #fff); }
    #laravelusers [data-lu-table] tbody tr:nth-of-type(odd) { background: var(--lu-table-stripe, #fcfcfd); }
    #laravelusers[data-lu-theme="dark"] { --lu-table-stripe: #1f293b; }
    #laravelusers[data-lu-table-buttons-icon-only="true"] [data-lu-table] .btn, #laravelusers[data-lu-table-buttons-icon-only="true"] [data-lu-table] .lu-button { font-size: 0; width: 34px; min-height: 34px; padding: 6px; }
    #laravelusers[data-lu-table-buttons-icon-only="true"] [data-lu-table] .btn span { display: none !important; }
    #laravelusers[data-lu-table-buttons-icon-only="true"] [data-lu-table] .btn i { font-size: 16px; }
    #laravelusers #confirmDelete .modal-header, #laravelusers .lu-modal[data-lu-delete="true"] .lu-card-heading { background: #b42332; color: #fff; }
    #laravelusers #confirmDelete .close, #laravelusers .lu-modal[data-lu-delete="true"] .lu-card-heading button { color: #fff; }
    #laravelusers #confirmDelete #confirm, #laravelusers .lu-modal[data-lu-delete="true"] #lu-confirm-submit { background: #b42332; color: #fff; border-color: #b42332; }
    #laravelusers .lu-modal[data-lu-delete="true"] .lu-card-heading button { background: transparent; border-color: #ffffff66; }
    #laravelusers .lu-login-details { display: block; max-width: 190px; font-size: .6rem; line-height: 1.4; }
    #laravelusers .lu-login-details > span { display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    #laravelusers [data-lu-table] .lu-actions { justify-content: flex-end; }
    #laravelusers [data-lu-table] .lu-actions .lu-button { min-width: 64px; }
    #laravelusers[data-lu-table-buttons-icon-only="true"] [data-lu-table] .lu-actions .lu-button { min-width: 34px; }
    #laravelusers .lu-card-heading .lu-button { min-height: 34px; padding: 6px 12px; border-radius: 5px; }
    #laravelusers .lu-date { display: inline-block; font-size: .7rem; line-height: 1.4; white-space: nowrap; }
    #laravelusers .lu-select-all-label { display: inline-flex; align-items: center; gap: 6px; margin: 0; }
    #laravelusers #lu-bulk { gap: 12px; flex-wrap: wrap; }
    #laravelusers #lu-bulk select { width: auto; min-width: 160px; padding-right: 36px; appearance: none; background-image: linear-gradient(45deg, transparent 50%, currentColor 50%), linear-gradient(135deg, currentColor 50%, transparent 50%); background-position: right 18px center, right 12px center; background-size: 6px 6px; background-repeat: no-repeat; }
    #laravelusers [data-lu-selected-count] { white-space: nowrap; }
    #laravelusers .lu-columns { position: relative; display: block; width: fit-content; max-width: calc(100% - 40px); margin: 12px 20px 24px; }
    #laravelusers .lu-search ~ .lu-columns { margin-top: -12px; }
    #laravelusers .lu-columns summary { display: flex; align-items: center; gap: 8px; list-style: none; cursor: pointer; padding: 6px 12px; border: 1px solid var(--lu-border, #ced4da); border-radius: 5px; }
    #laravelusers .lu-columns summary::-webkit-details-marker { display: none; }
    #laravelusers .lu-columns summary::before { content: ''; flex-shrink: 0; border-top: 4px solid transparent; border-bottom: 4px solid transparent; border-left: 5px solid currentColor; }
    #laravelusers .lu-columns[open] summary::before { transform: rotate(90deg); }
    #laravelusers .lu-column-options { position: absolute; left: 0; z-index: 10; width: max-content; min-width: 180px; padding: 12px; border: 1px solid var(--lu-border, #ced4da); border-radius: 5px; background: var(--lu-bg, #fff); box-shadow: 0 6px 20px #0002; }
    #laravelusers .lu-column-options label { display: block; margin: 4px 0; white-space: nowrap; }
    #laravelusers .lu-column-options input { margin-right: 6px; }
    #laravelusers .lu-mobile-sort, #laravelusers .lu-mobile-filters, #laravelusers .lu-mobile-only { display: none; }
    #laravelusers .lu-button:disabled { opacity: .55; cursor: default; }
    #laravelusers [hidden] { display: none !important; }
    @media (max-width: 640px) {
        #laravelusers[data-lu-responsive-table="true"] .lu-scroll, #laravelusers[data-lu-responsive-table="true"] .table-responsive { overflow: visible; }
        #laravelusers[data-lu-responsive-table="true"] [data-lu-table], #laravelusers[data-lu-responsive-table="true"] [data-lu-table] tbody { display: block; width: 100%; }
        #laravelusers[data-lu-responsive-table="true"] [data-lu-table] thead { display: block; }
        #laravelusers[data-lu-responsive-table="true"] [data-lu-table] thead tr:first-child { display: none; }
        #laravelusers[data-lu-responsive-table="true"] [data-lu-table] thead tr:not(:first-child) { display: none; }
        #laravelusers[data-lu-responsive-table="true"] [data-lu-table] thead td:empty { display: none; }
        #laravelusers[data-lu-responsive-table="true"] [data-lu-table] tbody td.hidden-xs:not([hidden]), #laravelusers[data-lu-responsive-table="true"] [data-lu-table] tbody td.hidden-sm:not([hidden]) { display: flex !important; }
        #laravelusers[data-lu-responsive-table="true"] [data-lu-table] tbody td:has(.btn) { display: inline-flex; width: auto; }
        #laravelusers[data-lu-responsive-table="true"] [data-lu-table] thead td { padding: 0; border: 0; }
        #laravelusers[data-lu-responsive-table="true"] [data-lu-table] tbody tr { display: block; margin: 12px; padding: 12px 16px; border: 1px solid var(--lu-border, #ced4da); border-radius: 8px; background: var(--lu-bg, #fff); }
        #laravelusers[data-lu-responsive-table="true"] [data-lu-table] tbody td { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 6px 0; border: 0; background: none; min-width: 0; white-space: normal; overflow-wrap: anywhere; }
        #laravelusers[data-lu-responsive-table="true"] [data-lu-table] tbody td::before { content: attr(data-lu-label); font-size: .8rem; color: var(--lu-muted, #526077); font-weight: 600; flex-shrink: 0; }
        #laravelusers[data-lu-responsive-table="true"] [data-lu-table] tbody td:has(.lu-actions)::before, #laravelusers[data-lu-responsive-table="true"] [data-lu-table] tbody td:has(.btn)::before { content: none; }
        #laravelusers[data-lu-responsive-table="true"] [data-lu-table] tbody td:has(.lu-actions) { justify-content: flex-end; }
        #laravelusers[data-lu-responsive-table="true"] [data-lu-table] caption { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0,0,0,0); }
        #laravelusers[data-lu-responsive-table="true"] .lu-mobile-only { display: flex; gap: 8px; align-items: center; margin: 12px 20px; font-size: .8rem; }
        #laravelusers[data-lu-responsive-table="true"] .lu-mobile-filters { display: block; padding: 8px 20px; }
        #laravelusers .lu-mobile-filter-fields { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; padding-top: 12px; }
        #laravelusers[data-lu-responsive-table="true"] .lu-mobile-sort { display: flex; gap: 8px; align-items: center; padding: 12px 20px; }
        #laravelusers[data-lu-responsive-buttons="true"] [data-lu-table] .lu-actions .lu-button { min-width: 40px; }
        #laravelusers[data-lu-responsive-table="true"] .lu-login-details { max-width: none; overflow: visible; white-space: normal; text-align: right; }
        #laravelusers[data-lu-responsive-table="true"] .lu-date { text-align: right; }
    }
</style>
