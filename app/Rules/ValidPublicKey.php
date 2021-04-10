<?php

namespace App\Rules;

use App\Crypto\DLogProof;
use App\Voting\CryptoSystems\ElGamal\EGPublicKey;
use Illuminate\Contracts\Validation\Rule;
use phpseclib3\Math\BigInteger;

class ValidPublicKey implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     * @noinspection PhpMissingParamTypeInspection
     * @noinspection PhpMissingReturnTypeInspection
     */
    public function passes($attribute, $value)
    {
        $key = json_decode($value, true);

        if (
            array_key_exists("public_key", $key)
            && array_key_exists("pok", $key)) {

            $public_key = json_decode($key['public_key'], true);
            $public_key = EGPublicKey::fromArray($public_key);

            $dlog_proof = json_decode($key['pok'], true);
            $dlog_proof = DLogProof::fromArray($dlog_proof);

            return $public_key->verifySecretKeyProof($dlog_proof, function (BigInteger $commitment) {
                $string_to_hash = $commitment->toString();
                // compute sha1 of the commitment
                return BI(sha1(utf8_encode($string_to_hash)), 16);
            });

        }

        return false;

    }

    /**
     * Get the validation error message.
     *
     * @return string
     * @noinspection PhpMissingReturnTypeInspection
     */
    public function message()
    {
        return 'The :attribute field is not a valid public key.';
    }
}
