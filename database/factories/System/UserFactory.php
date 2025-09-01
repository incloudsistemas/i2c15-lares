<?php

namespace Database\Factories\System;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name'              => fake()->name(),
            'email'             => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password'          => static::$password ??= Hash::make('password'),
            'remember_token'    => Str::random(10),
            // Additional attributes
            'additional_emails' => [
                [
                    'email' => $this->faker->unique()->safeEmail(),
                    'name'  => $this->faker->randomElement(['Pessoal', 'Trabalho', 'Outros']),
                ],
                [
                    'email' => $this->faker->unique()->safeEmail(),
                    'name'  => $this->faker->randomElement(['Pessoal', 'Trabalho', 'Outros']),
                ],
            ],
            'phones'            => [
                [
                    'number' => $this->faker->phoneNumber(),
                    'name'   => $this->faker->randomElement(['Celular', 'Whatsapp', 'Casa', 'Trabalho', 'Outros']),
                ],
                [
                    'number' => $this->faker->phoneNumber(),
                    'name'   => $this->faker->randomElement(['Celular', 'Whatsapp', 'Casa', 'Trabalho', 'Outros']),
                ],
            ],
            'cpf'               => $this->faker->unique()->numerify('###.###.###-##'),
            'rg'                => $this->faker->unique()->numerify('##.###.###-#'),
            'gender'            => $this->faker->randomElement(['M', 'F']),
            'birth_date'        => $this->faker->dateTimeBetween('-80 years', '-18 years')->format('d/m/Y'),
            'marital_status'    => $this->faker->randomElement(['1', '2', '3', '4', '5', '6']),
            'educational_level' => $this->faker->randomElement(['1', '2', '3', '4', '5', '6']),
            'nationality'       => $this->faker->randomElement(['Brasileiro', 'Estrangeiro']),
            'citizenship'       => $this->faker->country(),
            'complement'        => $this->faker->sentence(),
            'status'            => $this->faker->randomElement(['0', '1', '2']), // 0 - Inativo, 1 - Ativo, 2 - Pendente
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn(array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
