<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"><meta name="csrf-token" content="{{ csrf_token() }}"><title>{{ __('app.brand') }} · {{ __('portal.portal') }}</title><x-pwa-head/>@vite(['resources/css/app.css', 'resources/js/app.js'])</head>
<body x-data="{ moreOpen: false }" x-init="$watch('moreOpen', value => document.body.style.overflow = value ? 'hidden' : '')" @keydown.escape.window="if (moreOpen) { moreOpen = false; $refs.moreToggle.focus() }" class="bg-slate-50">
    <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:z-50 focus:bg-white focus:p-4">{{ __('app.skip_content') }}</a>
    <header class="sticky top-0 z-30 border-b border-slate-200/70 bg-white/95 backdrop-blur">
        <div class="mx-auto flex h-20 max-w-6xl items-center justify-between gap-2 px-4 sm:px-6">
            <a href="{{ route('dashboard') }}" class="flex min-w-0 items-center gap-2"><x-application-logo class="h-9 w-9 shrink-0"/><span class="hidden text-xl font-semibold tracking-tight text-slate-900 sm:block">{{ __('app.brand') }}<span class="block text-[10px] font-medium tracking-normal text-emerald-700">{{ __('portal.portal') }}</span></span></a>
            <div class="flex items-center gap-2 sm:gap-4"><x-language-switcher/><a href="{{ route('notifications.index') }}" class="relative rounded-xl p-2 text-slate-500 hover:bg-slate-100" aria-label="{{ __('workflow.notifications') }}"><x-icon name="bell"/>@if($unreadNotificationsCount)<span class="absolute end-0 top-0 h-2 w-2 rounded-full bg-emerald-600"></span>@endif</a><a href="{{ route('profile.edit') }}" class="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-100 font-semibold text-emerald-800" aria-label="{{ __('portal.profile') }}">{{ mb_substr(auth()->user()->name, 0, 1) }}</a></div>
        </div>
        <nav class="mx-auto hidden max-w-6xl items-center gap-1 overflow-x-auto px-6 pb-3 md:flex" aria-label="{{ __('portal.portal') }}">
            @foreach($tenantNavigation as $item)<a href="{{ route($item['route']) }}" @class(['flex items-center gap-2 whitespace-nowrap rounded-lg px-3 py-2 text-sm transition hover:bg-slate-50', 'bg-emerald-50 font-semibold text-emerald-800' => $item['active'], 'text-slate-500' => !$item['active']]) @if($item['active']) aria-current="page" @endif><x-icon :name="$item['icon']" class="h-4 w-4"/>{{ __('portal.'.$item['label']) }}</a>@endforeach
        </nav>
    </header>
    <div data-offline-banner hidden class="bg-amber-100 px-4 py-3 text-center text-sm text-amber-900" role="status">{{ __('portal.offline_banner') }}</div>
    <main id="main" class="mx-auto max-w-6xl space-y-6 px-4 pb-32 pt-7 sm:px-6 md:pb-10">
        @isset($breadcrumbs)<div>{{ $breadcrumbs }}</div>@endisset
        <x-flash/>{{ $slot }}<x-delete-confirmation/>
        <footer class="flex flex-wrap items-center justify-between gap-4 border-t border-slate-200 pt-6"><span class="text-xs text-slate-400">{{ __('app.tenant') }} · {{ __('portal.portal') }}</span><x-pwa-install/><form method="POST" action="{{ route('logout') }}">@csrf<button class="text-link text-xs">{{ __('app.logout') }}</button></form></footer>
    </main>
    <div x-cloak x-show="moreOpen" class="fixed inset-0 z-40 bg-slate-950/40 backdrop-blur-sm md:hidden" @click="moreOpen = false"></div>
    <section x-cloak x-show="moreOpen" x-transition role="dialog" aria-modal="true" aria-labelledby="more-menu-title" class="fixed inset-x-3 bottom-24 z-50 rounded-2xl bg-white p-5 shadow-xl md:hidden" @keydown.tab="const items = [...$el.querySelectorAll('a,button')]; if ($event.shiftKey && document.activeElement === items[0]) { $event.preventDefault(); items.at(-1).focus(); } else if (!$event.shiftKey && document.activeElement === items.at(-1)) { $event.preventDefault(); items[0].focus(); }">
        <div class="mb-3 flex items-center justify-between"><h2 id="more-menu-title" class="font-semibold text-slate-900">{{ __('portal.more') }}</h2><button @click="moreOpen = false; $refs.moreToggle.focus()" class="rounded-lg p-2" aria-label="{{ __('app.close') }}"><x-icon name="close"/></button></div>
        <nav class="grid grid-cols-2 gap-2" aria-label="{{ __('portal.more') }}">@foreach($tenantNavigation as $item)<a href="{{ route($item['route']) }}" class="flex items-center gap-3 rounded-xl bg-slate-50 p-3 text-sm hover:bg-emerald-50"><x-icon :name="$item['icon']" class="text-emerald-700"/>{{ __('portal.'.$item['label']) }}</a>@endforeach</nav>
    </section>
    <nav class="fixed inset-x-0 bottom-0 z-40 grid grid-cols-5 border-t border-slate-200 bg-white/95 px-2 pt-2 shadow-lg backdrop-blur md:hidden" style="padding-bottom: max(.5rem, env(safe-area-inset-bottom))" aria-label="{{ __('portal.home') }}">
        @foreach($tenantMobileNavigation as $item)<a href="{{ route($item['route']) }}" @class(['flex min-h-14 flex-col items-center justify-center gap-1 rounded-xl px-1 text-[10px]', 'font-semibold text-emerald-800 bg-emerald-50' => $item['active'], 'text-slate-500' => !$item['active']])><x-icon :name="$item['icon']"/>{{ __('portal.'.$item['label']) }}</a>@endforeach
        <button x-ref="moreToggle" @click="moreOpen = !moreOpen; if (moreOpen) $nextTick(() => document.querySelector('[aria-labelledby=more-menu-title] button').focus())" :aria-expanded="moreOpen" class="flex min-h-14 flex-col items-center justify-center gap-1 rounded-xl text-[10px] text-slate-500"><x-icon name="menu"/>{{ __('portal.more') }}</button>
    </nav>
</body></html>
