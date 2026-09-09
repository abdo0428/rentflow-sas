<?php

namespace Database\Seeders;

use App\Models\LeaseContract;
use App\Models\MaintenanceRequest;
use App\Models\ManagementCompany;
use App\Models\User;
use App\Support\Tenancy;
use Illuminate\Database\Seeder;

class MaintenanceRequestSeeder extends Seeder
{
    public function run(): void
    {
        app(Tenancy::class)->runWithoutScope(function () {
            $company = ManagementCompany::where('slug', 'demo')->firstOrFail();
            $leases = LeaseContract::where('company_id', $company->id)->with('unit')->orderBy('id')->get();
            foreach (['Kitchen tap repair', 'Air conditioner inspection', 'Bedroom window handle'] as $index => $title) {
                $lease = $leases[$index];
                MaintenanceRequest::firstOrCreate(['company_id' => $company->id, 'title' => $title], ['building_id' => $lease->unit->building_id, 'unit_id' => $lease->unit_id, 'tenant_id' => $lease->tenant_id, 'assigned_to' => $index === 0 ? User::where('email', 'maintenance@example.com')->firstOrFail()->id : null, 'description' => 'Please arrange a maintenance visit during the afternoon.', 'priority' => $index === 0 ? 'high' : 'medium', 'status' => $index === 0 ? 'assigned' : 'new', 'preferred_date' => today()->addDays(2)]);
            }
        });
    }
}
