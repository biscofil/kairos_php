<?php


namespace App\Voting\AnonymizationMethods;

/**
 * Interface BelongsToAnonymizationMethod
 * @package App\Voting\AnonymizationMethods
 */
interface BelongsToAnonymizationMethod
{

    /**
     * @return string|\App\Voting\AnonymizationMethods\AnonymizationMethod
     */
    public static function getAnonimizationMethod(): string;

}
