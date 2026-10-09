<script>
    import { statusIcon } from '../shared.js';
    let { operation, state, store } = $props();
</script>

{#if operation}
    <div class="lu-flash lu-native-package-status" role={operation.status === 'failed' ? 'alert' : 'status'} data-lu-package-state={operation.status}>
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" class={'lu-package-status-icon ' + (operation.status === 'running' ? 'lu-package-spinner' : '')}><path d={statusIcon(operation.status)} stroke-linecap="round" stroke-linejoin="round"/></svg>
        <div><p>{operation.message}</p>{#if operation.transport_error}<p role="alert">{operation.transport_error}</p>{/if}{#if ['completed', 'failed'].includes(operation.status) && state.page.urls.settings}<a href={state.page.urls.settings + '#packages'} class="lu-button lu-secondary" onclick={event => { event.preventDefault(); store.reloadPage(); }}>{state.page.labels.package_refresh}</a>{/if}</div>
    </div>
{/if}
