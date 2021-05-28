<?php


namespace App\Voting\AnonymizationMethods\MixNets\DecryptionReEncryption;


trait BelongsToDecryptionReEncryptionMixNode
{

    /**
     * @return string
     */
    public static function getAnonimizationMethod(): string
    {
        return DecryptionReEncryptionMixNode::class;
    }

}
