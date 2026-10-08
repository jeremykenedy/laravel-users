@extends('laravelusers::modern.page')
@section('template_title', __('laravelusers::ui.settings'))
@section('users_content')
    <section class="lu-panel lu-settings-panel">
        <header class="lu-heading lu-card-heading">
            <h1 class="lu-list-title">@include('laravelusers::partials.icon', ['name' => 'settings']) {{ __('laravelusers::ui.settings') }}</h1>
            <a class="lu-button lu-secondary" href="{{ route('users') }}" title="{{ __('laravelusers::ui.back') }}" aria-label="{{ __('laravelusers::ui.back') }}">@include('laravelusers::partials.icon', ['name' => 'reply']) {{ __('laravelusers::ui.back') }}</a>
        </header>
        @include('laravelusers::partials.settings-tabs')@include('laravelusers::partials.settings-form', ['modern' => true])
        <div data-lu-settings-panel="packages" id="lu-settings-packages" role="tabpanel" aria-labelledby="lu-tab-packages">@include('laravelusers::partials.package-settings', ['modern' => true])</div>
        <div data-lu-settings-panel="cleanup" id="lu-settings-cleanup" role="tabpanel" aria-labelledby="lu-tab-cleanup">@include('laravelusers::partials.cleanup-settings', ['modern' => true])</div>
        <div data-lu-settings-panel="emails" id="lu-settings-emails" role="tabpanel" aria-labelledby="lu-tab-emails">@include('laravelusers::partials.email-template-settings', ['modern' => true])</div>
<div data-lu-settings-panel="accounts" id="lu-settings-accounts" role="tabpanel" aria-labelledby="lu-tab-accounts">@include('laravelusers::partials.account-settings', ['modern' => true])</div>    </section>
@endsection
