<?php


namespace Database\Factories;


use App\Models\PeerServer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Class PeerServerFactory
 * @package Database\Factories
 * @method PeerServer create($attributes = [], ?Model $parent = null)
 */
class PeerServerFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = PeerServer::class;

    /**
     * @return array
     */
    public function definition()
    {
        return [
            'name' => Str::random(10),
            'domain' => Str::random(10),
        ];
    }

}
