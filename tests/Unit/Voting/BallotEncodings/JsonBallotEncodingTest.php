<?php


namespace Tests\Unit\Voting\BallotEncodings;


use App\Voting\BallotEncodings\JsonBallotEncoding;
use App\Voting\CryptoSystems\ElGamal\EGPlaintext;
use Tests\TestCase;

/**
 * Class JsonBallotEncodingTest
 * @package Tests\Unit\Voting\BallotEncodings
 */
class JsonBallotEncodingTest extends TestCase
{

    /**
     * @test
     */
    public function encode_decode()
    {
        $votePlain = [1, 3, 4, 3, 5];

        /** @var \App\Voting\CryptoSystems\ElGamal\EGPlaintext $plaintext */
        $plaintext = (JsonBallotEncoding::encode($votePlain, EGPlaintext::class))[0];

        $out = JsonBallotEncoding::decode($plaintext);

        self::assertEquals($votePlain, $out);

    }

    /**
     * @test
     */
    public function from_javascript_value()
    {

        $votePlain = [1, 3, 4, 3, 5];

        // [1, 3, 4, 3, 5]
        // js output : 110244460886710670085338461n

        /** @var \App\Voting\CryptoSystems\ElGamal\EGPlaintext $plaintext */
        $plaintext = (JsonBallotEncoding::encode($votePlain, EGPlaintext::class))[0];

        $pt = new EGPlaintext(BI('110244460886710670085338461', 10));

        self::assertTrue($plaintext->equals($pt));

        $jsEncoding = JsonBallotEncoding::decode($pt);

        self::assertEquals($votePlain, $jsEncoding);

    }


}
