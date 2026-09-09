<?php

namespace App\Http\Controllers;

use App\Http\Requests\PropertyFilterRequest;
use App\Http\Requests\SaveTenantRequest;
use App\Models\Document;
use App\Models\LeaseContract;
use App\Models\MaintenanceRequest;
use App\Models\ManagementCompany;
use App\Models\RentPayment;
use App\Models\Tenant;
use App\Services\DeletePropertyRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class TenantController extends Controller
{
    public function index(PropertyFilterRequest $request): View
    {
        Gate::authorize('viewAny', Tenant::class);
        $filters = $request->validated();
        $search = $filters['q'] ?? '';
        $tenants = Tenant::query()->withCount('leases')
            ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $query) use ($search) {
                $query->where('full_name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%')->orWhere('phone', 'like', '%'.$search.'%');
            }))->latest('id')->paginate(10)->withQueryString();

        return view('tenants.index', ['tenants' => $tenants, 'filters' => $filters, 'total' => Tenant::count()]);
    }

    public function create(): View
    {
        Gate::authorize('create', Tenant::class);

        return $this->form(new Tenant);
    }

    public function store(SaveTenantRequest $request): RedirectResponse
    {
        Gate::authorize('create', Tenant::class);
        $tenant = Tenant::create([...$request->validated(), 'company_id' => $request->companyId()]);

        return to_route('tenants.show', $tenant)->with('success', __('property.tenant_created'));
    }

    public function show(PropertyFilterRequest $request, Tenant $tenant): View
    {
        Gate::authorize('view', $tenant);
        $user = $request->user();

        return view('tenants.show', [
            'tenant' => $tenant,
            'currentLease' => $user->can('leases.view') ? $tenant->leases()->with('unit.building')->where('status', 'active')
                ->whereDate('start_date', '<=', today())->whereDate('end_date', '>=', today())->latest('start_date')->first() : null,
            'leases' => $user->can('leases.view') ? $tenant->leases()->latest('start_date')->paginate(5, ['*'], 'leases_page')->withQueryString() : null,
            'payments' => $user->can('payments.view') ? $tenant->payments()->latest('due_date')->paginate(5, ['*'], 'payments_page')->withQueryString() : null,
            'maintenanceRequests' => $user->can('maintenance.view') ? $tenant->maintenanceRequests()->latest('id')->paginate(5, ['*'], 'maintenance_page')->withQueryString() : null,
            'documents' => $user->can('documents.view') ? Document::whereHasMorph('documentable', [Tenant::class, LeaseContract::class, RentPayment::class, MaintenanceRequest::class],
                fn (Builder $query, string $type) => $query->where($type === Tenant::class ? 'id' : 'tenant_id', $tenant->id))
                ->latest('id')->paginate(5, ['*'], 'documents_page')->withQueryString() : null,
            'deletionReason' => $tenant->deletionBlockReason(),
        ]);
    }

    public function edit(Tenant $tenant): View
    {
        Gate::authorize('update', $tenant);

        return $this->form($tenant);
    }

    public function update(SaveTenantRequest $request, Tenant $tenant): RedirectResponse
    {
        Gate::authorize('update', $tenant);
        $tenant->update($request->safe()->except('company_id'));

        return to_route('tenants.show', $tenant)->with('success', __('property.tenant_updated'));
    }

    public function destroy(Tenant $tenant, DeletePropertyRecord $delete): RedirectResponse
    {
        Gate::authorize('delete', $tenant);
        $delete->handle($tenant);

        return to_route('tenants.index')->with('success', __('property.tenant_deleted'));
    }

    private function form(Tenant $tenant): View
    {
        return view('tenants.form', [
            'tenant' => $tenant,
            'companies' => auth()->user()->hasRole('super_admin') && ! $tenant->exists
                ? ManagementCompany::where('status', 'active')->orderBy('name')->pluck('name', 'id') : collect(),
        ]);
    }
}
