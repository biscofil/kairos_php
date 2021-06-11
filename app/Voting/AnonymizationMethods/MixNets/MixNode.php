<?php


namespace App\Voting\AnonymizationMethods\MixNets;


use App\Jobs\GenerateMix;
use App\Models\Election;
use App\Voting\AnonymizationMethods\AnonymizationMethod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Class MixNet
 * @package App\Voting\MixNets
 */
abstract class MixNode implements AnonymizationMethod
{

    /**
     * @param Election $election
     * @param array $originalCiphertexts
     * @param int $shadowMixCount
     * @return \App\Voting\AnonymizationMethods\MixNets\MixWithShadowMixes
     * @throws \Exception
     */
    public static function generate(Election $election, array $originalCiphertexts, int $shadowMixCount = 100): MixWithShadowMixes
    {
        if ($shadowMixCount > 160) {
            throw new \Exception('The max is 160'); // TODO only for elgamal
        }

        // generate primary mix
        $primaryMix = static::forward($election, $originalCiphertexts);

        // generate shadow mixes
        $shadowMixes = [];
        for ($i = 0; $i < $shadowMixCount; $i++) {
            $shadowMixes[] = static::forward($election, $originalCiphertexts);
        }

        $MixWithShadowMixesClass = static::getMixWithShadowMixesClass();
        return new $MixWithShadowMixesClass(
            $originalCiphertexts,
            $primaryMix,
            $shadowMixes,
            $election
        );

    }

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
     * @param Election $election
     * @param array $ciphertexts
     * @param MixNodeParameterSet|null $parameterSet
     * @return Mix
     * @noinspection PhpMissingParamTypeInspection
     */
    abstract public static function forward(Election $election, array $ciphertexts, $parameterSet = null): Mix;

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
     */
    public static function afterVotingPhaseEnds(Election &$election)
    {
        Log::debug('MixNode afterVotingPhaseEnds');

        $meTrustee = $election->getTrusteeFromPeerServer(getCurrentServer());

        if ($meTrustee) {
            // current server is a peer
            Log::debug('afterVotingPhaseEnds > Current server is a trustee');

            // if ($meTrustee->getPeerServerIndex() === 1) { // TODO check
            if ($meTrustee->accepts_ballots) { // TODO check
                // current server is a peer

                Log::debug('MixNode afterVotingPhaseEnds > dispatching GenerateMix');

                // dispatch mix job
                GenerateMix::dispatchSync($election);
            } else {
                Log::debug('afterVotingPhaseEnds > Current server does not accept vallots');
            }
        } else {
            Log::debug('afterVotingPhaseEnds > Current server is not a trustee > do nothing');
        }
    }

}
