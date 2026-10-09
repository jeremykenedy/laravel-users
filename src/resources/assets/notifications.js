(function () {
    function mount() {
        const root = document.getElementById('laravelusers');
        if (!root || root.dataset.luNotificationsReady) return;
        root.dataset.luNotificationsReady = 'true';
        const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

        function prepare(toast) {
            if (toast.dataset.luToastReady) return;
            toast.dataset.luToastReady = 'true';
            let frame;
            let last = performance.now();
            let elapsed = 0;
            let hovered = false;
            let focused = false;
            let dismissed = false;
            const duration = Math.max(0, Number(toast.dataset.duration) || 0);
            const bar = toast.querySelector('[data-lu-toast-progress]');
            const paused = () => toast.dataset.pauseOnHover === 'true' && (hovered || focused);

            function animate(direction) {
                const name = toast.dataset[direction + 'Animation'] || 'none';
                const seconds = Math.min(5, Math.max(0, Number(toast.dataset[direction + 'Duration']) || 0));
                if (name === 'none' || !/^[a-z-]+$/.test(name) || reducedMotion.matches) return 0;
                toast.style.animation = 'toast-' + (direction === 'enter' ? 'enter-' : '') + name + ' ' + seconds + 's ease forwards';
                return seconds * 1000;
            }

            function dismiss() {
                if (dismissed) return;
                dismissed = true;
                cancelAnimationFrame(frame);
                const delay = animate('exit');
                const remove = () => {
                    const event = new CustomEvent('laravelusers-toast-dismiss', {bubbles: true, cancelable: true, detail: {id: toast.dataset.luToastId}});
                    if (toast.dispatchEvent(event)) toast.remove();
                };
                if (delay) setTimeout(remove, delay);
                else remove();
            }

            function tick(now) {
                if (!toast.isConnected || dismissed) return;
                if (!paused()) elapsed += now - last;
                last = now;
                if (bar) bar.style.width = Math.max(0, 100 - elapsed / duration * 100) + '%';
                if (elapsed >= duration) dismiss();
                else frame = requestAnimationFrame(tick);
            }

            toast.querySelector('[data-lu-dismiss-toast]')?.addEventListener('click', dismiss);
            toast.addEventListener('mouseenter', () => { hovered = true; });
            toast.addEventListener('mouseleave', () => { hovered = false; });
            toast.addEventListener('focusin', () => { focused = true; });
            toast.addEventListener('focusout', event => { focused = toast.contains(event.relatedTarget); });
            animate('enter');
            if (toast.dataset.autoDismiss === 'true' && duration > 0) frame = requestAnimationFrame(tick);
        }

        function initialize() {
            root.querySelectorAll('[data-lu-toast]').forEach(prepare);
            const driver = root.querySelector('#settings-notifications');
            const settings = root.querySelector('[data-lu-toast-settings]');
            if (driver && settings) settings.hidden = !['toast', 'both'].includes(driver.value);
        }

        root.addEventListener('click', event => {
            const close = event.target.closest('[data-lu-dismiss-alert]');
            if (close) close.closest('.lu-flash')?.remove();
        });
        root.addEventListener('change', event => { if (event.target.id === 'settings-notifications') initialize(); });
        new MutationObserver(records => {
            if (records.some(record => record.addedNodes.length)) initialize();
        }).observe(root, {childList: true, subtree: true});
        initialize();
    }
    document.addEventListener('livewire:navigated', mount);
    mount();
})();
