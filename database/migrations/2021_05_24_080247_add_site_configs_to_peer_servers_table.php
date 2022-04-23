<?php

/** @noinspection PhpUnused */

use App\Models\PeerServer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSiteConfigsToPeerServersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('peer_servers', function (Blueprint $table) {

            $table->string('site_title')->nullable();
            $table->string('help_email_address')->nullable();

            $table->string('main_logo_url')->nullable();
            $table->string('footer_logo_url')->nullable();

            $table->boolean('show_user_info')->default(true); // TODO check
            $table->boolean('show_login_options')->default(true); // TODO check

            $table->text('welcome_message')->nullable();
        });

        $me = PeerServer::findOrFail(PeerServer::meID);
        $me->site_title = config('app.name');
        $me->help_email_address = 'help@example.com';
        $me->main_logo_url = asset('favicon.ico');
        $me->footer_logo_url = asset('favicon.ico');
        $me->welcome_message = 'Welcome to Kairos';
        $me->save();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('peer_servers', function (Blueprint $table) {
            $table->dropColumn('site_title');
        });
        Schema::table('peer_servers', function (Blueprint $table) {
            $table->dropColumn('help_email_address');
        });
        Schema::table('peer_servers', function (Blueprint $table) {
            $table->dropColumn('main_logo_url');
        });
        Schema::table('peer_servers', function (Blueprint $table) {
            $table->dropColumn('footer_logo_url');
        });
        Schema::table('peer_servers', function (Blueprint $table) {
            $table->dropColumn('show_user_info');
        });
        Schema::table('peer_servers', function (Blueprint $table) {
            $table->dropColumn('show_login_options');
        });
        Schema::table('peer_servers', function (Blueprint $table) {
            $table->dropColumn('welcome_message');
        });
    }
}
