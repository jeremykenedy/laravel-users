<script>
    import NativeIcon from './NativeIcon.svelte';
    let { user, state, store } = $props();
</script>

<div class="lu-actions">
    {#each user.links ?? [] as link (link.url)}<a href={link.url} class={'lu-button ' + (link.class ?? 'lu-secondary')} onclick={event => { event.preventDefault(); store.navigate(link.url); }}><NativeIcon action={link.name} enabled={state.page.features.icons}/><span>{link.label}</span></a>{/each}
    {#each user.actions ?? [] as action (action.name)}<button type="button" class={'lu-button ' + (action.class ?? 'lu-secondary')} disabled={action.disabled || state.busy} onclick={() => store.openUserAction(action.name, user.id)}><NativeIcon action={action.name} enabled={state.page.features.icons}/><span>{action.label}</span></button>{/each}
</div>
