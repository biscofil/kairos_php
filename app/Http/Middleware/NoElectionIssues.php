<?php

namespace App\Http\Middleware;

use App\Models\Election;
use Closure;
use Illuminate\Http\Request;

class NoElectionIssues
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

        $election = $request->route('election');

        if ($election && $election instanceof Election) {
            if (count($election->issues)) {
                return response([
                    "error" => "there are issues",
                    "issues" => $election->issues
                ], 400);
            }
        }

        return $next($request);
    }
}
