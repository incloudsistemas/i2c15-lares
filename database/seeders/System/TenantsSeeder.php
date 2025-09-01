<?php

namespace Database\Seeders\System;

use App\Models\System\Tenant;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TenantsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->truncateTable();

        Tenant::factory(2)
            ->create();
    }

    protected function truncateTable()
    {
        $this->command->info('Truncating Tenants table');
        Schema::disableForeignKeyConstraints();

        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            $databaseName = $tenant->tenancy_db_name;

            if ($databaseName) {
                DB::statement("DROP DATABASE IF EXISTS `$databaseName`");
                $this->command->warn("Dropped database: $databaseName");
            }
        }

        DB::table('tenants')
            ->truncate();

        Schema::enableForeignKeyConstraints();
    }
}
