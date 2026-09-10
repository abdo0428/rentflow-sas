<?php

namespace Database\Seeders;

use App\Models\LeaseContract;
use App\Models\ManagementCompany;
use App\Services\RentPaymentService;
use App\Support\Tenancy;
use Illuminate\Database\Seeder;

class RentPaymentSeeder extends Seeder
{
    public function run(): void
    {
        app(Tenancy::class)->runWithoutScope(function () {
            foreach (LeaseContract::where('company_id', ManagementCompany::where('slug', 'demo')->firstOrFail()->id)->get() as $lease) {
                $existingIds = $lease->payments()->pluck('id');
                app(RentPaymentService::class)->generate($lease);
                foreach ($lease->payments()->whereNotIn('id', $existingIds)->get() as $payment) {
                    if ($payment->due_date->lt(today()->startOfMonth()->subMonth())) {
                        $payment->update(['status' => 'paid', 'paid_at' => today()->startOfMonth(), 'payment_method' => 'bank_transfer']);
                    } elseif ($payment->due_date->isBefore(today())) {
                        $payment->update(['status' => 'overdue']);
                    }
                }
            }
        });
    }
}
