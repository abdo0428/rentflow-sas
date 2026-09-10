<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('app.brand') }} · {{ __('app.tagline') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <x-pwa-head/>
</head>
<body x-data="{ sidebarOpen: false }" @keydown.escape.window="if (sidebarOpen) { sidebarOpen = false; $refs.mobileToggle.focus() }">
    <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:z-50 focus:bg-white focus:p-4">{{ __('app.skip_content') }}</a>
    <div x-cloak x-show="sidebarOpen" @click="sidebarOpen = false" class="fixed inset-0 z-30 bg-slate-950/50 backdrop-blur-sm lg:hidden"></div>
    <aside :class="sidebarOpen ? 'flex' : 'hidden lg:flex'" id="workspace-sidebar" x-ref="sidebar" @keydown.tab="if(sidebarOpen) { const items = [...$el.querySelectorAll('a,button')].filter(el => el.getClientRects().length); if($event.shiftKey && document.activeElement === items[0]) { $event.preventDefault(); items.at(-1).focus(); } else if(!$event.shiftKey && document.activeElement === items.at(-1)) { $event.preventDefault(); items[0].focus(); } }" class="fixed inset-y-0 start-0 z-40 w-64 flex-col bg-slate-950 text-white">
        <a href="{{ route('dashboard') }}" class="flex h-24 items-center gap-3 px-7"><x-application-logo class="h-10 w-10"/><span class="text-2xl font-semibold tracking-tight">{{ __('app.brand') }}<span class="text-emerald-400">.</span></span></a>
        <button @click="sidebarOpen = false" class="absolute end-3 top-4 rounded p-2 text-slate-400 lg:hidden" aria-label="{{ __('app.close') }}"><x-icon name="close"/></button>
        <nav class="flex-1 space-y-1 overflow-y-auto px-4 pb-5" aria-label="{{ __('app.workspace') }}">
            <p class="px-3 pb-3 pt-4 text-[10px] font-semibold tracking-[0.18em] text-slate-500">{{ __('app.workspace') }}</p>
            <a href="{{ route('dashboard') }}" @class(['nav-link', 'nav-active' => request()->routeIs('dashboard')]) @if(request()->routeIs('dashboard')) aria-current="page" @endif><x-icon name="dashboard"/>{{ __('app.overview') }}</a>
            @foreach($navigation as $item)
                <a href="{{ route($item['route']) }}" @class(['nav-link', 'nav-active' => request()->routeIs(str_replace('.index', '.*', $item['route']))]) @if(request()->routeIs(str_replace('.index', '.*', $item['route']))) aria-current="page" @endif><x-icon :name="$item['icon']"/>{{ $item['label'] }}</a>
            @endforeach
        </nav>
        <div class="mx-5 mb-5 rounded-xl border border-white/10 bg-white/[0.03] p-4"><span class="mb-2 flex items-center gap-2 text-xs font-medium text-emerald-300"><span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>{{ __('app.secure_workspace') }}</span><p class="text-xs text-slate-500">{{ __('app.'.$currentRole) }}</p></div>
        <form method="POST" action="{{ route('logout') }}" class="border-t border-white/10 p-4">@csrf<button class="nav-link w-full"><x-icon name="logout"/>{{ __('app.logout') }}</button></form>
    </aside>
    <div class="min-h-screen lg:ms-64">
        <header class="sticky top-0 z-20 flex h-20 items-center justify-between gap-3 border-b border-slate-200/80 bg-white/90 px-4 sm:px-8">
            <div class="flex min-w-0 items-center gap-3">
                <button x-ref="mobileToggle" @click="sidebarOpen = !sidebarOpen; if (sidebarOpen) $nextTick(() => $refs.sidebar.querySelector('a,button').focus())" :aria-expanded="sidebarOpen" aria-controls="workspace-sidebar" class="rounded-lg p-2 lg:hidden" aria-label="{{ __('app.menu') }}"><x-icon name="menu"/></button>
                @isset($breadcrumbs){{ $breadcrumbs }}@else<x-breadcrumbs :items="[(string) ($header ?? __('app.overview')) => null]"/>@endisset
            </div>
            <div class="flex shrink-0 items-center gap-3 sm:gap-5">
                <x-language-switcher/>
                <a href="{{ route('notifications.index') }}" class="relative rounded-xl p-2 text-slate-500 transition hover:bg-slate-100 hover:text-emerald-700" aria-label="{{ __('workflow.notifications') }}"><x-icon name="bell"/>@if($unreadNotificationsCount)<span class="absolute -end-1 -top-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-emerald-600 px-1 text-[10px] font-semibold text-white">{{ $unreadNotificationsCount > 99 ? '99+' : $unreadNotificationsCount }}</span>@endif</a>
                <x-dropdown align="right" width="64">
                    <x-slot name="trigger">
                        <button x-ref="trigger" type="button" :aria-expanded="open" aria-controls="user-menu" aria-label="{{ __('property.user_menu') }}" @keydown.arrow-down.prevent="open = true; $nextTick(() => $refs.panel.querySelector('a').focus())" class="flex items-center gap-3 rounded-xl py-1 ps-1 pe-2 transition hover:bg-slate-50">
                            <span class="hidden text-end md:block"><span class="block max-w-40 truncate text-sm font-semibold text-slate-800">{{ auth()->user()->name }}</span><span class="text-xs text-slate-400">{{ __('app.'.$currentRole) }}</span></span>
                            <span class="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-100 font-semibold text-emerald-800">{{ mb_substr(auth()->user()->name, 0, 1) }}</span><x-icon name="chevron" class="hidden h-3.5 w-3.5 text-slate-400 sm:block"/>
                        </button>
                    </x-slot>
                    <x-slot name="content">
                        <div id="user-menu" class="p-2"><div class="border-b border-slate-100 px-3 pb-3 pt-2"><p class="truncate text-sm font-semibold text-slate-900">{{ auth()->user()->name }}</p><p class="mt-1 truncate text-xs text-slate-400" dir="ltr">{{ auth()->user()->email }}</p></div><a href="{{ route('profile.edit') }}" class="mt-1 flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm hover:bg-slate-50"><x-icon name="profile" class="h-4 w-4"/>{{ __('property.view_profile') }}</a>@can('settings.view')<a href="{{ route('settings.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm hover:bg-slate-50"><x-icon name="settings" class="h-4 w-4"/>{{ __('app.settings') }}</a>@endcan<form method="POST" action="{{ route('logout') }}">@csrf<button class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-start text-sm text-red-600 hover:bg-red-50"><x-icon name="logout" class="h-4 w-4"/>{{ __('app.logout') }}</button></form></div>
                    </x-slot>
                </x-dropdown>
            </div>
        </header>
        <main id="main" class="mx-auto max-w-[1600px] space-y-7 p-4 py-7 sm:p-8 lg:p-10">
            <div data-offline-banner hidden class="rounded-lg bg-amber-100 p-4 text-sm text-amber-900" role="status">{{ __('portal.offline_banner') }}</div>
            <x-pwa-install/><x-flash/>
            {{ $slot }}
            <x-delete-confirmation/>
        </main>
    </div>
</body>
</html>
