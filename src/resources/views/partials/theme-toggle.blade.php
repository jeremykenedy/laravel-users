<label for="lu-theme" class="lu-theme-label">{{ __('laravelusers::ui.theme') }}</label>
<select id="lu-theme" class="lu-input" style="width:auto" aria-label="{{ __('laravelusers::ui.theme') }}">
    @foreach(['light', 'dark', 'system'] as $theme)
        <option value="{{ $theme }}">{{ __('laravelusers::ui.'.$theme) }}</option>
    @endforeach
</select>
