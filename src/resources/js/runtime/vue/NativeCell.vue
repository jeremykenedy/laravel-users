<script setup>
import NativeAvatar from './NativeAvatar.vue';
import { cellText, dateLabel } from '../shared.js';
defineProps({ user: Object, column: Object, state: Object, store: Object });
</script>

<template>
    <NativeAvatar v-if="column.type === 'avatar'" :avatar="user.avatar"/>
    <dl v-else-if="column.type === 'activity'" class="lu-details">
        <div><dt>{{ state.page.labels.last_login_at }}</dt><dd><time v-if="user.activity?.last_login_at" :datetime="user.activity.last_login_at">{{ dateLabel(user.activity.last_login_at, state.page) }}</time><span v-else>{{ state.page.labels.no_logins }}</span></dd></div>
        <template v-for="key in ['device', 'os', 'browser', 'ip_address']" :key="key"><div v-if="user.activity?.[key]"><dt>{{ state.page.labels[key] }}</dt><dd>{{ user.activity[key] }}</dd></div></template>
    </dl>
    <time v-else-if="column.type === 'date' && cellText(user, column)" :datetime="cellText(user, column)">{{ dateLabel(cellText(user, column), state.page) }}</time>
    <a v-else-if="column.key === 'name' && user.urls?.show" :href="user.urls.show" @click.prevent="store.navigate(user.urls.show)">{{ user.name }}</a>
    <a v-else-if="column.key === 'email' && column.linked" :href="'mailto:' + user.email">{{ user.email }}</a>
    <span v-else-if="column.type === 'presence'" :class="['lu-badge', user.activity?.online ? 'lu-online' : '']">{{ user.activity?.online ? state.page.labels.online : state.page.labels.offline }}</span>
    <span v-else>{{ cellText(user, column) }}</span>
</template>
