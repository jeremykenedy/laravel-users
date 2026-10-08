@extends('laravelusers::modern.page')
@section('template_title', __('laravelusers::laravelusers.showing-user', ['name' => $user->name]))
@section('users_content')
    @include('laravelusers::partials.profile-card', ['modern' => true])
@endsection
