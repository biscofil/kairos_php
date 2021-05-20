<?php


namespace App\Voting\CryptoSystems\ElGamal;


use App\Voting\CryptoSystems\Plaintext;
use phpseclib3\Math\BigInteger;

/**
 * Class EGPlaintext
 * @package App\Voting\CryptoSystems\ElGamal
 * @property BigInteger $m
 */
class EGPlaintext implements Plaintext
{

    public BigInteger $m;

    /**
     * EGPlaintext constructor.
     * @param BigInteger|string $m BigInt or hex string
     */
    public function __construct($m)
    {
        if (!$m instanceof BigInteger) {
            $m = BI($m, 16);
        }
        $this->m = $m;
    }

    /**
     * @param EGPlaintext $b
     * @return bool
     * @noinspection PhpMissingParamTypeInspection
     */
    public function equals($b): bool
    {
        return $this->m->equals($b->m);
    }

    /**
     * @return string
     */
    public function toString(): string
    {
        return $this->m->toHex();
    }
}
