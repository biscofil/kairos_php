<?php

/** @noinspection PhpUnused */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLastVoteCastIdToVotersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('voters', function (Blueprint $table) {

            $table->unsignedBigInteger('last_vote_cast_id')->nullable();
            $table->foreign('last_vote_cast_id')->references('id')->on('cast_votes');
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
            Schema::table('voters', function (Blueprint $table) {
                $table->dropForeign(['last_vote_cast_id']);
            });
        }
        Schema::table('voters', function (Blueprint $table) {
            $table->dropColumn('last_vote_cast_id');
        });
    }
}
