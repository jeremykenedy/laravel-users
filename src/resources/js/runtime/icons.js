import actions from './icon-actions.json' with { type: 'json' };

const definitions = typeof __LARAVEL_USERS_ICONS__ === 'undefined' ? {} : __LARAVEL_USERS_ICONS__;

export function iconForAction(action) {
    const name = Object.hasOwn(actions, action) ? actions[action] : null;
    return name && definitions[name]?.length ? { name, shapes: definitions[name] } : null;
}

export function submitAction(form) {
    if (form.danger) return 'delete';
    if (form.async && form.values?.operation) return form.values.operation;
    return Object.hasOwn(actions, form.id) ? form.id : 'save';
}
