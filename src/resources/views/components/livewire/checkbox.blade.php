@props(['name', 'label', 'model', 'value' => null, 'disabled' => false, 'help' => null, 'errorName' => null, 'errors' => null])
@php
    $id = $attributes->get('id', 'lu-field-'.str_replace(['.', '_'], '-', $model).($value === null ? '' : '-'.$value));
    $error = $errors?->first($errorName ?? $model);
@endphp
<div class="lu-field">
    <label class="lu-check" for="{{ $id }}">
        <input {{ $attributes->except('id')->merge(['class' => 'lu-checkbox']) }} type="checkbox" id="{{ $id }}" name="{{ $name }}" wire:model="{{ $model }}" @if($value !== null) value="{{ $value }}" @endif @if($disabled) disabled @endif @if($error) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif>
        <span>{{ $label }}</span>
    </label>
    @if($help)<p class="lu-muted">{{ $help }}</p>@endif
    @if($error)<p id="{{ $id }}-error" class="lu-field-error">{{ $error }}</p>@endif
</div>
