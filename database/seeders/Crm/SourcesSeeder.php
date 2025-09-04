<?php

namespace Database\Seeders\Crm;

use App\Models\Crm\Source;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SourcesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->truncateTable();

        $sources = [
            'Website',
            'Meta Ads',
            'Google Ads',
            'Tráfego direto',
            'Pesquisa orgânica',
            'Pesquisa paga',
            'Email marketing',
            'Mídia social',
            'Referências',
            'Fontes offline',
            'Outras campanhas'
        ];

        foreach ($sources as $source) {
            Source::create([
                'name' => $source,
            ]);
        }

        // Source::factory(30)
        //     ->create();
    }

    protected function truncateTable()
    {
        $this->command->info('Truncating CRM Source table');
        Schema::disableForeignKeyConstraints();

        DB::table('crm_sources')
            ->truncate();

        Schema::enableForeignKeyConstraints();
    }
}
