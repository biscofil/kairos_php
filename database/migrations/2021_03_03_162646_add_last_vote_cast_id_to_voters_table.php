<?php /** @noinspection PhpUnused */

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
        Schema::table('voters', function (Blueprint $table) {

            $table->dropForeign(['last_vote_cast_id']);
            $table->dropColumn('last_vote_cast_id');

        });
    }
}
