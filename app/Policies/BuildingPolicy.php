<?php

namespace App\Policies;

class BuildingPolicy extends CompanyResourcePolicy
{
    protected string $permission = 'buildings';
}
