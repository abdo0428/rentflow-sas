<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}"><title>{{ __('app.brand') }} · {{ __('app.tagline') }}</title>@vite(['resources/css/app.css', 'resources/js/app.js'])<x-pwa-head/></head>
<body>
<div class="grid min-h-screen lg:grid-cols-2">
    <section class="relative hidden flex-col overflow-hidden bg-slate-950 p-12 text-white lg:flex xl:p-16">
        <a href="{{ route('login') }}" class="relative z-10 flex items-center gap-3"><x-application-logo class="h-11 w-11"/><span class="text-3xl font-semibold tracking-tight">{{ __('app.brand') }}<span class="text-emerald-400">.</span></span></a>
        <div class="relative z-10 my-auto py-14"><div class="mb-8 inline-flex items-center gap-2 rounded-full border border-emerald-400/20 bg-emerald-400/10 px-4 py-2 text-xs text-emerald-300"><span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>{{ __('app.tagline') }}</div><h1 class="whitespace-pre-line text-5xl font-semibold leading-[1.2] tracking-tight xl:text-6xl">{{ __('app.auth_headline') }}</h1><p class="mt-6 max-w-md leading-7 text-slate-400">{{ __('app.auth_description') }}</p><div class="mt-10 space-y-4">@foreach(['auth_feature_1','auth_feature_2','auth_feature_3'] as $feature)<div class="flex items-center gap-3 text-sm text-slate-300"><span class="rounded-full bg-emerald-400/10 p-1 text-emerald-400"><x-icon name="check" class="h-4 w-4"/></span>{{ __('app.'.$feature) }}</div>@endforeach</div></div>
        <div aria-hidden="true" class="pointer-events-none absolute -bottom-32 -end-32 h-96 w-96 rounded-full border-[50px] border-emerald-500/5"></div>
        <p class="relative z-10 text-xs text-slate-500">{{ __('app.auth_footer') }}</p>
    </section>
    <section class="flex flex-col bg-white px-6 py-6 sm:px-12">
        <div class="flex items-center justify-between lg:justify-end"><a href="{{ route('login') }}" class="flex items-center gap-2 font-semibold text-slate-900 lg:hidden"><x-application-logo class="h-9 w-9"/>{{ __('app.brand') }}</a><x-language-switcher/></div>
        <main class="mx-auto flex w-full max-w-md flex-1 flex-col justify-center py-12">{{ $slot }}</main>
        <p class="text-center text-xs text-slate-400">{{ __('app.auth_footer') }}</p>
    </section>
</div>
</body></html>