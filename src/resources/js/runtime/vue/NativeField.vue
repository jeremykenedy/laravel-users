<script setup>
import { computed } from 'vue';
import { displayValue, getValue } from '../shared.js';
const props = defineProps({ field: Object, form: Object, state: Object, store: Object, dialog: Boolean });
const value = computed(() => getValue(props.state.values[props.form.id], props.field.key));
const id = computed(() => `lu-field-${props.form.id}-${props.field.key.replaceAll('.', '-')}${props.dialog ? '-dialog' : ''}`);
const error = computed(() => (props.state.errors[props.form.id]?.[props.field.key] ?? []).join(' '));
const disabled = computed(() => props.field.disabled || props.state.busy || (props.field.nullable && value.value === null));
const description = computed(() => [props.field.help ? `${id.value}-help` : '', error.value ? `${id.value}-error` : ''].filter(Boolean).join(' ') || undefined);
const set = value => props.store.setValue(props.form.id, props.field.key, value);
</script>

<template>
    <div :class="['lu-field', state.page.classes.field]">
        <label :for="id" :class="[field.type === 'checkbox' ? 'lu-check' : '', state.page.classes['field-label']]">
            <input v-if="field.type === 'checkbox'" :id="id" class="lu-checkbox" type="checkbox" :name="field.name" :checked="Boolean(value)" :disabled="disabled" :required="field.required" :aria-invalid="error ? 'true' : undefined" :aria-describedby="description" @change="set($event.target.checked)">
            <span>{{ field.label }}</span>
        </label>
        <div :class="['lu-control', state.page.classes['field-control']]">
            <button v-if="field.nullable" type="button" class="lu-button lu-secondary" :aria-pressed="value === null" :disabled="field.disabled || state.busy" @click="store.toggleInheritance(form.id, field.key)">{{ field.inherit_label ?? state.page.labels.appearance_inherit }}</button>
            <div v-if="field.type !== 'checkbox'" :class="['lu-input-group', state.page.classes.control]">
                <select v-if="field.type === 'select'" :id="id" :name="field.name" :class="['lu-input', state.page.classes.select]" :multiple="field.multiple" :required="field.required" :disabled="disabled" :aria-invalid="error ? 'true' : undefined" :aria-describedby="description" @change="set(field.multiple ? Array.from($event.target.selectedOptions, option => option.value) : $event.target.value)"><option v-for="option in field.options" :key="option.value" :value="option.value" :disabled="option.disabled" :selected="field.multiple ? (value ?? []).map(String).includes(String(option.value)) : String(value ?? '') === String(option.value)">{{ option.label }}</option></select>
                <textarea v-else-if="field.type === 'textarea'" :id="id" :name="field.name" :class="['lu-input', state.page.classes.input]" :value="value ?? ''" :required="field.required" :disabled="disabled" :maxlength="field.maxlength" :aria-invalid="error ? 'true' : undefined" :aria-describedby="description" @input="set($event.target.value)"></textarea>
                <input v-else :id="id" :name="field.name" :class="['lu-input', state.page.classes.input]" :type="field.type" :value="displayValue(field, state.values[form.id])" :required="field.required" :disabled="disabled" :min="field.min" :max="field.max" :step="field.step" :maxlength="field.maxlength" :autocomplete="field.type === 'password' ? 'new-password' : undefined" :aria-invalid="error ? 'true' : undefined" :aria-describedby="description" @input="set($event.target.value)">
            </div>
            <p v-if="field.help" :id="id + '-help'" class="lu-muted">{{ field.help }}</p>
            <p v-if="error" :id="id + '-error'" class="lu-field-error" role="alert">{{ error }}</p>
        </div>
    </div>
</template>
