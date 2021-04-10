<?php


namespace App\Voting\CryptoSystems\RSA;


use App\Voting\CryptoSystems\KeyPair;
use Illuminate\Support\Facades\Storage;
use phpseclib3\Crypt\RSA\PrivateKey;

/**
 * Class RSAKeyPair
 * @package App\Voting\CryptoSystems\RSA
 */
class RSAKeyPair implements KeyPair
{

    public RSAPublicKey $pk;
    public RSASecretKey $sk;

    public function __construct(RSAPublicKey $pk, RSASecretKey $sk)
    {
        $this->pk = $pk;
        $this->sk = $sk;
    }

    public static function generate(): RSAKeyPair
    {
        $sk = PrivateKey::createKey();
        $pk = $sk->getPublicKey();
        return new static(new RSAPublicKey($pk), new RSASecretKey($sk));
    }

    /**
     * @param string $filePath Example: "/home/private_key.json"
     */
    public function storeToFile(string $filePath): void
    {
        $content = json_encode($this->sk->toArray(), JSON_PRETTY_PRINT);
        Storage::put($filePath, $content);
    }

    /**
     * @param string $filePath Example: "/home/private_key.json"
     * @return RSAKeyPair
     */
    public static function fromFile(string $filePath): RSAKeyPair
    {
        $content = Storage::get($filePath);
        $sk = RSASecretKey::fromArray(json_decode($content));
        $pk = $sk->value->getPublicKey();
        return new static($pk, $sk);
    }
}
