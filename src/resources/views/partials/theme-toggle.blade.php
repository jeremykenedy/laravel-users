<button type="button" id="{{ $toggleId ?? 'lu-theme' }}" data-lu-theme-target="{{ $themeTarget ?? '#laravelusers' }}" class="lu-theme-toggle" data-theme-label="{{ __('laravelusers::ui.theme_toggle') }}" aria-label="{{ __('laravelusers::ui.theme_toggle') }}: {{ __('laravelusers::ui.themes.'.\jeremykenedy\laravelusers\Support\Frontend::theme()) }}" title="{{ __('laravelusers::ui.themes.'.\jeremykenedy\laravelusers\Support\Frontend::theme()) }}" disabled>
    @foreach(['light', 'dark', 'system'] as $theme)
        <svg data-theme-icon="{{ $theme }}" data-label="{{ __('laravelusers::ui.themes.'.$theme) }}" @if(\jeremykenedy\laravelusers\Support\Frontend::theme() !== $theme) hidden @endif width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
            @if($theme === 'light')
                <circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M2 12h2m16 0h2M5 5l1.5 1.5m11 11L19 19M5 19l1.5-1.5m11-11L19 5"/>
            @elseif($theme === 'dark')
                <path d="M20.5 13A8.5 8.5 0 0 1 11 3.5 8.5 8.5 0 1 0 20.5 13Z"/>
            @else
                <rect x="3" y="4" width="18" height="13" rx="2"/><path d="M8 21h8m-4-4v4"/>
            @endif
        </svg>
    @endforeach
</button>
