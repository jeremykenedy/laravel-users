<script>
    import { onMount } from 'svelte';
    import { observeDialogs } from '../shared.js';
    import NativeAvatar from './NativeAvatar.svelte';
    import NativeCell from './NativeCell.svelte';
    import NativeActions from './NativeActions.svelte';
    import NativeTable from './NativeTable.svelte';
    import NativeForm from './NativeForm.svelte';
    let { store } = $props();
    const state = $derived($store);
    onMount(() => observeDialogs(document.getElementById('lu-native-app'), () => { if (!store.getSnapshot().busy) store.closeDialog(); }));
</script>

<div data-lu-native-screen={state.page.screen} aria-busy={state.busy}>
    {#each [...state.page.flash, ...(state.notice ? [state.notice] : [])] as message}<p class="lu-alert" role={message.type === 'error' ? 'alert' : 'status'}>{message.message}</p>{/each}
    {#if state.page.data.banner}<div class="lu-alert" role="status">{state.page.data.banner.message}<button type="button" class="lu-button lu-secondary" disabled={state.busy} onclick={store.submitBanner}>{state.page.data.banner.label}</button></div>{/if}
    <section class={'lu-panel ' + state.page.classes.panel}>
        <header class="lu-heading lu-card-heading"><h1 tabindex="-1" data-lu-native-heading>{state.page.title}</h1><div class="lu-actions">{#each state.page.data.navigation as link (link.url)}<a href={link.url} class="lu-button lu-secondary" onclick={event => { event.preventDefault(); store.navigate(link.url); }}>{link.label}</a>{/each}{#if state.page.features.theme_toggle}<button type="button" class="lu-button lu-secondary" onclick={store.toggleTheme}>{state.page.theme === 'dark' ? state.page.labels.theme_light : state.page.labels.theme_dark}</button>{/if}</div></header>
        {#if state.page.data.notice}<p class="lu-pad" role="status">{state.page.data.notice}</p>{/if}
        {#if ['users', 'deleted-users'].includes(state.page.screen)}
            {#if state.page.features.search}<form class="lu-search" onsubmit={event => { event.preventDefault(); store.search(); }}><label class="lu-sr-only" for="lu-native-search">{state.page.labels.search}</label><input id="lu-native-search" class="lu-input" type="search" maxlength="255" value={state.search} oninput={event => store.setSearch(event.target.value)}><button type="submit" class="lu-button" disabled={state.busy}>{state.page.labels.search}</button></form>{/if}
            <NativeTable {state} {store}/>
            <div class="lu-pagination">{#if state.page.features.show_count}<span>{state.page.labels.total_users.replace(':count', state.page.data.pagination.total)}</span>{/if}{#if state.page.data.pagination.enabled}<nav class="lu-actions" aria-label={state.page.labels.pagination}>{#if state.page.data.pagination.previous}<a href={state.page.data.pagination.previous} class="lu-button lu-secondary" onclick={event => { event.preventDefault(); store.navigate(state.page.data.pagination.previous); }}>{state.page.labels.previous}</a>{/if}<span>{state.page.labels.page.replace(':page', state.page.data.pagination.current).replace(':total', state.page.data.pagination.last)}</span>{#if state.page.data.pagination.next}<a href={state.page.data.pagination.next} class="lu-button lu-secondary" onclick={event => { event.preventDefault(); store.navigate(state.page.data.pagination.next); }}>{state.page.labels.next}</a>{/if}</nav>{/if}</div>
        {/if}
        {#if ['show-user', 'account'].includes(state.page.screen)}
            <div class="lu-profile-body"><header class="lu-native-profile"><NativeAvatar avatar={state.page.data.user.avatar}/><div><h2>{state.page.data.user.full_name ?? state.page.data.user.name}</h2><p>{state.page.data.user.email}</p></div></header>{#if state.page.data.user.pending_email}<p role="status">{state.page.labels.account_email_pending_to.replace(':email', state.page.data.user.pending_email)}</p>{/if}
                {#if state.page.screen === 'show-user'}<dl class="lu-profile-details">{#each state.page.data.columns.filter(column => column.type !== 'avatar') as column (column.key)}<div class="lu-detail"><dt>{column.label}</dt><dd><NativeCell user={state.page.data.user} {column} {state} {store}/></dd></div>{/each}</dl>{#if state.page.data.user.permissions?.length}<p>{state.page.labels.direct_permissions}: {state.page.data.user.permissions.map(item => item.label).join(', ')}</p>{/if}{#if state.page.data.user.role_level}<p>{state.page.labels.role_level.replace(':level', state.page.data.user.role_level)}</p>{/if}<NativeActions user={state.page.data.user} {state} {store}/>{/if}
            </div>
        {/if}
        {#each state.page.data.form_ids as id (id)}<NativeForm form={state.page.forms[id]} {state} {store}/>{/each}
        {#if state.page.data.settings_actions?.length}<div class="lu-pad lu-actions">{#each state.page.data.settings_actions as action (action.name)}<button type="button" class={'lu-button ' + (action.class ?? 'lu-secondary')} disabled={action.disabled || state.busy} onclick={() => store.openSettingsAction(action.name)}>{action.label}</button>{/each}</div>{/if}
    </section>
    {#if store.activeForm()}<div class="lu-native-dialog-backdrop"><div class="lu-email-dialog lu-native-dialog" role="dialog" aria-modal="true" aria-labelledby="lu-native-action-title" tabindex="-1" data-lu-native-dialog><header class="lu-email-heading"><h2 id="lu-native-action-title">{store.activeForm().title}</h2><button type="button" class="lu-email-close" aria-label={state.page.labels.close} disabled={state.busy} onclick={store.closeDialog}>{state.page.labels.close}</button></header><div class="lu-email-body"><NativeForm form={store.activeForm()} {state} {store} dialog={true}/></div></div></div>{/if}
</div>
