<?php

namespace Database\Factories\Crm\Contacts;

use App\Models\Crm\Contacts\Contact;
use App\Models\Crm\Contacts\Role;
use App\Models\Crm\Source;
use App\Models\Polymorphics\Address;
use App\Models\System\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Crm\Contacts\Contact>
 */
class ContactFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'contactable_type'  => null, // Set dynamically
            'contactable_id'    => null, // Set dynamically
            'user_id'           => User::inRandomOrder()->first()->id,
            'source_id'         => Source::inRandomOrder()->first()->id,
            'name'              => $this->faker->name,
            'email'             => $this->faker->unique()->safeEmail(),
            'additional_emails' => [
                [
                    'email' => $this->faker->unique()->safeEmail(),
                    'name'  => $this->faker->randomElement(['Pessoal', 'Trabalho', 'Outros'])
                ]
            ],
            'phones'            => [
                [
                    'number' => $this->faker->numerify('(##) #####-####'),
                    'name'   => $this->faker->randomElement(['Celular', 'Whatsapp', 'Casa', 'Trabalho', 'Outros'])
                ]
            ],
            'status'            => $this->faker->boolean,
        ];
    }

    /**
     * After creating a Contact, automatically:
     * - Associate one or more existing Roles
     */
    public function configure()
    {
        return $this->afterCreating(
            function (Contact $contact): void {
                // Attach one or more existing Roles to the Contact
                $roles = Role::inRandomOrder()
                    ->limit(rand(1, 3))
                    ->pluck('id');

                $contact->roles()
                    ->attach($roles);
            }
        );
    }
}
