<?php


namespace App\Voting\CryptoSystems\RSA;

use App\Voting\CryptoSystems\CipherText;
use App\Voting\CryptoSystems\Plaintext;
use phpseclib3\Math\BigInteger;

/**
 * Class RSACiphertext
 * @package App\Voting\CryptoSystems\RSA;
 * @property string $cipherText
 */
class RSAPlaintext implements Plaintext
{
    public string $plainText;

    /**
     * RSAPlaintext constructor.
     * @param string $cipherText
     */
    public function __construct(string $cipherText)
    {
        $this->cipherText = $cipherText;
    }

    /**
     * @param RSACiphertext $b
     * @return bool
     * @throws \Exception
     * @noinspection PhpMissingParamTypeInspection
     */
    public function equals($b): bool
    {
        if (!$b instanceof RSACiphertext) {
            throw new \RuntimeException("RSACiphertext::equals > invalid type, must be RSACiphertext");
        }
        // TODO $this->pk->ensureSameCryptosystem($b->pk);
        return $this->cipherText === $b->cipherText;
    }

}
