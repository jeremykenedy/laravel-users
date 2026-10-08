@if(\jeremykenedy\laravelusers\Support\GoodbyeEmail::allowed())
<div data-lu-goodbye-options hidden>
    <label class="lu-email-check"><input type="checkbox" data-lu-send-goodbye @if(config('laravelusers.emails.goodbye_on_delete', false)) checked @endif> {{ __('laravelusers::ui.goodbye_send') }}</label>
    <details data-lu-goodbye-editor><summary>{{ __('laravelusers::ui.goodbye_customize') }}</summary>
        <fieldset data-lu-goodbye-fields disabled><legend class="lu-sr-only sr-only">{{ __('laravelusers::ui.goodbye_customize') }}</legend><x-laravelusers::email-fields prefix="lu-goodbye" name-prefix="goodbye" :contents="\jeremykenedy\laravelusers\Support\EmailContent::defaults('goodbye')" /></fieldset>
    </details>
</div>
@endif
