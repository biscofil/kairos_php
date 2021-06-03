<?php

use App\Models\Election;
use App\Models\Question;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQuestionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::dropIfExists('questions');

        Schema::create('questions', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('election_id');
            $table->foreign('election_id')->references('id')->on('elections');

            $table->string('question_type');

            $table->string('question');

            $table->text('answers'); // json array

            $table->unsignedSmallInteger('min');
            $table->unsignedSmallInteger('max');

            $table->timestamps();
        });

        /** @var Election $election */
        foreach (Election::all() as $election) {

            if (is_array($election->questions)) {
                foreach ($election->questions as $question) {
                    $q = new Question();
                    $q->election_id = $election->id;
                    $q->min = $question['min'];
                    $q->max = $question['max'];
                    $q->question = $question['question'];
                    $q->answers = $question['answers'];
                    $q->question_type = 'multiple_choice';
                    $q->save();
                }
            }

        }

        Schema::table('elections', function (Blueprint $table) {
            $table->dropColumn('questions');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

        Schema::table('elections', function (Blueprint $table) {
            $table->text('questions')->nullable()->after('is_featured');
        });

        $electionQuestions = [];
        foreach (Question::all() as $question) {
            if (!array_key_exists($question->election_id, $electionQuestions)) {
                $electionQuestions[$question->election_id] = [];
            }
            $electionQuestions[$question->election_id][] = [
                'min' => $question->min,
                'max' => $question->max,
                'question' => $question->question,
                'answers' => $question->answers,
                'result_type' => $question->question_type,
            ];
        }
        foreach ($electionQuestions as $electionID => $questions) {
            Election::where('id', '=', $electionID)->update([
                'questions' => json_encode($questions)
            ]);
        }

        Schema::dropIfExists('questions');
    }
}
