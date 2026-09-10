<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}"><head><meta charset="utf-8"><style>
body { font-family: dejavusans; font-size: 11pt; color: #334155; line-height: 1.7; }
h1 { font-size: 23pt; color: #0f172a; margin: 8px 0 12px; } h2 { font-size: 13pt; color: #047857; }
.brand { color: #047857; font-size: 16pt; font-weight: bold; } .muted { color: #64748b; font-size: 9pt; }
.company { background: #f1f5f9; padding: 16px; margin: 24px 0; }
table { width: 100%; border-collapse: collapse; table-layout: fixed; } td { padding: 11px; border-bottom: 1px solid #e2e8f0; overflow-wrap: break-word; }
.label { color: #64748b; width: 35%; } .terms { margin-top: 24px; font-size: 10pt; } .footer { margin-top: 32px; border-top: 2px solid #047857; padding-top: 12px; }
</style></head><body>
<div class="brand">{{ __('app.brand') }}</div><h1>{{ $title }}</h1><p class="muted">{{ $reference }}</p>
<div class="company"><h2>{{ $company->name }}</h2><p>{{ $company->address }}</p><p class="muted">{{ $company->email }} · {{ $company->phone }}</p></div>
<table>@foreach($fields as $label => $value)<tr><td class="label">{{ __('portal.'.$label) }}</td><td>@if(in_array($label, ['start_date','end_date','paid_at','receipt_number']))<span dir="ltr">{{ $value }}</span>@else{{ $value ?? __('app.not_available') }}@endif</td></tr>@endforeach</table>
@if($terms)<div class="terms"><h2>{{ __('portal.terms') }}</h2><p>{{ $terms }}</p></div>@endif
<div class="footer muted">{{ __('portal.summary_note') }}<br>{{ __('portal.generated_at') }}: <span dir="ltr">{{ now()->format('Y-m-d H:i') }}</span></div>
</body></html>
