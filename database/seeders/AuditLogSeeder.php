<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\ManagementCompany;
use App\Models\User;
use App\Support\Tenancy;
use Illuminate\Database\Seeder;

class AuditLogSeeder extends Seeder
{
    public function run(): void
    {
        app(Tenancy::class)->runWithoutScope(function () {
            $company = ManagementCompany::where('slug', 'demo')->firstOrFail();
            AuditLog::firstOrCreate(['company_id' => $company->id, 'action' => 'demo.created', 'model_type' => 'company', 'model_id' => $company->id], ['user_id' => User::where('email', 'admin@example.com')->firstOrFail()->id, 'new_values' => ['source' => 'demo'], 'ip_address' => '127.0.0.1']);
        });
    }
}
