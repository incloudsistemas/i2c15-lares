<?php

namespace Database\Seeders;

use App\Models\System\Tenant;
use Database\Seeders\Crm\Contacts\ContactsSeeder;
use Database\Seeders\Crm\Contacts\RolesSeeder as ContactRolesSeeder;
use Database\Seeders\Crm\SourcesSeeder;
use Database\Seeders\System\AgenciesSeeder;
use Database\Seeders\System\RolesAndPermissionsSeeder;
use Database\Seeders\System\TeamsSeeder;
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
                AgenciesSeeder::class,
                TeamsSeeder::class,

                ContactRolesSeeder::class,
                SourcesSeeder::class,
                ContactsSeeder::class,
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
