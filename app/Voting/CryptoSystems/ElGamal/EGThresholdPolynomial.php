<?php


namespace App\Voting\CryptoSystems\ElGamal;


use phpseclib3\Math\BigInteger;

/**
 * Class EGThresholdPolynomial
 * @package App\Voting\CryptoSystems\ElGamal
 * @property BigInteger[] $factors
 * @property EGParameterSet $ps
 */
class EGThresholdPolynomial
{

    public array $factors;
    public EGParameterSet $ps;

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
            $A_i_k = $this->ps->g->modPow($a_i_k, $this->ps->p);
            $values[] = $A_i_k;
        }
//        dump("{$this->id} is broadcasting " . $b->toString());
        return new EGThresholdBroadcast($values, $this->ps);
    }

    /**
     * @param int $j
     * @return \phpseclib3\Math\BigInteger
     */
    public function getShare(int $j)
    {
        return $this->compute(BI($j))->modPow(BI1(), $this->ps->q);
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
     * @return array
     */
    public function toArray(): array
    {
        return [
            'ps' => $this->ps->toArray(),
            'factors' => array_map(function (BigInteger $f) {
                return $f->toHex();
            }, $this->factors)
        ];
    }

    /**
     * @param array $data
     * @return EGThresholdPolynomial
     */
    public static function fromArray(array $data): EGThresholdPolynomial
    {
        $ps = EGParameterSet::fromArray($data['ps']);
        $factors = array_map(function (string $f) {
            return new BigInteger($f, 16);
        }, $data['factors']);
        return new static($factors, $ps);
    }

}
