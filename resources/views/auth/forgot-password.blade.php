<x-guest-layout>
<div class="mb-8"><h1 class="text-3xl font-semibold tracking-tight text-slate-900">{{ __('app.forgot_title') }}</h1><p class="muted mt-3">{{ __('app.forgot_description') }}</p></div>
<x-auth-session-status class="mb-4" :status="session('status')"/>
<form method="POST" action="{{ route('password.email') }}" x-data="{ submitting: false }" @submit="submitting = true" class="space-y-5">@csrf<x-field name="email" type="email" autocomplete="email" autofocus dir="ltr"/><x-submit class="w-full">{{ __('app.send_reset') }}</x-submit></form>
<a href="{{ route('login') }}" class="text-link mt-6 text-center text-sm">{{ __('app.login') }}</a>
</x-guest-layout>