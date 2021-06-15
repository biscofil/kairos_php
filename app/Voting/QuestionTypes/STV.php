<?php


namespace App\Voting\QuestionTypes;


use App\Models\Answer;
use App\Models\Question;
use App\Voting\AnonymizationMethods\MixNets\TallyDatabase;
use Illuminate\Database\Schema\Blueprint;

class STV extends QuestionType
{

    /**
     * @param \App\Voting\AnonymizationMethods\MixNets\TallyDatabase $tallyDatabase
     * @param \App\Models\Question $question
     */
    public static function createAnswersTable(TallyDatabase $tallyDatabase, Question $question): void
    {
        $question_answers_table_name = TallyDatabase::getQuestionAnswersTableName($question);
        $tallyDatabase->connection->getSchemaBuilder()->dropIfExists($question_answers_table_name);

        $tallyDatabase->connection->getSchemaBuilder()->create($question_answers_table_name, function (Blueprint $table) {
            $table->unsignedInteger('id')->primary();
            $table->boolean('excluded');
            $table->string('answer');
            $table->string('url');
        });

        $question->answers->each(function (Answer $answer) use ($tallyDatabase, $question_answers_table_name) {
            $tallyDatabase->connection->table($question_answers_table_name)->insert([
                'id' => $answer->local_id,
                'excluded' => false,
                'answer' => $answer->answer,
                'url' => $answer->url
            ]);
        });
    }

    /**
     * TODO
     * @param \App\Models\Question $question
     * @return string
     */
    public static function getTallyQuery(Question $question): string
    {
        $questionAnswerCols = TallyDatabase::getAnswerColumnNames($question);
        foreach ($questionAnswerCols as $questionAnswerCol) {
            // COALESCE (id inner joined)
        }
        return '';
    }
}
