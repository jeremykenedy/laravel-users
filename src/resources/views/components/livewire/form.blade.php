@props(['form', 'values' => [], 'activeTab' => 'profile', 'dialog' => false, 'ready' => true])
@php($sections = array_values(array_unique(array_column(array_filter($form['fields'], fn ($field) => $field['type'] !== 'hidden'), 'section'))))
<form {{ $attributes->merge(['class' => 'lu-form lu-pad']) }} method="POST" action="{{ $form['action'] }}" wire:submit="{{ $dialog ? 'confirmSubmit' : "prepareSubmit('".$form['id']."')" }}" data-lu-native-form="{{ $form['id'] }}" @if($dialog) data-lu-dialog-form @endif @if($form['preview'] ?? null) data-lu-preview-url="{{ $form['preview'] }}" @endif @if($form['async'] ?? false) data-lu-native-async @endif>
    @csrf
    @if($form['method'] !== 'POST')@method($form['method'])@endif
    @if(!$dialog)<h2>{{ $form['title'] }}</h2>@endif
    @if($form['help'] ?? null)<p class="lu-muted">{{ $form['help'] }}</p>@endif
    @if($form['confirm'] ?? null)<p>{{ $form['confirm'] }}</p>@endif
    @if(($form['tabs'] ?? false) && count($sections) > 1)
        <nav class="lu-settings-tabs" role="tablist" aria-label="{{ $form['title'] }}">
            @foreach($sections as $section)<button type="button" id="lu-tab-{{ $form['id'] }}-{{ $section }}" role="tab" aria-controls="lu-panel-{{ $form['id'] }}-{{ $section }}" aria-selected="{{ $activeTab === $section ? 'true' : 'false' }}" wire:click="setTab('{{ $form['id'] }}', '{{ $section }}')">{{ ucwords(str_replace('-', ' ', $section)) }}</button>@endforeach
        </nav>
    @endif
    <div data-lu-native-editor>
    <fieldset @if($form['disabled'] ?? false) disabled @endif>
        <legend class="lu-sr-only">{{ $form['title'] }}</legend>
        @foreach($form['fields'] as $field)
            @php($value = \Illuminate\Support\Arr::get($values, $field['key']))
            @if($field['type'] === 'hidden')
                @if($field['multiple'] ?? false)@foreach((array) $value as $item)<input type="hidden" name="{{ $field['name'] }}" value="{{ $item }}">@endforeach @else<input type="hidden" name="{{ $field['name'] }}" value="{{ $value }}">@endif
            @elseif(!isset($field['when']) || \Illuminate\Support\Arr::get($values, $field['when']['key']) == $field['when']['equals'])
                <div wire:key="field-{{ $form['id'] }}-{{ $field['key'] }}" @if(($form['tabs'] ?? false) && count($sections) > 1) @if($activeTab !== $field['section']) hidden @endif @endif>
                    @if($field['type'] === 'checkbox')
                        <input type="hidden" name="{{ $field['name'] }}" value="0">
                        <x-laravelusers::livewire.checkbox :name="$field['name']" :label="$field['label']" :model="'values.'.$form['id'].'.'.$field['key']" :disabled="$field['disabled']" :help="$field['help'] ?? null" :value="1" :live="true" :required="$field['required']" />
                    @else
                        @if($field['nullable'] ?? false)
                            <input type="hidden" name="{{ $field['name'] }}" value="">
                            <button type="button" class="lu-button lu-secondary" wire:click="toggleInheritance('{{ $form['id'] }}', '{{ $field['key'] }}')" aria-pressed="{{ $value === null ? 'true' : 'false' }}">{{ __('laravelusers::ui.appearance_inherit') }}</button>
                        @endif
                        <x-laravelusers::livewire.field :name="$field['name']" :label="$field['label']" :model="'values.'.$form['id'].'.'.$field['key']" :type="$field['type']" :options="$field['options'] ?? []" :multiple="$field['multiple'] ?? false" :required="$field['required']" :disabled="$field['disabled'] || (($field['nullable'] ?? false) && $value === null)" :help="$field['help'] ?? null" :min="$field['min'] ?? null" :max="$field['max'] ?? null" :maxlength="$field['maxlength'] ?? null" :live="isset($field['required_text']) || $field['type'] === 'select'" />
                    @endif
                </div>
            @endif
        @endforeach
    </fieldset>
    </div>
    @if($form['preview'] ?? null)
        <section data-lu-native-preview hidden><button type="button" class="lu-button lu-secondary" data-lu-preview-edit>{{ __('laravelusers::ui.email_back_editing') }}</button><p data-lu-preview-recipient class="lu-muted"></p><iframe title="{{ __('laravelusers::ui.email_preview_frame') }}" sandbox="" referrerpolicy="no-referrer" data-lu-preview-frame></iframe></section>
        <p data-lu-preview-error role="alert" hidden></p>
        <button type="button" class="lu-button lu-secondary" data-lu-native-preview>{{ __('laravelusers::ui.email_preview') }}</button>
    @endif
    <div class="lu-actions lu-form-actions">
        <button type="submit" class="lu-button {{ ($form['danger'] ?? false) ? 'lu-danger' : 'lu-success' }}" wire:loading.attr="disabled" @if(!$ready || ($form['disabled'] ?? false)) disabled @endif>{{ $form['submit'] }}</button>
        @if($dialog)<button type="button" class="lu-button lu-secondary" wire:click="closeDialog">{{ __('laravelusers::forms.cancel') }}</button>@endif
    </div>
</form>
