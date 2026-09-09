<?php

namespace App\Console\Commands;

use App\Services\RentPaymentService;
use App\Support\Tenancy;
use Illuminate\Console\Command;

class UpdateRentPayments extends Command
{
    protected $signature = 'payments:update-overdue';

    protected $description = 'Mark past-due payments overdue and send upcoming/overdue in-app reminders';

    public function handle(Tenancy $tenancy, RentPaymentService $payments): int
    {
        $counts = $tenancy->runWithoutScope(fn () => $payments->updateDuePayments());
        $this->info(__('workflow.payments_updated', $counts));

        return self::SUCCESS;
    }
}
