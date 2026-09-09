<?php

namespace Database\Factories;

use App\Models\Building;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Unit> */
class UnitFactory extends Factory
{
    public function definition(): array
    {
        return ['building_id' => Building::factory(), 'company_id' => fn (array $attributes) => Building::withoutGlobalScopes()->findOrFail($attributes['building_id'])->company_id, 'unit_number' => fake()->unique()->numerify('U-####'), 'floor' => 1, 'type' => 'apartment', 'bedrooms' => 2, 'bathrooms' => 2, 'area' => 110, 'rent_amount' => 3500, 'status' => 'vacant'];
    }
}
