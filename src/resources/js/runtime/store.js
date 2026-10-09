import { displayUsers, fieldVisible, formData, formReady, getValue, request, sameOriginUrl, setValue } from './shared.js';

function initialState(page) {
    return {
        page,
        values: Object.fromEntries(Object.entries(page.forms).map(([id, form]) => [id, structuredClone(form.values)])),
        errors: Object.fromEntries(Object.entries(page.forms).map(([id, form]) => [id, form.errors])),
        tabs: Object.fromEntries(Object.entries(page.forms).map(([id, form]) => [id, form.fields.find(field => field.type !== 'hidden')?.section ?? 'profile'])),
        table: { filter: '', sort: 'id', direction: 'desc', mode: 'table', selected: [], hiddenColumns: [] },
        search: new URL(window.location.href).searchParams.get('user_search_box') ?? '',
        activeForm: null,
        activeAction: null,
        preview: null,
        notice: null,
        busy: false,
    };
}

export function createNativeStore(page, runtime) {
    let state = initialState(page);
    let searchTimer;
    let requestId = 0;
    const listeners = new Set();
    const emit = () => { state = { ...state }; listeners.forEach(listener => listener(state)); };
    const report = error => { state.notice = { type: 'error', message: error.message }; };
    const load = payload => {
        state = initialState(payload);
        const root = document.getElementById('laravelusers');
        if (root) { root.dataset.luTheme = payload.theme; root.dataset.luCss = payload.framework; }
        document.title = payload.title;
    };
    const activate = action => {
        const form = state.page.forms[action.form];
        if (!form || form.disabled || action.disabled) return;
        state.activeForm = action.form;
        state.activeAction = action.url ?? null;
        for (const [key, value] of Object.entries(action.values ?? {})) setValue(state.values[action.form], key, structuredClone(value));
        state.preview = null;
        state.notice = null;
        emit();
    };
    const store = {
        subscribe(listener) { listeners.add(listener); listener(state); return () => listeners.delete(listener); },
        getSnapshot() { return state; },
        value(id, key) { return getValue(state.values[id], key); },
        setValue(id, key, value) {
            if (!state.page.forms[id]?.fields.some(field => field.key === key)) return;
            setValue(state.values[id], key, value);
            state.preview = null;
            if (state.errors[id]) delete state.errors[id][key];
            emit();
        },
        toggleInheritance(id, key) {
            const field = state.page.forms[id]?.fields.find(field => field.key === key);
            if (field?.nullable) store.setValue(id, key, store.value(id, key) === null ? field.fallback : null);
        },
        setTab(id, section) {
            if (state.page.forms[id]?.fields.some(field => field.section === section)) { state.tabs[id] = section; emit(); }
        },
        ready(id) { return formReady(state.page.forms[id], state.values[id]); },
        activeForm() {
            const form = state.page.forms[state.activeForm];
            return form ? { ...form, action: state.activeAction ?? form.action } : null;
        },
        openUserAction(name, id) {
            const user = (state.page.data.users ?? [state.page.data.user]).find(user => user && String(user.id) === String(id));
            const action = user?.actions?.find(action => action.name === name);
            if (action) activate(action);
        },
        openSettingsAction(name) {
            const action = state.page.data.settings_actions?.find(action => action.name === name);
            if (action) activate(action);
        },
        openBulkAction(name) {
            const action = state.page.features.bulk_actions.find(action => action.name === name);
            const ids = state.table.selected.filter(id => state.page.data.users.some(user => String(user.id) === String(id) && user.selectable));
            if (state.page.features.bulk && action && ids.length) activate({ ...action, values: { ids } });
        },
        closeDialog() {
            const form = store.activeForm();
            for (const field of form?.fields ?? []) if (field.type === 'password') setValue(state.values[form.id], field.key, '');
            state.activeForm = null;
            state.activeAction = null;
            state.preview = null;
            emit();
        },
        setFilter(value) { state.table.filter = value; emit(); },
        sortBy(key) {
            if (!state.page.features.sorting || !state.page.data.columns.some(column => column.key === key && column.sortable !== false)) return;
            state.table.direction = state.table.sort === key && state.table.direction === 'asc' ? 'desc' : 'asc';
            state.table.sort = key;
            emit();
        },
        setMode(mode) { if (state.page.features.view_toggle && ['table', 'cards'].includes(mode)) { state.table.mode = mode; emit(); } },
        toggleColumn(key) {
            const hidden = state.table.hiddenColumns;
            if (!state.page.features.columns || !state.page.data.columns.some(column => column.key === key)) return;
            if (hidden.includes(key)) state.table.hiddenColumns = hidden.filter(item => item !== key);
            else if (state.page.data.columns.length - hidden.length > 1) hidden.push(key);
            emit();
        },
        select(id, checked) {
            const user = state.page.data.users?.find(user => String(user.id) === String(id));
            if (!state.page.features.bulk || !user?.selectable) return;
            state.table.selected = checked ? [...new Set([...state.table.selected, String(id)])] : state.table.selected.filter(item => item !== String(id));
            emit();
        },
        selectAll() {
            if (!state.page.features.bulk) return;
            const ids = displayUsers(state.page, state.table).filter(user => user.selectable).map(user => String(user.id));
            state.table.selected = ids.every(id => state.table.selected.includes(id)) ? state.table.selected.filter(id => !ids.includes(id)) : [...new Set([...state.table.selected, ...ids])];
            emit();
        },
        setSearch(value) {
            state.search = value;
            clearTimeout(searchTimer);
            emit();
            if (state.page.features.search_debounce !== null) searchTimer = setTimeout(() => store.search(), state.page.features.search_debounce);
        },
        search() {
            clearTimeout(searchTimer);
            if (!state.page.features.search || !state.page.urls.users) return;
            const url = sameOriginUrl(state.page.urls.users);
            if (state.search.trim()) url.searchParams.set('user_search_box', state.search.trim().slice(0, 255));
            return store.navigate(url.href);
        },
        async navigate(value, historyMode = 'push') {
            clearTimeout(searchTimer);
            const id = ++requestId;
            state.busy = true;
            emit();
            try {
                const url = sameOriginUrl(value);
                const { payload } = await request(url, runtime, state.page.csrf);
                if (id !== requestId || !payload) return;
                if (!payload.screen) throw new Error(payload.message ?? 'The selected page is unavailable.');
                load(payload);
                if (historyMode !== 'none') window.history[historyMode === 'replace' ? 'replaceState' : 'pushState']({}, '', url.href);
                window.scrollTo({ top: 0, behavior: 'instant' });
                queueMicrotask(() => document.querySelector('[data-lu-native-heading]')?.focus());
            } catch (error) { if (id === requestId) report(error); }
            finally { if (id === requestId) { state.busy = false; emit(); } }
        },
        async submit(id, confirmed = false) {
            const form = state.activeForm === id ? store.activeForm() : state.page.forms[id];
            if (!form || state.busy || !store.ready(id)) return;
            if (form.confirm && !confirmed) { activate({ form: id }); return; }
            state.busy = true;
            state.notice = null;
            state.errors[id] = {};
            emit();
            try {
                let { payload, response } = await request(form.action, runtime, state.page.csrf, { method: 'POST', body: formData(form, state.values[id], state.page.csrf) });
                if (!payload) return;
                if (response.status === 422) { state.errors[id] = payload.errors ?? {}; report(new Error(payload.message)); return; }
                if (payload.screen) { load(payload); return; }
                if (form.async && payload.status_url) {
                    const statusUrl = sameOriginUrl(payload.status_url).href;
                    do {
                        state.notice = { type: 'status', message: payload.message ?? payload.status ?? '' };
                        emit();
                        await new Promise(resolve => setTimeout(resolve, 2000));
                        ({ payload } = await request(statusUrl, runtime, state.page.csrf));
                    } while (payload && ['queued', 'running'].includes(payload.status));
                    if (!payload) return;
                }
                if (payload.status === 'failed') throw new Error(payload.message);
                if (payload.redirect) { await store.navigate(payload.redirect); return; }
                if (form.async && payload.status === 'completed') { await store.navigate(window.location.href, 'replace'); return; }
                store.closeDialog();
                state.notice = { type: 'success', message: payload.message ?? '' };
            } catch (error) { report(error); }
            finally { state.busy = false; emit(); }
        },
        async preview(id) {
            const form = state.page.forms[id];
            if (!form?.preview || state.busy) return;
            state.busy = true;
            state.notice = null;
            emit();
            try {
                const { payload, response } = await request(form.preview, runtime, state.page.csrf, { method: 'POST', body: formData(form, state.values[id], state.page.csrf) });
                if (!payload) return;
                if (response.status === 422) { state.errors[id] = payload.errors ?? {}; report(new Error(payload.message)); return; }
                state.preview = { form: id, html: payload.html ?? '', recipient: payload.recipient ?? '' };
            } catch (error) { report(error); }
            finally { state.busy = false; emit(); }
        },
        editPreview() { state.preview = null; emit(); },
        async submitBanner() {
            const banner = state.page.data.banner;
            if (!banner || state.busy) return;
            state.busy = true;
            emit();
            try {
                const body = new FormData(); body.set('_token', state.page.csrf ?? '');
                const { payload } = await request(banner.action, runtime, state.page.csrf, { method: 'POST', body });
                if (payload?.redirect) await store.navigate(payload.redirect);
                else if (payload) await store.navigate(window.location.href, 'replace');
            } catch (error) { report(error); }
            finally { state.busy = false; emit(); }
        },
        toggleTheme() {
            state.page.theme = state.page.theme === 'dark' ? 'light' : 'dark';
            document.getElementById('laravelusers').dataset.luTheme = state.page.theme;
            try { window.localStorage.setItem('laravelusers-theme', state.page.theme); } catch {}
            emit();
        },
    };
    window.addEventListener('popstate', () => store.navigate(window.location.href, 'none'));
    return store;
}

export function visibleColumns(state) {
    return state.page.data.columns.filter(column => !state.table.hiddenColumns.includes(column.key));
}

export function formSections(form) {
    return [...new Set(form.fields.filter(field => field.type !== 'hidden').map(field => field.section))];
}

export function labelSection(section) {
    return section.replaceAll('-', ' ').replace(/\b\w/g, letter => letter.toUpperCase());
}

export { displayUsers, fieldVisible, getValue };
