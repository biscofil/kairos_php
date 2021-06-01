<?php


namespace App\Voting\AnonymizationMethods\MixNets\Decryption;


use App\Models\Election;
use App\Models\PeerServer;
use App\Voting\AnonymizationMethods\MixNets\Mix;
use App\Voting\AnonymizationMethods\MixNets\MixNode;

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
        $mePeer = $election->getTrusteeFromPeerServer(PeerServer::me(), true);

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
     * @param \App\Models\Election $election
     * @noinspection PhpMissingParentCallCommonInspection
     */
    public static function afterSuccessfulMixProcess(Election &$election): void
    {
        // TODO dispatch extraction
    }

}
