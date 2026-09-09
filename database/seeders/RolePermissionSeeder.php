<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $permissions = ['companies.view', 'companies.create', 'companies.update', 'companies.delete', 'buildings.view', 'buildings.create', 'buildings.update', 'buildings.delete', 'units.view', 'units.create', 'units.update', 'units.delete', 'tenants.view', 'tenants.create', 'tenants.update', 'tenants.delete', 'leases.view', 'leases.create', 'leases.update', 'leases.delete', 'payments.view', 'payments.create', 'payments.update', 'payments.delete', 'maintenance.view', 'maintenance.create', 'maintenance.update', 'maintenance.delete', 'maintenance.assign', 'announcements.view', 'announcements.create', 'announcements.update', 'announcements.delete', 'documents.view', 'documents.create', 'documents.delete', 'reports.view', 'settings.view', 'settings.update'];
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        $roles = [
            'super_admin' => $permissions,
            'company_admin' => array_values(array_filter($permissions, fn (string $permission) => ! str_starts_with($permission, 'companies.'))),
            'property_manager' => array_values(array_filter($permissions, fn (string $permission) => in_array(explode('.', $permission)[0], ['buildings', 'units', 'tenants', 'leases', 'maintenance', 'announcements', 'documents', 'reports']) || $permission === 'payments.view')),
            'accountant' => ['buildings.view', 'units.view', 'tenants.view', 'leases.view', 'payments.view', 'payments.create', 'payments.update', 'documents.view', 'documents.create', 'reports.view'],
            'maintenance_staff' => ['maintenance.view', 'maintenance.update'],
            'tenant' => ['units.view', 'leases.view', 'payments.view', 'maintenance.view', 'maintenance.create', 'announcements.view', 'documents.view'],
        ];
        foreach ($roles as $name => $grants) {
            Role::findOrCreate($name, 'web')->syncPermissions($grants);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
