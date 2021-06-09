<?php


namespace Tests\Unit\Voting\BallotEncodings;


use App\Voting\BallotEncodings\ASCII_JSONBallotEncoding;
use App\Voting\CryptoSystems\ElGamal\EGPlaintext;
use Tests\TestCase;

/**
 * Class JsonBallotEncodingTest
 * @package Tests\Unit\Voting\BallotEncodings
 */
class ASCII_JSONBallotEncodingTest extends TestCase
{

    /**
     * @test
     */
    public function encode_decode()
    {
        $votePlain =  [[1, 3], [4, 5]];

        /** @var \App\Voting\CryptoSystems\ElGamal\EGPlaintext $plaintext */
        $plaintext = (ASCII_JSONBallotEncoding::encode($votePlain, EGPlaintext::class))[0];

        $out = ASCII_JSONBallotEncoding::decode($plaintext);

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
        $plaintext = (ASCII_JSONBallotEncoding::encode($votePlain, EGPlaintext::class))[0];

        $pt = new EGPlaintext(BI('110244460886710670085338461', 10));

        self::assertTrue($plaintext->equals($pt));

        $jsEncoding = ASCII_JSONBallotEncoding::decode($pt);

        self::assertEquals($votePlain, $jsEncoding);

    }

    /**
     * @test
     */
    public function prevent_vote_selling()
    {

        $a = '[1,2,3]';
        $b = '[1,   2,  3]';
        $c = ' [1,2,3] ';
        $d = '[1,"2",3]'; // '2' is not valid in JSON

        self::assertTrue(ASCII_JSONBallotEncoding::isBallotValid($a));
        self::assertFalse(ASCII_JSONBallotEncoding::isBallotValid($b));
        self::assertFalse(ASCII_JSONBallotEncoding::isBallotValid($c));
        self::assertFalse(ASCII_JSONBallotEncoding::isBallotValid($d));

        self::assertEquals(json_decode($a), json_decode($b));
        self::assertEquals(json_decode($a), json_decode($c));

        self::assertEquals($a, json_encode(json_decode($a)));
        $invalidItemsD = array_filter(json_decode($a, true), function ($v) {
            return !is_int($v);
        });
        self::assertCount(0, $invalidItemsD);

        self::assertNotEquals($b, json_encode(json_decode($b)));
        self::assertNotEquals($c, json_encode(json_decode($c)));

//        dump(json_decode($d));
//        self::assertNotEquals(json_decode($a, true), json_decode($d, true));
        $invalidItemsD = array_filter(json_decode($d, true), function ($v) {
            return !is_int($v);
        });
        self::assertCount(1, $invalidItemsD);
        // assertNotEquals ignores type!!!!! "2" and 2 are considered equal

    }

}