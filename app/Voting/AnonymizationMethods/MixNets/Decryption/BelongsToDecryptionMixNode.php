<?php


namespace App\Voting\AnonymizationMethods\MixNets\Decryption;


use App\Voting\AnonymizationMethods\MixNets\DecryptionReEncryption\DecryptionReEncryptionMixNode;

trait BelongsToDecryptionMixNode
{

    /**
     * @return string
     */
    public static function getAnonimizationMethod(): string
    {
        return DecryptionReEncryptionMixNode::class;
    }

}
