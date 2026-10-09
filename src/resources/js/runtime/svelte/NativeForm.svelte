<script>
    import { fieldVisible, formSections, labelSection } from '../store.js';
    import { passwordFeedback } from '../shared.js';
    import NativeField from './NativeField.svelte';
    import AppearancePreview from './AppearancePreview.svelte';
    let { form, state, store, dialog = false } = $props();
    const sections = $derived(formSections(form));
    const feedback = $derived(passwordFeedback(state.values[form.id]?.password ?? '', state.values[form.id]?.password_confirmation ?? '', state.page.data.password));
</script>

<form method="POST" action={form.action ?? undefined} class="lu-form lu-pad" data-lu-native-form={form.id} onsubmit={event => { event.preventDefault(); store.submit(form.id, dialog); }}>
    <input type="hidden" name="_token" value={state.page.csrf ?? ''}>{#if form.method !== 'POST'}<input type="hidden" name="_method" value={form.method}>{/if}
    {#if !dialog}<h2>{form.title}</h2>{/if}{#if form.help}<p class="lu-muted">{form.help}</p>{/if}{#if form.confirm}<p>{form.confirm}</p>{/if}
    {#if form.tabs && sections.length > 1}<div class="lu-settings-tabs" role="tablist" aria-label={form.title}>{#each sections as section}<button type="button" id={`lu-tab-${form.id}-${section}${dialog ? '-dialog' : ''}`} role="tab" aria-controls={`lu-panel-${form.id}-${section}${dialog ? '-dialog' : ''}`} aria-selected={state.tabs[form.id] === section} onclick={() => store.setTab(form.id, section)}>{labelSection(section)}</button>{/each}</div>{/if}
    <fieldset disabled={form.disabled || state.busy} hidden={state.preview?.form === form.id}><legend class="lu-sr-only">{form.title}</legend>
        {#each sections as section}<svelte:element this={form.accordion && section.startsWith('email-') ? 'details' : 'section'} class={form.accordion && section.startsWith('email-') ? 'lu-email-template' : undefined} id={`lu-panel-${form.id}-${section}${dialog ? '-dialog' : ''}`} hidden={form.tabs && sections.length > 1 && state.tabs[form.id] !== section} role={form.tabs && sections.length > 1 ? 'tabpanel' : undefined} aria-labelledby={form.tabs && sections.length > 1 ? `lu-tab-${form.id}-${section}${dialog ? '-dialog' : ''}` : undefined}>{#if form.accordion && section.startsWith('email-')}<summary><span>{state.page.labels['email_template_' + section.slice(6)]}</span></summary>{/if}{#each form.fields as field (field.key)}{#if field.type !== 'hidden' && field.section === section && fieldVisible(field, state.values[form.id])}<NativeField {field} {form} {state} {store} {dialog}/>{/if}{/each}{#if form.id === 'settings' && section === 'appearance'}<AppearancePreview {state}/>{/if}</svelte:element>{/each}
        {#if state.page.features.password_meter && feedback && state.values[form.id]?.password}<div class="lu-password-meter"><p>{state.page.labels.password_strength}: <strong>{feedback.label}</strong></p><meter min="0" max="4" value={feedback.score} aria-label={state.page.labels.password_strength}></meter></div>{/if}
        {#if state.page.features.password_feedback && state.passwordMismatch[form.id]}<p class="lu-password-confirmation-error" role="status">{state.page.labels.password_mismatch}</p>{/if}
    </fieldset>
    {#if state.preview?.form === form.id}<section><button type="button" class="lu-button lu-secondary" onclick={store.editPreview}>{state.page.labels.email_back_editing}</button><p class="lu-muted">{state.preview.recipient}</p><iframe title={state.page.labels.email_preview_frame} sandbox="" referrerpolicy="no-referrer" srcdoc={state.preview.html}></iframe></section>{/if}
    {#if form.preview && state.preview?.form !== form.id}<button type="button" class="lu-button lu-secondary" disabled={state.busy} onclick={() => store.preview(form.id)}>{state.page.labels.email_preview}</button>{/if}
    <div class="lu-actions lu-form-actions"><button type="submit" class={'lu-button ' + (form.danger ? 'lu-danger' : 'lu-success')} disabled={state.busy || !store.ready(form.id)}>{form.submit}</button>{#if dialog}<button type="button" class="lu-button lu-secondary" disabled={state.busy} onclick={store.closeDialog}>{state.page.labels.cancel}</button>{/if}</div>
</form>
