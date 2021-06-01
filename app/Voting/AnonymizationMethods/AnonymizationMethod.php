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

    /**
     * @param \App\Models\Election $election
     * @return array
     */
    public static function getProofs(Election &$election) : array;

}
