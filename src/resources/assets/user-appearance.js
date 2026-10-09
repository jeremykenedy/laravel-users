(function () {
    const root = document.getElementById('laravelusers');
    if (!root) return;
    const groups = root.querySelectorAll('[data-lu-appearance-controls]');

    function syncHighlightInheritance() {
        root.querySelectorAll('[data-lu-gradient-highlight-color]').forEach(highlight => {
            const inherit = root.querySelector('[data-lu-inherit-color="' + highlight.id + '"]');
            if (!inherit?.checked) return;
            const fallback = highlight.dataset.luHighlightFallback;
            if (fallback) highlight.value = document.getElementById(fallback).value;
            else if (highlight.dataset.luHighlightDefault) highlight.value = highlight.dataset.luHighlightDefault;
        });
    }

    function updatePreviews() {
        syncHighlightInheritance();
        groups.forEach(group => {
            const color = group.querySelector('[data-lu-color]').value;
            const strength = Number(group.querySelector('[data-lu-gradient-strength]').value) / 50;
            const highlight = group.querySelector('[data-lu-gradient-highlight-color]');
            const highlightChannels = (highlight?.value || '#ffffff').slice(1).match(/../g).map(channel => parseInt(channel, 16));
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
                ? `radial-gradient(ellipse at 50% 40%, rgba(${highlightChannels.join(',')},${glow}), transparent 75%), linear-gradient(145deg, transparent, rgba(${light ? '255,255,255' : '0,0,0'},${shade}))` : 'none';
        });
    }
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
                inherit.checked = value === 'inherit';
                if (!inherit.checked) input.value = value;
                inherit.dispatchEvent(new Event('change', {bubbles: true}));
            } else if (input.type === 'checkbox') input.checked = value === '1';
            else input.value = value;
            input.dispatchEvent(new Event('input', {bubbles: true}));
            input.dispatchEvent(new Event('change', {bubbles: true}));
        });
    });
    root.addEventListener('input', updatePreviews);
    root.addEventListener('change', updatePreviews);
    updatePreviews();

    const form = root.querySelector('[data-lu-avatar-preview-url]');
    const source = form?.elements.namedItem('avatar_source');
    const status = form?.querySelector('[data-lu-avatar-preview-status]');
    let previewRequest;
    let previewSource = source?.value;
    async function updateAvatars() {
        if (!source || source.matches(':disabled')) return;
        previewSource = source.value;
        previewRequest?.abort();
        const controller = new AbortController();
        previewRequest = controller;
        status.hidden = true;
        try {
            const response = await fetch(form.dataset.luAvatarPreviewUrl, {
                method: 'POST', credentials: 'same-origin', signal: controller.signal,
                headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': form.querySelector('[name="_token"]').value},
                body: JSON.stringify({avatar_source: source.value})
            });
            if (!response.ok) throw new Error('Avatar preview failed');
            const data = await response.json();
            if (controller.signal.aborted) return;
            Object.entries(data.avatars).forEach(([kind, sample]) => {
                const panel = form.querySelector('[data-lu-avatar-preview="' + kind + '"]');
                const avatar = panel?.querySelector('.lu-avatar');
                if (!avatar) return;
                avatar.querySelector('img')?.remove();
                const fallback = avatar.querySelector('[data-lu-initials]');
                const icon = avatar.querySelector('[data-lu-avatar-icon]');
                if (fallback) {
                    fallback.textContent = sample.avatar.initials;
                    fallback.hidden = sample.avatar.fallback !== 'initials';
                }
                if (icon) icon.toggleAttribute('hidden', sample.avatar.fallback === 'initials');
                avatar.style.width = avatar.style.height = sample.avatar.size + 'px';
                if (sample.avatar.src) {
                    const image = document.createElement('img');
                    image.src = sample.avatar.src;
                    image.alt = '';
                    image.width = image.height = sample.avatar.size;
                    image.referrerPolicy = 'no-referrer';
                    image.addEventListener('error', () => image.remove());
                    avatar.appendChild(image);
                }
            });
        } catch (error) {
            if (error.name === 'AbortError') return;
            status.textContent = form.dataset.luAvatarPreviewError;
            status.hidden = false;
        }
    }
    source?.addEventListener('change', updateAvatars);
    window.addEventListener('pageshow', () => {
        if (source?.value !== previewSource) updateAvatars();
    });
    if (!form?.querySelector('[data-lu-avatar-preview] img')) updateAvatars();
})();
