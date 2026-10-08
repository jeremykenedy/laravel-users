@if(config('laravelusers.emails.enabled', false))
<script>
(function () {
    const root = document.getElementById('laravelusers');
    const modal = root && root.querySelector('#lu-email-dialog');
    if (!modal) return;
    const form = modal.querySelector('form');
    const fields = modal.querySelector('[data-lu-email-fields]');
    const notice = modal.querySelector('[data-lu-email-notice]');
    @php
        $emailTitles = ['message' => __('laravelusers::ui.email_message'), 'reset' => __('laravelusers::ui.email_reset'), 'welcome' => __('laravelusers::ui.email_welcome')];
        $resetMinutes = config('auth.passwords.'.(config('laravelusers.emails.password_broker') ?: config('auth.defaults.passwords')).'.expire', 60);
        $resetMaximum = max(1, min(525600, (int) config('laravelusers.emails.reset_max_expire', 43200)));
        $accountMaximum = max(1, min(525600, (int) config('laravelusers.account_links.max_expire', 43200)));
        $expiryNotices = ['reset' => __('laravelusers::ui.reset_expiry', ['minutes' => ':minutes']), 'account' => __('laravelusers::ui.account_links_expiry', ['minutes' => ':minutes'])];
        $emailNotices = ['reset' => __('laravelusers::ui.email_reset_confirm'), 'welcome' => __('laravelusers::ui.email_welcome_confirm')];
        $contentDefaults = ['message' => ['subject' => '', 'message' => ''], 'reset' => \jeremykenedy\laravelusers\Support\EmailContent::defaults('reset'), 'welcome' => \jeremykenedy\laravelusers\Support\EmailContent::defaults('welcome')];
        foreach (['restore', 'force_delete', 'goodbye'] as $preset) {
            $emailTitles[$preset] = __('laravelusers::ui.email_'.$preset);
            $contentDefaults[$preset] = \jeremykenedy\laravelusers\Support\EmailContent::defaults($preset);
        }
        $editableContent = ['message' => true, 'reset' => config('laravelusers.emails.edit_reset', true), 'welcome' => config('laravelusers.emails.edit_welcome', true)];
        $neverNotices = ['reset' => __('laravelusers::ui.reset_never_expiry'), 'account' => __('laravelusers::ui.account_never_expiry')];
        $formDefaults = ['subject' => '', 'message' => '', 'use_greeting' => config('laravelusers.emails.use_greeting', true), 'greeting' => config('laravelusers.emails.greeting', 'Hi'), 'include_name' => config('laravelusers.emails.include_name', true), 'use_signoff' => config('laravelusers.emails.use_signoff', true), 'signoff' => config('laravelusers.emails.signoff', 'Thanks'), 'signoff_name' => config('laravelusers.emails.signoff_name', '')];
    @endphp
    const titles = @json($emailTitles);
    const messages = @json($emailNotices);
    const expiryNotices = @json($expiryNotices);
    const neverNotices = @json($neverNotices);
    const contentDefaults = @json($contentDefaults);
    const editableContent = @json($editableContent);
    const formDefaults = @json($formDefaults);
    let contentAction = @json(old('email_form') === '1' ? old('action', 'message') : null);
    const drafts = {};
    const accountOptions = modal.querySelector('[data-lu-account-options]');
    const durationGroups = {};
    const factors = { minutes: 1, hours: 60, days: 1440 };
    const maximums = { reset: @json($resetMaximum), account: @json($accountMaximum) };
    function updateDuration(prefix) {
        const group = durationGroups[prefix];
        const minutes = group ? Number(group.input.value) * factors[group.unit.value] : @json((int) $resetMinutes);
        if (group) {
            group.input.max = Math.max(1, Math.floor(maximums[prefix] / factors[group.unit.value]));
            group.values.hidden = !!group.never?.checked;
            group.input.disabled = group.unit.disabled = !group.active || !!group.never?.checked;
            group.element.querySelectorAll('input[name="' + prefix + '_never_expire"]').forEach(input => { input.disabled = !group.active; });
        }
        const text = group?.never?.checked ? neverNotices[prefix] : expiryNotices[prefix].replace(':minutes', Number.isFinite(minutes) && minutes > 0 ? minutes : '');
        if (prefix === 'reset' && form.elements.action.value === 'reset') notice.textContent = messages.reset + ' ' + text;
        if (prefix === 'account' && accountOptions) accountOptions.querySelector('[data-lu-account-notice]').textContent = text;
    }
    ['reset', 'account'].forEach(prefix => {
        const element = modal.querySelector('[data-lu-' + prefix + '-duration]');
        if (!element) return;
        const input = element.querySelector('input[type="number"]'), unit = element.querySelector('select');
        const never = element.querySelector('input[type="checkbox"]');
        const group = durationGroups[prefix] = { element, input, unit, never, previousUnit: unit.value, values: element.querySelector('[data-lu-expiry-values]'), active: false };
        never?.addEventListener('change', () => updateDuration(prefix));
        Array.from(unit.options).forEach(option => { option.disabled = factors[option.value] > maximums[prefix]; });
        input.addEventListener('input', () => updateDuration(prefix));
        unit.addEventListener('change', function () {
            const minutes = Number(input.value) * factors[group.previousUnit];
            input.value = Math.max(1, Math.min(Math.floor(maximums[prefix] / factors[this.value]), Math.ceil(minutes / factors[this.value])));
            group.previousUnit = this.value; updateDuration(prefix);
        });
    });
    function accountMode() {
        if (!accountOptions) return;
        const enabled = form.elements.deleted.value === '1' && form.elements.action.value === 'message';
        accountOptions.hidden = !enabled; accountOptions.disabled = !enabled;
        const selected = enabled && Array.from(accountOptions.querySelectorAll('input[type="checkbox"][name^="include_"]')).some(input => input.checked);
        const group = durationGroups.account;
        group.element.hidden = !selected;
        group.active = selected;
        accountOptions.querySelector('[data-lu-account-notice]').hidden = !selected;
        updateDuration('account');
    }
    accountOptions?.querySelectorAll('input[type="checkbox"]').forEach(input => input.addEventListener('change', accountMode));
    let previousFocus;
    let recipients = [];
    let bulkSelection = false;
    const recipientInputs = modal.querySelector('[data-lu-email-ids]');
    const recipientChips = modal.querySelector('[data-lu-email-chips]');
    const editor = modal.querySelector('[data-lu-email-editor]');
    const preview = modal.querySelector('[data-lu-email-preview]');
    const previewButton = modal.querySelector('[data-lu-email-preview-button]');
    const previewError = modal.querySelector('[data-lu-email-preview-error]');
    let previewRequest;
    let previewUrl;
    function editing() {
        previewRequest?.abort(); previewRequest = null;
        editor.hidden = false; preview.hidden = true;
        if (previewButton) { previewButton.hidden = false; previewButton.disabled = false; }
        modal.querySelector('[data-lu-email-frame]').src = 'about:blank';
        if (previewUrl) URL.revokeObjectURL(previewUrl);
        previewUrl = null;
    }
    modal.querySelector('[data-lu-email-back]').addEventListener('click', () => { editing(); previewButton?.focus(); });
    previewButton?.addEventListener('click', async function () {
        if (!recipients.length || !form.reportValidity()) return;
        this.disabled = true; previewError.hidden = true;
        const controller = new AbortController(); previewRequest = controller;
        try {
            const response = await fetch(@json(route('users.email.preview')), { method: 'POST', body: new FormData(form), headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, signal: controller.signal });
            const data = await response.json();
            if (!response.ok) throw new Error(data.errors ? Object.values(data.errors).flat().join(' ') : @json(__('laravelusers::ui.email_preview_failed')));
            if (!modal.open || previewRequest !== controller) return;
            previewUrl = URL.createObjectURL(new Blob([data.html], { type: 'text/html' }));
            modal.querySelector('[data-lu-email-frame]').src = previewUrl;
            modal.querySelector('[data-lu-email-preview-recipient]').textContent = @json(__('laravelusers::ui.email_preview_recipient', ['name' => ':name'])).replace(':name', data.recipient);
            editor.hidden = true; preview.hidden = false; this.hidden = true;
            modal.querySelector('[data-lu-email-back]').focus();
        } catch (error) {
            if (error.name !== 'AbortError') { previewError.textContent = error.name === 'Error' ? error.message : @json(__('laravelusers::ui.email_preview_failed'));  previewError.hidden = false; }
        } finally { if (previewRequest === controller) { this.disabled = false; previewRequest = null; } }
    });
    function recipientName(id) {
        const button = Array.from(root.querySelectorAll('[data-lu-email-user]')).find(button => button.dataset.luEmailUser === String(id));
        return button?.dataset.luEmailName || @json(__('laravelusers::ui.email_recipient_id', ['id' => ':id'])).replace(':id', id);
    }
    function updateRecipients() {
        const scroll = recipientChips.scrollTop;
        recipientInputs.replaceChildren(); recipientChips.replaceChildren();
        recipientChips.hidden = !bulkSelection;
        recipients.forEach(recipient => {
            const input = document.createElement('input');
            input.type = 'hidden'; input.name = 'ids[]'; input.value = recipient.id; recipientInputs.append(input);
            if (!bulkSelection) return;
            const chip = document.createElement('span');
            chip.className = 'lu-recipient-chip'; chip.setAttribute('role', 'listitem');
            const name = document.createElement('span'); name.textContent = recipient.name;
            const remove = document.createElement('button');
            remove.type = 'button'; remove.dataset.luRemoveRecipient = recipient.id; remove.textContent = '×';
            remove.setAttribute('aria-label', @json(__('laravelusers::ui.email_remove_recipient', ['name' => ':name'])).replace(':name', recipient.name));
            chip.append(name, remove); recipientChips.append(chip);
        });
        recipientChips.scrollTop = scroll;
        modal.querySelector('[data-lu-email-recipient]').textContent = recipients.length ? @json(__('laravelusers::ui.email_recipients')) + ': ' + (bulkSelection ? recipients.length : recipients[0].name) : @json(__('laravelusers::ui.email_no_recipients'));
        form.querySelector('[type="submit"]').disabled = recipients.length === 0;
    }
    function mode(action) {
        const preset = ['restore', 'force_delete', 'goodbye'].includes(action) ? action : null;
        if (contentAction !== action) {
            const inputs = Array.from(fields.querySelectorAll('input:not([type="hidden"]), textarea'));
            if (contentAction) drafts[contentAction] = Object.fromEntries(inputs.map(input => [input.name, input.type === 'checkbox' ? input.checked : input.value]));
            if (drafts[action]) inputs.forEach(input => { if (input.type === 'checkbox') input.checked = drafts[action][input.name]; else input.value = drafts[action][input.name]; });
            else ['subject', 'message'].forEach(name => { form.elements[name].value = contentDefaults[action][name]; });
            contentAction = action;
        }
        modal.querySelector('#lu-email-title span').textContent = titles[action];
        if (preset) {
            ['restore', 'force_delete'].forEach(name => {
                const input = accountOptions?.querySelector('input[type="checkbox"][name="include_' + name + '"]');
                if (input) input.checked = name === preset;
            });
            action = 'message';
        }
        form.elements.action.value = action;
        fields.hidden = !editableContent[action];
        fields.querySelectorAll('input, textarea').forEach(input => { input.disabled = !editableContent[action]; });
        notice.hidden = action === 'message'; notice.textContent = messages[action] || '';
        const group = durationGroups.reset;
        if (group) { group.element.hidden = action !== 'reset'; group.active = action === 'reset'; }
        updateDuration('reset'); accountMode();
    }
    function open(action, ids, names, deleted = false) {
        if (!ids.length) return;
        editing(); previewError.hidden = true;
        previousFocus = document.activeElement;
        bulkSelection = !names;
        recipients = ids.map(id => ({ id: String(id), name: names || recipientName(id) }));
        recipientChips.scrollTop = 0;
        updateRecipients();
        form.elements.deleted.value = deleted ? '1' : '0';
        mode(action); modal.showModal();
    }
    function discard() {
        editing();
        form.reset();
        fields.querySelectorAll('input:not([type="hidden"]), textarea').forEach(input => {
            if (input.type === 'checkbox') input.checked = !!formDefaults[input.name];
            else input.value = formDefaults[input.name] ?? '';
        });
        Object.keys(drafts).forEach(action => delete drafts[action]);
        contentAction = null;
        recipients = []; bulkSelection = false;
        form.elements.deleted.value = '0';
        accountOptions?.querySelectorAll('input[type="checkbox"]').forEach(input => { input.checked = false; });
        Object.values(durationGroups).forEach(group => {
            group.input.value = group.element.dataset.luDefaultMinutes;
            group.unit.value = group.previousUnit = 'minutes';
            if (group.never) group.never.checked = false;
        });
        mode('message'); updateRecipients();
        recipientChips.scrollTop = 0;
        previewError.hidden = true; previewError.textContent = '';
        modal.querySelector('[data-lu-email-preview-recipient]').textContent = '';
        modal.querySelector('[data-lu-email-validation-errors]')?.remove();
    }
    function bulk(event) {
        const bulkForm = root.querySelector('#lu-bulk');
        const action = bulkForm?.elements.action.value;
        const emailAction = { restore_email: 'restore', force_delete_email: 'force_delete' }[action] || action;
        if (!bulkForm || !titles[emailAction] || action === 'restore' || action === 'force_delete') return false;
        event.preventDefault(); event.stopImmediatePropagation();
        open(emailAction, Array.from(bulkForm.querySelectorAll('[name="ids[]"]')).map(input => input.value), null, !!root.querySelector('[data-lu-view="deleted"]'));
        return true;
    }
    root.addEventListener('click', function (event) {
        const remove = event.target.closest('[data-lu-remove-recipient]');
        if (remove) {
            const index = recipients.findIndex(recipient => recipient.id === remove.dataset.luRemoveRecipient);
            recipients = recipients.filter(recipient => recipient.id !== remove.dataset.luRemoveRecipient);
            updateRecipients();
            const buttons = recipientChips.querySelectorAll('button');
            (buttons[Math.min(index, buttons.length - 1)] || modal.querySelector('[data-lu-email-dismiss]')).focus({ preventScroll: true });
            return;
        }
        const button = event.target.closest('[data-lu-email-action]');
        if (button && !button.disabled) { event.preventDefault(); open(button.dataset.luEmailAction, [button.dataset.luEmailUser], button.dataset.luEmailName, button.dataset.luEmailDeleted === '1'); button.closest('.lu-email-menu')?.removeAttribute('open'); }
        if (event.target.closest('#lu-bulk-submit')) bulk(event);
    }, true);
    root.addEventListener('submit', function (event) { if (event.target.id === 'lu-bulk') bulk(event); }, true);
    modal.querySelectorAll('[data-lu-email-dismiss]').forEach(button => button.addEventListener('click', () => modal.close()));
    modal.addEventListener('close', () => { discard(); if (previousFocus?.isConnected) previousFocus.focus(); });
    modal.addEventListener('click', event => { if (event.target === modal) { const bounds = modal.getBoundingClientRect(); if (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom) modal.close(); } });
    form.addEventListener('submit', function (event) { if (!recipients.length) event.preventDefault(); else form.querySelector('[type="submit"]').disabled = true; });
    document.addEventListener('click', event => { root.querySelectorAll('.lu-email-menu[open]').forEach(menu => { if (!menu.contains(event.target)) menu.removeAttribute('open'); }); });
    root.addEventListener('toggle', event => {
        const menu = event.target;
        if (!menu.matches('.lu-email-menu')) return;
        const options = menu.querySelector('.lu-email-options');
        if (!options?.showPopover) return;
        if (!menu.open) {
            if (options.matches(':popover-open')) options.hidePopover();
            return;
        }
        options.setAttribute('popover', 'manual');
        options.showPopover();
        const anchor = menu.querySelector('summary').getBoundingClientRect();
        const bounds = options.getBoundingClientRect();
        const above = anchor.top - bounds.height - 8;
        options.style.left = `${Math.max(12, Math.min(anchor.right - bounds.width, innerWidth - bounds.width - 12))}px`;
        options.style.top = `${Math.max(12, Math.min(above >= 12 ? above : anchor.bottom + 8, innerHeight - bounds.height - 12))}px`;
    }, true);
    document.addEventListener('keydown', event => { if (event.key === 'Escape') root.querySelectorAll('.lu-email-menu[open]').forEach(menu => { menu.removeAttribute('open'); menu.querySelector('summary').focus(); }); });
    window.addEventListener('resize', () => root.querySelectorAll('.lu-email-menu[open]').forEach(menu => menu.removeAttribute('open')));
    if (@json(old('email_form') === '1')) { open(form.elements.action.value, Array.from(recipientInputs.querySelectorAll('input')).map(input => input.value), null, form.elements.deleted.value === '1'); }
})();
</script>
@endif
