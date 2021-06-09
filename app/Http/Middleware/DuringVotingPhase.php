<?php

namespace App\Http\Middleware;

use App\Models\Election;
use Closure;
use Illuminate\Http\Request;

class DuringVotingPhase
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     * @throws \Exception
     */
    public function handle(Request $request, Closure $next)
    {

        $election = $request->route('election');

        if ($election && $election instanceof Election) {

            if (!(!is_null($election->voting_started_at) && is_null($election->voting_ended_at))) {
                throw new \Exception('Voting phase is not open');
            }

        }

        return $next($request);
    }
}
