<script setup>
import { statusIcon } from '../shared.js';
defineProps({ operation: Object, state: Object, store: Object });
</script>

<template>
    <div v-if="operation" class="lu-flash lu-native-package-status" :role="operation.status === 'failed' ? 'alert' : 'status'" :data-lu-package-state="operation.status">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" :class="['lu-package-status-icon', operation.status === 'running' ? 'lu-package-spinner' : '']"><path :d="statusIcon(operation.status)" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <div><p>{{ operation.message }}</p><p v-if="operation.transport_error" role="alert">{{ operation.transport_error }}</p><a v-if="['completed', 'failed'].includes(operation.status) && state.page.urls.settings" :href="state.page.urls.settings" class="lu-button lu-secondary">{{ state.page.labels.package_refresh }}</a></div>
    </div>
</template>
