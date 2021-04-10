<?php

namespace App\Http\Middleware;

use App\Models\Election;
use Closure;
use Illuminate\Http\Request;

/**
 * Class CanVote
 * @package App\Http\Middleware
 * @deprecated
 */
class CanVote
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

            $voter = $election->getAuthVoter();

            // auto-register this person if the election is openreg
//            if (is_null($voter) && $election->hasOpenRegistration()) {
//                $voter = $election->createVoter(getAuthUser());
//            }

            if (is_null($voter)) {
                return response()->json(["error" => "not a registered voter"], 403);
            }
        }

        return $next($request);
    }
}
