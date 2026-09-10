<?php

namespace Database\Seeders;

use App\Models\LeaseContract;
use App\Models\ManagementCompany;
use App\Models\Tenant;
use App\Models\Unit;
use App\Support\Tenancy;
use Illuminate\Database\Seeder;

class LeaseContractSeeder extends Seeder
{
    public function run(): void
    {
        app(Tenancy::class)->runWithoutScope(function () {
            $company = ManagementCompany::where('slug', 'demo')->firstOrFail();
            $units = Unit::where('company_id', $company->id)->orderBy('id')->take(3)->get();
            foreach (Tenant::where('company_id', $company->id)->orderBy('id')->get() as $index => $tenant) {
                $unit = $units[$index];
                $start = today()->startOfMonth()->subMonths(2);
                LeaseContract::firstOrCreate(['company_id' => $company->id, 'contract_number' => 'RF-DEMO-00'.($index + 1)], ['tenant_id' => $tenant->id, 'unit_id' => $unit->id, 'start_date' => $start, 'end_date' => $start->copy()->addYear()->subDay(), 'monthly_rent' => $unit->rent_amount, 'security_deposit' => $unit->rent_amount, 'payment_due_day' => 1, 'status' => 'active']);
                $unit->update(['status' => 'occupied']);
            }
        });
    }
}
