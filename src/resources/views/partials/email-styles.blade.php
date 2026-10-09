<style>
    #laravelusers .lu-email-dialog:not([open]) { display: none; }
    #laravelusers [data-lu-email-frame] { display: block; width: 100%; height: 420px; border: 1px solid var(--lu-border, #ced4da); border-radius: 5px; background: #fff; }
    #laravelusers [data-lu-email-preview-recipient] { margin: 12px 0; font-size: .75rem; }
    #laravelusers [data-lu-account-options] legend { font-size: .875rem; font-weight: 600; margin-bottom: 10px; }

    #laravelusers .lu-legacy-edit-controls { display: flex; align-items: center; gap: 6px; }
    #laravelusers .lu-legacy-edit-controls > a { flex: 1; min-width: 0; }
    #laravelusers .lu-legacy-edit-controls .lu-email-toggle { min-height: 31px; }

    #laravelusers .lu-email-controls { display: flex; align-items: center; justify-content: center; gap: 8px; padding: 16px 24px; border-top: 1px solid var(--lu-border, #dee2e6); }
    #laravelusers .lu-email-controls .lu-button { flex: 1; white-space: nowrap; font-size: .75rem; }
    #laravelusers .lu-email-menu { position: relative; min-width: 0; }
    #laravelusers .lu-email-toggle { display: flex; align-items: center; justify-content: center; gap: 4px; padding: 6px 8px; min-height: 34px; border: 1px solid var(--lu-border, #ced4da); border-radius: 5px; background: var(--lu-bg, #fff); color: var(--lu-text, #172033); font-size: .75rem; line-height: 1.3; cursor: pointer; list-style: none; white-space: nowrap; }
    #laravelusers .lu-email-toggle::-webkit-details-marker { display: none; }
    #laravelusers .lu-email-options { position: absolute; z-index: 15; bottom: calc(100% + 8px); right: 0; padding: 6px; width: max-content; border: 1px solid var(--lu-border, #ced4da); border-radius: 6px; background: var(--lu-bg, #fff); color: var(--lu-text, #172033); box-shadow: 0 4px 16px #0002; }
    #laravelusers .lu-email-options[popover] { position: fixed; inset: auto; margin: 0; max-width: calc(100vw - 24px); max-height: calc(100vh - 24px); overflow: auto; }
    #laravelusers .lu-email-choice { display: flex; align-items: center; gap: 8px; width: 100%; padding: 10px; border: 0; border-radius: 4px; background: transparent; color: inherit; font-size: .8rem; text-align: left; white-space: nowrap; cursor: pointer; }
    #laravelusers .lu-email-choice:hover { background: var(--lu-soft, #f1f4f9); }
    #laravelusers .lu-email-choice:disabled { opacity: .5; cursor: not-allowed; }
    #laravelusers .lu-email-dialog { width: min(560px, calc(100% - 32px)); max-height: calc(100% - 32px); padding: 0; border: 1px solid var(--lu-border, #ced4da); border-radius: 8px; background: var(--lu-bg, #fff); color: var(--lu-text, #172033); }
    #laravelusers #lu-email-form, #laravelusers #lu-package-form { display: flex; flex-direction: column; max-height: calc(100vh - 34px); margin: 0; }
    #laravelusers .lu-email-dialog::backdrop { background: #11182780; }
    #laravelusers .lu-email-heading, #laravelusers .lu-email-footer { display: flex; align-items: center; justify-content: space-between; flex-shrink: 0; gap: 12px; padding: 16px 20px; background: var(--lu-soft, #f4f6fa); }
    #laravelusers .lu-email-heading { border-bottom: 1px solid var(--lu-border, #ced4da); }
    #laravelusers .lu-email-heading h2 { display: flex; align-items: center; gap: 8px; margin: 0; font-size: 1.1rem; }
    #laravelusers .lu-email-close { display: inline-flex; align-items: center; justify-content: center; border: 0; padding: 6px; background: transparent; color: inherit; cursor: pointer; }
    #laravelusers .lu-email-body { min-height: 0; overflow-y: auto; padding: 20px; }
    #laravelusers .lu-reset-duration-row { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 8px; }
    #laravelusers .lu-email-recipients { display: flex; flex-wrap: wrap; align-content: flex-start; gap: 6px; height: {{ max(48, min(320, (int) config('laravelusers.emails.recipient_height', 96))) }}px; overflow-y: auto; margin-bottom: 16px; padding: 8px; border: 1px solid var(--lu-border, #ced4da); border-radius: 5px; }
    #laravelusers .lu-email-recipients[hidden] { display: none; }
    #laravelusers .lu-recipient-chip { display: inline-flex; align-items: center; gap: 4px; max-width: 100%; padding: 2px 4px 2px 10px; border-radius: 16px; background: var(--lu-soft, #f4f6fa); color: var(--lu-text, #172033); font-size: .75rem; }
    #laravelusers .lu-recipient-chip > span { min-width: 0; overflow-wrap: anywhere; }
    #laravelusers .lu-recipient-chip button { display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; width: 24px; height: 24px; padding: 0; border: 0; border-radius: 50%; background: transparent; color: inherit; font-size: 18px; line-height: 1; cursor: pointer; }
    #laravelusers .lu-recipient-chip button:hover { background: var(--lu-bg, #fff); }
    #laravelusers .lu-email-dialog label { display: block; margin-bottom: 6px; font-size: .8rem; }
    #laravelusers .lu-email-input { display: block; width: 100%; margin-bottom: 12px; padding: 8px 10px; border: 1px solid var(--lu-border, #ced4da); border-radius: 5px; background: var(--lu-bg, #fff); color: inherit; font: inherit; }
    #laravelusers textarea.lu-email-input { min-height: 130px; resize: vertical; }
    #laravelusers .lu-email-section { padding: 12px 0; }
    #laravelusers .lu-email-dialog .lu-email-check { display: flex; align-items: center; gap: 8px; }
    #laravelusers .lu-email-footer { justify-content: flex-end; border-top: 1px solid var(--lu-border, #ced4da); }
    #laravelusers .lu-email-dialog .lu-button, #laravelusers .lu-email-controls .lu-button { display: inline-flex; align-items: center; justify-content: center; gap: 8px; min-height: 38px; padding: 8px 12px; border: 1px solid var(--lu-border, #ced4da); border-radius: 5px; background: #286090; color: #fff; cursor: pointer; }
    #laravelusers .lu-email-dialog .lu-secondary, #laravelusers .lu-email-controls .lu-secondary { background: var(--lu-bg, #fff); color: var(--lu-text, #172033); }
    #laravelusers .lu-email-dialog .lu-button:disabled, #laravelusers .lu-email-controls .lu-button:disabled { opacity: .5; cursor: not-allowed; }
    #laravelusers .lu-password-status { display: flex; align-items: center; justify-content: space-between; font-size: .75rem; }
    #laravelusers .lu-password-confirmation-error[hidden] { display: block !important; visibility: hidden; }
    #laravelusers .lu-password-confirmation-error { margin: 6px 0 0; color: #b42332; font-size: .75rem; }
    #laravelusers[data-lu-theme="dark"] .lu-password-confirmation-error { color: #ffb4bc; }
    #laravelusers .lu-password-meter meter { display: block; width: 100%; height: 12px; margin: 4px 0; }
    #laravelusers .lu-password-meter ul { margin: 8px 0 0; padding-left: 18px; font-size: .75rem; color: var(--lu-muted, #526077); }
    #laravelusers .lu-password-meter [data-lu-met="true"] { color: var(--lu-text, #172033); }
    #laravelusers[data-lu-table-view="cards"] .lu-email-menu .lu-email-toggle { width: 100%; }
    #laravelusers[data-lu-table-buttons-icon-only="true"] [data-lu-table] .lu-email-toggle { width: 34px; height: 34px; padding: 0; gap: 0; font-size: 0; }
    #laravelusers[data-lu-table-buttons-icon-only="true"] [data-lu-table] .lu-email-toggle > span { display: none; }
    #laravelusers[data-lu-table-view="cards"] [data-lu-table] tbody tr { container-type: inline-size; }
    @container (max-width: 280px) {
        #laravelusers[data-lu-table-view="cards"][data-lu-responsive-buttons="true"] [data-lu-table] .lu-actions { display: flex; }
        #laravelusers[data-lu-table-view="cards"][data-lu-responsive-buttons="true"] [data-lu-table] .lu-actions > a, #laravelusers[data-lu-table-view="cards"][data-lu-responsive-buttons="true"] [data-lu-table] .lu-actions > form { flex: 0 0 auto; }
        #laravelusers[data-lu-table-view="cards"][data-lu-responsive-buttons="true"] [data-lu-table] .lu-actions .lu-button, #laravelusers[data-lu-table-view="cards"][data-lu-responsive-buttons="true"] [data-lu-table] .lu-email-toggle { width: 40px; height: 40px; min-width: 40px; min-height: 40px; padding: 0; gap: 0; font-size: 0; }
        #laravelusers[data-lu-table-view="cards"][data-lu-responsive-buttons="true"] [data-lu-table] .lu-email-toggle > span { display: none; }
    }
    @container (max-width: 600px) {
        #laravelusers[data-lu-table-view="cards"][data-lu-responsive-buttons="true"] [data-lu-view="deleted"] .lu-actions .btn i { font-size: 16px; }
        #laravelusers[data-lu-table-view="cards"][data-lu-responsive-buttons="true"] [data-lu-view="deleted"] .lu-actions { display: flex; }
        #laravelusers[data-lu-table-view="cards"][data-lu-responsive-buttons="true"] [data-lu-view="deleted"] .lu-actions > form, #laravelusers[data-lu-table-view="cards"][data-lu-responsive-buttons="true"] [data-lu-view="deleted"] .lu-actions > a { flex: 0 0 auto; }
        #laravelusers[data-lu-table-view="cards"][data-lu-responsive-buttons="true"] [data-lu-view="deleted"] .lu-actions .lu-button, #laravelusers[data-lu-table-view="cards"][data-lu-responsive-buttons="true"] [data-lu-view="deleted"] .lu-actions .btn, #laravelusers[data-lu-table-view="cards"][data-lu-responsive-buttons="true"] [data-lu-view="deleted"] .lu-email-toggle { width: 40px; height: 40px; min-width: 40px; min-height: 40px; padding: 0; gap: 0; font-size: 0; }
        #laravelusers[data-lu-table-view="cards"][data-lu-responsive-buttons="true"] [data-lu-view="deleted"] .lu-actions .btn > span, #laravelusers[data-lu-table-view="cards"][data-lu-responsive-buttons="true"] [data-lu-view="deleted"] .lu-email-toggle > span { display: none; }
    }
    @media (max-width: 1024px) {
        #laravelusers[data-lu-responsive-buttons="true"] [data-lu-table] .lu-email-toggle { width: 40px; height: 40px; padding: 0; gap: 0; font-size: 0; }
        #laravelusers[data-lu-responsive-buttons="true"] [data-lu-table] .lu-email-toggle > span { display: none; }
    }
    @media (max-width: 640px) {
        #laravelusers[data-lu-responsive-buttons="true"] .lu-email-controls .lu-button, #laravelusers[data-lu-responsive-buttons="true"] .lu-email-dialog .lu-button { flex: 0 0 auto; width: 40px; height: 40px; padding: 0; gap: 0; font-size: 0; }
        #laravelusers .lu-email-options { max-width: calc(100vw - 72px); }
    }
</style>
