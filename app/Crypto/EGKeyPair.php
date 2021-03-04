<?php


namespace App\Crypto;


use phpseclib3\Math\BigInteger;

/**
 * Class EGKeyPair
 * @package App\Crypto
 * @property EGPublicKey $pk
 * @property EGPrivateKey $sk
 */
class EGKeyPair
{
    public $pk;
    public $sk;

    public function __construct(EGPublicKey $pk, EGPrivateKey $sk)
    {
        $this->pk = $pk;
        $this->sk = $sk;
    }

    /**
     * Generate an ElGamal keypair
     */
    public static function generate(): EGKeyPair
    {

        $g = new BigInteger(config('elgamal.g'), config('elgamal.base'));
        $p = new BigInteger(config('elgamal.p'), config('elgamal.base'));
        $q = new BigInteger(config('elgamal.q'), config('elgamal.base'));

        $x = BigInteger::randomRange(new BigInteger(1), $q->subtract(BI1()));
        $y = $g->modPow($x, $p);

        $pk = new EGPublicKey($g, $p, $q, $y);
        $sk = new EGPrivateKey($pk, $x);

        return new EGKeyPair($pk, $sk);
    }


}
