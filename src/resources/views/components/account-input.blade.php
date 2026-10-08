@props(['name', 'label', 'icon' => 'user', 'type' => 'text', 'value' => null, 'required' => true])
<div class="lu-account-field">
    <label for="{{ $attributes->get('id', $name) }}">{{ $label }}</label>
    <div class="lu-input-group"><input {{ $attributes->merge(['class' => 'lu-input', 'id' => $name]) }} name="{{ $name }}" type="{{ $type }}" @if($type !== 'password') value="{{ old($name, $value) }}" maxlength="255" @else autocomplete="{{ $name === 'current_password' ? 'current-password' : 'new-password' }}" @endif @if($required) required @endif @error($name) aria-invalid="true" @enderror><label class="lu-input-icon" for="{{ $attributes->get('id', $name) }}">@include('laravelusers::partials.icon', ['name' => $icon])<span class="lu-sr-only">{{ $label }}</span></label></div>
    @error($name)<p class="lu-field-error">{{ $message }}</p>@enderror
</div>
