<?php


namespace App\Voting\CryptoSystems\ElGamal;


use App\Voting\CryptoSystems\CryptoSystemParameterSet;
use phpseclib3\Math\BigInteger;

/**
 * Class EGParameterSet
 * @package App\Voting\CryptoSystems\ElGamal
 * @property BigInteger $g big prime which is a factor of $p-1
 * @property BigInteger $p big big prime
 * @property BigInteger $q @deprecated TODO remove???
 */
class EGParameterSet implements CryptoSystemParameterSet
{

    /**
     * @var BigInteger
     */
    public BigInteger $g;

    /**
     * @var BigInteger
     */
    public BigInteger $p;

    /**
     * @deprecated
     * @var BigInteger
     */
    public BigInteger $q; // TODO remove???

    /**
     * EGParameterSet constructor.
     * @param BigInteger $g
     * @param BigInteger $p
     * @param BigInteger $q
     */
    public function __construct(BigInteger $g, BigInteger $p, BigInteger $q)
    {
        $this->g = $g;
        $this->p = $p;
        $this->q = $q;
    }

    /**
     * @param int $size
     * @return static
     */
    public static function random(int $size = 10)
    {
        // find a prime g
        $g = BigInteger::randomPrime($size);
        // find a prime p such that g is a factor of p-1
        $k = 2;
        do {
            $p = $g->multiply(BI($k))->add(BI1());
            $k++;
        } while (!$p->isPrime());
        //
        return new static($g, $p, BI(100)); // TODO q
    }

    /**
     *
     */
    public static function default()
    {
        $g = BI(config('elgamal.g'), config('elgamal.base')); // generator g
        $p = BI(config('elgamal.p'), config('elgamal.base')); // prime p
        $q = BI(config('elgamal.q'), config('elgamal.base')); // TODO check?!?!
        return new static($g, $p, $q);
    }

    /**
     * @return string
     */
    public function toString(): string
    {
        return "G={$this->g->toString()}, P={$this->p->toString()}, Q={$this->q->toString()}";
    }

    /**
     * @param array $data
     * @param int $base
     * @return static
     */
    public static function fromArray(array $data, int $base = 16): EGParameterSet
    {
        return new static(
            BI($data['g'], $base),
            BI($data['p'], $base),
            BI($data['q'], $base)
        );
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            "g" => $this->g->toHex(),
            "p" => $this->p->toHex(),
            "q" => $this->q->toHex(),
        ];
    }

    /**
     * @param \App\Voting\CryptoSystems\ElGamal\EGParameterSet $parameterSet
     * @return bool
     */
    public function equals($parameterSet): bool
    {
        return $this->p->equals($parameterSet->p)
            && $this->q->equals($parameterSet->q)
            && $this->g->equals($parameterSet->g);
    }

}
