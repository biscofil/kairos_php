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

            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description');
            $table->string('help_email');
            $table->string('info_url');

            $table->unsignedBigInteger('admin_id');
            $table->foreign('admin_id')->references('id')->on('users');

            // $table->enum('election_type',['election', 'referendum']);

            $table->boolean('is_private')->default(false);
            $table->boolean('is_featured')->default(false);

            $table->json('questions')->nullable();

            $table->text('public_key')->nullable();
            $table->text('private_key')->nullable();

            $table->enum('eligibility', ['open', 'email_list', 'category'])->default('open');
            $table->unsignedBigInteger('category_id')->nullable();
            $table->foreign('category_id')->references('id')->on('categories');

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
