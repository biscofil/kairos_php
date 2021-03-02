<?php


namespace App\Crypto;

use phpseclib3\Math\BigInteger;

/**
 * Class EGCiphertext
 * @package App\Crypto
 * @property EGPublicKey $pk
 * @property BigInteger $alpha
 * @property BigInteger $beta
 */
class EGCiphertext
{
    public $pk;
    public $alpha;
    public $beta;

    public function __construct(EGPublicKey $pk, BigInteger $alpha, BigInteger $beta)
    {
        $this->pk = $pk;
        $this->alpha = $alpha;
        $this->beta = $beta;
    }

}
