<x-app-layout>
    <x-slot name="header">{{ __('app.maintenance') }}</x-slot>
    <x-page-header :title="__('app.maintenance')" :description="__('workflow.maintenance_intro')" icon="maintenance"><x-slot name="actions">@can('create', App\Models\MaintenanceRequest::class)<a class="btn-primary" href="{{ route('maintenance.create') }}"><x-icon name="plus"/>{{ __('workflow.create_request') }}</a>@endcan</x-slot></x-page-header>
    <div class="grid gap-4 sm:grid-cols-3"><x-stat-card :label="__('workflow.open_requests')" :value="$openCount" icon="maintenance"/><x-stat-card :label="__('workflow.awaiting_assignment')" :value="$counts['under_review'] ?? 0" icon="tenants"/><x-stat-card :label="__('workflow.completed_requests')" :value="$counts['completed'] ?? 0" icon="dashboard"/></div>
    <x-card :title="__('app.maintenance')" :description="__('workflow.filters_hint')">
        <form method="GET" class="grid items-end gap-4 p-5 sm:grid-cols-2 xl:grid-cols-5">
            <x-select name="status" :label="__('app.status')" :options="collect(array_keys(App\Services\MaintenanceWorkflowService::TRANSITIONS))->mapWithKeys(fn ($s) => [$s => __('app.'.$s)])" :value="$filters['status'] ?? ''" :placeholder="__('property.all_statuses')"/>
            <x-select name="priority" :label="__('app.priority')" :options="collect(['low','medium','high','urgent'])->mapWithKeys(fn ($s) => [$s => __('app.'.$s)])" :value="$filters['priority'] ?? ''"/>
            <x-select name="building_id" :label="__('workflow.attributes.building_id')" :options="$buildings" :value="$filters['building_id'] ?? ''" :placeholder="__('property.all_buildings')"/>
            <x-select name="assigned_to" :label="__('workflow.attributes.assigned_to')" :options="$staff" :value="$filters['assigned_to'] ?? ''"/>
            <div class="flex flex-wrap gap-2"><button class="btn-primary">{{ __('property.apply_filters') }}</button><a class="btn-secondary" href="{{ route('maintenance.index') }}">{{ __('property.clear_filters') }}</a></div>
        </form>
        @if($requests->isEmpty())<x-empty-state :title="__('workflow.no_results')" :description="__('workflow.no_results_hint')" icon="maintenance"/>
        @else<x-data-table :caption="__('app.maintenance')" :headings="[__('app.title'),__('workflow.request_location'),__('app.priority'),__('app.status'),__('workflow.attributes.assigned_to'),__('app.preferred_date'),__('app.actions')]">
            @foreach($requests as $maintenanceRequest)<tr><td><a class="text-link" href="{{ route('maintenance.show', $maintenanceRequest) }}">{{ Str::limit($maintenanceRequest->title, 55) }}</a><span class="mt-1 block text-xs text-slate-400">{{ $maintenanceRequest->created_at->format('Y-m-d') }}</span></td><td>{{ $maintenanceRequest->building_label }}<span class="mt-1 block text-xs text-slate-400">{{ $maintenanceRequest->unit_label }}</span></td><td><x-badge :value="$maintenanceRequest->priority"/></td><td><x-badge :value="$maintenanceRequest->status"/></td><td>{{ $maintenanceRequest->assignee?->name ?? __('workflow.unassigned') }}</td><td><x-data-value :value="$maintenanceRequest->preferred_date" column="preferred_date"/></td><td><a class="btn-secondary !min-h-8 !px-3 !py-1.5 !text-xs" href="{{ route('maintenance.show', $maintenanceRequest) }}">{{ __('app.view') }}</a></td></tr>@endforeach
        </x-data-table><div class="p-5">{{ $requests->links('components.pagination') }}</div>@endif
    </x-card>
</x-app-layout>
