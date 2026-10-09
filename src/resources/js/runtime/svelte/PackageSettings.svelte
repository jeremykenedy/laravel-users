<script>
    import { statusIcon } from '../shared.js';
    import NativeIcon from './NativeIcon.svelte';
    let { state, store } = $props();
</script>

{#if state.page.data.packages}
<section id="packages" class="lu-package-settings lu-pad" aria-labelledby="lu-native-packages-title">
    <h2 id="lu-native-packages-title">{state.page.labels.settings_packages}</h2>
    <p class="lu-muted">{state.page.labels.packages_hint}</p>
    {#if !state.page.data.packages.ready}<p>{state.page.labels.packages_queue_required}</p>{/if}
    <div class="lu-actions lu-native-package-actions" data-lu-native-requirement-actions>
        <button type="button" class="lu-button lu-secondary" disabled={state.busy || state.page.data.packages.ready} onclick={() => store.openSettingsAction('package-requirements')}><NativeIcon action={state.page.data.packages.ready ? 'check' : 'settings'} enabled={state.page.features.icons}/><span>{state.page.data.packages.ready ? state.page.labels.package_requirements_completed : state.page.labels.package_requirements_setup}</span></button>
        <button type="button" class="lu-button lu-secondary" disabled={state.busy || state.packageRequirements?.busy} onclick={store.verifyRequirements}><NativeIcon action="verify" enabled={state.page.features.icons}/><span>{state.page.data.packages.ready ? state.page.labels.package_requirements_reverify : state.page.labels.package_requirements_verify}</span></button>
    </div>
    {#if state.packageRequirements}<div class="lu-native-package-status" role="status" data-lu-native-package-requirements>
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" class={state.packageRequirements.status === 'checking' ? 'lu-package-spinner' : ''}><path d={statusIcon(state.packageRequirements.status === 'checking' ? 'running' : state.packageRequirements.queue_ready ? 'completed' : 'failed')}/></svg>
        <div><p>{state.packageRequirements.message}</p>{#if state.packageRequirements.transport_error}<p role="alert">{state.packageRequirements.transport_error}</p>{/if}</div>
    </div>{/if}
    <p class="lu-muted">{state.page.labels.package_requirements_hint}</p>
    <div class="lu-actions">{#each state.page.data.packages.help as link (link.url)}<a href={link.url} target="_blank" rel="noopener noreferrer">{link.label}</a>{/each}</div>
    <div class="lu-settings-grid">{#each state.page.data.packages.choices as choice (choice.name)}<section class="lu-settings-choice">
        <h3>{choice.label}</h3><p>{choice.installed ? state.page.labels.package_installed : state.page.labels.package_not_installed}</p>
        {#if choice.hint}<p class="lu-muted">{choice.hint}</p>{/if}{#if choice.reason}<p class="lu-muted">{choice.reason}</p>{/if}
        <div class="lu-actions lu-native-package-actions"><button type="button" class="lu-button lu-secondary" disabled={choice.blocked || !state.page.data.packages.ready || state.busy} onclick={() => store.openSettingsAction(choice.name)}><NativeIcon action={choice.installed ? 'remove' : 'install'} enabled={state.page.features.icons}/><span>{choice.installed ? state.page.labels.package_remove : state.page.labels.package_install}</span></button>
        {#if choice.configure_name && !choice.setup_completed}<button type="button" class="lu-button lu-secondary" disabled={!state.page.data.packages.ready || state.busy} onclick={() => store.openSettingsAction(choice.configure_name)}><NativeIcon action="configure" enabled={state.page.features.icons}/><span>{state.page.labels.package_configure}</span></button>{/if}</div>
        {#if choice.setup_completed}<p class="lu-setup-completed"><NativeIcon action="check" enabled={state.page.features.icons}/><span>{state.page.labels.package_setup_completed}</span></p>{/if}
        {#if choice.setup_hint}<p class="lu-muted">{choice.setup_hint}</p>{/if}
    </section>{/each}</div>
</section>
{/if}
