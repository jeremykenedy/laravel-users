<style>
    :where([data-lu-theme="dark"]) .lu-user-menu-component { --lu-bg: #1d2738; --lu-text: #edf2fa; --lu-border: #42516a; --lu-soft: #253145; }
    :where(#laravelusers, .lu-user-menu-component) .lu-user-menu { position: relative; }
    :where(#laravelusers, .lu-user-menu-component) .lu-user-menu-toggle { display: flex; align-items: center; gap: 10px; padding: 8px 0; color: inherit; font-size: .875rem; list-style: none; white-space: nowrap; }
    :where(#laravelusers, .lu-user-menu-component) summary.lu-user-menu-toggle { cursor: pointer; }
    :where(#laravelusers, .lu-user-menu-component) .lu-user-menu-toggle::-webkit-details-marker { display: none; }
    :where(#laravelusers, .lu-user-menu-component) .lu-user-menu-caret { margin: 3px 8px 0 2px; border: 4px solid transparent; border-top-color: currentColor; }
    :where(#laravelusers, .lu-user-menu-component) .lu-user-menu-items { position: absolute; right: 0; top: 100%; z-index: 30; min-width: 200px; padding: 5px; background: var(--lu-bg, #fff); color: var(--lu-text, #172033); border: 1px solid var(--lu-border, #ced4da); border-radius: 5px; box-shadow: 0 4px 12px #0002; }
    :where(#laravelusers, .lu-user-menu-component) .lu-user-menu-items :is(button, a) { display: flex; align-items: center; gap: 8px; width: 100%; padding: 8px 10px; border: 0; background: transparent; color: inherit; font: inherit; font-size: .875rem; text-align: left; cursor: pointer; text-decoration: none; }
    :where(#laravelusers, .lu-user-menu-component) .lu-user-menu-items a { color: var(--lu-text, #172033) !important; text-decoration: none !important; }
    :where(#laravelusers, .lu-user-menu-component) .lu-user-menu-items :is(button, a):hover { background: var(--lu-soft, #f1f4f9); }
    #laravelusers form label input[type="checkbox"] { margin-right: 8px; flex-shrink: 0; }
    #laravelusers .lu-email-check input[type="checkbox"] { margin-right: 0; }
</style>
