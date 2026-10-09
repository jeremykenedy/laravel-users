<script>
    import { displayUsers, visibleColumns } from '../store.js';
    import NativeAvatar from './NativeAvatar.svelte';
    import NativeCell from './NativeCell.svelte';
    import NativeActions from './NativeActions.svelte';
    import NativeIcon from './NativeIcon.svelte';
    import UserSelection from './UserSelection.svelte';
    let { state, store } = $props();
    const users = $derived(displayUsers(state.page, state.table));
    const columns = $derived(visibleColumns(state));
</script>

<div data-lu-native-table>
    <div class="lu-pad lu-actions lu-table-controls">
        {#if state.page.features.filtering}<label class="lu-field" for="lu-native-table-filter"><span>{state.page.labels.filter}</span><input id="lu-native-table-filter" class="lu-input" type="search" maxlength="255" value={state.table.filter} oninput={event => store.setFilter(event.target.value)}></label>{/if}
        {#if state.page.features.view_toggle}<div class="lu-actions" role="group" aria-label={state.page.labels.view}>{#each ['table', 'cards'] as mode}<button type="button" class="lu-button lu-secondary" aria-pressed={state.table.mode === mode} onclick={() => store.setMode(mode)}>{state.page.labels[mode]}</button>{/each}</div>{/if}
        {#if state.page.features.columns}<details class="lu-column-controls"><summary>{state.page.labels.columns}</summary><div class="lu-pad">{#each state.page.data.columns as column (column.key)}<label class="lu-check"><input type="checkbox" checked={!state.table.hiddenColumns.includes(column.key)} onchange={() => store.toggleColumn(column.key)}><span>{column.label}</span></label>{/each}</div></details>{/if}
    </div>
    {#if state.page.features.bulk}<div class="lu-pad lu-actions"><span role="status">{state.page.labels.selected.replace(':count', state.table.selected.length)}</span>{#each state.page.features.bulk_actions as action (action.name)}<button type="button" class={'lu-button ' + (action.class ?? 'lu-secondary')} disabled={!state.table.selected.length || action.disabled || state.busy} onclick={() => store.openBulkAction(action.name)}><NativeIcon action={action.name} enabled={state.page.features.icons}/><span>{action.label}</span></button>{/each}</div>{/if}
    {#if state.table.mode === 'cards' && state.page.features.view_toggle}
        <div class="lu-native-cards lu-pad">{#each users as user (user.id)}<article class={'lu-panel lu-pad lu-native-user-card ' + state.page.classes.panel}><header class="lu-heading"><NativeAvatar avatar={user.avatar}/><h2>{#if user.urls?.show}<a href={user.urls.show} onclick={event => { event.preventDefault(); store.navigate(user.urls.show); }}>{user.name}</a>{:else}{user.name}{/if}</h2>{#if state.page.features.bulk}<UserSelection {user} {state} {store}/>{/if}</header><dl class="lu-details">{#each columns as column (column.key)}<div><dt>{column.label}</dt><dd><NativeCell {user} {column} {state} {store}/></dd></div>{/each}</dl><NativeActions {user} {state} {store}/></article>{/each}{#if !users.length}<p role="status">{state.page.labels.empty}</p>{/if}</div>
    {:else}
        <div class={'lu-scroll ' + state.page.classes.scroll}><table class={'lu-native-table ' + state.page.classes.table}>
            <caption>{state.page.labels.directory}</caption>
            <thead><tr>{#if state.page.features.bulk}<th scope="col"><button type="button" class="lu-button lu-secondary" onclick={store.selectAll}>{state.page.labels.select_all}</button></th>{/if}{#each columns as column (column.key)}<th scope="col" aria-sort={state.table.sort === column.key ? (state.table.direction === 'asc' ? 'ascending' : 'descending') : undefined}>{#if state.page.features.sorting && column.sortable !== false}<button type="button" class="lu-native-sort" onclick={() => store.sortBy(column.key)}>{column.label}</button>{:else}{column.label}{/if}</th>{/each}<th scope="col">{state.page.labels.actions}</th></tr></thead>
            <tbody>{#each users as user (user.id)}<tr>{#if state.page.features.bulk}<td><UserSelection {user} {state} {store}/></td>{/if}{#each columns as column (column.key)}<td><NativeCell {user} {column} {state} {store}/></td>{/each}<td><NativeActions {user} {state} {store}/></td></tr>{/each}{#if !users.length}<tr><td colspan={columns.length + 1 + Number(state.page.features.bulk)} ><p role="status">{state.page.labels.empty}</p></td></tr>{/if}</tbody>
        </table></div>
    {/if}
</div>
