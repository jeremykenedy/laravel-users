<style>
    #laravelusers .lu-profile { max-width: 960px; margin: 0 auto; background: var(--lu-bg, #fff); border: 1px solid var(--lu-border, #dee2e6); border-radius: 8px; overflow: hidden; }
    #laravelusers .lu-profile-header { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 16px 24px; border-bottom: 1px solid var(--lu-border, #dee2e6); }
    #laravelusers .lu-profile-header h1 { margin: 0; font-size: 1.25rem; font-weight: 500; letter-spacing: normal; }
    #laravelusers .lu-profile-identity { display: flex; align-items: center; gap: 24px; padding: 28px 24px; background: var(--lu-soft, #f8fafc); }
    #laravelusers .lu-profile-identity .lu-avatar { width: 80px !important; height: 80px !important; font-size: 26px; }
    #laravelusers .lu-profile-identity .lu-avatar svg { width: 36px; height: 36px; }
    #laravelusers .lu-profile-identity h2 { margin: 0 0 4px; font-size: 1.5rem; font-weight: 600; }
    #laravelusers .lu-profile-identity p { margin: 0; }
    #laravelusers .lu-profile-identity > div { min-width: 0; overflow-wrap: anywhere; }
    #laravelusers .lu-profile-details { display: grid; grid-template-columns: 1fr 1fr; gap: 0 28px; padding: 8px 24px 24px; margin: 0; }
    #laravelusers .lu-profile-details .lu-detail { display: block; padding: 16px 0; border-bottom: 1px solid var(--lu-border, #dee2e6); }
    #laravelusers .lu-profile-details dt { display: flex; align-items: center; gap: 8px; margin-bottom: 6px; color: var(--lu-muted, #526077); font-size: .8rem; font-weight: 600; }
    #laravelusers .lu-profile-details dd { margin: 0; padding-left: 26px; overflow-wrap: anywhere; }
    #laravelusers .lu-profile .lu-date { font-size: .875rem; }
    #laravelusers .lu-profile .lu-icon { width: 18px; height: 18px; flex-shrink: 0; }
    #laravelusers .lu-profile-actions { display: flex; flex-wrap: nowrap; gap: 24px; padding: 16px 24px; border-top: 1px solid var(--lu-border, #dee2e6); background: var(--lu-soft, #f8fafc); }
    #laravelusers .lu-profile-actions > a, #laravelusers .lu-profile-actions > form { flex: 1; min-width: 0; }
    #laravelusers .lu-profile-actions button { width: 100%; }
    #laravelusers .lu-profile .lu-button, #laravelusers .lu-profile .btn { min-height: 38px; padding: 8px 12px; border-radius: 5px; font-size: .875rem; }
    @media (max-width: 640px) {
        #laravelusers .lu-profile-header, #laravelusers .lu-profile-identity, #laravelusers .lu-profile-actions { padding: 16px; gap: 16px; }
        #laravelusers .lu-profile-details { grid-template-columns: 1fr; padding: 0 16px 16px; }
        #laravelusers .lu-profile-header h1 { font-size: 1rem; }
        #laravelusers .lu-profile-actions .lu-button { width: 100%; font-size: .875rem; }
    }
</style>
