    @foreach($fields as $field => $label)
        <div class="lu-field {{ \jeremykenedy\laravelusers\Support\Frontend::classes('field') }}">
            <label class="{{ \jeremykenedy\laravelusers\Support\Frontend::classes('field-label') }}" for="{{ $field }}">{{ __('laravelusers::forms.'.$label) }}</label>
            <div class="lu-control {{ \jeremykenedy\laravelusers\Support\Frontend::classes('field-control') }}"><div class="lu-input-group {{ \jeremykenedy\laravelusers\Support\Frontend::classes('control') }}">
            <input class="lu-input {{ \jeremykenedy\laravelusers\Support\Frontend::classes('input') }}" id="{{ $field }}" name="{{ $field }}"
                type="{{ str_contains($field, 'password') ? 'password' : ($field === 'email' ? 'email' : 'text') }}"
                @if(!str_contains($field, 'password')) value="{{ old($field, isset($user) ? $user->$field : '') }}" maxlength="255" required @else autocomplete="new-password" @if(!isset($user)) required @endif @endif
                @if($errors->has($field)) aria-invalid="true" aria-describedby="{{ $field }}-error" @elseif(isset($user) && $field === 'password') aria-describedby="password-help" @endif>
            @if(config('laravelusers.iconsEnabled', true))<label class="lu-input-icon" for="{{ $field }}">@include('laravelusers::partials.icon', ['name' => str_contains($field, 'password') ? 'lock' : ($field === 'email' ? 'mail' : 'user')])</label>@endif
            </div>
            @if(isset($user) && $field === 'password')<p id="password-help" class="lu-muted">{{ __('laravelusers::ui.password_hint') }}</p>@endif
            @if($field === 'password')@include('laravelusers::partials.password-meter', ['creating' => !isset($user)])@endif
            @if($field === 'password_confirmation')@include('laravelusers::partials.password-confirmation')@endif
            @if($errors->has($field))<p class="lu-field-error" id="{{ $field }}-error">{{ $errors->first($field) }}</p>@endif
            </div>
        </div>
    @endforeach
