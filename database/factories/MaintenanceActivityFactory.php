<?php

namespace Database\Factories;

use App\Models\MaintenanceRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

class MaintenanceActivityFactory extends Factory
{
    public function definition(): array
    {
        return [
            'maintenance_request_id' => MaintenanceRequest::factory(),
            'company_id' => fn (array $attributes) => MaintenanceRequest::withoutGlobalScopes()->findOrFail($attributes['maintenance_request_id'])->company_id,
            'kind' => 'status',
            'from_status' => null,
            'to_status' => 'new',
        ];
    }
}
