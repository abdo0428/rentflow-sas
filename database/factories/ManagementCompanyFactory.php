<?php

namespace Database\Factories;

use App\Models\ManagementCompany;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ManagementCompany> */
class ManagementCompanyFactory extends Factory
{
    public function definition(): array
    {
        return ['name' => fake()->company(), 'slug' => fake()->unique()->slug(), 'email' => fake()->companyEmail(), 'phone' => fake()->phoneNumber(), 'address' => fake()->address(), 'status' => 'active', 'trial_ends_at' => now()->addDays(14)];
    }
}
