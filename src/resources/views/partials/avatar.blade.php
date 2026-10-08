<span class="lu-avatar" style="width: {{ $avatar['size'] }}px; height: {{ $avatar['size'] }}px;" aria-hidden="true">
    @if($avatar['fallback'] === 'initials')<span>{{ $avatar['initials'] }}</span>@else<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2"/></svg>@endif
    @if($avatar['src'])<img src="{{ $avatar['src'] }}" width="{{ $avatar['size'] }}" height="{{ $avatar['size'] }}" alt="" loading="lazy" referrerpolicy="no-referrer">@endif
</span>
