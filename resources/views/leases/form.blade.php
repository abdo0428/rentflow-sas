<x-app-layout>
    <x-slot name="breadcrumbs"><x-breadcrumbs :items="[__('app.leases') => route('leases.index'), ($lease->exists ? __('workflow.edit_lease') : __('workflow.create_lease')) => null]"/></x-slot>
    <x-page-header :title="$lease->exists ? __('workflow.edit_lease') : __('workflow.create_lease')" :description="__('workflow.leases_intro')" icon="leases"/>
    @if($locked)<div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-900">{{ __('workflow.lease_terms_locked') }}</div>@endif
    <form action="{{ $lease->exists ? route('leases.update', $lease) : route('leases.store') }}" method="POST" class="space-y-6" x-data="{ submitting: false }" @submit="submitting = true">
        @csrf @if($lease->exists) @method('PUT') @endif
        <x-form-section :title="__('workflow.lease_terms')" :description="__('workflow.lease_terms_hint')" icon="leases">
            <x-field name="contract_number" label="workflow.attributes.contract_number" :value="$lease->contract_number" maxlength="100" autofocus/>
            <x-select name="status" :label="__('app.status')" :options="$statuses" :value="$lease->status" required/>
            <x-select name="tenant_id" :label="__('workflow.attributes.tenant_id')" :options="$tenants" :value="$lease->tenant_id" :disabled="$locked" required/>
            <x-select name="unit_id" :label="__('workflow.attributes.unit_id')" :options="$units" :value="$lease->unit_id" :disabled="$locked" required/>
            @if($locked)<input type="hidden" name="tenant_id" value="{{ $lease->tenant_id }}"><input type="hidden" name="unit_id" value="{{ $lease->unit_id }}">@endif
            <x-field name="start_date" label="workflow.attributes.start_date" type="date" :value="$lease->start_date?->format('Y-m-d')" :readonly="$locked"/>
            <x-field name="end_date" label="workflow.attributes.end_date" type="date" :value="$lease->end_date?->format('Y-m-d')" :readonly="$locked"/>
        </x-form-section>
        <x-form-section :title="__('workflow.lease_schedule')" :description="__('workflow.lease_schedule_hint')" icon="payments">
            <x-field name="monthly_rent" label="workflow.attributes.monthly_rent" type="number" min="0.01" step="0.01" max="9999999999.99" :value="$lease->monthly_rent" :readonly="$locked"/>
            <x-field name="security_deposit" label="workflow.attributes.security_deposit" type="number" min="0" step="0.01" max="9999999999.99" :value="$lease->security_deposit" :readonly="$locked"/>
            <x-field name="payment_due_day" label="workflow.attributes.payment_due_day" type="number" min="1" max="31" :value="$lease->payment_due_day" :readonly="$locked"/>
        </x-form-section>
        <x-form-footer :cancel="route('leases.index')" :label="__('app.save')"/>
    </form>
</x-app-layout>
