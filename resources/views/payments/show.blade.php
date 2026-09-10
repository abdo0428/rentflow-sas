<x-app-layout>
    <x-slot name="breadcrumbs"><x-breadcrumbs :items="[__('app.payments') => route('payments.index'), __('workflow.payment_reference', ['id' => $payment->id]) => null]"/></x-slot>
    <x-page-header :title="__('workflow.payment_reference', ['id' => $payment->id])" :description="$payment->tenant?->full_name" icon="payments"><x-slot name="actions">@if($payment->status === 'paid' && $payment->paid_at)<a href="{{ route('payments.receipt', $payment) }}" class="btn-secondary">{{ __('portal.download_receipt') }}</a>@endif<x-badge :value="$payment->status"/></x-slot></x-page-header>
    <div class="grid items-start gap-6 xl:grid-cols-[3fr_2fr]">
        <div class="space-y-6">
            <div class="rounded-2xl bg-slate-950 p-7 text-white"><p class="text-xs font-medium text-emerald-300">{{ __('app.amount') }}</p><p class="mt-3 text-4xl font-semibold tabular-nums">{{ number_format($payment->amount, 2) }} <span class="text-lg font-normal text-slate-400">{{ __('app.sar') }}</span></p><p class="mt-5 text-sm text-slate-400">{{ $payment->unit?->building?->name }} · {{ $payment->unit?->unit_number }}</p></div>
            <x-card :title="__('workflow.payment_details')"><x-details-list :record="$payment" :fields="['due_date','paid_at','payment_method','status']" translation="workflow.attributes"/><div class="border-t border-slate-100 px-6 py-4">@if($payment->leaseContract)<a class="text-link" href="{{ route('leases.show', $payment->leaseContract) }}">{{ $payment->leaseContract->contract_number }}</a>@endif</div></x-card>
        </div>
        @can('update', $payment)
            @if(in_array($payment->status, ['pending','overdue']))
            <x-card :title="__('workflow.register_payment')" :description="__('workflow.register_hint')">
                <form method="POST" action="{{ route('payments.paid', $payment) }}" class="space-y-5 p-6" x-data="{ submitting: false, confirmed: false }" @submit.prevent="if (confirmed) { submitting = true; $el.submit() } else { $dispatch('open-modal', 'confirm-payment') }">
                    @csrf @method('PATCH')
                    <x-field name="paid_at" label="workflow.attributes.paid_at" type="datetime-local" :value="now()->format('Y-m-d\TH:i')" :max="now()->format('Y-m-d\TH:i')"/>
                    <x-select name="payment_method" :label="__('workflow.attributes.payment_method')" :options="collect(['cash','bank_transfer','card'])->mapWithKeys(fn ($s) => [$s => __('workflow.'.$s)])" required/>
                    <x-submit>{{ __('workflow.mark_paid') }}</x-submit>
                    <x-modal name="confirm-payment" labelled-by="confirm-payment-title" focusable>
                        <div class="p-6"><h2 id="confirm-payment-title" class="text-lg font-semibold text-slate-900">{{ __('workflow.payment_confirm_title') }}</h2><p class="muted mt-3">{{ __('workflow.payment_confirm_hint') }}</p><p class="mt-4 text-2xl font-semibold tabular-nums">{{ number_format($payment->amount, 2) }} {{ __('app.sar') }}</p><div class="mt-6 flex flex-wrap justify-end gap-3"><button type="button" class="btn-secondary" @click="$dispatch('close-modal', 'confirm-payment')">{{ __('app.cancel') }}</button><button type="button" class="btn-primary" :disabled="submitting" @click="confirmed = true; $el.closest('form').requestSubmit()">{{ __('workflow.confirm_payment') }}</button></div></div>
                    </x-modal>
                </form>
            </x-card>
            @endif
        @endcan
    </div>
</x-app-layout>
