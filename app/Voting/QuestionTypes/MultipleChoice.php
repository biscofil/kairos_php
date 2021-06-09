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
     * @return string
     */
    public static function getTallyQuery(Question $question): string
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

        $tallyDatabase = $question->election->getTallyDatabase();

        $questionAnswerCols = $tallyDatabase->getAnswerColumnNames($question);

        $question_answers_table_name = $tallyDatabase->getOutputTableName();

        $innerQuery = '';
        $first = true;
        foreach ($questionAnswerCols as $questionAnswerCol) {
            if (!$first) {
                $innerQuery .= ' UNION ALL ';
            }
            $otherColumns = array_diff($questionAnswerCols, [$questionAnswerCol]);
            $whereDifferentFromOtherColumsClause = '';
            if (count($otherColumns)) {
                $whereDifferentFromOtherColumsClause = implode('","', $otherColumns);
                $whereDifferentFromOtherColumsClause = " WHERE COALESCE(\"$questionAnswerCol\" NOT IN (\"$whereDifferentFromOtherColumsClause\"),1) ";
            }
            $innerQuery .= "
                    SELECT \"$questionAnswerCol\" as id, COUNT(id) as c
                    FROM \"$question_answers_table_name\"
                    $whereDifferentFromOtherColumsClause
                    GROUP BY \"$questionAnswerCol\" ";
            $first = false;
        }

        $outerQuery = 'SELECT id, SUM(c) as count FROM (%s) GROUP BY id HAVING id IS NOT NULL';
        return sprintf($outerQuery, $innerQuery);

    }

}
