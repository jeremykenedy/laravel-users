import { observeDialogs, request, sameOriginUrl } from './shared.js';

let disconnect;
function initialize() {
    disconnect?.();
    const root = document.querySelector('#laravelusers[data-lu-runtime="livewire"]');
    if (!root) return;
    disconnect = observeDialogs(root, dialog => dialog.querySelector('[wire\\:click="closeDialog"]')?.click());
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
        let { payload } = await request(form.action, 'livewire', token, { method: 'POST', body: new FormData(form) });
        if (!payload) return;
        if (payload.errors) { notice(form, Object.values(payload.errors).flat().join(' '), true); return; }
        while (payload.status_url || ['queued', 'running'].includes(payload.status)) {
            const url = payload.status_url;
            if (!url) break;
            notice(form, payload.message ?? payload.status ?? '');
            await new Promise(resolve => setTimeout(resolve, 2000));
            const result = await request(url, 'livewire', token);
            if (!result.payload) return;
            payload = { ...result.payload, status_url: ['queued', 'running'].includes(result.payload.status) ? url : null };
        }
        notice(form, payload.message ?? '', payload.status === 'failed');
        if (payload.status === 'completed') window.location.reload();
    } catch (error) {
        notice(form, error.message, true);
    } finally {
        buttons.forEach(button => { button.disabled = false; });
    }
}

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
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initialize, { once: true });
else initialize();
