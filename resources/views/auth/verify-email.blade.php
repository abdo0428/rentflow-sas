<x-guest-layout>
<div class="mb-8"><h1 class="text-3xl font-semibold tracking-tight text-slate-900">{{ __('app.verify_title') }}</h1><p class="muted mt-3">{{ __('app.verify_description') }}</p></div>
@if(session('status') === 'verification-link-sent')<p role="status" class="mb-5 rounded-lg bg-emerald-50 p-4 text-sm text-emerald-800">{{ __('app.verification_sent') }}</p>@endif
<form method="POST" action="{{ route('verification.send') }}" x-data="{ submitting: false }" @submit="submitting = true">@csrf<x-submit class="w-full">{{ __('app.resend') }}</x-submit></form>
<form method="POST" action="{{ route('logout') }}" class="mt-5 text-center">@csrf<button class="text-link text-sm">{{ __('app.logout') }}</button></form>
</x-guest-layout>