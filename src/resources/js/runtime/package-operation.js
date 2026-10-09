import { formData, request, sameOriginUrl } from './shared.js';

function requirementsValid(value) {
    return Boolean(value && ['checking', 'not_ready', 'completed'].includes(value.status) && typeof value.queue_ready === 'boolean');
}

function validateRequirements(payload, response) {
    if (response.status === 422) throw new Error(Object.values(payload.errors ?? {}).flat().join(' ') || payload.message);
    if (!requirementsValid(payload)) throw new Error('The application returned an unexpected requirements status.');
}

function validateOperation(payload) {
    if (!payload.id || !payload.status) throw new Error('The application returned an unexpected package status.');
}

export function createPackageRequirementsTracker(runtime, csrf, form, initial, onChange) {
    let status = initial;
    let timer;
    let polling = false;
    let stopped = false;
    const schedule = () => {
        clearTimeout(timer);
        if (!stopped && status?.status === 'checking') timer = setTimeout(() => tracker.verify(), 2000);
    };
    const tracker = {
        current() { return status; },
        update(value) {
            if (!requirementsValid(value)) return;
            stopped = false;
            status = { ...value };
            onChange(status);
            schedule();
        },
        async verify() {
            if (!form || polling || stopped) return;
            polling = true;
            onChange({ ...status, busy: true });
            try {
                const { payload, response } = await request(form.action, runtime, csrf(), { method: 'POST', body: formData(form, form.values, csrf()) });
                if (!payload) { tracker.stop(); return; }
                validateRequirements(payload, response);
                tracker.update(payload);
            } catch (error) {
                if ([401, 403, 404].includes(error.status)) tracker.stop();
                status = { ...status, transport_error: error.message };
                onChange(status);
            } finally { polling = false; schedule(); }
        },
        stop() { stopped = true; clearTimeout(timer); },
    };
    if (initial) tracker.update(initial);
    return tracker;
}

export function createPackageOperationTracker(runtime, csrf, initial, onChange) {
    let operation = null;
    let timer;
    let polling = false;
    let stopped = false;
    const pending = () => operation && ['queued', 'running'].includes(operation.status);
    const schedule = () => {
        clearTimeout(timer);
        if (!stopped && pending()) timer = setTimeout(() => tracker.poll(), 2000);
    };
    const reportPollingError = error => {
        if ([401, 403, 404].includes(error.status)) {
            operation = null;
            onChange(null);
            tracker.stop();
        } else {
            operation = { ...operation, transport_error: error.message };
            onChange(operation);
        }
    };
    const tracker = {
        current() { return operation; },
        update(value) {
            if (!value || !['queued', 'running', 'completed', 'failed'].includes(value.status) || !value.id || !value.status_url) return;
            sameOriginUrl(value.status_url);
            stopped = false;
            operation = { ...value };
            onChange(operation);
            schedule();
        },
        async poll() {
            if (polling || stopped || !pending()) return;
            polling = true;
            const id = operation.id;
            try {
                const { payload } = await request(operation.status_url, runtime, csrf());
                if (operation?.id !== id) return;
                if (!payload) { tracker.stop(); return; }
                validateOperation(payload);
                tracker.update({ ...payload, status_url: payload.status_url ?? operation.status_url });
            } catch (error) {
                reportPollingError(error);
            } finally { polling = false; schedule(); }
        },
        stop() { stopped = true; clearTimeout(timer); },
        clear() { tracker.stop(); operation = null; onChange(null); },
    };
    if (initial) tracker.update(initial);
    return tracker;
}
