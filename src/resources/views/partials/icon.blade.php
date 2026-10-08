@if(config('laravelusers.iconsEnabled', true))
    <svg class="lu-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
        @switch($name)
            @case('clock')<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>@break
            @case('device')<rect x="3" y="3" width="18" height="13" rx="2"/><path d="M8 21h8m-4-5v5"/>@break
            @case('browser')<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a18 18 0 0 1 0 18 18 18 0 0 1 0-18"/>@break
            @case('network')<rect x="8" y="2" width="8" height="6" rx="1"/><path d="M12 8v6M4 14h16M4 14v3m16-3v3"/><rect x="1" y="17" width="6" height="5" rx="1"/><rect x="17" y="17" width="6" height="5" rx="1"/>@break
            @case('id')<rect x="2" y="5" width="20" height="14" rx="2"/><circle cx="8" cy="10" r="2"/><path d="M5 16v-1a3 3 0 0 1 6 0v1m3-6h5m-5 4h5"/>@break
            @case('user')<path d="M20 21v-2a7 7 0 0 0-14 0v2"/><circle cx="13" cy="7" r="4"/>@break
            @case('add-user')<path d="M16 21v-2a6 6 0 0 0-12 0v2m15-14v6m-3-3h6"/><circle cx="10" cy="7" r="4"/>@break
            @case('mail')<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 6 9 7 9-7"/>@break
            @case('lock')<rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3m-4 5v2"/>@break
            @case('edit')<path d="m16 3 5 5-12 12-6 1 1-6Z"/><path d="m14 5 5 5"/>@break
            @case('delete')<path d="M3 6h18M9 6V3h6v3M5 6l1 15h12l1-15M10 10v7m4-7v7"/>@break
            @case('show')<path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>@break
            @case('search')<circle cx="10" cy="10" r="6"/><path d="m15 15 6 6"/>@break
            @case('save')<path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h12l4 4v12a2 2 0 0 1-2 2Z"/><path d="M7 3v6h10V3M7 21v-8h10v8"/>@break
            @case('back')<path d="m9 5-7 7 7 7m-7-7h20"/>@break
            @case('next')<path d="m15 5 7 7-7 7m7-7H2"/>@break
            @case('logout')<path d="M9 3H4v18h5m6-14 5 5-5 5m5-5H9"/>@break
            @case('role')<path d="m12 3 8 3v6c0 5-8 9-8 9s-8-4-8-9V6Z"/><path d="m9 12 2 2 4-4"/>@break
            @default<path d="m6 6 12 12M6 18 18 6"/>
        @endswitch
    </svg>
@endif
