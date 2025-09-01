<?php

namespace Database\Factories\System;

use App\Models\System\Tenant;
use App\Models\System\TenantAccount;
use App\Models\System\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\System\Tenant>
 */
class TenantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->company();

        return [
            'id' => Str::slug($name . '-' . uniqid()),
        ];
    }

    /**
     * After creating a Tenant, automatically:
     * - Create an Account
     */
    public function configure()
    {
        return $this->afterCreating(function (Tenant $tenant) {
            // Create an Address related to the Tenant
            TenantAccount::factory()
                ->create([
                    'tenant_id' => $tenant->id,
                    'name'      => $tenant->id,
                ]);

            // Create an Domain related to the Tenant
            $baseDomain = config('tenancy.base_domain');
            $domain = "{$tenant->id}.{$baseDomain}";

            $tenant->domains()
                ->create([
                    'domain' => $domain,
                ]);

            // tenancy()->initialize($tenant);

            // User::factory(rand(1, 5))
            //     ->create();

            // tenancy()->end();
        });
    }
}
