<script setup>
import { computed } from 'vue';
import { displayUsers, visibleColumns } from '../store.js';
import NativeAvatar from './NativeAvatar.vue';
import NativeCell from './NativeCell.vue';
import NativeActions from './NativeActions.vue';
import NativeIcon from './NativeIcon.vue';
const props = defineProps({ state: Object, store: Object });
const users = computed(() => displayUsers(props.state.page, props.state.table));
const columns = computed(() => visibleColumns(props.state));
</script>

<template>
    <div data-lu-native-table>
        <div class="lu-pad lu-actions lu-table-controls">
            <label v-if="state.page.features.filtering" class="lu-field" for="lu-native-table-filter"><span>{{ state.page.labels.filter }}</span><input id="lu-native-table-filter" class="lu-input" type="search" maxlength="255" :value="state.table.filter" @input="store.setFilter($event.target.value)"></label>
            <div v-if="state.page.features.view_toggle" class="lu-actions" role="group" :aria-label="state.page.labels.view"><button v-for="mode in ['table', 'cards']" :key="mode" type="button" class="lu-button lu-secondary" :aria-pressed="state.table.mode === mode" @click="store.setMode(mode)">{{ state.page.labels[mode] }}</button></div>
            <details v-if="state.page.features.columns" class="lu-column-controls"><summary>{{ state.page.labels.columns }}</summary><div class="lu-pad"><label v-for="column in state.page.data.columns" :key="column.key" class="lu-check"><input type="checkbox" :checked="!state.table.hiddenColumns.includes(column.key)" @change="store.toggleColumn(column.key)"><span>{{ column.label }}</span></label></div></details>
        </div>
        <div v-if="state.page.features.bulk" class="lu-pad lu-actions"><span role="status">{{ state.page.labels.selected.replace(':count', state.table.selected.length) }}</span><button v-for="action in state.page.features.bulk_actions" :key="action.name" type="button" :class="['lu-button', action.class ?? 'lu-secondary']" :disabled="!state.table.selected.length || action.disabled || state.busy" @click="store.openBulkAction(action.name)"><NativeIcon :action="action.name" :enabled="state.page.features.icons"/><span>{{ action.label }}</span></button></div>
        <div v-if="state.table.mode === 'cards' && state.page.features.view_toggle" class="lu-native-cards lu-pad">
            <article v-for="user in users" :key="user.id" :class="['lu-panel lu-pad lu-native-user-card', state.page.classes.panel]">
                <header class="lu-heading"><NativeAvatar :avatar="user.avatar"/><h2><a v-if="user.urls?.show" :href="user.urls.show" @click.prevent="store.navigate(user.urls.show)">{{ user.name }}</a><span v-else>{{ user.name }}</span></h2><label v-if="state.page.features.bulk" class="lu-check"><input type="checkbox" :value="user.id" :checked="state.table.selected.includes(String(user.id))" :disabled="!user.selectable" @change="store.select(user.id, $event.target.checked)"><span class="lu-sr-only">{{ state.page.labels.select_user.replace(':name', user.name) }}</span></label></header>
                <dl class="lu-details"><div v-for="column in columns" :key="column.key"><dt>{{ column.label }}</dt><dd><NativeCell :user="user" :column="column" :state="state" :store="store"/></dd></div></dl>
                <NativeActions :user="user" :state="state" :store="store"/>
            </article>
            <p v-if="!users.length" role="status">{{ state.page.labels.empty }}</p>
        </div>
        <div v-else :class="['lu-scroll', state.page.classes.scroll]"><table :class="['lu-native-table', state.page.classes.table]">
            <caption>{{ state.page.labels.directory }}</caption>
            <thead><tr><th v-if="state.page.features.bulk" scope="col"><button type="button" class="lu-button lu-secondary" @click="store.selectAll">{{ state.page.labels.select_all }}</button></th><th v-for="column in columns" :key="column.key" scope="col" :aria-sort="state.table.sort === column.key ? (state.table.direction === 'asc' ? 'ascending' : 'descending') : undefined"><button v-if="state.page.features.sorting && column.sortable !== false" type="button" class="lu-native-sort" @click="store.sortBy(column.key)">{{ column.label }}</button><span v-else>{{ column.label }}</span></th><th scope="col">{{ state.page.labels.actions }}</th></tr></thead>
            <tbody><tr v-for="user in users" :key="user.id"><td v-if="state.page.features.bulk"><label class="lu-check"><input type="checkbox" :value="user.id" :checked="state.table.selected.includes(String(user.id))" :disabled="!user.selectable" @change="store.select(user.id, $event.target.checked)"><span class="lu-sr-only">{{ state.page.labels.select_user.replace(':name', user.name) }}</span></label></td><td v-for="column in columns" :key="column.key"><NativeCell :user="user" :column="column" :state="state" :store="store"/></td><td><NativeActions :user="user" :state="state" :store="store"/></td></tr><tr v-if="!users.length"><td :colspan="columns.length + 1 + Number(state.page.features.bulk)" role="status">{{ state.page.labels.empty }}</td></tr></tbody>
        </table></div>
    </div>
</template>
