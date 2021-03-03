<?php


namespace App\Crypto;

use phpseclib3\Math\BigInteger;

/**
 * Class EGPublicKey
 * @package App
 * @property BigInteger $g
 * @property BigInteger $p
 * @property BigInteger $q
 * @property BigInteger $y
 */
class EGPublicKey
{

    public $g;
    public $p;
    public $q;
    public $y;

    public function __construct(BigInteger $g, BigInteger $p, BigInteger $q, BigInteger $y)
    {
        $this->g = $g;
        $this->p = $p;
        $this->q = $q;
        $this->y = $y;
    }

    /**
     * @param array $data
     * @param bool $onlyY
     * @return EGPublicKey
     */
    public static function fromArray(array $data, bool $onlyY = false): EGPublicKey
    {

        if ($onlyY) {
            // Copy from config
            return new EGPublicKey(
                new BigInteger(config('elgamal.g')),
                new BigInteger(config('elgamal.p')),
                new BigInteger(config('elgamal.q')),
                new BigInteger($data['y'])
            );
        }

        return new EGPublicKey(
            new BigInteger($data['g']),
            new BigInteger($data['p']),
            new BigInteger($data['q']),
            new BigInteger($data['y'])
        );
    }

    /**
     * @param bool $onlyY
     * @return array
     */
    public function toArray(bool $onlyY = false): array
    {

        if ($onlyY) {
            return ["y" => $this->y->toString()];
        }

        return [
            "g" => $this->g->toString(),
            "p" => $this->p->toString(),
            "q" => $this->q->toString(),
            "y" => $this->y->toString()
        ];
    }

    /**
     * Multiply the Y value
     * @param EGPublicKey $b
     * @return EGPublicKey
     * @throws \Exception
     */
    public function combine(EGPublicKey $b): EGPublicKey
    {

        if ($b->y->equals(new BigInteger(0)) || $b->y->equals(new BigInteger(1))) {
            return $this;
        }

        // P, G and Q must be the same
        if (!(
            $this->p->equals($b->p)
            && $this->q->equals($b->q)
            && $this->g->equals($b->g)
        )) {
            throw new \Exception("incompatible public keys");
        }

        // (self.y * other.y) % result.p
        return new EGPublicKey(
            $this->g,
            $this->p,
            $this->q,
            $this->y->multiply($b->y)->powMod(new BigInteger(1), $this->p)
        );

    }

    /**
     * expecting plaintext.m to be a big integer
     * @param BigInteger $plaintext
     * @param BigInteger $r
     * @param bool $encode_message
     */
    private function encrypt_with_r(BigInteger $plaintext, BigInteger $r, bool $encode_message = False): EGCiphertext
    {
        if ($encode_message) {
            $y = $plaintext + BI1();
            if ($y->modPow($this->q, $this->p)->equals(BI1())) {
                $m = $y;
            } else {
                $m = $y->modInverse($this->p);
            }
        } else {
            $m = $plaintext;
        }

        $alpha = $this->g->modPow($r, $this->p);
        $beta = $m->multiply($this->y->modPow($r, $this->p))->modPow(BI1(), $this->p);

        return new EGCiphertext($this, $alpha, $beta);
    }

    /**
     * Encrypt a plaintext and return the randomness just generated and used.
     * @param BigInteger $plaintext
     * @return array
     */
    public function encrypt_return_r(BigInteger $plaintext): array
    {
        $r = BigInteger::randomRange(BI1(), $this->q->subtract(BI1()));
        $ciphertext = $this->encrypt_with_r($plaintext, $r);
        return [$ciphertext, $r];
    }

    /**
     * @param BigInteger $plaintext
     * @return EGCiphertext
     */
    public function encrypt(BigInteger $plaintext): EGCiphertext
    {
        /** @var EGCiphertext $ciphertext */
        /** @var BigInteger $r */
        list($ciphertext, $r) = $this->encrypt_return_r($plaintext);
        return $ciphertext;
    }

}
