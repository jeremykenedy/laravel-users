export function sameOriginUrl(value, origin = window.location.href) {
    const url = new URL(value, origin);
    const current = new URL(origin);
    if (url.origin !== current.origin || !['http:', 'https:'].includes(url.protocol)) throw new Error('The action URL must belong to this application.');
    return url;
}

export function getValue(values, key) {
    return key.split('.').reduce((value, part) => value?.[part], values);
}

export function setValue(values, key, value) {
    const parts = key.split('.');
    if (parts.some(part => ['__proto__', 'constructor', 'prototype'].includes(part))) throw new Error('Invalid field name.');
    let target = values;
    for (const part of parts.slice(0, -1)) target = target[part] ??= {};
    target[parts.at(-1)] = value;
}

export function fieldVisible(field, values) {
    return !field.when || String(getValue(values, field.when.key)) === String(field.when.equals);
}

export function formReady(form, values) {
    return !form.disabled && form.fields.every(field => !fieldVisible(field, values) ||
        ((!field.required_text || getValue(values, field.key) === field.required_text) &&
        !(field.type === 'checkbox' && field.required && !getValue(values, field.key))));
}

export function formData(form, values, csrf) {
    const data = new FormData();
    data.set('_token', csrf ?? '');
    if (form.method !== 'POST') data.set('_method', form.method);
    for (const field of form.fields) {
        if (field.disabled || !fieldVisible(field, values)) continue;
        const value = getValue(values, field.key);
        if (field.multiple) {
            for (const item of Array.isArray(value) ? value : []) data.append(field.name, String(item));
        } else {
            data.set(field.name, field.type === 'checkbox' ? (value ? '1' : '0') : String(value ?? ''));
        }
    }
    return data;
}

export async function request(url, runtime, csrf, options = {}) {
    const response = await fetch(sameOriginUrl(url), {
        credentials: 'same-origin',
        signal: AbortSignal.timeout(30000),
        ...options,
        headers: { Accept: 'application/json', 'X-LaravelUsers-Runtime': runtime, 'X-CSRF-TOKEN': csrf ?? '', ...options.headers },
    });
    if (!(response.headers.get('Content-Type') ?? '').includes('application/json')) {
        if (response.redirected) {
            window.location.assign(sameOriginUrl(response.url).href);
            return { response, payload: null };
        }
        throw new Error('The application returned an unexpected response.');
    }
    const payload = await response.json();
    if (!response.ok && !payload.screen && response.status !== 422) {
        const error = new Error(payload.message || `Request failed (${response.status}).`);
        error.status = response.status;
        throw error;
    }
    return { response, payload };
}

export function statusIcon(status) {
    return {
        queued: 'M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0M12 7v5l3 2',
        running: 'M21 12a9 9 0 1 1-9-9',
        completed: 'm5 12 4 4L19 6',
        failed: 'm12 3 10 18H2L12 3m0 6v5m0 3v1',
    }[status] ?? '';
}

export function cellText(user, column) {
    const value = getValue(user, column.key);
    if (Array.isArray(value)) return value.map(item => typeof item === 'object' ? item.name ?? item.label ?? '' : String(item)).join(', ');
    return ['string', 'number', 'boolean'].includes(typeof value) ? String(value) : '';
}

export function displayUsers(page, table) {
    let users = [...(page.data.users ?? [])];
    const filter = table.filter.trim().toLocaleLowerCase(page.features.locale);
    if (page.features.filtering && filter) users = users.filter(user => page.data.columns.some(column => cellText(user, column).toLocaleLowerCase(page.features.locale).includes(filter)));
    const column = page.data.columns.find(column => column.key === table.sort && column.sortable !== false);
    if (page.features.sorting && column) users.sort((left, right) => cellText(left, column).localeCompare(cellText(right, column), page.features.locale, { numeric: true, sensitivity: 'base' }) * (table.direction === 'asc' ? 1 : -1));
    return users;
}

export function dateLabel(value, page) {
    if (!value || !page.features.localize_dates) return value;
    const date = new Date(value);
    if (!Number.isFinite(date.getTime())) return value;
    try {
        return new Intl.DateTimeFormat(page.features.locale, { dateStyle: page.features.date_style, timeStyle: page.features.time_style }).format(date);
    } catch {
        return value;
    }
}

export function passwordFeedback(value, confirmation, config) {
    const rules = config?.settings;
    if (!rules) return null;
    const length = Array.from(value ?? '').length;
    const checks = { length: length >= rules.min && (rules.max === null || length <= rules.max), mixed_case: /\p{Ll}/u.test(value) && /\p{Lu}/u.test(value), numbers: /\p{N}/u.test(value), symbols: /[^\p{L}\p{N}\s]/u.test(value) };
    let score = Number(checks.length) + Number(length >= Math.max(12, rules.min)) + Number(checks.mixed_case) + Number(checks.numbers && checks.symbols);
    if (!checks.length || ['mixed_case', 'numbers', 'symbols'].some(rule => rules[rule] && !checks[rule])) score = Math.min(score, 1);
    return { score, label: config.strength_labels[Math.max(0, score - 1)], checks, mismatch: Boolean(value || confirmation) && value !== confirmation };
}

export function observeDialogs(root, dismiss) {
    let dialog = null;
    let returnFocus = null;
    let oldOverflow = '';
    const focusable = () => [...dialog.querySelectorAll('a[href],button,input,select,textarea,[tabindex]')].filter(node => !node.disabled && node.tabIndex >= 0 && node.getClientRects().length);
    const update = () => {
        const next = root.querySelector('[data-lu-native-dialog]');
        if (next === dialog) return;
        if (!dialog && next) {
            returnFocus = document.activeElement;
            oldOverflow = document.body.style.overflow;
            document.body.style.overflow = 'hidden';
        }
        dialog = next;
        if (dialog) (focusable()[0] ?? dialog).focus();
        else {
            document.body.style.overflow = oldOverflow;
            if (returnFocus?.isConnected) returnFocus.focus();
            returnFocus = null;
        }
    };
    const keydown = event => {
        if (!dialog) return;
        if (event.key === 'Escape') { event.preventDefault(); dismiss(dialog); }
        if (event.key !== 'Tab') return;
        const nodes = focusable();
        const target = event.shiftKey ? nodes.at(-1) : nodes[0];
        if (!nodes.length || !dialog.contains(document.activeElement) || document.activeElement === (event.shiftKey ? nodes[0] : nodes.at(-1))) {
            event.preventDefault();
            (target ?? dialog).focus();
        }
    };
    const observer = new MutationObserver(update);
    observer.observe(root, { childList: true, subtree: true });
    document.addEventListener('keydown', keydown);
    update();
    return () => { observer.disconnect(); document.removeEventListener('keydown', keydown); document.body.style.overflow = oldOverflow; };
}
