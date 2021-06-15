<?php


namespace App\Voting\QuestionTypes;


use App\Models\Question;
use App\Voting\AnonymizationMethods\MixNets\TallyDatabase;
use Illuminate\Support\Facades\Validator;

/**
 * Class QuestionType
 * @package App\Voting\QuestionTypes
 * @property \App\Models\Question question
 */
abstract class QuestionType
{
    public Question $question;

    /**
     * QuestionType constructor.
     * @param \App\Models\Question $question
     */
    public function __construct(Question $question)
    {
        $this->question = $question;
    }

    /**
     * @param \App\Voting\AnonymizationMethods\MixNets\TallyDatabase $tallyDatabase
     * @param \App\Models\Question $question
     * @return void
     */
    abstract public static function createAnswersTable(TallyDatabase $tallyDatabase, Question $question): void;

    /**
     * @param \App\Models\Question $question
     * @return string
     */
    abstract public static function getTallyQuery(Question $question): string;

    /**
     * @throws \Illuminate\Validation\ValidationException
     */
    public static function validate($data): array
    {
        return Validator::make($data, [
            'question' => ['required', 'string'],
            'min' => ['required', 'int', 'min:0'],
            'max' => ['required', 'int', 'gte:min'],
            'answers' => ['required', 'array', 'min:2'],  // at least two answers
            'answers.*.answer' => ['required', 'string'],
            'answers.*.url' => ['nullable', 'url'],
        ])->validated();
    }

}
