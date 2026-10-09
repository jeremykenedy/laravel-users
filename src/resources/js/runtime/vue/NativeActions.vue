<script setup>
import NativeIcon from './NativeIcon.vue';
defineProps({ user: Object, store: Object, state: Object });
</script>

<template>
    <div class="lu-actions">
        <a v-for="link in user.links ?? []" :key="link.url" :href="link.url" :class="['lu-button', link.class ?? 'lu-secondary']" @click.prevent="store.navigate(link.url)"><NativeIcon :action="link.name" :enabled="state.page.features.icons"/><span>{{ link.label }}</span></a>
        <button v-for="action in user.actions ?? []" :key="action.name" type="button" :class="['lu-button', action.class ?? 'lu-secondary']" :disabled="action.disabled || state.busy" @click="store.openUserAction(action.name, user.id)"><NativeIcon :action="action.name" :enabled="state.page.features.icons"/><span>{{ action.label }}</span></button>
    </div>
</template>
