@props(['user'])
<div {{ $attributes->merge(['class' => 'lu-actions']) }}>
    @foreach($user['links'] ?? [] as $link)<a class="lu-button {{ $link['class'] ?? 'lu-secondary' }}" href="{{ $link['url'] }}" wire:navigate>{{ $link['label'] }}</a>@endforeach
    @foreach($user['actions'] ?? [] as $action)<button type="button" class="lu-button {{ $action['class'] ?? 'lu-secondary' }}" wire:click="requestAction('{{ $action['name'] }}', '{{ $user['id'] }}')" wire:loading.attr="disabled" @if($action['disabled'] ?? false) disabled @endif>{{ $action['label'] }}</button>@endforeach
</div>
