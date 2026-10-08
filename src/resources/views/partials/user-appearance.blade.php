@if($appearanceEnabled ?? false)
    <fieldset class="lu-user-appearance" @if(!$appearanceAvailable) disabled @endif>
        <legend>{{ __('laravelusers::ui.appearance_heading') }}</legend>
        <div class="lu-appearance-modes"><section class="lu-appearance-mode"><h2>{{ __('laravelusers::ui.appearance_light') }}</h2>
        @include('laravelusers::partials.user-appearance-controls', ['mode' => '', 'colorLabel' => __('laravelusers::ui.settings_profile_color')])
        </section>
        @if($appearanceDarkAvailable)
            <section class="lu-appearance-mode"><h2>{{ __('laravelusers::ui.appearance_dark') }}</h2>
            @include('laravelusers::partials.user-appearance-controls', ['mode' => '_dark', 'colorLabel' => __('laravelusers::ui.settings_profile_dark_color')])
            </section>
        @else
            <p class="lu-muted text-muted">{{ __('laravelusers::ui.appearance_dark_migration_required') }}</p>
        @endif
        </div>
        <p class="lu-muted text-muted">{{ __($appearanceAvailable ? 'laravelusers::ui.appearance_hint' : 'laravelusers::ui.appearance_migration_required') }}</p>
    </fieldset>
@endif
