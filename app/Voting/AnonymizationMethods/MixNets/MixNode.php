<?php


namespace App\Voting\AnonymizationMethods\MixNets;


use App\Jobs\GenerateMix;
use App\Models\Election;
use App\Voting\AnonymizationMethods\AnonymizationMethod;
use App\Voting\BallotEncodings\JsonBallotEncoding;
use App\Voting\CryptoSystems\CipherText;
use Illuminate\Database\Connection;
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
     */
    public static function afterSuccessfulMixProcess(Election &$election): void
    {
        // do nothing
    }

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
     * @param \App\Models\Election $election
     * @param \Illuminate\Database\Connection $connection
     * @param \App\Voting\CryptoSystems\CipherText $cipherText
     * @return bool
     */
    public static function insertBallot(Election &$election, Connection &$connection, CipherText &$cipherText): bool
    {
        $plainVote = JsonBallotEncoding::decode($election->private_key->decrypt($cipherText));

        $connection->getSchemaBuilder()->enableForeignKeyConstraints();

//        Log::debug($plainVote);

        if (!is_array($plainVote) || count($plainVote) !== $election->questions->count()) {

            Log::warning('Ignoring vote due to wrong lenght');
            Log::debug($plainVote);
            return false;

        }

        try {
            $record = [];

            //set all as null
            foreach ($election->questions as $questionIdx => $question) {
                $q = $questionIdx + 1;
                for ($aIdx = 0; $aIdx < $question->max; $aIdx++) {
                    $a = $aIdx + 1;
                    $cName = "q_{$q}_a_{$a}";
                    $record[$cName] = null;
                }
            }

            // fill
            foreach ($plainVote as $questionIdx => $questionAnswers) {
                $q = $questionIdx + 1;
                foreach ($questionAnswers as $idx => $questionAnswer) {
                    $a = $idx + 1;
                    $record["q_{$q}_a_{$a}"] = $questionAnswer;
                }
            }

            return $connection->table($election->getOutputTableName())->insert($record);

        } catch (\Exception $e) {
            Log::error($e->getMessage());
            Log::debug($record);
        }

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

            // if ($meTrustee->getPeerServerIndex() === 1) { // TODO check
            if ($meTrustee->accepts_ballots) { // TODO check
                // current server is a peer

                Log::debug('MixNode afterVotingPhaseEnds > dispatching GenerateMix');

                // dispatch mix job
                GenerateMix::dispatchSync($election);
            }
        }
    }

}
