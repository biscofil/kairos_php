<?php

namespace App\Voting\CryptoSystems\ElGamal;

use phpseclib3\Math\BigInteger;

/**
 * Class EGDLogCommitment
 * @package App\Voting\CryptoSystems\ElGamal;
 * @property BigInteger a
 * @property BigInteger b
 */
class EGDLogCommitment
{

    public BigInteger $a;
    public BigInteger $b;

    public function __construct(BigInteger $a, BigInteger $b)
    {
        $this->a = $a;
        $this->b = $b;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'a' => $this->a->toHex(),
            'b' => $this->b->toHex()
        ];
    }

    /**
     * @param array $data
     * @return static
     */
    public static function fromArray(array $data): self
    {
        return new static(
            BI($data['a'], 16),
            BI($data['b'], 16)
        );
    }

}
