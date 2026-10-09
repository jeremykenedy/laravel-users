<script>
(function () {
    const root = document.getElementById('laravelusers');
    const dialog = root?.querySelector('#lu-package-dialog');
    if (!dialog) return;
    const form = dialog.querySelector('form');
    const submit = form.querySelector('[type="submit"]');
    const error = form.querySelector('[data-lu-package-error]');
    const status = root.querySelector('[data-lu-package-operation-status]');
    const statusMessage = status.querySelector('[data-lu-package-status-message]');
    const requirementsStatus = root.querySelector('[data-lu-package-status]');
    const retryStatus = root.querySelector('[data-lu-package-status-retry]');
    let operation = @json($packageOperation ?? null);
    let busy = ['queued', 'running'].includes(operation?.status);
    let statusUrl = operation?.status_url;
    let pollTimer;
    let polling = false;
    let verifying = false;
    let verificationAttempts = 0;
    let requiredWord;
    let queueReady = @json((bool) ($packageQueueReady ?? false));
    function setStatus(message, verified = false, state = verified ? 'verified' : '', target = status) {
        target.hidden = false;
        target.dataset.state = state;
        target.setAttribute('role', state === 'failed' ? 'alert' : 'status');
        target.querySelector('[data-lu-package-status-message]').textContent = message;
        target.querySelector('[data-lu-package-status-verified]').hidden = !verified && state !== 'completed';
        target.querySelectorAll('[data-lu-package-status-icon]').forEach(icon => { icon.hidden = icon.dataset.luPackageStatusIcon !== state; });
        if (target === status) {
            const worker = root.querySelector('[data-lu-package-worker]');
            if (worker) worker.hidden = state !== 'queued' && !(state === 'failed' && operation?.stage === 'queue');
        }
    }
    function setRequirementsStatus(message, verified = false, state = verified ? 'verified' : '') {
        setStatus(message, verified, state, requirementsStatus);
    }
    function updateWorkerCommand(result) {
        const command = root.querySelector('[data-lu-package-worker] code');
        if (command && typeof result.worker_command === 'string') command.textContent = result.worker_command;
    }
    function updateButtons() {
        root.querySelectorAll('[data-lu-package]').forEach(button => {
            button.disabled = busy || (button.dataset.luPackage === 'requirements' && queueReady) || button.hasAttribute('data-lu-package-blocked') || (!queueReady && button.dataset.luPackage !== 'requirements');
        });
        const requirementsButton = root.querySelector('[data-lu-package="requirements"][data-lu-package-operation="setup"]');
        if (requirementsButton) {
            requirementsButton.querySelector('[data-lu-package-requirements-icon="setup"]').hidden = queueReady;
            requirementsButton.querySelector('[data-lu-package-requirements-icon="complete"]').hidden = !queueReady;
            requirementsButton.querySelector('[data-lu-package-requirements-label]').textContent = queueReady
                ? @json(__('laravelusers::ui.package_requirements_completed'))
                : @json(__('laravelusers::ui.package_requirements_setup'));
        }
        const warning = root.querySelector('[data-lu-package-requirements-warning]');
        if (warning) warning.hidden = queueReady;
        root.querySelectorAll('[data-lu-package-requirements]').forEach(message => { message.hidden = queueReady; });
        const verifyLabel = root.querySelector('[data-lu-package-verify-label]');
        if (verifyLabel) verifyLabel.textContent = queueReady
            ? @json(__('laravelusers::ui.package_requirements_reverify'))
            : @json(__('laravelusers::ui.package_requirements_verify'));
        const verifyButton = root.querySelector('[data-lu-package-verify]');
        if (verifyButton) verifyButton.disabled = busy || verifying;
    }
    function ready() {
        submit.disabled = busy || !form.elements.acknowledgement.checked || form.elements.confirmation.value !== requiredWord;
    }
    root.querySelectorAll('[data-lu-package]').forEach(button => button.addEventListener('click', () => {
        form.reset();
        error.hidden = true;
        const remove = button.dataset.luPackageOperation === 'remove';
        const setup = button.dataset.luPackage === 'requirements';
        const configure = button.dataset.luPackageOperation === 'configure';
        const options = form.querySelector('[data-lu-package-setup]'); options.hidden = options.disabled = remove || setup || button.dataset.luPackage === 'toast';
        requiredWord = remove ? 'remove' : 'continue';
        form.elements.package.value = button.dataset.luPackage;
        form.elements.operation.value = button.dataset.luPackageOperation;
        form.querySelector('[data-lu-package-word]').textContent = requiredWord;
        form.querySelector('[data-lu-package-warning]').textContent = setup ? @json(__('laravelusers::ui.package_requirements_hint')) : (configure ? @json(__('laravelusers::ui.package_configuration_hint')) : (remove ? @json(__('laravelusers::ui.packages_remove_warning')) : (button.dataset.luPackage === 'toast' ? @json(__('laravelusers::ui.package_toast_install_warning')) : @json(__('laravelusers::ui.packages_install_warning')))));
        form.querySelector('[data-lu-package-role-warning]').hidden = !remove || button.dataset.luPackage === 'toast';
        dialog.querySelector('[data-lu-package-title]').textContent = setup ? @json(__('laravelusers::ui.package_requirements_setup')) : (configure ? @json(__('laravelusers::ui.package_configure')) : (remove ? @json(__('laravelusers::ui.package_remove')) : @json(__('laravelusers::ui.package_install')))) + ' ' + button.dataset.luPackageName;
        dialog.querySelectorAll('[data-lu-package-remove-icon]').forEach(icon => { icon.hidden = !remove; });
        dialog.querySelectorAll('[data-lu-package-install-icon]').forEach(icon => { icon.hidden = remove || configure || setup; });
        dialog.querySelectorAll('[data-lu-package-configure-icon]').forEach(icon => { icon.hidden = !configure && !setup; });
        dialog.dataset.luRemove = String(remove);
        submit.classList.toggle('lu-danger', remove);
        ready();
        dialog.showModal();
    }));
    async function verifyRequirements() {
        verifying = true;
        updateButtons();
        setRequirementsStatus(@json(__('laravelusers::ui.package_verifying')), false, 'running');
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
            updateWorkerCommand(result);
            setRequirementsStatus(result.message, result.queue_ready === true);
            queueReady = result.queue_ready === true;
            if (result.status === 'checking' && ++verificationAttempts < 15) {
                setRequirementsStatus(result.message, false, 'running');
                setTimeout(verifyRequirements, 2000);
                return;
            }
            verifying = false;
            if (!queueReady) {
                setRequirementsStatus(result.status === 'checking' ? @json(__('laravelusers::ui.package_worker_not_verified')) : result.message, false, 'failed');
                const worker = root.querySelector('[data-lu-package-worker]');
                if (worker) worker.hidden = result.status !== 'checking';
            }
            updateButtons();
        } catch (exception) {
            verifying = false;
            setRequirementsStatus(exception.message, false, 'failed');
            updateButtons();
        }
    }
    root.querySelector('[data-lu-package-verify]')?.addEventListener('click', () => { verificationAttempts = 0; verifyRequirements(); });
    form.addEventListener('input', ready);
    dialog.querySelectorAll('[data-lu-package-dismiss]').forEach(button => button.addEventListener('click', () => dialog.close()));
    dialog.addEventListener('close', () => { form.reset(); error.hidden = true; ready(); });
    async function poll(url) {
        clearTimeout(pollTimer);
        if (polling) return;
        polling = true;
        try {
            const target = new URL(url, location.href);
            if (target.origin !== location.origin) throw new Error(@json(__('laravelusers::ui.package_status_failed')));
            const response = await fetch(target.href, {headers: {Accept: 'application/json'}, credentials: 'same-origin', cache: 'no-store', signal: AbortSignal.timeout(15000)});
            if (!response.ok) {
                const exception = new Error(@json(__('laravelusers::ui.package_status_failed')));
                exception.status = response.status;
                throw exception;
            }
            const result = await response.json();
            if (!['queued', 'running', 'completed', 'failed'].includes(result.status)) throw new Error(@json(__('laravelusers::ui.package_status_failed')));
            operation = result;
            if (retryStatus) retryStatus.hidden = true;
            setStatus(result.message || (result.status === 'running' ? @json(__('laravelusers::ui.package_running')) : @json(__('laravelusers::ui.package_queued'))), false, result.status);
            if (['completed', 'failed'].includes(result.status)) {
                busy = false;
                updateButtons();
                if (result.status === 'completed') {
                    const reload = document.createElement('a');
                    reload.href = @json(route('users.settings').'#packages');
                    reload.textContent = @json(__('laravelusers::ui.package_refresh'));
                    reload.setAttribute('data-lu-package-refresh', '');
                    statusMessage.append(document.createTextNode(' '), reload);
                    window.location.reload();
                }
                return;
            }
            pollTimer = setTimeout(() => poll(url), 2000);
        } catch (exception) {
            setStatus(@json(__('laravelusers::ui.package_status_failed')), false, 'failed');
            if (retryStatus) retryStatus.hidden = false;
            if (![401, 403, 404].includes(exception.status)) pollTimer = setTimeout(() => poll(url), 2000);
        } finally {
            polling = false;
        }
    }
    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (submit.disabled) return;
        busy = true; ready(); updateButtons(); error.hidden = true;
        try {
            const response = await fetch(form.action, {method: 'POST', body: new FormData(form), headers: {Accept: 'application/json'}, credentials: 'same-origin'});
            const result = await response.json();
            if (!response.ok) throw new Error(Object.values(result.errors || {}).flat().join(' ') || result.message || @json(__('laravelusers::ui.package_failed')));
            if (typeof result.queue_ready === 'boolean') {
                updateWorkerCommand(result);
                busy = false; dialog.close(); setRequirementsStatus(result.message, result.queue_ready === true);
                queueReady = result.queue_ready;
                updateButtons();
                if (result.status === 'checking') {
                    verificationAttempts = 0;
                    verifyRequirements();
                } else if (!queueReady) {
                    setRequirementsStatus(result.message, false, 'failed');
                }
                return;
            }
            const url = new URL(result.status_url, location.href);
            if (url.origin !== location.origin) throw new Error(@json(__('laravelusers::ui.package_failed')));
            dialog.close();
            operation = result;
            statusUrl = url.href;
            root.querySelectorAll('[data-lu-package]').forEach(button => { button.disabled = true; });
            status.hidden = false;
            setStatus(result.message || @json(__('laravelusers::ui.package_queued')), false, result.status || 'queued');
            poll(url.href);
        } catch (exception) {
            busy = false; ready(); updateButtons(); error.textContent = exception.message; error.hidden = false;
        }
    });
    retryStatus?.addEventListener('click', () => {
        if (statusUrl) {
            retryStatus.hidden = true;
            poll(statusUrl);
        }
    });
    root.addEventListener('click', event => {
        if (event.target.closest('[data-lu-package-refresh]')) {
            event.preventDefault();
            window.location.reload();
        }
    });
    updateButtons();
    if (busy && statusUrl) poll(statusUrl);
    else if (@json(($packageRequirements['status'] ?? null) === 'checking')) verifyRequirements();
})();
</script>
