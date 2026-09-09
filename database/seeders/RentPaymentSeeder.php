<?php

namespace Database\Seeders;

use App\Models\LeaseContract;
use App\Models\ManagementCompany;
use App\Models\RentPayment;
use App\Support\Tenancy;
use Illuminate\Database\Seeder;

class RentPaymentSeeder extends Seeder
{
    public function run(): void
    {
        app(Tenancy::class)->runWithoutScope(function () {
            foreach (LeaseContract::where('company_id', ManagementCompany::where('slug', 'demo')->firstOrFail()->id)->get() as $lease) {
                foreach ([-1 => 'paid', 0 => 'overdue', 1 => 'pending'] as $month => $status) {
                    RentPayment::firstOrCreate(['lease_contract_id' => $lease->id, 'due_date' => today()->startOfMonth()->addMonths($month)->toDateString()], ['company_id' => $lease->company_id, 'tenant_id' => $lease->tenant_id, 'unit_id' => $lease->unit_id, 'amount' => $lease->monthly_rent, 'status' => $status, 'paid_at' => $status === 'paid' ? today()->startOfMonth()->subMonth() : null, 'payment_method' => $status === 'paid' ? 'bank_transfer' : null]);
                }
            }
        });
    }
}
