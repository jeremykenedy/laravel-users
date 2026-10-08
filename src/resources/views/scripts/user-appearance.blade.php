<script>
(function () {
    const root = document.getElementById('laravelusers');
    root?.addEventListener('lu:appearance', event => {
        root.querySelectorAll('[data-lu-user]').forEach(row => {
            const colors = event.detail[row.dataset.luUser];
            if (!colors) return;
            for (const [key, variable] of Object.entries({base: 'color', text: 'text', shade: 'shade', highlight: 'glow'})) {
                if (/^#[a-f0-9]{3,8}$/i.test(colors[key])) row.style.setProperty('--lu-profile-' + variable, colors[key]);
            }
            row.style.setProperty('--lu-profile-image', colors.gradient ? 'initial' : 'none');
            if (colors.dark) {
                for (const [key, variable] of Object.entries({base: 'color', text: 'text', shade: 'shade', highlight: 'glow'})) {
                    if (/^#[a-f0-9]{3,8}$/i.test(colors.dark[key])) row.style.setProperty('--lu-profile-dark-' + variable, colors.dark[key]);
                }
                row.style.setProperty('--lu-profile-dark-image', colors.dark.gradient ? 'initial' : 'none');
            }
        });
    });
    root?.querySelectorAll('[data-lu-inherit-color], [data-lu-inherit-strength]').forEach(input => {
        input.addEventListener('change', () => {
            const field = input.hasAttribute('data-lu-inherit-color') ? (input.dataset.luInheritColor || 'user-card-color') : (input.dataset.luInheritStrength || 'user-card-strength');
            document.getElementById(field).disabled = input.checked;
        });
    });
    root?.querySelectorAll('[data-lu-gradient-strength]').forEach(input => {
        input.addEventListener('input', () => {
            const output = root.querySelector('output[for="' + input.id + '"]');
            if (output) output.value = input.value + '%';
        });
    });
    root?.querySelectorAll('[data-lu-appearance-reset]').forEach(button => {
        button.addEventListener('click', () => {
            const input = document.getElementById(button.dataset.luAppearanceReset);
            if (!input || input.closest('fieldset[disabled]')) return;
            const value = button.dataset.luDefault;
            const inherit = root.querySelector('[data-lu-inherit-color="' + input.id + '"], [data-lu-inherit-strength="' + input.id + '"]') || (input.id === 'user-card-color' ? root.querySelector('[data-lu-inherit-color]') : input.id === 'user-card-strength' ? root.querySelector('[data-lu-inherit-strength]') : null);
            if (inherit) {
                inherit.checked = true;
                inherit.dispatchEvent(new Event('change', {bubbles: true}));
            } else if (input.type === 'checkbox') input.checked = value === '1';
            else input.value = value;
            input.dispatchEvent(new Event('input', {bubbles: true}));
            input.dispatchEvent(new Event('change', {bubbles: true}));
        });
    });
    root?.querySelectorAll('[data-lu-appearance-controls]').forEach(group => {
        function preview() {
            const color = group.querySelector('[data-lu-color]').value;
            const strength = Number(group.querySelector('[data-lu-gradient-strength]').value) / 50;
            const channels = color.slice(1).match(/../g).map(channel => {
                const value = parseInt(channel, 16) / 255;
                return value <= .04045 ? value / 12.92 : ((value + .055) / 1.055) ** 2.4;
            });
            const light = channels[0] * .2126 + channels[1] * .7152 + channels[2] * .0722 > .179;
            const panel = group.querySelector('[data-lu-appearance-preview]');
            const glow = Math.round(72 * strength) / 255;
            const shade = Math.round((light ? 72 : 80) * strength) / 255;
            panel.style.backgroundColor = color;
            panel.style.backgroundImage = group.querySelector('[data-lu-gradient]').checked
                ? `radial-gradient(ellipse at 50% 40%, rgba(255,255,255,${glow}), transparent 75%), linear-gradient(145deg, transparent, rgba(${light ? '255,255,255' : '0,0,0'},${shade}))` : 'none';
        }
        group.addEventListener('input', preview);
        group.addEventListener('change', preview);
        preview();
    });
})();
</script>
