<?php


namespace App\Voting\AnonymizationMethods;


use App\Models\Election;
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
        Log::debug('Homomorphic afterVotingPhaseEnds > do nothing');

        // proceed to tally
        $election->cryptosystem->getClass()::tally($election);

    }

}
