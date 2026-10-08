@extends('laravelusers::modern.page')
@php($tailwind = \jeremykenedy\laravelusers\Support\Frontend::framework() === 'tailwind')
@section('template_title', __('laravelusers::laravelusers.editing-user', ['name' => $user->name]))
@section('users_content')
    <section class="lu-panel lu-form-card">
    <header class="lu-heading lu-card-heading"><h1>{{ __('laravelusers::laravelusers.editing-user', ['name' => $user->name]) }}</h1><a class="lu-button lu-secondary" href="{{ route('users') }}">@include('laravelusers::partials.icon', ['name' => 'back']) {{ __('laravelusers::ui.back') }}</a></header>
    @include('laravelusers::modern.form')
    </section>
@endsection
