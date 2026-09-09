<?php

namespace Database\Seeders;

use App\Models\Building;
use App\Models\ManagementCompany;
use App\Models\Unit;
use App\Support\Tenancy;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    public function run(): void
    {
        app(Tenancy::class)->runWithoutScope(function () {
            foreach (Building::where('company_id', ManagementCompany::where('slug', 'demo')->firstOrFail()->id)->get() as $building) {
                foreach (range(1, 6) as $number) {
                    Unit::firstOrCreate(['building_id' => $building->id, 'unit_number' => '10'.$number], ['company_id' => $building->company_id, 'floor' => 1, 'type' => 'apartment', 'bedrooms' => 2, 'bathrooms' => 2, 'area' => 110 + $number * 5, 'rent_amount' => 3000 + $number * 250, 'status' => $number === 6 ? 'maintenance' : 'vacant']);
                }
            }
        });
    }
}
