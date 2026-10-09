<script>
    import NativeAvatar from './NativeAvatar.svelte';
    import NativeCell from './NativeCell.svelte';
    let { state, store } = $props();
</script>

{#if state.page.features.show_header && !state.page.features.custom_header}
    <nav class="lu-toolbar" aria-label={state.page.labels.navigation}><a class="lu-brand" href={state.page.data.home}>{state.page.labels.package_name}</a><div class="lu-actions">
        {#if state.page.data.current_user}<details class="lu-user-menu"><summary class="lu-user-menu-toggle"><NativeAvatar avatar={state.page.data.current_user.avatar}/><span>{state.page.data.current_user.name}</span><span class="lu-user-menu-caret" aria-hidden="true"></span></summary><div class="lu-user-menu-items">
            {#if state.page.data.current_user.activity}<div class="lu-user-menu-login" aria-label={state.page.labels.login_details}><NativeCell user={state.page.data.current_user} column={{key: 'activity', type: 'activity'}} {state} {store}/></div>{/if}
            {#if state.page.urls.users}<a href={state.page.urls.users} onclick={event => { event.preventDefault(); store.navigate(state.page.urls.users); }}>{state.page.labels.manage_users}</a>{/if}
            {#if state.page.urls.account}<a href={state.page.urls.account} onclick={event => { event.preventDefault(); store.navigate(state.page.urls.account); }}>{state.page.labels.account_menu_label}</a>{/if}
            {#if state.page.urls.logout}<form method="POST" action={state.page.urls.logout}><input type="hidden" name="_token" value={state.page.csrf}><button type="submit">{state.page.labels.logout}</button></form>{/if}
        </div></details>{/if}
        {#if state.page.features.theme_toggle}<button type="button" class="lu-button lu-secondary" onclick={store.toggleTheme}>{state.page.theme === 'dark' ? state.page.labels.theme_light : state.page.labels.theme_dark}</button>{/if}
    </div></nav>
{/if}
{#if state.page.features.breadcrumbs}<nav class="lu-breadcrumbs lu-native-breadcrumbs" aria-label={state.page.labels.breadcrumbs}><ol>{#each state.page.data.breadcrumbs as crumb, index}<li aria-current={index === state.page.data.breadcrumbs.length - 1 ? 'page' : undefined}>{#if index}<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m9 5 7 7-7 7"/></svg>{/if}{#if index < state.page.data.breadcrumbs.length - 1 && crumb.url}<a href={crumb.url} onclick={event => { if (crumb.native !== false) { event.preventDefault(); store.navigate(crumb.url); } }}>{crumb.label}</a>{:else}<span>{crumb.label}</span>{/if}</li>{/each}</ol></nav>{/if}
