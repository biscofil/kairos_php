<?php


namespace App\Voting\QuestionTypes;


use App\Models\Answer;
use App\Models\Question;
use App\Voting\AnonymizationMethods\MixNets\TallyDatabase;
use drupol\phpermutations\Generators\Permutations;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Class MultipleChoice
 * @package App\Voting\QuestionTypes
 * Ignores order
 */
class MultipleChoice extends QuestionType implements SupportsHomomorhpicEncryption
{

    /**
     * @param \App\Voting\AnonymizationMethods\MixNets\TallyDatabase $tallyDatabase
     * @param \App\Models\Question $question
     */
    public static function createAnswersTable(TallyDatabase $tallyDatabase, Question $question): void
    {

        $question_answers_table_name = TallyDatabase::getQuestionAnswersTableName($question);

        try {
            $tallyDatabase->connection->getSchemaBuilder()->dropIfExists($question_answers_table_name);
        } catch (Throwable $e) {
            Log::error($e->getMessage());
        }

        $tallyDatabase->connection->getSchemaBuilder()->create($question_answers_table_name, function (Blueprint $table) {
            $table->unsignedInteger('id')->primary();
            $table->string('answer');
            $table->string('url');
        });

        $question->answers->each(function (Answer $answer) use ($tallyDatabase, $question_answers_table_name) {
            $tallyDatabase->connection->table($question_answers_table_name)->insert([
                'id' => $answer->local_id,
                'answer' => $answer->answer,
                'url' => $answer->url
            ]);
        });
    }

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

        $questionAnswerCols = TallyDatabase::getAnswerColumnNames($question);

        $question_answers_table_name = TallyDatabase::getOutputTableName($question->election);

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

    /**
     * @param \App\Models\Question $question
     * @return array
     */
    public static function generateAllCombinations(Question $question)
    {
        $out = [];
        $list = $question->answers()->pluck('local_id')->toArray();
        for ($i = $question->min; $i <= $question->max; $i++) { // max(1, $question->min)
            if ($i === 0) {
                $out[] = [];
                continue;
            }
            $permutations = new Permutations($list, $i);
            $out = array_merge($out, $permutations->toArray());
        }
        return $out;
    }

    /**
     * check the given order matches the traditional order
     * @param int[][] $ballot
     * @return bool
     */
    public static function isDecryptedBallotValid(array $ballot) : bool
    {
        foreach ($ballot as $questionAnswers){
            $sorted = $questionAnswers;
            sort($sorted);
            if($sorted !== $questionAnswers){
                // if the user has submitted an invalid order to make the ballot recognizible, ignore the ballot
                return false;
            }
        }
        return true;
    }

}
