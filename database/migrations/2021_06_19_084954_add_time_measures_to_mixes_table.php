<?php

/** @noinspection PhpUnused */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTimeMeasuresToMixesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('mixes', function (Blueprint $table) {
            $table->unsignedInteger('mixes_generated_in')->nullable();
            $table->unsignedInteger('proofs_generated_in')->nullable();
            $table->unsignedInteger('verified_in')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('mixes', function (Blueprint $table) {
            $table->dropColumn('verified_in');
        });
        Schema::table('mixes', function (Blueprint $table) {
            $table->dropColumn('proofs_generated_in');
        });
        Schema::table('mixes', function (Blueprint $table) {
            $table->dropColumn('mixes_generated_in');
        });
    }
}
