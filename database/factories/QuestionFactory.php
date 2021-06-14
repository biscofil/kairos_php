<?php


namespace Database\Factories;


use App\Enums\QuestionTypeEnum;
use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Class QuestionFactory
 * @package Database\Factories
 * @method Question create($attributes = [], ?Model $parent = null)
 * @method Question make($attributes = [], ?Model $parent = null)
 */
class QuestionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Question::class;

    /**
     * @return array
     */
    public function definition()
    {
        return [
            'question' => Str::random(10),
            'min' => 0,
            'max' => 1,
            'question_type' => QuestionTypeEnum::MultipleChoice
        ];
    }

}
