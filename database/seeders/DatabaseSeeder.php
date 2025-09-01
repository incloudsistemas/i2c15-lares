<?php

namespace Database\Seeders;

use App\Models\System\Tenant;
use Database\Seeders\System\RolesAndPermissionsSeeder;
use Database\Seeders\System\TenantCategoriesSeeder;
use Database\Seeders\System\TenantPlansSeeder;
use Database\Seeders\System\TenantRolesAndPermissionsSeeder;
use Database\Seeders\System\TenantsSeeder;
use Database\Seeders\System\UsersSeeder;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $commandName = $this->command?->getName();

        // For tenants
        if ($commandName === 'tenants:seed') {
            $this->call([
                TenantRolesAndPermissionsSeeder::class,
                UsersSeeder::class,
            ]);

            return;
        }

        // For landlord
        $this->call([
            RolesAndPermissionsSeeder::class,
            UsersSeeder::class,

            // TenantPlansSeeder::class,
            // TenantCategoriesSeeder::class,
            // TenantsSeeder::class,
        ]);
    }
}
