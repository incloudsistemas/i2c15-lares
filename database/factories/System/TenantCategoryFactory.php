<?php

namespace Database\Factories\System;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\System\TenantCategory>
 */
class TenantCategoryFactory extends Factory
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
            'category_id' => null,
            'name'        => $name,
            'slug'        => Str::slug($name . '-' . uniqid()),
            'order'       => $this->faker->numberBetween(1, 10),
            'featured'    => $this->faker->boolean(),
            'status'      => $this->faker->randomElement([0, 1]),
        ];
    }
}
