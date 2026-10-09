@if(config('laravelusers.welcome.enabled', false))
<script>
(function () {
    const reset = document.querySelector('[name="force_password_reset"]');
    const welcome = document.querySelector('[name="send_welcome_email"]');
    if (!reset || !welcome) return;
    function update() {
        if (reset.checked) welcome.checked = true;
        ['password', 'password_confirmation'].forEach(function (name) {
            const field = document.getElementById(name);
            if (field) field.required = !reset.checked;
        });
    }
    reset.addEventListener('change', update);
    welcome.addEventListener('change', function () { if (!welcome.checked) reset.checked = false; update(); });
    update();
})();
</script>

@endif
