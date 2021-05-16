<?php


namespace App\Voting\CryptoSystems\ElGamal;

use App\Voting\CryptoSystems\BelongsToCryptoSystem;
use App\Voting\CryptoSystems\CipherText;
use Exception;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use phpseclib3\Math\BigInteger;
use RuntimeException;

/**
 * Class EGCiphertext
 * @package App\Voting\CryptoSystems\ElGamal;
 * @property EGPublicKey $pk
 * @property BigInteger $alpha
 * @property BigInteger $beta
 */
class EGCiphertext implements CipherText, BelongsToCryptoSystem
{

    use BelongsToElgamal;

    public EGPublicKey $pk;
    public BigInteger $alpha;
    public BigInteger $beta;

    public function __construct(EGPublicKey $pk, BigInteger $alpha, BigInteger $beta)
    {
        $this->pk = $pk;
        $this->alpha = $alpha;
        $this->beta = $beta;
    }

    // ##################################################################################
    // ##################################################################################
    // ##################################################################################

    /**
     * @param array $data
     * @return array
     * @throws ValidationException
     */
    public static function validate(array $data): array
    {
        return Validator::make($data, [
            'alpha' => ['required', 'string'], // TODO hex?
            'beta' => ['required', 'string'], //  TODO hex?
        ])->validated();
    }

    // ##################################################################################

    /**
     * @param array $data
     * @param null|\App\Voting\CryptoSystems\ElGamal\EGPublicKey $publicKey
     * @param bool $ignoreParameterSet
     * @param int $base
     * @return EGCiphertext
     */
    public static function fromArray(array $data, $publicKey = null, bool $ignoreParameterSet = false, int $base = 16): EGCiphertext
    {
        return new static(
            $publicKey ?? EGPublicKey::fromArray($data['pk'], $ignoreParameterSet, $base),
            BI($data['alpha'], $base),
            BI($data['beta'], $base)
        );
    }

    /**
     * TODO does not match castable
     * @param bool $includePublicKey
     * @param bool $ignoreParameterSet
     * @return array
     */
    public function toArray(bool $includePublicKey = false, bool $ignoreParameterSet = false): array
    {
        $out = [
            'alpha' => $this->alpha->toHex(),
            'beta' => $this->beta->toHex()
        ];
        if ($includePublicKey) {
            $out['pk'] = $this->pk->toArray($ignoreParameterSet);
        }
        return $out;
    }

    // ##################################################################################

    /**
     * TODO check format
     * @return string
     */
    public function getFingerprint(): string
    {
        $v = $this->alpha->toHex() . ',' . $this->beta->toHex();
        return base64_encode(hash('sha256', $v));
    }

    // ##################################################################################
    // ################################### Re Encrypt ###################################
    // ##################################################################################

    /**
     * @return EGCiphertext
     */
    public function reEncrypt(): EGCiphertext
    {
        /** @var EGCiphertext $ciphertext */
        /** @var BigInteger $r */
        /** @noinspection PhpUnusedLocalVariableInspection */
        list($ciphertext, $r) = $this->reEncryptAndReturnRandomness();
        return $ciphertext;
    }

    /**
     * Encrypt a plaintext and return the randomness just generated and used.
     * @return array
     */
    public function reEncryptAndReturnRandomness(): array
    {
        // r: randomness
        $r = randomBIgt($this->pk->parameterSet->q);
        $ciphertext = $this->reEncryptWithRandomness($r);
        return [$ciphertext, $r];
    }

    /**
     * Re-encrypts the ciphertext
     * @param BigInteger $randomness r
     * @return EGCiphertext
     */
    public function reEncryptWithRandomness(BigInteger $randomness): EGCiphertext
    {

        // a = a * (g ^ r mod p) mod p
        $alpha = $this->alpha
            ->multiply($this->pk->parameterSet->g->modPow($randomness, $this->pk->parameterSet->p))
            ->modPow(BI1(), $this->pk->parameterSet->p);

        // b = b * (y ^ r mod p) mod p
        $beta = $this->beta
            ->multiply($this->pk->y->modPow($randomness, $this->pk->parameterSet->p))
            ->modPow(BI1(), $this->pk->parameterSet->p);

        return new static($this->pk, $alpha, $beta);
    }

    // ##################################################################################
    // #################################### Decrypt #####################################
    // ##################################################################################

    /**
     * @param BigInteger $randomness
     * @return EGCiphertext
     */
    public function decryptWithRandomness(BigInteger $randomness): EGCiphertext
    {

        // a = a * (g ^ r mod p)^-1
        $alphaOriginal = $this->alpha->multiply(
            $this->pk->parameterSet->g->modPow($randomness, $this->pk->parameterSet->p)->modInverse($this->pk->parameterSet->p)
        )->modPow(BI1(), $this->pk->parameterSet->p);

        // b = b * (y ^ r mod p)^-1
        $betaOriginal = $this->beta->multiply(
            $this->pk->y->modPow($randomness, $this->pk->parameterSet->p)->modInverse($this->pk->parameterSet->p)
        )->modPow(BI1(), $this->pk->parameterSet->p);

        return new static($this->pk, $alphaOriginal, $betaOriginal);

    }

    /**
     * decrypt a ciphertext given a list of decryption factors (from multiple trustees)
     * For now, no support for threshold
     * @param BigInteger[] $decryption_factors are these the x values?
     * @param EGPublicKey $pk // TODO from this
     * @return BigInteger
     */
    public function decryptFromFactors(array $decryption_factors, EGPublicKey $pk): BigInteger
    {
        $running_decryption = $this->beta;
        foreach ($decryption_factors as $dec_factor) {
            $running_decryption = $running_decryption->multiply($dec_factor->modInverse($pk->parameterSet->p))
                ->modPow(BI1(), $pk->parameterSet->p);
        }
        return $running_decryption;
    }

    // ##################################################################################
    // ##################################################################################
    // ##################################################################################

    /**
     * @param EGCiphertext $b
     * @return bool
     * @throws Exception
     * @noinspection PhpMissingParamTypeInspection
     */
    public function equals($b): bool
    {
        if (!$b instanceof EGCiphertext) {
            throw new RuntimeException('EGCiphertext::equals > invalid type, must be EGCiphertext');
        }
        $this->pk->ensureSameParameters($b->pk);
        return $this->alpha->equals($b->alpha) && $this->beta->equals($b->beta);
    }

}
