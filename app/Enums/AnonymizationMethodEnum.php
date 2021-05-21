<?php

namespace App\Enums;

use App\Voting\AnonymizationMethods\AnonymizationMethod;
use App\Voting\AnonymizationMethods\HomomorphicAnonymizationMethod;
use App\Voting\AnonymizationMethods\MixNets\DecryptingMixNode;
use App\Voting\AnonymizationMethods\MixNets\ReEncryptingMixNode;
use BenSampo\Enum\Enum;

/**
 * @method static static EncMixNet()
 * @method static static DecMixNet()
 * @method static static Homomorphic()
 */
final class AnonymizationMethodEnum extends Enum
{
    public const EncMixNet = 'enc_mixnet';
    public const DecMixNet = 'dec_mixnet';
    public const Homomorphic = 'homomorphic';

    public const ANONYMIZATION_METHODS = [
        AnonymizationMethodEnum::EncMixNet => ReEncryptingMixNode::class,
        AnonymizationMethodEnum::DecMixNet => DecryptingMixNode::class,
        AnonymizationMethodEnum::Homomorphic => HomomorphicAnonymizationMethod::class
    ];

    /**
     * @param string $anonomizationSystemIdentifier
     * @return string|AnonymizationMethod
     */
    public static function getByIdentifier(string $anonomizationSystemIdentifier): string
    {
        if (!array_key_exists($anonomizationSystemIdentifier, self::ANONYMIZATION_METHODS)) {
            throw new \RuntimeException('Invalid anonymization method ' . $anonomizationSystemIdentifier);
        }
        return self::ANONYMIZATION_METHODS[$anonomizationSystemIdentifier];
    }

    /**
     * @return string|AnonymizationMethod
     */
    public function getAnonymizationSystemClass(): string
    {
        return self::getByIdentifier($this->value);
    }
}
