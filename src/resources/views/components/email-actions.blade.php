@props(['user' => null, 'compact' => false, 'deleted' => false])
@php($allowedEmails = array_filter($deleted ? ['message'] : ['reset', 'message', 'welcome'], fn ($action) => config('laravelusers.emails.'.$action, true) && ($action !== 'welcome' || config('laravelusers.welcome.enabled', false)) && \jeremykenedy\laravelusers\Support\UserAccess::email($action, $deleted)))
@php($emailButtons = $deleted ? ['message' => 'mail'] : ['reset' => 'lock', 'message' => 'mail', 'welcome' => 'add-user'])
@if(in_array('message', $allowedEmails, true) && \jeremykenedy\laravelusers\Support\GoodbyeEmail::allowed())
    @php($emailButtons['goodbye'] = 'mail')
    @php($allowedEmails[] = 'goodbye')
@endif
@if($deleted && in_array('message', $allowedEmails, true) && config('laravelusers.account_links.enabled', false))
    @foreach(['restore' => 'restore_users', 'force_delete' => 'force_delete'] as $preset => $permission)
        @if(config('laravelusers.account_links.'.$preset, true) && \jeremykenedy\laravelusers\Support\UserAccess::allows($permission))
            @php($emailButtons[$preset] = $preset === 'restore' ? 'restore' : 'delete')
            @php($allowedEmails[] = $preset)
        @endif
    @endforeach
@endif
@if($allowedEmails && config('laravelusers.emails.enabled', false) && Auth::check() && (!config('laravelusers.emails.gate') || Gate::allows(config('laravelusers.emails.gate'))) && (!$deleted || config('laravelusers.emails.deleted', true)))
    @if($compact)
        <details {{ $attributes->merge(['class' => 'lu-email-menu']) }}>
            <summary class="lu-email-toggle" aria-label="{{ __('laravelusers::ui.email_actions') }}" @if(config('laravelusers.tooltipsEnabled', true)) title="{{ __('laravelusers::ui.email_actions') }}" @endif>@include('laravelusers::partials.icon', ['name' => 'mail']) <span>{{ __('laravelusers::ui.email_actions') }}</span></summary>
            <div class="lu-email-options">
    @else
        <div {{ $attributes->merge(['class' => 'lu-email-controls']) }}>
    @endif
    @foreach($emailButtons as $action => $icon)
        @if(in_array($action, $allowedEmails, true))
            <button type="button" class="{{ $compact ? 'lu-email-choice' : 'lu-button lu-secondary' }}" data-lu-email-deleted="{{ $deleted ? '1' : '0' }}" data-lu-email-action="{{ $action }}" data-lu-email-user="{{ $user->id ?? '' }}" data-lu-email-name="{{ $user->name ?? '' }}" aria-label="{{ __('laravelusers::ui.email_'.$action) }}" @if(config('laravelusers.tooltipsEnabled', true)) title="{{ __('laravelusers::ui.email_'.$action) }}" @endif @if($action === 'reset' && !Route::has('password.reset')) disabled @endif>@include('laravelusers::partials.icon', ['name' => $icon]) <span>{{ __('laravelusers::ui.email_'.$action) }}</span></button>
        @endif
    @endforeach
    @if($compact)</div></details>@else</div>@endif
@endif
