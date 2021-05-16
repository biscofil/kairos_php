<?php

namespace App\Http\Controllers;

use App\Http\Middleware\AuthenticateWithElectionCreatorJwt;
use App\Models\CastVote;
use App\Models\Election;
use App\Voting\CryptoSystems\CipherText;
use Illuminate\Http\Request;
use Vuetable\Vuetable;

/**
 * Class CastVoteController
 * @package App\Http\Controllers
 */
class CastVoteController extends Controller
{

    /**
     * @return \Illuminate\Pagination\LengthAwarePaginator
     * @throws \Exception
     */
    public function index(Election $election)
    {
        return Vuetable::of($election->votes()->getQuery())->make();
    }

    /**
     * Cast a vote, store without re-ecnrypting it for not
     * @param Election $election
     * @param Request $request
     * @return CastVote
     */
    public function store(Election $election, Request $request): CastVote
    {
        $data = $request->validate([
            'vote' => ['required', 'array'],
            /**
             * claim added by @see AuthenticateWithElectionCreatorJwt::handle()
             */
            AuthenticateWithElectionCreatorJwt::UserIdClaimName => ['required']
        ]);

        /** @var CipherText $skClass */
        $skClass = $election->cryptosystem->getCryptoSystemClass()::getCipherTextClass();

        $voteArray = $skClass::validate($data['vote']);

        $userID = $request->get(AuthenticateWithElectionCreatorJwt::UserIdClaimName);

        $vote = $skClass::fromArray($voteArray, $election->public_key);

        $cast_vote = new CastVote();
        $cast_vote->vote = $vote;
        $cast_vote->election_id = $election->id;
        $cast_vote->voter_id = $userID; // TODO user ID vs voter ID
        $cast_vote->hash = $vote->getFingerprint();
        $cast_vote->ip = \request()->ip();
        $cast_vote->save();

//        VerifyVote::dispatch($cast_vote);

        return $cast_vote->withoutRelations();

    }

}
