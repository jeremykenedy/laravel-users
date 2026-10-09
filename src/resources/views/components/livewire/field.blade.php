@props(['name', 'label', 'model', 'type' => 'text', 'options' => [], 'multiple' => false, 'required' => false, 'disabled' => false, 'help' => null, 'placeholder' => null, 'errorName' => null, 'errors' => null, 'live' => false])
@php
    $id = $attributes->get('id', 'lu-field-'.str_replace(['.', '_'], '-', $model));
    $errorName = $errorName ?? $model;
    $error = $errors?->first($errorName);
    $description = trim(($help ? $id.'-help ' : '').($error ? $id.'-error' : ''));
@endphp
<div class="lu-field {{ \jeremykenedy\laravelusers\Support\Frontend::classes('field') }}">
    <label for="{{ $id }}" class="{{ \jeremykenedy\laravelusers\Support\Frontend::classes('field-label') }}">{{ $label }}</label>
    <div class="lu-control {{ \jeremykenedy\laravelusers\Support\Frontend::classes('field-control') }}">
        <div class="lu-input-group {{ \jeremykenedy\laravelusers\Support\Frontend::classes('control') }}">
        @if($type === 'select')
            <select {{ $attributes->except('id')->merge(['class' => 'lu-input '.\jeremykenedy\laravelusers\Support\Frontend::classes('select')]) }} id="{{ $id }}" name="{{ $name }}" @if($live) wire:model.live="{{ $model }}" @else wire:model="{{ $model }}" @endif @if($multiple) multiple @endif @if($required) required @endif @if($disabled) disabled @endif @if($error) aria-invalid="true" @endif @if($description) aria-describedby="{{ $description }}" @endif>
                @if($placeholder)<option value="">{{ $placeholder }}</option>@endif
                @foreach($options as $option)
                    <option value="{{ $option['value'] }}" @if($option['disabled'] ?? false) disabled @endif>{{ $option['label'] }}</option>
                @endforeach
            </select>
        @elseif($type === 'textarea')
            <textarea {{ $attributes->except('id')->merge(['class' => 'lu-input '.\jeremykenedy\laravelusers\Support\Frontend::classes('input')]) }} id="{{ $id }}" name="{{ $name }}" @if($live) wire:model.live.debounce.250ms="{{ $model }}" @else wire:model="{{ $model }}" @endif @if($required) required @endif @if($disabled) disabled @endif @if($placeholder) placeholder="{{ $placeholder }}" @endif @if($error) aria-invalid="true" @endif @if($description) aria-describedby="{{ $description }}" @endif></textarea>
        @else
            <input {{ $attributes->except('id')->merge(['class' => 'lu-input '.\jeremykenedy\laravelusers\Support\Frontend::classes('input')]) }} id="{{ $id }}" name="{{ $name }}" type="{{ $type }}" @if($live) wire:model.live.debounce.250ms="{{ $model }}" @else wire:model="{{ $model }}" @endif @if($required) required @endif @if($disabled) disabled @endif @if($placeholder) placeholder="{{ $placeholder }}" @endif @if($type === 'password') autocomplete="new-password" @endif @if($error) aria-invalid="true" @endif @if($description) aria-describedby="{{ $description }}" @endif>
        @endif
        </div>
        @if($help)<p id="{{ $id }}-help" class="lu-muted">{{ $help }}</p>@endif
        @if($error)<p id="{{ $id }}-error" class="lu-field-error">{{ $error }}</p>@endif
    </div>
</div>
