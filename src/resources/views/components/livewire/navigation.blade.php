@props(['page'])
@if($page['features']['show_header'] && !$page['features']['custom_header'])
    <nav class="lu-toolbar" aria-label="{{ $page['labels']['navigation'] }}">
        <a class="lu-brand" href="{{ $page['data']['home'] }}">{{ $page['labels']['package_name'] }}</a>
        <div class="lu-actions">
            @if($page['data']['current_user'] ?? null)
                @php($currentUser = $page['data']['current_user'])
                <details class="lu-user-menu"><summary class="lu-user-menu-toggle">@if($currentUser['avatar'])<x-laravelusers::livewire.avatar :avatar="$currentUser['avatar']" />@endif<span>{{ $currentUser['name'] }}</span><span class="lu-user-menu-caret" aria-hidden="true"></span></summary><div class="lu-user-menu-items">
                    @if($currentUser['activity'])<div class="lu-user-menu-login" aria-label="{{ $page['labels']['login_details'] }}"><x-laravelusers::livewire.activity :activity="$currentUser['activity']" :labels="$page['labels']" /></div>@endif
                    @if($page['urls']['users'] ?? null)<a href="{{ $page['urls']['users'] }}" wire:navigate>{{ $page['labels']['manage_users'] }}</a>@endif
                    @if($page['urls']['account'] ?? null)<a href="{{ $page['urls']['account'] }}" wire:navigate>{{ $page['labels']['account_menu_label'] }}</a>@endif
                    @if($page['urls']['logout'] ?? null)<form method="POST" action="{{ $page['urls']['logout'] }}">@csrf<button type="submit">{{ $page['labels']['logout'] }}</button></form>@endif
                </div></details>
            @endif
            @if($page['features']['theme_toggle'])<button type="button" class="lu-button lu-secondary" wire:click="toggleTheme">{{ $page['theme'] === 'dark' ? $page['labels']['theme_light'] : $page['labels']['theme_dark'] }}</button>@endif
        </div>
    </nav>
@endif
@if($page['features']['breadcrumbs'])
    <nav class="lu-breadcrumbs lu-native-breadcrumbs" aria-label="{{ $page['labels']['breadcrumbs'] }}"><ol>
        @foreach($page['data']['breadcrumbs'] as $crumb)
            <li @if($loop->last) aria-current="page" @endif>@if(!$loop->first)<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m9 5 7 7-7 7"/></svg>@endif @if(!$loop->last && ($crumb['url'] ?? null))<a href="{{ $crumb['url'] }}" @if($crumb['native'] ?? true) wire:navigate @endif>{{ $crumb['label'] }}</a>@else<span>{{ $crumb['label'] }}</span>@endif</li>
        @endforeach
    </ol></nav>
@endif
