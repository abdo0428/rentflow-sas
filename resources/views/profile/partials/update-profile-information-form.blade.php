<h2 class="mb-6 text-lg font-semibold text-slate-900">{{ __('app.account_details') }}</h2>
<form id="send-verification" method="POST" action="{{ route('verification.send') }}">@csrf</form>
<form method="POST" action="{{ route('profile.update') }}" x-data="{ submitting: false }" @submit="submitting = true" class="space-y-5">@csrf @method('patch')
<x-field name="name" :value="$user->name" autocomplete="name"/>
<x-field name="email" type="email" :value="$user->email" autocomplete="username" dir="ltr"/>
<x-field name="phone" type="tel" :value="$user->phone" :required="false" autocomplete="tel" dir="ltr"/>
@if(! $user->hasVerifiedEmail())<div class="rounded-lg bg-amber-50 p-3 text-sm text-amber-800"><p>{{ __('app.unverified') }}</p><button form="send-verification" class="mt-1 underline">{{ __('app.verify_again') }}</button></div>@endif
@if(session('status') === 'verification-link-sent')<p role="status" class="text-sm text-emerald-700">{{ __('app.verification_sent') }}</p>@endif
<div class="flex items-center gap-4"><x-submit>{{ __('app.save') }}</x-submit>@if(session('status') === 'profile-updated')<p role="status" class="text-sm text-emerald-700">{{ __('app.saved') }}</p>@endif</div>
</form>