<?php


namespace App\Voting\CryptoSystems\ElGamal;


use App\Voting\CryptoSystems\CryptoSystemParameterSet;
use phpseclib3\Math\BigInteger;

/**
 * Class EGParameterSet
 * @package App\Voting\CryptoSystems\ElGamal
 * @property BigInteger $p big safe prime $p = 2$g + 1 where $g is also prime.
 * @property BigInteger $q big prime which is a factor of $p-1
 * @property BigInteger $g q such that q^g mod p = 1
 */
class EGParameterSet implements CryptoSystemParameterSet
{

    /**
     * @var BigInteger
     */
    public BigInteger $p;

    /**
     * @var BigInteger
     */
    public BigInteger $q;

    /**
     * @var BigInteger
     */
    public BigInteger $g;

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
     *
     */
    public static function default(): EGParameterSet
    {
        $p = BI(config('elgamal.p'), config('elgamal.base')); // prime p
        // NOTE: Q,G are inverted!!!
        $g = BI(config('elgamal.q'), config('elgamal.base'));
        // NOTE: Q,G are inverted!!!
        $q = BI(config('elgamal.g'), config('elgamal.base'));
        return new static($g, $p, $q);
    }

    /**
     * TODO p is not not 2q+1
     * @param int $size
     * @return static
     */
    public static function random(int $size = 10): EGParameterSet
    {
        // find a prime g
        $q = BigInteger::randomPrime($size);

        // find a prime p such that q is a factor of p-1
        $k = 2;
        do {
            $p = $q->multiply(BI($k))->add(BI1());
            $k++;
        } while (!$p->isPrime());

        // g^q mod p must be 1
        $g = BI(1);
        do {
            $g = BigInteger::randomRange(BI(1), $p);
        } while (!$g->modPow($q, $p)->equals(BI(1)));

        return new static($g, $p, $q);
    }

    // ############################################################
    // ############################################################
    // ############################################################

    /**
     * @return string
     */
    public function toString(): string
    {
        return "< G={$this->g->toString()}, P={$this->p->toString()}, Q={$this->q->toString()} >";
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
            'g' => $this->g->toHex(),
            'p' => $this->p->toHex(),
            'q' => $this->q->toHex(),
        ];
    }

    // ############################################################
    // ############################################################
    // ############################################################

    /**
     * Encode the message into the proper q-order subgroup.
     * @param \phpseclib3\Math\BigInteger $m
     * @return \phpseclib3\Math\BigInteger
     */
    public function mapMessageIntoSubgroup(BigInteger $m): BigInteger
    {
        $m = $m->add(BI1());
        if (!$m->modPow($this->q, $this->p)->equals(BI1())) {
            $m = $m->negate()->modPow(BI1(), $this->p);
        }
        return $m;
    }

    /**
     * Get the message back from the q-order subgroup
     * @param \phpseclib3\Math\BigInteger $m
     * @return \phpseclib3\Math\BigInteger
     */
    public function extractMessageFromSubgroup(BigInteger $m): BigInteger
    {
        if ($m >= $this->q) {
            $m = $m->negate()->modPow(BI1(), $this->p);
        }
        return $m->subtract(BI1());
    }

    // ############################################################
    // ############################################################
    // ############################################################

    /**
     * @param \App\Voting\CryptoSystems\ElGamal\EGParameterSet $parameterSet
     * @return bool
     */
    public function equals($parameterSet): bool
    {
        return $this->p->equals($parameterSet->p)
            && $this->g->equals($parameterSet->g)
            && $this->q->equals($parameterSet->q);
    }

    /**
     * Checks the validity of the parameters
     * @return bool
     */
    public function isValid(): bool
    {
        // p has to be prime
        if (!$this->p->isPrime()) {
            return false;
        }
        // q has to be prime
        if (!$this->q->isPrime()) {
            return false;
        }

        // q has to be a factor of p-1
        list($quotient, $remainder) = $this->p->subtract(BI1())->divide($this->q);
        if (!$remainder->equals(BI(0))) {
            return false;
        }

        // g^q mod p must be 1
        return $this->g->modPow($this->q, $this->p)->equals(BI(1));

    }

}
