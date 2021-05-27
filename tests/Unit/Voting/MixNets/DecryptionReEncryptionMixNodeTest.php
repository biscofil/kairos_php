<?php


namespace Tests\Unit\Voting\MixNets;


use App\Voting\CryptoSystems\ElGamal\EGDLogProof;
use App\Voting\CryptoSystems\ElGamal\EGKeyPair;
use App\Voting\CryptoSystems\ElGamal\EGParameterSet;
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

        $ps = EGParameterSet::getDefault();

        $kp1 = EGKeyPair::generate($ps);
        $kp2 = EGKeyPair::generate($ps);

        $pk = $kp1->pk->combine($kp2->pk);
        //$sk = $kp1->sk->combine($kp2->sk);

        $plain = new EGPlaintext(BigInteger::random(20));
        $cipher = $pk->encrypt($plain); // calls mapMessageIntoSubgroup(plaintext)

        // ################################################################################### mixnet starts here

//        $reEncryptionRandomness = BigInteger::random($size);
        $reEncryptionRandomness = randomBIgt($kp1->pk->parameterSet->q);

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

        $proof = EGDLogProof::generate($kp1->sk, $cipher,
            [EGDLogProof::class, 'DLogChallengeGenerator']);

        $unReEncryptedCipher->beta = $kp1->pk->parameterSet->extractMessageFromSubgroup($unReEncryptedCipher->beta);
        $proofPlain = new EGPlaintext($unReEncryptedCipher->beta);

        static::assertTrue($proof->verify($kp1->pk, $cipher, $proofPlain,
            [EGDLogProof::class, 'DLogChallengeGenerator']));

    }


}
