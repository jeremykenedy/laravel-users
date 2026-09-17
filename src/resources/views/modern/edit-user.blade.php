@extends('laravelusers::modern.page')
@php($tailwind = \jeremykenedy\laravelusers\Support\Frontend::framework() === 'tailwind')
@section('template_title', __('laravelusers::laravelusers.editing-user', ['name' => $user->name]))
@section('users_content')
    <header class="lu-heading"><h1>{{ __('laravelusers::laravelusers.editing-user', ['name' => $user->name]) }}</h1><a href="{{ route('users') }}">{{ __('laravelusers::ui.back') }}</a></header>
    @include('laravelusers::modern.form')
@endsection
