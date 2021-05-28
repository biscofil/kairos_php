<?php


namespace App\Voting\AnonymizationMethods\MixNets\DecryptionReEncryption;


use App\Models\Election;
use App\Models\PeerServer;
use App\Voting\AnonymizationMethods\MixNets\Decryption\DecryptionMixWithShadowMixes;
use App\Voting\AnonymizationMethods\MixNets\Mix;
use App\Voting\AnonymizationMethods\MixNets\MixNode;
use App\Voting\AnonymizationMethods\MixNets\MixNodeParameterSet;
use App\Voting\CryptoSystems\CipherText;

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

        /** @var \App\Models\Trustee $mePeer */
        $mePeer = $election->getTrusteeFromPeerServer(PeerServer::me(), true);

        /** @var \App\Voting\CryptoSystems\PartialDecryptionSecretKey $sk */
        $sk = $mePeer->private_key;

        // do partial decryption
        $ciphertexts = array_map(function (CipherText $cipherText) use ($sk) {
            return $sk->partiallyDecrypt($cipherText);
        }, $ciphertexts);

        // do re-encryption
        $reEncryptedCiphertexts = [];
        foreach ($ciphertexts as $idx => $ciphertext) {
            // todo only combine secret keys of next peers
            $r = $parameterSet->reEncryptionFactors[$idx];
            $reEncryptedCiphertexts[] = $ciphertext->reEncryptWithRandomness($r);
        }

        // shuffle
        $reEncryptedCiphertexts = $parameterSet->permuteArray($reEncryptedCiphertexts);

        return new DecryptionReEncryptionMix(
            $election,
            $reEncryptedCiphertexts,
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

}
