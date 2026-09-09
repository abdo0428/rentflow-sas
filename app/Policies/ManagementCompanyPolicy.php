<?php

namespace App\Policies;

class ManagementCompanyPolicy extends CompanyResourcePolicy
{
    protected string $permission = 'companies';
}
