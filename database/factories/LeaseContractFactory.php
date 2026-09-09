<?php

namespace Database\Factories;

use App\Models\LeaseContract;
use App\Models\Tenant;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<LeaseContract> */
class LeaseContractFactory extends Factory
{
    public function definition(): array
    {
        return ['unit_id' => Unit::factory(), 'company_id' => fn (array $attributes) => Unit::withoutGlobalScopes()->findOrFail($attributes['unit_id'])->company_id, 'tenant_id' => fn (array $attributes) => Tenant::factory()->create(['company_id' => $attributes['company_id']])->id, 'contract_number' => fake()->unique()->bothify('RF-####-????'), 'start_date' => today()->startOfMonth(), 'end_date' => today()->startOfMonth()->addYear()->subDay(), 'monthly_rent' => 3500, 'security_deposit' => 3500, 'payment_due_day' => 1, 'status' => 'active'];
    }
}
