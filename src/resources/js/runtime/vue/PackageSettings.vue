<script setup>
import { statusIcon } from '../shared.js';
defineProps({ state: Object, store: Object });
</script>

<template>
    <section v-if="state.page.data.packages" class="lu-package-settings lu-pad" aria-labelledby="lu-native-packages-title">
        <h2 id="lu-native-packages-title">{{ state.page.labels.settings_packages }}</h2>
        <p class="lu-muted">{{ state.page.labels.packages_hint }}</p>
        <p v-if="!state.page.data.packages.ready">{{ state.page.labels.packages_queue_required }}</p>
        <div class="lu-actions">
            <button type="button" class="lu-button lu-secondary" :disabled="state.busy || state.page.data.packages.ready" @click="store.openSettingsAction('package-requirements')">{{ state.page.data.packages.ready ? state.page.labels.package_requirements_completed : state.page.labels.package_requirements_setup }}</button>
            <button type="button" class="lu-button lu-secondary" :disabled="state.busy || state.packageRequirements?.busy" @click="store.verifyRequirements">{{ state.page.labels.package_requirements_verify }}</button>
        </div>
        <div v-if="state.packageRequirements" class="lu-native-package-status" role="status" data-lu-native-package-requirements>
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" :class="state.packageRequirements.status === 'checking' ? 'lu-package-spinner' : ''"><path :d="statusIcon(state.packageRequirements.status === 'checking' ? 'running' : state.packageRequirements.queue_ready ? 'completed' : 'failed')"/></svg>
            <div><p>{{ state.packageRequirements.message }}</p><p v-if="state.packageRequirements.transport_error" role="alert">{{ state.packageRequirements.transport_error }}</p></div>
        </div>
        <p class="lu-muted">{{ state.page.labels.package_requirements_hint }}</p>
        <div class="lu-actions"><a v-for="link in state.page.data.packages.help" :key="link.url" :href="link.url" target="_blank" rel="noopener noreferrer">{{ link.label }}</a></div>
        <div class="lu-settings-grid">
            <section v-for="choice in state.page.data.packages.choices" :key="choice.name" class="lu-settings-choice">
                <h3>{{ choice.label }}</h3><p>{{ choice.installed ? state.page.labels.package_installed : state.page.labels.package_not_installed }}</p>
                <p v-if="choice.hint" class="lu-muted">{{ choice.hint }}</p><p v-if="choice.reason" class="lu-muted">{{ choice.reason }}</p>
                <button type="button" class="lu-button lu-secondary" :disabled="choice.blocked || !state.page.data.packages.ready || state.busy" @click="store.openSettingsAction(choice.name)">{{ choice.installed ? state.page.labels.package_remove : state.page.labels.package_install }}</button>
                <button v-if="choice.configure_name" type="button" class="lu-button lu-secondary" :disabled="!state.page.data.packages.ready || state.busy" @click="store.openSettingsAction(choice.configure_name)">{{ state.page.labels.package_configure }}</button>
                <p v-if="choice.setup_hint" class="lu-muted">{{ choice.setup_hint }}</p>
            </section>
        </div>
    </section>
</template>
