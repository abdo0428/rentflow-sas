<?php

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\ManagementCompany;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AuditLog> */
class AuditLogFactory extends Factory
{
    public function definition(): array
    {
        return ['company_id' => ManagementCompany::factory(), 'action' => 'demo.created', 'model_type' => 'company', 'model_id' => fn (array $attributes) => $attributes['company_id'], 'new_values' => ['source' => 'demo'], 'ip_address' => '127.0.0.1'];
    }
}
