<?php /** @noinspection PhpUnused */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTrusteesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trustees', function (Blueprint $table) {

            $table->id();

            $table->uuid('uuid')->unique();

            // TODO remove user trustees?
            $table->unsignedBigInteger('user_id')->nullable(); // Null if system trustee
            $table->foreign('user_id')->references('id')->on('users');

            $table->unsignedBigInteger('peer_server_id')->nullable(); // Null if system trustee
            $table->foreign('peer_server_id')->references('id')->on('peer_servers');

            $table->unsignedBigInteger('election_id');
            $table->foreign('election_id')->references('id')->on('elections');

            $table->text('public_key')->nullable();
            $table->text('private_key')->nullable();

            $table->string('public_key_hash')->nullable();
            $table->text('pok')->nullable();

            $table->text('polynomial')->nullable()->comment('NULL for all but itself'); // use to store our own polynomial
            $table->text('broadcast')->nullable();

            $table->text('share_sent')->nullable()->comment('NULL for self and user trustees');
            $table->text('share_received')->nullable()->comment('NULL for self and user trustees');

            $table->boolean('freeze_ready')->default(false)->comment('Used by coordinator');

            $table->boolean('qualified')->nullable();

            $table->timestamps();

            $table->unique(['user_id', 'election_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('trustees');
    }
}
