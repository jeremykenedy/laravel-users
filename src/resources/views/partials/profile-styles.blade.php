<style>
    #laravelusers .lu-profile { max-width: 960px; margin: 0 auto; background: var(--lu-bg, #fff); border: 1px solid var(--lu-border, #dee2e6); border-radius: 8px; overflow: hidden; }
    #laravelusers .lu-profile-header { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 16px 24px; border-bottom: 1px solid var(--lu-border, #dee2e6); }
    #laravelusers .lu-profile-header h1 { margin: 0; font-size: 1.25rem; font-weight: 500; letter-spacing: normal; }
    #laravelusers .lu-profile-identity { display: flex; align-items: center; gap: 24px; padding: 28px 24px; background-color: var(--lu-profile-color); background-image: var(--lu-profile-image, radial-gradient(ellipse at var(--lu-profile-highlight, 50% 40%), var(--lu-profile-glow), transparent 75%), linear-gradient(145deg, transparent, var(--lu-profile-shade))); color: var(--lu-profile-text); }
    #laravelusers .lu-profile-identity a { color: var(--lu-profile-text); }
    #laravelusers[data-lu-theme="dark"] .lu-profile-identity { background-color: var(--lu-profile-dark-color); background-image: var(--lu-profile-dark-image, radial-gradient(ellipse at var(--lu-profile-highlight, 50% 40%), var(--lu-profile-dark-glow), transparent 75%), linear-gradient(145deg, transparent, var(--lu-profile-dark-shade))); color: var(--lu-profile-dark-text); }
    #laravelusers[data-lu-theme="dark"] .lu-profile-identity a { color: var(--lu-profile-dark-text); }
    @php($editColors = \jeremykenedy\laravelusers\Support\Frontend::profileColors('editCardColor', '#705000'))
    #laravelusers .lu-edit-card { --lu-profile-color: {{ $editColors['base'] }}; --lu-profile-text: {{ $editColors['text'] }}; --lu-profile-shade: {{ $editColors['shade'] }}; --lu-profile-glow: {{ $editColors['highlight'] }}; --lu-profile-image: {{ config('laravelusers.editCardGradient', true) ? 'initial' : 'none' }}; }
    @php($darkEditColors = \jeremykenedy\laravelusers\Support\Frontend::profileColors('editCardColor', '#705000', true))
    #laravelusers .lu-edit-card { --lu-profile-dark-color: {{ $darkEditColors['base'] }}; --lu-profile-dark-text: {{ $darkEditColors['text'] }}; --lu-profile-dark-shade: {{ $darkEditColors['shade'] }}; --lu-profile-dark-glow: {{ $darkEditColors['highlight'] }}; --lu-profile-dark-image: {{ (config('laravelusers.editCardDarkGradient') ?? config('laravelusers.editCardGradient', true)) ? 'initial' : 'none' }}; }
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
    @if(\jeremykenedy\laravelusers\Support\Frontend::framework() === 'bootstrap5')
        #laravelusers .lu-profile-header { background: var(--lu-soft, #f8fafc); }
        #laravelusers .lu-profile .lu-profile-header > a { width: auto; flex-shrink: 0; white-space: nowrap; }
        #laravelusers .lu-profile-body { display: grid; grid-template-columns: 240px minmax(0, 1fr); }
        #laravelusers .lu-profile-identity { flex-direction: column; justify-content: center; gap: 20px; padding: 32px 24px; text-align: center; }
        #laravelusers .lu-edit-card .lu-field { grid-template-columns: 1fr; gap: 6px; margin-bottom: 18px; }
        #laravelusers .lu-edit-card .lu-field > label { text-align: left; padding: 0; }
        #laravelusers .lu-profile-header .lu-actions { flex-wrap: nowrap; gap: 8px; }
        #laravelusers .lu-profile-identity .lu-avatar { width: 112px !important; height: 112px !important; border: 5px solid #fff; font-size: 36px; background: #f4f6fa; color: #172235; }
        #laravelusers .lu-profile-identity h2 { margin-bottom: 8px; font-size: 1.25rem; }
        #laravelusers .lu-profile-identity p { font-size: .875rem; }
        #laravelusers .lu-profile-identity a { color: var(--lu-profile-text); }
        #laravelusers .lu-profile-details { display: block; padding: 16px 24px; }
        #laravelusers .lu-profile-details .lu-detail { display: grid; grid-template-columns: 180px minmax(0, 1fr); align-items: center; gap: 16px; min-height: 40px; padding: 10px 0; }
        #laravelusers .lu-profile-details .lu-detail:last-child { border-bottom: 0; }
        #laravelusers .lu-profile-details dt { gap: 10px; margin: 0; font-weight: 500; }
        #laravelusers .lu-profile-details dd { padding: 0; font-size: .875rem; }
        @media (max-width: 640px) {
            #laravelusers[data-lu-responsive-buttons="true"] .lu-profile .lu-form-actions .lu-button { justify-content: center; gap: 0; width: 40px; height: 40px; min-height: 40px; padding: 0; font-size: 0; }
            #laravelusers[data-lu-responsive-buttons="true"] .lu-profile-header .lu-actions > a { justify-content: center; gap: 0; width: 40px; height: 40px; min-height: 40px; padding: 0; font-size: 0; }
            #laravelusers[data-lu-responsive-buttons="true"] .lu-profile .lu-profile-header > a { justify-content: center; gap: 0; width: 40px; height: 40px; min-height: 40px; padding: 0; font-size: 0; }
            #laravelusers .lu-profile-body { grid-template-columns: 1fr; }
            #laravelusers .lu-profile-identity { --lu-profile-highlight: 56px 50%; flex-direction: row; justify-content: flex-start; padding: 20px 16px; gap: 16px; text-align: left; }
            #laravelusers .lu-profile-identity .lu-avatar { width: 80px !important; height: 80px !important; font-size: 26px; flex-shrink: 0; }
            #laravelusers .lu-profile-details { padding: 8px 16px; }
            #laravelusers .lu-profile-details .lu-detail { grid-template-columns: minmax(130px, 1fr) minmax(0, 1fr); gap: 12px; }
        }
    @endif
</style>
