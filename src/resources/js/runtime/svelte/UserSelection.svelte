<script>
    let { user, state, store } = $props();
</script>

<label class="lu-check"><input type="checkbox" value={user.id} checked={state.table.selected.includes(String(user.id))} disabled={!user.selectable} onchange={event => store.select(user.id, event.target.checked)}><span class="lu-sr-only">{state.page.labels.select_user.replace(':name', user.name)}</span></label>
