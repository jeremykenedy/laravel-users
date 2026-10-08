<style>
    #laravelusers .lu-date { display: inline-block; max-width: 100px; font-size: .8rem; line-height: 1.4; }
    #laravelusers .lu-columns { position: relative; display: inline-block; margin: 12px 20px; }
    #laravelusers .lu-columns summary { cursor: pointer; padding: 6px 12px; border: 1px solid var(--lu-border, #ced4da); border-radius: 5px; }
    #laravelusers .lu-column-options { position: absolute; left: 0; z-index: 10; min-width: 180px; padding: 12px; border: 1px solid var(--lu-border, #ced4da); border-radius: 5px; background: var(--lu-bg, #fff); box-shadow: 0 6px 20px #0002; }
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
        #laravelusers[data-lu-responsive-table="true"] [data-lu-table] tbody td { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 6px 0; border: 0; background: none; min-width: 0; overflow-wrap: anywhere; }
        #laravelusers[data-lu-responsive-table="true"] [data-lu-table] tbody td::before { content: attr(data-lu-label); font-size: .8rem; color: var(--lu-muted, #526077); font-weight: 600; flex-shrink: 0; }
        #laravelusers[data-lu-responsive-table="true"] [data-lu-table] tbody td:has(.lu-actions)::before, #laravelusers[data-lu-responsive-table="true"] [data-lu-table] tbody td:has(.btn)::before { content: none; }
        #laravelusers[data-lu-responsive-table="true"] [data-lu-table] tbody td:has(.lu-actions) { justify-content: flex-end; }
        #laravelusers[data-lu-responsive-table="true"] [data-lu-table] caption { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0,0,0,0); }
        #laravelusers[data-lu-responsive-table="true"] .lu-mobile-only { display: flex; gap: 8px; align-items: center; flex-basis: 100%; font-size: .8rem; }
        #laravelusers[data-lu-responsive-table="true"] .lu-mobile-filters { display: block; padding: 8px 20px; }
        #laravelusers .lu-mobile-filter-fields { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; padding-top: 12px; }
        #laravelusers[data-lu-responsive-table="true"] .lu-mobile-sort { display: flex; gap: 8px; align-items: center; padding: 12px 20px; }
        #laravelusers[data-lu-responsive-table="true"] .lu-date { white-space: normal; text-align: right; }
    }
</style>
