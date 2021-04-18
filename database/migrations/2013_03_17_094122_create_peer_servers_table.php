<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePeerServersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('peer_servers', function (Blueprint $table) {

            $table->id();

            $table->string('name');

            $table->ipAddress('ip');

            $table->point('gps')->nullable();
            $table->string('country_code',5)->nullable();

            $table->text('jwt_public_key')->nullable();
            $table->text('token')->nullable();

            $table->timestamps();

        });

        Artisan::call('db:seed', ['--class' => 'PeerServersTableSeeder', '--force' => 1]);

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('peer_servers');
    }
}
