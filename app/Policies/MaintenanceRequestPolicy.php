<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class MaintenanceRequestPolicy extends CompanyResourcePolicy
{
    protected string $permission = 'maintenance';

    public function assign(User $user, Model $record): bool
    {
        return $user->hasAnyRole(['super_admin', 'company_admin', 'property_manager']) && parent::assign($user, $record);
    }

    public function note(User $user, Model $record): bool
    {
        return ! $user->hasRole('tenant') && parent::update($user, $record);
    }
}
