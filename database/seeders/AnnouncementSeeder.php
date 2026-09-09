<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\Building;
use App\Models\ManagementCompany;
use App\Support\Tenancy;
use Illuminate\Database\Seeder;

class AnnouncementSeeder extends Seeder
{
    public function run(): void
    {
        app(Tenancy::class)->runWithoutScope(function () {
            $company = ManagementCompany::where('slug', 'demo')->firstOrFail();
            Announcement::firstOrCreate(['company_id' => $company->id, 'title' => 'Welcome to RentFlow'], ['body' => 'Your property, payments and maintenance updates are now in one place.', 'target' => 'company', 'status' => 'published', 'published_at' => now()->subDay()]);
            Announcement::firstOrCreate(['company_id' => $company->id, 'title' => 'Scheduled elevator service'], ['body' => 'The elevator will be inspected this Thursday between 9 AM and 11 AM.', 'target' => 'building', 'building_id' => Building::where('company_id', $company->id)->firstOrFail()->id, 'status' => 'published', 'published_at' => now()->subHours(3)]);
        });
    }
}
