<x-guest-layout>
<div class="mb-8"><h1 class="text-3xl font-semibold tracking-tight text-slate-900">{{ __('app.confirm_title') }}</h1><p class="muted mt-3">{{ __('app.confirm_description') }}</p></div>
<form method="POST" action="{{ route('password.confirm') }}" x-data="{ submitting: false }" @submit="submitting = true" class="space-y-5">@csrf<x-field name="password" type="password" autocomplete="current-password" autofocus/><x-submit class="w-full">{{ __('app.confirm') }}</x-submit></form>
</x-guest-layout>