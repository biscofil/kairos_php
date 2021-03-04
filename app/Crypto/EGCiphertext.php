<?php


namespace App\Crypto;

use phpseclib3\Math\BigInteger;

/**
 * Class EGCiphertext
 * @package App\Crypto
 * @property EGPublicKey $pk
 * @property BigInteger $alpha
 * @property BigInteger $beta
 */
class EGCiphertext
{
    public $pk;
    public $alpha;
    public $beta;

    public function __construct(EGPublicKey $pk, BigInteger $alpha, BigInteger $beta)
    {
        $this->pk = $pk;
        $this->alpha = $alpha;
        $this->beta = $beta;
    }

    /**
     * @param array $data
     * @param bool $onlyY
     * @return EGCiphertext
     */
    public static function fromArray(array $data, bool $onlyY = false): EGCiphertext
    {
        return new EGCiphertext(
            EGPublicKey::fromArray($data['pk'], $onlyY),
            new BigInteger($data['alpha'], 16),
            new BigInteger($data['beta'], 16)
        );
    }

    /**
     * @param bool $onlyY
     * @return array
     */
    public function toArray(bool $onlyY = false): array
    {
        return [
            'pk' => $this->pk->toArray($onlyY),
            'alpha' => $this->alpha->toHex(),
            'beta' => $this->beta->toHex()
        ];
    }

}
