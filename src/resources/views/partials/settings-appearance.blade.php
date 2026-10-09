@php($controlId = str_replace('_', '-', $kind))
@php($darkHighlight = str_ends_with($kind, '_dark'))
@php($highlightKey = $keys['highlight'] ?? str_replace($darkHighlight ? 'DarkColor' : 'Color', $darkHighlight ? 'DarkGradientHighlightColor' : 'GradientHighlightColor', $keys['color']))
@php($highlightFallbackKey = $fallback['highlight'] ?? str_replace('Color', 'GradientHighlightColor', $fallback['color']))
@php($highlightColor = old($kind.'_gradient_highlight_color', config('laravelusers.'.$highlightKey)))
<div class="lu-settings-choice" data-lu-appearance-controls>
    <label class="lu-icon-label lu-control-title" for="settings-{{ $controlId }}-color"><span class="lu-title-icon lu-title-icon-palette">@include('laravelusers::partials.icon', ['name' => 'palette'])</span><span>{{ $colorLabel }}</span></label>
    <div class="lu-color-controls">
        <input id="settings-{{ $controlId }}-color" name="{{ $kind }}_color" type="color" value="{{ old($kind.'_color', \jeremykenedy\laravelusers\Support\Frontend::colors(config('laravelusers.'.$keys['color']) ?? config('laravelusers.'.$fallback['color']))['base']) }}" data-lu-color>
        @include('laravelusers::partials.appearance-reset', ['target' => 'settings-'.$controlId.'-color', 'value' => config('laravelusers.settings.defaults.'.$keys['color']) ?? config('laravelusers.settings.defaults.'.$fallback['color']), 'label' => $colorLabel])
    </div>
    <label class="lu-icon-label lu-control-title" for="settings-{{ $controlId }}-gradient-highlight-color"><span class="lu-title-icon lu-title-icon-palette">@include('laravelusers::partials.icon', ['name' => 'palette'])</span><span>{{ __('laravelusers::ui.gradient_highlight_color') }}</span></label>
    <div class="lu-color-controls">
        @if($darkHighlight)<input type="hidden" name="{{ $kind }}_gradient_highlight_color" value="">@endif
        <input id="settings-{{ $controlId }}-gradient-highlight-color" name="{{ $kind }}_gradient_highlight_color" type="color" value="{{ \jeremykenedy\laravelusers\Support\Frontend::colors($highlightColor ?? config('laravelusers.'.$highlightFallbackKey, '#ffffff'), '#ffffff')['base'] }}" data-lu-gradient-highlight-color @if($darkHighlight) data-lu-highlight-fallback="settings-{{ str_replace('-dark', '', $controlId) }}-gradient-highlight-color" @if($highlightColor === null) disabled @endif @endif>
        @include('laravelusers::partials.appearance-reset', ['target' => 'settings-'.$controlId.'-gradient-highlight-color', 'value' => config('laravelusers.settings.defaults.'.$highlightKey) ?? ($darkHighlight ? 'inherit' : '#ffffff'), 'label' => __('laravelusers::ui.gradient_highlight_color')])
    </div>
    @if($darkHighlight)<label class="lu-appearance-inherit"><input type="checkbox" data-lu-inherit-color="settings-{{ $controlId }}-gradient-highlight-color" @if($highlightColor === null) checked @endif> {{ __('laravelusers::ui.appearance_inherit_highlight_color') }}</label>@endif
    @error($kind.'_gradient_highlight_color')<p class="lu-field-error text-danger">{{ $message }}</p>@enderror
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
    @php($sample = $appearancePreviewAvatars[$kind] ?? ['name' => \jeremykenedy\laravelusers\Actions\AvatarPreview::SAMPLES[$kind]['name'], 'avatar' => ['src' => null, 'initials' => \jeremykenedy\laravelusers\Actions\AvatarPreview::SAMPLES[$kind]['initials'], 'size' => 40, 'fallback' => 'initials']])
    <div class="lu-settings-preview" data-lu-appearance-preview data-lu-avatar-preview="{{ $kind }}" role="img" aria-label="{{ __('laravelusers::ui.appearance_avatar_preview', ['name' => $sample['name']]) }}">@include('laravelusers::partials.avatar', ['avatar' => $sample['avatar']])</div>
</div>
