<?php


namespace App\Voting\QuestionTypes;


use App\Models\Question;

interface SupportsHomomorhpicEncryption
{

    /**
     * @param \App\Models\Question $question
     * @return mixed
     */
    public static function generateAllCombinations(Question $question);

}
