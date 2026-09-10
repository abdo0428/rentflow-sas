<?php

namespace App\Services;

use App\Models\Announcement;
use App\Models\Building;
use App\Models\LeaseContract;
use App\Models\MaintenanceRequest;
use App\Models\ManagementCompany;
use App\Models\RentPayment;
use App\Models\Unit;
use App\Models\User;
use App\Support\Tenancy;

class DashboardService
{
    public function forUser(User $user): array
    {
        abort_unless($user->hasActiveWorkspace(), 403);

        return app(Tenancy::class)->runFor($user, function () use ($user) {
            $metrics = [];
            $add = function (string $label, string|int|float $value, string $icon, ?string $route = null) use (&$metrics): void {
                $metrics[] = compact('label', 'value', 'icon', 'route');
            };
            if ($user->hasRole('super_admin')) {
                $add('companies', ManagementCompany::count(), 'companies', 'companies.index');
                $add('active_companies', ManagementCompany::where('status', 'active')->count(), 'companies', 'companies.index');
                $add('users', User::count(), 'tenants');
            }
            if ($user->can('buildings.view')) {
                $add('buildings', Building::count(), 'buildings', 'buildings.index');
            }
            if ($user->can('units.view')) {
                $counts = Unit::selectRaw('status, COUNT(*) AS aggregate')->groupBy('status')->pluck('aggregate', 'status');
                $total = $counts->sum();
                $occupied = (int) ($counts['occupied'] ?? 0);
                $add('units', $total, 'units', 'units.index');
                $add('occupied_units', $occupied, 'units', 'units.index');
                $add('vacant_units', (int) ($counts['vacant'] ?? 0), 'units', 'units.index');
                $add('occupancy', ($total ? round($occupied / $total * 100, 1) : 0).'%', 'reports', 'units.index');
            }
            if ($user->can('leases.view')) {
                $add('active_leases', LeaseContract::where('status', 'active')->count(), 'leases', 'leases.index');
                $add('ending_soon', LeaseContract::where('status', 'active')->whereBetween('end_date', [today(), today()->addDays(30)])->count(), 'leases', 'leases.index');
            }
            if ($user->can('payments.view')) {
                $month = [today()->startOfMonth(), today()->endOfMonth()];
                $money = fn ($amount) => number_format((float) $amount, 2).' '.__('app.sar');
                $add('due_this_month', $money(RentPayment::where('status', '!=', 'cancelled')->whereBetween('due_date', $month)->sum('amount')), 'payments', 'payments.index');
                $add('overdue_amount', $money(RentPayment::whereIn('status', ['pending', 'overdue'])->whereDate('due_date', '<', today())->sum('amount')), 'bell', 'payments.overdue');
                $add('revenue_this_month', $money(RentPayment::where('status', 'paid')->where('paid_at', '>=', today()->startOfMonth())->where('paid_at', '<', today()->startOfMonth()->addMonth())->sum('amount')), 'payments', 'payments.index');
            }
            $statusCounts = $user->can('maintenance.view')
                ? MaintenanceRequest::selectRaw('status, COUNT(*) AS aggregate')->groupBy('status')->pluck('aggregate', 'status') : collect();
            if ($user->can('maintenance.view')) {
                $add('open_maintenance', $statusCounts->only(['new', 'under_review', 'assigned', 'in_progress'])->sum(), 'maintenance', 'maintenance.index');
            }
            $quickActions = collect([
                ['create', Building::class, 'buildings.create', 'create_building', 'buildings'],
                ['create', Unit::class, 'units.create', 'create_unit', 'units'],
                ['create', LeaseContract::class, 'leases.create', 'create_lease', 'leases'],
                ['create', MaintenanceRequest::class, 'maintenance.create', 'create_request', 'maintenance'],
            ])->filter(fn ($action) => $user->can($action[0], $action[1]))->values();

            return [
                'metrics' => $metrics, 'role' => $user->getRoleNames()->first(), 'quickActions' => $quickActions,
                'statusCounts' => $statusCounts, 'requestTotal' => $statusCounts->sum(),
                'maintenanceBuildings' => $user->can('buildings.view') && $user->can('maintenance.view')
                    ? Building::withCount('maintenanceRequests')->having('maintenance_requests_count', '>', 0)->orderByDesc('maintenance_requests_count')->limit(5)->get() : collect(),
                'endingLeases' => $user->can('leases.view') ? LeaseContract::with(['tenant', 'unit.building'])->where('status', 'active')->whereBetween('end_date', [today(), today()->addDays(30)])->orderBy('end_date')->limit(5)->get() : collect(),
                'payments' => $user->can('payments.view') ? RentPayment::with('tenant')->whereIn('status', ['pending', 'overdue'])->orderBy('due_date')->limit(5)->get() : collect(),
                'requests' => $user->can('maintenance.view') ? MaintenanceRequest::latest()->limit(4)->get() : collect(),
                'announcements' => $user->can('announcements.view') ? Announcement::where('status', 'published')->where('published_at', '<=', now())->latest('published_at')->limit(3)->get() : collect(),
            ];
        });
    }
}
