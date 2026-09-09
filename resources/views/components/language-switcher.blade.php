<form method="POST" action="{{ route('locale.update') }}" class="flex items-center gap-1 rounded-lg border border-slate-200 bg-white p-1" aria-label="{{ __('app.language') }}">
    @csrf
    @foreach(['en', 'ar'] as $locale)
        <button type="submit" name="locale" value="{{ $locale }}" lang="{{ $locale }}" aria-pressed="{{ app()->getLocale() === $locale ? 'true' : 'false' }}" @class(['rounded-md px-2.5 py-1.5 text-xs font-semibold transition hover:bg-slate-100', 'bg-emerald-50 text-emerald-800' => app()->getLocale() === $locale, 'text-slate-500' => app()->getLocale() !== $locale])>{{ __('app.'.$locale) }}</button>
    @endforeach
</form>