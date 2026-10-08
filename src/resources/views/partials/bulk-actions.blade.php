@if(config('laravelusers.bulkActions', false))
    <label class="lu-mobile-only"><input type="checkbox" data-lu-select-all-mobile> {{ __('laravelusers::ui.select_all') }}</label>
    <form id="lu-bulk" class="{{ $modern ? 'lu-actions lu-pad' : 'd-flex align-items-center p-3' }}" method="POST" action="{{ route('users.bulk') }}" @if($modern && config('laravelusers.confirmDelete', true)) data-lu-confirm="{{ __('laravelusers::ui.confirm_bulk') }}" data-lu-confirm-title="{{ __('laravelusers::ui.bulk_actions') }}" @endif hidden>
        @csrf
        <span data-lu-selected-count aria-live="polite">{{ __('laravelusers::ui.selected', ['count' => 0]) }}</span>
        <label for="lu-bulk-action" class="{{ $modern ? 'lu-sr-only' : 'sr-only' }}">{{ __('laravelusers::ui.bulk_actions') }}</label>
        <select id="lu-bulk-action" name="action" class="{{ $modern ? 'lu-input' : 'custom-select' }}">
            @if($deleted ?? false)<option value="restore">{{ __('laravelusers::ui.restore') }}</option><option value="force_delete">{{ __('laravelusers::ui.permanently_delete') }}</option>@else<option value="delete">{{ __('laravelusers::ui.delete') }}</option>@endif
        </select>
        <span data-lu-selected-inputs></span>
        <button id="lu-bulk-submit" class="{{ $modern ? 'lu-button lu-danger' : 'btn btn-danger' }}" type="{{ !$modern && config('laravelusers.confirmDelete', true) ? 'button' : 'submit' }}" @if(!$modern && config('laravelusers.confirmDelete', true)) data-toggle="modal" data-target="#confirmDelete" data-title="{{ __('laravelusers::ui.bulk_actions') }}" data-message="{{ __('laravelusers::ui.confirm_bulk') }}" @endif disabled>@include('laravelusers::partials.icon', ['name' => 'save']) {{ __('laravelusers::ui.apply') }}</button>
    </form>
@endif
