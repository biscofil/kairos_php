<?php


namespace App\Voting\QuestionTypes;

interface BelongsToQuestionType
{

    /**
     * @return string|QuestionType
     */
    public static function getQuestionType() : string;
}
