@include('laravelusers::partials.asset', ['name' => 'notifications.css'])
<style>
    #laravelusers .lu-notifications { width: 100%; min-width: 0; margin-inline: auto; }
    #laravelusers.lu-shell > :is(.lu-toolbar, .lu-notifications, .lu-breadcrumbs, .lu-panel, .lu-profile) { width: 100%; max-width: none; margin-inline: auto; }
    #laravelusers.lu-shell > .lu-toolbar { position: relative; border-bottom: 0; }
    #laravelusers.lu-shell > .lu-toolbar::after { content: ""; position: absolute; inset-inline: 0; bottom: 0; height: 1px; background: var(--lu-border, #ced4da); box-shadow: 0 0 0 100vmax var(--lu-border, #ced4da); clip-path: inset(0 -100vmax); pointer-events: none; }
    #laravelusers:not(.lu-shell) #app > .navbar-laravel { padding-inline: 0; border-bottom: 1px solid var(--lu-border, #ced4da); }
    #laravelusers:not(.lu-shell) #app > .navbar-laravel > .container { padding-inline: 15px; }
    #laravelusers .lu-flash { box-sizing: border-box; }
    #laravelusers .lu-flash { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; width: 100%; margin: 0 0 16px; padding: 12px 16px; border: 1px solid var(--lu-border, #ced4da); border-radius: 6px; background: var(--lu-soft, #f1f4f9); color: var(--lu-text, #172033); font-size: .875rem; overflow-wrap: anywhere; }
    #laravelusers .lu-flash > span, #laravelusers .lu-flash > div { min-width: 0; }
    #laravelusers .lu-flash button { display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; padding: 2px; border: 0; background: transparent; color: inherit; cursor: pointer; }
    #laravelusers .lu-flash p { margin: 0 0 6px; }
    #laravelusers .lu-flash ul { margin: 0; padding-left: 20px; }
    #laravelusers .lu-flash.alert-success { border-left: 4px solid #087f5b; }
    #laravelusers .lu-flash.alert-danger { border-left: 4px solid #b42332; }
</style>
