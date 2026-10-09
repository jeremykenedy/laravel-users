<script>
    let { state, store } = $props();
</script>

<div class="lu-native-notifications" data-lu-notification-style={state.page.features.notification_driver}>
    {#each [...state.page.flash, ...(state.notice ? [state.notice] : [])] as message, index}
        {#if !state.dismissedMessages.includes(index) && (state.page.features.notifications || message.type === 'error')}<div class={'lu-flash ' + (message.type === 'error' ? 'alert-danger' : message.type === 'success' ? 'alert-success' : '')} role={message.type === 'error' ? 'alert' : 'status'}><span>{message.message}</span>{#if state.page.features.notification_dismissible}<button type="button" aria-label={state.page.labels.close} onclick={() => store.dismissMessage(index)}>{state.page.labels.close}</button>{/if}</div>{/if}
    {/each}
</div>
