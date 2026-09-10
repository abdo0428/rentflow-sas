<?php

namespace App\Providers;

use App\Models\Announcement;
use App\Models\Building;
use App\Models\LeaseContract;
use App\Models\MaintenanceRequest;
use App\Models\ManagementCompany;
use App\Models\RentPayment;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use App\Support\Navigation;
use App\Support\Tenancy;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(Tenancy::class);
    }

    public function boot(): void
    {
        Gate::define('tenantPortal', fn (User $user): bool => $user->hasRole('tenant') && $user->hasActiveWorkspace());
        Relation::enforceMorphMap(['user' => User::class, 'announcement' => Announcement::class, 'company' => ManagementCompany::class, 'building' => Building::class, 'unit' => Unit::class, 'tenant' => Tenant::class, 'lease' => LeaseContract::class, 'payment' => RentPayment::class, 'maintenance' => MaintenanceRequest::class]);
        View::composer(['layouts.app', 'layouts.tenant'], function ($view) {
            $view->with('navigation', Navigation::forUser(auth()->user()));
            $view->with('currentRole', auth()->user()->getRoleNames()->first() ?? 'tenant');
            $view->with('unreadNotificationsCount', auth()->user()->unreadNotifications()->count());
            if (auth()->user()->hasRole('tenant')) {
                $items = collect([
                    ['home', 'dashboard', 'dashboard'], ['my_unit', 'tenant.unit', 'units'],
                    ['my_lease', 'tenant.lease', 'leases'], ['my_payments', 'payments.index', 'payments'],
                    ['maintenance', 'maintenance.index', 'maintenance'], ['announcements', 'tenant.announcements', 'announcements'],
                    ['documents', 'tenant.documents', 'documents'], ['profile', 'profile.edit', 'profile'],
                ])->map(fn (array $item) => ['label' => $item[0], 'route' => $item[1], 'icon' => $item[2], 'active' => request()->routeIs($item[1], str_replace('.index', '.*', $item[1]))]);
                $view->with('tenantNavigation', $items);
                $view->with('tenantMobileNavigation', $items->whereIn('label', ['home', 'my_unit', 'my_payments', 'maintenance']));
            }
        });
    }
}
