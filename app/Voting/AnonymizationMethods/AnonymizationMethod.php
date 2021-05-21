<?php


namespace App\Voting\AnonymizationMethods;


use App\Models\Election;

/**
 * Class AnonymizationMethod
 * @package App\Voting\AnonymizationMethods
 */
interface AnonymizationMethod
{

    /**
     * @param Election $election
     */
    public static function afterVotingPhaseEnds(Election &$election);

}
