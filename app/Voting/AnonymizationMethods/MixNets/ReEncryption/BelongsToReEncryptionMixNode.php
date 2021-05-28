<?php


namespace App\Voting\AnonymizationMethods\MixNets\ReEncryption;


trait BelongsToReEncryptionMixNode
{

    /**
     * @return string
     */
    public static function getAnonimizationMethod(): string
    {
        return ReEncryptingMixNode::class;
    }

}
