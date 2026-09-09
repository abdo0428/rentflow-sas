<?php

namespace App\Services;

use App\Models\ManagementCompany;
use App\Models\User;
use App\Support\Tenancy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class RegisterCompany
{
    /** Create the workspace and its owner atomically; role/company never come from input. */
    public function handle(array $data): User
    {
        return app(Tenancy::class)->runWithoutScope(fn () => DB::transaction(function () use ($data) {
            $company = ManagementCompany::create([
                'name' => $data['company_name'],
                'slug' => Str::slug($data['company_name']).'-'.Str::lower((string) Str::ulid()),
                'email' => $data['email'], 'phone' => '', 'address' => '',
                'status' => 'active', 'trial_ends_at' => now()->addDays(14),
            ]);
            $user = new User(['name' => $data['name'], 'email' => $data['email'], 'password' => $data['password']]);
            $user->company_id = $company->id;
            $user->status = 'active';
            $user->save();
            $user->assignRole(Role::findByName('company_admin', 'web'));

            return $user;
        }));
    }
}
