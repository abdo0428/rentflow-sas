<?php

namespace Database\Seeders;

use App\Models\Building;
use App\Models\ManagementCompany;
use App\Support\Tenancy;
use Illuminate\Database\Seeder;

class BuildingSeeder extends Seeder
{
    public function run(): void
    {
        app(Tenancy::class)->runWithoutScope(function () {
            $company = ManagementCompany::where('slug', 'demo')->firstOrFail();
            foreach (['OLY' => 'Olaya Residences', 'NRG' => 'Narjis Gardens'] as $code => $name) {
                Building::firstOrCreate(['company_id' => $company->id, 'code' => $code], ['name' => $name, 'address' => 'King Fahd Road', 'city' => 'Riyadh', 'district' => $code === 'OLY' ? 'Al Olaya' : 'Al Narjis', 'total_floors' => 4, 'status' => 'active']);
            }
        });
    }
}
