<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Building;
use App\Models\LeaseContract;
use App\Models\MaintenanceRequest;
use App\Models\ManagementCompany;
use App\Models\RentPayment;
use App\Models\Tenant;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $metrics = [];
        foreach ([
            'companies' => [ManagementCompany::class, 'companies'],
            'buildings' => [Building::class, 'buildings'],
            'units' => [Unit::class, 'units'],
            'tenants' => [Tenant::class, 'tenants'],
            'leases' => [LeaseContract::class, 'leases'],
        ] as $permission => [$model, $label]) {
            if ($user->can($permission.'.view')) {
                $metrics[] = ['label' => $label, 'value' => $model::count(), 'icon' => $permission, 'route' => $permission.'.index'];
            }
        }
        if ($user->can('payments.view')) {
            $metrics[] = ['label' => 'pending_rent', 'value' => number_format((float) RentPayment::whereIn('status', ['pending', 'overdue'])->sum('amount'), 2).' '.__('app.sar'), 'icon' => 'payments', 'route' => 'payments.index'];
        }
        if ($user->can('maintenance.view')) {
            $metrics[] = ['label' => 'open_requests', 'value' => MaintenanceRequest::whereNotIn('status', ['completed', 'cancelled', 'rejected'])->count(), 'icon' => 'maintenance', 'route' => 'maintenance.index'];
        }

        return view('dashboard', [
            'metrics' => $metrics,
            'payments' => $user->can('payments.view') ? RentPayment::orderBy('due_date')->limit(5)->get() : collect(),
            'requests' => $user->can('maintenance.view') ? MaintenanceRequest::latest()->limit(4)->get() : collect(),
            'announcements' => $user->can('announcements.view') ? Announcement::where('status', 'published')->where('published_at', '<=', now())->latest('published_at')->limit(3)->get() : collect(),
            'role' => $user->getRoleNames()->first() ?? 'tenant',
        ]);
    }
}
