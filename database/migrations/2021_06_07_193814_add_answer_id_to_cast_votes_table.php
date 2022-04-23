<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAnswerIdToCastVotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cast_votes', function (Blueprint $table) {

            $table->unsignedBigInteger('answer_id')->nullable();
            $table->foreign('answer_id')->references('id')->on('answers');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");
        if ($driver !== "sqlite") {
            Schema::table('cast_votes', function (Blueprint $table) {
                $table->dropForeign(['answer_id']);
            });
        }
        Schema::table('cast_votes', function (Blueprint $table) {
            $table->dropColumn('answer_id');
        });
    }
}
