@if($packageManagementAllowed ?? false)
<section class="lu-package-settings" aria-labelledby="lu-packages-title">
    <h2 id="lu-packages-title" class="lu-title-heading"><span class="lu-title-icon">@include('laravelusers::partials.icon', ['name' => 'package'])</span><span>{{ __('laravelusers::ui.settings_packages') }}</span></h2>
    <p class="lu-muted text-muted">{{ __('laravelusers::ui.packages_hint') }}</p>
    <p data-lu-package-requirements-warning role="status" @if($packageQueueReady) hidden @endif>{{ __('laravelusers::ui.packages_queue_required') }}</p>
    <div class="lu-package-requirement-actions">
        <button type="button" class="{{ $modern ? 'lu-button lu-secondary' : 'btn btn-outline-secondary btn-sm' }}" data-lu-package="requirements" data-lu-package-name="{{ __('laravelusers::ui.package_requirements') }}" data-lu-package-operation="setup" @if($packageQueueReady) disabled @endif>
            <span data-lu-package-requirements-icon="setup" @if($packageQueueReady) hidden @endif>@include('laravelusers::partials.icon', ['name' => 'settings'])</span>
            <span data-lu-package-requirements-icon="complete" @unless($packageQueueReady) hidden @endunless>@include('laravelusers::partials.icon', ['name' => 'check'])</span>
            <span data-lu-package-requirements-label>{{ __($packageQueueReady ? 'laravelusers::ui.package_requirements_completed' : 'laravelusers::ui.package_requirements_setup') }}</span>
        </button>
        <button type="button" class="{{ $modern ? 'lu-button lu-secondary' : 'btn btn-outline-secondary btn-sm' }}" data-lu-package-verify>
            @include('laravelusers::partials.icon', ['name' => 'verify']) <span data-lu-package-verify-label>{{ __($packageQueueReady ? 'laravelusers::ui.package_requirements_reverify' : 'laravelusers::ui.package_requirements_verify') }}</span>
        </button>
    </div>
    <p class="lu-muted text-muted">{{ __('laravelusers::ui.package_requirements_hint') }}</p>
    @php($laravelDocs = 'https://laravel.com/docs/'.explode('.', \Illuminate\Foundation\Application::VERSION)[0].'.x')
    <p class="lu-package-help"><a href="{{ $laravelDocs }}/queues#introduction" target="_blank" rel="noopener noreferrer">{{ __('laravelusers::ui.packages_queue_setup') }}</a> · <a href="{{ $laravelDocs }}/queues#running-the-queue-worker" target="_blank" rel="noopener noreferrer">{{ __('laravelusers::ui.packages_worker_setup') }}</a> · <a href="{{ $laravelDocs }}/cache#atomic-locks" target="_blank" rel="noopener noreferrer">{{ __('laravelusers::ui.packages_cache_setup') }}</a></p>
    <p data-lu-package-status role="status" @unless($packageQueueReady) hidden @endunless><span data-lu-package-status-verified @unless($packageQueueReady) hidden @endunless>@include('laravelusers::partials.icon', ['name' => 'check'])</span><span data-lu-package-status-message>{{ $packageQueueReady ? __('laravelusers::ui.package_requirements_verified') : '' }}</span></p>
    <div class="lu-settings-grid">
        @foreach(['toast' => 'Laravel Toast', 'laravel-roles' => 'Laravel Roles', 'spatie' => 'Spatie Permissions'] as $package => $label)
        <div class="lu-settings-choice">
            <h3>{{ $label }}@if($package === 'laravel-roles') <small class="lu-package-preferred">({{ __('laravelusers::ui.preferred_roles') }})</small>@endif</h3><p class="lu-package-state"><span class="lu-package-badge {{ $managedPackages[$package] ? 'lu-package-badge-installed' : 'lu-package-badge-available' }}">{{ __($managedPackages[$package] ? 'laravelusers::ui.package_installed' : 'laravelusers::ui.package_not_installed') }}</span></p>
            @if($package === 'toast')<p class="lu-muted text-muted">{{ __('laravelusers::ui.packages_toast_hint') }} <a href="https://github.com/jeremykenedy/laravel-toast#requirements" target="_blank" rel="noopener noreferrer">{{ __('laravelusers::ui.packages_toast_requirements') }}</a></p>@endif
            @php($blocked = !$managedPackages[$package] && $package !== 'toast' && ($managedPackages['laravel-roles'] || $managedPackages['spatie']))
            @php($requirementsMissing = !$managedPackages[$package] && !$packageQueueReady)
            @php($toastUnsupported = !$managedPackages[$package] && $package === 'toast' && (PHP_VERSION_ID < 80200 || version_compare(\Illuminate\Foundation\Application::VERSION, '10.0.0', '<')))
            @if($blocked)<p class="lu-muted text-muted">{{ __('laravelusers::ui.packages_roles_conflict') }}</p>@endif
            @if($requirementsMissing)<p class="lu-muted text-muted" data-lu-package-requirements>{{ __('laravelusers::ui.packages_queue_required') }}</p>@endif
            @if($toastUnsupported)<p class="lu-muted text-muted" data-lu-package-requirements>{{ __('laravelusers::ui.packages_toast_unsupported') }}</p>@endif
            <button type="button" class="{{ $modern ? 'lu-button lu-secondary' : 'btn btn-outline-secondary btn-sm' }}" data-lu-package="{{ $package }}" data-lu-package-name="{{ $label }}" data-lu-package-operation="{{ $managedPackages[$package] ? 'remove' : 'install' }}" @if($blocked) data-lu-package-blocked @endif @if($blocked || $requirementsMissing || $toastUnsupported || !$packageQueueReady) disabled @endif>@include('laravelusers::partials.icon', ['name' => $managedPackages[$package] ? 'delete' : 'install']) {{ __($managedPackages[$package] ? 'laravelusers::ui.package_remove' : 'laravelusers::ui.package_install') }}</button>
            @if($managedPackages[$package])
                <details class="lu-package-instructions"><summary>{{ __('laravelusers::ui.package_setup') }}</summary>
                    @if($package === 'toast')<p>{{ __('laravelusers::ui.package_toast_setup') }}</p><code>php artisan toast:install --css={{ \jeremykenedy\laravelusers\Support\Frontend::framework() }} --frontend=blade</code>
                    @else<p>{{ __('laravelusers::ui.package_roles_setup') }}</p><code>php artisan laravelusers:update --roles={{ $package }}</code>@endif
                </details>
            @endif
        </div>
        @endforeach
        @if($accessAvailable ?? false)
        <div class="lu-settings-choice" data-lu-impersonation-package>
            <h3>@include('laravelusers::partials.icon', ['name' => 'secret-agent']) {{ __('laravelusers::ui.impersonation') }}</h3>
            <p class="lu-package-state"><span class="lu-package-badge {{ $impersonationEnabled ? 'lu-package-badge-installed' : 'lu-package-badge-available' }}">{{ __($impersonationEnabled ? 'laravelusers::ui.package_enabled' : 'laravelusers::ui.package_disabled') }}</span></p>
            <p class="lu-muted text-muted">{{ __('laravelusers::ui.impersonation_description') }}</p>
            <form method="POST" action="{{ route('users.settings.impersonation') }}">@csrf
                @unless($settingsAvailable)<p class="lu-muted text-muted">{{ __('laravelusers::ui.settings_migration_required') }}</p>@endunless
                <button type="submit" name="enabled" value="{{ $impersonationEnabled ? '0' : '1' }}" class="{{ $modern ? 'lu-button lu-secondary' : 'btn btn-outline-secondary btn-sm' }}" @unless($settingsAvailable) disabled @endunless>
                    @include('laravelusers::partials.icon', ['name' => $impersonationEnabled ? 'toggle-off' : 'toggle-on']) {{ __($impersonationEnabled ? 'laravelusers::ui.impersonation_disable' : 'laravelusers::ui.impersonation_enable') }}
                </button>
            </form>
        </div>
        @endif
    </div>
</section>
<dialog id="lu-package-dialog" class="lu-email-dialog" aria-labelledby="lu-package-title">
    <form id="lu-package-form" method="POST" action="{{ route('users.settings.packages') }}">
        @csrf
        <input type="hidden" name="package"><input type="hidden" name="operation">
        <header class="lu-email-heading"><h2 id="lu-package-title"><span data-lu-package-remove-icon hidden>@include('laravelusers::partials.icon', ['name' => 'delete'])</span><span data-lu-package-install-icon>@include('laravelusers::partials.icon', ['name' => 'install'])</span><span data-lu-package-title></span></h2><button type="button" class="lu-email-close" data-lu-package-dismiss aria-label="{{ __('laravelusers::ui.close') }}">@include('laravelusers::partials.icon', ['name' => 'close'])</button></header>
        <div class="lu-email-body">
            <p data-lu-package-warning></p>
            <fieldset data-lu-package-setup class="lu-email-section"><legend>{{ __('laravelusers::ui.package_setup') }}</legend><label class="lu-email-check"><input type="checkbox" name="setup" value="1" checked> {{ __('laravelusers::ui.package_publish_missing') }}</label><label class="lu-email-check"><input type="checkbox" name="migrate" value="1"> {{ __('laravelusers::ui.package_run_migrations') }}</label></fieldset>
            <p data-lu-package-role-warning hidden class="lu-package-warning">{{ __('laravelusers::ui.packages_remove_roles_warning') }}</p>
            <label class="lu-email-check"><input type="checkbox" name="acknowledgement" value="1"> {{ __('laravelusers::ui.packages_acknowledgement') }}</label>
            <label for="lu-package-confirmation">{{ __('laravelusers::ui.package_confirmation') }} <strong data-lu-package-word></strong></label>
            <input class="lu-email-input" id="lu-package-confirmation" name="confirmation" autocomplete="off" spellcheck="false" required>
            <p data-lu-package-error role="alert" hidden></p>
        </div>
        <footer class="lu-email-footer"><button type="button" class="lu-button lu-secondary" data-lu-package-dismiss title="{{ __('laravelusers::forms.cancel') }}" aria-label="{{ __('laravelusers::forms.cancel') }}">@include('laravelusers::partials.icon', ['name' => 'close']) <span>{{ __('laravelusers::forms.cancel') }}</span></button><button type="submit" class="lu-button" title="{{ __('laravelusers::ui.package_confirm') }}" aria-label="{{ __('laravelusers::ui.package_confirm') }}" disabled>@include('laravelusers::partials.icon', ['name' => 'check']) <span>{{ __('laravelusers::ui.package_confirm') }}</span></button></footer>
    </form>
</dialog>
@endif
