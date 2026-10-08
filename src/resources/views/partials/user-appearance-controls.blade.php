@php($modeId = str_replace('_', '-', $mode))
@php($globalColors = \jeremykenedy\laravelusers\Support\Frontend::profileColors('profileCardColor', '#2458b7', $mode !== ''))
@if($mode !== '')
    @php($globalColors['base'] = config('laravelusers.profileCardDarkColor') === null ? ($appearancePreference['color'] ?? $globalColors['base']) : $globalColors['base'])
    @php($globalColors['strength'] = config('laravelusers.profileCardDarkGradientStrength') === null ? ($appearancePreference['strength'] ?? $globalColors['strength']) : $globalColors['strength'])
@endif
@php($cardColor = old('user_card'.$mode.'_color', $appearancePreference[$mode === '' ? 'color' : 'dark_color'] ?? null))
<div class="{{ $modern ? 'lu-field' : 'form-group row' }}">
    <label for="user-card{{ $modeId }}-color" class="lu-icon-label lu-control-title {{ $modern ? '' : 'col-md-3 control-label' }}"><span class="lu-title-icon lu-title-icon-palette">@include('laravelusers::partials.icon', ['name' => 'palette'])</span><span>{{ $colorLabel }}</span></label>
    <div class="{{ $modern ? 'lu-control' : 'col-md-9' }}">
        <input type="hidden" name="user_card{{ $mode }}_color" value="">
        <input id="user-card{{ $modeId }}-color" name="user_card{{ $mode }}_color" type="color" value="{{ \jeremykenedy\laravelusers\Support\Frontend::colors($cardColor ?? $globalColors['base'])['base'] }}" @if(!$cardColor) disabled @endif>
        @include('laravelusers::partials.appearance-reset', ['target' => 'user-card'.$modeId.'-color', 'value' => 'inherit', 'label' => $colorLabel])
        <label class="lu-appearance-inherit"><input type="checkbox" data-lu-inherit-color="user-card{{ $modeId }}-color" @if(!$cardColor) checked @endif> {{ __('laravelusers::ui.appearance_inherit_color') }}</label>
        @error('user_card'.$mode.'_color')<p class="lu-field-error text-danger">{{ $message }}</p>@enderror
    </div>
</div>
<div class="{{ $modern ? 'lu-field' : 'form-group row' }}">
    <label for="user-card{{ $modeId }}-gradient" class="{{ $modern ? '' : 'col-md-3 control-label' }}">{{ __('laravelusers::ui.appearance_gradient') }}</label>
    <div class="{{ $modern ? 'lu-control' : 'col-md-9' }}">
        <select id="user-card{{ $modeId }}-gradient" name="user_card{{ $mode }}_gradient" class="{{ $modern ? 'lu-input' : 'form-control custom-select' }}">
            @php($gradient = old('user_card'.$mode.'_gradient', isset($appearancePreference[$mode === '' ? 'gradient' : 'dark_gradient']) ? ($appearancePreference[$mode === '' ? 'gradient' : 'dark_gradient'] ? 'on' : 'off') : 'inherit'))
            @foreach(['inherit', 'on', 'off'] as $value)<option value="{{ $value }}" @if($gradient === $value) selected @endif>{{ __('laravelusers::ui.appearance_'.$value) }}</option>@endforeach
        </select>
        @include('laravelusers::partials.appearance-reset', ['target' => 'user-card'.$modeId.'-gradient', 'value' => 'inherit', 'label' => __('laravelusers::ui.appearance_gradient')])
        @error('user_card'.$mode.'_gradient')<p class="lu-field-error text-danger">{{ $message }}</p>@enderror
    </div>
</div>
@if($mode !== '' || $appearanceStrengthAvailable)
<div class="{{ $modern ? 'lu-field' : 'form-group row' }}">
    <label for="user-card{{ $modeId }}-strength" class="{{ $modern ? '' : 'col-md-3 control-label' }}">{{ __('laravelusers::ui.gradient_strength') }}</label>
    <div class="{{ $modern ? 'lu-control' : 'col-md-9' }}">
        @php($strength = old('user_card'.$mode.'_gradient_strength', $appearancePreference[$mode === '' ? 'strength' : 'dark_strength'] ?? null))
        <input type="hidden" name="user_card{{ $mode }}_gradient_strength" value="">
        <div class="lu-range-controls">
            <input id="user-card{{ $modeId }}-strength" name="user_card{{ $mode }}_gradient_strength" type="range" min="0" max="100" value="{{ $strength ?? $globalColors['strength'] }}" data-lu-gradient-strength @if($strength === null) disabled @endif>
            <output for="user-card{{ $modeId }}-strength">{{ $strength ?? $globalColors['strength'] }}%</output>
            @include('laravelusers::partials.appearance-reset', ['target' => 'user-card'.$modeId.'-strength', 'value' => 'inherit', 'label' => __('laravelusers::ui.gradient_strength')])
        </div>
        <div class="lu-range-labels lu-muted text-muted"><span>{{ __('laravelusers::ui.gradient_less') }}</span><span>{{ __('laravelusers::ui.gradient_more') }}</span></div>
        <label class="lu-appearance-inherit"><input type="checkbox" data-lu-inherit-strength="user-card{{ $modeId }}-strength" @if($strength === null) checked @endif> {{ __('laravelusers::ui.appearance_inherit_strength') }}</label>
        @error('user_card'.$mode.'_gradient_strength')<p class="lu-field-error text-danger">{{ $message }}</p>@enderror
    </div>
</div>
@endif
