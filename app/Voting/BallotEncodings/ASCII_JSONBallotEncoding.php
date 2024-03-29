<?php


namespace App\Voting\BallotEncodings;


use App\Voting\CryptoSystems\Plaintext;

/**
 * Class JsonBallotEncoding
 * @package App\Voting\BallotEncodings
 */
class ASCII_JSONBallotEncoding extends JSONBallotEncoding
{

    /**
     * Encodes a ballot using Json
     * TODO could store as string or as number
     * @param array $vote
     * @param string $outClass
     * @return Plaintext
     */
    public static function encode(array $vote, string $outClass): Plaintext
    {
        $jsonStr = json_encode($vote);
        // from UTF-8 to ASCII
        $str = iconv('UTF-8', 'ASCII', $jsonStr);
        $str = head(unpack('H*', $str));
        return new $outClass($str);
    }

    /**
     * Decodes a BigInt that represents a ballot
     * @param Plaintext $representation
     * @return mixed|void
     */
    public static function decode(Plaintext $representation)
    {
        $jsonStr = pack('H*', $representation->toString());
        return json_decode($jsonStr, true);
    }

}
