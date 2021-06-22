<?php


namespace App\Voting\CryptoSystems\ElGamal;


use App\Voting\CryptoSystems\ThresholdPolynomial;
use phpseclib3\Math\BigInteger;

/**
 * Class EGThresholdPolynomial
 * @package App\Voting\CryptoSystems\ElGamal
 * @property BigInteger[] $factors
 * @property EGParameterSet $ps
 */
class EGThresholdPolynomial implements ThresholdPolynomial
{

    use BelongsToElgamal;

    public array $factors;
    public $ps;

    /**
     * EGThresholdPolynomial constructor.
     * @param array $factors
     * @param \App\Voting\CryptoSystems\ElGamal\EGParameterSet $ps
     */
    public function __construct(array $factors, EGParameterSet $ps)
    {
        $this->factors = $factors;
        $this->ps = $ps;
    }

    /**
     * @param \phpseclib3\Math\BigInteger $x secret value
     * @param int $t 0 <= t <= l-1
     * @param \App\Voting\CryptoSystems\ElGamal\EGParameterSet $ps
     * @return EGThresholdPolynomial
     */
    public static function random(BigInteger $x, int $t, EGParameterSet $ps): EGThresholdPolynomial
    {
        $factors = [$x];
        for ($i = 1; $i < $t; $i++) {
            $factors[] = randomBIgt($ps->g);
        }
        return new static($factors, $ps);
    }

    /**
     * computes the polynomial at point x without module
     * @param BigInteger $x
     * @return BigInteger
     */
    public function compute(BigInteger $x): BigInteger
    {
        $out = $this->factors[0]; // secret
        for ($i = 1; $i < count($this->factors); $i++) {
            $out = $out->add(
                $this->factors[$i]->multiply($x->pow(BI($i)))
            );
        }
        return $out;
    }

    // #######################################################################################################
    // #######################################################################################################
    // #######################################################################################################

    /**
     * @return \App\Voting\CryptoSystems\ElGamal\EGThresholdBroadcast
     */
    public function getBroadcast(): EGThresholdBroadcast
    {
        $values = [];
        foreach ($this->factors as $a_i_k) {
            // this is the same as the public key generation
            $A_i_k = $this->ps->g->powMod($a_i_k, $this->ps->p);
            $values[] = $A_i_k;
        }
        $tbClass = static::getCryptosystem()::getThresholdBroadcastClass();
        return new $tbClass($values, $this->ps);
    }

    /**
     * @param int $j
     * @return \phpseclib3\Math\BigInteger
     */
    public function getShare(int $j): BigInteger
    {
        return mod($this->compute(BI($j)), $this->ps->q);
    }

    // #######################################################################################################
    // #######################################################################################################
    // #######################################################################################################

    /**
     * @return string
     */
    public function toString(): string
    {
        return '[' . implode(' + ', array_map(function (BigInteger $factor, $key) {
                $out = $factor->toString();
                if ($key > 0) {
                    $out .= 'x' . ($key > 1 ? ('^' . $key) : '');
                }
                return $out;
            }, $this->factors, array_keys($this->factors))) . ']';
    }

    // #######################################################################################################
    // #######################################################################################################
    // #######################################################################################################

    /**
     * @param bool $ignoreParameterSet
     * @return array
     */
    public function toArray(bool $ignoreParameterSet = false): array
    {
        $out = [
            'factors' => array_map(function (BigInteger $f) {
                return $f->toHex();
            }, $this->factors)
        ];
        if (!$ignoreParameterSet) {
            $out['ps'] = $this->ps->toArray();
        }
        return $out;
    }

    /**
     * @param array $data
     * @param bool $ignoreParameterSet
     * @param int $base
     * @return EGThresholdPolynomial
     */
    public static function fromArray(array $data, bool $ignoreParameterSet = false, int $base = 16): EGThresholdPolynomial
    {
        $psClass = static::getCryptosystem()::getParameterSetClass();
        $ps = $ignoreParameterSet ? $psClass::getDefault() : $psClass::fromArray($data['ps'], $base);
        $factors = array_map(function (string $f) use ($base) {
            return new BigInteger($f, $base);
        }, $data['factors']);
        return new static($factors, $ps);
    }

}
