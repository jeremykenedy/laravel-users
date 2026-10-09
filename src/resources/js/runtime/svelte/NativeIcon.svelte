<script>
    import { iconForAction } from '../icons.js';
    let { action, enabled } = $props();
    const icon = $derived(enabled ? iconForAction(action) : null);
</script>

{#if icon}<svg data-lu-icon={icon.name} class="lu-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">{#each icon.shapes as shape, index (index)}{#if shape.tag === 'path'}<path {...shape.attributes}/>{:else if shape.tag === 'circle'}<circle {...shape.attributes}/>{:else if shape.tag === 'rect'}<rect {...shape.attributes}/>{/if}{/each}</svg>{/if}
