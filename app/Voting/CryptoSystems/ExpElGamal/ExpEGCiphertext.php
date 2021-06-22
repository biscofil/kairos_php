<?php


namespace App\Voting\CryptoSystems\ExpElGamal;


use App\Models\CastVote;
use App\Models\Election;
use App\Voting\CryptoSystems\ElGamal\EGCiphertext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Class ExpEGCiphertext
 * @package App\Voting\CryptoSystems\ExpElGamal
 */
class ExpEGCiphertext extends EGCiphertext
{

    use BelongsToExpElgamal;

    /**
     * @param int $userID
     * @param \App\Models\Election $election
     * @param \Illuminate\Http\Request $request
     * @return CastVote[]
     * @throws \Illuminate\Validation\ValidationException
     * @noinspection PhpMissingParentCallCommonInspection
     */
    public static function validateAndStoreVotes(int $userID, Election $election, Request $request): array
    {
        $data = Validator::make($request->all(), [
            'votes' => ['required', 'array'],
            'votes.*' => ['required', 'array'],
            'votes.*.answer_id' => ['required', 'integer', 'exists:answers,id'], // TODO filter by question
            'votes.*.alpha' => ['required', 'string', 'regex:/^([A-Fa-f0-9]+)$/'],
            'votes.*.beta' => ['required', 'string', 'regex:/^([A-Fa-f0-9]+)$/'],
        ])->validated();

        $cast_votes = [];
        foreach ($data['votes'] as $voteArray) {

            $vote = self::fromArray($voteArray, $election->public_key);

            $cast_vote = new CastVote();
            $cast_vote->vote = $vote;
            $cast_vote->answer_id = $voteArray['answer_id'];
            $cast_vote->election_id = $election->id;
            $cast_vote->voter_id = $userID; // TODO user ID vs voter ID
            $cast_vote->hash = $vote->getFingerprint();
            $cast_vote->ip = $request->ip();
            $cast_vote->save();

            $cast_votes[] = $cast_vote;
        }

        return $cast_votes;

    }

    /**
     * @param \App\Voting\CryptoSystems\ExpElGamal\ExpEGCiphertext $b
     * @return self
     */
    public function homomorphicSum(ExpEGCiphertext $b): self
    {
        return new static(
            $this->pk,
            mod($this->alpha->multiply($b->alpha), $this->pk->parameterSet->p),
            mod($this->beta->multiply($b->beta), $this->pk->parameterSet->p)
        );
    }

}
