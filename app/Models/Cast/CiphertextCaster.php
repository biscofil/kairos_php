<?php


namespace App\Models\Cast;


use App\Models\Voter;
use App\Voting\CryptoSystems\ElGamal\EGCiphertext;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Database\Eloquent\SerializesCastableAttributes;

/**
 * Class CiphertextCaster TODO dynamic caster
 * @package App\Models\Cast
 */
class CiphertextCaster extends FieldWithParameterSet
{


    /**
     * Specify the name of the constant of a cryptosystem class (RSA/Elgamal) that contains the name of the class
     * we want to cast the value to
     * @return string
     * @see \App\Voting\CryptoSystems\CryptoSystem::CipherTextClass
     */
    public function getTargetClassConstantName(): string
    {
        return 'CipherTextClass';
    }

    /**
     * @param ModelWithFieldsWithParameterSets $model
     * @param string $key
     * @param string|null $value
     * @param array $attributes
     * @return null|EGCiphertext
     * @noinspection PhpMissingParamTypeInspection
     */
    public function get($model, string $key, $value, array $attributes): ?EGCiphertext
    {
        if (is_null($value)) {
            return null;
        }
        $data = json_decode($value, true);
        // use the attribute to get the public key y of the election
        $data['pk'] = [
            'y' => Voter::findOrFail($attributes['voter_id'])->election->public_key->y->toHex()
        ];
        return EGCiphertext::fromArray($data, true);
    }

    /**
     * @param ModelWithFieldsWithParameterSets $model
     * @param string $key
     * @param null|EGCiphertext $value
     * @param array $attributes
     * @return null|string
     * @noinspection PhpMissingParamTypeInspection
     */
    public function set($model, string $key, $value, array $attributes): ?string
    {
        if (is_null($value)) {
            return null;
        }
        $out = $value->toArray();
        return json_encode($out);
    }

    /**
     * @param ModelWithFieldsWithParameterSets $model
     * @param string $key
     * @param EGCiphertext|null $value
     * @param array $attributes
     * @return null|array
     * @noinspection PhpMissingParamTypeInspection
     * @noinspection PhpMissingReturnTypeInspection
     */
    public function serialize($model, string $key, $value, array $attributes)
    {
        if (is_null($value)) {
            return null;
        }
        return $value->toArray();
    }
}
