@props(['page'])
@if(array_key_exists('package_operation', $page['data']))
    <div data-lu-native-package-region wire:ignore>
        <div class="lu-flash lu-native-package-status" data-lu-native-package-status hidden><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" class="lu-package-status-icon"><path stroke-linecap="round" stroke-linejoin="round"/></svg><div><p data-lu-native-package-message></p><p data-lu-native-package-error role="alert" hidden></p><a href="{{ $page['urls']['settings'] }}" class="lu-button lu-secondary" data-lu-native-package-refresh hidden>{{ $page['labels']['package_refresh'] }}</a></div></div>
    </div>
@endif
