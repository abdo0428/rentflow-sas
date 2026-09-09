<?php

namespace App\Http\Controllers;

use App\Http\Requests\MarkPaymentPaidRequest;
use App\Http\Requests\WorkflowFilterRequest;
use App\Models\Building;
use App\Models\RentPayment;
use App\Models\Tenant;
use App\Services\RentPaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class RentPaymentController extends Controller
{
    public function index(WorkflowFilterRequest $request): View
    {
        return $this->listing($request);
    }

    public function overdue(WorkflowFilterRequest $request): View
    {
        return $this->listing($request, true);
    }

    public function show(RentPayment $payment): View
    {
        Gate::authorize('view', $payment);
        $payment->load(['tenant', 'unit.building', 'leaseContract']);

        return view('payments.show', compact('payment'));
    }

    public function paid(MarkPaymentPaidRequest $request, RentPayment $payment, RentPaymentService $payments): RedirectResponse
    {
        $payments->markPaid($payment, $request->validated());

        return to_route('payments.show', $payment)->with('success', __('workflow.payment_saved'));
    }

    private function listing(WorkflowFilterRequest $request, bool $overdueOnly = false): View
    {
        Gate::authorize('viewAny', RentPayment::class);
        $filters = $request->validated();
        if ($overdueOnly) {
            $filters['status'] = 'overdue';
        }
        $query = RentPayment::with(['tenant', 'unit.building', 'leaseContract'])
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['tenant_id'] ?? null, fn ($query, $id) => $query->where('tenant_id', $id))
            ->when($filters['building_id'] ?? null, fn ($query, $id) => $query->whereHas('unit', fn ($query) => $query->where('building_id', $id)))
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('due_date', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('due_date', '<=', $date));
        $totals = (clone $query)->selectRaw('status, SUM(amount) AS aggregate')->groupBy('status')->pluck('aggregate', 'status');

        return view('payments.index', [
            'payments' => $query->orderBy('due_date')->orderBy('id')->paginate(12)->withQueryString(),
            'filters' => $filters, 'overdueOnly' => $overdueOnly, 'totals' => $totals,
            'outstanding' => ($totals['pending'] ?? 0) + ($totals['overdue'] ?? 0),
            'tenants' => Tenant::orderBy('full_name')->pluck('full_name', 'id'),
            'buildings' => Building::orderBy('name')->pluck('name', 'id'),
        ]);
    }
}
