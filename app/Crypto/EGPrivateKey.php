<?php


namespace App\Crypto;

use phpseclib3\Math\BigInteger;

/**
 * Class EGPrivateKey
 * @package App
 * @property EGPublicKey $pk
 * @property BigInteger $x
 */
class EGPrivateKey
{

    public $pk;
    public $x;

    /**
     * EGPrivateKey constructor.
     * @param EGPublicKey $pk
     * @param BigInteger $x
     */
    public function __construct(EGPublicKey $pk, BigInteger $x)
    {
        $this->pk = $pk;
        $this->x = $x;
    }

    /**
     * @param array $data
     * @return EGPrivateKey
     */
    public static function fromArray(array $data): EGPrivateKey
    {
        return new EGPrivateKey(
            EGPublicKey::fromArray($data['pk']),
            new BigInteger($data['x'])
        );
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            "pk" => [
                $this->pk->toArray(),
            ],
            "x" => $this->x->toString()
        ];
    }

    /**
     * provide the decryption factor, not yet inverted because of needed proof
     * @param EGCiphertext $ciphertext
     * @return BigInteger
     */
    public function decryption_factor(EGCiphertext $ciphertext): BigInteger
    {
        return $ciphertext->alpha->modPow($this->x, $this->pk->p);
    }


    /**
     * Decrypt a ciphertext. Optional parameter decides whether to encode the message into the proper subgroup.
     * @param EGCiphertext $ciphertext
     * @param BigInteger|null $dec_factor
     * @param bool $decode_m
     * @return mixed
     */
    public function decrypt(EGCiphertext $ciphertext, BigInteger $dec_factor = null, bool $decode_m = False): EGPlaintext
    {
        if (is_null($dec_factor)) {
            $dec_factor = $this->decryption_factor($ciphertext);
        }

        $m = $dec_factor->modInverse($this->pk->p)
            ->multiply($ciphertext->beta)
            ->modPow(BI1(), $this->pk->p);

        if ($decode_m) {  # get m back from the q-order subgroup
            if ($m < $this->pk->q) {
                $y = $m;
            } else {
                $y = $m->modInverse($this->pk->p);
            }
            return new EGPlaintext($y->subtract(BI1()), $this->pk);
        } else {
            return new EGPlaintext($m, $this->pk);
        }
    }
}
