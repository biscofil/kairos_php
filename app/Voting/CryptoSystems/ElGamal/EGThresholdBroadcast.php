<?php


namespace App\Voting\CryptoSystems\ElGamal;

use App\Voting\CryptoSystems\ThresholdBroadcast;
use phpseclib3\Math\BigInteger;

/**
 * Represents the set of values broadcasted from node i
 * Class EGThresholdBroadcast
 * @package App\Voting\CryptoSystems\ElGamal
 * @property BigInteger[] $values
 * @property \App\Voting\CryptoSystems\ElGamal\EGParameterSet $ps
 */
class EGThresholdBroadcast implements ThresholdBroadcast
{

    public array $values;
    public EGParameterSet $ps;

    /**
     * EGThresholdBroadcast constructor.
     * @param array $values keys should be 0..k
     * @param EGParameterSet $ps
     */
    public function __construct(array $values, EGParameterSet $ps)
    {
        $this->values = $values;
        $this->ps = $ps;
    }

    /**
     * @param BigInteger $share_i_j
     * @param int $j
     * @return bool
     */
    public function isValid(BigInteger $share_i_j, int $j): bool
    {

        $mod = $this->ps->p;

        // left part
        $left = $this->ps->g->modPow($share_i_j, $mod);
        dump("   A = {$this->ps->g->toString()}^{$share_i_j->toString()} mod {$mod->toString()} = {$left->toString()}");

        // right part
        $right = BI(1);
        foreach ($this->values as $k => $A_i_k) {

            $exp = BI(pow($j, $k)); // BI($j * $k);
            $term = $A_i_k->modPow($exp, $mod);
            $right = $right->multiply($term)->modPow(BI1(), $mod);
            dump("   B = B * [{$A_i_k->toString()}^[$j^$k] mod {$mod->toString()} = {$term->toString()}] = {$right->toString()}");

        }

        dump("{$left->toString()} =? {$right->toString()}");

        return $left->equals($right);
    }

    // #######################################################################################################
    // #######################################################################################################
    // #######################################################################################################

    /**
     * @return string
     */
    public function toString()
    {
        return implode(",", array_map(function (BigInteger $n) {
            return $n->toString();
        }, $this->values));
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'ps' => $this->ps->toArray(),
            'values' => array_map(function (BigInteger $f) {
                return $f->toHex();
            }, $this->values)
        ];
    }

    /**
     * @param array $data
     * @return EGThresholdBroadcast
     */
    public static function fromArray(array $data): EGThresholdBroadcast
    {
        $ps = EGParameterSet::fromArray($data['ps']);
        $values = array_map(function (string $f) {
            return new BigInteger($f, 16);
        }, $data['values']);
        return new static($values, $ps);
    }

}
