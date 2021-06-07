<?php


namespace App\Voting\BallotEncodings;


use App\Voting\CryptoSystems\Plaintext;

/**
 * Class SmallJsonBallotEncoding
 * @package App\Voting\BallotEncodings
 */
class Small_JSONBallotEncoding extends JSONBallotEncoding
{


    public const alphabet = '0123456789[],#!@';

    /**
     * Encodes a ballot using Json
     * TODO could store as string or as number
     * @param mixed $vote
     * @param string $outClass
     * @return Plaintext[]
     */
    public static function encode($vote, string $outClass): array
    {
        $jsonStr = json_encode($vote);

        // TODO CHECK leading zeros

        // remove all invalid chars
//        $jsonStr = preg_replace("/[^0-9\{\}\:\"\,]/", '', $jsonStr);
        // remap
        $chars = array_map(function ($char) {
            return dechex(strpos(self::alphabet, $char));
        }, str_split($jsonStr));
        $str = implode('', $chars);

        return [new $outClass($str)];
    }

    /**
     * Decodes a BigInt that represents a ballot
     * @param Plaintext $representation
     * @return mixed|void
     */
    public static function decode(Plaintext $representation)
    {

        $hex = $representation->toString();

        // remove leading zeros
        $hex = dechex(hexdec($hex)); // TODO CHECK

        // remap
        $chars = array_map(function ($char) {
            return self::alphabet[hexdec($char)];
        }, str_split($hex));

        $str = implode('', $chars);

        return json_decode($str, true);
    }

}
