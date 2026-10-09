@extends('laravelusers::modern.page')
@php($tailwind = \jeremykenedy\laravelusers\Support\Frontend::framework() === 'tailwind')
@section('template_title', __('laravelusers::laravelusers.editing-user', ['name' => e($user->name)]))
@section('users_content')
    <section class="lu-profile lu-edit-card">
    <header class="lu-profile-header lu-page-heading"><h1 class="lu-list-title">@include('laravelusers::partials.icon', ['name' => 'user']) <span class="lu-title-text">{{ __('laravelusers::laravelusers.editing-user', ['name' => $user->name]) }}</span></h1><div class="lu-actions">@if(!($deletedUser ?? false) && \jeremykenedy\laravelusers\Support\UserAccess::allows('view_users'))<a class="lu-button lu-secondary" href="{{ route('users.show', $user->id) }}">@include('laravelusers::partials.icon', ['name' => 'show']) {{ __('laravelusers::ui.view_user') }}</a>@endif<a class="lu-button lu-secondary" href="{{ route(($deletedUser ?? false) ? 'users.deleted' : 'users') }}">@include('laravelusers::partials.icon', ['name' => 'reply']) {{ __('laravelusers::ui.back') }}</a></div></header>
    <div class="lu-profile-body">
        @include('laravelusers::partials.user-identity')
        @include('laravelusers::modern.form')
    </div>
    <x-laravelusers::email-actions :user="$user" :deleted="$deletedUser ?? false" />
    </section>
@endsection
