<?php


namespace Database\Factories;


use App\Models\Answer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Class AnswerFactory
 * @package Database\Factories
 * @method Answer create($attributes = [], ?Model $parent = null)
 * @method Answer make($attributes = [], ?Model $parent = null)
 */
class AnswerFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Answer::class;

    /**
     * @return array
     */
    public function definition()
    {
        return [
            'answer' => Str::random(10),
            'url' => $this->faker->url
        ];
    }

}
