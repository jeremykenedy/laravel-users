@props(['prefix' => 'lu-email', 'namePrefix' => '', 'contents' => []])
@php($fieldName = fn ($name) => $namePrefix ? $namePrefix.'['.$name.']' : $name)
<label for="{{ $prefix }}-subject">{{ __('laravelusers::ui.email_subject') }}</label>
<input class="lu-email-input" id="{{ $prefix }}-subject" name="{{ $fieldName('subject') }}" value="{{ $contents['subject'] ?? old('subject') }}" maxlength="150" required>
<div class="lu-email-section">
    <label class="lu-email-check"><input type="hidden" name="{{ $fieldName('use_greeting') }}" value="0"><input type="checkbox" name="{{ $fieldName('use_greeting') }}" value="1" @if(old('use_greeting', config('laravelusers.emails.use_greeting', true))) checked @endif> {{ __('laravelusers::ui.email_greeting') }}</label>
    <label class="lu-sr-only sr-only" for="{{ $prefix }}-greeting">{{ __('laravelusers::ui.email_greeting_text') }}</label>
    <input class="lu-email-input" id="{{ $prefix }}-greeting" name="{{ $fieldName('greeting') }}" value="{{ old('greeting', config('laravelusers.emails.greeting', 'Hi')) }}" maxlength="120">
    <label class="lu-email-check"><input type="hidden" name="{{ $fieldName('include_name') }}" value="0"><input type="checkbox" name="{{ $fieldName('include_name') }}" value="1" @if(old('include_name', config('laravelusers.emails.include_name', true))) checked @endif> {{ __('laravelusers::ui.email_include_name') }}</label>
</div>
<label for="{{ $prefix }}-message">{{ __('laravelusers::ui.email_body') }}</label>
<textarea class="lu-email-input" id="{{ $prefix }}-message" name="{{ $fieldName('message') }}" rows="5" maxlength="{{ max(1, (int) config('laravelusers.emails.max_length', 10000)) }}" required>{{ $contents['message'] ?? old('message') }}</textarea>
<div class="lu-email-section">
    <label class="lu-email-check"><input type="hidden" name="{{ $fieldName('use_signoff') }}" value="0"><input type="checkbox" name="{{ $fieldName('use_signoff') }}" value="1" @if(old('use_signoff', config('laravelusers.emails.use_signoff', true))) checked @endif> {{ __('laravelusers::ui.email_signoff') }}</label>
    <label class="lu-sr-only sr-only" for="{{ $prefix }}-signoff">{{ __('laravelusers::ui.email_signoff_text') }}</label>
    <input class="lu-email-input" id="{{ $prefix }}-signoff" name="{{ $fieldName('signoff') }}" value="{{ old('signoff', config('laravelusers.emails.signoff', 'Thanks')) }}" maxlength="120">
    <label for="{{ $prefix }}-signoff-name">{{ __('laravelusers::ui.email_signoff_name') }}</label>
    <input class="lu-email-input" id="{{ $prefix }}-signoff-name" name="{{ $fieldName('signoff_name') }}" value="{{ old('signoff_name', config('laravelusers.emails.signoff_name', '')) }}" maxlength="120">
</div>
