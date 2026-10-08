@if(config('laravelusers.softDeletedEnabled', false) && \jeremykenedy\laravelusers\Support\UserAccess::allows('edit_cleanup') && \jeremykenedy\laravelusers\Support\UserAccess::allows('force_delete'))
<section class="lu-package-settings" aria-labelledby="lu-cleanup-title">
    <h2 id="lu-cleanup-title" class="lu-title-heading"><span class="lu-title-icon lu-title-icon-danger">@include('laravelusers::partials.icon', ['name' => 'delete'])</span><span>{{ __('laravelusers::ui.cleanup_title') }}</span></h2>
    <div class="lu-cleanup-warning" role="alert">
        <span class="lu-cleanup-warning-icon">@include('laravelusers::partials.icon', ['name' => 'warning'])</span>
        <p>{{ __('laravelusers::ui.cleanup_warning') }}</p>
    </div>
    <p class="lu-muted text-muted">{{ __('laravelusers::ui.cleanup_hint') }} <a href="https://laravel.com/docs/{{ explode('.', \Illuminate\Foundation\Application::VERSION)[0] }}.x/scheduling#running-the-scheduler" target="_blank" rel="noopener noreferrer">{{ __('laravelusers::ui.cleanup_scheduler') }}</a></p>
    <form id="lu-cleanup-form" method="POST" action="{{ route('users.settings.cleanup') }}">
        @csrf @method('PUT')
        <input type="hidden" name="confirmation" value="">
        <fieldset @if(!$settingsAvailable) disabled @endif>
            <legend class="{{ $modern ? 'lu-sr-only' : 'sr-only' }}">{{ __('laravelusers::ui.cleanup_title') }}</legend>
            <label class="lu-email-check"><input type="hidden" name="enabled" value="0"><input type="checkbox" id="lu-cleanup-enabled" name="enabled" value="1" @if(old('enabled', config('laravelusers.cleanup.enabled', false))) checked @endif> {{ __('laravelusers::ui.cleanup_enable') }}</label>
            <label for="lu-cleanup-amount">{{ __('laravelusers::ui.cleanup_retention') }}</label>
            <div class="lu-reset-duration-row">
                <input class="{{ $modern ? 'lu-input' : 'form-control' }}" id="lu-cleanup-amount" type="number" name="amount" min="1" max="10000" value="{{ old('amount', config('laravelusers.cleanup.amount', 180)) }}">
                <label for="lu-cleanup-unit" class="{{ $modern ? 'lu-sr-only' : 'sr-only' }}">{{ __('laravelusers::ui.cleanup_unit') }}</label>
                <select class="{{ $modern ? 'lu-input' : 'form-control' }}" id="lu-cleanup-unit" name="unit">@foreach(['immediately', 'minutes', 'hours', 'days', 'months', 'years'] as $unit)<option value="{{ $unit }}" @if(old('unit', config('laravelusers.cleanup.unit', 'days')) === $unit) selected @endif>{{ __('laravelusers::ui.cleanup_'.$unit) }}</option>@endforeach</select>
            </div>
            <div class="lu-settings-footer"><button type="submit" class="{{ $modern ? 'lu-button' : 'btn btn-primary' }}">@include('laravelusers::partials.icon', ['name' => 'save']) {{ __('laravelusers::ui.cleanup_save') }}</button></div>
        </fieldset>
    </form>
</section>
<dialog id="lu-cleanup-dialog" class="lu-email-dialog" aria-labelledby="lu-cleanup-confirm-title">
    <header class="lu-email-heading"><h2 id="lu-cleanup-confirm-title">@include('laravelusers::partials.icon', ['name' => 'delete']) {{ __('laravelusers::ui.cleanup_confirm_title') }}</h2><button type="button" class="lu-email-close" data-lu-cleanup-dismiss aria-label="{{ __('laravelusers::ui.close') }}">@include('laravelusers::partials.icon', ['name' => 'close'])</button></header>
    <div class="lu-email-body"><p>{{ __('laravelusers::ui.cleanup_warning') }}</p><p data-lu-cleanup-summary></p><label for="lu-cleanup-confirmation">{{ __('laravelusers::ui.cleanup_confirmation') }}</label><input id="lu-cleanup-confirmation" class="lu-email-input" autocomplete="off" spellcheck="false"></div>
    <footer class="lu-email-footer"><button type="button" class="lu-button lu-secondary" data-lu-cleanup-dismiss>{{ __('laravelusers::forms.cancel') }}</button><button type="button" class="lu-button lu-danger" data-lu-cleanup-confirm disabled>@include('laravelusers::partials.icon', ['name' => 'delete']) {{ __('laravelusers::ui.cleanup_confirm') }}</button></footer>
</dialog>
@endif
