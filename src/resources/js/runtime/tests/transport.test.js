import assert from 'node:assert/strict';
import { test } from 'node:test';
import { displayValue, formData, formReady, getValue, sameOriginUrl, setValue } from '../shared.js';
import { createNativeStore } from '../store.js';

function environment(t) {
    const previousWindow = globalThis.window;
    const previousDocument = globalThis.document;
    globalThis.window = {
        location: { href: 'https://example.test/users', assign: value => { window.location.href = value; }, reload() {} },
        history: { pushState: (_, __, value) => { window.location.href = value; }, replaceState: (_, __, value) => { window.location.href = value; } },
        addEventListener() {}, scrollTo() {}, localStorage: { setItem() {} },
    };
    globalThis.document = { title: '', getElementById: () => ({ dataset: {} }), querySelector: () => null };
    t.after(() => { globalThis.window = previousWindow; globalThis.document = previousDocument; });
}

function page() {
    return {
        screen: 'users', title: 'Users', framework: 'bootstrap4', theme: 'light', csrf: 'session-token',
        urls: { users: 'https://example.test/users' }, labels: {}, flash: [],
        features: { search: true, search_debounce: null, sorting: true, filtering: true, columns: true, view_toggle: true, bulk: true, bulk_actions: [{ name: 'delete', form: 'bulk-delete' }] },
        data: {
            form_ids: [], navigation: [], columns: [{ key: 'id' }, { key: 'name' }],
            users: [{ id: 2, name: 'Target', selectable: true, actions: [{ name: 'delete', form: 'delete-user', url: 'https://example.test/users/2' }] }],
        },
        forms: {
            'delete-user': { id: 'delete-user', action: null, method: 'DELETE', fields: [], values: {}, errors: {} },
            'bulk-delete': { id: 'bulk-delete', action: 'https://example.test/users/bulk', method: 'POST', fields: [{ key: 'action', name: 'action', type: 'hidden' }, { key: 'ids', name: 'ids[]', type: 'hidden', multiple: true }], values: { action: 'delete', ids: [] }, errors: {} },
            account: { id: 'account', action: 'https://example.test/users/account', method: 'PUT', fields: [{ key: 'email', name: 'email', type: 'email', section: 'profile' }, { key: 'current_password', name: 'current_password', type: 'password', section: 'profile' }], values: { email: 'current@example.test', current_password: '' }, errors: {} },
        },
    };
}

test('form transport preserves nested names and method spoofing while omitting unlisted and disabled data', () => {
    const form = { method: 'PUT', fields: [
        { key: 'templates.welcome.subject', name: 'templates[welcome][subject]', type: 'text' },
        { key: 'access.edit.roles', name: 'access[edit][roles][]', type: 'select', multiple: true },
        { key: 'enabled', name: 'enabled', type: 'checkbox' },
        { key: 'disabled', name: 'disabled', type: 'text', disabled: true },
        { key: 'confirmation', name: 'confirmation', type: 'text', when: { key: 'enabled', equals: true } },
    ] };
    const values = { templates: { welcome: { subject: 'Welcome' } }, access: { edit: { roles: ['2', '3'] } }, enabled: false, disabled: 'private', confirmation: 'continue', injected: 'unexpected' };
    assert.deepEqual([...formData(form, values, 'csrf')], [['_token', 'csrf'], ['_method', 'PUT'], ['templates[welcome][subject]', 'Welcome'], ['access[edit][roles][]', '2'], ['access[edit][roles][]', '3'], ['enabled', '0']]);
});

test('security confirmation is exact and conditional, without validating business fields', () => {
    const form = { fields: [{ key: 'enabled', type: 'checkbox' }, { key: 'confirmation', type: 'text', required_text: 'permanently delete', when: { key: 'enabled', equals: true } }] };
    assert.equal(formReady(form, { enabled: false, confirmation: '' }), true);
    assert.equal(formReady(form, { enabled: true, confirmation: 'Permanently delete' }), false);
    assert.equal(formReady(form, { enabled: true, confirmation: 'permanently delete' }), true);
    assert.throws(() => setValue({}, '__proto__.polluted', true));
    assert.equal({}.polluted, undefined);
});

test('form values read only own fields and reject prototype paths before writing', () => {
    const values = Object.create({ inherited: 'unexpected', nested: { secret: 'unexpected' } });
    values.profile = { name: 'Morgan' };
    assert.equal(getValue(values, 'profile.name'), 'Morgan');
    assert.equal(getValue(values, 'inherited'), undefined);
    assert.equal(getValue(values, 'nested.secret'), undefined);
    assert.equal(getValue(values, 'profile.constructor'), undefined);
    setValue(values, 'nested.subject', 'Welcome');
    assert.equal(getValue(values, 'nested.subject'), 'Welcome');
    assert.equal(getValue(values, 'nested.secret'), undefined);
    for (const key of ['__proto__.polluted', 'constructor.prototype.polluted', 'profile.prototype.polluted']) {
        assert.throws(() => setValue(values, key, true), /Invalid field name/);
    }
    assert.equal({}.polluted, undefined);
});

test('inherited highlight pickers follow the selected light color while their submitted value stays nullable', t => {
    environment(t);
    const payload = page();
    const field = { key: 'profile_dark_gradient_highlight_color', name: 'profile_dark_gradient_highlight_color', type: 'color', nullable: true, fallback: '#ffffff', inherit_from: 'profile_gradient_highlight_color', section: 'appearance' };
    payload.forms.settings = { id: 'settings', action: '/users/settings', method: 'PUT', fields: [field, { key: 'profile_gradient_highlight_color', name: 'profile_gradient_highlight_color', type: 'color', section: 'appearance' }], values: { profile_gradient_highlight_color: '#123456', profile_dark_gradient_highlight_color: null }, errors: {} };
    const store = createNativeStore(payload, 'vue');
    const values = store.getSnapshot().values.settings;
    assert.equal(displayValue(field, values), '#123456');
    store.setValue('settings', 'profile_gradient_highlight_color', '#abcdef');
    assert.equal(displayValue(field, values), '#abcdef');
    assert.equal(formData(payload.forms.settings, values, 'csrf').get(field.name), '');
    store.toggleInheritance('settings', field.key);
    assert.equal(store.value('settings', field.key), '#abcdef');
    store.toggleInheritance('settings', field.key);
    assert.equal(store.value('settings', field.key), null);
});

test('password mismatch feedback waits for the configured pause and clears when passwords match', t => {
    environment(t);
    t.mock.timers.enable({ apis: ['setTimeout'] });
    const payload = page();
    payload.features.password_feedback = true;
    payload.data.password = { feedback_delay: 2000, settings: { min: 8, max: null }, strength_labels: ['Weak', 'Fair', 'Good', 'Strong'] };
    payload.forms.user = { id: 'user', fields: [{ key: 'password', type: 'password' }, { key: 'password_confirmation', type: 'password' }], values: { password: '', password_confirmation: '' }, errors: {} };
    const store = createNativeStore(payload, 'react');
    store.setValue('user', 'password', 'NativePass123!');
    t.mock.timers.tick(1999);
    assert.equal(store.getSnapshot().passwordMismatch.user, false);
    t.mock.timers.tick(1);
    assert.equal(store.getSnapshot().passwordMismatch.user, true);
    store.setValue('user', 'password_confirmation', 'NativePass123!');
    assert.equal(store.getSnapshot().passwordMismatch.user, false);
});

test('apply-all dialogs carry current selections and reset explicit confirmations when reopened', t => {
    environment(t);
    const payload = page();
    payload.forms.accounts = { id: 'accounts', fields: [{ key: 'enabled', type: 'checkbox' }], values: { enabled: false }, errors: {} };
    payload.forms.apply = { id: 'apply', fields: [{ key: 'enabled', type: 'checkbox' }, { key: 'confirmation', type: 'text', required_text: 'change' }, { key: 'acknowledgement', type: 'checkbox', required: true }], values: { enabled: false, confirmation: '', acknowledgement: false }, errors: {} };
    payload.data.settings_actions = [{ name: 'apply', form: 'apply', values_from: 'accounts' }];
    const store = createNativeStore(payload, 'svelte');
    store.setValue('accounts', 'enabled', true);
    store.openSettingsAction('apply');
    assert.equal(store.value('apply', 'enabled'), true);
    store.setValue('apply', 'confirmation', 'change');
    store.setValue('apply', 'acknowledgement', true);
    assert.equal(store.ready('apply'), true);
    store.closeDialog();
    store.openSettingsAction('apply');
    assert.equal(store.value('apply', 'enabled'), true);
    assert.equal(store.value('apply', 'confirmation'), '');
    assert.equal(store.value('apply', 'acknowledgement'), false);
    assert.equal(store.ready('apply'), false);
});

test('transport rejects cross-origin and executable action URLs before fetching', t => {
    environment(t);
    assert.equal(sameOriginUrl('/users').href, 'https://example.test/users');
    assert.throws(() => sameOriginUrl('https://external.test/users'));
    assert.throws(() => sameOriginUrl('javascript:alert(1)'));
    assert.throws(() => sameOriginUrl('//external.test/users'));
});

test('row and bulk dialogs accept only offered actions and known selectable users', t => {
    environment(t);
    const store = createNativeStore(page(), 'react');
    store.openUserAction('delete', '999');
    assert.equal(store.getSnapshot().activeForm, null);
    store.openUserAction('unlisted', 2);
    assert.equal(store.getSnapshot().activeForm, null);
    store.openUserAction('delete', 2);
    assert.equal(store.activeForm().action, 'https://example.test/users/2');
    store.closeDialog();
    store.getSnapshot().table.selected = ['2', '999'];
    store.openBulkAction('delete');
    assert.deepEqual(store.getSnapshot().values['bulk-delete'].ids, ['2']);
});

test('existing mutation URLs receive CSRF and backend validation errors remain attached to the form', async t => {
    environment(t);
    t.mock.method(globalThis, 'fetch', async (url, options) => {
        assert.equal(url.href, 'https://example.test/users/account');
        assert.equal(options.headers['X-LaravelUsers-Runtime'], 'vue');
        assert.equal(options.headers['X-CSRF-TOKEN'], 'session-token');
        assert.equal(options.credentials, 'same-origin');
        assert.equal(options.body.get('_method'), 'PUT');
        assert.equal(options.body.get('email'), 'invalid-address');
        return Response.json({ message: 'Invalid fields.', errors: { email: ['The email must be valid.'] } }, { status: 422 });
    });
    const store = createNativeStore(page(), 'vue');
    store.setValue('account', 'email', 'invalid-address');
    await store.submit('account');
    assert.deepEqual(store.getSnapshot().errors.account.email, ['The email must be valid.']);
    assert.equal(store.value('account', 'email'), 'invalid-address');
    assert.equal(store.getSnapshot().busy, false);
});

test('search navigates the existing native GET contract and updates browser history', async t => {
    environment(t);
    t.mock.method(globalThis, 'fetch', async (url, options) => {
        assert.equal(url.pathname, '/users');
        assert.equal(url.searchParams.get('user_search_box'), 'A & B');
        assert.equal(options.headers.Accept, 'application/json');
        assert.equal(options.headers['X-LaravelUsers-Runtime'], 'svelte');
        return Response.json(page());
    });
    const store = createNativeStore(page(), 'svelte');
    store.setSearch(' A & B ');
    await store.search();
    assert.equal(new URL(window.location.href).searchParams.get('user_search_box'), 'A & B');
    assert.equal(document.title, 'Users');
});

test('a pending package completion reloads real settings once and retains the Packages fragment', async t => {
    environment(t);
    t.mock.timers.enable({ apis: ['setTimeout'] });
    const payload = page();
    payload.urls.settings = 'https://example.test/users/settings';
    payload.data.package_operation = { id: 'setup-id', status_url: '/users/settings/packages/setup-id', status: 'queued', message: 'Waiting for the worker.' };
    const reload = t.mock.method(window.location, 'reload');
    const fetch = t.mock.method(globalThis, 'fetch', async () => Response.json({ ...payload.data.package_operation, status: 'completed', message: 'Setup completed.' }));
    const store = createNativeStore(payload, 'vue');
    assert.equal(reload.mock.calls.length, 0);
    t.mock.timers.tick(2000);
    await new Promise(resolve => setImmediate(resolve));
    assert.equal(store.getSnapshot().packageOperation.status, 'completed');
    assert.equal(reload.mock.calls.length, 1);
    assert.equal(window.location.href, 'https://example.test/users/settings#packages');
    t.mock.timers.tick(4000);
    await new Promise(resolve => setImmediate(resolve));
    assert.equal(fetch.mock.calls.length, 1);
    assert.equal(reload.mock.calls.length, 1);
});

test('cached completed package status does not reload on mount and manual refresh still requests a reload', t => {
    environment(t);
    const payload = page();
    payload.urls.settings = 'https://example.test/users/settings';
    payload.data.package_operation = { id: 'setup-id', status_url: '/users/settings/packages/setup-id', status: 'completed', message: 'Setup completed.' };
    const reload = t.mock.method(window.location, 'reload');
    const store = createNativeStore(payload, 'react');
    assert.equal(reload.mock.calls.length, 0);
    store.reloadPage();
    assert.equal(reload.mock.calls.length, 1);
    assert.equal(window.location.href, 'https://example.test/users/settings#packages');
});
