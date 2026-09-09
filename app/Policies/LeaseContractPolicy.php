<?php

namespace App\Policies;

class LeaseContractPolicy extends CompanyResourcePolicy
{
    protected string $permission = 'leases';
}
