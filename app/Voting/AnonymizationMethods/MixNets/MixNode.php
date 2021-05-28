<?php


namespace App\Voting\AnonymizationMethods\MixNets;


use App\Jobs\GenerateMix;
use App\Models\Election;
use App\Models\PeerServer;
use App\Voting\AnonymizationMethods\AnonymizationMethod;
use Illuminate\Support\Facades\Log;

/**
 * Class MixNet
 * @package App\Voting\MixNets
 * @property Election $election
 * @property int shadowMixCount
 * @property string $challengeBits
 */
abstract class MixNode implements AnonymizationMethod
{

    public Election $election;
    public int $shadowMixCount;
    public string $challengeBits;

    /**
     * @param Election $election
     * @param array $originalCiphertexts
     * @param int $shadowMixCount
     * @return \App\Voting\AnonymizationMethods\MixNets\MixWithShadowMixes
     * @throws \Exception
     */
    public function generate(Election $election, array $originalCiphertexts, int $shadowMixCount = 100): MixWithShadowMixes
    {
        $this->election = $election;

        if ($shadowMixCount > 160) {
            throw new \Exception('The max is 160'); // TODO only for elgamal
        }

        // generate primary mix
        $primaryMix = static::forward($this->election, $originalCiphertexts);

        // ghenerate shadow mixes
        $this->shadowMixCount = $shadowMixCount;
        $shadowMixes = [];
        for ($i = 0; $i < $shadowMixCount; $i++) {
            $shadowMixes[] = static::forward($this->election, $originalCiphertexts);
        }

        $mixModel = new \App\Models\Mix();
        $mixModel->round = 1; // TODO
        $mixModel->trustee_id = $election->getTrusteeFromPeerServer(PeerServer::me())->id;
        $mixModel->save();

        $MixWithShadowMixesClass = static::getMixWithShadowMixesClass();
        return new $MixWithShadowMixesClass(
            $originalCiphertexts,
            $primaryMix,
            $shadowMixes,
            $this->election
        );

    }

    /**
     * @return string|\App\Voting\AnonymizationMethods\MixNets\MixWithShadowMixes
     */
    abstract public static function getMixWithShadowMixesClass(): string;

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
     */
    public static function afterVotingPhaseEnds(Election &$election)
    {
        Log::debug('MixNode afterVotingPhaseEnds > dispatching GenerateMix');
        // dispatch mix job
        GenerateMix::dispatch($election);
    }

}
