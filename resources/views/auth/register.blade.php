<x-guest-layout>
<div class="mb-8"><h1 class="text-3xl font-semibold tracking-tight text-slate-900">{{ __('app.register_title') }}</h1><p class="muted mt-3">{{ __('app.register_description') }}</p></div>
<form method="POST" action="{{ route('register') }}" x-data="{ submitting: false }" @submit="submitting = true" class="space-y-4">
@csrf
<x-field name="company_name" autocomplete="organization" autofocus/>
<x-field name="name" autocomplete="name"/>
<x-field name="email" type="email" autocomplete="username" dir="ltr"/>
<x-field name="password" type="password" autocomplete="new-password"/>
<x-field name="password_confirmation" type="password" autocomplete="new-password"/>
<x-submit class="w-full">{{ __('app.register') }}</x-submit>
</form><p class="muted mt-6 text-center">{{ __('app.has_account') }} <a href="{{ route('login') }}" class="text-link">{{ __('app.login') }}</a></p>
</x-guest-layout>