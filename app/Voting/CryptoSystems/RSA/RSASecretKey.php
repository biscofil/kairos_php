<?php


namespace App\Voting\CryptoSystems\RSA;


use App\Voting\CryptoSystems\CipherText;
use App\Voting\CryptoSystems\Plaintext;
use App\Voting\CryptoSystems\SecretKey;
use Illuminate\Http\Request;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Crypt\RSA\PrivateKey;

/**
 * Class RSASecretKey
 * @package App\Voting\CryptoSystems\RSA
 * @property PrivateKey $value
 */
class RSASecretKey extends SecretKey
{

    const CRYPTOSYSTEM = RSA::class;

    private PrivateKey $value;

    public function __construct(PrivateKey $sk)
    {
        $this->value = $sk;
    }

    public static function fromArray(array $data, bool $onlyXY = false, int $base = 16): RSASecretKey
    {
        $sk = PrivateKey::load($data['v']); // TODO
        return new static($sk);
    }

    /**
     * @param bool $onlyXY
     * @return array
     */
    public function toArray(bool $onlyXY = false): array
    {
        return [
            'v' => $this->value->__toString() // TODO
        ];
    }

    /**
     * @param RSACiphertext $cipherText
     * @return Plaintext
     */
    public function decrypt($cipherText): Plaintext
    {
        return $this->value->decrypt($cipherText);
    }

}
