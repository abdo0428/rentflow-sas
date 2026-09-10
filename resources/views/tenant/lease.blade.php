<x-app-layout>
    <x-page-header :title="__('portal.my_lease')" :description="__('portal.lease_intro')" icon="leases">@if($lease)<x-slot name="actions"><a href="{{ route('leases.pdf', $lease) }}" class="btn-primary"><x-icon name="documents"/>{{ __('portal.download_contract') }}</a></x-slot>
@endif</x-page-header>
    @if($lease)<x-card :title="$lease->contract_number"><x-details-list :record="$lease" :fields="['contract_number','start_date','end_date','monthly_rent','security_deposit','status']" translation="workflow.attributes"/></x-card>@else<x-card><x-empty-state :title="__('portal.no_lease')" :description="__('portal.no_lease_hint')" icon="leases"/></x-card>@endif
</x-app-layout>
