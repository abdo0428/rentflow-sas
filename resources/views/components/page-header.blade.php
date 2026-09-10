@props(['title', 'description' => null, 'icon' => 'buildings'])
<div class="flex flex-wrap items-start justify-between gap-5">
    <div class="flex min-w-0 items-start gap-4">
        <span class="hidden rounded-2xl border border-emerald-100 bg-emerald-50 p-3.5 text-emerald-700 sm:block"><x-icon :name="$icon" class="h-7 w-7"/></span>
        <div class="min-w-0"><p class="mb-2 text-[10px] font-bold uppercase tracking-[.18em] text-emerald-700">{{ auth()->user()?->hasRole('tenant') ? __('portal.portal') : __('property.portfolio') }}</p><h1 class="page-title break-words">{{ $title }}</h1>@if($description)<p class="muted mt-2 max-w-2xl break-words">{{ $description }}</p>@endif</div>
    </div>
    @isset($actions)<div class="flex min-w-0 max-w-full flex-wrap items-center gap-2">{{ $actions }}</div>@endisset
</div>
