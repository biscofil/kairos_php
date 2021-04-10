<?php


namespace App\Voting\CryptoSystems;


interface ThresholdBroadcast
{
    /**
     * @return array
     */
    public function toArray(): array;

    /**
     * @param array $data
     * @return ThresholdBroadcast
     */
    public static function fromArray(array $data): ThresholdBroadcast;

}
