@props(['title' => null, 'description' => null])
<section {{ $attributes->class(['card min-w-0 overflow-hidden']) }}>
@if($title)<div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-5 sm:px-6"><div><h2 class="font-semibold text-slate-900">{{ $title }}</h2>@if($description)<p class="muted mt-1">{{ $description }}</p>@endif</div>@isset($actions){{ $actions }}@endisset</div>@endif
{{ $slot }}
</section>