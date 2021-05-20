<?php


namespace App\Voting\CryptoSystems\ElGamal;


use App\Voting\CryptoSystems\BelongsToCryptoSystem;
use App\Voting\CryptoSystems\PublicKey;
use phpseclib3\Math\BigInteger;

/**
 * Class EGPublicKey
 * @package App\Voting\CryptoSystems\ElGamal
 * @property EGParameterSet $parameterSet
 * @property BigInteger $y
 */
class EGPublicKey implements PublicKey
{

    use BelongsToElgamal;

    public $parameterSet;
    public BigInteger $y;

    /**
     * EGPublicKey constructor.
     * @param EGParameterSet $parameterSet
     * @param BigInteger $y
     */
    public function __construct(EGParameterSet $parameterSet, BigInteger $y)
    {
        $this->parameterSet = $parameterSet;
        $this->y = $y;
    }

    /**
     * @param \App\Voting\CryptoSystems\ElGamal\EGPublicKey $b
     * @return bool
     * @throws \Exception
     */
    public function equals(EGPublicKey $b): bool
    {
        $this->ensureSameParameters($b);
        return $this->y->equals($b->y);
    }

    // ####################################################################
    // ####################################################################
    // ####################################################################

    /**
     * @param array $data
     * @param bool $ignoreParameterSet
     * @param int $base
     * @return EGPublicKey
     */
    public static function fromArray(array $data, bool $ignoreParameterSet = false, int $base = 16): EGPublicKey
    {

        /** @var self $self */
        $self = get_called_class();

        if ($ignoreParameterSet) {
            // Copy from config
            return new static(
                $self::getCryptosystem()::getParameterSetClass()::getDefault(),
                BI($data['y'], $base)
            );
        }

        return new static(
            $self::getCryptosystem()::getParameterSetClass()::fromArray($data, $base),
            BI($data['y'], $base)
        );
    }

    /**
     * @param bool $ignoreParameterSet
     * @return array
     */
    public function toArray(bool $ignoreParameterSet = false): array
    {
        $out = [];
        if (!$ignoreParameterSet) {
            $out = $this->parameterSet->toArray();
        }
        $out['y'] = $this->y->toHex();
        return $out;
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
    public function ensureSameParameters($b): void
    {
        // P, G and Q must be the same
        if (!$this->parameterSet->equals($b->parameterSet)) {
            throw new \Exception('incompatible parameter sets');
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

        $this->ensureSameParameters($b);

        return new EGPublicKey(
            $this->parameterSet,
            $this->y->multiply($b->y)->powMod(BI1(), $this->parameterSet->p)
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

        $left_side = $this->parameterSet->q->modPow($dlog_proof->response, $this->parameterSet->p);
        $right_side = $dlog_proof->commitment
            ->multiply($this->y->modPow($dlog_proof->challenge, $this->parameterSet->p))
            ->modPow(BI1(), $this->parameterSet->p);

        /** @var BigInteger $expected_challenge */
        $expected_challenge = $challenge_generator($dlog_proof->commitment)->modPow(BI1(), $this->parameterSet->g);

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
     * @param EGPlaintext $plainText
     * @return \App\Voting\CryptoSystems\ElGamal\EGCiphertext
     * @noinspection PhpMissingParamTypeInspection
     * @noinspection PhpMissingReturnTypeInspection
     */
    public function encrypt($plainText)
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
        $r = randomBIgt($this->parameterSet->q); // 0 < r < q-1 : randomness
        $ciphertext = $this->encryptWithRandomness($plainText, $r);
        return [$ciphertext, $r];
    }

    /**
     * @param EGPlaintext $plainText
     * @param BigInteger $randomness r
     * @return EGCiphertext
     * @noinspection PhpMissingReturnTypeInspection
     */
    public function encryptWithRandomness(EGPlaintext $plainText, BigInteger $randomness)
    {

        $m = $plainText->m;

        $m = $this->parameterSet->mapMessageIntoSubgroup($m);

        $m = $this->getMToEncrypt($m);

        // alpha = g^r mod p
        $alpha = $this->parameterSet->g->modPow($randomness, $this->parameterSet->p);
        // beta = m*(y^r) mod p
        $beta = $m->multiply($this->y->modPow($randomness, $this->parameterSet->p))->modPow(BI1(), $this->parameterSet->p);

        /** @var self $self */
        $self = get_called_class();
        $ctClass = $self::getCryptosystem()::getCipherTextClass();
        return new $ctClass($this, $alpha, $beta);
    }

    /**
     * @param \phpseclib3\Math\BigInteger $m
     * @return \phpseclib3\Math\BigInteger
     */
    public function getMToEncrypt(BigInteger $m): BigInteger
    {
        return $m;
    }

}
