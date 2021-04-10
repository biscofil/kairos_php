<?php


namespace App\Voting\CryptoSystems\ElGamal;


use phpseclib3\Math\BigInteger;

/**
 * Class EGThresholdPolynomial
 * @package App\Voting\CryptoSystems\ElGamal
 * @property BigInteger[] $factors
 * @property EGPublicKey $pk
 */
class EGThresholdPolynomial
{

    public array $factors;
    public EGPublicKey $pk;

    /**
     * EGThresholdPolynomial constructor.
     * @param array $factors
     * @param EGPublicKey $pk
     */
    public function __construct(array $factors, EGPublicKey $pk)
    {
        $this->factors = $factors;
        $this->pk = $pk;
    }

    /**
     * @param int $degree
     * @param EGPublicKey $pk
     * @return EGThresholdPolynomial
     */
    public static function random(int $degree, EGPublicKey $pk): EGThresholdPolynomial
    {
        $out = [];
        for ($i = 0; $i <= $degree; $i++) {
            $out[] = randomBIgt($pk->p); // TODO check
        }
        return new static($out, $pk);
    }

    /**
     * @param BigInteger $x
     * @return BigInteger
     */
    public function compute(BigInteger $x): BigInteger
    {
        $out = $this->factors[0];
        for ($i = 1; $i < count($this->factors); $i++) {
            $out = $out->add(
                $this->factors[$i]->multiply($x->modPow(BI($i), $this->pk->p)) // TODO check Q / P
            )->modPow(BI1(), $this->pk->p); // TODO check Q / P
        }
        return $out->modPow(BI1(), $this->pk->p); // TODO check Q / P
    }

    /**
     * @return EGThresholdBroadcast
     */
    public function getBroadcast(): EGThresholdBroadcast
    {
        $values = [];
        foreach ($this->factors as $k => $a_i_k) {
            $A_i_k = $this->pk->g->modPow($a_i_k, $this->pk->q); // TODO check Q / P
            $values[] = $A_i_k;
        }
        return new EGThresholdBroadcast($values, $this->pk);
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
                    $out .= 'x';
                    if ($key > 1) {
                        $out .= '^' . $key;
                    }
                }
                return $out;
            }, $this->factors, array_keys($this->factors))) . " mod " . $this->pk->p->toString();
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
            'pk' => $this->pk->toArray(),
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
        $pk = EGPublicKey::fromArray($data['pk']);
        $factors = array_map(function (string $f) {
            return new BigInteger($f, 16);
        }, $data['factors']);
        return new static($factors, $pk);
    }

}
