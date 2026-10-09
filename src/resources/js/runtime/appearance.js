function hexColor(value, fallback) {
    return typeof value === 'string' && /^#[a-f0-9]{6}$/i.test(value) ? value.toLowerCase() : fallback;
}

function colors(base, strength, highlight, gradient) {
    const channels = base.slice(1).match(/../g).map(value => {
        const channel = parseInt(value, 16) / 255;
        return channel <= 0.04045 ? channel / 12.92 : ((channel + 0.055) / 1.055) ** 2.4;
    });
    const light = channels[0] * 0.2126 + channels[1] * 0.7152 + channels[2] * 0.0722 > 0.179;
    const opacity = Math.max(0, Math.min(100, Number(strength))) / 50;
    const alpha = value => Math.min(255, Math.round(value * opacity)).toString(16).padStart(2, '0');
    return { base, text: light ? '#000' : '#fff', shade: (light ? '#ffffff' : '#000000') + alpha(light ? 72 : 80), highlight: highlight + alpha(72), gradient };
}

export function previewColors(page, values, kind) {
    const fallback = page.data.appearance_defaults[kind];
    const lightKind = kind.replace('_dark', '');
    const highlight = values[kind + '_gradient_highlight_color'] ?? values[lightKind + '_gradient_highlight_color'] ?? fallback.highlight_color;
    return colors(hexColor(values[kind + '_color'], fallback.base), values[kind + '_gradient_strength'] ?? fallback.strength, hexColor(highlight, '#ffffff'), values[kind + '_gradient'] ?? fallback.gradient);
}

export function profileStyle(page, user, editing = false, values = null) {
    const kind = editing ? 'edit' : 'profile';
    let light = user?.appearance ?? page.data.appearance_defaults[kind];
    let dark = user?.appearance?.dark ?? page.data.appearance_defaults[kind + '_dark'];
    if (values) {
        const make = (mode, fallback) => colors(
            hexColor(values[`user_card${mode}_color`], fallback.base),
            values[`user_card${mode}_gradient_strength`] ?? fallback.strength,
            hexColor(values[`user_card${mode}_gradient_highlight_color`], fallback.highlight_color ?? '#ffffff'),
            values[`user_card${mode}_gradient`] === 'inherit' || values[`user_card${mode}_gradient`] === undefined ? fallback.gradient : values[`user_card${mode}_gradient`] === 'on',
        );
        light = make('', page.data.appearance_defaults[kind]);
        dark = make('_dark', page.data.appearance_defaults[kind + '_dark']);
    }
    return { ...styleVariables(light), ...styleVariables(dark, true) };
}

export function styleVariables(value, dark = false) {
    const prefix = dark ? '--lu-profile-dark-' : '--lu-profile-';
    return { [prefix + 'color']: value.base, [prefix + 'text']: value.text, [prefix + 'shade']: value.shade, [prefix + 'glow']: value.highlight, [prefix + 'image']: value.gradient ? 'initial' : 'none' };
}

export function previewStyle(page, values, kind) {
    const value = previewColors(page, values, kind);
    return { ...styleVariables(value), ...styleVariables(value, true) };
}
