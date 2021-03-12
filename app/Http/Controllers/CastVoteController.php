<?php

namespace App\Http\Controllers;

use App\Crypto\EGCiphertext;
use App\Models\CastVote;
use App\Models\Election;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use phpseclib3\Math\BigInteger;

class CastVoteController extends Controller
{

    /**
     * @return Response
     */
    public function index()
    {
        //
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
            'vote' => ['required', 'array'], // "alpha,beta" in hex
            'vote.alpha' => ['required', 'string'], // "alpha,beta" in hex
            'vote.beta' => ['required', 'string'], // "alpha,beta" in hex
        ]);

        // TODO Cache::put(); put encrypted vote in cache
        // TODO encrypted_vote = request.session['encrypted_vote']

        $voter = $election->getAuthVoter();

        $vote = new EGCiphertext(
            $election->public_key,
            new BigInteger($data['vote']['alpha'], 16),
            new BigInteger($data['vote']['beta'], 16)
        );

        $vote_fingerprint = base64_encode(hash('sha256', "$ vote")); // TODO

        $cast_vote = new CastVote(); // vote = legacy/EncryptedVote obj
        $cast_vote->vote = $vote;
        $cast_vote->voter()->associate($voter);
        $cast_vote->hash = $vote_fingerprint;
        $cast_vote->ip = \request()->ip();
        $cast_vote->save();

        $voter->lastVoteCast()->associate($cast_vote)->save();

//        VerifyVote::dispatch($cast_vote);

        return $cast_vote->withoutRelations();

    }

    /**
     * Display the specified resource.
     *
     * @param CastVote $castVote
     * @return Response
     */
    public function show(CastVote $castVote)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param CastVote $castVote
     * @return Response
     */
    public function update(Request $request, CastVote $castVote)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param CastVote $castVote
     * @return Response
     */
    public function destroy(CastVote $castVote)
    {
        //
    }
}
