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
        Relation::enforceMorphMap(['user' => User::class, 'announcement' => Announcement::class, 'company' => ManagementCompany::class, 'building' => Building::class, 'unit' => Unit::class, 'tenant' => Tenant::class, 'lease' => LeaseContract::class, 'payment' => RentPayment::class, 'maintenance' => MaintenanceRequest::class]);
        View::composer('layouts.app', function ($view) {
            $view->with('navigation', Navigation::forUser(auth()->user()));
            $view->with('currentRole', auth()->user()->getRoleNames()->first() ?? 'tenant');
            $view->with('unreadNotificationsCount', auth()->user()->unreadNotifications()->count());
        });
    }
}
