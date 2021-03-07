<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCastVotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cast_votes', function (Blueprint $table) {

            $table->id();

            $table->unsignedBigInteger('voter_id');
            $table->foreign('voter_id')->references('id')->on('voters');

            $table->ipAddress('ip');

            $table->text('vote');
            $table->string('hash');

            $table->timestamp('cast_at');
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('invalidated_at')->nullable();

            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cast_votes');
    }
}
