<x-guest-layout>
<div class="mb-8"><h1 class="text-3xl font-semibold tracking-tight text-slate-900">{{ __('app.login_title') }}</h1><p class="muted mt-3">{{ __('app.login_description') }}</p></div>
<x-auth-session-status class="mb-4" :status="session('status')"/>
<form method="POST" action="{{ route('login') }}" x-data="{ submitting: false }" @submit="submitting = true" class="space-y-5">
@csrf
<x-field name="email" type="email" autocomplete="username" autofocus dir="ltr"/>
<x-field name="password" type="password" autocomplete="current-password"/>
<div class="flex flex-wrap items-center justify-between gap-3 text-sm"><label class="flex items-center gap-2"><input type="checkbox" name="remember" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">{{ __('app.remember') }}</label><a href="{{ route('password.request') }}" class="text-link">{{ __('app.forgot') }}</a></div>
<x-submit class="w-full">{{ __('app.login') }}<x-icon name="arrow" class="h-4 w-4 rtl:rotate-180"/></x-submit>
</form>
<p class="muted mt-8 text-center">{{ __('app.no_account') }} <a href="{{ route('register') }}" class="text-link">{{ __('app.register') }}</a></p>
</x-guest-layout>