@extends('laravelusers::modern.page')
@section('template_title', __('laravelusers::ui.account_email_confirm'))
@section('users_content')
<section class="lu-panel lu-pad lu-account-confirm"><h1>@include('laravelusers::partials.icon', ['name' => 'mail']) {{ __('laravelusers::ui.account_email_confirm') }}</h1>
@if(isset($change) && $change)<p>{{ __('laravelusers::ui.account_email_both') }}</p><form method="POST" action="{{ route('users.account.email.accept', $token) }}">@csrf<button type="submit" class="lu-button">@include('laravelusers::partials.icon', ['name' => 'check']) {{ __('laravelusers::ui.account_email_confirm') }}</button></form>
@else<p role="status">{{ __('laravelusers::ui.'.(($completed ?? null) === 'complete' ? 'account_email_complete' : (($completed ?? null) === 'pending' ? 'account_email_waiting' : 'account_email_invalid'))) }}</p>@endif
<a class="lu-button lu-secondary" href="{{ route('users.account') }}">@include('laravelusers::partials.icon', ['name' => 'reply']) {{ __('laravelusers::ui.account_title') }}</a></section>
@endsection
