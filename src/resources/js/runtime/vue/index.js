import { createApp } from 'vue';
import UsersApp from './UsersApp.vue';
import { createNativeStore } from '../store.js';

const target = document.getElementById('lu-native-app');
const data = document.getElementById('lu-native-page');
if (target && data) createApp(UsersApp, { store: createNativeStore(JSON.parse(data.textContent), 'vue') }).mount(target);
