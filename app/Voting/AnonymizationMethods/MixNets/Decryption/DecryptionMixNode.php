<?php


namespace App\Voting\AnonymizationMethods\MixNets\Decryption;


use App\Models\Election;
use App\Voting\AnonymizationMethods\MixNets\Mix;
use App\Voting\AnonymizationMethods\MixNets\MixNode;
use App\Voting\CryptoSystems\CipherText;
use Illuminate\Support\Facades\Log;

/**
 * Class DecryptionMixNode
 * @package App\Voting\AnonymizationMethods\MixNets
 */
class DecryptionMixNode extends MixNode
{

    /**
     * @param Election $election
     * @param array $ciphertexts
     * @param \App\Voting\AnonymizationMethods\MixNets\Decryption\DecryptionParameterSet|null $parameterSet
     * @return Mix
     * @throws \Exception
     */
    public static function forward(Election $election, array $ciphertexts, $parameterSet = null): Mix
    {

        if (is_null($parameterSet)) {

            $psClass = self::getParameterSetClass();

            // if not provided, generate as many randomness factors as there are ciphertexts
            $parameterSet = $psClass::create($election->public_key, count($ciphertexts));
        }

        /** @var \App\Models\Trustee $mePeer */
        $mePeer = $election->getTrusteeFromPeerServer(getCurrentServer(), true);

        /** @var \App\Voting\CryptoSystems\PartialDecryptionSecretKey $sk */
        $sk = $mePeer->private_key;

        // decrypt
        $decryptedCiphertexts = [];
        foreach ($ciphertexts as $idx => $ciphertext) {
            $decryptedCiphertexts[$idx] = $sk->partiallyDecrypt($ciphertext);
        }

        // shuffle
        $decryptedCiphertexts = $parameterSet->permuteArray($decryptedCiphertexts);

        return new DecryptionMix(
            $election,
            $decryptedCiphertexts,
            $parameterSet
        );
    }

    /**
     * @return string|DecryptionMixWithShadowMixes
     */
    public static function getMixWithShadowMixesClass(): string
    {
        return DecryptionMixWithShadowMixes::class;
    }

    /**
     * @return string|DecryptionMix
     */
    public static function getMixClass(): string
    {
        return DecryptionMix::class;
    }

    /**
     * @return string|DecryptionParameterSet
     */
    public static function getParameterSetClass(): string
    {
        return DecryptionParameterSet::class;
    }

    /**
     * This is executed by the bulletin board after the mix procedure
     * @param \App\Models\Election $election
     * @noinspection PhpMissingParentCallCommonInspection
     * @throws \Exception
     */
    public static function afterSuccessfulMixProcess(Election &$election): void
    {
        Log::debug('DecryptionMixNode afterSuccessfulMixProcess > tally');

        $plainTexts = self::extractVotes($election);

        self::storePlainTextBallots($election, $plainTexts);

        self::runTally($election);
    }

    /**
     * @param \App\Models\Election $election
     * @return \App\Voting\CryptoSystems\Plaintext[]
     * @throws \Exception
     */
    public static function extractVotes(Election &$election): array
    {
        /** @var \App\Models\Mix $lastMix */
        $lastMix = $election->mixes()->latest()->firstOrFail();

        $cipherTexts = $lastMix->getMixWithShadowMixes()->primaryMix->ciphertexts;

        return array_map(function (CipherText $cipherText) use ($election) {
            return null; // TODO !!!! convert an already decrypted ciphertext into a plaintext
        }, $cipherTexts);
    }

}
