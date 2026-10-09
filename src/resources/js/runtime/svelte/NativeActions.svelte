<script>
    let { user, state, store } = $props();
</script>

<div class="lu-actions">
    {#each user.links ?? [] as link (link.url)}<a href={link.url} class={'lu-button ' + (link.class ?? 'lu-secondary')} onclick={event => { event.preventDefault(); store.navigate(link.url); }}>{link.label}</a>{/each}
    {#each user.actions ?? [] as action (action.name)}<button type="button" class={'lu-button ' + (action.class ?? 'lu-secondary')} disabled={action.disabled || state.busy} onclick={() => store.openUserAction(action.name, user.id)}>{action.label}</button>{/each}
</div>
