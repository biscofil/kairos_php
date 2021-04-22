<?php


namespace App\Voting\CryptoSystems\RSA;


use App\Voting\CryptoSystems\Plaintext;
use App\Voting\CryptoSystems\SecretKey;
use phpseclib3\Crypt\RSA\PrivateKey;

/**
 * Class RSASecretKey
 * @package App\Voting\CryptoSystems\RSA
 * @property PrivateKey $value
 */
class RSASecretKey extends SecretKey
{

    public const CRYPTOSYSTEM = RSA::class;

    private PrivateKey $value;

    public function __construct(PrivateKey $sk)
    {
        $this->value = $sk;
    }

    /**
     * @param RSACiphertext $cipherText
     * @return RSAPlaintext
     */
    public function decrypt($cipherText): RSAPlaintext
    {
        return $this->value->decrypt($cipherText);
    }

    // ####################################################################################################
    // ####################################################################################################
    // ####################################################################################################

    public static function fromArray(array $data, bool $onlyXY = false, int $base = 16): RSASecretKey
    {
        $sk = PrivateKey::load($data['v']); // TODO
        return new static($sk);
    }

    /**
     * @param bool $ignoreParameterSet
     * @return array
     */
    public function toArray(bool $ignoreParameterSet = false): array
    {
        return [
            'v' => $this->value->__toString() // TODO
        ];
    }

    // ####################################################################################################
    // ####################################################################################################
    // ####################################################################################################

    /**
     * @param string $filePath
     * @param string $type Example: "PKCS8"
     * @return bool
     */
    public function toPemFile(string $filePath, string $type = "PKCS8"): bool
    {
        $content = $this->value->toString($type);
        return file_put_contents($filePath, $content);
    }

    /**
     * @param string $filePath
     * @param string $type Example: "PKCS8"
     * @return RSASecretKey
     */
    public static function fromPemFile(string $filePath, string $type = "PKCS8"): RSASecretKey
    {
        $sk = file_get_contents($filePath);
        $sk = PrivateKey::loadFormat($type, $sk);
        return new static($sk);
    }

}
