<?php


namespace App\Crypto;


use phpseclib3\Math\BigInteger;
use phpseclib3\Crypt\RSA\PublicKey;

/**
 * Class EGPlaintext
 * @package App\Crypto
 * @property BigInteger $m
 * @property EGPublicKey $pk
 */
class EGPlaintext
{

    public $m;
    public $pk;

    public function __construct(BigInteger $m, EGPublicKey $pk)
    {
        $this->m = $m;
        $this->pk = $pk;
    }


    /**
     * @return EGCiphertext
     */
    public function encrypt(): EGCiphertext
    {
        return $this->pk->encrypt($this->m);
    }


}
