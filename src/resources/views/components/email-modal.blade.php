@if(config('laravelusers.emails.enabled', false) && Auth::check())
<dialog id="lu-email-dialog" class="lu-email-dialog" aria-labelledby="lu-email-title">
    <form method="POST" action="{{ route('users.email') }}" id="lu-email-form">
        @csrf
        <input type="hidden" name="deleted" value="{{ old('deleted', '0') }}">
        <input type="hidden" name="email_form" value="1">
        <input type="hidden" name="action" value="{{ old('action', 'message') }}">
        <div data-lu-email-ids>@foreach((array) old('ids', []) as $id)<input type="hidden" name="ids[]" value="{{ $id }}">@endforeach</div>
        <header class="lu-email-heading"><h2 id="lu-email-title">@include('laravelusers::partials.icon', ['name' => 'mail']) <span>{{ __('laravelusers::ui.email_message') }}</span></h2><button type="button" class="lu-email-close" data-lu-email-dismiss aria-label="{{ __('laravelusers::ui.close') }}">@include('laravelusers::partials.icon', ['name' => 'close'])</button></header>
        <div class="lu-email-body">
            <div data-lu-email-editor>
            <p class="lu-muted" data-lu-email-recipient aria-live="polite"></p>
            <div class="lu-email-recipients" data-lu-email-chips role="list" aria-label="{{ __('laravelusers::ui.email_recipients') }}" tabindex="0" hidden></div>
            @if(old('email_form') === '1' && $errors->any())<div role="alert" data-lu-email-validation-errors>@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
            <p data-lu-email-preview-error role="alert" hidden></p>
            <p data-lu-email-notice hidden></p>
            @if(config('laravelusers.emails.reset_duration', true))
            <x-laravelusers::email-expiry :minutes="config('auth.passwords.'.(config('laravelusers.emails.password_broker') ?: config('auth.defaults.passwords')).'.expire', 60)" />
            @endif
            @if(config('laravelusers.account_links.enabled', false))
            <fieldset data-lu-account-options class="lu-email-section" hidden disabled>
                <legend>{{ __('laravelusers::ui.account_links_heading') }}</legend>
                @foreach(['restore', 'force_delete'] as $action)
                @if(config('laravelusers.account_links.'.$action, true) && \jeremykenedy\laravelusers\Support\UserAccess::allows($action === 'restore' ? 'restore_users' : 'force_delete'))<label class="lu-email-check"><input type="hidden" name="include_{{ $action }}" value="0"><input type="checkbox" name="include_{{ $action }}" value="1" @if(old('include_'.$action, false)) checked @endif> {{ __('laravelusers::ui.account_include_'.$action) }}</label>@endif
                @endforeach
                <x-laravelusers::email-expiry prefix="account" :minutes="config('laravelusers.account_links.expire', 60)" />
                <p data-lu-account-notice hidden></p>
            </fieldset>
            @endif
            <div data-lu-email-fields>
                <x-laravelusers::email-fields />
            </div>
            </div>
            <section data-lu-email-preview hidden>
                <button class="lu-button lu-secondary" type="button" data-lu-email-back>@include('laravelusers::partials.icon', ['name' => 'reply']) {{ __('laravelusers::ui.email_back_editing') }}</button>
                <p data-lu-email-preview-recipient class="lu-muted"></p>
                <iframe title="{{ __('laravelusers::ui.email_preview_frame') }}" sandbox="" referrerpolicy="no-referrer" data-lu-email-frame></iframe>
            </section>
        </div>
        <footer class="lu-email-footer">@if(config('laravelusers.emails.preview', true))<button class="lu-button lu-secondary" type="button" data-lu-email-preview-button>@include('laravelusers::partials.icon', ['name' => 'show']) {{ __('laravelusers::ui.email_preview') }}</button>@endif<button class="lu-button lu-secondary" type="button" data-lu-email-dismiss>@include('laravelusers::partials.icon', ['name' => 'close']) {{ __('laravelusers::forms.cancel') }}</button><button class="lu-button" type="submit">@include('laravelusers::partials.icon', ['name' => 'mail']) {{ __('laravelusers::ui.email_send') }}</button></footer>
    </form>
</dialog>
@endif
