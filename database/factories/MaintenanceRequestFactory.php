<?php

namespace Database\Factories;

use App\Models\MaintenanceRequest;
use App\Models\Tenant;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<MaintenanceRequest> */
class MaintenanceRequestFactory extends Factory
{
    public function definition(): array
    {
        return ['unit_id' => Unit::factory(), 'company_id' => fn (array $attributes) => Unit::withoutGlobalScopes()->findOrFail($attributes['unit_id'])->company_id, 'building_id' => fn (array $attributes) => Unit::withoutGlobalScopes()->findOrFail($attributes['unit_id'])->building_id, 'tenant_id' => fn (array $attributes) => Tenant::factory()->create(['company_id' => $attributes['company_id']])->id, 'title' => 'Kitchen tap needs repair', 'description' => 'The kitchen tap is leaking. Please arrange a visit.', 'priority' => 'medium', 'status' => 'new', 'preferred_date' => today()->addDays(2)];
    }
}
