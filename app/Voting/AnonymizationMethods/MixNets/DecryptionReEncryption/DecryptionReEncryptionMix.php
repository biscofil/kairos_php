<?php


namespace App\Voting\AnonymizationMethods\MixNets\DecryptionReEncryption;


use App\Voting\AnonymizationMethods\MixNets\Mix;
use App\Voting\CryptoSystems\ElGamal\EGCiphertext;

/**
 * Class DecryptionReEncryptionMix
 * @package App\Voting\AnonymizationMethods\MixNets\DecryptionReEncryption
 */
class DecryptionReEncryptionMix extends Mix
{

    use BelongsToDecryptionReEncryptionMixNode;


    /**
     * TODO CHECK!!!!, only Beta is compared !!!!
     * @param Mix $b
     * @return bool
     * @throws \Exception
     * @noinspection PhpMissingParentCallCommonInspection
     */
    public function equals(Mix $b): bool
    {
        for ($i = 0; $i < count($this->ciphertexts); $i++) {
            if (!$this->ciphertexts[$i]->beta->equals($b->ciphertexts[$i]->beta)) {

//                dump(array_map(function (EGCiphertext $ct) {
//                    return $ct->pk->parameterSet->extractMessageFromSubgroup($ct->beta)->toHex();
////                    return $ct->getFingerprint();
//                }, $this->ciphertexts));
//
//                dump(array_map(function (EGCiphertext $ct) {
//                    return $ct->pk->parameterSet->extractMessageFromSubgroup($ct->beta)->toHex();
////                    return $ct->getFingerprint();
//                }, $b->ciphertexts));

                return false;
            }
        }
        return true;
    }

}
