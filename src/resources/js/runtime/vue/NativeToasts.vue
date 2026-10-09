<script setup>
import { toastGroups, toastIcon } from '../toasts.js';
defineProps({ state: Object });
</script>

<template>
    <div v-for="[position, toasts] in toastGroups(state.page)" :key="position" class="lu-toast-stack" :data-lu-toast-position="position" aria-live="polite">
        <div v-for="toast in toasts" :key="toast.id" class="lu-toast" data-laravel-toast="vue" data-lu-toast :data-lu-toast-id="toast.id" :data-type="toast.type" :data-auto-dismiss="String(toast.auto_dismiss)" :data-duration="toast.duration" :data-pause-on-hover="String(toast.pause_on_hover)" :data-enter-animation="toast.enter_animation" :data-enter-duration="toast.enter_duration" :data-exit-animation="toast.exit_animation" :data-exit-duration="toast.exit_duration" :data-border="String(toast.show_border)" :dir="toast.dir" :role="toast.type === 'error' ? 'alert' : 'status'" :style="{opacity: toast.opacity}">
            <div v-if="toast.show_progress && toast.auto_dismiss && toast.duration > 0" class="lu-toast-progress" :data-position="toast.progress_position" :data-direction="toast.progress_direction"><span data-lu-toast-progress></span></div>
            <div class="lu-toast-body"><span v-if="toast.show_icon" class="lu-toast-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path :d="toastIcon(toast.type)"/></svg></span><div class="lu-toast-message"><strong v-if="toast.title">{{ toast.title }}</strong><span>{{ toast.message }}</span></div><button v-if="toast.show_close" type="button" data-lu-dismiss-toast :aria-label="state.page.labels.close"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 6 12 12M6 18 18 6"/></svg></button></div>
        </div>
    </div>
</template>
