<?php

namespace App\Support;

use App\Models\Announcement;
use App\Models\Building;
use App\Models\Document;
use App\Models\LeaseContract;
use App\Models\MaintenanceRequest;
use App\Models\ManagementCompany;
use App\Models\RentPayment;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;

class Navigation
{
    public const MODULES = [
        'companies' => ['model' => ManagementCompany::class, 'columns' => ['name', 'email', 'phone', 'status']],
        'buildings' => ['model' => Building::class, 'columns' => ['name', 'code', 'city', 'total_floors', 'status']],
        'units' => ['model' => Unit::class, 'columns' => ['unit_number', 'floor', 'type', 'area', 'rent_amount', 'status']],
        'tenants' => ['model' => Tenant::class, 'columns' => ['full_name', 'email', 'phone']],
        'leases' => ['model' => LeaseContract::class, 'columns' => ['contract_number', 'start_date', 'end_date', 'monthly_rent', 'status']],
        'payments' => ['model' => RentPayment::class, 'columns' => ['id', 'amount', 'due_date', 'paid_at', 'status']],
        'maintenance' => ['model' => MaintenanceRequest::class, 'columns' => ['title', 'priority', 'preferred_date', 'status']],
        'announcements' => ['model' => Announcement::class, 'columns' => ['title', 'target', 'published_at', 'status']],
        'documents' => ['model' => Document::class, 'columns' => ['title', 'document_type', 'created_at']],
    ];

    public static function forUser(User $user): array
    {
        return collect([...array_keys(self::MODULES), 'reports', 'settings'])
            ->filter(fn (string $module) => $user->can($module.'.view'))
            ->map(fn (string $module) => ['label' => __('app.'.$module), 'route' => $module.'.index', 'icon' => $module])
            ->values()->all();
    }
}
