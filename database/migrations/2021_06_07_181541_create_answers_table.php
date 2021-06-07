<?php

use App\Models\Answer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateAnswersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('answers', function (Blueprint $table) {

            $table->id();

            $table->unsignedBigInteger('question_id');
            $table->foreign('question_id')->references('id')->on('questions');

            $table->unsignedSmallInteger('local_id');
            $table->unique(['question_id', 'local_id']);

            $table->string('answer');

            $table->string('url')->nullable();

            $table->text('attributes')->nullable(); // json array

            $table->timestamps();

        });

        // move answers from question table to current table

        DB::table('questions')->whereNotNull('answers')->get()->each(function ($question) {
            foreach (json_decode($question->answers) as $idx => $answer) {
                /** @var \App\Models\Question $question */
                $a = new Answer();
                $a->question_id = $question->id;
                $a->local_id = $idx + 1;
                $a->answer = $answer->answer;
                $a->url = $answer->url;
                unset($answer->answer);
                unset($answer->url);
                $a->attributes = (array)$answer;
                $a->save();
            }
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn('answers');
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

        Schema::table('questions', function (Blueprint $table) {
            $table->text('answers'); // json array
        });

        // move answers from current table to question table
        $questionAnswers = [];
        foreach (DB::table('answers')->get() as $answer) {
            if (!array_key_exists($answer->question_id, $questionAnswers)) {
                $questionAnswers[$answer->question_id] = [];
            }
            $questionAnswers[$answer->question_id][] = array_merge([
                'answer' => $answer->answer,
                'url' => $answer->url
            ], json_decode($answer->attributes, true));
        };
        foreach ($questionAnswers as $questionID => $answers) {
            DB::table('questions')->where('id', '=', $questionID)->update(['answers' => $answers]);
        }

        Schema::dropIfExists('answers');
    }
}
