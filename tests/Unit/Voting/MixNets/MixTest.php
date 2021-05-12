<?php


namespace Tests\Unit\Voting\MixNets;

use App\Voting\AnonymizationMethods\MixNets\ReEncryptingMixNode;
use App\Voting\CryptoSystems\ElGamal\EGCiphertext;
use App\Voting\CryptoSystems\ElGamal\EGKeyPair;
use App\Voting\CryptoSystems\ElGamal\EGPlaintext;
use phpseclib3\Math\BigInteger;
use Tests\TestCase;

/**
 * Class MixTest
 * @package Tests\Unit\Voting\MixNets
 */
class MixTest extends TestCase
{

    /**
     * @test
     * @throws \Exception
     */
    public function reverse()
    {

        $keyPair = EGKeyPair::generate();

        $ciphertexts = [];
        for ($i = 0; $i < rand(5, 10); $i++) {
            $ciphertexts[] = $keyPair->pk->encrypt(new EgPlaintext(BigInteger::random(20)));
        }

        $mix = ReEncryptingMixNode::forward($keyPair->pk, $ciphertexts);

        $revMix = ReEncryptingMixNode::backward($keyPair->pk, $mix->ciphertexts, $mix->parameterSet);

        $this->assertTrue(self::areCiphertextListsEquals(
            $ciphertexts,
            $revMix->ciphertexts
        ));

    }

    /**
     * @param EGCiphertext[] $a
     * @param EGCiphertext[] $b
     * @return bool
     * @throws \Exception
     */
    public static function areCiphertextListsEquals(array $a, array $b): bool
    {
        for ($i = 0; $i < count($a); $i++) {
            if (!$a[$i]->equals($b[$i])) {
                dump($a[$i]->alpha->toHex());
                dump($b[$i]->alpha->toHex());
                dump($a[$i]->beta->toHex());
                dump($b[$i]->beta->toHex());
                return false;
            }
        }
        return true;
    }

}
