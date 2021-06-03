<?php


namespace App\Voting\QuestionTypes;


use App\Models\Question;

/**
 * Class MultipleChoice
 * @package App\Voting\QuestionTypes
 * Ignores order
 */
class MultipleChoice extends QuestionType
{

    /**
     * @param \App\Models\Question $question
     * @param int $questionId
     * @return string
     */
    public static function getTallyQuery(Question $question, int $questionId)
    {

        /**
         * SELECT id, sum(c) as count FROM (
         * SELECT "q_1_a_1" as id, COUNT(id) as c FROM "e_33" WHERE COALESCE("q_1_a_1" NOT IN ("q_1_a_2","q_1_a_3"),1) GROUP BY "q_1_a_1"
         * UNION ALL
         * SELECT "q_1_a_2" as id, COUNT(id) as c FROM "e_33" WHERE COALESCE("q_1_a_2" NOT IN ("q_1_a_1","q_1_a_3"),1) GROUP BY "q_1_a_2"
         * UNION ALL
         * SELECT "q_1_a_3" as id, COUNT(id) as c FROM "e_33" WHERE COALESCE("q_1_a_3" NOT IN ("q_1_a_1","q_1_a_2"),1) GROUP BY "q_1_a_3"
         * ) GROUP BY id HAVING id NOT NULL
         */

        $questionAnswerCols = $question->getAnswerColumnNames($questionId);

        $question_answers_table_name = $question->election->getOutputTableName();
        $query = 'SELECT id, sum(c) as count FROM (';
        $first = true;
        foreach ($questionAnswerCols as $questionAnswerCol) {
            if (!$first) {
                $query .= ' UNION ALL ';
            }
            $otherColumns = array_diff($questionAnswerCols, [$questionAnswerCol]);
            $otherColumnStr = implode('","', $otherColumns);
            $query .= "
                    SELECT \"$questionAnswerCol\" as id, COUNT(id) as c
                    FROM \"$question_answers_table_name\"
                    WHERE COALESCE(\"$questionAnswerCol\" NOT IN (\"$otherColumnStr\"),1)
                    GROUP BY \"$questionAnswerCol\" ";
            $first = false;
        }
        $query .= ') GROUP BY id HAVING id NOT NULL';

        return $query;

    }

}
