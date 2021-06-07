<?php

namespace App\Http\Controllers;

use App\Http\Middleware\AuthenticateWithElectionCreatorJwt;
use App\Models\CastVote;
use App\Models\Election;
use App\Voting\CryptoSystems\CipherText;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Vuetable\Vuetable;

/**
 * Class CastVoteController
 * @package App\Http\Controllers
 */
class CastVoteController extends Controller
{

    /**
     * @param \App\Models\Election $election
     * @return \Illuminate\Pagination\LengthAwarePaginator
     * @throws \Exception
     */
    public function index(Election $election): LengthAwarePaginator
    {
        return Vuetable::of($election->votes()->getQuery())->make();
    }

    /**
     * Cast a vote, store without re-ecnrypting it for not
     * @param Election $election
     * @param Request $request
     * @return array
     */
    public function store(Election $election, Request $request): array
    {
        $data = $request->validate([
            /**
             * claim added by @see AuthenticateWithElectionCreatorJwt::handle()
             */
            AuthenticateWithElectionCreatorJwt::UserIdClaimName => ['required']
        ]);

        $userID = $request->get(AuthenticateWithElectionCreatorJwt::UserIdClaimName);

        /** @var CipherText $skClass */
        $skClass = $election->cryptosystem->getClass()::getCipherTextClass();

        $votes = $skClass::validateAndStoreVotes($userID, $election, $request);

//        VerifyVote::dispatch($cast_vote);

        return $votes; //->withoutRelations();

    }

}
