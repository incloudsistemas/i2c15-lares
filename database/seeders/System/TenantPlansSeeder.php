<?php

namespace Database\Seeders\System;

use App\Models\System\TenantPlan;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TenantPlansSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->truncateTable();

        TenantPlan::factory(4)
            ->create();
    }

    protected function truncateTable()
    {
        $this->command->info('Truncating Tenant Plans table');
        Schema::disableForeignKeyConstraints();

        DB::table('tenant_plans')
            ->truncate();

        Schema::enableForeignKeyConstraints();
    }
}
