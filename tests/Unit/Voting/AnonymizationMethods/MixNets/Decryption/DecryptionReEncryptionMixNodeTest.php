<?php


namespace Tests\Unit\Voting\AnonymizationMethods\MixNets\Decryption;


use App\Voting\CryptoSystems\ElGamal\EGDLogProof;
use App\Voting\CryptoSystems\ElGamal\EGKeyPair;
use App\Voting\CryptoSystems\ElGamal\EGPlaintext;
use phpseclib3\Math\BigInteger;
use Tests\TestCase;

class DecryptionReEncryptionMixNodeTest extends TestCase
{

    /**
     * @test
     */
    public function proof()
    {

        $kp1 = EGKeyPair::generate();
        $kp2 = EGKeyPair::generate();

        $pk = $kp1->pk->combine($kp2->pk);

        $plain = new EGPlaintext(BigInteger::random(20));
        $cipher = $pk->encrypt($plain);

        // ################################################################################### mixnet starts here

        $reEncryptionRandomness = $kp1->pk->parameterSet->getReEncryptionFactor();

        // ###################  forward step 1/3 : partial decryption ###################
        $partiallyDecryptedCipher = $kp1->sk->partiallyDecrypt($cipher);

        // ###################  forward step 2/3 : re-encryption ###################
        $reEncryptedCipher = $partiallyDecryptedCipher->reEncryptWithRandomness($reEncryptionRandomness);

        // ###################  forward step 3/3 -> no shuffling ###################
        // ###################  backwards step 3/3 -> no deshuffling ###################

        // ################### backwards step 2/3 -> reverse re-encryption ###################
        $unReEncryptedCipher = $reEncryptedCipher->reverseReEncryptionWithRandomness($reEncryptionRandomness);

        // ###################  backwards step 1/3 -> reverse partial decryption (prove) ###################
        static::assertTrue($unReEncryptedCipher->alpha->equals($cipher->alpha));
        static::assertFalse($unReEncryptedCipher->beta->equals($cipher->beta));

        $proof = EGDLogProof::generate($kp1->sk, $cipher);

        $proofPlain = $unReEncryptedCipher->extractPlainTextFromBeta(true);

        static::assertTrue($proof->isValid($kp1->pk, $cipher, $proofPlain));

    }


}
