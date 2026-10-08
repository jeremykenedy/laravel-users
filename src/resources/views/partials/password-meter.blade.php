@if(config('laravelusers.password.meter', false))
    @php($passwordSettings = \jeremykenedy\laravelusers\Support\PasswordRules::settings($creating ?? false))
    <div class="lu-password-meter" data-lu-password-meter hidden>
        <div class="lu-password-status"><span>{{ __('laravelusers::ui.password_strength') }}</span><strong data-lu-password-strength aria-live="polite"></strong></div>
        <meter min="0" max="4" low="2" high="3" optimum="4" value="0" aria-label="{{ __('laravelusers::ui.password_strength') }}"></meter>
        <ul>
            <li data-lu-password-rule="length">{{ __($passwordSettings['max'] === null ? 'laravelusers::ui.password_length_min' : 'laravelusers::ui.password_length', $passwordSettings) }}</li>
            @foreach(['mixed_case', 'numbers', 'symbols'] as $rule)@if($passwordSettings[$rule])<li data-lu-password-rule="{{ $rule }}">{{ __('laravelusers::ui.password_'.$rule) }}</li>@endif @endforeach
        </ul>
    </div>
@endif
