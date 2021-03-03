<?php


namespace App\Models\Cast;

use App\Crypto\EGPublicKey;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Database\Eloquent\SerializesCastableAttributes;

/**
 * Class EGPublicKeyCaster
 * @package App\Models\Cast
 */
class EGPublicKeyCaster implements CastsAttributes, SerializesCastableAttributes
{

    /**
     * @param ModelWithCryptoFields $model
     * @param string $key
     * @param static|null $value
     * @param array $attributes
     * @return null|EGPublicKey
     * @noinspection PhpMissingParamTypeInspection
     */
    public function get($model, string $key, $value, array $attributes): ?EGPublicKey
    {
        if (is_null($value)) {
            return null;
        }
        $data = json_decode($value, true);
        return EGPublicKey::fromArray($data, $model->onlyStoreXY($key));
    }

    /**
     * @param ModelWithCryptoFields $model
     * @param string $key
     * @param EGPublicKey|null $value
     * @param array $attributes
     * @return null|string
     * @noinspection PhpMissingParamTypeInspection
     */
    public function set($model, string $key, $value, array $attributes): ?string
    {
        if (is_null($value)) {
            return null;
        }

        return json_encode($value->toArray($model->onlyStoreXY($key)));
    }


    /**
     * @param ModelWithCryptoFields $model
     * @param string $key
     * @param EGPublicKey|null $value
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
