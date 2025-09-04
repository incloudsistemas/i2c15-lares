<?php

namespace Database\Factories\Crm\Contacts;

use App\Models\Crm\Contacts\LegalEntity;
use App\Models\Polymorphics\Address;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Crm\Contacts\LegalEntity>
 */
class LegalEntityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'trade_name'             => $this->faker->company(),
            'cnpj'                   => $this->faker->unique()->numerify('##.###.###/####-##'),
            'municipal_registration' => $this->faker->optional()->numerify('########'),
            'state_registration'     => $this->faker->optional()->numerify('###########'),
            'url'                    => $this->faker->optional()->url(),
            'sector'                 => $this->faker->optional()->randomElement([
                'Serviços',
                'Comércio',
                'Indústria',
                'Outros'
            ]),
            'num_employees'          => $this->faker->optional()->randomElement([
                '1-5',
                '6-10',
                '11-50',
                '51-250',
                '251-1000'
            ]),
            'monthly_income'         => $this->faker->optional()->randomElement([
                'até R$ 10.000',
                'entre R$ 11.000 e R$ 50.000',
                'entre R$ 51.000 e R$ 100.000',
                'entre R$ 101.000 e R$ 300.000',
                'acima de R$ 300.000'
            ]),
        ];
    }

    /**
     * After creating a LegalEntity, automatically:
     * - Create an Address
     */
    public function configure()
    {
        return $this->afterCreating(
            function (LegalEntity $legalEntity): void {
                // Create an Address related to the LegalEntity
                Address::factory()
                    ->create([
                        'addressable_id'   => $legalEntity->id,
                        'addressable_type' => MorphMapByClass(model: LegalEntity::class),
                    ]);
            }
        );
    }
}
