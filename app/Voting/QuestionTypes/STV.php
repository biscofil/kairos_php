<?php


namespace App\Voting\QuestionTypes;


use App\Models\Question;

class STV extends QuestionType
{

    /**
     * @param \App\Models\Question $question
     * @return string
     */
    public static function getTallyQuery(Question $question): string
    {
        return ''; // TODO
    }
}
