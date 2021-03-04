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
     * @param bool $onlyXY
     * @param int $base
     * @return EGPrivateKey
     */
    public static function fromArray(array $data, bool $onlyXY = false, int $base = 16): EGPrivateKey
    {
        return new EGPrivateKey(
            EGPublicKey::fromArray($data['pk'], $onlyXY, $base),
            new BigInteger($data['x'], $base)
        );
    }

    /**
     * @param bool $onlyXY
     * @return array
     */
    public function toArray(bool $onlyXY = false): array
    {
        return [
            "pk" => $this->pk->toArray($onlyXY),
            "x" => $this->x->toHex()
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

    /**
     * @param BigInteger $commitment
     * @return BigInteger
     */
    public static function DLogChallengeGenerator(BigInteger $commitment): BigInteger
    {
        $string_to_hash = $commitment->toString();
        // compute sha1 of the commitment
        return new BigInteger(sha1(utf8_encode($string_to_hash)), 16);
    }

    /**
     * Generate a PoK of the secret key
     * Prover generates w, a random integer modulo q, and computes commitment = g^w mod p.
     * Verifier provides challenge modulo q.
     * Prover computes response = w + x*challenge mod q, where x is the secret key.
     * @param callable $challenge_generator
     * @return DLogProof
     */
    public function proveSecretKey(callable $challenge_generator): DLogProof
    {
        $w = BigInteger::randomRange(BI1(), $this->pk->q->subtract(BI1()));
        $commitment = $this->pk->g->modPow($w, $this->pk->p);

        /** @var BigInteger $challenge */
        $challenge = $challenge_generator($commitment);
        $challenge = $challenge->modPow(BI1(), $this->pk->q);

        $response = $w->add($this->x->multiply($challenge)->powMod(BI1(), $this->pk->q));

        return new DLogProof($commitment, $challenge, $response);
    }
}
