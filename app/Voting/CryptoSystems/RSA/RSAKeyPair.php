<?php


namespace App\Voting\CryptoSystems\RSA;


use App\Voting\CryptoSystems\KeyPair;
use Illuminate\Support\Facades\Storage;
use phpseclib3\Crypt\RSA\PrivateKey;

/**
 * Class RSAKeyPair
 * @package App\Voting\CryptoSystems\RSA
 * @property RSAPublicKey $pk
 * @property RSASecretKey $sk
 */
class RSAKeyPair implements KeyPair
{
    use BelongsToRSA;

    public RSAPublicKey $pk;
    public RSASecretKey $sk;

    public function __construct(RSAPublicKey $pk, RSASecretKey $sk)
    {
        $this->pk = $pk;
        $this->sk = $sk;
    }

    /**
     * @param \App\Voting\CryptoSystems\RSA\RSAParameterSet $parameterSet
     * @return \App\Voting\CryptoSystems\RSA\RSAKeyPair
     */
    public static function generate($parameterSet = null): RSAKeyPair
    {
        $sk = PrivateKey::createKey();
        $pk = $sk->getPublicKey();
        return new static(new RSAPublicKey($pk), new RSASecretKey($sk));
    }

    // ####################################################################################################
    // ####################################################################################################
    // ####################################################################################################

    /**
     * @param string $filePath Example: "/home/private_key.json"
     */
    public function storeToFile(string $filePath): void
    {
        $content = json_encode($this->sk->toArray(), JSON_PRETTY_PRINT);
        Storage::put($filePath, $content);
    }

    /**
     * @param string $filePath Example: "/home/private_key.json"
     * @return RSAKeyPair
     */
    public static function fromFile(string $filePath): RSAKeyPair
    {
        $content = Storage::get($filePath);
        $sk = RSASecretKey::fromArray(json_decode($content));
        $pk = $sk->value->getPublicKey();
        return new static($pk, $sk);
    }

    // ####################################################################################################
    // ####################################################################################################
    // ####################################################################################################

    /**
     * @param string $skFilePath
     * @param string $pkFilePath
     * @param string $type Example: "PKCS8"
     * @return bool
     */
    public function toPemFiles(string $skFilePath, string $pkFilePath, $type = 'PKCS8'): bool
    {
        return $this->sk->toPemFile($skFilePath, $type) && $this->pk->toPemFile($pkFilePath, $type);
    }

    /**
     * @param string $skFilePath
     * @param string $pkFilePath
     * @param string $type Example: "PKCS8"
     * @return RSAKeyPair
     */
    public static function fromPemFiles(string $skFilePath, string $pkFilePath, string $type = 'PKCS8'): RSAKeyPair
    {
        $sk = RSASecretKey::fromPemFile($skFilePath, $type);
        $pk = RSAPublicKey::fromPemFile($pkFilePath, $type);
        return new static($pk, $sk);
    }
}
