<x-app-layout>
    <x-slot name="header">{{ $overdueOnly ? __('workflow.overdue_payments') : __('app.payments') }}</x-slot>
    <x-page-header :title="$overdueOnly ? __('workflow.overdue_payments') : __('app.payments')" :description="__('workflow.payments_intro')" icon="payments"><x-slot name="actions"><a class="btn-secondary" href="{{ route($overdueOnly ? 'payments.index' : 'payments.overdue') }}">{{ $overdueOnly ? __('workflow.all_payments') : __('workflow.overdue_payments') }}</a></x-slot></x-page-header>
    <div class="grid gap-4 sm:grid-cols-3"><x-stat-card :label="__('workflow.collected')" :value="number_format($totals['paid'] ?? 0, 2)" :hint="__('app.sar')" icon="payments"/><x-stat-card :label="__('workflow.outstanding')" :value="number_format($outstanding, 2)" :hint="__('app.sar')" icon="leases"/><x-stat-card :label="__('workflow.overdue_payments')" :value="number_format($totals['overdue'] ?? 0, 2)" :hint="__('app.sar')" icon="bell"/></div>
    <x-card :title="__('workflow.collection_summary')" :description="__('workflow.filter_summary')">
        <form method="GET" class="grid items-end gap-4 p-5 sm:grid-cols-2 xl:grid-cols-3">
            <x-select name="status" :label="__('app.status')" :options="collect($overdueOnly ? ['overdue'] : ['pending','paid','overdue','cancelled'])->mapWithKeys(fn ($s) => [$s => __('app.'.$s)])" :value="$filters['status'] ?? ''" :placeholder="__('property.all_statuses')"/>
            <x-select name="tenant_id" :label="__('workflow.attributes.tenant_id')" :options="$tenants" :value="$filters['tenant_id'] ?? ''" :placeholder="__('property.select_option')"/>
            <x-select name="building_id" :label="__('workflow.attributes.building_id')" :options="$buildings" :value="$filters['building_id'] ?? ''" :placeholder="__('property.all_buildings')"/>
            <x-field name="date_from" label="workflow.attributes.date_from" type="date" :value="$filters['date_from'] ?? ''" :required="false"/><x-field name="date_to" label="workflow.attributes.date_to" type="date" :value="$filters['date_to'] ?? ''" :required="false"/>
            <div class="flex gap-2"><button class="btn-primary">{{ __('property.apply_filters') }}</button><a class="btn-secondary" href="{{ route($overdueOnly ? 'payments.overdue' : 'payments.index') }}">{{ __('property.clear_filters') }}</a></div>
        </form>
        <x-payment-table :payments="$payments"/>
    </x-card>
</x-app-layout>
