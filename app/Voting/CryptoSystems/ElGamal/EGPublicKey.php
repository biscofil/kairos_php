<?php


namespace App\Voting\CryptoSystems\ElGamal;


use App\Voting\CryptoSystems\Plaintext;
use App\Voting\CryptoSystems\PublicKey;
use phpseclib3\Math\BigInteger;

/**
 * Class EGPublicKey
 * @package App\Voting\CryptoSystems\ElGamal
 * @property BigInteger $g
 * @property BigInteger $p
 * @property BigInteger $q
 * @property BigInteger $y
 */
class EGPublicKey implements PublicKey
{

    const CRYPTOSYSTEM = ElGamal::class;

    public BigInteger $g;
    public BigInteger $p;
    public BigInteger $q;
    public BigInteger $y;

    /**
     * EGPublicKey constructor.
     * @param BigInteger $g
     * @param BigInteger $p
     * @param BigInteger $q
     * @param BigInteger $y
     */
    public function __construct(BigInteger $g, BigInteger $p, BigInteger $q, BigInteger $y)
    {
        $this->g = $g;
        $this->p = $p;
        $this->q = $q;
        $this->y = $y;
    }


    // ####################################################################
    // ####################################################################
    // ####################################################################

    /**
     * @param array $data
     * @param bool $onlyY
     * @param int $base
     * @return EGPublicKey
     */
    public static function fromArray(array $data, bool $onlyY = false, int $base = 16): EGPublicKey
    {

        if ($onlyY) {
            // Copy from config
            return new EGPublicKey(
                BI(config('elgamal.g'), config('elgamal.base')),
                BI(config('elgamal.p'), config('elgamal.base')),
                BI(config('elgamal.q'), config('elgamal.base')),
                BI($data['y'], $base)
            );
        }

        return new EGPublicKey(
            BI($data['g'], $base),
            BI($data['p'], $base),
            BI($data['q'], $base),
            BI($data['y'], $base)
        );
    }

    /**
     * @param bool $onlyY
     * @return array
     */
    public function toArray(bool $onlyY = false): array
    {

        if ($onlyY) {
            return ["y" => $this->y->toHex()];
        }

        return [
            "g" => $this->g->toHex(),
            "p" => $this->p->toHex(),
            "q" => $this->q->toHex(),
            "y" => $this->y->toHex()
        ];
    }

    // ####################################################################
    // ####################################################################
    // ####################################################################

    /**
     * Check that the two public keys have the same values of P,G and Q
     * @param EGPublicKey $b
     * @throws \Exception
     * @noinspection PhpMissingParamTypeInspection
     */
    public function ensureSameCryptosystem($b): void
    {
        // P, G and Q must be the same
        if (!(
            $this->p->equals($b->p)
            && $this->q->equals($b->q)
            && $this->g->equals($b->g)
        )) {
            throw new \Exception("incompatible public keys");
        }
    }

    /**
     * Returns the combination of two public keys,
     * that is the product of A.y and B.y mod P
     * @param null|EGPublicKey $b
     * @return EGPublicKey
     * @throws \Exception
     */
    public function combine(?EGPublicKey $b): EGPublicKey
    {

        if (is_null($b) || $b->y->equals(BI(0)) || $b->y->equals(BI1())) {
            return $this;
        }

        $this->ensureSameCryptosystem($b);

        return new EGPublicKey(
            $this->g,
            $this->p,
            $this->q,
            $this->y->multiply($b->y)->powMod(BI1(), $this->p)
        );

    }

    /**
     * verify the proof of knowledge of the secret key g^response = commitment * y^challenge
     * @param DLogProof $dlog_proof
     * @param callable $challenge_generator
     * @return bool
     */
    public function verifySecretKeyProof(DLogProof $dlog_proof, callable $challenge_generator): bool
    {

        $left_side = $this->g->modPow($dlog_proof->response, $this->p);
        $right_side = $dlog_proof->commitment
            ->multiply($this->y->modPow($dlog_proof->challenge, $this->p))
            ->modPow(BI1(), $this->p);

        /** @var BigInteger $expected_challenge */
        $expected_challenge = $challenge_generator($dlog_proof->commitment)->modPow(BI1(), $this->q);

        return $left_side->equals($right_side) && $dlog_proof->challenge->equals($expected_challenge);

    }

    /**
     * @return string
     */
    public function getFingerprint(): string
    {
        return base64_encode(hash('sha256', $this->y));
    }

    // ####################################################################
    // ####################################################################
    // ####################################################################

    /**
     * @param Plaintext $plainText
     * @return EGCiphertext
     * @noinspection PhpMissingParamTypeInspection
     */
    public function encrypt($plainText): EGCiphertext
    {
        /** @var EGCiphertext $ciphertext */
        /** @var BigInteger $r */
        /** @noinspection PhpUnusedLocalVariableInspection */
        list($ciphertext, $r) = $this->encryptAndReturnRandomness($plainText);
        return $ciphertext;
    }

    /**
     * Encrypt a plaintext and return the randomness just generated and used.
     * @param EGPlaintext $plainText
     * @return array
     */
    public function encryptAndReturnRandomness(EGPlaintext $plainText): array
    {
        $r = randomBIgt($this->q); // 0 < r < q-1 : randomness
        $ciphertext = $this->encryptWithRandomness($plainText, $r);
        return [$ciphertext, $r];
    }

    /**
     * @param EGPlaintext $plainText
     * @param BigInteger $randomness r
     * @param bool $encode_message
     * @return EGCiphertext
     */
    public function encryptWithRandomness(EGPlaintext $plainText, BigInteger $randomness, bool $encode_message = false): EGCiphertext
    {

        if ($encode_message) {
            // TODO what is this??????
            $y = $plainText->m->add(BI1());
            if ($y->modPow($this->q, $this->p)->equals(BI1())) {
                $m = $y;
            } else {
                $m = $y->modInverse($this->p);
            }
        } else {
            $m = $plainText->m;
        }

        // alpha = g^r mod p
        $alpha = $this->g->modPow($randomness, $this->p);
        // beta = m*(y^r) mod p
        $beta = $m->multiply($this->y->modPow($randomness, $this->p))->modPow(BI1(), $this->p);

        return new EGCiphertext($this, $alpha, $beta);
    }

}
