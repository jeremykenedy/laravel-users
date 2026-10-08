@if(config('laravelusers.confirmDelete', true) || config('laravelusers.confirmSave', true))
    <dialog id="lu-confirmation" class="lu-modal" aria-labelledby="lu-confirm-title" aria-describedby="lu-confirm-message">
        <div class="lu-card-heading lu-heading">
            <h2 id="lu-confirm-title">{{ __('laravelusers::modals.confirm_modal_title_text') }}</h2>
            <button class="lu-button lu-secondary" type="button" data-lu-dismiss aria-label="{{ __('laravelusers::forms.cancel') }}">@include('laravelusers::partials.icon', ['name' => 'close'])</button>
        </div>
        <div class="lu-pad"><p id="lu-confirm-message"></p></div>
        <div class="lu-actions lu-modal-footer">
            <button class="lu-button lu-secondary" type="button" data-lu-dismiss>@include('laravelusers::partials.icon', ['name' => 'close']) {{ __('laravelusers::forms.cancel') }}</button>
            <button class="lu-button" type="button" id="lu-confirm-submit"><span data-lu-delete-icon hidden>@include('laravelusers::partials.icon', ['name' => 'delete'])</span><span data-lu-save-icon>@include('laravelusers::partials.icon', ['name' => 'save'])</span> {{ __('laravelusers::ui.confirm') }}</button>
        </div>
    </dialog>
@endif
