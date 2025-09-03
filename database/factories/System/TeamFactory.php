<?php

namespace Database\Factories\System;

use App\Models\System\Agency;
use App\Models\System\Role;
use App\Models\System\Team;
use App\Models\System\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\System\Team>
 */
class TeamFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $agency = Agency::inRandomOrder()->first() ?? Agency::factory()->create();
        $name = $this->faker->word();

        return [
            'agency_id'  => $agency->id,
            'name'       => $name,
            // 'slug'    => Str::slug($name),
            'complement' => $this->faker->optional()->sentence(),
            // 'status'     => $this->faker->boolean,
        ];
    }

    /**
     * After creating a Team, automatically:
     * - Associate to Users
     */
    public function configure()
    {
        return $this->afterCreating(
            function (Team $team): void {
                $this->createAndAttachUsersToTeam(
                    team: $team,
                    roleId: 5, // 5 - Coordenador
                    quantity: rand(1, 2)
                );

                $this->createAndAttachUsersToTeam(
                    team: $team,
                    roleId: 6, // 6 - Colaborador
                    quantity: Arr::random([5, 10, 15, 20])
                );
            }
        );
    }

    protected function createAndAttachUsersToTeam(Team $team, int $roleId, int $quantity): void
    {
        $role = Role::find($roleId);

        if (!$role) {
            return;
        }

        User::factory($quantity)
            ->create()
            ->each(function (User $user) use ($team, $role): void {
                $user->syncRoles([$role->name]);

                // 4 - Líder, 5 - Coordenador
                if (in_array($role->id, [3, 4, 5])) {
                    $teamRole = 1; // 1 - 'Líder/Leader ou Coordenador/Coordinator'
                }

                if ($role->id > 5) {
                    $teamRole = 2; // 2 - 'Colaborador/Collaborator'
                }

                $user->teams()
                    ->attach($team->id, ['role' => $teamRole]);
            });
    }
}
