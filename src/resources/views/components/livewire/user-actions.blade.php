@props(['user'])
<div {{ $attributes->merge(['class' => 'lu-actions']) }}>
    @foreach($user['links'] ?? [] as $link)<a class="lu-button {{ $link['class'] ?? 'lu-secondary' }}" href="{{ $link['url'] }}" wire:navigate><x-laravelusers::livewire.icon :action="$link['name']"/><span>{{ $link['label'] }}</span></a>@endforeach
    @foreach($user['actions'] ?? [] as $action)<button type="button" class="lu-button {{ $action['class'] ?? 'lu-secondary' }}" wire:click="requestAction('{{ $action['name'] }}', '{{ $user['id'] }}')" wire:loading.attr="disabled" @if($action['disabled'] ?? false) disabled @endif><x-laravelusers::livewire.icon :action="$action['name']"/><span>{{ $action['label'] }}</span></button>@endforeach
</div>
