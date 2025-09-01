<?php

namespace Database\Seeders\System;

use App\Models\System\TenantCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TenantCategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->truncateTable();

        TenantCategory::factory(50)
            ->create();
    }

    protected function truncateTable()
    {
        $this->command->info('Truncating Tenant Categories table');
        Schema::disableForeignKeyConstraints();

        DB::table('tenant_categories')
            ->truncate();

        Schema::enableForeignKeyConstraints();
    }
}
