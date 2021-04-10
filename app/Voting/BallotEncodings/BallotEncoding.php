<?php


namespace App\Voting\BallotEncodings;


use App\Voting\CryptoSystems\Plaintext;
use phpseclib3\Math\BigInteger;

interface BallotEncoding
{

    /**
     * @param mixed $vote // TODO Ballot
     * @param string $outClass
     * @return Plaintext[]
     */
    public static function encode($vote, string $outClass) : array;

    /**
     * @param Plaintext $representation
     * @return mixed // TODO Ballot
     */
    public static function decode(Plaintext $representation);

}
