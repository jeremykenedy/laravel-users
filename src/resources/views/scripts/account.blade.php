<script>
(function () {
    const root = document.getElementById('laravelusers');
    const access = root?.querySelector('#lu-account-access-form');
    const applyModal = root?.querySelector('#lu-account-apply-dialog');
    if (access && applyModal) {
        let setting;
        const input = applyModal.querySelector('#lu-account-apply-confirmation');
        const confirm = applyModal.querySelector('[data-lu-apply-confirm]');
        access.querySelectorAll('[data-lu-account-apply]').forEach(button => button.addEventListener('click', () => {
            setting = button.dataset.luAccountApply;
            const checkbox = access.querySelector(`input[type="checkbox"][name="${setting}"]`);
            applyModal.querySelector('[data-lu-apply-summary]').textContent = `${checkbox.closest('.lu-settings-choice').querySelector('h3').textContent}: ${checkbox.checked ? @json(__('laravelusers::ui.account_on')) : @json(__('laravelusers::ui.account_off'))}`;
            input.value = ''; confirm.disabled = true; applyModal.showModal(); input.focus();
        }));
        input.addEventListener('input', () => { confirm.disabled = input.value !== 'change'; });
        applyModal.querySelectorAll('[data-lu-apply-dismiss]').forEach(button => button.addEventListener('click', () => applyModal.close()));
        applyModal.addEventListener('close', () => { input.value = ''; confirm.disabled = true; });
        confirm.addEventListener('click', () => {
            if (confirm.disabled) return;
            const target = access.querySelector('[name="apply_all"]'); target.disabled = false; target.value = setting;
            const confirmation = access.querySelector('[name="confirmation"]'); confirmation.disabled = false; confirmation.value = 'change';
            applyModal.close(); access.requestSubmit(); target.disabled = true; confirmation.disabled = true;
        });
    }
    const modal = root?.querySelector('#lu-account-delete-dialog');
    if (!modal) return;
    const form = modal.querySelector('form');
    const confirmation = form.querySelector('[name="confirmation"]');
    const submit = form.querySelector('[data-lu-account-delete-confirm]');
    root.querySelector('[data-lu-account-delete]')?.addEventListener('click', () => { form.reset(); submit.disabled = true; modal.showModal(); form.querySelector('[name="current_password"]').focus(); });
    confirmation.addEventListener('input', () => { submit.disabled = confirmation.value !== 'delete'; });
    modal.querySelectorAll('[data-lu-account-dismiss]').forEach(button => button.addEventListener('click', () => modal.close()));
    modal.addEventListener('close', () => { form.reset(); submit.disabled = true; });
})();
</script>
