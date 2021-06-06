<?php


namespace App\Voting\QuestionTypes;


use App\Models\Election;
use App\Models\Question;

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
    abstract public static function getTallyQuery(Question $question, int $questionId) : string;

    // read from DB

    /**
     * TODO
     */
    public function setupOutputTables(Election $election)
    {

    }

}
