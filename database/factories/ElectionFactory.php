<?php

namespace Database\Factories;

use App\Enums\AnonymizationMethodEnum;
use App\Enums\CryptoSystemEnum;
use App\Models\Election;
use App\Models\PeerServer;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Class ElectionFactory
 * @package Database\Factories
 * @method Election create($attributes = [], ?Model $parent = null)
 */
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
            'peer_server_id' => PeerServer::meID,
            //
            'cryptosystem' => array_rand(CryptoSystemEnum::CRYPTOSYSTEMS),
            'anonymization_method' => array_rand(AnonymizationMethodEnum::ANONYMIZATION_METHODS),
            //
            'min_peer_count_t' => 1,
            'name' => $this->faker->name,
            'uuid' => $this->faker->uuid,
            'slug' => Str::random(100),
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
            'frozen_at' => null,
            'voting_starts_at' => Carbon::now()->addMinutes(1),
            'voting_started_at' => null,
            'voting_extended_until' => null,
            'voting_ends_at' => Carbon::now()->addMinutes(2),
            'voting_ended_at' => null,
            //
            'tallying_started_at' => null,
            'tallying_finished_at' => null,
            'tallying_combined_at' => null,
            'results_released_at' => null,
            //
            'archived_at' => null,
        ];
    }

    /**
     * Indicate that the election is frozen
     * @param User $user
     * @return self
     * @noinspection PhpMissingReturnTypeInspection
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
