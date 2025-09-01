<?php

namespace Database\Factories\System;

use App\Models\Polymorphics\Address;
use App\Models\System\TenantAccount;
use App\Models\System\TenantCategory;
use App\Models\System\TenantPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\System\TenantAccount>
 */
class TenantAccountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'plan_id'       => TenantPlan::inRandomOrder()->value('id') ?? TenantPlan::factory(),
            'role'          => $this->faker->randomElement(['1']),
            'name'          => $this->faker->company(),
            'cpf_cnpj'      => $this->faker->randomElement([
                $this->faker->unique()->numerify('###.###.###-##'),
                $this->faker->unique()->numerify('##.###.###/####-##'),
            ]),
            'holder_name'   => $this->faker->name(),
            'emails'        => [
                [
                    'email' => $this->faker->unique()->safeEmail(),
                    'name'  => $this->faker->randomElement(['Pessoal', 'Trabalho', 'Outros']),
                ],
                [
                    'email' => $this->faker->unique()->safeEmail(),
                    'name'  => $this->faker->randomElement(['Pessoal', 'Trabalho', 'Outros']),
                ],
            ],
            'phones'        => [
                [
                    'number' => $this->faker->phoneNumber(),
                    'name'   => $this->faker->randomElement(['Celular', 'Whatsapp', 'Casa', 'Trabalho', 'Outros']),
                ],
                [
                    'number' => $this->faker->phoneNumber(),
                    'name'   => $this->faker->randomElement(['Celular', 'Whatsapp', 'Casa', 'Trabalho', 'Outros']),
                ],
            ],
            'complement'    => $this->faker->sentence(),
            'social_media'  => [
                [
                    'role' => $this->faker->randomElement(['Instagram', 'Facebook']),
                    'url'  => $this->faker->url(),
                ],
                [
                    'role' => $this->faker->randomElement(['Twitter', 'LinkedIn']),
                    'url'  => $this->faker->url(),
                ]
            ],
            'opening_hours' => ["Seg - Sex: 08h - 17h", "Sáb: 08h - 12h"],
            'theme'         => [
                'primary_color'    => $this->faker->hexColor(),
                'secondary_color'  => $this->faker->optional()->hexColor(),
                'background_color' => $this->faker->hexColor(),
            ],
            'status'        => $this->faker->randomElement([0, 1]),
            'settings'      => null,
            'custom'        => null,
        ];
    }

    /**
     * After creating a TenantAccount, automatically:
     * - Create an Address
     * - Associate one or more existing Categories
     */
    public function configure()
    {
        return $this->afterCreating(function (TenantAccount $tenantAccount) {
            // Create an Address related to the Tenant
            Address::factory()
                ->create([
                    'addressable_id'   => $tenantAccount->id,
                    'addressable_type' => MorphMapByClass(model: TenantAccount::class),
                ]);

            // Attach one or more existing Categories to the Tenant
            $categories = TenantCategory::inRandomOrder()
                ->limit(rand(1, 3))
                ->pluck('id');

            $tenantAccount->categories()
                ->attach($categories);
        });
    }
}
