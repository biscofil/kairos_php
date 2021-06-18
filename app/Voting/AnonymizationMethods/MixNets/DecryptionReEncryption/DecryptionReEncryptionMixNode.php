<?php


namespace App\Voting\AnonymizationMethods\MixNets\DecryptionReEncryption;


use App\Models\Election;
use App\Models\Trustee;
use App\Voting\AnonymizationMethods\MixNets\Mix;
use App\Voting\AnonymizationMethods\MixNets\MixNode;
use App\Voting\AnonymizationMethods\MixNets\MixNodeParameterSet;
use App\Voting\CryptoSystems\CipherText;
use App\Voting\CryptoSystems\ElGamal\EGCiphertext;
use App\Voting\CryptoSystems\PublicKey;
use Illuminate\Support\Facades\Log;
use phpseclib3\Math\BigInteger;

/**
 * Class DecryptionReEncryptionMixNode
 * @package App\Voting\AnonymizationMethods\MixNets
 */
class DecryptionReEncryptionMixNode extends MixNode
{

    /**
     * @param \App\Voting\AnonymizationMethods\MixNets\Mix $inputMix
     * @param \App\Voting\AnonymizationMethods\MixNets\DecryptionReEncryption\DecryptionReEncryptionParameterSet $parameterSet
     * @param \App\Models\Trustee $trusteeRunningMix
     * @return Mix
     */
    public static function forward(Mix $inputMix, MixNodeParameterSet $parameterSet, Trustee $trusteeRunningMix): Mix
    {
        // apply re-encryption on original ciphertexts
        $ciphertexts = array_map(function (CipherText $ciphertext, BigInteger $r) use ($parameterSet): EGCiphertext {
            $ciphertext->pk = $parameterSet->pk;
            $ciphertext = $ciphertext->reEncryptWithRandomness($r);
            $ciphertext->pk = $parameterSet->pk;
            return $ciphertext;
        }, $inputMix->ciphertexts, $parameterSet->reEncryptionFactors);

        // shuffle partially decryption and re-encrypted ciphertexts
        /** @var EGCiphertext[] $ciphertexts */
        $ciphertexts = $parameterSet->permuteArray($ciphertexts);

        if ($parameterSet->decryption) {
            /** @var \App\Voting\CryptoSystems\PartialDecryptionSecretKey $sk */
            $sk = $trusteeRunningMix->private_key;

            // do partial decryption on re-encrypted ciphertexts
            $ciphertexts = array_map(function (EGCiphertext $cipherText) use ($parameterSet, $sk): EGCiphertext {
                /** @noinspection PhpIncompatibleReturnTypeInspection */
                $out = $sk->partiallyDecrypt($cipherText);
                $out->pk = $parameterSet->pk;
                return $out;
            }, $ciphertexts);
        }

        return new DecryptionReEncryptionMix($ciphertexts, $parameterSet, null);
    }

    /**
     * @return string|DecryptionReEncryptionMixWithShadowMixes
     */
    public static function getMixWithShadowMixesClass(): string
    {
        return DecryptionReEncryptionMixWithShadowMixes::class;
    }

    /**
     * @return string|DecryptionReEncryptionMix
     */
    public static function getMixClass(): string
    {
        return DecryptionReEncryptionMix::class;
    }

    /**
     * @return string|DecryptionReEncryptionParameterSet
     */
    public static function getParameterSetClass(): string
    {
        return DecryptionReEncryptionParameterSet::class;
    }

    /**
     * This is executed by the bulletin board after the mix procedure
     * @param \App\Models\Election $election
     * @noinspection PhpMissingParentCallCommonInspection
     * @throws \Exception
     */
    public static function afterSuccessfulMixProcess(Election &$election): void
    {
        Log::debug('DecryptionReEncryptionMixNode afterSuccessfulMixProcess > tally');

        $plainTexts = self::extractVotes($election);

        if (!self::storePlainTextBallots($election, $plainTexts)) {
            Log::error('at least one failed insertion in storePlainTextBallots, proceeding to tally');
            // TODO return;
        }

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

        $cipherTexts = $lastMix->getMixWithShadowMixes()->getPrimaryMix()->ciphertexts;

        return array_map(function (EGCiphertext $cipherText) use ($election) {
            return $cipherText->extractPlainTextFromBeta(true);
        }, $cipherTexts);
    }

    /**
     * @param \App\Voting\CryptoSystems\PublicKey $public_key
     * @param int $cipherTextCount
     * @return \App\Voting\AnonymizationMethods\MixNets\MixNodeParameterSet
     * @throws \Exception
     */
    public static function getPrimaryMixParameterSet(PublicKey $public_key, int $cipherTextCount): MixNodeParameterSet
    {
        $psClass = static::getParameterSetClass();
        return $psClass::create($public_key, $cipherTextCount);
    }

    /**
     * @param \App\Voting\CryptoSystems\PublicKey $public_key
     * @param int $cipherTextCount
     * @return \App\Voting\AnonymizationMethods\MixNets\MixNodeParameterSet
     * @throws \Exception
     */
    public static function getShadowMixParameterSet(PublicKey $public_key, int $cipherTextCount): MixNodeParameterSet
    {
        $psClass = static::getParameterSetClass();
        $out = $psClass::create($public_key, $cipherTextCount);
        $out->decryption = false;
        return $out;
    }
}
