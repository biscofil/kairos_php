<?php


namespace App\Crypto;

use phpseclib3\Math\BigInteger;

/**
 * Class EGPlaintext
 * @package App\Crypto
 * @property BigInteger $m
 * @property EGPublicKey $pk
 */
class EGPlaintext
{

    public $m;
    public $pk;

    public function __construct(BigInteger $m, EGPublicKey $pk)
    {
        $this->m = $m;
        $this->pk = $pk;
    }

    /**
     * @param string $plaintext ASCII only
     * @param EGPublicKey $pk
     * @return EGPlaintext
     */
    public static function fromString(string $plaintext, EGPublicKey $pk): EGPlaintext
    {
        $str = iconv("UTF-8", "ASCII", $plaintext);
        $str = head(unpack('H*', $str));
        return new EGPlaintext(new BigInteger($str, 16), $pk);
    }


    /**
     * @return EGCiphertext
     */
    public function encrypt(): EGCiphertext
    {
        return $this->pk->encrypt($this->m);
    }

    /**
     * Returns the ASCII string
     * @return false|string
     */
    public function toString()
    {
        return pack('H*', $this->m->toHex());
    }
}
