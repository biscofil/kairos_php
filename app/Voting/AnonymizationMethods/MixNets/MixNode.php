<?php


namespace App\Voting\AnonymizationMethods\MixNets;


use App\Jobs\GenerateMix;
use App\Models\Election;
use App\Models\Trustee;
use App\Voting\AnonymizationMethods\AnonymizationMethod;
use App\Voting\CryptoSystems\PublicKey;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Class MixNet
 * @package App\Voting\MixNets
 */
abstract class MixNode implements AnonymizationMethod
{

    // ########################################################################

    /**
     * @return string|\App\Voting\AnonymizationMethods\MixNets\MixWithShadowMixes
     */
    abstract public static function getMixWithShadowMixesClass(): string;

    /**
     * @return string|\App\Voting\AnonymizationMethods\MixNets\Mix
     */
    abstract public static function getMixClass(): string;

    /**
     * @return string|\App\Voting\AnonymizationMethods\MixNets\MixNodeParameterSet
     */
    abstract public static function getParameterSetClass(): string;

    // ########################################################################

    /**
     * @param \App\Voting\AnonymizationMethods\MixNets\Mix $inputMix
     * @param MixNodeParameterSet $parameterSet
     * @param \App\Models\Trustee $trusteeRunningMix
     * @return Mix
     * @noinspection PhpMissingParamTypeInspection
     */
    abstract public static function forward(Mix $inputMix, MixNodeParameterSet $parameterSet, Trustee $trusteeRunningMix): Mix;

    /**
     * @param \App\Voting\CryptoSystems\PublicKey $public_key
     * @param int $cipherTextCount
     * @return \App\Voting\AnonymizationMethods\MixNets\MixNodeParameterSet
     */
    abstract public static function getPrimaryMixParameterSet(PublicKey $public_key, int $cipherTextCount): MixNodeParameterSet;

    /**
     * @param \App\Voting\CryptoSystems\PublicKey $public_key
     * @param int $cipherTextCount
     * @return \App\Voting\AnonymizationMethods\MixNets\MixNodeParameterSet
     */
    abstract public static function getShadowMixParameterSet(PublicKey $public_key, int $cipherTextCount): MixNodeParameterSet;

    // ########################################################################

    /**
     * @param \App\Models\Election $election
     * @return bool
     */
    public static function preFreeze(Election &$election): bool
    {
        // Create a sqlite database with plaintexts ballots
        return $election->getTallyDatabase()->setupOutputTables();
    }

    /**
     * @param \App\Models\Election $election
     */
    public static function afterSuccessfulMixProcess(Election &$election): void
    {
        // do nothing
    }

    /**
     * @param \App\Models\Election $election
     * @param \App\Voting\CryptoSystems\Plaintext[] $plainTexts
     * @return bool
     */
    public static function storePlainTextBallots(Election &$election, array $plainTexts): bool
    {
        return $election->getTallyDatabase()->insertPlainTextBallots($plainTexts);
    }

    /**
     * @param \App\Models\Election $election
     * @return void
     */
    public static function tally(Election &$election)
    {
        $election->getTallyDatabase()->tally(); // only for mixnets
    }

    /**
     * @param \App\Models\Election $election
     * @throws \Exception
     */
    public static function runTally(Election &$election)
    {
        $election->tally();
    }

    // ########################################################################

    /**
     * @param \App\Models\Election $election
     * @return array
     */
    public static function getProofs(Election &$election): array
    {
        $peerServerTrusteeIDs = $election->trustees()->peerServers()
            ->select('trustees.id')
            ->pluck('trustees.id')
            ->toArray();

        /** @var \App\Models\Mix[]|Collection $mixes */
        $mixes = \App\Models\Mix::query()
            ->whereIn('trustee_id', $peerServerTrusteeIDs)
            ->orderByDesc('mixes.id')
            ->get();

        return [
            'mixes' => $mixes //self::createHierarchy($mixes, null);
        ];
    }

    /**
     * TODO optimize
     * @param \Illuminate\Support\Collection $mixes
     * @param int|null $parent
     * @return \Illuminate\Support\Collection
     */
    private static function createHierarchy(Collection &$mixes, ?int $parent): Collection
    {
        return $mixes->filter(function (\App\Models\Mix $mix) use ($parent) {
            return $mix->previous_mix_id === $parent;
        })->map(function (\App\Models\Mix $mix) use ($mixes, $parent) {
            $mixA = $mix->toArray();
            $mixA['derived'] = self::createHierarchy($mixes, $mix->id);
            return $mixA;
        })->values();
    }

    /**
     * @param \App\Models\Election $election
     * @param \App\Models\Trustee|null $trusteeRunningCode
     */
    public static function afterVotingPhaseEnds(Election &$election, ?Trustee $trusteeRunningCode = null)
    {
        Log::debug('MixNode afterVotingPhaseEnds');

        $trusteeRunningCode = $trusteeRunningCode ?? $election->getTrusteeFromPeerServer(getCurrentServer());

        if ($trusteeRunningCode) {
            // current server is a peer
            Log::debug('afterVotingPhaseEnds > Current server is a trustee');

            // if ($meTrustee->getPeerServerIndex() === 1) { // TODO check
            if ($trusteeRunningCode->accepts_ballots) { // TODO check
                // current server is a peer

                Log::debug('MixNode afterVotingPhaseEnds > dispatching GenerateMix');

                // dispatch mix job
                GenerateMix::dispatch($election, null, $trusteeRunningCode);
            } else {
                Log::debug('afterVotingPhaseEnds > Current server does not accept vallots');
            }
        } else {
            Log::debug('afterVotingPhaseEnds > Current server is not a trustee > do nothing');
        }
    }

}
