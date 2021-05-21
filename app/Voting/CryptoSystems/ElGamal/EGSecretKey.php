<?php


namespace App\Voting\CryptoSystems\ElGamal;

use App\Voting\CryptoSystems\SecretKey;
use phpseclib3\Math\BigInteger;

/**
 * Class EGSecretKey
 * @package App
 * @property EGPublicKey $pk
 * @property BigInteger $x
 */
class EGSecretKey implements SecretKey
{

    use BelongsToElgamal;

    public $pk;
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

    /**
     * @param \App\Voting\CryptoSystems\ElGamal\EGSecretKey $b
     * @return bool
     * @throws \Exception
     */
    public function equals(EGSecretKey $b): bool
    {
        return $this->x->equals($b->x) && $this->pk->equals($b->pk);
    }

    // ##############################################################
    // ##############################################################
    // ##############################################################

    /**
     * @param array $data
     * @param bool $ignoreParameterSet
     * @param int $base
     * @return EGSecretKey
     */
    public static function fromArray(array $data, bool $ignoreParameterSet = false, int $base = 16): self
    {
        $pkClass = self::getCryptosystem()::getPublicKeyClass();
        return new static(
            $pkClass::fromArray($data['pk'], $ignoreParameterSet, $base),
            BI($data['x'], $base)
        );
    }

    /**
     * @param bool $ignoreParameterSet
     * @return array
     */
    public function toArray(bool $ignoreParameterSet = false): array
    {
        return [
            'pk' => $this->pk->toArray($ignoreParameterSet),
            'x' => $this->x->toHex()
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
        return $ciphertext->alpha->modPow($this->x, $this->pk->parameterSet->p);
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
        $proof = EGZKProof::generate($this->pk->parameterSet, $this->x, $ciphertext->alpha, $challenge_generator);
        return [$dec_factor, $proof];
    }

    /**
     * Decrypt a ciphertext
     * @param EGCiphertext $ciphertext
     * @return mixed
     * @noinspection PhpMissingParamTypeInspection
     * @noinspection PhpParameterNameChangedDuringInheritanceInspection
     */
    public function decrypt($ciphertext): EGPlaintext
    {

        // (alpha^x) mod p
        $dec_factor = $this->decryptionFactor($ciphertext);

        // ( [( alpha^x) mod p ] ^ -1 mod p * beta ) mod p
        $m = $dec_factor->modInverse($this->pk->parameterSet->p)
            ->multiply($ciphertext->beta)
            ->modPow(BI1(), $this->pk->parameterSet->p);

        $m = $this->pk->parameterSet->extractMessageFromSubgroup($m);

        $m = $this->getMOnceFullyDecrypted($m); // extractMessageFromSubgroup

        $ptClass = static::getCryptosystem()::getPlainTextClass();
        return new $ptClass($m);
    }

    /**
     * @param \phpseclib3\Math\BigInteger $m
     * @return \phpseclib3\Math\BigInteger
     */
    public function getMOnceFullyDecrypted(BigInteger $m): BigInteger
    {
        return $m;
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
        $w = randomBIgt($this->pk->parameterSet->g);
        $commitment = $this->pk->parameterSet->q->modPow($w, $this->pk->parameterSet->p);
        /** @var BigInteger $challenge */
        $challenge = $challenge_generator($commitment);
        // challenge = challenge mod p
        $challenge = $challenge->modPow(BI1(), $this->pk->parameterSet->g);
        // w + x * challenge mod q, where x is the secret key.
        $response = $w->add($this->x->multiply($challenge)->powMod(BI1(), $this->pk->parameterSet->g));
        return new DLogProof($commitment, $challenge, $response);
    }

    // ##############################################################
    // ##############################################################
    // ##############################################################

    /**
     * @param EGSecretKey|null $b
     * @return EGSecretKey
     * @throws \Exception
     */
    public function combine(?EGSecretKey $b): self
    {

        if (is_null($b)) {
            return $this;
        }

        $this->pk->ensureSameParameters($b->pk);

        return new static(
            $this->pk,
            $this->x->add($b->x->powMod(BI1(), $this->pk->parameterSet->p))
        );
    }

    /**
     * @param EGCiphertext $cipher
     * @param bool $lastStep
     * @return EGCiphertext
     */
    public function partiallyDecrypt(EGCiphertext $cipher, bool $lastStep = false): EGCiphertext
    {
        $inv = $cipher->alpha->powMod($this->x, $cipher->pk->parameterSet->p)
            ->modInverse($cipher->pk->parameterSet->p);

        $beta = $inv->multiply($cipher->beta)->powMod(BI1(), $cipher->pk->parameterSet->p);

        if ($lastStep) {
            // if this is the last partial decryption we must extract from the subgroup
            $beta = $this->pk->parameterSet->extractMessageFromSubgroup($beta);
            $beta = $this->getMOnceFullyDecrypted($beta); // extractMessageFromSubgroup
            // TODO return plaintext
        }

        $ctClass = static::getCryptosystem()::getCipherTextClass();
        return new $ctClass(
            $cipher->pk,
            $cipher->alpha,
            $beta
        );
    }

    // ##############################################################
    // ##############################################################
    // ##############################################################

    /**
     * @param int $t
     * @return \App\Voting\CryptoSystems\ElGamal\EGThresholdPolynomial
     */
    public function getThresholdPolynomial(int $t): EGThresholdPolynomial
    {
        $tpClass = static::getCryptosystem()::getThresholdPolynomialClass();
        return $tpClass::random($this->x, $t, $this->pk->parameterSet);
    }

}
