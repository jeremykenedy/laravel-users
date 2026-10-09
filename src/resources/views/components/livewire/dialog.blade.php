@props(['id', 'title', 'dismiss' => 'closeDialog', 'confirm' => null, 'confirmLabel' => null, 'confirmDisabled' => false, 'danger' => false, 'footer' => true])
<div class="lu-native-dialog-backdrop">
    <section {{ $attributes->merge(['class' => 'lu-email-dialog lu-native-dialog']) }} role="dialog" aria-modal="true" aria-labelledby="{{ $id }}-title" tabindex="-1" data-lu-native-dialog>
        <header class="lu-email-heading">
            <h2 id="{{ $id }}-title">{{ $title }}</h2>
            <button type="button" class="lu-email-close" wire:click="{{ $dismiss }}" aria-label="{{ __('laravelusers::ui.close') }}">{{ __('laravelusers::ui.close') }}</button>
        </header>
        <div class="lu-email-body">{{ $slot }}</div>
        @if($footer)<footer class="lu-email-footer">
            <button type="button" class="lu-button lu-secondary" wire:click="{{ $dismiss }}">{{ __('laravelusers::forms.cancel') }}</button>
            @if($confirm)<button type="button" class="lu-button {{ $danger ? 'lu-danger' : 'lu-success' }}" wire:click="{{ $confirm }}" wire:loading.attr="disabled" @if($confirmDisabled) disabled @endif>{{ $confirmLabel ?? __('laravelusers::ui.confirm') }}</button>@endif
        </footer>@endif
    </section>
</div>
