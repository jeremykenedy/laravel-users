@if(config('laravelusers.welcome.enabled', true))
    <fieldset class="lu-welcome mb-3">
        <legend class="lu-muted" style="font-size: 1rem;">{{ __('laravelusers::ui.send_welcome') }}</legend>
        <label><input type="checkbox" name="send_welcome_email" value="1" @if(old('send_welcome_email')) checked @endif> {{ __('laravelusers::ui.send_welcome') }}</label>
        @if(config('laravelusers.welcome.force_password_reset', true))
            <label><input type="checkbox" name="force_password_reset" value="1" @if(old('force_password_reset')) checked @endif> {{ __('laravelusers::ui.force_reset') }}</label>
            <p class="lu-muted text-muted">{{ __('laravelusers::ui.reset_notice') }}</p>
        @endif
    </fieldset>
@endif
