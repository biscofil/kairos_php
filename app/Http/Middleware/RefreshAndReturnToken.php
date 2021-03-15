<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class RefreshAndReturnToken
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {

        /** @var Response $out */
        $out = $next($request);

        $newToken = null;
        $tokenExpiresIn = null;

        if (auth('api')->check()) {
            $newToken = auth('api')->refresh();
            $tokenExpiresIn = auth('api')->factory()->getTTL() * 60;
        }

        return $out->withHeaders([
            "access_token" => $newToken,
            "access_token_expires_in" => $tokenExpiresIn,
        ]);
    }

}
