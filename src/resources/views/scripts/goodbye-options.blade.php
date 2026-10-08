<script>
(function () {
    const root = document.getElementById('laravelusers');
    if (!root) return;
    function options(modal, enabled) {
        const panel = modal.querySelector('[data-lu-goodbye-options]');
        if (!panel) return;
        panel.hidden = !enabled;
        const checkbox = panel.querySelector('[data-lu-send-goodbye]');
        checkbox.checked = @json((bool) (config('laravelusers.emails.goodbye_on_delete', false) || config('laravelusers.emails.goodbye_auto_send', false)));
        checkbox.disabled = @json((bool) config('laravelusers.emails.goodbye_auto_send', false));
        const fields = panel.querySelector('[data-lu-goodbye-fields]');
        fields.querySelectorAll('input, textarea').forEach(input => { if (input.type === 'checkbox') input.checked = input.defaultChecked; else input.value = input.defaultValue; });
        fields.disabled = !enabled || !checkbox.checked;
        panel.querySelector('[data-lu-goodbye-editor]').open = false;
        checkbox.onchange = () => { fields.disabled = !enabled || !checkbox.checked; };
    }
    function copy(modal, form) {
        form.querySelectorAll('[data-lu-goodbye-input]').forEach(input => input.remove());
        const panel = modal.querySelector('[data-lu-goodbye-options]');
        if (!panel || panel.hidden || !panel.querySelector('[data-lu-send-goodbye]').checked) return;
        const values = [['send_goodbye', '1'], ...Array.from(panel.querySelectorAll('input:not([type="hidden"])[name], textarea[name]')).map(input => [input.name, input.type === 'checkbox' ? (input.checked ? '1' : '0') : input.value])];
        values.forEach(([name, value]) => {
            const input = document.createElement('input'); input.type = 'hidden'; input.name = name; input.value = value; input.dataset.luGoodbyeInput = ''; form.append(input);
        });
    }
    root.addEventListener('lu:delete-modal', event => options(event.target, event.detail?.action === 'delete'));
    root.addEventListener('lu:delete-confirm', event => copy(event.target, event.detail.form));
    if (window.jQuery) {
        window.jQuery('#confirmDelete').on('show.bs.modal', function (event) {
            const form = event.relatedTarget?.closest('form');
            const bulk = form?.querySelector('[name="action"]');
            options(this, !!form && (bulk ? bulk.value === 'delete' : !/\/force(?:\?|$)/.test(form.action)));
        });
        root.addEventListener('click', event => {
            const button = event.target.closest('#confirmDelete #confirm');
            const form = button && window.jQuery(button).data('form')?.[0];
            if (form) copy(button.closest('#confirmDelete'), form);
        }, true);
    }
})();
</script>
