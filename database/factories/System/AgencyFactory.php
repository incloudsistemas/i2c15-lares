<?php

namespace Database\Factories\System;

use App\Models\System\Agency;
use App\Models\System\Role;
use App\Models\System\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\System\Agency>
 */
class AgencyFactory extends Factory
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
            'name'       => $name,
            // 'slug'    => Str::slug($name),
            'complement' => $this->faker->optional()->sentence(),
            // 'status'     => $this->faker->boolean,
        ];
    }

    /**
     * After creating a Agency, automatically:
     * - Associate to Leader User(s)
     */
    public function configure()
    {
        return $this->afterCreating(
            function (Agency $agency): void {
                User::factory(rand(1, 2))
                    ->create()
                    ->each(function (User $user) use ($agency): void {
                        $role = Role::find(4); // 4 - Líder
                        $user->syncRoles([$role->name]);

                        $user->agencies()
                            ->attach($agency->id);
                    });
            }
        );
    }
}
