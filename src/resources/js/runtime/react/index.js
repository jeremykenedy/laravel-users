import React from 'react';
import { createRoot } from 'react-dom/client';
import UsersApp from './UsersApp.jsx';
import { createNativeStore } from '../store.js';

const target = document.getElementById('lu-native-app');
const data = document.getElementById('lu-native-page');
if (target && data) createRoot(target).render(React.createElement(UsersApp, { store: createNativeStore(JSON.parse(data.textContent), 'react') }));
