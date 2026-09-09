<x-app-layout>
    <x-slot name="header">{{ __('app.leases') }}</x-slot>
    <x-page-header :title="__('app.leases')" :description="__('workflow.leases_intro')" icon="leases"><x-slot name="actions">@can('create', App\Models\LeaseContract::class)<a href="{{ route('leases.create') }}" class="btn-primary"><x-icon name="plus"/>{{ __('workflow.create_lease') }}</a>@endcan</x-slot></x-page-header>
    <div class="grid gap-4 sm:grid-cols-3"><x-stat-card :label="__('workflow.total_contracts')" :value="$total" icon="leases"/><x-stat-card :label="__('workflow.active_contracts')" :value="$counts['active'] ?? 0" icon="units"/><x-stat-card :label="__('app.draft')" :value="$counts['draft'] ?? 0" icon="edit"/></div>
    <x-card :title="__('app.leases')" :description="__('workflow.filters_hint')">
        <form method="GET" class="grid items-end gap-4 p-5 sm:grid-cols-[2fr_1fr_auto]">
            <x-field name="q" label="workflow.attributes.contract_number" :value="$filters['q'] ?? ''" :required="false" maxlength="150"/>
            <x-select name="status" :label="__('app.status')" :options="collect(['draft','active','expired','terminated'])->mapWithKeys(fn ($s) => [$s => __('app.'.$s)])" :value="$filters['status'] ?? ''" :placeholder="__('property.all_statuses')"/>
            <div class="flex gap-2"><button class="btn-primary">{{ __('property.apply_filters') }}</button><a href="{{ route('leases.index') }}" class="btn-secondary">{{ __('property.clear_filters') }}</a></div>
        </form>
        @if($leases->isEmpty())<x-empty-state :title="__('workflow.no_results')" :description="__('workflow.no_results_hint')" icon="leases"/>
        @else
        <x-data-table :caption="__('app.leases')" :headings="[__('app.contract_number'), __('workflow.attributes.tenant_id'), __('workflow.attributes.unit_id'), __('app.start_date'), __('app.end_date'), __('app.monthly_rent'), __('app.status'), __('app.actions')]">
            @foreach($leases as $lease)<tr><td><a class="text-link" href="{{ route('leases.show', $lease) }}">{{ $lease->contract_number }}</a></td><td>{{ $lease->tenant?->full_name }}</td><td><span class="font-medium text-slate-900">{{ $lease->unit?->unit_number }}</span><span class="mt-1 block text-xs text-slate-400">{{ $lease->unit?->building?->name }}</span></td><td><x-data-value :value="$lease->start_date" column="start_date"/></td><td><x-data-value :value="$lease->end_date" column="end_date"/></td><td><x-data-value :value="$lease->monthly_rent" column="monthly_rent"/></td><td><x-badge :value="$lease->status"/></td><td><x-record-actions :record="$lease" module="leases" :label="$lease->contract_number"/></td></tr>@endforeach
        </x-data-table><div class="p-5">{{ $leases->links('components.pagination') }}</div>@endif
    </x-card>
</x-app-layout>
