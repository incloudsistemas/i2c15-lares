<?php

namespace Database\Seeders\System;

use App\Models\System\Agency;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AgenciesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->truncateTable();

        Agency::factory(5)
            ->create();
    }

    protected function truncateTable()
    {
        $this->command->info('Truncating Agency table');
        Schema::disableForeignKeyConstraints();

        DB::table('agencies')
            ->truncate();

        DB::table('agency_user')
            ->truncate();

        Schema::enableForeignKeyConstraints();
    }
}
