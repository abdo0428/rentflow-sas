<?php

namespace App\Policies;

class DocumentPolicy extends CompanyResourcePolicy
{
    protected string $permission = 'documents';
}
