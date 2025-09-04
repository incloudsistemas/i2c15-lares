<?php

namespace Database\Factories\Crm\Contacts;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Crm\Contacts\Role>
 */
class RoleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->word();

        return [
            'name'        => $name,
            // 'slug'        => Str::slug($name),
            'description' => $this->faker->optional()->sentence(),
            'status'      => $this->faker->boolean,
        ];
    }
}
