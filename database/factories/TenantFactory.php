<?php

namespace Database\Factories;

use App\Models\ManagementCompany;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Tenant> */
class TenantFactory extends Factory
{
    public function definition(): array
    {
        return ['company_id' => ManagementCompany::factory(), 'user_id' => null, 'full_name' => fake()->name(), 'email' => fake()->unique()->safeEmail(), 'phone' => fake()->phoneNumber()];
    }
}
