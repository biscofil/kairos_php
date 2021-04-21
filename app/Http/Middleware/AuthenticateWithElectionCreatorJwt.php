<?php


namespace App\Http\Middleware;


use App\Models\Election;
use Closure;
use Illuminate\Auth\AuthenticationException;
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
     * @throws \Illuminate\Auth\AuthenticationException
     */
    public function handle(Request $request, Closure $next)
    {

        // get election
        $election = $request->route('election');
        if (!$election instanceof Election) {
            abort(400);
        }

        $tokenStrReceived = $request->bearerToken();
        if (is_null($tokenStrReceived)) {
            throw new AuthenticationException();
        };

        // get election creator peer and check token
        $userID = $election->peerServerAuthor->checkJwtTokenAndReturnUserID($tokenStrReceived);
        if (is_null($userID)) {
            throw new AuthenticationException();
        };

        // prevent users from specifying it manually
        $request->request->remove(self::UserIdClaimName);

        $request->request->add([self::UserIdClaimName => $userID]);

        return $next($request);

    }


}
