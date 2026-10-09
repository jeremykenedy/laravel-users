@props(['page'])
@if($page['data']['packages'] ?? null)
<section class="lu-package-settings lu-pad" aria-labelledby="lu-native-packages-title">
    <h2 id="lu-native-packages-title">{{ $page['labels']['settings_packages'] }}</h2>
    <p class="lu-muted">{{ $page['labels']['packages_hint'] }}</p>
    @unless($page['data']['packages']['ready'])<p>{{ $page['labels']['packages_queue_required'] }}</p>@endunless
    <div class="lu-actions">
        <button type="button" class="lu-button lu-secondary" wire:click="openSettingsAction('package-requirements')" @if($page['data']['packages']['ready']) disabled @endif>{{ $page['data']['packages']['ready'] ? $page['labels']['package_requirements_completed'] : $page['labels']['package_requirements_setup'] }}</button>
        <button type="button" class="lu-button lu-secondary" data-lu-native-verify-requirements>{{ $page['labels']['package_requirements_verify'] }}</button>
    </div>
    <div wire:ignore class="lu-native-package-status" role="status" data-lu-native-package-requirements @unless($page['data']['packages']['requirements']) hidden @endunless>
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path/></svg>
        <div><p data-lu-native-requirements-message>{{ $page['data']['packages']['requirements']['message'] ?? '' }}</p><p data-lu-native-requirements-error role="alert" hidden></p></div>
    </div>
    <p class="lu-muted">{{ $page['labels']['package_requirements_hint'] }}</p>
    <div class="lu-actions">@foreach($page['data']['packages']['help'] as $link)<a href="{{ $link['url'] }}" target="_blank" rel="noopener noreferrer">{{ $link['label'] }}</a>@endforeach</div>
    <div class="lu-settings-grid">@foreach($page['data']['packages']['choices'] as $choice)<section class="lu-settings-choice">
        <h3>{{ $choice['label'] }}</h3><p>{{ $choice['installed'] ? $page['labels']['package_installed'] : $page['labels']['package_not_installed'] }}</p>
        @if($choice['hint'])<p class="lu-muted">{{ $choice['hint'] }}</p>@endif @if($choice['reason'])<p class="lu-muted">{{ $choice['reason'] }}</p>@endif
        <button type="button" class="lu-button lu-secondary" wire:click="openSettingsAction('{{ $choice['name'] }}')" @if($choice['disabled']) disabled @endif>{{ $choice['installed'] ? $page['labels']['package_remove'] : $page['labels']['package_install'] }}</button>
        @if($choice['configure_name'])<button type="button" class="lu-button lu-secondary" wire:click="openSettingsAction('{{ $choice['configure_name'] }}')" @unless($page['data']['packages']['ready']) disabled @endunless>{{ $page['labels']['package_configure'] }}</button>@endif
        @if($choice['setup_hint'])<p class="lu-muted">{{ $choice['setup_hint'] }}</p>@endif
    </section>@endforeach</div>
</section>
@endif
