@props(['user', 'column', 'text'])
@if(($column['type'] ?? 'text') === 'avatar')
    @if($user['avatar'] ?? null)<x-laravelusers::livewire.avatar :avatar="$user['avatar']" />@endif
@elseif(($column['type'] ?? 'text') === 'activity')
    <x-laravelusers::livewire.activity :activity="$user['activity'] ?? []" />
@elseif(($column['type'] ?? 'text') === 'date' && $text)
    <time datetime="{{ $text }}">{{ $user['date_labels'][$column['key']] ?? $text }}</time>
@elseif($column['key'] === 'name' && ($user['urls']['show'] ?? null))
    <a href="{{ $user['urls']['show'] }}" wire:navigate>{{ $text }}</a>
@elseif($column['key'] === 'email' && ($column['linked'] ?? false))
    <a href="mailto:{{ $text }}">{{ $text }}</a>
@elseif(($column['type'] ?? 'text') === 'presence')
    @if($user['activity']['online'] ?? false)<span class="lu-badge lu-online">{{ __('laravelusers::ui.online') }}</span>@endif
@else
    {{ $text }}
@endif
