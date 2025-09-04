<?php

namespace Database\Seeders\Crm\Contacts;

use App\Models\Crm\Contacts\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->truncateTable();

        $roles = [
            'Assinante',
            'Lead',
            'Cliente',
            // 'Proprietário',
            'Fornecedor',
            'Outro'
        ];

        foreach ($roles as $role) {
            Role::create([
                'name' => $role,
            ]);
        }

        // Role::factory(10)
        //     ->create();
    }

    protected function truncateTable()
    {
        $this->command->info('Truncating CRM Contact Role table');
        Schema::disableForeignKeyConstraints();

        DB::table('crm_contact_roles')
            ->truncate();

        DB::table('crm_contact_crm_contact_role')
            ->truncate();

        Schema::enableForeignKeyConstraints();
    }
}
