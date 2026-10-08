@props(['prefix' => 'reset', 'minutes' => 60])
<div data-lu-{{ $prefix }}-duration data-lu-default-minutes="{{ $minutes }}" hidden>
    <label for="lu-{{ $prefix }}-duration">{{ __('laravelusers::ui.reset_duration') }}</label>
    @if(config($prefix === 'reset' ? 'laravelusers.emails.reset_allow_never_expire' : 'laravelusers.account_links.allow_never_expire', true))
    <label class="lu-email-check"><input type="hidden" name="{{ $prefix }}_never_expire" value="0" disabled><input type="checkbox" name="{{ $prefix }}_never_expire" value="1" @if(old($prefix.'_never_expire', false)) checked @endif disabled> {{ __('laravelusers::ui.never_expire') }}</label>
    @endif
    <div class="lu-reset-duration-row" data-lu-expiry-values>
        <input class="lu-email-input" type="number" id="lu-{{ $prefix }}-duration" name="{{ $prefix }}_duration" min="1" step="1" value="{{ old($prefix.'_duration', $minutes) }}" required disabled>
        <label class="lu-sr-only sr-only" for="lu-{{ $prefix }}-unit">{{ __('laravelusers::ui.reset_unit') }}</label>
        <select class="lu-email-input" id="lu-{{ $prefix }}-unit" name="{{ $prefix }}_unit" disabled>@foreach(['minutes', 'hours', 'days'] as $unit)<option value="{{ $unit }}" @if(old($prefix.'_unit', 'minutes') === $unit) selected @endif>{{ __('laravelusers::ui.reset_'.$unit) }}</option>@endforeach</select>
    </div>
</div>
