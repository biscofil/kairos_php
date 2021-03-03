<?php


namespace App\Models\Cast;


use App\Crypto\EGPrivateKey;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Database\Eloquent\SerializesCastableAttributes;

/**
 * Class EGPrivateKey
 * @package App\Models\Cast
 */
class EGPrivateKeyCaster implements CastsAttributes, SerializesCastableAttributes
{

    /**
     * @param ModelWithCryptoFields $model
     * @param string $key
     * @param string|null $value
     * @param array $attributes
     * @return null|EGPrivateKey
     * @noinspection PhpMissingParamTypeInspection
     */
    public function get($model, string $key, $value, array $attributes): ?EGPrivateKey
    {
        if (is_null($value)) {
            return null;
        }
        $data = json_decode($value, true);
        return EGPrivateKey::fromArray($data, $model->onlyStoreXY($key));
    }

    /**
     * @param ModelWithCryptoFields $model
     * @param string $key
     * @param null|EGPrivateKey $value
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
     * @param EGPrivateKey|null $value
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
