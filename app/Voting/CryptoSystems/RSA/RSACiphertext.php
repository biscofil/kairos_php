<?php


namespace App\Voting\CryptoSystems\RSA;

use App\Voting\CryptoSystems\CipherText;
use Illuminate\Http\Request;
use phpseclib3\Math\BigInteger;

/**
 * Class RSACiphertext
 * @package App\Voting\CryptoSystems\RSA;
 * @property RSAPublicKey $pk
 * @property string $cipherText
 */
class RSACiphertext implements CipherText
{
    public RSAPublicKey $pk;
    public string $cipherText;

    public function __construct(RSAPublicKey $pk, string $cipherText)
    {
        $this->pk = $pk;
        $this->cipherText = $cipherText;
    }

    // ##################################################################################
    // ##################################################################################
    // ##################################################################################

    /**
     * @param array $data
     * @param bool $ignoreParameterSet
     * @param RSAPublicKey|null $pk
     * @return RSACiphertext
     */
    public static function fromArray(array $data, bool $ignoreParameterSet = false, $pk = null) : RSACiphertext
    {
        return new static(
            $pk ?? RSAPublicKey::fromArray($data['pk'], $ignoreParameterSet),
            $data['c'],
        );
    }

    /**
     * @param bool $includePublicKey
     * @param bool $ignoreParameterSet
     * @return array
     */
    public function toArray(bool $includePublicKey = false, bool $ignoreParameterSet = false): array
    {
        $out = [
            'c' => $this->cipherText
        ];
        if ($includePublicKey) {
            $out['pk'] = $this->pk->toArray($ignoreParameterSet);
        }
        return $out;
    }

    // ##################################################################################
    // ##################################################################################
    // ##################################################################################

    /**
     * @return string
     */
    public function getFingerprint(): string
    {
        $v = $this->cipherText;
        return base64_encode(hash('sha256', $v));
    }

    /**
     * @param RSACiphertext $b
     * @return bool
     * @throws \Exception
     * @noinspection PhpMissingParamTypeInspection
     */
    public function equals($b): bool
    {
        if (!$b instanceof RSACiphertext) {
            throw new \RuntimeException("RSACiphertext::equals > invalid type, must be RSACiphertext");
        }
        // TODO $this->pk->ensureSameCryptosystem($b->pk);
        return $this->cipherText === $b->cipherText;
    }

    /**
     * @param array $data
     * @return array
     */
    public static function validate(array $data): array{
        return []; // TODO
    }

}
