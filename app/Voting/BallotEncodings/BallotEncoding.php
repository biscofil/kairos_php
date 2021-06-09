<?php


namespace App\Voting\BallotEncodings;


use App\Voting\CryptoSystems\Plaintext;

interface BallotEncoding
{

    /**
     * @param array $vote // TODO Ballot
     * @param string $outClass
     * @return Plaintext
     */
    public static function encode(array $vote, string $outClass): Plaintext;

    /**
     * @param Plaintext $representation
     * @return mixed // TODO Ballot
     */
    public static function decode(Plaintext $representation);

}
