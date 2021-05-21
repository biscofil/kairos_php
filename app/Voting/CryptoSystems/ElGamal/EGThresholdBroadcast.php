<?php


namespace App\Voting\CryptoSystems\ElGamal;

use App\Voting\CryptoSystems\BelongsToCryptoSystem;
use App\Voting\CryptoSystems\ThresholdBroadcast;
use phpseclib3\Math\BigInteger;

/**
 * Represents the set of values broadcasted from node i
 * Class EGThresholdBroadcast
 * @package App\Voting\CryptoSystems\ElGamal
 * @property BigInteger[] $A_I_K_values
 * @property \App\Voting\CryptoSystems\ElGamal\EGParameterSet $ps
 */
class EGThresholdBroadcast implements ThresholdBroadcast
{

    use BelongsToElgamal;

    public array $A_I_K_values;
    public $ps;

    /**
     * EGThresholdBroadcast constructor.
     * @param array $A_I_K_values keys should be 0..k
     * @param EGParameterSet $ps
     */
    public function __construct(array $A_I_K_values, EGParameterSet $ps)
    {
        $this->A_I_K_values = $A_I_K_values;
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

        $left = $this->ps->g->modPow($share_i_j, $mod); // mod

//        dump("   A = {$this->ps->q->toString()}^{$share_i_j->toString()} mod {$mod->toString()} = {$left->toString()}");// mod

        // right part
        $right = BI(1);
        foreach ($this->A_I_K_values as $k => $A_i_k) {

            $exp = BI(pow($j, $k));
//              $exp =BI($j * $k);

            $term = $A_i_k->modPow($exp, $mod);
            $right = $right->multiply($term)->modPow(BI1(), $mod);
//            dump("   B = B * [{$A_i_k->toString()}^[$j^$k] mod {$mod->toString()} = {$term->toString()}] = {$right->toString()}");

        }

//        dump("{$left->toString()} =? {$right->toString()}");

        return $left->equals($right);
    }

    // #######################################################################################################
    // #######################################################################################################
    // #######################################################################################################

    /**
     * @return string
     */
    public function toString(): string
    {
        return implode(',', array_map(function (BigInteger $n) {
            return $n->toString();
        }, $this->A_I_K_values));
    }

    /**
     * @param bool $ignoreParameterSet
     * @return array
     */
    public function toArray(bool $ignoreParameterSet = false): array
    {
        $out = [
            'a_i_k_values' => array_map(function (BigInteger $f) {
                return $f->toHex();
            }, $this->A_I_K_values)
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
     * @return EGThresholdBroadcast
     */
    public static function fromArray(array $data, bool $ignoreParameterSet = false, int $base = 16): EGThresholdBroadcast
    {
        $psClass = static::getCryptosystem()::getParameterSetClass();
        $ps = $ignoreParameterSet ? $psClass::getDefault() : $psClass::fromArray($data['ps'], $base);
        $a_i_k_values = array_map(function (string $f) use ($base) {
            return new BigInteger($f, $base);
        }, $data['a_i_k_values']);
        return new static($a_i_k_values, $ps);
    }

    /**
     * @param \App\Voting\CryptoSystems\ElGamal\EGThresholdBroadcast $broadcast
     * @return bool
     */
    public function equals($broadcast): bool
    {
        // check parameter set
        if (!$this->ps->equals($broadcast->ps)) {
            return false;
        }
        // check factors
        foreach ($this->A_I_K_values as $idx => $value) {
            if (!$value->equals($broadcast->A_I_K_values[$idx])) {
                return false;
            }
        }
        return true;
    }

}
