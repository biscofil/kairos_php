<?php

namespace App\Http\Controllers;

use App\Models\CastVote;
use App\Models\Election;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

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
     * @return Response
     */
    public function store(Election $election, Request $request)
    {
        $data = $request->validate([
           'vote' => ['required', 'string']
        ]);

        // TODO put encrypted vote in cache
        // TODO Cache::put();
        // TODO encrypted_vote = request.session['encrypted_vote']
        // TODO vote_fingerprint = cryptoutils.hash_b64(encrypted_vote)

        $voter = $election->getAuthVoter();

        $vote = $data['vote'];
        $vote_fingerprint = base64_encode(hash('sha256', $vote));

        $cast_vote = new CastVote(); // vote = legacy/EncryptedVote obj
        $cast_vote->vote = $vote;
        $cast_vote->voter()->associate($voter);
        $cast_vote->hash = $vote_fingerprint;
        $cast_vote->cast_at = now();
        $cast_vote->ip = \request()->ip();
        $cast_vote->save();

        $voter->lastVoteCast()->associate($cast_vote)->save();

//        VerifyVote::dispatch($cast_vote);

        return [];

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
