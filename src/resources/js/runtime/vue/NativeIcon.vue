<script setup>
import { computed } from 'vue';
import { iconForAction } from '../icons.js';
const props = defineProps({ action: String, enabled: Boolean });
const icon = computed(() => props.enabled ? iconForAction(props.action) : null);
</script>

<template>
    <svg v-if="icon" :data-lu-icon="icon.name" class="lu-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><component v-for="(shape, index) in icon.shapes" :key="index" :is="shape.tag" v-bind="shape.attributes"/></svg>
</template>
