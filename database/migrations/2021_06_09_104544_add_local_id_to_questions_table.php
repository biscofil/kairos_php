<?php

use App\Models\Election;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLocalIdToQuestionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('questions', function (Blueprint $table) {

            $table->unsignedSmallInteger('local_id')->nullable();
            $table->unique(['id', 'local_id']);
        });

        Election::all()->each(function (Election $election) {
            foreach ($election->questions as $idx => $question) {
                $question->local_id = $idx + 1;
                $question->save();
            }
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->unsignedSmallInteger('local_id')->nullable(false)->change();
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
            $table->dropUnique(['id', 'local_id']);
        });
        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn('local_id');
        });
    }
}
