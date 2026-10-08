@if(config('laravelusers.bulkActions', false) && \jeremykenedy\laravelusers\Support\UserAccess::selectable($deleted ?? false))
    <div class="lu-selection-controls">
        <button type="button" class="lu-button lu-secondary" data-lu-select-visible aria-label="{{ __('laravelusers::ui.select_all_label') }}" @if(config('laravelusers.tooltipsEnabled', true)) title="{{ __('laravelusers::ui.select_all') }}" @endif>@include('laravelusers::partials.icon', ['name' => 'select-all']) {{ __('laravelusers::ui.select_all_label') }}</button>
        <button type="button" class="lu-button lu-secondary" data-lu-deselect-visible aria-label="{{ __('laravelusers::ui.deselect_all') }}" @if(config('laravelusers.tooltipsEnabled', true)) title="{{ __('laravelusers::ui.deselect_all') }}" @endif disabled>@include('laravelusers::partials.icon', ['name' => 'deselect-all']) {{ __('laravelusers::ui.deselect_all') }}</button>
    </div>
    <form id="lu-bulk" class="{{ $modern ? 'lu-actions lu-pad' : 'd-flex align-items-center p-3' }}" method="POST" action="{{ route('users.bulk') }}" @if($modern && config('laravelusers.confirmDelete', true)) data-lu-confirm="{{ __('laravelusers::ui.confirm_bulk') }}" data-lu-confirm-title="{{ __('laravelusers::ui.bulk_actions') }}" @endif hidden>
        @csrf
        <span data-lu-selected-count aria-live="polite">{{ __('laravelusers::ui.selected', ['count' => 0]) }}</span>
        <label for="lu-bulk-action" class="{{ $modern ? 'lu-sr-only' : 'sr-only' }}">{{ __('laravelusers::ui.bulk_actions') }}</label>
        <select id="lu-bulk-action" name="action" class="{{ $modern ? 'lu-input' : 'custom-select' }}">
            @if($deleted ?? false)
                @if(\jeremykenedy\laravelusers\Support\UserAccess::allows('restore_users'))
                    <option value="restore">{{ __('laravelusers::ui.restore') }}</option>
                @endif
                @if(\jeremykenedy\laravelusers\Support\UserAccess::allows('force_delete'))
                    <option value="force_delete">{{ __('laravelusers::ui.permanently_delete') }}</option>
                @endif
            @else
                @if(\jeremykenedy\laravelusers\Support\UserAccess::allows('delete_users'))
                    <option value="delete">{{ __('laravelusers::ui.delete') }}</option>
                @endif
            @endif
            @if(($deleted ?? false) && config('laravelusers.emails.enabled', false) && config('laravelusers.emails.bulk', true) && config('laravelusers.emails.deleted', true) && config('laravelusers.emails.message', true) && \jeremykenedy\laravelusers\Support\UserAccess::email('message', true))<option value="message">{{ __('laravelusers::ui.email_message') }}</option>@endif
            @if(($deleted ?? false) && config('laravelusers.emails.enabled', false) && config('laravelusers.emails.bulk', true) && config('laravelusers.emails.deleted', true) && config('laravelusers.emails.message', true) && config('laravelusers.account_links.enabled', false) && \jeremykenedy\laravelusers\Support\UserAccess::email('message', true))
                @foreach(['restore' => 'restore_users', 'force_delete' => 'force_delete'] as $preset => $permission)
                    @if(config('laravelusers.account_links.'.$preset, true) && \jeremykenedy\laravelusers\Support\UserAccess::allows($permission))<option value="{{ $preset }}_email">{{ __('laravelusers::ui.email_'.$preset) }}</option>@endif
                @endforeach
            @endif
            @if(!($deleted ?? false) && config('laravelusers.emails.enabled', false) && config('laravelusers.emails.bulk', true))@foreach(['message', 'reset', 'welcome'] as $action)@if(config('laravelusers.emails.'.$action, true) && ($action !== 'welcome' || config('laravelusers.welcome.enabled', false)) && \jeremykenedy\laravelusers\Support\UserAccess::email($action))<option value="{{ $action }}">{{ __('laravelusers::ui.email_'.$action) }}</option>@endif @endforeach @endif
        </select>
        <span data-lu-selected-inputs></span>
        <button id="lu-bulk-submit" class="{{ $modern ? 'lu-button lu-danger' : 'btn btn-danger' }}" type="{{ !$modern && config('laravelusers.confirmDelete', true) ? 'button' : 'submit' }}" @if(!$modern && config('laravelusers.confirmDelete', true)) data-toggle="modal" data-target="#confirmDelete" data-title="{{ __('laravelusers::ui.bulk_actions') }}" data-message="{{ __('laravelusers::ui.confirm_bulk') }}" @endif disabled>@include('laravelusers::partials.icon', ['name' => 'save']) {{ __('laravelusers::ui.apply') }}</button>
    </form>
@endif
