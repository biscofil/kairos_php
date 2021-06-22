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
     * TODO remove in prod, test only
     */
    public function fill_test_votes(Election $election): array
    {
        $ok = 0;
        for ($i = 0; $i < 50; $i++) {

            try {
                $user = User::factory()->create();
                $voter = new Voter();
                $voter->user_id = $user->id;
                $voter->election_id = $election->id;
                $voter->save();

                // generate a JSON vote structure
                $votePlain = $election->questions->map(function (Question $question) {
                    $min = $question->min;
                    $max = $question->max;
                    $idxs = [1 => 1, 2 => 2, 3 => 3]; // TODO
                    return rand(0, $max) === 0
                        ? []
                        : (array)array_rand($idxs, rand($min, $max));
                })->toArray();

//            dump($votePlain);

                $plaintext = Small_JSONBallotEncoding::encode($votePlain, EGPlaintext::class);
                $cipher = $election->public_key->encrypt($plaintext);

                $castVote = new CastVote();
                $castVote->vote = $cipher;
                $castVote->voter_id = $voter->id;
                $castVote->election_id = $election->id;
                $castVote->hash = $cipher->getFingerprint();
                $castVote->ip = request()->ip();
                $castVote->save();
                $ok++;

            } catch (\Exception $e) {

            }

        }

        return ['ok' => $ok];

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
