<?php

namespace Database\Factories;

use App\Models\Election;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ElectionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Election::class;

    /**
     * @return array
     */
    public function definition()
    {

        return [
            'name' => $this->faker->name,
            'slug' => $this->faker->slug,
            'description' => $this->faker->paragraph,
            'help_email' => $this->faker->email,
            'info_url' => $this->faker->url,
            //
            'is_private' => $this->faker->boolean,
            'is_featured' => $this->faker->boolean,
            //
            // 'is_registration_open' => $this->faker->boolean,
            'use_voter_alias' => $this->faker->boolean,
            'use_advanced_audit_features' => $this->faker->boolean,
            'randomize_answer_order' => $this->faker->boolean,
            //
            'registration_starts_at' => null,
            'voting_starts_at' => null,
            'voting_started_at' => null,
            'voting_extended_until' => null,
            'voting_end_at' => null,
            'voting_ended_at' => null,
            //
            'tallying_started_at' => null,
            'tallying_finished_at' => null,
            'tallying_combined_at' => null,
            'results_released_at' => null,
            //
            'frozen_at' => null,
            'archived_at' => null,
        ];
    }

    /**
     * Indicate that the election is frozen
     * @return self
     */
    public function withUUID()
    {
        return $this->state(function (array $attributes) {
            return [
                'uuid' => (string)Str::uuid(),
            ];
        });
    }

    /**
     * Indicate that the election is frozen
     * @return self
     */
    public function withAdmin(User $user)
    {
        return $this->state(function (array $attributes) use ($user) {
            return [
                'admin_id' => $user->id
            ];
        });
    }


    /**
     * Indicate that the election is frozen
     * @return Factory
     */
    public function frozen()
    {
        return $this->state(function (array $attributes) {
            return [
                'voting_starts_at' => now()->subMinutes(3),
                'frozen_at' => now()->subMinutes(2),
                'voting_started_at' => now()->subMinutes(1),
            ];
        });
    }


}
