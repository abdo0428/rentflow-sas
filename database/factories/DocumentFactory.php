<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\LeaseContract;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Document> */
class DocumentFactory extends Factory
{
    public function definition(): array
    {
        return ['documentable_type' => 'lease', 'documentable_id' => LeaseContract::factory(), 'company_id' => fn (array $attributes) => LeaseContract::withoutGlobalScopes()->findOrFail($attributes['documentable_id'])->company_id, 'uploaded_by' => fn (array $attributes) => User::factory()->create(['company_id' => $attributes['company_id']])->id, 'title' => 'Lease information', 'file_path' => 'demo/lease-information.txt', 'document_type' => 'lease'];
    }
}
