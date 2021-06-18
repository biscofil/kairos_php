<?php


namespace App\Voting\AnonymizationMethods;


use App\Models\Election;
use App\Models\Trustee;

/**
 * Class AnonymizationMethod
 * @package App\Voting\AnonymizationMethods
 */
interface AnonymizationMethod
{

    /**
     * @param Election $election
     * @param \App\Models\Trustee|null $trusteeRunningCode
     */
    public static function afterVotingPhaseEnds(Election &$election, ?Trustee $trusteeRunningCode = null);

    /**
     * @param \App\Models\Election $election
     * @return array
     */
    public static function getProofs(Election &$election) : array;

    /**
     * @param \App\Models\Election $election
     * @return mixed
     */
    public static function tally(Election &$election);

    /**
     * @param \App\Models\Election $election
     * @return bool
     */
    public static function preFreeze(Election &$election) : bool;

}
