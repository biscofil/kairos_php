<?php


namespace App\Voting\AnonymizationMethods\MixNets\DecryptionReEncryption;


use App\Models\Election;
use App\Voting\AnonymizationMethods\MixNets\Mix;
use App\Voting\AnonymizationMethods\MixNets\MixNode;
use App\Voting\AnonymizationMethods\MixNets\MixNodeParameterSet;
use App\Voting\CryptoSystems\CipherText;
use App\Voting\CryptoSystems\ElGamal\EGCiphertext;
use Illuminate\Support\Facades\Log;
use phpseclib3\Math\BigInteger;

/**
 * Class DecryptionReEncryptionMixNode
 * @package App\Voting\AnonymizationMethods\MixNets
 */
class DecryptionReEncryptionMixNode extends MixNode
{

    /**
     * @param Election $election
     * @param \App\Voting\CryptoSystems\CipherText[] $ciphertexts
     * @param MixNodeParameterSet|null $parameterSet
     * @return Mix
     * @throws \Exception
     */
    public static function forward(Election $election, array $ciphertexts, $parameterSet = null): Mix
    {

        if (is_null($parameterSet)) {
            // if not provided, generate as many randomness factors as there are ciphertexts
            $parameterSet = DecryptionReEncryptionParameterSet::create($election->public_key, count($ciphertexts));
        }

        // apply re-encryption on original ciphertexts

        if (!$parameterSet->skipDecryption) {

            $ciphertexts = array_map(function (CipherText $ciphertext, BigInteger $r) use ($parameterSet): CipherText {
                return $ciphertext->reEncryptWithRandomness($r);
            }, $ciphertexts, $parameterSet->reEncryptionFactors);

            /** @var \App\Models\Trustee $mePeer */
            $mePeer = $election->getTrusteeFromPeerServer(getCurrentServer(), true);

            /** @var \App\Voting\CryptoSystems\PartialDecryptionSecretKey $sk */
            $sk = $mePeer->private_key;

            // do partial decryption on re-encrypted ciphertexts
            $ciphertexts = array_map(function (CipherText $cipherText) use ($sk): CipherText {
                return $sk->partiallyDecrypt($cipherText);
            }, $ciphertexts);
        }

        // shuffle partially decryption and re-encrypted ciphertexts
        $ciphertexts = $parameterSet->permuteArray($ciphertexts);

        return new DecryptionReEncryptionMix(
            $election,
            $ciphertexts,
            $parameterSet
        );
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
            Log::error('storePlainTextBallots failed, no tally');
            return;
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

        $cipherTexts = $lastMix->getMixWithShadowMixes()->primaryMix->ciphertexts;

        return array_map(function (EGCiphertext $cipherText) use ($election) {
            return $cipherText->extractPlainTextFromBeta();
        }, $cipherTexts);
    }

}
