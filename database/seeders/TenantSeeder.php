<?php

namespace Database\Seeders;

use App\Models\ManagementCompany;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Tenancy;
use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        app(Tenancy::class)->runWithoutScope(function () {
            $company = ManagementCompany::where('slug', 'demo')->firstOrFail();
            foreach (['tenant@example.com' => 'Omar Al Harbi', 'sara@example.com' => 'Sara Abdullah', 'khalid@example.com' => 'Khalid Hassan'] as $email => $name) {
                Tenant::firstOrCreate(['company_id' => $company->id, 'email' => $email], ['full_name' => $name, 'user_id' => $email === 'tenant@example.com' ? User::where('email', $email)->firstOrFail()->id : null, 'phone' => '+966 50 555 0100']);
            }
        });
    }
}
