<?php

namespace App\Http\Middleware;

use App\Models\PeerServer;
use Closure;
use Illuminate\Http\Request;

class ActAsPeer
{

    public const ActAsPeerServerKey = 'act_as_peer_server';

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // TODO if testing and if request contains "act_as_peer_server" then act as another peer
        if (in_array(config('app.env'), ['testing', 'local'])
            && $request->hasHeader(self::ActAsPeerServerKey)) {
            $peerID = intval($request->header(self::ActAsPeerServerKey));
            $me = PeerServer::findOrFail($peerID);

            /**
             * can be accessed with @see getCurrentServer()
             */
            app()->singleton('peer_server_me', function ($app) use ($me) {
                return $me;
            });
        }

        return $next($request);
    }
}
