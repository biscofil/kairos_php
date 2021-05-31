<?php /** @noinspection PhpUnused */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMixesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mixes', function (Blueprint $table) {

            $table->id();

            $table->unsignedInteger('round');

            $table->unsignedBigInteger('trustee_id');
            $table->foreign('trustee_id')->references('id')->on('trustees');

            $table->timestamps();

        });

        Schema::table('mixes', function (Blueprint $table) {

            $table->unsignedBigInteger('previous_mix_id')->after('id')->nullable();
            $table->foreign('previous_mix_id')->references('id')->on('mixes');

        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('mixes');
    }
}
