<style>
    :where([data-lu-theme="dark"]) .lu-user-menu-component { --lu-bg: #1d2738; --lu-text: #edf2fa; --lu-border: #42516a; --lu-soft: #253145; }
    :where(#laravelusers, .lu-user-menu-component) .lu-user-menu { position: relative; }
    :where(#laravelusers, .lu-user-menu-component) .lu-user-menu-toggle { display: flex; align-items: center; gap: 10px; padding: 8px 0; color: inherit; font-size: .875rem; list-style: none; white-space: nowrap; }
    :where(#laravelusers, .lu-user-menu-component) summary.lu-user-menu-toggle { cursor: pointer; }
    :where(#laravelusers, .lu-user-menu-component) .lu-user-menu-toggle::-webkit-details-marker { display: none; }
    :where(#laravelusers, .lu-user-menu-component) .lu-user-menu-caret { margin: 3px 8px 0 2px; border: 4px solid transparent; border-top-color: currentColor; }
    :where(#laravelusers, .lu-user-menu-component) .lu-user-menu-items { position: absolute; right: 0; top: 100%; z-index: 30; min-width: 200px; padding: 5px; background: var(--lu-bg, #fff); color: var(--lu-text, #172033); border: 1px solid var(--lu-border, #ced4da); border-radius: 5px; box-shadow: 0 4px 12px #0002; }
    :where(#laravelusers, .lu-user-menu-component) .lu-user-menu-login { min-width: 260px; max-width: 340px; padding: 7px 9px; border-bottom: 1px solid var(--lu-border, #ced4da); color: inherit; font-size: .68rem; line-height: 1.35; }
    :where(#laravelusers, .lu-user-menu-component) .lu-user-menu-login-row { display: grid; grid-template-columns: 14px 66px minmax(0, 1fr); align-items: center; gap: 6px; padding: 2px 0; }
    :where(#laravelusers, .lu-user-menu-component) .lu-user-menu-login-row > :last-child { min-width: 0; overflow-wrap: anywhere; }
    :where(#laravelusers, .lu-user-menu-component) .lu-user-menu-login-icon svg { width: 13px; height: 13px; vertical-align: middle; }
    :where(#laravelusers, .lu-user-menu-component) .lu-user-menu-login .lu-date { white-space: normal; }
    :where(#laravelusers, .lu-user-menu-component) .lu-user-menu-items :is(button, a) { display: flex; align-items: center; gap: 8px; width: 100%; padding: 8px 10px; border: 0; background: transparent; color: inherit; font: inherit; font-size: .875rem; text-align: left; cursor: pointer; text-decoration: none; }
    :where(#laravelusers, .lu-user-menu-component) .lu-user-menu-items a { color: var(--lu-text, #172033) !important; text-decoration: none !important; }
    :where(#laravelusers, .lu-user-menu-component) .lu-user-menu-items :is(button, a):hover { background: var(--lu-soft, #f1f4f9); }
    @if(is_array(session('laravelusers.impersonation')))
    .lu-impersonation-banner { position: fixed; z-index: 1050; top: 0; left: 0; display: flex; align-items: center; gap: 10px; width: 100%; min-height: 48px; padding: 7px 14px; background: #9a4b08; color: #fff; box-shadow: 0 2px 8px #0003; }
    .lu-impersonation-mark { display: inline-grid; place-items: center; width: 30px; height: 30px; flex: 0 0 30px; border: 1px solid #ffffff88; border-radius: 50%; }
    .lu-impersonation-mark svg { width: 17px; height: 17px; }
    .lu-impersonation-label { flex: 1; font-weight: 600; }
    .lu-impersonation-banner form { margin: 0; }
    .lu-impersonation-exit { display: inline-flex; align-items: center; gap: 7px; min-height: 34px; padding: 6px 10px; border: 1px solid #ffffff88; border-radius: 5px; background: transparent; color: #fff; font: inherit; cursor: pointer; }
    .lu-impersonation-exit:hover { background: #ffffff20; }
    @endif
    @if($canImpersonateUsers ?? false)
    .lu-impersonate-form { display: inline-flex; margin: 0; }
    @endif
    #laravelusers form label input[type="checkbox"] { margin-right: 8px; flex-shrink: 0; }
    #laravelusers .lu-email-check input[type="checkbox"] { margin-right: 0; }
</style>
