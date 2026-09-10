<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Document;
use App\Models\LeaseContract;
use App\Services\TenantDashboardService;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class TenantPortalController extends Controller
{
    public function unit(TenantDashboardService $dashboard): View
    {
        Gate::authorize('tenantPortal');
        $lease = $dashboard->currentLease();
        if ($lease?->unit) {
            Gate::authorize('view', $lease->unit);
        }

        return view('tenant.unit', ['unit' => $lease?->unit]);
    }

    public function lease(TenantDashboardService $dashboard): View
    {
        Gate::authorize('tenantPortal');
        Gate::authorize('viewAny', LeaseContract::class);
        $lease = $dashboard->currentLease() ?? LeaseContract::with(['tenant', 'unit.building'])->latest('end_date')->first();

        return view('tenant.lease', compact('lease'));
    }

    public function announcements(): View
    {
        Gate::authorize('tenantPortal');
        Gate::authorize('viewAny', Announcement::class);

        return view('tenant.announcements', ['announcements' => Announcement::latest('published_at')->paginate(9)]);
    }

    public function documents(): View
    {
        Gate::authorize('tenantPortal');
        Gate::authorize('viewAny', Document::class);

        return view('tenant.documents', [
            'documents' => Document::latest('id')->paginate(10),
            'leases' => LeaseContract::latest('start_date')->paginate(5, ['*'], 'leases_page'),
        ]);
    }
}
