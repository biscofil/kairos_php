<?php


namespace App\Models\Cast;

use App\Voting\CryptoSystems\ElGamal\DLogProof;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Database\Eloquent\SerializesCastableAttributes;

/**
 * Class POKCaster TODO dynamic caster
 * @package App\Models\Cast
 */
class POKCaster implements CastsAttributes, SerializesCastableAttributes
{

    /**
     * @param ModelWithCryptoFields $model
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
     * @param ModelWithCryptoFields $model
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
     * @param ModelWithCryptoFields $model
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
