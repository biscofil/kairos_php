<?php


namespace App\Voting\BallotEncodings;


use App\Voting\CryptoSystems\Plaintext;

/**
 * Class JsonBallotEncoding
 * @package App\Voting\BallotEncodings
 */
class JsonBallotEncoding implements BallotEncoding
{

    /**
     * Encodes a ballot using Json
     * TODO could store as tring or as number
     * @param mixed $vote
     * @param string $outClass
     * @return Plaintext[]
     */
    public static function encode($vote, string $outClass): array
    {
        $jsonStr = json_encode($vote);
        // from UTF-8 to ASCII
        $str = iconv("UTF-8", "ASCII", $jsonStr);
        $str = head(unpack('H*', $str));
        return [new $outClass($str)];
    }

    /**
     * Decodes a BigInt that represents a ballot
     * @param Plaintext $representation
     * @return mixed|void
     */
    public static function decode(Plaintext $representation)
    {
        $jsonStr = pack('H*', $representation->m->toHex());
        return json_decode($jsonStr, true);
    }

}
