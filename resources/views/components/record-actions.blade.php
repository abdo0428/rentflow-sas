@props(['record', 'module', 'label', 'show' => true])
<div class="flex items-center justify-end gap-2">
@if($show)<a href="{{ route($module.'.show', $record) }}" class="rounded-lg p-2 text-slate-500 transition hover:bg-emerald-50 hover:text-emerald-700" aria-label="{{ __('app.view') }}: {{ $label }}"><x-icon name="arrow" class="h-4 w-4 rtl:rotate-180"/></a>@endif
@can('update', $record)<a href="{{ route($module.'.edit', $record) }}" class="rounded-lg p-2 text-slate-500 transition hover:bg-slate-100" aria-label="{{ __('property.edit') }}: {{ $label }}"><x-icon name="edit" class="h-4 w-4"/></a>@endcan
@can('delete', $record)<button type="button" x-data @click="$dispatch('confirm-delete', { action: @js(route($module.'.destroy', $record)), label: @js($label) })" class="rounded-lg p-2 text-slate-400 transition hover:bg-red-50 hover:text-red-600" aria-label="{{ __('property.delete') }}: {{ $label }}"><x-icon name="trash" class="h-4 w-4"/></button>@endcan
</div>