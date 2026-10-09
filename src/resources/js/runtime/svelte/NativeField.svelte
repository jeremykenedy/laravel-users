<script>
    import { getValue } from '../shared.js';
    let { field, form, state, store, dialog = false } = $props();
    const value = $derived(getValue(state.values[form.id], field.key));
    const id = $derived(`lu-field-${form.id}-${field.key.replaceAll('.', '-')}${dialog ? '-dialog' : ''}`);
    const error = $derived((state.errors[form.id]?.[field.key] ?? []).join(' '));
    const disabled = $derived(field.disabled || state.busy || (field.nullable && value === null));
    const description = $derived([field.help ? `${id}-help` : '', error ? `${id}-error` : ''].filter(Boolean).join(' ') || undefined);
    const set = value => store.setValue(form.id, field.key, value);
</script>

<div class={'lu-field ' + state.page.classes.field}>
    <label for={id} class={(field.type === 'checkbox' ? 'lu-check ' : '') + state.page.classes['field-label']}>
        {#if field.type === 'checkbox'}<input {id} class="lu-checkbox" type="checkbox" name={field.name} checked={Boolean(value)} {disabled} required={field.required} aria-invalid={error ? 'true' : undefined} aria-describedby={description} onchange={event => set(event.target.checked)}>{/if}<span>{field.label}</span>
    </label>
    <div class={'lu-control ' + state.page.classes['field-control']}>
        {#if field.nullable}<button type="button" class="lu-button lu-secondary" aria-pressed={value === null} disabled={field.disabled || state.busy} onclick={() => store.toggleInheritance(form.id, field.key)}>{state.page.labels.appearance_inherit}</button>{/if}
        {#if field.type !== 'checkbox'}<div class={'lu-input-group ' + state.page.classes.control}>
            {#if field.type === 'select'}<select {id} name={field.name} class={'lu-input ' + state.page.classes.select} multiple={field.multiple} required={field.required} {disabled} aria-invalid={error ? 'true' : undefined} aria-describedby={description} onchange={event => set(field.multiple ? Array.from(event.target.selectedOptions, option => option.value) : event.target.value)}>{#each field.options as option (option.value)}<option value={option.value} disabled={option.disabled} selected={field.multiple ? (value ?? []).map(String).includes(String(option.value)) : String(value ?? '') === String(option.value)}>{option.label}</option>{/each}</select>
            {:else if field.type === 'textarea'}<textarea {id} name={field.name} class={'lu-input ' + state.page.classes.input} value={value ?? ''} required={field.required} {disabled} maxlength={field.maxlength} aria-invalid={error ? 'true' : undefined} aria-describedby={description} oninput={event => set(event.target.value)}></textarea>
            {:else}<input {id} name={field.name} class={'lu-input ' + state.page.classes.input} type={field.type} value={value ?? (field.type === 'color' ? field.fallback : '')} required={field.required} {disabled} min={field.min} max={field.max} maxlength={field.maxlength} autocomplete={field.type === 'password' ? 'new-password' : undefined} aria-invalid={error ? 'true' : undefined} aria-describedby={description} oninput={event => set(event.target.value)}>{/if}
        </div>{/if}
        {#if field.help}<p id={id + '-help'} class="lu-muted">{field.help}</p>{/if}{#if error}<p id={id + '-error'} class="lu-field-error" role="alert">{error}</p>{/if}
    </div>
</div>
