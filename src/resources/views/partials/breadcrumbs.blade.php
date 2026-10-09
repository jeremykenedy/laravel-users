@php
    $crumbs = [['label' => __('laravelusers::ui.home'), 'url' => url('/')]];
    $routeName = request()->route()?->getName();

    if (request()->routeIs('users.account*')) {
        $crumbs[] = ['label' => __('laravelusers::ui.account_title')];
    } else {
        $crumbs[] = ['label' => __('laravelusers::ui.breadcrumb_users'), 'url' => route('users')];

        if (request()->routeIs('users.create')) {
            $crumbs[] = ['label' => __('laravelusers::ui.breadcrumb_create_user')];
        } elseif (request()->routeIs('users.show')) {
            $crumbs[] = ['label' => isset($user) ? $user->name : __('laravelusers::ui.breadcrumb_show_user')];
        } elseif (request()->routeIs('users.edit')) {
            $crumbs[] = ['label' => isset($user) ? $user->name : __('laravelusers::ui.breadcrumb_edit_user'), 'url' => isset($user) ? route('users.show', $user->getKey()) : null];
            $crumbs[] = ['label' => __('laravelusers::ui.breadcrumb_edit_user')];
        } elseif (request()->routeIs('users.deleted.edit')) {
            $crumbs[] = ['label' => __('laravelusers::ui.breadcrumb_deleted_users'), 'url' => route('users.deleted')];
            $crumbs[] = ['label' => __('laravelusers::ui.breadcrumb_edit_deleted_user')];
        } elseif (request()->routeIs('users.deleted')) {
            $crumbs[] = ['label' => __('laravelusers::ui.breadcrumb_deleted_users')];
        } elseif (request()->routeIs('users.settings*')) {
            $crumbs[] = ['label' => __('laravelusers::ui.breadcrumb_settings')];
        } elseif ($routeName !== 'users') {
            $crumbs[] = ['label' => __('laravelusers::ui.breadcrumb_users')];
        }
    }
@endphp
<nav class="lu-breadcrumbs{{ request()->routeIs('users.show') ? ' lu-breadcrumbs-profile' : '' }}{{ request()->routeIs('users.create', 'users.edit', 'users.deleted.edit') ? ' lu-breadcrumbs-form' : '' }}" aria-label="{{ __('laravelusers::ui.breadcrumbs') }}">
    <ol>
        @foreach($crumbs as $index => $crumb)
            <li @if($index === count($crumbs) - 1) aria-current="page" @endif>
                @if($index < count($crumbs) - 1 && !empty($crumb['url']))
                    <a href="{{ $crumb['url'] }}">{{ $crumb['label'] }}</a>
                @else
                    <span>{{ $crumb['label'] }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
<style>
    #laravelusers .lu-breadcrumbs { margin: 0 0 24px; color: var(--lu-muted, #667085); font-size: .8rem; line-height: 1.4; }
    #laravelusers:not(.lu-shell) .lu-breadcrumbs-profile { max-width: 960px; margin-inline: auto; }
    @media (min-width: 992px) { #laravelusers:not(.lu-shell) .lu-breadcrumbs-form { width: calc(83.333333% - 5px); margin-inline: auto; } }
    #laravelusers .lu-breadcrumbs ol { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; margin: 0; padding: 0; list-style: none; }
    #laravelusers .lu-breadcrumbs li { display: inline-flex; align-items: center; gap: 8px; }
    #laravelusers .lu-breadcrumbs li + li::before { content: ""; width: 6px; height: 6px; border-top: 1.5px solid currentColor; border-right: 1.5px solid currentColor; transform: rotate(45deg); opacity: .65; }
    #laravelusers .lu-breadcrumbs a { color: var(--lu-muted, #667085); text-decoration: none; }
    #laravelusers .lu-breadcrumbs a:hover { color: var(--lu-accent, #2456c2); text-decoration: underline; }
    #laravelusers .lu-breadcrumbs [aria-current="page"] { color: #344054; font-weight: 600; }
    #laravelusers[data-lu-theme="dark"] .lu-breadcrumbs [aria-current="page"] { color: #e1e9f4; }
</style>
