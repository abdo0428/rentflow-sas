<?php

namespace Database\Factories;

use App\Models\Announcement;
use App\Models\ManagementCompany;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Announcement> */
class AnnouncementFactory extends Factory
{
    public function definition(): array
    {
        return ['company_id' => ManagementCompany::factory(), 'title' => 'Welcome to your resident portal', 'body' => 'View your lease, follow rent payments and stay updated on your building.', 'target' => 'company', 'status' => 'published', 'published_at' => now()->subHour()];
    }
}
