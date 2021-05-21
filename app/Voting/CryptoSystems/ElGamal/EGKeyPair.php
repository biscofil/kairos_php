<?php


namespace App\Voting\CryptoSystems\ElGamal;

use App\Voting\CryptoSystems\BelongsToCryptoSystem;
use App\Voting\CryptoSystems\KeyPair;
use App\Voting\CryptoSystems\SupportsThresholdEncryption;
use Illuminate\Support\Facades\Storage;

/**
 * Class EGKeyPair
 * @package App\Voting\CryptoSystems\ElGamal;
 * @property EGPublicKey $pk
 * @property EGSecretKey $sk
 */
class EGKeyPair implements KeyPair, SupportsThresholdEncryption
{

    use BelongsToElgamal;

    public $pk;
    public $sk;

    public function __construct(EGPublicKey $pk, EGSecretKey $sk)
    {
        $this->pk = $pk;
        $this->sk = $sk;
    }

    /**
     * Generate an ElGamal keypair
     * @param \App\Voting\CryptoSystems\ElGamal\EGParameterSet $parameterSet
     */
    public static function generate($parameterSet = null): self // TODO add threshold boolean
    {
        $parameterSet = is_null($parameterSet) ? static::getCryptosystem()::getParameterSetClass()::getDefault() : $parameterSet;

        // TODO if threshold {
        // TODO     EGThresholdPolynomial::random($degree, $this->pk);
        // TODO }else{

        $x = randomBIgt($parameterSet->q); // TODO check
        $y = $parameterSet->g->modPow($x, $parameterSet->p); // also called h

        $pkCLass = static::getCryptosystem()::getPublicKeyClass();
        $pk = new $pkCLass($parameterSet, $y);

//        $pk = new EGPublicKey($parameterSet, $y);
        $skCLass = static::getCryptosystem()::getSecretKeyClass();
        $sk = new $skCLass($pk, $x);
        // TODO }

        return new static($pk, $sk);
    }

    /**
     * @param string $filePath Example: "private_key.json"
     */
    public function storeToFile(string $filePath): void
    {
        $content = json_encode($this->sk->toArray(), JSON_PRETTY_PRINT);
        Storage::put($filePath, $content);
    }

    /**
     * @param string $filePath Example: "private_key.json"
     * @return EGKeyPair
     */
    public static function fromFile(string $filePath): self
    {
        $content = Storage::get($filePath);
        $sk = static::getCryptosystem()::getSecretKeyClass()::fromArray(json_decode($content, true));
        $pk = $sk->pk;
        return new static($pk, $sk);
    }

}
