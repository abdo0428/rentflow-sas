<?php

namespace App\Policies;

use App\Models\ManagementCompany;
use App\Models\User;
use App\Support\Tenancy;
use Illuminate\Database\Eloquent\Model;

abstract class CompanyResourcePolicy
{
    protected string $permission;

    public function viewAny(User $user): bool
    {
        return $user->hasActiveWorkspace() && $user->can($this->permission.'.view');
    }

    public function view(User $user, Model $record): bool
    {
        return $this->allowed($user, $record, 'view');
    }

    public function create(User $user): bool
    {
        return $user->hasActiveWorkspace() && $user->can($this->permission.'.create')
            && ($user->hasRole('super_admin') || (bool) $user->company_id);
    }

    public function update(User $user, Model $record): bool
    {
        return $this->allowed($user, $record, 'update');
    }

    public function delete(User $user, Model $record): bool
    {
        return $this->allowed($user, $record, 'delete');
    }

    public function assign(User $user, Model $record): bool
    {
        return $this->allowed($user, $record, 'assign');
    }

    private function allowed(User $user, Model $record, string $action): bool
    {
        if (! $user->hasActiveWorkspace() || ! $user->can($this->permission.'.'.$action)) {
            return false;
        }
        if (! $user->hasRole('super_admin') && (int) ($record instanceof ManagementCompany ? $record->id : $record->company_id) !== (int) $user->company_id) {
            return false;
        }

        return app(Tenancy::class)->runFor($user, fn () => $record->newQuery()->whereKey($record->getKey())->exists());
    }
}
