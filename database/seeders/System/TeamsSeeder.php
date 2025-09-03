<?php

namespace Database\Seeders\System;

use App\Models\System\Agency;
use App\Models\System\Team;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Arr;

class TeamsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->truncateTable();

        Agency::all()
            ->each(function (Agency $agency): void {
                Team::factory(Arr::random([1, 3, 5]))
                    ->create(['agency_id' => $agency->id]);
            });
    }

    protected function truncateTable()
    {
        $this->command->info('Truncating Team table');
        Schema::disableForeignKeyConstraints();

        DB::table('teams')
            ->truncate();

        DB::table('team_user')
            ->truncate();

        Schema::enableForeignKeyConstraints();
    }
}
