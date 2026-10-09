<script setup>
import NativeAvatar from './NativeAvatar.vue';
import NativeCell from './NativeCell.vue';
defineProps({ state: Object, store: Object });
</script>

<template>
    <nav v-if="state.page.features.show_header && !state.page.features.custom_header" class="lu-toolbar" :aria-label="state.page.labels.navigation">
        <a class="lu-brand" :href="state.page.data.home">{{ state.page.labels.package_name }}</a>
        <div class="lu-actions">
            <details v-if="state.page.data.current_user" class="lu-user-menu"><summary class="lu-user-menu-toggle"><NativeAvatar :avatar="state.page.data.current_user.avatar"/><span>{{ state.page.data.current_user.name }}</span><span class="lu-user-menu-caret" aria-hidden="true"></span></summary><div class="lu-user-menu-items">
                <div v-if="state.page.data.current_user.activity" class="lu-user-menu-login" :aria-label="state.page.labels.login_details"><NativeCell :user="state.page.data.current_user" :column="{key: 'activity', type: 'activity'}" :state="state" :store="store"/></div>
                <a v-if="state.page.urls.users" :href="state.page.urls.users" @click.prevent="store.navigate(state.page.urls.users)">{{ state.page.labels.manage_users }}</a>
                <a v-if="state.page.urls.account" :href="state.page.urls.account" @click.prevent="store.navigate(state.page.urls.account)">{{ state.page.labels.account_menu_label }}</a>
                <form v-if="state.page.urls.logout" method="POST" :action="state.page.urls.logout"><input type="hidden" name="_token" :value="state.page.csrf"><button type="submit">{{ state.page.labels.logout }}</button></form>
            </div></details>
            <button v-if="state.page.features.theme_toggle" type="button" class="lu-button lu-secondary" @click="store.toggleTheme">{{ state.page.theme === 'dark' ? state.page.labels.theme_light : state.page.labels.theme_dark }}</button>
        </div>
    </nav>
    <nav v-if="state.page.features.breadcrumbs" class="lu-breadcrumbs lu-native-breadcrumbs" :aria-label="state.page.labels.breadcrumbs"><ol><li v-for="(crumb, index) in state.page.data.breadcrumbs" :key="index" :aria-current="index === state.page.data.breadcrumbs.length - 1 ? 'page' : undefined"><svg v-if="index" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m9 5 7 7-7 7"/></svg><a v-if="index < state.page.data.breadcrumbs.length - 1 && crumb.url" :href="crumb.url" @click="event => { if (crumb.native !== false) { event.preventDefault(); store.navigate(crumb.url); } }">{{ crumb.label }}</a><span v-else>{{ crumb.label }}</span></li></ol></nav>
</template>
