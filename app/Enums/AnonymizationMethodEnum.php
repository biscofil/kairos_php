<?php

namespace App\Enums;

use App\Voting\AnonymizationMethods\AnonymizationMethod;
use App\Voting\AnonymizationMethods\BelongsToAnonymizationSystem;
use App\Voting\AnonymizationMethods\HomomorphicAnonymizationMethod;
use App\Voting\AnonymizationMethods\MixNets\Decryption\DecryptionMixNode;
use App\Voting\AnonymizationMethods\MixNets\DecryptionReEncryption\DecryptionReEncryptionMixNode;
use App\Voting\AnonymizationMethods\MixNets\ReEncryption\ReEncryptingMixNode;
use BenSampo\Enum\Enum;

/**
 * @method static static EncMixNet()
 * @method static static DecMixNet()
 * @method static static DecReEncMixNet()
 * @method static static Homomorphic()
 */
final class AnonymizationMethodEnum extends Enum implements GetSetIdentifier
{
    public const EncMixNet = 'enc_mixnet';
    public const DecMixNet = 'dec_mixnet';
    public const DecReEncMixNet = 'dec_re_enc_mixnet';
    public const Homomorphic = 'homomorphic';

    public const ANONYMIZATION_METHODS = [
        AnonymizationMethodEnum::EncMixNet => ReEncryptingMixNode::class,
        AnonymizationMethodEnum::DecMixNet => DecryptionMixNode::class,
        AnonymizationMethodEnum::DecReEncMixNet => DecryptionReEncryptionMixNode::class, // elgamal only
        AnonymizationMethodEnum::Homomorphic => HomomorphicAnonymizationMethod::class // exp elgamal only
    ];

    /**
     * @param BelongsToAnonymizationSystem $obj
     * @return string
     */
    public static function getIdentifier($obj): string
    {
        $v = array_flip(self::ANONYMIZATION_METHODS); // [ ReEncryptingMixNode::class => 'enc_mixnet', ... ]
        $key = $obj::getAnonimizationMethod();
        if (!array_key_exists($key, $v)) {
            throw new \RuntimeException('Unknown anonymization system ' . $key);
        }
        return $v[$key];
    }

    /**
     * @param string $identifier
     * @return string|AnonymizationMethod
     */
    public static function getByIdentifier(string $identifier): string
    {
        if (!array_key_exists($identifier, self::ANONYMIZATION_METHODS)) {
            throw new \RuntimeException('Invalid anonymization method ' . $identifier);
        }
        return self::ANONYMIZATION_METHODS[$identifier];
    }

    /**
     * @return string|AnonymizationMethod
     */
    public function getClass(): string
    {
        return self::getByIdentifier($this->value);
    }
}
