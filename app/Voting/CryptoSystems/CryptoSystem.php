<?php


namespace App\Voting\CryptoSystems;

use App\Enums\CryptoSystemEnum;
use App\Models\Election;
use App\Voting\CryptoSystems\ElGamal\EGPrivateKey;
use App\Voting\CryptoSystems\ElGamal\EGPublicKey;
use App\Voting\CryptoSystems\ElGamal\ElGamal;
use App\Voting\CryptoSystems\RSA\RSA;
use App\Voting\CryptoSystems\RSA\RSAPublicKey;
use phpseclib3\File\ASN1\Maps\RSAPrivateKey;

/**
 * Class CryptoSystem
 * @package App\Voting\CryptoSystems
 */
abstract class CryptoSystem
{

    // these have to be set by each CryptoSystem that extends this class
    const PublicKeyClass = null;
    const SecretKeyClass = null;
    const PlainTextClass = null;
    const CipherTextClass = null;
    const ThresholdBroadcastClass = null;

    const CryptoSystems = [
        CryptoSystemEnum::ElGamal => ElGamal::class,
        CryptoSystemEnum::RSA => RSA::class
    ];

    /**
     * @param string $cryptoSystemIdentifier
     * @return string
     */
    public static function getByIdentifier(string $cryptoSystemIdentifier): string
    {
        if (!array_key_exists($cryptoSystemIdentifier, self::CryptoSystems)) {
            throw new \RuntimeException("Invalid cryptosystem " . $cryptoSystemIdentifier);
        }
        return CryptoSystem::CryptoSystems[$cryptoSystemIdentifier]; // ElGamal::class, RSA::class, ...
    }

    /**
     * @param RSAPublicKey|RSAPrivateKey|EGPublicKey|EGPrivateKey $obj
     * @return mixed
     */
    public static function getIdentifier($obj): string
    {
        $v = array_flip(self::CryptoSystems); // [ ElGamal::class => 'eg', ... ]
        $key = $obj::CRYPTOSYSTEM;
        if (!array_key_exists($key, $v)) {
            throw new \RuntimeException("Unhandled type " . $key);
        }
        return $v[$key];
    }

    /**
     * @return KeyPair
     */
    public abstract function generateKeypair();

    /**
     * @param Election $election
     */
    public function onElectionFreeze(Election &$election): void
    {
        // do nothing, Example: RSA
    }

    /**
     * @param Election $election
     */
    public function afterAnonymizationProcessEnds(Election &$election): void
    {
        // do nothing, Example: RSA
    }

}
