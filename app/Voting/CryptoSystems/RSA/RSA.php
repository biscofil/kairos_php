<?php


namespace App\Voting\CryptoSystems\RSA;


use App\Models\Election;
use App\Voting\CryptoSystems\CryptoSystem;

/**
 * Class RSA
 * @package App\Voting\CryptoSystems\RSA
 */
class RSA extends CryptoSystem
{

    public const PublicKeyClass = RSAPublicKey::class;
    public const SecretKeyClass = RSASecretKey::class;
    public const PlainTextClass = RSAPlaintext::class;
    public const CipherTextClass = RSACiphertext::class;

    private static ?RSA $instance = null;

    /**
     * Hidden
     * RSA constructor.
     */
    private function __construct()
    {

    }

    /**
     * @return RSA|null
     */
    public static function getInstance()
    {
        if (self::$instance == null) {
            self::$instance = new RSA();
        }
        return self::$instance;
    }

    /**
     * @return RSAKeyPair
     * @noinspection PhpMissingReturnTypeInspection
     */
    public function generateKeypair()
    {
        return RSAKeyPair::generate();
    }

}
