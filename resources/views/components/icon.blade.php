@props(['name' => 'dashboard'])
<svg {{ $attributes->merge(['class' => 'h-5 w-5 shrink-0']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
@switch($name)
    @case('bell')<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9 M10 21h4"/>@break
    @case('plus')<path d="M12 5v14 M5 12h14"/>@break
    @case('edit')<path d="m16 3 5 5-12 12-6 1 1-6z M14 5l5 5"/>@break
    @case('trash')<path d="M3 6h18 M9 6V3h6v3 M5 6l1 15h12l1-15 M10 10v7 M14 10v7"/>@break
    @case('search')<path d="M16 16l5 5 M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0"/>@break
    @case('chevron')<path d="m6 9 6 6 6-6"/>@break
    @case('location')<path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 1 1 16 0 M15 10a3 3 0 1 1-6 0 3 3 0 0 1 6 0"/>@break
    @case('dashboard')<path d="M3 3h7v7H3z M14 3h7v7h-7z M3 14h7v7H3z M14 14h7v7h-7z"/>@break
    @case('companies')<path d="M3 21h18 M5 21V7l7-4 7 4v14 M9 9h1 m4 0h1 M9 13h1 m4 0h1 M10 21v-4h4v4"/>@break
    @case('buildings')<path d="M3 21h18 M6 21V3h12v18 M9 7h1 m4 0h1 M9 11h1 m4 0h1 M10 21v-6h4v6"/>@break
    @case('units')<path d="m3 10 9-7 9 7 M5 9v12h14V9 M9 21v-8h6v8"/>@break
    @case('tenants')<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2 M16 4a4 4 0 0 1 0 8 M22 21v-2a4 4 0 0 0-3-3.87 M13 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0"/>@break
    @case('leases')<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z M14 2v6h6 M8 13h8 M8 17h5"/>@break
    @case('payments')<path d="M3 5h18v14H3z M3 10h18 M7 15h3"/>@break
    @case('maintenance')<path d="M14.7 6.3a5 5 0 0 0-6.4 6.4L3 18a2.1 2.1 0 0 0 3 3l5.3-5.3a5 5 0 0 0 6.4-6.4L14 13l-3-3z"/>@break
    @case('announcements')<path d="m3 10 14-6v16L3 14z M7 16l1 5h3l-1-4 M21 8v8"/>@break
    @case('documents')<path d="M3 7V4h6l2 3h10v13H3z"/>@break
    @case('reports')<path d="M4 3v18h17 M8 16v-4 M13 16V8 M18 16V5"/>@break
    @case('settings')<path d="M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8 M9 3h6l1 3 3 1 2 5-2 5-3 1-1 3H9l-1-3-3-1-2-5 2-5 3-1z"/>@break
    @case('logout')<path d="M9 21H3V3h6 M13 17l5-5-5-5 M7 12h14"/>@break
    @case('menu')<path d="M3 6h18 M3 12h18 M3 18h18"/>@break
    @case('close')<path d="m6 6 12 12 M6 18 18 6"/>@break
    @case('arrow')<path d="M5 12h14 m-6-6 6 6-6 6"/>@break
    @case('check')<path d="m5 12 4 4L19 6"/>@break
    @case('globe')<path d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0 M3 12h18 M12 3c5 5 5 13 0 18-5-5-5-13 0-18"/>@break
    @case('profile')<path d="M20 21v-2a6 6 0 0 0-6-6h-4a6 6 0 0 0-6 6v2 M16 6a4 4 0 1 1-8 0 4 4 0 0 1 8 0"/>@break
    @case('download')<path d="M12 3v12 m-5-5 5 5 5-5 M5 16v5h14v-5"/>@break
    @case('empty')<path d="M4 8l4-5h8l4 5v12H4z M4 12h5l1 3h4l1-3h5"/>@break
    @default <path d="M4 4h16v16H4z"/>
@endswitch
</svg>
