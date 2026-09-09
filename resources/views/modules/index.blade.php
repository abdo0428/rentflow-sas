<x-app-layout>
<x-slot name="header">{{ __('app.'.$module) }}</x-slot>
<div><p class="mb-2 text-xs font-semibold uppercase tracking-widest text-emerald-700">{{ __('app.foundation') }}</p><h1 class="page-title">{{ __('app.'.$module) }}</h1><p class="muted mt-2">{{ __('app.foundation_description') }}</p></div>
<section class="card overflow-hidden">
@if($records->isEmpty())<x-empty-state/>@else
<div class="table-wrap"><table class="data-table"><thead><tr>@foreach($columns as $column)<th scope="col">{{ __('app.'.$column) }}</th>@endforeach<th scope="col"><span class="sr-only">{{ __('app.actions') }}</span></th></tr></thead><tbody>@foreach($records as $record)<tr>@foreach($columns as $column)<td><x-data-value :value="$record->$column" :column="$column"/></td>@endforeach<td><a href="{{ route($module.'.show', $record) }}" class="text-link">{{ __('app.view') }}</a></td></tr>@endforeach</tbody></table></div><div class="p-5">{{ $records->links('components.pagination') }}</div>
@endif
</section></x-app-layout>