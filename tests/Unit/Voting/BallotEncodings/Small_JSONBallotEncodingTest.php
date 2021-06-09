<?php


namespace Tests\Unit\Voting\BallotEncodings;


use App\Voting\BallotEncodings\Small_JSONBallotEncoding;
use App\Voting\CryptoSystems\ElGamal\EGPlaintext;
use Tests\TestCase;

/**
 * This encoding takes half the space required by ASCII
 * Class Small_JSONBallotEncodingTest
 * @package Tests\Unit
 */
class Small_JSONBallotEncodingTest extends TestCase
{

    /**
     * @test
     */
    public function encode_decode()
    {

        $votePlain = [[1, 3], [4, 5]];

        /** @var \App\Voting\CryptoSystems\ElGamal\EGPlaintext $plaintext */
        $plaintext = Small_JSONBallotEncoding::encode($votePlain, EGPlaintext::class);

        $out = Small_JSONBallotEncoding::decode($plaintext);

        self::assertTrue($votePlain === $out);

    }


}
