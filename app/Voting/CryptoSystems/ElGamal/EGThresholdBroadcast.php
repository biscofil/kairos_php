<?php


namespace App\Voting\CryptoSystems\ElGamal;

use App\Voting\CryptoSystems\ThresholdBroadcast;
use phpseclib3\Math\BigInteger;

/**
 * Represents the set of values broadcasted from node i
 * Class EGThresholdBroadcast
 * @package App\Voting\CryptoSystems\ElGamal
 * @property BigInteger[] $values
 * @property EGPublicKey $pk
 */
class EGThresholdBroadcast implements ThresholdBroadcast
{

    public array $values;
    public EGPublicKey $pk;

    /**
     * EGThresholdBroadcast constructor.
     * @param array $values keys should be 0..k
     * @param EGPublicKey $pk
     */
    public function __construct(array $values, EGPublicKey $pk)
    {
        $this->values = $values;
        $this->pk = $pk;
    }

    /**
     * @param BigInteger $share_i_j
     * @param int $j
     * @return bool
     */
    public function isValid(BigInteger $share_i_j, int $j): bool
    {

        $g_s_i_j = $this->pk->g->modPow($share_i_j, $this->pk->q); // TODO mod?

        $prod = BI(1);
        foreach ($this->values as $k => $b) {
            $prod = $prod->multiply($b
                ->modPow(BI(pow($j, $k)), $this->pk->q)
            )->modPow(BI1(), $this->pk->q);
        }

        return $g_s_i_j->equals($prod);
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
        $pk = EGPublicKey::fromArray($data['pk']);
        $values = array_map(function (string $f) {
            return new BigInteger($f, 16);
        }, $data['values']);
        return new static($values, $pk);
    }

}
