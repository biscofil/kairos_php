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
     * @param int $t 0 <= t <= l-1
     * @param \App\Voting\CryptoSystems\ElGamal\EGParameterSet $ps
     * @return EGThresholdPolynomial
     */
    public static function random(int $t, EGParameterSet $ps): EGThresholdPolynomial
    {
        $out = [];
        for ($i = 0; $i <= $t; $i++) {
            $out[] = randomBIgt($ps->p);
        }
        return new static($out, $ps);
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
     * @return string
     */
    public function toString(): string
    {
        return implode("+", array_map(function (BigInteger $factor, $key) {
                $out = $factor->toString();
                if ($key > 0) {
                    $out .= 'x' . ($key > 1 ? ('^' . $key) : "");
                }
                return $out;
            }, $this->factors, array_keys($this->factors))) . " mod " .  $this->ps->p->toString();
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
