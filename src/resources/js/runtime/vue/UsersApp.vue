<script setup>
import { observeToastDismissals } from '../toasts.js';
import NativeToasts from './NativeToasts.vue';
import { shallowRef, onMounted, onUnmounted } from 'vue';
import { profileStyle } from '../appearance.js';
import { observeDialogs } from '../shared.js';
import NativeTable from './NativeTable.vue';
import NativeAvatar from './NativeAvatar.vue';
import NativeCell from './NativeCell.vue';
import NativeActions from './NativeActions.vue';
import NativeForm from './NativeForm.vue';
import PackageStatus from './PackageStatus.vue';
import PackageSettings from './PackageSettings.vue';
import NativeNotifications from './NativeNotifications.vue';
import NativeNavigation from './NativeNavigation.vue';
const props = defineProps({ store: Object });
const state = shallowRef(props.store.getSnapshot());
const unsubscribe = props.store.subscribe(value => { state.value = value; });
let disconnect;
let disconnectToasts;
onMounted(() => { disconnectToasts = observeToastDismissals(document.getElementById('lu-native-app'), props.store); disconnect = observeDialogs(document.getElementById('lu-native-app'), () => { if (!state.value.busy) props.store.closeDialog(); }); });
onUnmounted(() => { unsubscribe(); disconnect?.(); disconnectToasts?.(); });
</script>

<template>
    <div :data-lu-native-screen="state.page.screen" :aria-busy="state.busy">
        <NativeNavigation :state="state" :store="store"/>
        <NativeNotifications :state="state" :store="store"/>
        <NativeToasts :state="state"/>
        <PackageStatus :operation="state.packageOperation" :state="state" :store="store"/>
        <div v-if="state.page.data.banner" class="lu-alert" role="status">{{ state.page.data.banner.message }}<button type="button" class="lu-button lu-secondary" :disabled="state.busy" @click="store.submitBanner">{{ state.page.data.banner.label }}</button></div>
        <section :class="['lu-panel', state.page.classes.panel]">
            <header class="lu-heading lu-card-heading"><h1 tabindex="-1" data-lu-native-heading>{{ state.page.title }}</h1><div class="lu-actions"><a v-for="link in state.page.data.navigation" :key="link.url" :href="link.url" class="lu-button lu-secondary" @click.prevent="store.navigate(link.url)">{{ link.label }}</a></div></header>
            <p v-if="state.page.data.notice" class="lu-pad" role="status">{{ state.page.data.notice }}</p>
            <template v-if="['users', 'deleted-users'].includes(state.page.screen)">
                <form v-if="state.page.features.search" class="lu-search" @submit.prevent="store.search"><label class="lu-sr-only" for="lu-native-search">{{ state.page.labels.search }}</label><input id="lu-native-search" class="lu-input" type="search" maxlength="255" :value="state.search" @input="store.setSearch($event.target.value)"><button type="submit" class="lu-button" :disabled="state.busy">{{ state.page.labels.search }}</button><button v-if="state.search" type="button" class="lu-button lu-secondary" :disabled="state.busy" @click="store.clearSearch">{{ state.page.labels.clear }}</button></form>
                <NativeTable :state="state" :store="store"/>
                <div class="lu-pagination"><span v-if="state.page.features.show_count">{{ state.page.labels.total_users.replace(':count', state.page.data.pagination.total) }}</span><nav v-if="state.page.data.pagination.enabled" class="lu-actions" :aria-label="state.page.labels.pagination"><a v-if="state.page.data.pagination.previous" :href="state.page.data.pagination.previous" class="lu-button lu-secondary" @click.prevent="store.navigate(state.page.data.pagination.previous)">{{ state.page.labels.previous }}</a><span>{{ state.page.labels.page.replace(':page', state.page.data.pagination.current).replace(':total', state.page.data.pagination.last) }}</span><a v-if="state.page.data.pagination.next" :href="state.page.data.pagination.next" class="lu-button lu-secondary" @click.prevent="store.navigate(state.page.data.pagination.next)">{{ state.page.labels.next }}</a></nav></div>
            </template>
            <div v-if="['show-user', 'account', 'edit-user'].includes(state.page.screen)" class="lu-profile-body">
                <header class="lu-profile-identity lu-native-profile" :style="profileStyle(state.page, state.page.data.user, state.page.screen === 'edit-user', state.page.screen === 'edit-user' ? state.values.user : state.values['account-appearance'])"><NativeAvatar :avatar="state.page.data.user.avatar"/><div><h2>{{ state.page.data.user.full_name ?? state.page.data.user.name }}</h2><p>{{ state.page.data.user.email }}</p></div></header>
                <p v-if="state.page.data.user.pending_email" role="status">{{ state.page.labels.account_email_pending_to.replace(':email', state.page.data.user.pending_email) }}</p>
                <template v-if="state.page.screen === 'show-user'"><dl class="lu-profile-details"><div v-for="column in state.page.data.columns.filter(column => column.type !== 'avatar')" :key="column.key" class="lu-detail"><dt>{{ column.label }}</dt><dd><NativeCell :user="state.page.data.user" :column="column" :state="state" :store="store"/></dd></div></dl><p v-if="state.page.data.user.permissions?.length">{{ state.page.labels.direct_permissions }}: {{ state.page.data.user.permissions.map(item => item.label).join(', ') }}</p><p v-if="state.page.data.user.role_level">{{ state.page.labels.role_level.replace(':level', state.page.data.user.role_level) }}</p><NativeActions :user="state.page.data.user" :state="state" :store="store"/></template>
            </div>
            <NativeForm v-for="id in state.page.data.form_ids" :key="id" :form="state.page.forms[id]" :state="state" :store="store"/>
            <PackageSettings :state="state" :store="store"/>
            <div v-if="state.page.data.settings_actions?.some(action => !action.name.startsWith('package-'))" class="lu-pad lu-actions"><button v-for="action in state.page.data.settings_actions.filter(action => !action.name.startsWith('package-'))" :key="action.name" type="button" :class="['lu-button', action.class ?? 'lu-secondary']" :disabled="action.disabled || state.busy" @click="store.openSettingsAction(action.name)">{{ action.label }}</button></div>
        </section>
        <div v-if="store.activeForm()" class="lu-native-dialog-backdrop"><div class="lu-email-dialog lu-native-dialog" role="dialog" aria-modal="true" aria-labelledby="lu-native-action-title" tabindex="-1" data-lu-native-dialog><header class="lu-email-heading"><h2 id="lu-native-action-title">{{ store.activeForm().title }}</h2><button type="button" class="lu-email-close" :aria-label="state.page.labels.close" :disabled="state.busy" @click="store.closeDialog">{{ state.page.labels.close }}</button></header><div class="lu-email-body"><NativeForm :form="store.activeForm()" :state="state" :store="store" :dialog="true"/></div></div></div>
    </div>
</template>
