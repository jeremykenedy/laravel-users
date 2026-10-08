@php($controlId = str_replace('_', '-', $kind))
<div class="lu-settings-choice" data-lu-appearance-controls>
    <label class="lu-icon-label lu-control-title" for="settings-{{ $controlId }}-color"><span class="lu-title-icon lu-title-icon-palette">@include('laravelusers::partials.icon', ['name' => 'palette'])</span><span>{{ $colorLabel }}</span></label>
    <div class="lu-color-controls">
        <input id="settings-{{ $controlId }}-color" name="{{ $kind }}_color" type="color" value="{{ old($kind.'_color', \jeremykenedy\laravelusers\Support\Frontend::colors(config('laravelusers.'.$keys['color']) ?? config('laravelusers.'.$fallback['color']))['base']) }}" data-lu-color>
        @include('laravelusers::partials.appearance-reset', ['target' => 'settings-'.$controlId.'-color', 'value' => config('laravelusers.settings.defaults.'.$keys['color']) ?? config('laravelusers.settings.defaults.'.$fallback['color']), 'label' => $colorLabel])
    </div>
    <div class="lu-gradient-toggle">
        <label class="lu-settings-check"><input type="hidden" name="{{ $kind }}_gradient" value="0"><input id="settings-{{ $controlId }}-gradient" type="checkbox" name="{{ $kind }}_gradient" value="1" data-lu-gradient @if(old($kind.'_gradient', config('laravelusers.'.$keys['gradient']) ?? config('laravelusers.'.$fallback['gradient'], true))) checked @endif> {{ __('laravelusers::ui.appearance_gradient') }}</label>
        @include('laravelusers::partials.appearance-reset', ['target' => 'settings-'.$controlId.'-gradient', 'value' => (config('laravelusers.settings.defaults.'.$keys['gradient']) ?? config('laravelusers.settings.defaults.'.$fallback['gradient'], true)) ? '1' : '0', 'label' => __('laravelusers::ui.appearance_gradient')])
    </div>
    <label for="settings-{{ $controlId }}-strength">{{ __('laravelusers::ui.gradient_strength') }}</label>
    <div class="lu-range-controls">
        <input id="settings-{{ $controlId }}-strength" name="{{ $kind }}_gradient_strength" type="range" min="0" max="100" value="{{ old($kind.'_gradient_strength', config('laravelusers.'.$keys['strength']) ?? config('laravelusers.'.$fallback['strength'], 50)) }}" data-lu-gradient-strength>
        <output for="settings-{{ $controlId }}-strength">{{ old($kind.'_gradient_strength', config('laravelusers.'.$keys['strength']) ?? config('laravelusers.'.$fallback['strength'], 50)) }}%</output>
        @include('laravelusers::partials.appearance-reset', ['target' => 'settings-'.$controlId.'-strength', 'value' => config('laravelusers.settings.defaults.'.$keys['strength']) ?? config('laravelusers.settings.defaults.'.$fallback['strength'], 50), 'label' => __('laravelusers::ui.gradient_strength')])
    </div>
    <div class="lu-range-labels lu-muted text-muted"><span>{{ __('laravelusers::ui.gradient_less') }}</span><span>{{ __('laravelusers::ui.gradient_more') }}</span></div>
    <div class="lu-settings-preview" data-lu-appearance-preview aria-hidden="true"><span>@include('laravelusers::partials.icon', ['name' => 'user'])</span></div>
</div>
