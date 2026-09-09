<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveLeaseContractRequest;
use App\Http\Requests\WorkflowFilterRequest;
use App\Models\LeaseContract;
use App\Models\Tenant;
use App\Models\Unit;
use App\Services\LeaseContractService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class LeaseContractController extends Controller
{
    public function __construct(private LeaseContractService $leases) {}

    public function index(WorkflowFilterRequest $request): View
    {
        Gate::authorize('viewAny', LeaseContract::class);
        $filters = $request->validated();
        $search = $filters['q'] ?? '';
        $query = LeaseContract::with(['tenant', 'unit.building'])
            ->when($search !== '', fn ($query) => $query->where('contract_number', 'like', '%'.$search.'%'))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status));

        return view('leases.index', [
            'leases' => (clone $query)->latest('id')->paginate(10)->withQueryString(), 'filters' => $filters,
            'total' => LeaseContract::count(),
            'counts' => LeaseContract::selectRaw('status, COUNT(*) AS aggregate')->groupBy('status')->pluck('aggregate', 'status'),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', LeaseContract::class);

        return $this->form(new LeaseContract(['status' => 'draft', 'start_date' => today(), 'end_date' => today()->addYear()->subDay(), 'payment_due_day' => 5, 'security_deposit' => 0]));
    }

    public function store(SaveLeaseContractRequest $request): RedirectResponse
    {
        $lease = $this->leases->save($request->validated(), new LeaseContract);

        return to_route('leases.show', $lease)->with('success', __('workflow.lease_saved'));
    }

    public function show(LeaseContract $lease): View
    {
        Gate::authorize('view', $lease);
        $lease->load(['tenant', 'unit.building']);

        return view('leases.show', ['lease' => $lease,
            'payments' => auth()->user()->can('payments.view') ? $lease->payments()->with(['tenant', 'unit.building', 'leaseContract'])->orderBy('due_date')->paginate(12)->withQueryString() : null,
            'documents' => auth()->user()->can('documents.view') ? $lease->documents()->latest()->get() : collect(),
        ]);
    }

    public function edit(LeaseContract $lease): View
    {
        Gate::authorize('update', $lease);

        return $this->form($lease);
    }

    public function update(SaveLeaseContractRequest $request, LeaseContract $lease): RedirectResponse
    {
        $lease = $this->leases->save($request->validated(), $lease);

        return to_route('leases.show', $lease)->with('success', __('workflow.lease_saved'));
    }

    public function destroy(LeaseContract $lease): RedirectResponse
    {
        $this->leases->delete($lease);

        return to_route('leases.index')->with('success', __('workflow.lease_deleted'));
    }

    private function form(LeaseContract $lease): View
    {
        $units = Unit::with('building')->where(function ($query) use ($lease) {
            $query->whereIn('status', ['vacant', 'reserved']);
            if ($lease->exists) {
                $query->orWhere('id', $lease->unit_id);
            }
        })->when($lease->exists, fn ($query) => $query->where('company_id', $lease->company_id))->orderBy('building_id')->orderBy('unit_number')->get();
        $statuses = match ($lease->status) {
            'active' => ['active', 'expired', 'terminated'],
            'expired', 'terminated' => [$lease->status],
            default => ['draft', 'active'],
        };

        return view('leases.form', [
            'lease' => $lease, 'locked' => $lease->exists && $lease->status !== 'draft',
            'tenants' => Tenant::when($lease->exists, fn ($query) => $query->where('company_id', $lease->company_id))->orderBy('full_name')->pluck('full_name', 'id'),
            'units' => $units->mapWithKeys(fn (Unit $unit) => [$unit->id => $unit->building?->name.' · '.$unit->unit_number]),
            'statuses' => array_combine($statuses, array_map(fn ($status) => __('app.'.$status), $statuses)),
        ]);
    }
}
