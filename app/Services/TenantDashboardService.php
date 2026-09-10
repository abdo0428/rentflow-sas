<?php

namespace App\Services;

use App\Models\Announcement;
use App\Models\LeaseContract;
use App\Models\MaintenanceRequest;
use App\Models\RentPayment;
use App\Models\User;
use App\Support\Tenancy;

class TenantDashboardService
{
    public function currentLease(): ?LeaseContract
    {
        return LeaseContract::with(['unit.building', 'tenant'])->where('status', 'active')
            ->whereDate('start_date', '<=', today())->whereDate('end_date', '>=', today())
            ->latest('start_date')->latest('id')->first();
    }

    public function forUser(User $user): array
    {
        abort_unless($user->hasRole('tenant') && $user->hasActiveWorkspace(), 403);

        return app(Tenancy::class)->runFor($user, fn () => [
            'lease' => $this->currentLease(),
            'nextPayment' => RentPayment::where('status', 'pending')->whereDate('due_date', '>=', today())->orderBy('due_date')->orderBy('id')->first(),
            'overdueAmount' => RentPayment::whereIn('status', ['pending', 'overdue'])->whereDate('due_date', '<', today())->sum('amount'),
            'requests' => MaintenanceRequest::latest('id')->limit(3)->get(),
            'announcements' => Announcement::latest('published_at')->limit(3)->get(),
        ]);
    }
}
