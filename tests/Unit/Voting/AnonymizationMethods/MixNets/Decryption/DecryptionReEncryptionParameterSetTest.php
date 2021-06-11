<?php


namespace Tests\Unit\Voting\AnonymizationMethods\MixNets\Decryption;


use App\Voting\AnonymizationMethods\MixNets\DecryptionReEncryption\DecryptionReEncryptionParameterSet;
use App\Voting\CryptoSystems\ElGamal\EGKeyPair;
use Tests\TestCase;

class DecryptionReEncryptionParameterSetTest extends TestCase
{

    /**
     * @test
     * @throws \Exception
     */
    public function combine()
    {

        $keyPair = EGKeyPair::generate();

        $shadowMixPS = new DecryptionReEncryptionParameterSet($keyPair->pk, [
            BI(10),
            BI(11),
            BI(12),
            BI(13),
            BI(14)
        ], [2, 0, 4, 3, 1]);

        $primaryMixPS = new DecryptionReEncryptionParameterSet($keyPair->pk, [
            BI(30),
            BI(31),
            BI(32),
            BI(33),
            BI(34)
        ], [3, 2, 1, 0, 4]);

        $comb = $shadowMixPS->combine($primaryMixPS);

        static::assertEquals([3, 0, 4, 1, 2], $comb->permutation);
        foreach ($comb->reEncryptionFactors as $factor) {
            static::assertTrue($factor->equals(BI(20)));
        }

    }

    /**
     * @test
     * @throws \Exception
     */
    public function get_shuffling_order_reversed()
    {

        $keyPair = EGKeyPair::generate();

        $n = 5;

        $oldPerm = range(0, $n - 1);
        shuffle($oldPerm);

        $reEncryptionFactors = array_map(function () {
            return randomBIgt(BI(200));
        }, range(0, $n - 1));

        $shadowMixPS = new DecryptionReEncryptionParameterSet($keyPair->pk, $reEncryptionFactors, $oldPerm);

        $rev = $shadowMixPS->getShufflingOrderReversed();

        foreach ($rev as $value => $oldPos) {
            static::assertEquals($value, $oldPerm[$oldPos]);
        }

    }

    /**
     * @test
     * @throws \Exception
     */
    public function permute_array()
    {

        $keyPair = EGKeyPair::generate();

        $n = 5;

        $oldPerm = range(0, $n - 1);
        shuffle($oldPerm);

        $reEncryptionFactors = array_map(function () {
            return randomBIgt(BI(200));
        }, range(0, $n - 1));

        $shadowMixPS = new DecryptionReEncryptionParameterSet($keyPair->pk, $reEncryptionFactors, $oldPerm);

        $rev = $shadowMixPS->permuteArray(range(0, $n - 1));

        static::assertEquals($rev, $oldPerm);

    }


}
