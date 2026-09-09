<x-app-layout>
<x-slot name="header">{{ __('app.'.$module) }}</x-slot>
<a href="{{ route($module.'.index') }}" class="text-link inline-flex items-center gap-2 text-sm"><x-icon name="arrow" class="h-4 w-4 rotate-180 rtl:rotate-0"/>{{ __('app.back') }}</a>
<div class="flex flex-wrap items-center justify-between gap-4"><h1 class="page-title">{{ __('app.'.$module) }} <span class="text-slate-400">#{{ $record->id }}</span></h1>@if($module === 'documents')<a href="{{ route('documents.download', $record) }}" class="btn-primary"><x-icon name="download"/>{{ __('app.download') }}</a>@endif</div>
<dl class="card grid gap-6 p-6 sm:grid-cols-2">@foreach($columns as $column)<div @class(['sm:col-span-2' => in_array($column, ['body','description'])])><dt class="mb-2 text-xs font-medium text-slate-500">{{ __('app.'.$column) }}</dt><dd class="whitespace-pre-line break-words text-sm text-slate-900"><x-data-value :value="$record->$column" :column="$column"/></dd></div>@endforeach</dl>
</x-app-layout>