<?php

namespace Database\Factories\Crm\Contacts;

use App\Models\Crm\Contacts\Individual;
use App\Models\Polymorphics\Address;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Crm\Contacts\Individual>
 */
class IndividualFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cpf'        => $this->faker->unique()->numerify('###.###.###-##'),
            'rg'         => $this->faker->optional()->numerify('##.###.###-#'),
            'gender'     => $this->faker->optional()->randomElement(['M', 'F']),
            'birth_date' => $this->faker->dateTimeBetween('-80 years', '-18 years')->format('d/m/Y'),
            'occupation' => $this->faker->optional()->randomElement([
                'Sócio/Dono',
                'Diretor',
                'Gerente/Coordenador',
                'Auxiliar/Assistente',
                'Consultor',
                'Autônomo',
                'Outros'
            ]),
        ];
    }

    /**
     * After creating a Individual, automatically:
     * - Create an Address
     */
    public function configure()
    {
        return $this->afterCreating(
            function (Individual $individual): void {
                // Create an Address related to the Individual
                Address::factory()
                    ->create([
                        'addressable_id'   => $individual->id,
                        'addressable_type' => MorphMapByClass(model: Individual::class),
                    ]);
            }
        );
    }
}
