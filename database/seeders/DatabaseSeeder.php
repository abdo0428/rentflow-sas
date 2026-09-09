<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);
        if (! app()->environment(['local', 'testing'])) {
            return;
        }
        $this->call([ManagementCompanySeeder::class, BuildingSeeder::class, UnitSeeder::class, TenantSeeder::class, LeaseContractSeeder::class, RentPaymentSeeder::class, MaintenanceRequestSeeder::class, AnnouncementSeeder::class, DocumentSeeder::class, AuditLogSeeder::class]);
    }
}
