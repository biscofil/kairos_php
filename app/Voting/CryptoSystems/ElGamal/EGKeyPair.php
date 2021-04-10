<?php


namespace App\Voting\CryptoSystems\ElGamal;

use App\Voting\CryptoSystems\KeyPair;
use App\Voting\CryptoSystems\SupportsThresholdEncryption;
use Illuminate\Support\Facades\Storage;
use phpseclib3\Math\BigInteger;

/**
 * Class EGKeyPair
 * @package App\Voting\CryptoSystems\ElGamal;
 * @property EGPublicKey $pk
 * @property EGPrivateKey $sk
 */
class EGKeyPair implements KeyPair, SupportsThresholdEncryption
{
    public EGPublicKey $pk;
    public EGPrivateKey $sk;

    public function __construct(EGPublicKey $pk, EGPrivateKey $sk)
    {
        $this->pk = $pk;
        $this->sk = $sk;
    }

    /**
     * Generate an ElGamal keypair
     */
    public static function generate(): EGKeyPair // TODO add threshold boolean
    {
        // generator g
        $g = BI(config('elgamal.g'), config('elgamal.base'));
        // prime p
        $p = BI(config('elgamal.p'), config('elgamal.base'));
        $q = BI(config('elgamal.q'), config('elgamal.base')); // TODO check?!?!

        // TODO if threshold {
        // TODO     EGThresholdPolynomial::random($degree, $this->pk);
        // TODO }else{
        $x = randomBIgt($q);
        $y = $g->modPow($x, $p); // also called h
        $pk = new EGPublicKey($g, $p, $q, $y);
        $sk = new EGPrivateKey($pk, $x);
        // TODO }

        return new EGKeyPair($pk, $sk);
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
    static function fromFile(string $filePath): EGKeyPair
    {
        $content = Storage::get($filePath);
        $sk = EGPrivateKey::fromArray(json_decode($content, true));
        $pk = $sk->pk;
        return new EGKeyPair($pk, $sk);
    }

    /**
     * @param int $degree
     * @return EGThresholdPolynomial
     */
    public function generatePolynomial(int $degree): EGThresholdPolynomial
    {
        return EGThresholdPolynomial::random($degree, $this->pk);
    }

}
