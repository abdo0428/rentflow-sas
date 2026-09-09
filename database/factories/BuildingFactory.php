<?php

namespace Database\Factories;

use App\Models\Building;
use App\Models\ManagementCompany;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Building> */
class BuildingFactory extends Factory
{
    public function definition(): array
    {
        return ['company_id' => ManagementCompany::factory(), 'name' => fake()->streetName(), 'code' => fake()->unique()->bothify('BLD-####'), 'address' => fake()->streetAddress(), 'city' => 'Riyadh', 'district' => 'Al Olaya', 'total_floors' => 5, 'status' => 'active'];
    }
}
