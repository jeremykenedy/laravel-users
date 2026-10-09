<script setup>
defineProps({ user: Object, store: Object, state: Object });
</script>

<template>
    <div class="lu-actions">
        <a v-for="link in user.links ?? []" :key="link.url" :href="link.url" :class="['lu-button', link.class ?? 'lu-secondary']" @click.prevent="store.navigate(link.url)">{{ link.label }}</a>
        <button v-for="action in user.actions ?? []" :key="action.name" type="button" :class="['lu-button', action.class ?? 'lu-secondary']" :disabled="action.disabled || state.busy" @click="store.openUserAction(action.name, user.id)">{{ action.label }}</button>
    </div>
</template>
