export function toastGroups(page) {
    const groups = new Map();
    for (const toast of page.data.toasts ?? []) {
        if (!groups.has(toast.position)) groups.set(toast.position, []);
        groups.get(toast.position).push(toast);
    }
    return [...groups];
}

export function toastIcon(type) {
    if (type === 'success') return 'm5 12 4 4L19 6';
    if (['warning', 'error'].includes(type)) return 'm12 3 10 18H2L12 3m0 6v5m0 3v1';
    return 'M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0M12 11v6m0-10v1';
}

export function observeToastDismissals(root, store) {
    const dismiss = event => {
        if (!event.target.closest('[data-lu-toast]')) return;
        event.preventDefault();
        store.dismissToast(event.detail.id);
    };
    root.addEventListener('laravelusers-toast-dismiss', dismiss);
    return () => root.removeEventListener('laravelusers-toast-dismiss', dismiss);
}
