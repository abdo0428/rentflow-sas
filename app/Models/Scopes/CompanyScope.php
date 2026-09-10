<?php

namespace App\Models\Scopes;

use App\Models\Announcement;
use App\Models\Building;
use App\Models\Document;
use App\Models\LeaseContract;
use App\Models\MaintenanceActivity;
use App\Models\MaintenanceRequest;
use App\Models\ManagementCompany;
use App\Models\RentPayment;
use App\Models\Tenant;
use App\Models\Unit;
use App\Support\Tenancy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class CompanyScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (app(Tenancy::class)->isBypassed()) {
            return;
        }
        $user = app(Tenancy::class)->user();
        if (! $user || $user->status !== 'active') {
            $builder->whereRaw('1 = 0');

            return;
        }
        if ($user->hasRole('super_admin')) {
            return;
        }
        if (! $user->company_id) {
            $builder->whereRaw('1 = 0');

            return;
        }
        $builder->where($model->qualifyColumn($model instanceof ManagementCompany ? 'id' : 'company_id'), $user->company_id);
        if ($model instanceof ManagementCompany) {
            $builder->where('status', 'active');
        } else {
            $builder->whereExists(function ($query) use ($model) {
                $query->selectRaw('1')->from('management_companies')
                    ->whereColumn('management_companies.id', $model->qualifyColumn('company_id'))
                    ->where('management_companies.status', 'active');
            });
        }
        if ($user->hasRole('maintenance_staff')) {
            if ($model instanceof MaintenanceActivity) {
                $builder->whereHas('maintenanceRequest');

                return;
            }
            $model instanceof MaintenanceRequest
                ? $builder->where('assigned_to', $user->id)
                : $builder->whereRaw('1 = 0');

            return;
        }
        if (! $user->hasRole('tenant')) {
            if (! $user->hasAnyRole(['company_admin', 'property_manager', 'accountant'])) {
                $builder->whereRaw('1 = 0');
            }

            return;
        }
        if ($model instanceof Tenant) {
            $builder->where('user_id', $user->id);
        } elseif ($model instanceof MaintenanceActivity) {
            $builder->where('kind', 'status')->whereHas('maintenanceRequest');
        } elseif ($model instanceof LeaseContract || $model instanceof RentPayment || $model instanceof MaintenanceRequest) {
            $builder->whereIn('tenant_id', Tenant::query()->select('id'));
        } elseif ($model instanceof Unit) {
            $builder->whereHas('leases');
        } elseif ($model instanceof Building) {
            $builder->whereHas('units');
        } elseif ($model instanceof Announcement) {
            $builder->where('status', 'published')->where('published_at', '<=', now())
                ->where(function (Builder $query) {
                    $query->whereIn('target', ['all', 'company'])
                        ->orWhere(function (Builder $query) {
                            $query->where('target', 'building')->whereIn('building_id', Unit::query()->select('building_id')
                                ->whereHas('leases', fn (Builder $leases) => $leases->where('status', 'active')->whereDate('start_date', '<=', today())->whereDate('end_date', '>=', today())));
                        });
                });
        } elseif ($model instanceof Document) {
            $builder->whereHasMorph('documentable', [Tenant::class, LeaseContract::class, RentPayment::class, MaintenanceRequest::class, Unit::class], function (Builder $query, string $type) {
                if ($type === Unit::class) {
                    $query->whereHas('leases', fn (Builder $leases) => $leases->where('status', 'active')->whereDate('start_date', '<=', today())->whereDate('end_date', '>=', today()));
                }
            });
        } else {
            $builder->whereRaw('1 = 0');
        }
    }
}
