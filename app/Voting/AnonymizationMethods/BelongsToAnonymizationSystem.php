<?php


namespace App\Voting\AnonymizationMethods;

/**
 * Interface BelongsToAnonymizationSystem
 * @package App\Voting\AnonymizationMethods
 */
interface BelongsToAnonymizationSystem
{

    /**
     * @return string|\App\Voting\AnonymizationMethods\AnonymizationMethod
     */
    public static function getAnonimizationMethod(): string;

}
