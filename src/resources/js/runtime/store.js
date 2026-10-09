import { displayUsers, displayValue, fieldVisible, formData, formReady, getOwnValue, getValue, passwordFeedback, request, sameOriginUrl, setOwnValue, setValue } from './shared.js';
import { createPackageOperationTracker, createPackageRequirementsTracker } from './package-operation.js';

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
        packageOperation: page.data.package_operation ?? null,
        packageRequirements: page.data.packages?.requirements ?? null,
        dismissedMessages: [],
        appearancePreviewAvatars: page.data.appearance_preview?.avatars ?? {},
        appearancePreviewLoading: false,
        passwordMismatch: {},
    };
}

export function createNativeStore(page, runtime) {
    let state = initialState(page);
    let searchTimer;
    let requestId = 0;
    let previewRequestId = 0;
    const passwordTimers = new Map();
    const listeners = new Set();
    const emit = () => { state = { ...state }; listeners.forEach(listener => listener(state)); };
    const report = error => { state.notice = { type: 'error', message: error.message }; state.dismissedMessages = []; };
    const packageTracker = createPackageOperationTracker(runtime, () => state.page.csrf, page.data.package_operation, value => {
        const previous = state.packageOperation;
        state.packageOperation = value;
        emit();
        if (value?.status === 'completed' && previous?.id === value.id && ['queued', 'running'].includes(previous.status)) queueMicrotask(() => store.reloadPage());
    });
    let requirementsTracker;
    const trackRequirements = payload => {
        requirementsTracker?.stop();
        requirementsTracker = createPackageRequirementsTracker(runtime, () => state.page.csrf, payload.forms['package-verify'], payload.data.packages?.requirements, value => {
            state.packageRequirements = value;
            if (state.page.data.packages) state.page.data.packages.ready = value.queue_ready;
            for (const form of Object.values(state.page.forms)) if (form.requires_queue) form.disabled = form.blocked || !value.queue_ready;
            for (const action of state.page.data.settings_actions ?? []) if (action.name.startsWith('package-')) action.disabled = action.name === 'package-requirements' ? value.queue_ready : getOwnValue(state.page.forms, action.form)?.disabled;
            emit();
        });
    };
    const load = payload => {
        for (const timer of passwordTimers.values()) clearTimeout(timer);
        passwordTimers.clear();
        const previousActor = state.page.data.current_user?.id;
        state = initialState(payload);
        trackRequirements(payload);
        previewRequestId++;
        if (payload.data.package_operation) packageTracker.update(payload.data.package_operation);
        else if (previousActor === payload.data.current_user?.id) state.packageOperation = packageTracker.current();
        else packageTracker.clear();
        const root = document.getElementById('laravelusers');
        if (root) { root.dataset.luTheme = payload.theme; root.dataset.luCss = payload.framework; }
        document.title = payload.title;
    };
    const activate = action => {
        const form = getOwnValue(state.page.forms, action.form);
        if (!form || form.disabled || action.disabled) return;
        state.activeForm = action.form;
        state.activeAction = action.url ?? null;
        for (const field of form.fields) {
            if (field.required_text) setValue(getOwnValue(state.values, action.form), field.key, '');
            if (field.type === 'checkbox' && field.required) setValue(getOwnValue(state.values, action.form), field.key, false);
        }
        const source = getOwnValue(state.page.forms, action.values_from);
        if (source) for (const field of form.fields) if (source.fields.some(item => item.key === field.key)) setValue(getOwnValue(state.values, action.form), field.key, structuredClone(getValue(getOwnValue(state.values, source.id), field.key)));
        for (const [key, value] of Object.entries(action.values ?? {})) setValue(getOwnValue(state.values, action.form), key, structuredClone(value));
        state.preview = null;
        state.notice = null;
        emit();
    };
    const trackOperation = (form, payload) => {
        if (form.async && payload.status_url) {
            packageTracker.update(payload);
            store.closeDialog();
            return true;
        }
        return false;
    };
    const updateRequirements = (form, payload) => {
        if (form.async && typeof payload.queue_ready === 'boolean') {
            requirementsTracker.update(payload);
            store.closeDialog();
            return true;
        }
        return false;
    };
    const finishSubmission = async (form, payload) => {
        if (payload.redirect) { await store.navigate(payload.redirect); return; }
        if (form.async && payload.status === 'completed') { store.reloadPage(); return; }
        store.closeDialog();
        state.notice = { type: 'success', message: payload.message ?? '' };
    };
    const handleSubmission = async (id, form, payload, response) => {
        if (!payload) return;
        if (response.status === 422) { setOwnValue(state.errors, id, payload.errors ?? {}); report(new Error([payload.message, ...Object.values(payload.errors ?? {}).flat()].filter(Boolean).join(' '))); return; }
        if (payload.screen) { load(payload); return; }
        if (trackOperation(form, payload)) return;
        if (payload.status === 'failed') throw new Error(payload.message);
        if (updateRequirements(form, payload)) return;
        await finishSubmission(form, payload);
    };
    const store = {
        subscribe(listener) { listeners.add(listener); listener(state); return () => listeners.delete(listener); },
        getSnapshot() { return state; },
        verifyRequirements() { return requirementsTracker.verify(); },
        value(id, key) { return getValue(getOwnValue(state.values, id), key); },
        setValue(id, key, value) {
            if (!getOwnValue(state.page.forms, id)?.fields.some(field => field.key === key)) return;
            setValue(getOwnValue(state.values, id), key, value);
            state.preview = null;
            if (getOwnValue(state.errors, id)) Reflect.deleteProperty(getOwnValue(state.errors, id), key);
            if (state.page.features.password_feedback && ['password', 'password_confirmation'].includes(key)) {
                clearTimeout(passwordTimers.get(id));
                setOwnValue(state.passwordMismatch, id, false);
                const feedback = passwordFeedback(getOwnValue(state.values, id).password, getOwnValue(state.values, id).password_confirmation, state.page.data.password);
                if (feedback?.mismatch) passwordTimers.set(id, setTimeout(() => { setOwnValue(state.passwordMismatch, id, true); emit(); }, state.page.data.password.feedback_delay));
            }
            emit();
            if (id === 'settings' && key === 'avatar_source') store.previewAvatars();
        },
        toggleInheritance(id, key) {
            const field = getOwnValue(state.page.forms, id)?.fields.find(field => field.key === key);
            if (field?.nullable) store.setValue(id, key, store.value(id, key) === null ? displayValue(field, getOwnValue(state.values, id)) : null);
        },
        setTab(id, section) {
            if (getOwnValue(state.page.forms, id)?.fields.some(field => field.section === section)) { setOwnValue(state.tabs, id, section); emit(); }
        },
        ready(id) { return formReady(getOwnValue(state.page.forms, id), getOwnValue(state.values, id)); },
        dismissMessage(index) { state.dismissedMessages.push(index); emit(); },
        dismissToast(id) { state.page.data.toasts = (state.page.data.toasts ?? []).filter(toast => String(toast.id) !== String(id)); emit(); },
        activeForm() {
            const form = getOwnValue(state.page.forms, state.activeForm);
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
            for (const field of form?.fields ?? []) {
                if (field.type === 'password' || field.required_text) setValue(getOwnValue(state.values, form.id), field.key, '');
                if (field.type === 'checkbox' && field.required) setValue(getOwnValue(state.values, form.id), field.key, false);
            }
            if (form) { clearTimeout(passwordTimers.get(form.id)); setOwnValue(state.passwordMismatch, form.id, false); }
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
        clearSearch() { state.search = ''; return store.search(); },
        reloadPage() {
            const url = sameOriginUrl(state.page.urls.settings ?? window.location.href);
            url.hash = 'packages';
            window.history.replaceState({}, '', url.href);
            window.location.reload();
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
                if (historyMode === 'replace') window.history.replaceState({}, '', url.href);
                else if (historyMode !== 'none') window.history.pushState({}, '', url.href);
                window.scrollTo({ top: 0, behavior: 'instant' });
                queueMicrotask(() => document.querySelector('[data-lu-native-heading]')?.focus());
            } catch (error) { if (id === requestId) report(error); }
            finally { if (id === requestId) { state.busy = false; emit(); } }
        },
        async submit(id, confirmed = false) {
            const form = state.activeForm === id ? store.activeForm() : getOwnValue(state.page.forms, id);
            if (!form || state.busy || !store.ready(id)) return;
            if (form.confirm && !confirmed) { activate({ form: id }); return; }
            state.busy = true;
            state.notice = null;
            setOwnValue(state.errors, id, {});
            emit();
            try {
                let { payload, response } = await request(form.action, runtime, state.page.csrf, { method: 'POST', body: formData(form, getOwnValue(state.values, id), state.page.csrf) });
                await handleSubmission(id, form, payload, response);
            } catch (error) { report(error); }
            finally { state.busy = false; emit(); }
        },
        async preview(id) {
            const form = getOwnValue(state.page.forms, id);
            if (!form?.preview || state.busy) return;
            state.busy = true;
            state.notice = null;
            emit();
            try {
                const { payload, response } = await request(form.preview, runtime, state.page.csrf, { method: 'POST', body: formData(form, getOwnValue(state.values, id), state.page.csrf) });
                if (!payload) return;
                if (response.status === 422) { setOwnValue(state.errors, id, payload.errors ?? {}); report(new Error([payload.message, ...Object.values(payload.errors ?? {}).flat()].filter(Boolean).join(' '))); return; }
                state.preview = { form: id, html: payload.html ?? '', recipient: payload.recipient ?? '' };
            } catch (error) { report(error); }
            finally { state.busy = false; emit(); }
        },
        editPreview() { state.preview = null; emit(); },
        async previewAvatars() {
            const preview = state.page.data.appearance_preview;
            if (!preview || !state.page.forms.settings) return;
            const id = ++previewRequestId;
            const body = new FormData();
            body.set('_token', state.page.csrf ?? '');
            body.set('avatar_source', state.values.settings.avatar_source);
            state.appearancePreviewLoading = true;
            emit();
            try {
                const { payload } = await request(preview.url, runtime, state.page.csrf, { method: 'POST', body });
                if (id !== previewRequestId || !payload) return;
                if (payload.errors) throw new Error(Object.values(payload.errors).flat().join(' '));
                state.appearancePreviewAvatars = Object.fromEntries(['profile', 'edit', 'profile_dark', 'edit_dark'].filter(kind => getOwnValue(payload.avatars, kind)).map(kind => [kind, getOwnValue(payload.avatars, kind)]));
            } catch (error) { if (id === previewRequestId) report(error); }
            finally { if (id === previewRequestId) { state.appearancePreviewLoading = false; emit(); } }
        },
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
            try { window.localStorage.setItem('laravelusers-theme', state.page.theme); } catch { /* Storage can be blocked while the in-memory theme still changes. */ }
            emit();
        },
    };
    trackRequirements(page);
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
