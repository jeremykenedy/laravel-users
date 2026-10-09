<span class="lu-avatar{{ ($navigation ?? false) ? ' lu-navigation-avatar' : '' }}" style="width: {{ $avatar['size'] }}px; height: {{ $avatar['size'] }}px;" aria-hidden="true">
    <span data-lu-initials @if($avatar['fallback'] !== 'initials') hidden @endif>{{ $avatar['initials'] }}</span>
    <svg data-lu-avatar-icon width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" @if($avatar['fallback'] === 'initials') hidden @endif><circle cx="12" cy="8" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2"/></svg>
    @if($avatar['src'])<img src="{{ $avatar['src'] }}" width="{{ $avatar['size'] }}" height="{{ $avatar['size'] }}" alt="" loading="lazy" referrerpolicy="no-referrer">@endif
</span>
