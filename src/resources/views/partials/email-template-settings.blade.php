@if(\jeremykenedy\laravelusers\Support\UserAccess::allows('edit_email_templates'))
<section class="lu-package-settings" aria-labelledby="lu-email-templates-title">
    <h2 id="lu-email-templates-title" class="lu-title-heading"><span class="lu-title-icon">@include('laravelusers::partials.icon', ['name' => 'mail'])</span><span>{{ __('laravelusers::ui.email_templates_title') }}</span></h2>
    <p class="lu-muted text-muted">{{ __('laravelusers::ui.email_templates_hint') }}</p>
    <form method="POST" action="{{ route('users.settings.emails') }}" id="lu-email-templates-form">
        @csrf @method('PUT')
        <fieldset @if(!$settingsAvailable) disabled @endif><legend class="lu-sr-only sr-only">{{ __('laravelusers::ui.email_templates_title') }}</legend>
        <label class="lu-email-check"><input type="hidden" name="welcome_enabled" value="0"><input type="checkbox" name="welcome_enabled" value="1" @if(config('laravelusers.welcome.enabled', false)) checked @endif @if(!config('laravelusers.emails.enabled', false) || !config('laravelusers.emails.welcome', true)) disabled @endif> {{ __('laravelusers::ui.email_welcome_enabled') }}</label>
        <label class="lu-email-check"><input type="hidden" name="goodbye" value="0"><input type="checkbox" name="goodbye" value="1" @if(config('laravelusers.emails.goodbye', false)) checked @endif> {{ __('laravelusers::ui.goodbye_enable') }}</label>
        <label class="lu-email-check"><input type="hidden" name="goodbye_on_delete" value="0"><input type="checkbox" name="goodbye_on_delete" value="1" @if(config('laravelusers.emails.goodbye_on_delete', false)) checked @endif> {{ __('laravelusers::ui.goodbye_default') }}</label>
        <details class="lu-settings-choice lu-goodbye-settings"><summary>{{ __('laravelusers::ui.goodbye_options') }}</summary>
        @foreach(['auto_send', 'restore', 'force_delete', 'retention', 'show_expiry'] as $option)
            <label class="lu-email-check"><input type="hidden" name="goodbye_{{ $option }}" value="0"><input type="checkbox" name="goodbye_{{ $option }}" value="1" @if(config('laravelusers.emails.goodbye_'.$option, $option === 'show_expiry')) checked @endif @if(in_array($option, ['restore', 'force_delete'], true) && (!config('laravelusers.softDeletedEnabled', false) || !config('laravelusers.account_links.enabled', false) || !config('laravelusers.account_links.'.$option, true))) disabled @endif> {{ __('laravelusers::ui.goodbye_'.$option) }}</label>
        @endforeach
        <p class="lu-muted text-muted">{{ __('laravelusers::ui.goodbye_links_hint') }}</p>
        <label for="goodbye-expiry-mode">{{ __('laravelusers::ui.goodbye_expiry_mode') }}</label><select id="goodbye-expiry-mode" name="goodbye_expiry_mode" class="lu-email-input">@foreach(['custom', 'cleanup', 'never'] as $mode)<option value="{{ $mode }}" @if(config('laravelusers.emails.goodbye_expiry_mode', 'custom') === $mode) selected @endif @if(($mode === 'cleanup' && !config('laravelusers.cleanup.enabled', false)) || ($mode === 'never' && !config('laravelusers.account_links.allow_never_expire', true))) disabled @endif>{{ __('laravelusers::ui.goodbye_expiry_'.$mode) }}</option>@endforeach</select>
        <div class="lu-reset-duration-row"><div><label for="goodbye-duration">{{ __('laravelusers::ui.email_duration') }}</label><input id="goodbye-duration" type="number" name="goodbye_duration" class="lu-email-input" min="1" max="525600" value="{{ config('laravelusers.emails.goodbye_duration', 60) }}"></div><div><label for="goodbye-unit">{{ __('laravelusers::ui.email_duration_unit') }}</label><select id="goodbye-unit" name="goodbye_unit" class="lu-email-input">@foreach(['minutes', 'hours', 'days'] as $unit)<option value="{{ $unit }}" @if(config('laravelusers.emails.goodbye_unit', 'minutes') === $unit) selected @endif>{{ __('laravelusers::ui.cleanup_'.$unit) }}</option>@endforeach</select></div></div>
        </details>
        @foreach(['welcome', 'reset', 'restore', 'force_delete', 'goodbye'] as $action)
            @php($template = \jeremykenedy\laravelusers\Support\EmailContent::defaults($action))
            <details class="lu-email-template"><summary><span>{{ __('laravelusers::ui.email_template_'.$action) }}</span></summary>
                <label for="template-{{ $action }}-subject">{{ __('laravelusers::ui.email_subject') }}</label>
                <input class="lu-email-input" id="template-{{ $action }}-subject" name="templates[{{ $action }}][subject]" value="{{ old('templates.'.$action.'.subject', $template['subject']) }}" maxlength="150" required>
                <label for="template-{{ $action }}-message">{{ __('laravelusers::ui.email_body') }}</label>
                <textarea class="lu-email-input" id="template-{{ $action }}-message" name="templates[{{ $action }}][message]" rows="4" maxlength="{{ max(1, (int) config('laravelusers.emails.max_length', 10000)) }}" required>{{ old('templates.'.$action.'.message', $template['message']) }}</textarea>
            </details>
        @endforeach
        <div class="lu-settings-footer"><button type="submit" class="{{ $modern ? 'lu-button' : 'btn btn-primary' }}">@include('laravelusers::partials.icon', ['name' => 'save']) {{ __('laravelusers::ui.email_templates_save') }}</button></div></fieldset>
    </form>
</section>
@endif
