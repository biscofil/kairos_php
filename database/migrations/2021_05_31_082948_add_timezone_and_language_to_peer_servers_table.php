<?php

use App\Models\PeerServer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTimezoneAndLanguageToPeerServersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('peer_servers', function (Blueprint $table) {

            $table->string('timezone')->nullable();
            $table->string('locale')->nullable();

        });

        $me = getCurrentServer();
        $me->timezone = config('app.timezone');
        $me->locale = config('app.locale');
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
            $table->dropColumn('timezone');
            $table->dropColumn('locale');
        });
    }
}
