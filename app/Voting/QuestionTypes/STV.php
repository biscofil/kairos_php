<?php


namespace App\Voting\QuestionTypes;


use App\Models\Question;

class STV extends QuestionType
{

    /**
     * @param \App\Models\Question $question
     * @param int $questionId
     * @return string
     */
    public static function getTallyQuery(Question $question, int $questionId): string
    {
        return ""; // TODO
    }
}
