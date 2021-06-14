<?php


namespace App\Voting\AnonymizationMethods\MixNets\Decryption;


use App\Models\Election;
use App\Voting\AnonymizationMethods\MixNets\Mix;
use App\Voting\AnonymizationMethods\MixNets\MixNode;
use App\Voting\AnonymizationMethods\MixNets\MixNodeParameterSet;
use App\Voting\CryptoSystems\CipherText;
use App\Voting\CryptoSystems\PublicKey;
use Illuminate\Support\Facades\Log;

/**
 * Class DecryptionMixNode
 * @package App\Voting\AnonymizationMethods\MixNets
 */
class DecryptionMixNode extends MixNode
{

    /**
     * @param Election $election
     * @param CipherText[] $ciphertexts
     * @param \App\Voting\AnonymizationMethods\MixNets\MixNodeParameterSet $parameterSet
     * @return Mix
     * @throws \Exception
     */
    public static function forward(Election $election, array $ciphertexts, MixNodeParameterSet $parameterSet): Mix
    {

        if (count($ciphertexts) !== count($parameterSet->permutation)) {
            throw new \Exception('ciphertexts has ' . count($ciphertexts)
                . ' elements while parameterSet->permutation has ' . count($parameterSet->permutation));
        }

        /** @var \App\Models\Trustee $mePeer */
        $mePeer = $election->getTrusteeFromPeerServer(getCurrentServer(), true);

        /** @var \App\Voting\CryptoSystems\PartialDecryptionSecretKey $sk */
        $sk = $mePeer->private_key;

        // decrypt
        $decryptedCiphertexts = array_map(function (CipherText $ciphertext) use ($sk): CipherText {
            return $sk->partiallyDecrypt($ciphertext);
        }, $ciphertexts);

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

    /**
     * generate as many randomness factors as there are ciphertexts
     * @param \App\Voting\CryptoSystems\PublicKey $public_key
     * @param int $cipherTextCount
     * @return \App\Voting\AnonymizationMethods\MixNets\MixNodeParameterSet
     * @throws \Exception
     */
    public static function getPrimaryMixParameterSet(PublicKey $public_key, int $cipherTextCount): MixNodeParameterSet
    {
        $psClass = static::getParameterSetClass();
        // if not provided, generate as many randomness factors as there are ciphertexts
        return $psClass::create($public_key, $cipherTextCount);
    }

    /**
     * generate as many randomness factors as there are ciphertexts
     * @param \App\Voting\CryptoSystems\PublicKey $public_key
     * @param int $cipherTextCount
     * @return \App\Voting\AnonymizationMethods\MixNets\MixNodeParameterSet
     * @throws \Exception
     */
    public static function getShadowMixParameterSet(PublicKey $public_key, int $cipherTextCount): MixNodeParameterSet
    {
        $psClass = static::getParameterSetClass();
        return $psClass::create($public_key, $cipherTextCount);
    }
}
