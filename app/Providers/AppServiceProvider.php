<?php

namespace App\Providers;

use App\Models\PeerServer;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
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

        // can be accessed with app('peer_server_me')
//        $this->app->singleton('peer_server_me', function ($app) {
//            return PeerServer::me();
//        });

        // take the RSA keypair of the current server for JWT auth
        $me = PeerServer::me(false);
        if ($me && $me->jwt_public_key && $me->jwt_secret_key) {
            config(['jwt.keys.private' => $me->jwt_secret_key->toString()]);
            config(['jwt.keys.public' => $me->jwt_public_key->toString()]);
        }

    }
}
