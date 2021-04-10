<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateElectionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('elections', function (Blueprint $table) {

            $table->id();

            $table->uuid('uuid')->unique();
            $table->string('slug')->unique();

            $table->unsignedBigInteger('peer_server_id')->nullable(); // Null if created by this server
            $table->foreign('peer_server_id')->references('id')->on('peer_servers');

            $table->string('name');
            $table->text('description');
            $table->string('help_email');
            $table->string('info_url');

            $table->unsignedBigInteger('admin_id')->nullable(); // null if sent (P2P)
            $table->foreign('admin_id')->references('id')->on('users');

            $table->boolean('is_private')->default(false);
            $table->boolean('is_featured')->default(false);

            $table->text('questions')->nullable();

            $table->string('cryptosystem', 20);
            $table->unsignedSmallInteger('delta_t_l')->default(0);
            $table->text('public_key')->nullable();
            $table->text('private_key')->nullable();

            $table->boolean('use_voter_alias')->default(false);
            $table->boolean('use_advanced_audit_features')->default(false);
            $table->boolean('randomize_answer_order')->default(false);

            $table->timestamp('registration_starts_at')->nullable();

            $table->timestamp('voting_starts_at')->nullable();
            $table->timestamp('voting_started_at')->nullable();
            $table->timestamp('voting_extended_until')->nullable();
            $table->timestamp('voting_end_at')->nullable();
            $table->timestamp('voting_ended_at')->nullable();

            $table->timestamp('tallying_started_at')->nullable();
            $table->timestamp('tallying_finished_at')->nullable();
            $table->timestamp('tallying_combined_at')->nullable();

            $table->timestamp('results_released_at')->nullable();

            $table->timestamp('frozen_at')->nullable();
            $table->timestamp('archived_at')->nullable();

            // TODO voter hash
            // TODO encrypted tally
            // TODO result
            // TODO result_proof

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
        Schema::dropIfExists('elections');
    }
}
