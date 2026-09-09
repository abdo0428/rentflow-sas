<?php

namespace Database\Factories;

use App\Models\LeaseContract;
use App\Models\RentPayment;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<RentPayment> */
class RentPaymentFactory extends Factory
{
    public function definition(): array
    {
        return ['lease_contract_id' => LeaseContract::factory(), 'company_id' => fn (array $attributes) => LeaseContract::withoutGlobalScopes()->findOrFail($attributes['lease_contract_id'])->company_id, 'tenant_id' => fn (array $attributes) => LeaseContract::withoutGlobalScopes()->findOrFail($attributes['lease_contract_id'])->tenant_id, 'unit_id' => fn (array $attributes) => LeaseContract::withoutGlobalScopes()->findOrFail($attributes['lease_contract_id'])->unit_id, 'amount' => 3500, 'due_date' => today()->startOfMonth(), 'status' => 'pending'];
    }
}
