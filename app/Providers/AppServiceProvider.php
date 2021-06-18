<?php

namespace App\Providers;

use App\Models\PeerServer;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{

//    public const ActAsPeerServerKey = 'act_as_peer_server';

    /**
     * Register any application services.
     * @return void
     * @noinspection PhpMissingParentCallCommonInspection
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {

        Schema::defaultStringLength(191);

        $me = null;
        try {

            // TODO if testing and if request contains "act_as_peer_server" then act as another peer
//            if (in_array(config('app.env'), ['testing', 'local'])
//                && $request->hasHeader(self::ActAsPeerServerKey)) {
//                $peerID = intval($request->header(self::ActAsPeerServerKey));
//                Log::warning("Acting as peer server $peerID");
//                $me = PeerServer::findOrFail($peerID);
//            } else {
            $me = PeerServer::me(false);
//            }

        } catch (\Exception $e) {
            Log::warning('Failed getCurrentServer() in AppServiceProvider');
        }
        // take the RSA keypair of the current server for JWT auth

        if ($me) {
            config(['app.locale' => $me->locale]);
//            config(['app.timezone' => $me->timezone]);
            if ($me->jwt_public_key && $me->jwt_secret_key) {
                config(['jwt.keys.private' => $me->jwt_secret_key->toString()]);
                config(['jwt.keys.public' => $me->jwt_public_key->toString()]);
            }
        }

        /**
         * can be accessed with @see getCurrentServer()
         */
        $this->app->singleton('peer_server_me', function ($app) use ($me) {
            return $me;
        });


    }
}
