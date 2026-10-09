@if(config('laravelusers.iconsEnabled', true))
    <svg data-lu-icon="{{ $name }}" class="lu-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
        @switch($name)
            @case('settings')<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09A1.65 1.65 0 0 0 15 4.6a1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.32 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09A1.65 1.65 0 0 0 19.4 15Z"/>@break
            @case('add')<path d="M12 5v14m-7-7h14"/>@break
            @case('check')<path d="m5 12 4 4L19 6"/>@break
            @case('verify')<path d="M12 22s8-4 8-11V5l-8-3-8 3v6c0 7 8 11 8 11Z"/><path d="m9 12 2 2 4-4"/>@break
            @case('warning')<path d="M10.3 3.9 2.5 17.4A2 2 0 0 0 4.2 20h15.6a2 2 0 0 0 1.7-2.6L13.7 3.9a2 2 0 0 0-3.4 0Z"/><path d="M12 9v4m0 3h.01"/>@break
            @case('appearance')<circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42"/>@break
            @case('palette')<path d="M12 3a9 9 0 1 0 0 18h1.2a2.3 2.3 0 0 0 1.7-3.8 1.8 1.8 0 0 1 1.3-3.1H18a3 3 0 0 0 3-3c0-4.5-4-8.1-9-8.1Z"/><circle cx="7.5" cy="11" r=".8"/><circle cx="10" cy="7.5" r=".8"/><circle cx="14.5" cy="7.5" r=".8"/>@break
            @case('notifications')<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9m-8 12h4"/>@break
            @case('package')<path d="m12 3 9 5-9 5-9-5 9-5Z"/><path d="M3 8v9l9 5 9-5V8m-9 5v9m-4-17 9 5"/>@break
            @case('install')<path d="M12 3v12m-5-5 5 5 5-5M4 19h16v2H4z"/>@break
            @case('select-all')<rect x="3" y="3" width="18" height="18" rx="2"/><path data-lu-checkmark hidden d="m7 12 3 3 7-7"/>@break
            @case('deselect-all')<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M7 12h10"/>@break
            @case('sort')<path d="m4 8 4-4 4 4M8 4v16m4-4 4 4 4-4m-4 4V4"/>@break
            @case('columns')<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 3v18m6-18v18"/>@break
            @case('filter')<path d="M3 4h18l-7 8v7l-4 2v-9Z"/>@break
            @case('table')<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M3 15h18M9 3v18"/>@break
            @case('cards')<rect x="3" y="3" width="18" height="7" rx="2"/><rect x="3" y="14" width="18" height="7" rx="2"/>@break
            @case('clock')<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>@break
            @case('spinner')<path d="M21 12a9 9 0 1 1-9-9"/>@break
            @case('device')<rect x="3" y="3" width="18" height="13" rx="2"/><path d="M8 21h8m-4-5v5"/>@break
            @case('chrome')<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="4"/><path d="M12 8h8m-11 7-4-7m7 8-4 5"/>@break
            @case('safari')<circle cx="12" cy="12" r="9"/><path d="m16 8-3 5-5 3 3-5Z"/>@break
            @case('firefox')<path d="m18 3 1 5a9 9 0 1 1-14-1l1 4 4-2-1-5 5 4c4 2 4 6 1 8-2 1-4 0-5-1"/>@break
            @case('edge')<path d="M21 13a9 9 0 1 0-3 6H9a4 4 0 0 1 0-8h8a4 4 0 0 0-8-1m0 5h12"/>@break
            @case('windows')<path d="M3 5 10 4v7H3Zm10-1 8-1v8h-8ZM3 14h7v7l-7-1Zm10 0h8v8l-8-1Z"/>@break
            @case('apple')<path d="M14 6c1-3 3-4 4-4-1 3-3 4-4 4Zm-2 2c-6-4-10 2-8 7s4 7 6 6l2-1 2 1c3 1 5-2 6-5-4-2-4-6-1-8-2-2-4-2-7 0Z"/>@break
            @case('android')<path d="M5 10a7 7 0 0 1 14 0v7H5Zm2-7 2 3m8-3-2 3M8 17v4m8-4v4M2 10v7m20-7v7"/><circle cx="9" cy="9" r=".5"/><circle cx="15" cy="9" r=".5"/>@break
            @case('linux')<path d="M8 10V7a4 4 0 0 1 8 0v3c3 3 4 6 2 8H6c-2-2-1-5 2-8Zm1 0 3 2 3-2M5 18l-2 3h7m9-3 2 3h-7"/><circle cx="10" cy="7" r=".5"/><circle cx="14" cy="7" r=".5"/>@break
            @case('browser')<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a18 18 0 0 1 0 18 18 18 0 0 1 0-18"/>@break
            @case('network')<rect x="8" y="2" width="8" height="6" rx="1"/><path d="M12 8v6M4 14h16M4 14v3m16-3v3"/><rect x="1" y="17" width="6" height="5" rx="1"/><rect x="17" y="17" width="6" height="5" rx="1"/>@break
            @case('id')<rect x="2" y="5" width="20" height="14" rx="2"/><circle cx="8" cy="10" r="2"/><path d="M5 16v-1a3 3 0 0 1 6 0v1m3-6h5m-5 4h5"/>@break
            @case('users')<circle cx="9" cy="7" r="4"/><path d="M2 21v-2a7 7 0 0 1 14 0v2m1-18a4 4 0 0 1 0 8m1 3a6 6 0 0 1 4 5v2"/>@break
            @case('user')<path d="M20 21v-2a8 8 0 0 0-16 0v2"/><circle cx="12" cy="7" r="4"/>@break
            @case('add-user')<path d="M16 21v-2a6 6 0 0 0-12 0v2m15-14v6m-3-3h6"/><circle cx="10" cy="7" r="4"/>@break
            @case('mail')<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 6 9 7 9-7"/>@break
            @case('lock')<rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3m-4 5v2"/>@break
            @case('edit')<path d="m16 3 5 5-12 12-6 1 1-6Z"/><path d="m14 5 5 5"/>@break
            @case('delete')<path d="M3 6h18M9 6V3h6v3M5 6l1 15h12l1-15M10 10v7m4-7v7"/>@break
            @case('show')<path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>@break
            @case('search')<circle cx="10" cy="10" r="6"/><path d="m15 15 6 6"/>@break
            @case('save')<path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h12l4 4v12a2 2 0 0 1-2 2Z"/><path d="M7 3v6h10V3M7 21v-8h10v8"/>@break
            @case('back')<path d="m9 5-7 7 7 7m-7-7h20"/>@break
            @case('restore')<path d="M3 10a9 9 0 1 1 2.7 8.4M3 4v6h6"/>@break
            @case('reply')<path d="m9 4-6 6 6 6m-6-6h9a9 9 0 0 1 9 9v2"/>@break
            @case('next')<path d="m15 5 7 7-7 7m7-7H2"/>@break
            @case('logout')<path d="M9 3H4v18h5m6-14 5 5-5 5m5-5H9"/>@break
            @case('role')<path d="m12 3 8 3v6c0 5-8 9-8 9s-8-4-8-9V6Z"/><path d="m9 12 2 2 4-4"/>@break
            @case('secret-agent')<circle cx="12" cy="12" r="9"/><path d="M3.5 9h17M3.5 15h17"/><path d="M8 9a4 4 0 0 1 8 0m-8 6a4 4 0 0 0 8 0"/><path d="m12 10 1 2-1 2-1-2Z"/>@break
            @case('toggle-on')<rect x="2" y="6" width="20" height="12" rx="6"/><circle cx="16" cy="12" r="4"/>@break
            @case('toggle-off')<rect x="2" y="6" width="20" height="12" rx="6"/><circle cx="8" cy="12" r="4"/>@break
            @case('close')<path d="m6 6 12 12M6 18 18 6"/>@break
        @endswitch
    </svg>
@endif
