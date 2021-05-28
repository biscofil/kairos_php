<?php


namespace Tests\Unit\Voting\AnonymizationMethods\MixNets;

use App\Models\Election;
use App\Voting\AnonymizationMethods\MixNets\Mix;
use App\Voting\AnonymizationMethods\MixNets\ReEncryption\ReEncryptingMixNode;
use phpseclib3\Math\BigInteger;
use Tests\TestCase;

/**
 * Class MixTest
 * @package Tests\Unit\Voting\MixNets
 */
class MixTest extends TestCase
{

    /**
     * @ TODO test
     * @throws \Exception
     */
    public function to_from_array()
    {
        /** @var Election $election */
        $election = Election::factory()->create();

        $kpClass = $election->cryptosystem->getClass()::getKeyPairClass();
        $ptClass = $election->cryptosystem->getClass()::getPlainTextClass();

        $keyPair = $kpClass::generate();
        $election->public_key = $keyPair->pk;
        $election->private_key = $keyPair->sk;
        $election->save();

        $ciphertexts = [];
        for ($i = 0; $i < rand(5, 10); $i++) {
            $ciphertexts[] = $keyPair->pk->encrypt(new $ptClass(BigInteger::random(20)));
        }

        $mix = ReEncryptingMixNode::forward($election, $ciphertexts);

        // without parameter set
        $out = $mix->toArray(false);
        Mix::fromArray($out); // TODO

        // with parameter set
        $out = $mix->toArray(true);
        $read = Mix::fromArray($out); // TODO

        static::assertTrue(self::areCiphertextListsEquals(
            $mix->ciphertexts,
            $read->ciphertexts
        ));

    }

    /**
     * @ test
     * @throws \Exception
     */
    public function reverse()
    {
        /** @var Election $election */
        $election = Election::factory()->create();

        $kpClass = $election->cryptosystem->getClass()::getKeyPairClass();
        $ptClass = $election->cryptosystem->getClass()::getPlainTextClass();

        $keyPair = $kpClass::generate();
        $election->public_key = $keyPair->pk;
        $election->private_key = $keyPair->sk;
        $election->save();

        $ciphertexts = [];
        for ($i = 0; $i < rand(5, 10); $i++) {
            $ciphertexts[] = $keyPair->pk->encrypt(new $ptClass(BigInteger::random(20)));
        }

        $mix = ReEncryptingMixNode::forward($election, $ciphertexts);

//        $revMix = ReEncryptingMixNode::backward($election, $mix->ciphertexts, $mix->parameterSet);
//
//        static::assertTrue(self::areCiphertextListsEquals(
//            $ciphertexts,
//            $revMix->ciphertexts
//        ));

    }

    /**
     * @param \App\Voting\CryptoSystems\CipherText[] $a
     * @param \App\Voting\CryptoSystems\CipherText[] $b
     * @return bool
     * @throws \Exception
     */
    public static function areCiphertextListsEquals(array $a, array $b): bool
    {
        for ($i = 0; $i < count($a); $i++) {
            if (!$a[$i]->equals($b[$i])) {
//                dump($a[$i]->alpha->toHex());
//                dump($b[$i]->alpha->toHex());
//                dump($a[$i]->beta->toHex());
//                dump($b[$i]->beta->toHex());
                return false;
            }
        }
        return true;
    }

}
