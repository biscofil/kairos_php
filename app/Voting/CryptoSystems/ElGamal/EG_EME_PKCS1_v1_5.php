<?php

/**
 * See https://github.com/horde/Pgp/blob/master/lib/Horde/Pgp/Crypt/Elgamal.php
 */

namespace App\Voting\CryptoSystems\ElGamal;

use phpseclib3\Crypt\Random;
use phpseclib3\Math\BigInteger;

class EG_EME_PKCS1_v1_5
{


    /**
     * Encrypt data.
     *
     * @param \App\Voting\CryptoSystems\ElGamal\EGPublicKey $pk
     * @param string $text Plaintext.
     *
     * @return string|null imploded array of MPI values (alpha, beta).
     */
    public function encrypt(EGPublicKey $pk, string $text): ?string
    {
        // $p_len = strlen($this->_key->key['p']);
        $p_len = strlen($pk->parameterSet->p->toBytes()); // TODO check base
        $length = $p_len - 11;
        if ($length <= 0) {
            return null;
        }

        $out = [];

        foreach (str_split($text, $length) as $m) {
            // EME-PKCS1-v1_5 encoding
            $psLen = $p_len - strlen($m) - 3;

            $ps = '';
            while (($psLen2 = strlen($ps)) != $psLen) {
                $tmp = Random::string($psLen - $psLen2);
                $ps .= str_replace("\x00", '', $tmp);
            }

            $em = new BigInteger(
                chr(0) . chr(2) . $ps . chr(0) . $m,
                256
            );
            // End EME-PKCS1-v1_5 encoding

            $pt = new EGPlaintext($em);
            $ct = $pk->encrypt($pt);

            $out[] = str_pad($ct->alpha->toBytes(), $p_len, chr(0), STR_PAD_LEFT);
            $out[] = str_pad($ct->beta->toBytes(), $p_len, chr(0), STR_PAD_LEFT);
        }

        return implode('', $out);
    }

    /**
     * Decrypt data.
     *
     * @param string $parts PKCS1-v1_5 encoded text.
     *
     * @return string  Plaintext.
     */
    public function decrypt(EGSecretKey $sk, string $parts): string
    {
        $out = '';
        $p_len = strlen($sk->pk->parameterSet->p->toBytes());

        $parts = str_split($parts, $p_len);
        $parts[count($parts) - 1] = str_pad(
            $parts[count($parts) - 1],
            $p_len,
            chr(0),
            STR_PAD_LEFT
        );


        for ($i = 0, $j = count($parts); $i < $j; $i += 2) {
            $alpha = new BigInteger($parts[$i], 256);
            $beta = new BigInteger($parts[$i + 1], 256);

            $ct = new EGCiphertext($sk->pk, $alpha, $beta);
            $m_prime = $sk->decrypt($ct)->m;

            $em = str_pad(
                $m_prime->toBytes(),
                $p_len,
                chr(0),
                STR_PAD_LEFT
            );

            // EME-PKCS1-v1_5 decoding
            if ((ord($em[0]) !== 0) || (ord($em[1]) !== 2)) {
                throw new \RuntimeException('Error');
            }

            $out .= substr($em, strpos($em, chr(0), 2) + 1);
        }

        return $out;
    }

}
