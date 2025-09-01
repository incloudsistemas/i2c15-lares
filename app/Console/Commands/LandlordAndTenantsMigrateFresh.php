<?php

namespace App\Console\Commands;

use App\Models\System\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class LandlordAndTenantsMigrateFresh extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'landlord-tenants:migrate-fresh';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Drop all tenant databases and run migrate:fresh';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Dropping all tenant databases...');

        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            $dbName = $tenant->tenancy_db_name;

            if ($dbName) {
                DB::statement("DROP DATABASE IF EXISTS `$dbName`");
                $this->warn("Dropped database: $dbName");
            } else {
                $this->error("Database not found for tenant {$tenant->id}");
            }
        }

        $this->info('Running migrate:fresh...');
        $this->call('migrate:fresh');

        return self::SUCCESS;
    }
}
