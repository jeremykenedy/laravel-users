import React, { useEffect, useState } from 'react';
import { cellText, dateLabel, observeDialogs, passwordFeedback } from '../shared.js';
import { displayUsers, fieldVisible, formSections, getValue, labelSection, visibleColumns } from '../store.js';

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
    if (column.type === 'activity') return <dl className="lu-details">
        <div><dt>{state.page.labels.last_login_at}</dt><dd>{user.activity?.last_login_at ? <time dateTime={user.activity.last_login_at}>{dateLabel(user.activity.last_login_at, state.page)}</time> : state.page.labels.no_logins}</dd></div>
        {['device', 'os', 'browser', 'ip_address'].map(key => user.activity?.[key] && <div key={key}><dt>{state.page.labels[key]}</dt><dd>{user.activity[key]}</dd></div>)}
    </dl>;
    if (column.type === 'date' && text) return <time dateTime={text}>{dateLabel(text, state.page)}</time>;
    if (column.key === 'name' && user.urls?.show) return <a href={user.urls.show} onClick={event => { event.preventDefault(); store.navigate(user.urls.show); }}>{user.name}</a>;
    if (column.key === 'email' && column.linked) return <a href={'mailto:' + user.email}>{user.email}</a>;
    if (column.type === 'presence') return <span className={'lu-badge ' + (user.activity?.online ? 'lu-online' : '')}>{user.activity?.online ? state.page.labels.online : state.page.labels.offline}</span>;
    return <>{text}</>;
}

function NativeActions({ user, state, store }) {
    return <div className="lu-actions">
        {(user.links ?? []).map(link => <a key={link.url} href={link.url} className={'lu-button ' + (link.class ?? 'lu-secondary')} onClick={event => { event.preventDefault(); store.navigate(link.url); }}>{link.label}</a>)}
        {(user.actions ?? []).map(action => <button key={action.name} type="button" className={'lu-button ' + (action.class ?? 'lu-secondary')} disabled={action.disabled || state.busy} onClick={() => store.openUserAction(action.name, user.id)}>{action.label}</button>)}
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
            {state.page.features.view_toggle && <div className="lu-actions" role="group" aria-label={state.page.labels.view}>{['table', 'cards'].map(mode => <button key={mode} type="button" className="lu-button lu-secondary" aria-pressed={state.table.mode === mode} onClick={() => store.setMode(mode)}>{state.page.labels[mode]}</button>)}</div>}
            {state.page.features.columns && <details className="lu-column-controls"><summary>{state.page.labels.columns}</summary><div className="lu-pad">{state.page.data.columns.map(column => <label key={column.key} className="lu-check"><input type="checkbox" checked={!state.table.hiddenColumns.includes(column.key)} onChange={() => store.toggleColumn(column.key)}/><span>{column.label}</span></label>)}</div></details>}
        </div>
        {state.page.features.bulk && <div className="lu-pad lu-actions"><span role="status">{state.page.labels.selected.replace(':count', state.table.selected.length)}</span>{state.page.features.bulk_actions.map(action => <button key={action.name} type="button" className={'lu-button ' + (action.class ?? 'lu-secondary')} disabled={!state.table.selected.length || action.disabled || state.busy} onClick={() => store.openBulkAction(action.name)}>{action.label}</button>)}</div>}
        {state.table.mode === 'cards' && state.page.features.view_toggle ? <div className="lu-native-cards lu-pad">
            {users.map(user => <article key={user.id} className={'lu-panel lu-pad lu-native-user-card ' + state.page.classes.panel}><header className="lu-heading"><NativeAvatar avatar={user.avatar}/><h2>{user.urls?.show ? <a href={user.urls.show} onClick={event => { event.preventDefault(); store.navigate(user.urls.show); }}>{user.name}</a> : user.name}</h2>{state.page.features.bulk && <UserSelection user={user} state={state} store={store}/>}</header><dl className="lu-details">{columns.map(column => <div key={column.key}><dt>{column.label}</dt><dd><NativeCell user={user} column={column} state={state} store={store}/></dd></div>)}</dl><NativeActions user={user} state={state} store={store}/></article>)}
            {!users.length && <p role="status">{state.page.labels.empty}</p>}
        </div> : <div className={'lu-scroll ' + state.page.classes.scroll}><table className={'lu-native-table ' + state.page.classes.table}>
            <caption>{state.page.labels.directory}</caption>
            <thead><tr>{state.page.features.bulk && <th scope="col"><button type="button" className="lu-button lu-secondary" onClick={store.selectAll}>{state.page.labels.select_all}</button></th>}{columns.map(column => <th key={column.key} scope="col" aria-sort={state.table.sort === column.key ? (state.table.direction === 'asc' ? 'ascending' : 'descending') : undefined}>{state.page.features.sorting && column.sortable !== false ? <button type="button" className="lu-native-sort" onClick={() => store.sortBy(column.key)}>{column.label}</button> : column.label}</th>)}<th scope="col">{state.page.labels.actions}</th></tr></thead>
            <tbody>{users.map(user => <tr key={user.id}>{state.page.features.bulk && <td><UserSelection user={user} state={state} store={store}/></td>}{columns.map(column => <td key={column.key}><NativeCell user={user} column={column} state={state} store={store}/></td>)}<td><NativeActions user={user} state={state} store={store}/></td></tr>)}{!users.length && <tr><td colSpan={columns.length + 1 + Number(state.page.features.bulk)} role="status">{state.page.labels.empty}</td></tr>}</tbody>
        </table></div>}
    </div>;
}

function NativeField({ field, form, state, store, dialog }) {
    const value = getValue(state.values[form.id], field.key);
    const id = `lu-field-${form.id}-${field.key.replaceAll('.', '-')}${dialog ? '-dialog' : ''}`;
    const error = (state.errors[form.id]?.[field.key] ?? []).join(' ');
    const disabled = field.disabled || state.busy || (field.nullable && value === null);
    const description = [field.help ? `${id}-help` : '', error ? `${id}-error` : ''].filter(Boolean).join(' ') || undefined;
    const control = { id, name: field.name, required: field.required, disabled, 'aria-invalid': error ? 'true' : undefined, 'aria-describedby': description };
    const set = value => store.setValue(form.id, field.key, value);
    return <div className={'lu-field ' + state.page.classes.field}>
        <label htmlFor={id} className={(field.type === 'checkbox' ? 'lu-check ' : '') + state.page.classes['field-label']}>{field.type === 'checkbox' && <input {...control} className="lu-checkbox" type="checkbox" checked={Boolean(value)} onChange={event => set(event.target.checked)}/>}<span>{field.label}</span></label>
        <div className={'lu-control ' + state.page.classes['field-control']}>
            {field.nullable && <button type="button" className="lu-button lu-secondary" aria-pressed={value === null} disabled={field.disabled || state.busy} onClick={() => store.toggleInheritance(form.id, field.key)}>{state.page.labels.appearance_inherit}</button>}
            {field.type !== 'checkbox' && <div className={'lu-input-group ' + state.page.classes.control}>
                {field.type === 'select' ? <select {...control} className={'lu-input ' + state.page.classes.select} multiple={field.multiple} value={field.multiple ? (value ?? []).map(String) : String(value ?? '')} onChange={event => set(field.multiple ? Array.from(event.target.selectedOptions, option => option.value) : event.target.value)}>{field.options.map(option => <option key={option.value} value={option.value} disabled={option.disabled}>{option.label}</option>)}</select> : field.type === 'textarea' ? <textarea {...control} className={'lu-input ' + state.page.classes.input} value={value ?? ''} maxLength={field.maxlength} onChange={event => set(event.target.value)}/> : <input {...control} className={'lu-input ' + state.page.classes.input} type={field.type} value={value ?? (field.type === 'color' ? field.fallback : '')} min={field.min} max={field.max} maxLength={field.maxlength} autoComplete={field.type === 'password' ? 'new-password' : undefined} onChange={event => set(event.target.value)}/>}
            </div>}
            {field.help && <p id={id + '-help'} className="lu-muted">{field.help}</p>}{error && <p id={id + '-error'} className="lu-field-error" role="alert">{error}</p>}
        </div>
    </div>;
}

function NativeForm({ form, state, store, dialog = false }) {
    const sections = formSections(form);
    const feedback = passwordFeedback(state.values[form.id]?.password ?? '', state.values[form.id]?.password_confirmation ?? '', state.page.data.password);
    return <form method="POST" action={form.action ?? undefined} className="lu-form lu-pad" data-lu-native-form={form.id} onSubmit={event => { event.preventDefault(); store.submit(form.id, dialog); }}>
        <input type="hidden" name="_token" value={state.page.csrf ?? ''}/>{form.method !== 'POST' && <input type="hidden" name="_method" value={form.method}/>}
        {!dialog && <h2>{form.title}</h2>}{form.help && <p className="lu-muted">{form.help}</p>}{form.confirm && <p>{form.confirm}</p>}
        {form.tabs && sections.length > 1 && <div className="lu-settings-tabs" role="tablist" aria-label={form.title}>{sections.map(section => <button key={section} type="button" id={`lu-tab-${form.id}-${section}${dialog ? '-dialog' : ''}`} role="tab" aria-controls={`lu-panel-${form.id}-${section}${dialog ? '-dialog' : ''}`} aria-selected={state.tabs[form.id] === section} onClick={() => store.setTab(form.id, section)}>{labelSection(section)}</button>)}</div>}
        <fieldset disabled={form.disabled || state.busy} hidden={state.preview?.form === form.id}><legend className="lu-sr-only">{form.title}</legend>
            {sections.map(section => <section key={section} id={`lu-panel-${form.id}-${section}${dialog ? '-dialog' : ''}`} hidden={form.tabs && sections.length > 1 && state.tabs[form.id] !== section} role={form.tabs && sections.length > 1 ? 'tabpanel' : undefined} aria-labelledby={form.tabs && sections.length > 1 ? `lu-tab-${form.id}-${section}${dialog ? '-dialog' : ''}` : undefined}>{form.fields.filter(field => field.type !== 'hidden' && field.section === section && fieldVisible(field, state.values[form.id])).map(field => <NativeField key={field.key} field={field} form={form} state={state} store={store} dialog={dialog}/>)}</section>)}
            {state.page.features.password_meter && feedback && state.values[form.id]?.password && <div className="lu-password-meter"><p>{state.page.labels.password_strength}: <strong>{feedback.label}</strong></p><meter min="0" max="4" value={feedback.score} aria-label={state.page.labels.password_strength}/></div>}
            {state.page.features.password_feedback && feedback?.mismatch && <p className="lu-password-confirmation-error" role="status">{state.page.labels.password_mismatch}</p>}
        </fieldset>
        {state.preview?.form === form.id && <section><button type="button" className="lu-button lu-secondary" onClick={store.editPreview}>{state.page.labels.email_back_editing}</button><p className="lu-muted">{state.preview.recipient}</p><iframe title={state.page.labels.email_preview_frame} sandbox="" referrerPolicy="no-referrer" srcDoc={state.preview.html}/></section>}
        {form.preview && state.preview?.form !== form.id && <button type="button" className="lu-button lu-secondary" disabled={state.busy} onClick={() => store.preview(form.id)}>{state.page.labels.email_preview}</button>}
        <div className="lu-actions lu-form-actions"><button type="submit" className={'lu-button ' + (form.danger ? 'lu-danger' : 'lu-success')} disabled={state.busy || !store.ready(form.id)}>{form.submit}</button>{dialog && <button type="button" className="lu-button lu-secondary" disabled={state.busy} onClick={store.closeDialog}>{state.page.labels.cancel}</button>}</div>
    </form>;
}

function NativeDialog({ state, store }) {
    const form = store.activeForm();
    if (!form) return null;
    return <div className="lu-native-dialog-backdrop"><section className="lu-email-dialog lu-native-dialog" role="dialog" aria-modal="true" aria-labelledby="lu-native-action-title" tabIndex="-1" data-lu-native-dialog><header className="lu-email-heading"><h2 id="lu-native-action-title">{form.title}</h2><button type="button" className="lu-email-close" aria-label={state.page.labels.close} disabled={state.busy} onClick={store.closeDialog}>{state.page.labels.close}</button></header><div className="lu-email-body"><NativeForm form={form} state={state} store={store} dialog/></div></section></div>;
}

export default function UsersApp({ store }) {
    const [state, setState] = useState(store.getSnapshot());
    useEffect(() => store.subscribe(setState), [store]);
    useEffect(() => observeDialogs(document.getElementById('lu-native-app'), () => { if (!store.getSnapshot().busy) store.closeDialog(); }), [store]);
    const { page } = state;
    const user = page.data.user;
    return <div data-lu-native-screen={page.screen} aria-busy={state.busy}>
        {[...page.flash, ...(state.notice ? [state.notice] : [])].map((message, index) => <p key={index} className="lu-alert" role={message.type === 'error' ? 'alert' : 'status'}>{message.message}</p>)}
        {page.data.banner && <div className="lu-alert" role="status">{page.data.banner.message}<button type="button" className="lu-button lu-secondary" disabled={state.busy} onClick={store.submitBanner}>{page.data.banner.label}</button></div>}
        <section className={'lu-panel ' + page.classes.panel}>
            <header className="lu-heading lu-card-heading"><h1 tabIndex="-1" data-lu-native-heading>{page.title}</h1><div className="lu-actions">{page.data.navigation.map(link => <a key={link.url} href={link.url} className="lu-button lu-secondary" onClick={event => { event.preventDefault(); store.navigate(link.url); }}>{link.label}</a>)}{page.features.theme_toggle && <button type="button" className="lu-button lu-secondary" onClick={store.toggleTheme}>{page.theme === 'dark' ? page.labels.theme_light : page.labels.theme_dark}</button>}</div></header>
            {page.data.notice && <p className="lu-pad" role="status">{page.data.notice}</p>}
            {['users', 'deleted-users'].includes(page.screen) && <>
                {page.features.search && <form className="lu-search" onSubmit={event => { event.preventDefault(); store.search(); }}><label className="lu-sr-only" htmlFor="lu-native-search">{page.labels.search}</label><input id="lu-native-search" className="lu-input" type="search" maxLength="255" value={state.search} onChange={event => store.setSearch(event.target.value)}/><button type="submit" className="lu-button" disabled={state.busy}>{page.labels.search}</button></form>}
                <NativeTable state={state} store={store}/>
                <div className="lu-pagination">{page.features.show_count && <span>{page.labels.total_users.replace(':count', page.data.pagination.total)}</span>}{page.data.pagination.enabled && <nav className="lu-actions" aria-label={page.labels.pagination}>{page.data.pagination.previous && <a href={page.data.pagination.previous} className="lu-button lu-secondary" onClick={event => { event.preventDefault(); store.navigate(page.data.pagination.previous); }}>{page.labels.previous}</a>}<span>{page.labels.page.replace(':page', page.data.pagination.current).replace(':total', page.data.pagination.last)}</span>{page.data.pagination.next && <a href={page.data.pagination.next} className="lu-button lu-secondary" onClick={event => { event.preventDefault(); store.navigate(page.data.pagination.next); }}>{page.labels.next}</a>}</nav>}</div>
            </>}
            {['show-user', 'account'].includes(page.screen) && <div className="lu-profile-body"><header className="lu-native-profile"><NativeAvatar avatar={user.avatar}/><div><h2>{user.full_name ?? user.name}</h2><p>{user.email}</p></div></header>{user.pending_email && <p role="status">{page.labels.account_email_pending_to.replace(':email', user.pending_email)}</p>}{page.screen === 'show-user' && <><dl className="lu-profile-details">{page.data.columns.filter(column => column.type !== 'avatar').map(column => <div key={column.key} className="lu-detail"><dt>{column.label}</dt><dd><NativeCell user={user} column={column} state={state} store={store}/></dd></div>)}</dl>{Boolean(user.permissions?.length) && <p>{page.labels.direct_permissions}: {user.permissions.map(item => item.label).join(', ')}</p>}{Boolean(user.role_level) && <p>{page.labels.role_level.replace(':level', user.role_level)}</p>}<NativeActions user={user} state={state} store={store}/></>}</div>}
            {page.data.form_ids.map(id => <NativeForm key={id} form={page.forms[id]} state={state} store={store}/>)}
            {Boolean(page.data.settings_actions?.length) && <div className="lu-pad lu-actions">{page.data.settings_actions.map(action => <button key={action.name} type="button" className={'lu-button ' + (action.class ?? 'lu-secondary')} disabled={action.disabled || state.busy} onClick={() => store.openSettingsAction(action.name)}>{action.label}</button>)}</div>}
        </section>
        <NativeDialog state={state} store={store}/>
    </div>;
}
