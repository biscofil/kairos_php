<?php


namespace App\Voting\AnonymizationMethods;


use App\Models\Answer;
use App\Models\CastVote;
use App\Models\Election;
use App\Models\Question;
use App\Voting\CryptoSystems\ExpElGamal\ExpEGCiphertext;
use Illuminate\Support\Facades\Log;

/**
 * Class HomomorphicAnonymizationMethod
 * @package App\Voting\AnonymizationMethods
 */
class HomomorphicAnonymizationMethod implements AnonymizationMethod
{

    /**
     * @param \App\Models\Election $election
     */
    public static function afterVotingPhaseEnds(Election &$election)
    {
        Log::debug('Homomorphic afterVotingPhaseEnds > proceed to tally');

        $election->tally();

    }

    /**
     * @param \App\Models\Election $election
     */
    public static function tally(Election &$election)
    {

        $election->questions->each(function (Question $question) use ($election) {

            $question->tally_result = $question->answers->map(function (Answer $answer) use ($election) {

                /** @var ExpEGCiphertext $encryptedAnserVoteCount */
                $encryptedAnserVoteCount = $answer->votes->reduce(function (?ExpEGCiphertext $carry, CastVote $vote) {
                    /** @var ExpEGCiphertext $voteCiphertext */
                    $voteCiphertext = $vote->vote;
                    if (is_null($carry)) {
                        return $voteCiphertext;
                    }
                    return $voteCiphertext->homomorphicSum($carry);
                }, null);

                $count = $election->private_key->decrypt($encryptedAnserVoteCount); // int

                // TODO DLOG?!!!!!

                return ['answer_id' => $answer->id, 'count' => $count];
            });

            $question->save();

        });

    }

    /**
     * @param \App\Models\Election $election
     * @return array
     */
    public static function getProofs(Election &$election): array
    {
        return []; // TODO
    }

}
