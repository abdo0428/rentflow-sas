<?php

namespace App\Policies;

class RentPaymentPolicy extends CompanyResourcePolicy
{
    protected string $permission = 'payments';
}
