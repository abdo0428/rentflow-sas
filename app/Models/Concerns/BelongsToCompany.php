<?php

namespace App\Models\Concerns;

use App\Models\ManagementCompany;
use App\Models\Scopes\CompanyScope;
use App\Support\Tenancy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToCompany
{
    protected static function bootBelongsToCompany(): void
    {
        static::addGlobalScope(new CompanyScope);
        static::saving(function (Model $model) {
            if (app(Tenancy::class)->isBypassed()) {
                return;
            }
            $user = app(Tenancy::class)->user();
            if (! $user) {
                abort_unless(app()->runningInConsole(), 403);

                return;
            }
            abort_unless($user->hasActiveWorkspace(), 403);
            if ($user->hasRole('super_admin')) {
                return;
            }
            abort_if($model instanceof ManagementCompany || ! $user->company_id, 403);
            if ($model->exists) {
                abort_unless((int) $model->getOriginal('company_id') === (int) $user->company_id, 403);
                abort_if($model->isDirty('company_id'), 403);
                abort_unless($model->newQuery()->whereKey($model->getKey())->exists(), 403);
            }
            $model->company_id = $user->company_id;
        });
        static::deleting(function (Model $model) {
            if (! app(Tenancy::class)->isBypassed()) {
                abort_unless($model->newQuery()->whereKey($model->getKey())->exists(), 403);
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(ManagementCompany::class, 'company_id');
    }
}
