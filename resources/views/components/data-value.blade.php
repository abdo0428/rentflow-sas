@props(['value', 'column'])
@if($value === null)
    <span class="text-slate-400">{{ __('app.not_available') }}</span>
@elseif(in_array($column, ['status', 'priority']))
    <x-badge :value="$value"/>
@elseif($column === 'payment_method')
    {{ __('workflow.'.$value) }}
@elseif(in_array($column, ['type', 'target', 'document_type']))
    {{ __('app.'.$value) }}
@elseif($value instanceof \Carbon\CarbonInterface)
    <span dir="ltr">{{ $value->format(in_array($column, ['paid_at', 'completed_at']) ? 'Y-m-d H:i' : 'Y-m-d') }}</span>
@elseif(in_array($column, ['amount', 'rent_amount', 'monthly_rent', 'security_deposit']))
    <span class="font-medium tabular-nums">{{ number_format((float) $value, 2) }} <span class="text-xs text-slate-400">{{ __('app.sar') }}</span></span>
@else
    {{ $value }}
@endif
