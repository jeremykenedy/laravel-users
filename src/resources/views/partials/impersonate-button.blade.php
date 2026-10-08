@if(($canImpersonateUsers ?? false) && (string) Auth::id() !== (string) $target->getKey())
    <form method="POST" action="{{ route('users.impersonate', $target->getKey()) }}" class="lu-impersonate-form">
        @csrf
        <button type="submit" class="{{ $modern ? 'lu-button lu-secondary' : 'btn btn-outline-warning btn-sm' }}" title="{{ __('laravelusers::ui.impersonation_target') }}" aria-label="{{ __('laravelusers::ui.impersonation_target') }}">
            @include('laravelusers::partials.icon', ['name' => 'secret-agent']) <span>{{ __('laravelusers::ui.impersonation_target') }}</span>
        </button>
    </form>
@endif
