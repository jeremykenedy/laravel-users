import { mount } from 'svelte';
import UsersApp from './UsersApp.svelte';
import { createNativeStore } from '../store.js';

const target = document.getElementById('lu-native-app');
const data = document.getElementById('lu-native-page');
if (target && data) mount(UsersApp, { target, props: { store: createNativeStore(JSON.parse(data.textContent), 'svelte') } });
