<script>
    import { previewStyle } from '../appearance.js';
    import NativeAvatar from './NativeAvatar.svelte';
    let { state } = $props();
</script>

{#if state.page.data.appearance_preview}<div class="lu-native-cards" aria-busy={state.appearancePreviewLoading}>
    {#each Object.entries(state.appearancePreviewAvatars) as [kind, sample]}<section class="lu-settings-choice"><h3>{state.page.labels['settings_' + kind + '_color']}</h3><div class="lu-profile-identity lu-native-profile" style={Object.entries(previewStyle(state.page, state.values.settings, kind)).map(([key, value]) => `${key}:${value}`).join(';')}><NativeAvatar avatar={sample.avatar}/><div><h4>{sample.name}</h4><p>{state.page.labels.appearance_avatar_preview}</p></div></div></section>{/each}
</div>{/if}
