<script setup>
import { previewStyle } from '../appearance.js';
import NativeAvatar from './NativeAvatar.vue';
defineProps({ state: Object });
</script>

<template>
    <div v-if="state.page.data.appearance_preview" class="lu-native-cards" :aria-busy="state.appearancePreviewLoading">
        <section v-for="(sample, kind) in state.appearancePreviewAvatars" :key="kind" class="lu-settings-choice"><h3>{{ state.page.labels['settings_' + kind + '_color'] }}</h3><div class="lu-profile-identity lu-native-profile" :style="previewStyle(state.page, state.values.settings, kind)"><NativeAvatar :avatar="sample.avatar"/><div><h4>{{ sample.name }}</h4><p>{{ state.page.labels.appearance_avatar_preview }}</p></div></div></section>
    </div>
</template>
