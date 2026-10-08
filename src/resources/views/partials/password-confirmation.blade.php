@if(config('laravelusers.password.confirmation_feedback', false))
    <p id="lu-password-confirmation-error" class="lu-password-confirmation-error" aria-live="polite" hidden>{{ __('laravelusers::ui.password_mismatch') }}</p>
@endif
