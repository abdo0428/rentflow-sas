<?php

namespace App\Policies;

class TenantPolicy extends CompanyResourcePolicy
{
    protected string $permission = 'tenants';
}
