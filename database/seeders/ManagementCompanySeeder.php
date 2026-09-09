<?php

namespace Database\Seeders;

use App\Models\ManagementCompany;
use App\Models\User;
use App\Support\Tenancy;
use Illuminate\Database\Seeder;

class ManagementCompanySeeder extends Seeder
{
    public function run(): void
    {
        app(Tenancy::class)->runWithoutScope(function () {
            ManagementCompany::firstOrCreate(['slug' => 'demo'], ['name' => 'Demo Property Management', 'email' => 'hello@example.com', 'phone' => '+966 11 555 0100', 'address' => 'Olaya Street, Riyadh', 'status' => 'active', 'trial_ends_at' => now()->addDays(14)]);
            $company = ManagementCompany::where('slug', 'demo')->firstOrFail();
            foreach (['super' => 'super_admin', 'admin' => 'company_admin', 'manager' => 'property_manager', 'accountant' => 'accountant', 'maintenance' => 'maintenance_staff', 'tenant' => 'tenant'] as $prefix => $role) {
                $user = User::firstOrCreate(['email' => $prefix.'@example.com'], ['name' => ucwords(str_replace('_', ' ', $role)), 'password' => 'password']);
                if ($user->wasRecentlyCreated || $user->roles()->doesntExist()) {
                    $user->forceFill(['company_id' => $role === 'super_admin' ? null : $company->id, 'status' => 'active', 'email_verified_at' => now()])->save();
                    $user->syncRoles($role);
                }
            }
        });
    }
}
