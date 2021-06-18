<?php

use App\Models\Mix;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUuidColumnToMixesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     * @throws \Exception
     */
    public function up()
    {
        Schema::table('mixes', function (Blueprint $table) {

            $table->uuid('uuid')->nullable();

        });

        Mix::all()->each(function (Mix $mix) {
            $mix->uuid = Mix::getNewUUID()->string;
            $mix->save();
        });

        Schema::table('mixes', function (Blueprint $table) {

            $table->uuid('uuid')->unique()->nullable(false)->change();

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

            $table->dropUnique(['uuid']);
            $table->dropColumn('uuid');

        });
    }
}
