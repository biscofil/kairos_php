<?php


namespace App\Voting\CryptoSystems\ElGamal;

use App\Voting\CryptoSystems\Plaintext;
use App\Voting\CryptoSystems\SecretKey;
use phpseclib3\Math\BigInteger;

/**
 * Class EGPrivateKey
 * @package App
 * @property EGPublicKey $pk
 * @property BigInteger $x
 */
class EGPrivateKey extends SecretKey
{

    const CRYPTOSYSTEM = ElGamal::class;

    public EGPublicKey $pk;
    public BigInteger $x;

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

    // ##############################################################
    // ##############################################################
    // ##############################################################

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
            BI($data['x'], $base)
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

    // ##############################################################
    // ##############################################################
    // ##############################################################

    /**
     * provide the decryption factor, not yet inverted because of needed proof
     * @param EGCiphertext $ciphertext
     * @return BigInteger
     */
    public function decryptionFactor(EGCiphertext $ciphertext): BigInteger
    {
        // (alpha^x) mod p
        return $ciphertext->alpha->modPow($this->x, $this->pk->p);
    }

    /**
     * challenge generator is almost certainly EG_fiatshamir_challenge_generator
     * @param EGCiphertext $ciphertext
     * @param callable|null $challenge_generator
     * @return array
     */
    public function getDecryptionFactorAndProof(EGCiphertext $ciphertext, callable $challenge_generator = null): array
    {

        if (!is_null($challenge_generator)) {
            // TODO $challenge_generator = EG_fiatshamir_challenge_generator;
        }
        $dec_factor = $this->decryptionFactor($ciphertext);
        $proof = EGZKProof::generate($this->pk->g, $ciphertext->alpha, $this->x, $this->pk->p, $this->pk->q, $challenge_generator);
        return [$dec_factor, $proof];
    }

    /**
     * Decrypt a ciphertext. Optional parameter decides whether to encode the message into the proper subgroup.
     * @param EGCiphertext $ciphertext
     * @return mixed
     * @noinspection PhpMissingParamTypeInspection
     * @noinspection PhpParameterNameChangedDuringInheritanceInspection
     */
    public function decrypt($ciphertext): Plaintext
    {
        /** @var BigInteger $dec_factor */
        $dec_factor = null; // TODO param
        $decode_m = false; // TODO param

        if (is_null($dec_factor)) {
            // (alpha^x) mod p
            $dec_factor = $this->decryptionFactor($ciphertext);
        }

        // ( [( alpha^x) mod p ] ^ -1 mod p * beta ) mod p
        $m = $dec_factor->modInverse($this->pk->p)
            ->multiply($ciphertext->beta)
            ->modPow(BI1(), $this->pk->p);

        if ($decode_m) {  # get m back from the q-order subgroup
            // encode the message into the proper subgroup.
            if ($m < $this->pk->q) {
                $y = $m;
            } else {
                $y = $m->modInverse($this->pk->p);
            }
            return new EGPlaintext($y->subtract(BI1()));
        } else {
            return new EGPlaintext($m);
        }
    }

    // ##############################################################
    // ##############################################################
    // ##############################################################

    /**
     * Returns the BigInteger computed from the sha1 hashing of the
     * commitment (in base 10) encoded in UTF-8
     * @param BigInteger $commitment
     * @return BigInteger
     */
    public static function DLogChallengeGenerator(BigInteger $commitment): BigInteger
    {
        $string_to_hash = $commitment->toString();
        return BI(sha1(utf8_encode($string_to_hash)), 16);
    }

    /**
     * Old name: proveSecretKey
     * Generate a PoK of the secret key
     * Prover generates w, a random integer modulo q, and computes commitment = g^w mod p.
     * Verifier provides challenge modulo q.
     * Prover computes response = w + x * challenge mod q, where x is the secret key.
     * @param callable $challenge_generator
     * @return DLogProof
     */
    public function generateDLogProof(callable $challenge_generator): DLogProof
    {
        $w = randomBIgt($this->pk->q);
        $commitment = $this->pk->g->modPow($w, $this->pk->p);
        /** @var BigInteger $challenge */
        $challenge = $challenge_generator($commitment);
        // challenge = challenge mod p
        $challenge = $challenge->modPow(BI1(), $this->pk->q);
        // w + x * challenge mod q, where x is the secret key.
        $response = $w->add($this->x->multiply($challenge)->powMod(BI1(), $this->pk->q));
        return new DLogProof($commitment, $challenge, $response);
    }

    // ##############################################################
    // ##############################################################
    // ##############################################################

    /**
     * @param EGPrivateKey|null $b
     * @return EGPrivateKey
     * @throws \Exception
     */
    public function combine(?EGPrivateKey $b): EGPrivateKey
    {

        if (is_null($b)) {
            return $this;
        }

        $this->pk->ensureSameCryptosystem($b->pk);

        // sum of x mod (p-1) // TODO why?

        return new EGPrivateKey(
            $this->pk,
            $this->x->add($b->x->powMod(BI1(), $this->pk->p->subtract(BI1()))) // TODO check p-1 / p
        );
    }

    /**
     * @param EGCiphertext $cipher
     * @return EGCiphertext
     */
    public function partiallyDecrypt(EGCiphertext $cipher): EGCiphertext
    {
        $inv = $cipher->alpha->powMod($this->x, $cipher->pk->p)->modInverse($cipher->pk->p);
        return new EGCiphertext(
            $cipher->pk,
            $cipher->alpha,
            $inv->multiply($cipher->beta)->powMod(BI1(), $cipher->pk->p)
        );
    }

}