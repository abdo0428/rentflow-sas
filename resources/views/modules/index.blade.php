<x-app-layout>
<x-slot name="header">{{ __('app.'.$module) }}</x-slot>
<x-page-header :title="__('app.'.$module)" :description="__('app.foundation_description')" :icon="$module"/>
<section class="card overflow-hidden">
@if($records->isEmpty())<x-empty-state/>@else
<div class="table-wrap"><table class="data-table"><thead><tr>@foreach($columns as $column)<th scope="col">{{ __('app.'.$column) }}</th>@endforeach<th scope="col"><span class="sr-only">{{ __('app.actions') }}</span></th></tr></thead><tbody>@foreach($records as $record)<tr>@foreach($columns as $column)<td><x-data-value :value="$record->$column" :column="$column"/></td>@endforeach<td><a href="{{ route($module.'.show', $record) }}" class="text-link">{{ __('app.view') }}</a></td></tr>@endforeach</tbody></table></div><div class="p-5">{{ $records->links('components.pagination') }}</div>
@endif
</section></x-app-layout>
