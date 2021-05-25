<?php


namespace App\Models\Cast;

use App\Voting\CryptoSystems\ElGamal\DLogProof;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Database\Eloquent\SerializesCastableAttributes;

/**
 * Class DLogProofCaster
 * TODO use dynamic caster when all cryptosystems have a DLogProof
 * @package App\Models\Cast
 */
class DLogProofCaster implements CastsAttributes, SerializesCastableAttributes
{

    /**
     * @param ModelWithFieldsWithParameterSets $model
     * @param string $key
     * @param mixed $value
     * @param array $attributes
     * @return null|DLogProof
     * @noinspection PhpMissingParamTypeInspection
     */
    public function get($model, string $key, $value, array $attributes): ?DLogProof
    {
        if (is_null($value)) {
            return null;
        }
        $data = json_decode($value, true);
        return DLogProof::fromArray($data);
    }

    /**
     * @param ModelWithFieldsWithParameterSets $model
     * @param string $key
     * @param null|DLogProof $value
     * @param array $attributes
     * @return null|string
     * @noinspection PhpMissingParamTypeInspection
     */
    public function set($model, string $key, $value, array $attributes): ?string
    {
        if (is_null($value)) {
            return null;
        }

        return json_encode($value->toArray());
    }

    /**
     * @param ModelWithFieldsWithParameterSets $model
     * @param string $key
     * @param DLogProof|null $value
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
