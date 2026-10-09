import '@material/web/ripple/ripple.js';
import '@material/web/focus/md-focus-ring.js';

let root = document.querySelector('#laravelusers[data-lu-css="material3"]');

if (root) {
    const motion = matchMedia('(prefers-reduced-motion: reduce)');
    const buttons = '.lu-button, .lu-native-sort, button[role="tab"], .lu-email-choice, .lu-email-toggle, .lu-email-close, .lu-user-menu-toggle, .lu-user-menu-items a, .lu-user-menu-items button, .lu-table-toolbar summary';
    const fields = '.lu-input-group > input, .lu-input-group > select, .lu-input-group > textarea';
    const selector = `${buttons}, ${fields}`;
    const attached = new Map();

    const update = (control, components) => {
        if (components.ripple) {
            components.ripple.disabled = motion.matches || control.matches(':disabled, [aria-disabled="true"]');
        }
        if (control.matches(':disabled, [aria-disabled="true"]')) components.ring.visible = false;
    };

    const enhance = (control) => {
        if (attached.has(control)) {
            update(control, attached.get(control));
            return;
        }

        const container = control.matches(fields) ? control.parentElement : control;
        if (container.hasAttribute('data-lu-material-control')) {
            container.querySelectorAll(':scope > md-ripple, :scope > md-focus-ring').forEach(component => {
                component.detach?.();
                component.remove();
            });
        }
        const ring = document.createElement('md-focus-ring');
        ring.setAttribute('aria-hidden', 'true');
        ring.setAttribute('inward', '');
        container.append(ring);
        ring.attach(control);

        let ripple;
        if (control.matches(buttons)) {
            ripple = document.createElement('md-ripple');
            ripple.setAttribute('aria-hidden', 'true');
            container.append(ripple);
            ripple.attach(control);
        }

        const components = { ring, ripple };
        attached.set(control, components);
        container.setAttribute('data-lu-material-control', '');
        update(control, components);
    };

    const enhanceTree = (node) => {
        if (!(node instanceof Element)) return;
        if (node.matches(selector)) enhance(node);
        node.querySelectorAll(selector).forEach(enhance);
    };

    const release = (control, components) => {
        components.ring.detach();
        components.ring.remove();
        components.ripple?.detach();
        components.ripple?.remove();
        attached.delete(control);
    };

    motion.addEventListener('change', () => attached.forEach((components, control) => update(control, components)));

    const updateMutations = (mutations) => {
        for (const mutation of mutations) {
            if (mutation.type === 'attributes') {
                if (attached.has(mutation.target)) update(mutation.target, attached.get(mutation.target));
            } else {
                mutation.addedNodes.forEach(enhanceTree);
            }
        }
    };

    const reconnectControls = () => {
        for (const [control, components] of attached) {
            const connected = root.contains(control);
            if (connected && root.contains(components.ring) && (!components.ripple || root.contains(components.ripple))) continue;
            release(control, components);
            if (connected) enhance(control);
        }
    };

    const observer = new MutationObserver(mutations => {
        if (!root) return;
        updateMutations(mutations);
        reconnectControls();
    });

    const connect = () => {
        const nextRoot = document.querySelector('#laravelusers[data-lu-css="material3"]');
        if (nextRoot !== root) {
            observer.disconnect();
            attached.forEach((components, control) => release(control, components));
            root = nextRoot;
        }
        if (!root) return;

        enhanceTree(root);
        observer.observe(root, { childList: true, subtree: true, attributes: true, attributeFilter: ['disabled', 'aria-disabled'] });
    };

    connect();
    document.addEventListener('livewire:navigated', connect);
}
