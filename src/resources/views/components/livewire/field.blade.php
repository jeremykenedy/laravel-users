@props(['name', 'label', 'model', 'type' => 'text', 'options' => [], 'multiple' => false, 'required' => false, 'disabled' => false, 'help' => null, 'placeholder' => null, 'errorName' => null, 'errors' => null])
@php
    $id = $attributes->get('id', 'lu-field-'.str_replace(['.', '_'], '-', $model));
    $errorName = $errorName ?? $model;
    $error = $errors?->first($errorName);
    $description = trim(($help ? $id.'-help ' : '').($error ? $id.'-error' : ''));
@endphp
<div class="lu-field">
    <label for="{{ $id }}">{{ $label }}</label>
    <div class="lu-control">
        @if($type === 'select')
            <select {{ $attributes->except('id')->merge(['class' => 'lu-input']) }} id="{{ $id }}" name="{{ $name }}" wire:model="{{ $model }}" @if($multiple) multiple @endif @if($required) required @endif @if($disabled) disabled @endif @if($error) aria-invalid="true" @endif @if($description) aria-describedby="{{ $description }}" @endif>
                @if($placeholder)<option value="">{{ $placeholder }}</option>@endif
                @foreach($options as $option)
                    <option value="{{ $option['value'] }}" @if($option['disabled'] ?? false) disabled @endif>{{ $option['label'] }}</option>
                @endforeach
            </select>
        @elseif($type === 'textarea')
            <textarea {{ $attributes->except('id')->merge(['class' => 'lu-input']) }} id="{{ $id }}" name="{{ $name }}" wire:model="{{ $model }}" @if($required) required @endif @if($disabled) disabled @endif @if($placeholder) placeholder="{{ $placeholder }}" @endif @if($error) aria-invalid="true" @endif @if($description) aria-describedby="{{ $description }}" @endif></textarea>
        @else
            <input {{ $attributes->except('id')->merge(['class' => 'lu-input']) }} id="{{ $id }}" name="{{ $name }}" type="{{ $type }}" wire:model="{{ $model }}" @if($required) required @endif @if($disabled) disabled @endif @if($placeholder) placeholder="{{ $placeholder }}" @endif @if($type === 'password') autocomplete="new-password" @endif @if($error) aria-invalid="true" @endif @if($description) aria-describedby="{{ $description }}" @endif>
        @endif
        @if($help)<p id="{{ $id }}-help" class="lu-muted">{{ $help }}</p>@endif
        @if($error)<p id="{{ $id }}-error" class="lu-field-error">{{ $error }}</p>@endif
    </div>
</div>
