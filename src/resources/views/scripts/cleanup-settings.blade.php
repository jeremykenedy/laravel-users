<script>
(function () {
    const root = document.getElementById('laravelusers');
    const form = root?.querySelector('#lu-cleanup-form');
    const modal = root?.querySelector('#lu-cleanup-dialog');
    if (!form || !modal) return;
    const enabled = form.querySelector('#lu-cleanup-enabled');
    const amount = form.querySelector('[name="amount"]');
    const unit = form.querySelector('[name="unit"]');
    const confirmation = modal.querySelector('#lu-cleanup-confirmation');
    const confirm = modal.querySelector('[data-lu-cleanup-confirm]');
    let approved = false;
    function duration() { amount.disabled = unit.value === 'immediately'; amount.required = !amount.disabled; }
    unit.addEventListener('change', duration); duration();
    form.addEventListener('submit', function (event) {
        if (!enabled.checked || approved) return;
        event.preventDefault();
        confirmation.value = ''; confirm.disabled = true;
        modal.querySelector('[data-lu-cleanup-summary]').textContent = unit.value === 'immediately' ? @json(__('laravelusers::ui.cleanup_immediate_warning')) : @json(__('laravelusers::ui.cleanup_summary', ['amount' => ':amount', 'unit' => ':unit'])).replace(':amount', amount.value).replace(':unit', unit.selectedOptions[0].textContent);
        modal.showModal(); confirmation.focus();
    });
    confirmation.addEventListener('input', () => { confirm.disabled = confirmation.value !== 'permanently delete'; });
    modal.querySelectorAll('[data-lu-cleanup-dismiss]').forEach(button => button.addEventListener('click', () => modal.close()));
    modal.addEventListener('close', () => { confirmation.value = ''; confirm.disabled = true; });
    confirm.addEventListener('click', () => {
        if (confirm.disabled) return;
        form.querySelector('[name="confirmation"]').value = 'permanently delete'; approved = true;
        modal.close(); form.requestSubmit(); approved = false;
    });
})();
</script>
