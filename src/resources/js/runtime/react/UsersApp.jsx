import { observeToastDismissals, toastGroups, toastIcon } from '../toasts.js';
import React, { useEffect, useState } from 'react';
import { cellText, dateLabel, displayValue, getOwnValue, observeDialogs, passwordFeedback, statusIcon } from '../shared.js';
import { previewStyle, profileStyle } from '../appearance.js';
import { displayUsers, fieldVisible, formSections, getValue, labelSection, visibleColumns } from '../store.js';
import { iconForAction, submitAction } from '../icons.js';

function NativeIcon({ action, enabled }) {
    const icon = enabled ? iconForAction(action) : null;
    return icon && <svg data-lu-icon={icon.name} className="lu-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true" focusable="false">{icon.shapes.map((shape, index) => React.createElement(shape.tag, { ...shape.attributes, key: index }))}</svg>;
}

function NativeAvatar({ avatar }) {
    if (!avatar) return null;
    return <span className="lu-avatar" aria-hidden="true">
        {avatar.fallback === 'initials' ? <span>{avatar.initials ?? '?'}</span> : <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2"/></svg>}
        {avatar.src && <img src={avatar.src} width={avatar.size ?? 40} height={avatar.size ?? 40} alt="" loading="lazy" referrerPolicy="no-referrer" onError={event => { event.currentTarget.hidden = true; }}/ >}
    </span>;
}

function NativeCell({ user, column, state, store }) {
    const text = cellText(user, column);
    if (column.type === 'avatar') return <NativeAvatar avatar={user.avatar}/>;
    if (column.type === 'activity') return <NativeActivity user={user} state={state}/>;
    if (column.type === 'date' && text) return <time dateTime={text}>{dateLabel(text, state.page)}</time>;
    if (column.type === 'presence') return <NativePresence user={user} state={state}/>;
    return <NativeLinkedCell user={user} column={column} store={store} text={text}/>;
}

function NativePresence({ user, state }) {
    const online = user.activity?.online;
    return <span className={'lu-badge ' + (online ? 'lu-online' : '')}>{online ? state.page.labels.online : state.page.labels.offline}</span>;
}

function NativeLinkedCell({ user, column, store, text }) {
    if (column.key === 'name' && user.urls?.show) return <a href={user.urls.show} onClick={event => { event.preventDefault(); store.navigate(user.urls.show); }}>{user.name}</a>;
    if (column.key === 'email' && column.linked) return <a href={'mailto:' + user.email}>{user.email}</a>;
    return <>{text}</>;
}

function NativeActivity({ user, state }) {
    return <dl className="lu-details">
        <div><dt>{state.page.labels.last_login_at}</dt><dd>{user.activity?.last_login_at ? <time dateTime={user.activity.last_login_at}>{dateLabel(user.activity.last_login_at, state.page)}</time> : state.page.labels.no_logins}</dd></div>
        {['device', 'os', 'browser', 'ip_address'].map(key => getOwnValue(user.activity, key) && <div key={key}><dt>{getOwnValue(state.page.labels, key)}</dt><dd>{getOwnValue(user.activity, key)}</dd></div>)}
    </dl>;
}

function NativeActions({ user, state, store }) {
    return <div className="lu-actions">
        {(user.links ?? []).map(link => <a key={link.url} href={link.url} className={'lu-button ' + (link.class ?? 'lu-secondary')} onClick={event => { event.preventDefault(); store.navigate(link.url); }}><NativeIcon action={link.name} enabled={state.page.features.icons}/><span>{link.label}</span></a>)}
        {(user.actions ?? []).map(action => <button key={action.name} type="button" className={'lu-button ' + (action.class ?? 'lu-secondary')} disabled={action.disabled || state.busy} onClick={() => store.openUserAction(action.name, user.id)}><NativeIcon action={action.name} enabled={state.page.features.icons}/><span>{action.label}</span></button>)}
    </div>;
}

function UserSelection({ user, state, store }) {
    return <label className="lu-check"><input type="checkbox" value={user.id} checked={state.table.selected.includes(String(user.id))} disabled={!user.selectable} onChange={event => store.select(user.id, event.target.checked)}/><span className="lu-sr-only">{state.page.labels.select_user.replace(':name', user.name)}</span></label>;
}

function NativeTable({ state, store }) {
    const users = displayUsers(state.page, state.table);
    const columns = visibleColumns(state);
    return <div data-lu-native-table>
        <div className="lu-pad lu-actions lu-table-controls">
            {state.page.features.filtering && <label className="lu-field" htmlFor="lu-native-table-filter"><span>{state.page.labels.filter}</span><input id="lu-native-table-filter" className="lu-input" type="search" maxLength="255" value={state.table.filter} onChange={event => store.setFilter(event.target.value)}/></label>}
            {state.page.features.view_toggle && <div className="lu-actions" role="group" aria-label={state.page.labels.view}>{['table', 'cards'].map(mode => <button key={mode} type="button" className="lu-button lu-secondary" aria-pressed={state.table.mode === mode} onClick={() => store.setMode(mode)}>{getOwnValue(state.page.labels, mode)}</button>)}</div>}
            {state.page.features.columns && <details className="lu-column-controls"><summary>{state.page.labels.columns}</summary><div className="lu-pad">{state.page.data.columns.map(column => <label key={column.key} className="lu-check"><input type="checkbox" checked={!state.table.hiddenColumns.includes(column.key)} onChange={() => store.toggleColumn(column.key)}/><span>{column.label}</span></label>)}</div></details>}
        </div>
        {state.page.features.bulk && <div className="lu-pad lu-actions"><span role="status">{state.page.labels.selected.replace(':count', state.table.selected.length)}</span>{state.page.features.bulk_actions.map(action => <button key={action.name} type="button" className={'lu-button ' + (action.class ?? 'lu-secondary')} disabled={!state.table.selected.length || action.disabled || state.busy} onClick={() => store.openBulkAction(action.name)}><NativeIcon action={action.name} enabled={state.page.features.icons}/><span>{action.label}</span></button>)}</div>}
        {state.table.mode === 'cards' && state.page.features.view_toggle ? <div className="lu-native-cards lu-pad">
            {users.map(user => <article key={user.id} className={'lu-panel lu-pad lu-native-user-card ' + state.page.classes.panel}><header className="lu-heading"><NativeAvatar avatar={user.avatar}/><h2>{user.urls?.show ? <a href={user.urls.show} onClick={event => { event.preventDefault(); store.navigate(user.urls.show); }}>{user.name}</a> : user.name}</h2>{state.page.features.bulk && <UserSelection user={user} state={state} store={store}/>}</header><dl className="lu-details">{columns.map(column => <div key={column.key}><dt>{column.label}</dt><dd><NativeCell user={user} column={column} state={state} store={store}/></dd></div>)}</dl><NativeActions user={user} state={state} store={store}/></article>)}
            {!users.length && <p role="status">{state.page.labels.empty}</p>}
        </div> : <div className={'lu-scroll ' + state.page.classes.scroll}><table className={'lu-native-table ' + state.page.classes.table}>
            <caption>{state.page.labels.directory}</caption>
            <thead><tr>{state.page.features.bulk && <th scope="col"><button type="button" className="lu-button lu-secondary" onClick={store.selectAll}>{state.page.labels.select_all}</button></th>}{columns.map(column => <th key={column.key} scope="col" aria-sort={state.table.sort === column.key ? (state.table.direction === 'asc' ? 'ascending' : 'descending') : undefined}>{state.page.features.sorting && column.sortable !== false ? <button type="button" className="lu-native-sort" onClick={() => store.sortBy(column.key)}>{column.label}</button> : column.label}</th>)}<th scope="col">{state.page.labels.actions}</th></tr></thead>
            <tbody>{users.map(user => <tr key={user.id}>{state.page.features.bulk && <td><UserSelection user={user} state={state} store={store}/></td>}{columns.map(column => <td key={column.key}><NativeCell user={user} column={column} state={state} store={store}/></td>)}<td><NativeActions user={user} state={state} store={store}/></td></tr>)}{!users.length && <tr><td colSpan={columns.length + 1 + Number(state.page.features.bulk)}><p role="status">{state.page.labels.empty}</p></td></tr>}</tbody>
        </table></div>}
    </div>;
}

function NativeField({ field, form, state, store, dialog }) {
    const { value, id, error, control } = fieldAttributes(field, form, state, dialog);
    const set = value => store.setValue(form.id, field.key, value);
    return <div className={'lu-field ' + state.page.classes.field}>
        <label htmlFor={id} className={(field.type === 'checkbox' ? 'lu-check ' : '') + state.page.classes['field-label']}>{field.type === 'checkbox' && <input {...control} className="lu-checkbox" type="checkbox" checked={Boolean(value)} onChange={event => set(event.target.checked)}/>}<span>{field.label}</span></label>
        <div className={'lu-control ' + state.page.classes['field-control']}>
            {field.nullable && <button type="button" className="lu-button lu-secondary" aria-pressed={value === null} disabled={field.disabled || state.busy} onClick={() => store.toggleInheritance(form.id, field.key)}>{(field.inherit_label ?? state.page.labels.appearance_inherit)}</button>}
            {field.type !== 'checkbox' && <div className={'lu-input-group ' + state.page.classes.control}>
                <NativeControl field={field} control={control} value={value} set={set} state={state} form={form}/>
            </div>}
            {field.help && <p id={id + '-help'} className="lu-muted">{field.help}</p>}{error && <p id={id + '-error'} className="lu-field-error" role="alert">{error}</p>}
        </div>
    </div>;
}

function fieldAttributes(field, form, state, dialog) {
    const value = getValue(getOwnValue(state.values, form.id), field.key);
    const id = `lu-field-${form.id}-${field.key.replaceAll('.', '-')}${dialog ? '-dialog' : ''}`;
    const error = (getOwnValue(getOwnValue(state.errors, form.id), field.key) ?? []).join(' ');
    const disabled = field.disabled || state.busy || (field.nullable && value === null);
    const description = [field.help ? `${id}-help` : '', error ? `${id}-error` : ''].filter(Boolean).join(' ') || undefined;
    const control = { id, name: field.name, required: field.required, disabled, 'aria-invalid': error ? 'true' : undefined, 'aria-describedby': description };
    const attributes = { value, id, error, control };
    return attributes;
}

function NativeSelect({ field, control, value, set, state }) {
    return <select {...control} className={'lu-input ' + state.page.classes.select} multiple={field.multiple} value={field.multiple ? (value ?? []).map(String) : String(value ?? '')} onChange={event => set(field.multiple ? Array.from(event.target.selectedOptions, option => option.value) : event.target.value)}>{field.options.map(option => <option key={option.value} value={option.value} disabled={option.disabled}>{option.label}</option>)}</select>;
}

function NativeControl({ field, control, value, set, state, form }) {
    if (field.type === 'select') return <NativeSelect field={field} control={control} value={value} set={set} state={state}/>;
    if (field.type === 'textarea') return <textarea {...control} className={'lu-input ' + state.page.classes.input} value={value ?? ''} maxLength={field.maxlength} onChange={event => set(event.target.value)}/>;
    return <input {...control} className={'lu-input ' + state.page.classes.input} type={field.type} value={displayValue(field, getOwnValue(state.values, form.id))} min={field.min} max={field.max} step={field.step} maxLength={field.maxlength} autoComplete={field.type === 'password' ? 'new-password' : undefined} onChange={event => set(event.target.value)}/>;
}

function AppearancePreview({ state }) {
    if (!state.page.data.appearance_preview) return null;
    return <div className="lu-native-cards" aria-busy={state.appearancePreviewLoading}>{Object.entries(state.appearancePreviewAvatars).map(([kind, sample]) => <section key={kind} className="lu-settings-choice"><h3>{getOwnValue(state.page.labels, 'settings_' + kind + '_color')}</h3><div className="lu-profile-identity lu-native-profile" style={previewStyle(state.page, state.values.settings, kind)}><NativeAvatar avatar={sample.avatar}/><div><h4>{sample.name}</h4><p>{state.page.labels.appearance_avatar_preview}</p></div></div></section>)}</div>;
}

function NativeForm({ form, state, store, dialog = false }) {
    const sections = formSections(form);
    const feedback = passwordFeedback(getOwnValue(state.values, form.id)?.password ?? '', getOwnValue(state.values, form.id)?.password_confirmation ?? '', state.page.data.password);
    // React escapes field values and labels in this JSX form.
    // eslint-disable-next-line xss/no-mixed-html
    return <form method="POST" action={form.action ?? undefined} className="lu-form lu-pad" data-lu-native-form={form.id} onSubmit={event => { event.preventDefault(); store.submit(form.id, dialog); }}>
        <input type="hidden" name="_token" value={state.page.csrf ?? ''}/>{form.method !== 'POST' && <input type="hidden" name="_method" value={form.method}/>}
        {!dialog && <h2>{form.title}</h2>}{form.help && <p className="lu-muted">{form.help}</p>}{form.confirm && <p>{form.confirm}</p>}
        {form.tabs && sections.length > 1 && <div className="lu-settings-tabs" role="tablist" aria-label={form.title}>{sections.map(section => <button key={section} type="button" id={`lu-tab-${form.id}-${section}${dialog ? '-dialog' : ''}`} role="tab" aria-controls={`lu-panel-${form.id}-${section}${dialog ? '-dialog' : ''}`} aria-selected={getOwnValue(state.tabs, form.id) === section} onClick={() => store.setTab(form.id, section)}>{labelSection(section)}</button>)}</div>}
        <fieldset disabled={form.disabled || state.busy} hidden={state.preview?.form === form.id}><legend className="lu-sr-only">{form.title}</legend>
            {sections.map(section => <NativeFormSection key={section} section={section} sections={sections} form={form} state={state} store={store} dialog={dialog}/>)}
            {state.page.features.password_meter && feedback && getOwnValue(state.values, form.id)?.password && <div className="lu-password-meter"><p>{state.page.labels.password_strength}: <strong>{feedback.label}</strong></p><meter min="0" max="4" value={feedback.score} aria-label={state.page.labels.password_strength}/></div>}
            {state.page.features.password_feedback && getOwnValue(state.passwordMismatch, form.id) && <p className="lu-password-confirmation-error" role="status">{state.page.labels.password_mismatch}</p>}
        </fieldset>
        {state.preview?.form === form.id && <section><button type="button" className="lu-button lu-secondary" onClick={store.editPreview}><NativeIcon action="back" enabled={state.page.features.icons}/><span>{state.page.labels.email_back_editing}</span></button><p className="lu-muted">{state.preview.recipient}</p><iframe title={state.page.labels.email_preview_frame} sandbox="" referrerPolicy="no-referrer" srcDoc={state.preview.html}/></section>}
        {form.preview && state.preview?.form !== form.id && <button type="button" className="lu-button lu-secondary" disabled={state.busy} onClick={() => store.preview(form.id)}><NativeIcon action="preview" enabled={state.page.features.icons}/><span>{state.page.labels.email_preview}</span></button>}
        <div className="lu-actions lu-form-actions"><button type="submit" className={'lu-button ' + (form.danger ? 'lu-danger' : 'lu-success')} disabled={state.busy || !store.ready(form.id)}><NativeIcon action={submitAction(form)} enabled={state.page.features.icons}/><span>{form.submit}</span></button>{dialog && <button type="button" className="lu-button lu-secondary" disabled={state.busy} onClick={store.closeDialog}><NativeIcon action="cancel" enabled={state.page.features.icons}/><span>{state.page.labels.cancel}</span></button>}</div>
    </form>;
}

function NativeFormSection({ section, sections, form, state, store, dialog }) {
    const accordion = form.accordion && section.startsWith('email-');
    const Tag = accordion ? 'details' : 'section';
    return <Tag className={accordion ? 'lu-email-template' : undefined} key={section} id={`lu-panel-${form.id}-${section}${dialog ? '-dialog' : ''}`} hidden={form.tabs && sections.length > 1 && getOwnValue(state.tabs, form.id) !== section} role={form.tabs && sections.length > 1 ? 'tabpanel' : undefined} aria-labelledby={form.tabs && sections.length > 1 ? `lu-tab-${form.id}-${section}${dialog ? '-dialog' : ''}` : undefined}>{accordion && <summary><span>{getOwnValue(state.page.labels, 'email_template_' + section.slice(6))}</span></summary>}{form.fields.filter(field => field.type !== 'hidden' && field.section === section && fieldVisible(field, getOwnValue(state.values, form.id))).map(field => <NativeField key={field.key} field={field} form={form} state={state} store={store} dialog={dialog}/>)}{form.id === 'settings' && section === 'appearance' && <AppearancePreview state={state}/>}</Tag>;
}

function NativeDialog({ state, store }) {
    const form = store.activeForm();
    if (!form) return null;
    return <div className="lu-native-dialog-backdrop"><section className="lu-email-dialog lu-native-dialog" role="dialog" aria-modal="true" aria-labelledby="lu-native-action-title" tabIndex="-1" data-lu-native-dialog><header className="lu-email-heading"><h2 id="lu-native-action-title">{form.title}</h2><button type="button" className="lu-email-close" aria-label={state.page.labels.close} disabled={state.busy} onClick={store.closeDialog}>{state.page.labels.close}</button></header><div className="lu-email-body"><NativeForm form={form} state={state} store={store} dialog/></div></section></div>;
}

function PackageStatus({ state, store }) {
    const operation = state.packageOperation;
    if (!operation) return null;
    return <div className="lu-flash lu-native-package-status" role={operation.status === 'failed' ? 'alert' : 'status'} data-lu-package-state={operation.status}>
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden="true" className={'lu-package-status-icon ' + (operation.status === 'running' ? 'lu-package-spinner' : '')}><path d={statusIcon(operation.status)} strokeLinecap="round" strokeLinejoin="round"/></svg>
        <div><p>{operation.message}</p>{operation.transport_error && <p role="alert">{operation.transport_error}</p>}{['completed', 'failed'].includes(operation.status) && state.page.urls.settings && <a href={state.page.urls.settings + '#packages'} className="lu-button lu-secondary" onClick={event => { event.preventDefault(); store.reloadPage(); }}>{state.page.labels.package_refresh}</a>}</div>
    </div>;
}

function NativeNotifications({ state, store }) {
    return <div className="lu-native-notifications" data-lu-notification-style={state.page.features.notification_driver}>{[...state.page.flash, ...(state.notice ? [state.notice] : [])].map((message, index) => !state.dismissedMessages.includes(index) && (state.page.features.notifications || message.type === 'error') && <div key={index} className={'lu-flash ' + (message.type === 'error' ? 'alert-danger' : message.type === 'success' ? 'alert-success' : '')} role={message.type === 'error' ? 'alert' : 'status'}><span>{message.message}</span>{state.page.features.notification_dismissible && <button type="button" aria-label={state.page.labels.close} onClick={() => store.dismissMessage(index)}>{state.page.labels.close}</button>}</div>)}</div>;
}

function PackageSettings({ state, store }) {
    const packages = state.page.data.packages;
    if (!packages) return null;
    const requirements = state.packageRequirements;
    return <section id="packages" className="lu-package-settings lu-pad" aria-labelledby="lu-native-packages-title">
        <h2 id="lu-native-packages-title">{state.page.labels.settings_packages}</h2><p className="lu-muted">{state.page.labels.packages_hint}</p>
        {!packages.ready && <p>{state.page.labels.packages_queue_required}</p>}
        <div className="lu-actions lu-native-package-actions" data-lu-native-requirement-actions><button type="button" className="lu-button lu-secondary" disabled={state.busy || packages.ready} onClick={() => store.openSettingsAction('package-requirements')}><NativeIcon action={packages.ready ? 'check' : 'settings'} enabled={state.page.features.icons}/><span>{packages.ready ? state.page.labels.package_requirements_completed : state.page.labels.package_requirements_setup}</span></button><button type="button" className="lu-button lu-secondary" disabled={state.busy || requirements?.busy} onClick={store.verifyRequirements}><NativeIcon action="verify" enabled={state.page.features.icons}/><span>{packages.ready ? state.page.labels.package_requirements_reverify : state.page.labels.package_requirements_verify}</span></button></div>
        <PackageRequirements requirements={requirements}/>
        <p className="lu-muted">{state.page.labels.package_requirements_hint}</p><div className="lu-actions">{packages.help.map(link => <a key={link.url} href={link.url} target="_blank" rel="noopener noreferrer">{link.label}</a>)}</div>
        <div className="lu-settings-grid">{packages.choices.map(choice => <PackageChoice key={choice.name} choice={choice} packages={packages} state={state} store={store}/>)}</div>
    </section>;
}

function PackageRequirements({ requirements }) {
    if (!requirements) return null;
    return <div className="lu-native-package-status" role="status" data-lu-native-package-requirements><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden="true" className={requirements.status === 'checking' ? 'lu-package-spinner' : ''}><path d={statusIcon(requirements.status === 'checking' ? 'running' : requirements.queue_ready ? 'completed' : 'failed')}/></svg><div><p>{requirements.message}</p>{requirements.transport_error && <p role="alert">{requirements.transport_error}</p>}</div></div>;
}

function PackageChoice({ choice, packages, state, store }) {
    return <section className="lu-settings-choice" key={choice.name}><h3>{choice.label}</h3><p>{choice.installed ? state.page.labels.package_installed : state.page.labels.package_not_installed}</p>{choice.hint && <p className="lu-muted">{choice.hint}</p>}{choice.reason && <p className="lu-muted">{choice.reason}</p>}<PackageChoiceActions choice={choice} packages={packages} state={state} store={store}/>{choice.setup_completed && <p className="lu-setup-completed"><NativeIcon action="check" enabled={state.page.features.icons}/><span>{state.page.labels.package_setup_completed}</span></p>}{choice.setup_hint && <p className="lu-muted">{choice.setup_hint}</p>}</section>;
}

function PackageChoiceActions({ choice, packages, state, store }) {
    return <div className="lu-actions lu-native-package-actions"><button type="button" className="lu-button lu-secondary" disabled={choice.blocked || !packages.ready || state.busy} onClick={() => store.openSettingsAction(choice.name)}><NativeIcon action={choice.installed ? 'remove' : 'install'} enabled={state.page.features.icons}/><span>{choice.installed ? state.page.labels.package_remove : state.page.labels.package_install}</span></button>{choice.configure_name && !choice.setup_completed && <button type="button" className="lu-button lu-secondary" disabled={!packages.ready || state.busy} onClick={() => store.openSettingsAction(choice.configure_name)}><NativeIcon action="configure" enabled={state.page.features.icons}/><span>{state.page.labels.package_configure}</span></button>}</div>;
}

function NativeToasts({ state }) {
    return toastGroups(state.page).map(([position, toasts]) => <div key={position} className="lu-toast-stack" data-lu-toast-position={position} aria-live="polite">{toasts.map(toast => <div key={toast.id} className="lu-toast" data-laravel-toast="react" data-lu-toast data-lu-toast-id={toast.id} data-type={toast.type} data-auto-dismiss={String(toast.auto_dismiss)} data-duration={toast.duration} data-pause-on-hover={String(toast.pause_on_hover)} data-enter-animation={toast.enter_animation} data-enter-duration={toast.enter_duration} data-exit-animation={toast.exit_animation} data-exit-duration={toast.exit_duration} data-border={String(toast.show_border)} dir={toast.dir} role={toast.type === 'error' ? 'alert' : 'status'} style={{opacity: toast.opacity}}>
        {toast.show_progress && toast.auto_dismiss && toast.duration > 0 && <div className="lu-toast-progress" data-position={toast.progress_position} data-direction={toast.progress_direction}><span data-lu-toast-progress/></div>}
        <div className="lu-toast-body">{toast.show_icon && <span className="lu-toast-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden="true"><path d={toastIcon(toast.type)}/></svg></span>}<div className="lu-toast-message">{toast.title && <strong>{toast.title}</strong>}<span>{toast.message}</span></div>{toast.show_close && <button type="button" data-lu-dismiss-toast aria-label={state.page.labels.close}><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden="true"><path d="m6 6 12 12M6 18 18 6"/></svg></button>}</div>
    </div>)}</div>);
}

function NativeNavigation({ state, store }) {
    return <><NativeHeader state={state} store={store}/><NativeBreadcrumbs state={state} store={store}/></>;
}

function NativeHeader({ state, store }) {
    const { page } = state;
    if (!page.features.show_header || page.features.custom_header) return null;
    return <nav className="lu-toolbar" aria-label={page.labels.navigation}><a className="lu-brand" href={page.data.home}>{page.labels.package_name}</a><div className="lu-actions">
            <NativeUserMenu state={state} store={store}/>
            {page.features.theme_toggle && <button type="button" className="lu-button lu-secondary" onClick={store.toggleTheme}>{page.theme === 'dark' ? page.labels.theme_light : page.labels.theme_dark}</button>}
        </div></nav>;
}

function NativeUserMenu({ state, store }) {
    const { page } = state;
    if (!page.data.current_user) return null;
    return <details className="lu-user-menu"><summary className="lu-user-menu-toggle"><NativeAvatar avatar={page.data.current_user.avatar}/><span>{page.data.current_user.name}</span><span className="lu-user-menu-caret" aria-hidden="true"/></summary><div className="lu-user-menu-items">{page.data.current_user.activity && <div className="lu-user-menu-login" aria-label={page.labels.login_details}><NativeCell user={page.data.current_user} column={{ key: 'activity', type: 'activity' }} state={state} store={store}/></div>}{page.urls.users && <a href={page.urls.users} onClick={event => { event.preventDefault(); store.navigate(page.urls.users); }}>{page.labels.manage_users}</a>}{page.urls.account && <a href={page.urls.account} onClick={event => { event.preventDefault(); store.navigate(page.urls.account); }}>{page.labels.account_menu_label}</a>}{page.urls.logout && <form method="POST" action={page.urls.logout}><input type="hidden" name="_token" value={page.csrf}/><button type="submit">{page.labels.logout}</button></form>}</div></details>;
}

function NativeBreadcrumbItem({ crumb, index, page, store }) {
    const current = index === page.data.breadcrumbs.length - 1;
    const navigate = event => {
        if (crumb.native !== false) {
            event.preventDefault();
            store.navigate(crumb.url);
        }
    };
    return <li key={index} aria-current={current ? 'page' : undefined}>{Boolean(index) && <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden="true"><path d="m9 5 7 7-7 7"/></svg>}{!current && crumb.url ? <a href={crumb.url} onClick={navigate}>{crumb.label}</a> : <span>{crumb.label}</span>}</li>;
}

function NativeBreadcrumbs({ state, store }) {
    const { page } = state;
    if (!page.features.breadcrumbs) return null;
    return <nav className="lu-breadcrumbs lu-native-breadcrumbs" aria-label={page.labels.breadcrumbs}><ol>{page.data.breadcrumbs.map((crumb, index) => <NativeBreadcrumbItem key={index} crumb={crumb} index={index} page={page} store={store}/>)}</ol></nav>;
}

function NativeDirectory({ state, store }) {
    const page = state.page;
    if (!['users', 'deleted-users'].includes(page.screen)) return null;
    return <>
                {page.features.search && <form className="lu-search" onSubmit={event => { event.preventDefault(); store.search(); }}><label className="lu-sr-only" htmlFor="lu-native-search">{page.labels.search}</label><input id="lu-native-search" className="lu-input" type="search" maxLength="255" value={state.search} onChange={event => store.setSearch(event.target.value)}/><button type="submit" className="lu-button" disabled={state.busy}>{page.labels.search}</button>{state.search && <button type="button" className="lu-button lu-secondary" disabled={state.busy} onClick={store.clearSearch}>{page.labels.clear}</button>}</form>}
                <NativeTable state={state} store={store}/>
                <div className="lu-pagination">{page.features.show_count && <span>{page.labels.total_users.replace(':count', page.data.pagination.total)}</span>}{page.data.pagination.enabled && <nav className="lu-actions" aria-label={page.labels.pagination}>{page.data.pagination.previous && <a href={page.data.pagination.previous} className="lu-button lu-secondary" onClick={event => { event.preventDefault(); store.navigate(page.data.pagination.previous); }}>{page.labels.previous}</a>}<span>{page.labels.page.replace(':page', page.data.pagination.current).replace(':total', page.data.pagination.last)}</span>{page.data.pagination.next && <a href={page.data.pagination.next} className="lu-button lu-secondary" onClick={event => { event.preventDefault(); store.navigate(page.data.pagination.next); }}>{page.labels.next}</a>}</nav>}</div>
            </>;
}

function NativeProfile({ state, store }) {
    const page = state.page;
    const user = page.data.user;
    if (!['show-user', 'account', 'edit-user'].includes(page.screen)) return null;
    return <div className="lu-profile-body"><header className="lu-profile-identity lu-native-profile" style={profileStyle(page, user, page.screen === 'edit-user', page.screen === 'edit-user' ? state.values.user : state.values['account-appearance'])}><NativeAvatar avatar={user.avatar}/><div><h2>{user.full_name ?? user.name}</h2><p>{user.email}</p></div></header>{user.pending_email && <p role="status">{page.labels.account_email_pending_to.replace(':email', user.pending_email)}</p>}{page.screen === 'show-user' && <><dl className="lu-profile-details">{page.data.columns.filter(column => column.type !== 'avatar').map(column => <div key={column.key} className="lu-detail"><dt>{column.label}</dt><dd><NativeCell user={user} column={column} state={state} store={store}/></dd></div>)}</dl>{Boolean(user.permissions?.length) && <p>{page.labels.direct_permissions}: {user.permissions.map(item => item.label).join(', ')}</p>}{Boolean(user.role_level) && <p>{page.labels.role_level.replace(':level', user.role_level)}</p>}<NativeActions user={user} state={state} store={store}/></>}</div>;
}

function NativeSettingsActions({ state, store }) {
    const page = state.page;
    if (!Boolean(page.data.settings_actions?.some(action => !action.name.startsWith('package-')))) return null;
    return <div className="lu-pad lu-actions">{page.data.settings_actions.filter(action => !action.name.startsWith('package-')).map(action => <button key={action.name} type="button" className={'lu-button ' + (action.class ?? 'lu-secondary')} disabled={action.disabled || state.busy} onClick={() => store.openSettingsAction(action.name)}><NativeIcon action={action.name} enabled={state.page.features.icons}/><span>{action.label}</span></button>)}</div>;
}

export default function UsersApp({ store }) {
    const [state, setState] = useState(store.getSnapshot());
    useEffect(() => store.subscribe(setState), [store]);
    useEffect(() => observeToastDismissals(document.getElementById('lu-native-app'), store), [store]);
    useEffect(() => observeDialogs(document.getElementById('lu-native-app'), () => { if (!store.getSnapshot().busy) store.closeDialog(); }), [store]);
    const { page } = state;
    return <div data-lu-native-screen={page.screen} aria-busy={state.busy}>
        <NativeNavigation state={state} store={store}/>
        <NativeNotifications state={state} store={store}/>
        <NativeToasts state={state}/>
        <PackageStatus state={state} store={store}/>
        {page.data.banner && <div className="lu-alert" role="status">{page.data.banner.message}<button type="button" className="lu-button lu-secondary" disabled={state.busy} onClick={store.submitBanner}>{page.data.banner.label}</button></div>}
        <section className={'lu-panel ' + page.classes.panel}>
            <header className="lu-heading lu-card-heading"><h1 tabIndex="-1" data-lu-native-heading>{page.title}</h1><div className="lu-actions">{page.data.navigation.map(link => <a key={link.url} href={link.url} className="lu-button lu-secondary" onClick={event => { event.preventDefault(); store.navigate(link.url); }}>{link.label}</a>)}</div></header>
            {page.data.notice && <p className="lu-pad" role="status">{page.data.notice}</p>}
            <NativeDirectory state={state} store={store}/>
            <NativeProfile state={state} store={store}/>
            {page.data.form_ids.map(id => <NativeForm key={id} form={getOwnValue(page.forms, id)} state={state} store={store}/>)}
            <PackageSettings state={state} store={store}/>
            <NativeSettingsActions state={state} store={store}/>
        </section>
        <NativeDialog state={state} store={store}/>
    </div>;
}
