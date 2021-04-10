<?php


namespace App\Voting\CryptoSystems\ElGamal;


use App\Models\Election;
use App\Models\Trustee;
use App\Voting\CryptoSystems\CryptoSystem;
use App\Voting\CryptoSystems\SupportsReEncryption;

/**
 * Class ElGamal
 * @package App\Voting\CryptoSystems\ElGamal
 */
class ElGamal extends CryptoSystem implements SupportsReEncryption
{

    const PublicKeyClass = EGPublicKey::class;
    const SecretKeyClass = EGPrivateKey::class;
    const PlainTextClass = EGPlaintext::class;
    const CipherTextClass = EGCiphertext::class;
    const ThresholdBroadcastClass = EGThresholdBroadcast::class;

    private static ?ElGamal $instance = null;

    /**
     * Hidden
     * ElGamal constructor.
     */
    private function __construct()
    {

    }

    /**
     * @return ElGamal|null
     */
    public static function getInstance()
    {
        if (self::$instance == null) {
            self::$instance = new ElGamal();
        }
        return self::$instance;
    }

    /**
     * @return EGKeyPair
     * @noinspection PhpMissingReturnTypeInspection
     */
    public function generateKeypair()
    {
        return EGKeyPair::generate();
    }

    // #########################################################################
    // #########################################################################
    // #########################################################################

    public function onElectionFreeze(Election &$election): void
    {
        self::generateCombinedPublicKey($election);
    }

    public function afterAnonymizationProcessEnds(Election &$election): void
    {
        $this->generateCombinedPrivateKey($election);
    }

    // #########################################################################
    // #########################################################################
    // #########################################################################

    /**
     * Returns a public key which is the combination (product) of the public keys of the trustees
     * @param Election $election
     * @return void
     */
    public function generateCombinedPublicKey(Election &$election): void
    {
        $election->public_key = $election->trustees()->get()->reduce(function (?EGPublicKey $carry, Trustee $trustee): EGPublicKey {
            return $trustee->public_key->combine($carry);
        });
    }

    /**
     * @param Election $election
     * @return void
     */
    public function generateCombinedPrivateKey(Election &$election): void
    {
        /** @var EGPrivateKey $out */
        $election->private_key = $election->trustees()->get()->reduce(function (?EGPrivateKey $carry, Trustee $trustee): EGPrivateKey {
            return $trustee->private_key->combine($carry);
        });
    }

}
