<?php

namespace Database\Factories\System;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\System\TenantPlan>
 */
class TenantPlanFactory extends Factory
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
            'name'                => $name,
            'slug'                => Str::slug($name . '-' . uniqid()),
            'complement'          => $this->faker->sentence(),
            'monthly_price'       => $this->faker->randomNumber(5),
            'monthly_price_notes' => $this->faker->sentence(),
            'annual_price'        => $this->faker->randomNumber(6),
            'annual_price_notes'  => $this->faker->sentence(),
            'best_benefit_cost'   => $this->faker->boolean(),
            'order'               => $this->faker->numberBetween(1, 10),
            'status'              => $this->faker->randomElement([0, 1]),
            'features'            => null,
            'settings'            => null,
        ];
    }
}
