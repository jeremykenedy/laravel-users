@extends('laravelusers::modern.page')
@php($tailwind = \jeremykenedy\laravelusers\Support\Frontend::framework() === 'tailwind')
@section('template_title', __('laravelusers::laravelusers.create-new-user'))
@section('users_content')
    <header class="lu-heading"><h1>{{ __('laravelusers::laravelusers.create-new-user') }}</h1><a href="{{ route('users') }}">{{ __('laravelusers::ui.back') }}</a></header>
    @include('laravelusers::modern.form')
@endsection
