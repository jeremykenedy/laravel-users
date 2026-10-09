import { displayValue, observeDialogs, passwordFeedback, request, sameOriginUrl, statusIcon } from './shared.js';
import { createPackageOperationTracker, createPackageRequirementsTracker } from './package-operation.js';
import { previewStyle, profileStyle } from './appearance.js';

let disconnect;
let page;
let root;
let actor;
let packageTracker;
let requirementsTracker;
let previewRequestId = 0;
let previewSource;
const feedbackTimers = new WeakMap();

function renderPackageStatus(operation) {
    const output = root?.querySelector('[data-lu-native-package-status]');
    if (!output) return;
    output.hidden = !operation;
    if (!operation) return;
    output.dataset.luPackageState = operation.status;
    output.setAttribute('role', operation.status === 'failed' ? 'alert' : 'status');
    output.querySelector('svg path').setAttribute('d', statusIcon(operation.status));
    output.querySelector('svg').classList.toggle('lu-package-spinner', operation.status === 'running');
    output.querySelector('[data-lu-native-package-message]').textContent = operation.message;
    const error = output.querySelector('[data-lu-native-package-error]');
    error.hidden = !operation.transport_error;
    error.textContent = operation.transport_error ?? '';
    output.querySelector('[data-lu-native-package-refresh]').hidden = !['completed', 'failed'].includes(operation.status);
}

function initialize() {
    disconnect?.();
    root = document.querySelector('#laravelusers[data-lu-runtime="livewire"]');
    if (!root) return;
    page = JSON.parse(root.querySelector('[data-lu-native-client]').textContent);
    const nextActor = page.data.current_user?.id;
    if (!packageTracker || nextActor !== actor) {
        packageTracker?.stop();
        packageTracker = createPackageOperationTracker('livewire', () => page.csrf, page.data.package_operation, renderPackageStatus);
    } else if (page.data.package_operation) packageTracker.update(page.data.package_operation);
    actor = nextActor;
    requirementsTracker?.stop();
    requirementsTracker = createPackageRequirementsTracker('livewire', () => page.csrf, page.forms['package-verify'], page.data.packages?.requirements, value => {
        const output = root.querySelector('[data-lu-native-package-requirements]');
        if (!output) return;
        output.hidden = false;
        const status = value.status === 'checking' ? 'running' : value.queue_ready ? 'completed' : 'failed';
        output.querySelector('svg path').setAttribute('d', statusIcon(status));
        output.querySelector('svg').classList.toggle('lu-package-spinner', status === 'running');
        output.querySelector('[data-lu-native-requirements-message]').textContent = value.message ?? '';
        const error = output.querySelector('[data-lu-native-requirements-error]');
        error.hidden = !value.transport_error;
        error.textContent = value.transport_error ?? '';
        root.querySelector('[data-lu-native-verify-requirements]').disabled = Boolean(value.busy);
        if (value.queue_ready !== page.data.packages.ready) window.location.reload();
    });
    renderPackageStatus(packageTracker.current());
    previewRequestId++;
    previewSource = page.forms.settings?.values.avatar_source;
    enhanceForms();
    disconnect = observeDialogs(root, dialog => dialog.querySelector('[wire\\:click="closeDialog"]')?.click());
}

function formValues(form) {
    const definition = page.forms[form.dataset.luNativeForm];
    const values = structuredClone(definition?.values ?? {});
    for (const field of definition?.fields ?? []) {
        if (field.key.includes('.')) continue;
        const control = [...form.elements].find(control => control.name === field.name && control.type !== 'hidden');
        if (!control) continue;
        if (field.nullable && control.disabled) { values[field.key] = null; continue; }
        if (control.disabled) continue;
        values[field.key] = control.type === 'checkbox' ? control.checked : control.value;
    }
    return values;
}

function enhanceForms() {
    if (!page || !root) return;
    for (const form of root.querySelectorAll('form[data-lu-native-form]')) {
        const values = formValues(form);
        for (const field of page.forms[form.dataset.luNativeForm]?.fields ?? []) {
            if (!field.nullable || values[field.key] !== null) continue;
            const control = [...form.elements].find(control => control.name === field.name && control.type !== 'hidden');
            if (control) control.value = displayValue(field, values);
        }
    }
    const form = root.querySelector('form[data-lu-native-form="settings"]');
    if (form) for (const card of form.querySelectorAll('[data-lu-native-preview-card]')) {
        for (const [key, value] of Object.entries(previewStyle(page, formValues(form), card.dataset.luNativePreviewCard))) card.style.setProperty(key, value);
    }
    const profile = root.querySelector('[data-lu-native-user-profile]');
    if (profile && page.data.user) {
        const editing = page.screen === 'edit-user';
        const editor = root.querySelector(`form[data-lu-native-form="${editing ? 'user' : 'account-appearance'}"]`);
        for (const [key, value] of Object.entries(profileStyle(page, page.data.user, editing, editor ? formValues(editor) : null))) profile.style.setProperty(key, value);
    }
    for (const form of root.querySelectorAll('form[data-lu-native-form]')) updatePassword(form, false);
}

function updatePassword(form, delayed = true) {
    const feedback = passwordFeedback(form.querySelector('[name="password"]')?.value ?? '', form.querySelector('[name="password_confirmation"]')?.value ?? '', page?.data.password);
    if (!feedback) return;
    const meter = form.querySelector('[data-lu-password-meter]');
    if (meter) {
        meter.hidden = !form.querySelector('[name="password"]')?.value;
        meter.querySelector('meter').value = feedback.score;
        meter.querySelector('[data-lu-password-strength]').textContent = feedback.label;
    }
    const mismatch = form.querySelector('[data-lu-password-mismatch]');
    if (!mismatch) return;
    clearTimeout(feedbackTimers.get(form));
    feedbackTimers.set(form, setTimeout(() => { mismatch.hidden = !feedback.mismatch; }, delayed ? page.data.password.feedback_delay : 0));
}

async function previewAvatars(form) {
    const source = form.querySelector('[name="avatar_source"]')?.value;
    const preview = page.data.appearance_preview;
    if (!preview || !source || source === previewSource) return;
    previewSource = source;
    const id = ++previewRequestId;
    const body = new FormData();
    body.set('_token', page.csrf ?? '');
    body.set('avatar_source', source);
    try {
        const { payload } = await request(preview.url, 'livewire', page.csrf, { method: 'POST', body });
        if (id !== previewRequestId || !payload) return;
        if (payload.errors) throw new Error(Object.values(payload.errors).flat().join(' '));
        for (const card of form.querySelectorAll('[data-lu-native-preview-card]')) {
            const avatar = payload.avatars?.[card.dataset.luNativePreviewCard]?.avatar;
            if (!avatar) continue;
            const output = card.querySelector('[data-lu-native-preview-avatar]');
            output.replaceChildren();
            const span = document.createElement('span');
            span.className = 'lu-avatar'; span.setAttribute('aria-hidden', 'true');
            const fallback = document.createElement('span'); fallback.textContent = avatar.fallback === 'initials' ? avatar.initials : '';
            span.append(fallback);
            if (avatar.fallback !== 'initials') {
                const icon = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
                icon.setAttribute('viewBox', '0 0 24 24'); icon.setAttribute('fill', 'none'); icon.setAttribute('stroke', 'currentColor');
                const path = document.createElementNS('http://www.w3.org/2000/svg', 'path'); path.setAttribute('d', 'M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8M4 21v-2a8 8 0 0 1 16 0v2');
                icon.append(path); span.append(icon);
            }
            if (avatar.src) {
                const img = document.createElement('img'); img.src = avatar.src; img.alt = ''; img.loading = 'lazy'; img.referrerPolicy = 'no-referrer';
                img.addEventListener('error', () => { img.hidden = true; }); span.append(img);
            }
            output.append(span);
        }
    } catch (error) { if (id === previewRequestId) notice(form, error.message, true); }
}

function notice(form, message, error = false) {
    let output = form.querySelector('[data-lu-native-notice]');
    if (!output) {
        output = document.createElement('p');
        output.dataset.luNativeNotice = '';
        form.append(output);
    }
    output.setAttribute('role', error ? 'alert' : 'status');
    output.textContent = message;
}

async function submitAsync(form) {
    const token = form.querySelector('[name="_token"]')?.value;
    const buttons = form.querySelectorAll('button');
    buttons.forEach(button => { button.disabled = true; });
    try {
        const { payload } = await request(form.action, 'livewire', token, { method: 'POST', body: new FormData(form) });
        if (!payload) return;
        if (payload.errors) { notice(form, Object.values(payload.errors).flat().join(' '), true); return; }
        if (payload.status_url) {
            packageTracker.update(payload);
            form.closest('[data-lu-native-dialog]')?.querySelector('[wire\\:click="closeDialog"]')?.click();
            return;
        }
        if (typeof payload.queue_ready === 'boolean') {
            requirementsTracker.update(payload);
            form.closest('[data-lu-native-dialog]')?.querySelector('[wire\\:click="closeDialog"]')?.click();
            return;
        }
        notice(form, payload.message ?? '', payload.status === 'failed');
    } catch (error) {
        notice(form, error.message, true);
    } finally {
        buttons.forEach(button => { button.disabled = false; });
    }
}

function installListeners() {
document.addEventListener('laravelusers-native-submit', event => {
    const forms = [...document.querySelectorAll('#laravelusers[data-lu-runtime="livewire"] form[data-lu-native-form]')];
    const form = forms.find(form => form.dataset.luNativeForm === event.detail.form && Boolean(form.hasAttribute('data-lu-dialog-form')) === Boolean(event.detail.dialog));
    if (!form || !form.reportValidity()) return;
    try {
        sameOriginUrl(form.action);
        if (form.hasAttribute('data-lu-native-async')) submitAsync(form);
        else HTMLFormElement.prototype.submit.call(form);
    } catch (error) {
        notice(form, error.message, true);
    }
});

document.addEventListener('click', async event => {
    if (event.target.closest('[data-lu-native-verify-requirements]')) { requirementsTracker.verify(); return; }
    const button = event.target.closest('button[data-lu-native-preview],button[data-lu-preview-edit]');
    const form = button?.closest('form[data-lu-preview-url]');
    if (!form || !form.closest('#laravelusers[data-lu-runtime="livewire"]')) return;
    const preview = form.querySelector('section[data-lu-native-preview]');
    const editor = form.querySelector('[data-lu-native-editor]');
    const errorOutput = form.querySelector('[data-lu-preview-error]');
    if (button.hasAttribute('data-lu-preview-edit')) { preview.hidden = true; editor.hidden = false; return; }
    if (!form.reportValidity()) return;
    button.disabled = true;
    errorOutput.hidden = true;
    try {
        const { payload } = await request(form.dataset.luPreviewUrl, 'livewire', form.querySelector('[name="_token"]')?.value, { method: 'POST', body: new FormData(form) });
        if (!payload) return;
        if (payload.errors) throw new Error(Object.values(payload.errors).flat().join(' '));
        form.querySelector('[data-lu-preview-frame]').srcdoc = payload.html ?? '';
        form.querySelector('[data-lu-preview-recipient]').textContent = payload.recipient ?? '';
        editor.hidden = true;
        preview.hidden = false;
    } catch (error) {
        errorOutput.textContent = error.message;
        errorOutput.hidden = false;
    } finally {
        button.disabled = false;
    }
});

document.addEventListener('livewire:navigated', initialize);
document.addEventListener('laravelusers-native-theme', event => {
    if (root && ['light', 'dark'].includes(event.detail.theme)) {
        root.dataset.luTheme = event.detail.theme;
        try { window.localStorage.setItem('laravelusers-theme', event.detail.theme); } catch {}
    }
});
document.addEventListener('input', event => {
    const form = event.target.closest('form[data-lu-native-form]');
    if (!form || !form.closest('#laravelusers[data-lu-runtime="livewire"]')) return;
    enhanceForms();
    if (['password', 'password_confirmation'].includes(event.target.name)) updatePassword(form);
});
document.addEventListener('change', event => {
    const form = event.target.closest('form[data-lu-native-form="settings"]');
    if (form && event.target.name === 'avatar_source') previewAvatars(form);
});
const hooks = () => window.Livewire?.hook('morphed', enhanceForms);
if (window.Livewire) hooks();
else document.addEventListener('livewire:init', hooks, { once: true });
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initialize, { once: true });
else initialize();
}

if (!window.laravelUsersNativeLivewire) {
    window.laravelUsersNativeLivewire = true;
    installListeners();
}
