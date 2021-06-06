<?php


namespace App\Voting\QuestionTypes;


use App\Models\Question;
use Illuminate\Support\Facades\Validator;

abstract class QuestionType
{
    /**
     * @var \App\Models\Question
     */
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
     * @param \App\Models\Question $question
     * @param int $questionId
     * @return string
     */
    abstract public static function getTallyQuery(Question $question, int $questionId): string;

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
