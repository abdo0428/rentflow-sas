<?php

namespace App\Http\Controllers;

use App\Models\LeaseContract;
use App\Models\ManagementCompany;
use App\Models\RentPayment;
use App\Services\PdfDocumentService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class PdfDocumentController extends Controller
{
    public function contract(LeaseContract $lease, PdfDocumentService $pdf): Response
    {
        Gate::authorize('view', $lease);
        $lease->load(['tenant', 'unit.building']);
        $company = ManagementCompany::withoutGlobalScopes()->whereKey($lease->company_id)->firstOrFail();

        return $this->download($pdf->render([
            'title' => __('portal.contract_summary'), 'reference' => $lease->contract_number, 'company' => $company,
            'fields' => [
                'tenant' => $lease->tenant?->full_name, 'building' => $lease->unit?->building?->name, 'unit' => $lease->unit?->unit_number,
                'start_date' => $lease->start_date->format('Y-m-d'), 'end_date' => $lease->end_date->format('Y-m-d'),
                'monthly_rent' => number_format($lease->monthly_rent, 2).' '.__('app.sar'),
                'deposit' => number_format($lease->security_deposit, 2).' '.__('app.sar'),
                'status' => __('app.'.$lease->status),
            ],
            'terms' => __('portal.simple_terms'),
        ]), 'contract-'.$lease->id.'-'.app()->getLocale().'.pdf');
    }

    public function receipt(RentPayment $payment, PdfDocumentService $pdf): Response
    {
        Gate::authorize('view', $payment);
        abort_unless($payment->status === 'paid' && $payment->paid_at, 404);
        $payment->load(['tenant', 'unit.building']);
        $company = ManagementCompany::withoutGlobalScopes()->whereKey($payment->company_id)->firstOrFail();
        $reference = 'RF-'.str_pad((string) $payment->id, 8, '0', STR_PAD_LEFT);

        return $this->download($pdf->render([
            'title' => __('portal.payment_receipt'), 'reference' => $reference, 'company' => $company,
            'fields' => [
                'receipt_number' => $reference, 'tenant' => $payment->tenant?->full_name,
                'building' => $payment->unit?->building?->name, 'unit' => $payment->unit?->unit_number,
                'amount' => number_format($payment->amount, 2).' '.__('app.sar'),
                'paid_at' => $payment->paid_at->format('Y-m-d H:i'),
                'payment_method' => $payment->payment_method ? __('workflow.'.$payment->payment_method) : __('app.not_available'),
            ],
            'terms' => null,
        ]), 'receipt-'.$payment->id.'-'.app()->getLocale().'.pdf');
    }

    private function download(string $contents, string $filename): Response
    {
        return response($contents, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'private, no-store, max-age=0', 'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
