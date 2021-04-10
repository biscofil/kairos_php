<?php


namespace App\Http\Middleware;


use App\Models\Election;
use Closure;
use Illuminate\Http\Request;

class AuthenticateWithElectionCreatorJwt
{

    public const UserIdClaimName = 'sub';

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {

        // get election
        $election = $request->route('election');
        if (!$election instanceof Election) {
            abort(400);
        }

        $tokenStrReceived = $request->bearerToken();

        // get election creator peer and check token
        $userID = $election->peerServerAuthor->checkJwtTokenAndReturnUserID($tokenStrReceived);
        if (is_null($userID)) {
            abort(403);
        };

        // prevent users from specifying it manually
        $request->request->remove(self::UserIdClaimName);

        $request->request->add([self::UserIdClaimName => $userID]);

        return $next($request);

    }


}
