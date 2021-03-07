<?php

namespace App\Http\Middleware;

use App\Models\Election;
use Closure;
use Illuminate\Http\Request;

class ElectionNotFrozen
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $election = $request->route('election');

        if ($election && $election instanceof Election) {

            if (!is_null($election->frozen_at)) {
                return response(["error" => "election is frozen"], 403);
            }

        }

        return $next($request);
    }
}
