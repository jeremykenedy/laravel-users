@if(config('laravelusers.password.meter', false))
<script>
(function () {
    const input = document.getElementById('password');
    const meter = document.querySelector('[data-lu-password-meter]');
    if (!input || !meter) return;
    @php
        $meterRules = \jeremykenedy\laravelusers\Support\PasswordRules::settings(!isset($user));
    @endphp
    const rules = @json($meterRules);
    @php
        $passwordLabels = [__('laravelusers::ui.password_weak'), __('laravelusers::ui.password_fair'), __('laravelusers::ui.password_good'), __('laravelusers::ui.password_strong')];
    @endphp
    const labels = @json($passwordLabels);
    function update() {
        const value = input.value;
        const length = Array.from(value).length;
        meter.hidden = value === '';
        const checks = { length: length >= rules.min && (rules.max === null || length <= rules.max), mixed_case: /\p{Ll}/u.test(value) && /\p{Lu}/u.test(value), numbers: /\p{N}/u.test(value), symbols: /[^\p{L}\p{N}\s]/u.test(value) };
        meter.querySelectorAll('[data-lu-password-rule]').forEach(item => { item.dataset.luMet = String(checks[item.dataset.luPasswordRule]); });
        let score = Number(checks.length) + Number(length >= Math.max(12, rules.min)) + Number(checks.mixed_case) + Number(checks.numbers && checks.symbols);
        if (!checks.length || ['mixed_case', 'numbers', 'symbols'].some(rule => rules[rule] && !checks[rule])) score = Math.min(score, 1);
        meter.querySelector('meter').value = score;
        meter.querySelector('[data-lu-password-strength]').textContent = labels[Math.max(0, score - 1)];
    }
    input.addEventListener('input', update);
    update();
})();
</script>
@endif
@if(config('laravelusers.password.confirmation_feedback', false))
<script>
(function () {
    const password = document.getElementById('password');
    const confirmation = document.getElementById('password_confirmation');
    const error = document.getElementById('lu-password-confirmation-error');
    if (!password || !confirmation || !error) return;
    const description = confirmation.getAttribute('aria-describedby');
    const invalid = confirmation.getAttribute('aria-invalid');
    const delay = @json(max(0, (int) config('laravelusers.password.confirmation_debounce', 2000)));
    let timer;
    function check() {
        clearTimeout(timer);
        const mismatch = (password.value !== '' || confirmation.value !== '') && password.value !== confirmation.value;
        error.hidden = !mismatch;
        if (mismatch) {
            confirmation.setAttribute('aria-invalid', 'true');
            confirmation.setAttribute('aria-describedby', [description, error.id].filter(Boolean).join(' '));
        } else {
            if (invalid) confirmation.setAttribute('aria-invalid', invalid); else confirmation.removeAttribute('aria-invalid');
            if (description) confirmation.setAttribute('aria-describedby', description); else confirmation.removeAttribute('aria-describedby');
        }
    }
    function schedule() {
        clearTimeout(timer);
        if (password.value === confirmation.value) check();
        else timer = setTimeout(check, delay);
    }
    confirmation.addEventListener('blur', check);
    confirmation.addEventListener('input', schedule);
    password.addEventListener('input', schedule);
})();
</script>
@endif
