<?php


namespace App\Voting\CryptoSystems\RSA;


use App\Voting\CryptoSystems\PublicKey;
use Exception;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Crypt\RSA\PublicKey as phpsecRSA;

/**
 * Class RSAPublicKey
 * @package App\Voting\CryptoSystems\RSA
 * @property phpsecRSA $value
 */
class RSAPublicKey implements PublicKey
{

    const CRYPTOSYSTEM = RSA::class;

    public phpsecRSA $value;

    /**
     * RSAPublicKey constructor.
     * @param phpsecRSA $pk
     */
    public function __construct(phpsecRSA $pk)
    {
        $this->value = $pk;
    }

    // ######################################################################################################
    // ######################################################################################################
    // ######################################################################################################

    /**
     * @param array $data
     * @param bool $onlyY
     * @param int $base
     * @return RSAPublicKey
     */
    public static function fromArray(array $data, bool $onlyY = false, int $base = 16): RSAPublicKey
    {
        $pk = PublicKeyLoader::load($data['v']);
        return new static($pk); // TODO
    }

    /**
     * @param bool $onlyY
     * @return array
     */
    public function toArray(bool $onlyY = false): array
    {
        return [
            'v' => $this->value->toString('PKCS8') // TODO
        ];
    }

    // ######################################################################################################
    // ######################################################################################################
    // ######################################################################################################

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
     * @return RSAPublicKey
     */
    public static function fromPemFile(string $filePath, string $type = "PKCS8"): RSAPublicKey
    {
        $pk = file_get_contents($filePath);
        $pk = phpsecRSA::loadFormat($type, $pk);
        return new static($pk);
    }

    // ######################################################################################################
    // ######################################################################################################
    // ######################################################################################################

    /**
     * @return string
     */
    public function getFingerprint(): string
    {
        return base64_encode(hash('sha256', "abc")); // TODO
    }

    /**
     * @param RSAPlaintext $plainText
     * @return RSACiphertext
     * @noinspection PhpMissingParamTypeInspection
     */
    public function encrypt($plainText): RSACiphertext
    {
        return new RSACiphertext($this, $this->value->encrypt($plainText->cipherText));
    }

    /**
     * Check that the two public keys have the same values of P,G and Q
     * @param RSAPublicKey $b
     * @throws Exception
     * @noinspection PhpMissingParamTypeInspection
     */
    public function ensureSameCryptosystem($b): void
    {
        //TODO
    }


}
