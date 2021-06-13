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
     * @param string|Plaintext $outClass
     * @return Plaintext
     * @throws \Exception
     */
    public static function encode(array $vote, string $outClass): Plaintext
    {
        $jsonStr = json_encode($vote);

        // remap
        $chars = array_map(function ($decChar) use ($jsonStr) {
            if ($pos = strpos(self::alphabet, $decChar)) {
                return dechex($pos);
            }
            throw new \Exception("Unsupported input $jsonStr");
        }, str_split($jsonStr));

        $str = implode('', $chars);

        return new $outClass($str);
    }

    /**
     * Decodes a BigInt that represents a ballot
     * @param Plaintext $representation
     * @return mixed|void
     */
    public static function decode(Plaintext $representation): array
    {

        $hex = $representation->toString(); // seems to add leading zeros

        // Note : BigInteger->hex() returns a different format than dechex() with a leading zero
        // remove leading zeros TODO CHECK
        $hex = ltrim($hex, '0');

        // remap
        $chars = array_map(function ($hexChar) {
            return self::alphabet[hexdec($hexChar)];
        }, str_split($hex));

        $str = implode('', $chars);

        return json_decode($str, true);
    }

}
