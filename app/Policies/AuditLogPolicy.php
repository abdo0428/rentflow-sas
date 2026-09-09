<?php

namespace App\Policies;

class AuditLogPolicy extends CompanyResourcePolicy
{
    protected string $permission = 'settings';
}
