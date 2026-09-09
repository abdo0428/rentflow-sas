<?php

namespace App\Console\Commands;

use App\Services\LeaseContractService;
use App\Support\Tenancy;
use Illuminate\Console\Command;

class ExpireLeaseContracts extends Command
{
    protected $signature = 'leases:expire';

    protected $description = 'Expire ended active contracts and release their units';

    public function handle(Tenancy $tenancy, LeaseContractService $leases): int
    {
        $count = $tenancy->runWithoutScope(fn () => $leases->expireDue());
        $this->info(__('workflow.leases_expired', ['count' => $count]));

        return self::SUCCESS;
    }
}
