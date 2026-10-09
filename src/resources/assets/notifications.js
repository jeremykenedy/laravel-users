(function () {
    function mount() {
        const root = document.getElementById('laravelusers');
        if (!root || root.dataset.luNotificationsReady) return;
        root.dataset.luNotificationsReady = 'true';
        const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

        function prepare(toast) {
            if (toast.dataset.luToastReady) return;
            toast.dataset.luToastReady = 'true';
            toast.style.setProperty('--lu-toast-opacity', toast.style.opacity || '1');
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
            root.querySelectorAll('[data-lu-dismiss-alert], [data-lu-dismiss-toast], [data-lu-preview-notification]').forEach(button => { button.disabled = false; });
            const driver = root.querySelector('#settings-notifications');
            const settings = root.querySelector('[data-lu-toast-settings]');
            if (driver && settings) settings.hidden = !['toast', 'both'].includes(driver.value);
        }

        function alignPreviewAlert() {
            if (!root.isConnected) {
                window.removeEventListener('resize', alignPreviewAlert);
                return;
            }
            const alert = root.querySelector('.lu-flash[data-lu-notification-preview]');
            if (!alert) return;
            const bounds = alert.parentElement.getBoundingClientRect();
            alert.style.left = bounds.left + 'px';
            alert.style.width = bounds.width + 'px';
            const topToast = root.querySelector('[data-lu-preview-toast-stack][data-lu-toast-position^="top-"] [data-lu-toast]');
            alert.style.top = topToast ? 'auto' : '16px';
            alert.style.bottom = topToast ? '16px' : 'auto';
        }

        function previewOptions(settings) {
            const options = new Map();
            for (const control of settings.querySelectorAll('[name^="toast["]:not([type="hidden"])')) {
                if (!control.reportValidity()) return null;
                const value = control.type === 'checkbox' ? control.checked : control.type === 'number' ? Number(control.value) : control.value;
                options.set(control.name.slice(6, -1), value);
            }
            return options;
        }

        function previewAlert(settings) {
            const alert = settings.querySelector('[data-lu-preview-alert-template]').content.firstElementChild.cloneNode(true);
            if (!settings.querySelector('[name="notifications_dismissible"][type="checkbox"]').checked) alert.querySelector('[data-lu-dismiss-alert]').remove();
            root.querySelector('.lu-notifications').append(alert);
            alignPreviewAlert();
        }

        function previewStack(options) {
            const previews = () => Array.from(root.querySelectorAll('[data-lu-preview-toast-stack] [data-lu-toast]'));
            if (!options.get('stack')) previews().forEach(toast => toast.remove());
            if (options.get('max_visible') > 0) {
                while (previews().length >= options.get('max_visible')) previews()[0].remove();
            }
            const position = options.get('position');
            let stack = root.querySelector('[data-lu-preview-toast-stack][data-lu-toast-position="' + position + '"]');
            if (!stack) {
                stack = document.createElement('div');
                stack.className = 'lu-toast-stack';
                stack.dataset.luPreviewToastStack = 'true';
                stack.dataset.luToastPosition = position;
                stack.setAttribute('aria-live', 'polite');
                root.append(stack);
            }
            return stack;
        }

        function configurePreviewToast(toast, options) {
            const attributes = {autoDismiss: 'auto_dismiss', duration: 'duration', pauseOnHover: 'pause_on_hover', enterAnimation: 'enter_animation', enterDuration: 'enter_duration', exitAnimation: 'exit_animation', exitDuration: 'exit_duration', border: 'show_border'};
            for (const [attribute, key] of Object.entries(attributes)) {
                const name = 'data-' + attribute.replace(/[A-Z]/g, letter => '-' + letter.toLowerCase());
                toast.setAttribute(name, String(options.get(key)));
            }
            toast.dataset.luToastId = 'preview-' + Date.now();
            toast.dir = options.get('dir');
            toast.style.opacity = String(options.get('opacity'));
            if (!options.get('show_icons')) toast.querySelector('.lu-toast-icon').remove();
            if (!options.get('show_close')) toast.querySelector('[data-lu-dismiss-toast]').remove();
            const progress = toast.querySelector('.lu-toast-progress');
            if (!options.get('show_progress') || !options.get('auto_dismiss') || options.get('duration') <= 0) {
                progress.remove();
                return;
            }
            progress.dataset.position = options.get('progress_position');
            progress.dataset.direction = options.get('progress_direction');
        }

        function previewNotification(button) {
            const settings = button.closest('.lu-settings-notifications');
            const driver = settings.querySelector('#settings-notifications')?.value || 'alert';
            const toastTemplate = settings.querySelector('[data-lu-preview-toast-template]');
            const useToast = ['toast', 'both'].includes(driver) && toastTemplate;
            const options = useToast ? previewOptions(settings) : new Map();
            if (!options) return;

            root.querySelectorAll('.lu-flash[data-lu-notification-preview]').forEach(alert => alert.remove());
            if (!useToast || driver === 'both') previewAlert(settings);
            if (!useToast) {
                root.querySelectorAll('[data-lu-preview-toast-stack]').forEach(stack => stack.remove());
                alignPreviewAlert();
                return;
            }
            const stack = previewStack(options);
            const toast = toastTemplate.content.firstElementChild.cloneNode(true);
            configurePreviewToast(toast, options);
            stack.append(toast);
            prepare(toast);
            alignPreviewAlert();
        }

        root.addEventListener('click', event => {
            const close = event.target.closest('[data-lu-dismiss-alert]');
            if (close) close.closest('.lu-flash')?.remove();
            const preview = event.target.closest('[data-lu-preview-notification]');
            if (preview && !preview.disabled && !preview.closest('fieldset[disabled]')) previewNotification(preview);
        });
        root.addEventListener('change', event => { if (event.target.id === 'settings-notifications') initialize(); });
        window.addEventListener('resize', alignPreviewAlert);
        new MutationObserver(records => {
            if (records.some(record => record.addedNodes.length)) initialize();
        }).observe(root, {childList: true, subtree: true});
        initialize();
    }
    document.addEventListener('livewire:navigated', mount);
    mount();
})();
