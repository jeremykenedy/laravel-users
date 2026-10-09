<script setup>
defineProps({ state: Object, store: Object });
</script>

<template>
    <div class="lu-native-notifications" :data-lu-notification-style="state.page.features.notification_driver">
        <template v-for="(message, index) in [...state.page.flash, ...(state.notice ? [state.notice] : [])]" :key="index"><div v-if="!state.dismissedMessages.includes(index) && (state.page.features.notifications || message.type === 'error')" :class="['lu-flash', message.type === 'error' ? 'alert-danger' : message.type === 'success' ? 'alert-success' : '']" :role="message.type === 'error' ? 'alert' : 'status'"><span>{{ message.message }}</span><button v-if="state.page.features.notification_dismissible" type="button" :aria-label="state.page.labels.close" @click="store.dismissMessage(index)">{{ state.page.labels.close }}</button></div></template>
    </div>
</template>
