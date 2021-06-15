<?php


namespace App\Voting\CryptoSystems\ElGamal;


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

    // ####################################################################

    /**
     * @param self $b
     * @return bool
     * @throws \Exception
     */
    public function equals($b): bool
    {
        $this->ensureSameParameters($b);
        return $this->y->equals($b->y);
    }

    // ####################################################################

    /**
     * @param array $data
     * @param bool $ignoreParameterSet
     * @param int $base
     * @return EGPublicKey
     */
    public static function fromArray(array $data, bool $ignoreParameterSet = false, int $base = 16): EGPublicKey
    {
        if ($ignoreParameterSet) {
            // Copy from config
            return new static(
                static::getCryptosystem()::getParameterSetClass()::getDefault(),
                BI($data['y'], $base)
            );
        }

        return new static(
            static::getCryptosystem()::getParameterSetClass()::fromArray($data['ps'], $base),
            BI($data['y'], $base)
        );
    }

    /**
     * @param bool $ignoreParameterSet
     * @return array
     */
    public function toArray(bool $ignoreParameterSet = false): array
    {
        $out = [
            'y' => $this->y->toHex()
        ];
        if (!$ignoreParameterSet) {
            $out['ps'] = $this->parameterSet->toArray();
        }
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
        $r = $this->parameterSet->getReEncryptionFactor();
        $ciphertext = $this->encryptWithRandomness($plainText, $r);
        return [$ciphertext, $r];
    }

    /**
     * @param EGPlaintext $plainText
     * @param BigInteger $randomness r
     * @return EGCiphertext
     * @noinspection PhpMissingReturnTypeInspection
     * @throws \Exception
     */
    public function encryptWithRandomness(EGPlaintext $plainText, BigInteger $randomness)
    {

        // check plaintext size: make sure that bin lenght of the message is lett than the bit lenght of p
        if ($plainText->m->getLength() >= $this->parameterSet->p->getLength()) {
            throw new \Exception("Bit lenght of the message is {$plainText->m->getLength()} but has to be < {$this->parameterSet->p->getLength()}");
        }

        $m = $plainText->m;

        $m = $this->parameterSet->mapMessageIntoSubgroup($m);

        $m = $this->getMToEncrypt($m);

        // alpha = g^r mod p
        $alpha = $this->parameterSet->g->modPow($randomness, $this->parameterSet->p);
        // beta = m*(y^r) mod p
        $beta = $m->multiply($this->y->modPow($randomness, $this->parameterSet->p))->modPow(BI1(), $this->parameterSet->p);

        $ctClass = static::getCryptosystem()::getCipherTextClass();
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
