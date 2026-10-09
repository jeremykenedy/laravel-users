<script>
(function () {
    const root = document.getElementById('laravelusers');
    const dialog = root?.querySelector('#lu-package-dialog');
    if (!dialog) return;
    const form = dialog.querySelector('form');
    const submit = form.querySelector('[type="submit"]');
    const error = form.querySelector('[data-lu-package-error]');
    const status = root.querySelector('[data-lu-package-status]');
    const statusMessage = status.querySelector('[data-lu-package-status-message]') || status;
    const statusIcon = status.querySelector('[data-lu-package-status-verified]');
    let busy = false;
    let requiredWord;
    let queueReady = @json((bool) ($packageQueueReady ?? false));
    function setStatus(message, verified = false) {
        statusMessage.textContent = message;
        if (statusIcon) statusIcon.hidden = !verified;
    }
    function updateButtons() {
        root.querySelectorAll('[data-lu-package]').forEach(button => {
            button.disabled = busy || (button.dataset.luPackage === 'requirements' && queueReady) || button.hasAttribute('data-lu-package-blocked') || (!queueReady && button.dataset.luPackage !== 'requirements');
        });
        const requirementsButton = root.querySelector('[data-lu-package-operation="setup"]');
        if (requirementsButton) {
            requirementsButton.querySelector('[data-lu-package-requirements-icon="setup"]').hidden = queueReady;
            requirementsButton.querySelector('[data-lu-package-requirements-icon="complete"]').hidden = !queueReady;
            requirementsButton.querySelector('[data-lu-package-requirements-label]').textContent = queueReady
                ? @json(__('laravelusers::ui.package_requirements_completed'))
                : @json(__('laravelusers::ui.package_requirements_setup'));
        }
        const warning = root.querySelector('[data-lu-package-requirements-warning]');
        if (warning) warning.hidden = queueReady;
        const verifyLabel = root.querySelector('[data-lu-package-verify-label]');
        if (verifyLabel) verifyLabel.textContent = queueReady
            ? @json(__('laravelusers::ui.package_requirements_reverify'))
            : @json(__('laravelusers::ui.package_requirements_verify'));
    }
    function ready() {
        submit.disabled = busy || !form.elements.acknowledgement.checked || form.elements.confirmation.value !== requiredWord;
    }
    root.querySelectorAll('[data-lu-package]').forEach(button => button.addEventListener('click', () => {
        form.reset();
        error.hidden = true;
        const remove = button.dataset.luPackageOperation === 'remove';
        const setup = button.dataset.luPackageOperation === 'setup';
        const options = form.querySelector('[data-lu-package-setup]'); options.hidden = options.disabled = remove || setup;
        requiredWord = remove ? 'remove' : 'continue';
        form.elements.package.value = button.dataset.luPackage;
        form.elements.operation.value = button.dataset.luPackageOperation;
        form.querySelector('[data-lu-package-word]').textContent = requiredWord;
        form.querySelector('[data-lu-package-warning]').textContent = setup ? @json(__('laravelusers::ui.package_requirements_hint')) : (remove ? @json(__('laravelusers::ui.packages_remove_warning')) : @json(__('laravelusers::ui.packages_install_warning')));
        form.querySelector('[data-lu-package-role-warning]').hidden = !remove || button.dataset.luPackage === 'toast';
        dialog.querySelector('[data-lu-package-title]').textContent = setup ? @json(__('laravelusers::ui.package_requirements_setup')) : (remove ? @json(__('laravelusers::ui.package_remove')) : @json(__('laravelusers::ui.package_install'))) + ' ' + button.dataset.luPackageName;
        dialog.querySelectorAll('[data-lu-package-remove-icon]').forEach(icon => { icon.hidden = !remove; });
        dialog.querySelectorAll('[data-lu-package-install-icon]').forEach(icon => { icon.hidden = remove; });
        dialog.dataset.luRemove = String(remove);
        submit.classList.toggle('lu-danger', remove);
        ready();
        dialog.showModal();
    }));
    root.querySelector('[data-lu-package-verify]')?.addEventListener('click', async event => {
        const button = event.currentTarget;
        button.disabled = true;
        status.hidden = false;
        setStatus(@json(__('laravelusers::ui.package_verifying')));
        try {
            const body = new FormData();
            body.append('package', 'requirements');
            body.append('operation', 'verify');
            body.append('_token', form.querySelector('[name="_token"]').value);
            const response = await fetch(@json(route('users.settings.packages.verify')), {
                method: 'POST',
                body,
                headers: {Accept: 'application/json'},
                credentials: 'same-origin'
            });
            const result = await response.json();
            if (!response.ok) throw new Error(result.message || @json(__('laravelusers::ui.package_status_failed')));
            setStatus(result.message, result.queue_ready === true);
            queueReady = result.queue_ready === true;
            updateButtons();
        } catch (exception) {
            setStatus(exception.message);
        } finally {
            button.disabled = false;
        }
    });
    form.addEventListener('input', ready);
    dialog.querySelectorAll('[data-lu-package-dismiss]').forEach(button => button.addEventListener('click', () => dialog.close()));
    dialog.addEventListener('close', () => { form.reset(); error.hidden = true; ready(); });
    async function poll(url) {
        try {
            const response = await fetch(url, {headers: {Accept: 'application/json'}, credentials: 'same-origin'});
            if (!response.ok) throw new Error(@json(__('laravelusers::ui.package_status_failed')));
            const result = await response.json();
            setStatus(result.message || (result.status === 'running' ? @json(__('laravelusers::ui.package_running')) : @json(__('laravelusers::ui.package_queued'))));
            if (['completed', 'failed'].includes(result.status)) {
                busy = false;
                updateButtons();
                if (result.status === 'completed') {
                    const reload = document.createElement('a');
                    reload.href = @json(route('users.settings').'#packages');
                    reload.textContent = @json(__('laravelusers::ui.package_refresh'));
                    statusMessage.append(document.createTextNode(' '), reload);
                }
                return;
            }
            setTimeout(() => poll(url), 2000);
        } catch (exception) {
            setStatus(exception.message);
        }
    }
    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (submit.disabled) return;
        busy = true; ready(); error.hidden = true;
        try {
            const response = await fetch(form.action, {method: 'POST', body: new FormData(form), headers: {Accept: 'application/json'}, credentials: 'same-origin'});
            const result = await response.json();
            if (!response.ok) throw new Error(Object.values(result.errors || {}).flat().join(' ') || result.message || @json(__('laravelusers::ui.package_failed')));
            if (result.status === 'completed') {
                busy = false; dialog.close(); status.hidden = false; setStatus(result.message, result.queue_ready === true);
                if (typeof result.queue_ready === 'boolean') queueReady = result.queue_ready;
                updateButtons();
                const reload = document.createElement('a'); reload.href = @json(route('users.settings').'#packages'); reload.textContent = @json(__('laravelusers::ui.package_refresh')); statusMessage.append(document.createTextNode(' '), reload);
                return;
            }
            const url = new URL(result.status_url, location.href);
            if (url.origin !== location.origin) throw new Error(@json(__('laravelusers::ui.package_failed')));
            dialog.close();
            root.querySelectorAll('[data-lu-package]').forEach(button => { button.disabled = true; });
            status.hidden = false;
            setStatus(@json(__('laravelusers::ui.package_queued')));
            poll(url.href);
        } catch (exception) {
            busy = false; ready(); error.textContent = exception.message; error.hidden = false;
        }
    });
})();
</script>
