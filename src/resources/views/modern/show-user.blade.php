@extends('laravelusers::modern.page')
@php($tailwind = \jeremykenedy\laravelusers\Support\Frontend::framework() === 'tailwind')
@section('template_title', __('laravelusers::laravelusers.showing-user', ['name' => $user->name]))
@section('users_content')
    <header class="lu-heading"><div><h1>{{ $user->name }}</h1><p class="lu-muted">{{ __('laravelusers::ui.profile') }}</p></div><a href="{{ route('users') }}">{{ __('laravelusers::ui.back') }}</a></header>
    <section class="lu-panel lu-pad">
        <div class="lu-actions"><a class="lu-button" href="{{ route('users.edit', $user->id) }}">{{ __('laravelusers::ui.edit') }}</a>@include('laravelusers::modern.delete')</div>
        <dl>
            @foreach(['id', 'name', 'email', 'created_at', 'updated_at'] as $field)
                <div class="lu-detail"><dt>{{ strip_tags(__('laravelusers::laravelusers.show-user.'.str_replace('_at', '', $field))) }}</dt><dd>{{ $user->$field }}</dd></div>
            @endforeach
            @if(config('laravelusers.rolesEnabled'))<div class="lu-detail"><dt>{{ __('laravelusers::laravelusers.users-table.role') }}</dt><dd>{{ $user->roles->pluck('name')->implode(', ') }}</dd></div>@endif
        </dl>
    </section>
@endsection
