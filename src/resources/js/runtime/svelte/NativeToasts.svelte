<script>
    import { toastGroups, toastIcon } from '../toasts.js';
    let { state } = $props();
</script>

{#each toastGroups(state.page) as [position, toasts] (position)}<div class="lu-toast-stack" data-lu-toast-position={position} aria-live="polite">
    {#each toasts as toast (toast.id)}<div class="lu-toast" data-laravel-toast="svelte" data-lu-toast data-lu-toast-id={toast.id} data-type={toast.type} data-auto-dismiss={String(toast.auto_dismiss)} data-duration={toast.duration} data-pause-on-hover={String(toast.pause_on_hover)} data-enter-animation={toast.enter_animation} data-enter-duration={toast.enter_duration} data-exit-animation={toast.exit_animation} data-exit-duration={toast.exit_duration} data-border={String(toast.show_border)} dir={toast.dir} role={toast.type === 'error' ? 'alert' : 'status'} style={`opacity:${toast.opacity}`}>
        {#if toast.show_progress && toast.auto_dismiss && toast.duration > 0}<div class="lu-toast-progress" data-position={toast.progress_position} data-direction={toast.progress_direction}><span data-lu-toast-progress></span></div>{/if}
        <div class="lu-toast-body">{#if toast.show_icon}<span class="lu-toast-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d={toastIcon(toast.type)}/></svg></span>{/if}<div class="lu-toast-message">{#if toast.title}<strong>{toast.title}</strong>{/if}<span>{toast.message}</span></div>{#if toast.show_close}<button type="button" data-lu-dismiss-toast aria-label={state.page.labels.close}><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 6 12 12M6 18 18 6"/></svg></button>{/if}</div>
    </div>{/each}
</div>{/each}
