<script setup>
import { computed } from 'vue';
import { fieldVisible, formSections, labelSection } from '../store.js';
import { passwordFeedback } from '../shared.js';
import NativeField from './NativeField.vue';
import AppearancePreview from './AppearancePreview.vue';
const props = defineProps({ form: Object, state: Object, store: Object, dialog: Boolean });
const sections = computed(() => formSections(props.form));
const feedback = computed(() => passwordFeedback(props.state.values[props.form.id]?.password ?? '', props.state.values[props.form.id]?.password_confirmation ?? '', props.state.page.data.password));
</script>

<template>
    <form method="POST" :action="form.action" class="lu-form lu-pad" :data-lu-native-form="form.id" @submit.prevent="store.submit(form.id, dialog)">
        <input type="hidden" name="_token" :value="state.page.csrf ?? ''">
        <input v-if="form.method !== 'POST'" type="hidden" name="_method" :value="form.method">
        <h2 v-if="!dialog">{{ form.title }}</h2>
        <p v-if="form.help" class="lu-muted">{{ form.help }}</p><p v-if="form.confirm">{{ form.confirm }}</p>
        <div v-if="form.tabs && sections.length > 1" class="lu-settings-tabs" role="tablist" :aria-label="form.title"><button v-for="section in sections" :key="section" type="button" :id="`lu-tab-${form.id}-${section}${dialog ? '-dialog' : ''}`" role="tab" :aria-controls="`lu-panel-${form.id}-${section}${dialog ? '-dialog' : ''}`" :aria-selected="state.tabs[form.id] === section" @click="store.setTab(form.id, section)">{{ labelSection(section) }}</button></div>
        <fieldset :disabled="form.disabled || state.busy" :hidden="state.preview?.form === form.id"><legend class="lu-sr-only">{{ form.title }}</legend>
            <component :is="form.accordion && section.startsWith('email-') ? 'details' : 'section'" :class="form.accordion && section.startsWith('email-') ? 'lu-email-template' : undefined" v-for="section in sections" :key="section" :id="`lu-panel-${form.id}-${section}${dialog ? '-dialog' : ''}`" :hidden="form.tabs && sections.length > 1 && state.tabs[form.id] !== section" :role="form.tabs ? 'tabpanel' : undefined" :aria-labelledby="form.tabs ? `lu-tab-${form.id}-${section}${dialog ? '-dialog' : ''}` : undefined"><summary v-if="form.accordion && section.startsWith('email-')"><span>{{ state.page.labels['email_template_' + section.slice(6)] }}</span></summary><template v-for="field in form.fields" :key="field.key"><NativeField v-if="field.type !== 'hidden' && field.section === section && fieldVisible(field, state.values[form.id])" :field="field" :form="form" :state="state" :store="store" :dialog="dialog"/></template><AppearancePreview v-if="form.id === 'settings' && section === 'appearance'" :state="state"/></component>
            <div v-if="state.page.features.password_meter && feedback && state.values[form.id]?.password" class="lu-password-meter"><p>{{ state.page.labels.password_strength }}: <strong>{{ feedback.label }}</strong></p><meter min="0" max="4" :value="feedback.score" :aria-label="state.page.labels.password_strength"></meter></div>
            <p v-if="state.page.features.password_feedback && feedback?.mismatch" class="lu-password-confirmation-error" role="status">{{ state.page.labels.password_mismatch }}</p>
        </fieldset>
        <section v-if="state.preview?.form === form.id"><button type="button" class="lu-button lu-secondary" @click="store.editPreview">{{ state.page.labels.email_back_editing }}</button><p class="lu-muted">{{ state.preview.recipient }}</p><iframe :title="state.page.labels.email_preview_frame" sandbox="" referrerpolicy="no-referrer" :srcdoc="state.preview.html"></iframe></section>
        <button v-if="form.preview && state.preview?.form !== form.id" type="button" class="lu-button lu-secondary" :disabled="state.busy" @click="store.preview(form.id)">{{ state.page.labels.email_preview }}</button>
        <div class="lu-actions lu-form-actions"><button type="submit" :class="['lu-button', form.danger ? 'lu-danger' : 'lu-success']" :disabled="state.busy || !store.ready(form.id)">{{ form.submit }}</button><button v-if="dialog" type="button" class="lu-button lu-secondary" :disabled="state.busy" @click="store.closeDialog">{{ state.page.labels.cancel }}</button></div>
    </form>
</template>
