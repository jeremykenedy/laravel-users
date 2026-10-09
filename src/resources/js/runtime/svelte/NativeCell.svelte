<script>
    import NativeAvatar from './NativeAvatar.svelte';
    import { cellText, dateLabel } from '../shared.js';
    let { user, column, state, store } = $props();
    const text = $derived(cellText(user, column));
</script>

{#if column.type === 'avatar'}<NativeAvatar avatar={user.avatar}/>
{:else if column.type === 'activity'}
    <dl class="lu-details"><div><dt>{state.page.labels.last_login_at}</dt><dd>{#if user.activity?.last_login_at}<time datetime={user.activity.last_login_at}>{dateLabel(user.activity.last_login_at, state.page)}</time>{:else}{state.page.labels.no_logins}{/if}</dd></div>{#each ['device', 'os', 'browser', 'ip_address'] as key}{#if user.activity?.[key]}<div><dt>{state.page.labels[key]}</dt><dd>{user.activity[key]}</dd></div>{/if}{/each}</dl>
{:else if column.type === 'date' && text}<time datetime={text}>{dateLabel(text, state.page)}</time>
{:else if column.key === 'name' && user.urls?.show}<a href={user.urls.show} onclick={event => { event.preventDefault(); store.navigate(user.urls.show); }}>{user.name}</a>
{:else if column.key === 'email' && column.linked}<a href={'mailto:' + user.email}>{user.email}</a>
{:else if column.type === 'presence'}<span class={'lu-badge ' + (user.activity?.online ? 'lu-online' : '')}>{user.activity?.online ? state.page.labels.online : state.page.labels.offline}</span>
{:else}{text}{/if}
