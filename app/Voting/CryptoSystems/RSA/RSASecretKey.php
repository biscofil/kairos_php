<?php


namespace App\Voting\CryptoSystems\RSA;


use App\Voting\CryptoSystems\SecretKey;
use phpseclib3\Crypt\RSA\PrivateKey;

/**
 * Class RSASecretKey
 * @package App\Voting\CryptoSystems\RSA
 * @property PrivateKey $value
 */
class RSASecretKey implements SecretKey
{

    use BelongsToRSA;

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
        return new RSAPlaintext($this->value->decrypt($cipherText->cipherText));
    }

    // ####################################################################################################
    // ####################################################################################################
    // ####################################################################################################

    /**
     * @return string
     */
    public function toString(): string
    {
        return $this->value->__toString();
    }

    /**
     * @param array $data
     * @param bool $ignoreParameterSet
     * @param int $base
     * @return \App\Voting\CryptoSystems\RSA\RSASecretKey
     */
    public static function fromArray(array $data, bool $ignoreParameterSet = false, int $base = 16): RSASecretKey
    {
        $sk = PrivateKey::load($data['v']);
        /** @noinspection PhpParamsInspection */
        return new static($sk);
    }

    /**
     * @param bool $ignoreParameterSet
     * @return array
     */
    public function toArray(bool $ignoreParameterSet = false): array
    {
        return [
            'v' => $this->toString()
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
    public function toPemFile(string $filePath, string $type = 'PKCS8'): bool
    {
        $content = $this->value->toString($type);
        return file_put_contents($filePath, $content);
    }

    /**
     * @param string $filePath
     * @param string $type Example: "PKCS8"
     * @return RSASecretKey
     */
    public static function fromPemFile(string $filePath, string $type = 'PKCS8'): RSASecretKey
    {
        $sk = file_get_contents($filePath);
        $sk = PrivateKey::loadFormat($type, $sk);
        /** @noinspection PhpParamsInspection */
        return new static($sk);
    }

}
