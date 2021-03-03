<?php


namespace App\Models\Cast;

use App\Crypto\EGPublicKey;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Class EGPublicKeyCaster
 * @package App\Models\Cast
 */
class EGPublicKeyCaster implements CastsAttributes
{

    const ONLY_STORE_Y = true;

    /**
     * @param Model $model
     * @param string $key
     * @param mixed $value
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
        return EGPublicKey::fromArray($data, self::ONLY_STORE_Y);
    }

    /**
     * @param Model $model
     * @param string $key
     * @param EGPublicKey $value
     * @param array $attributes
     * @return null|string
     * @noinspection PhpMissingParamTypeInspection
     */
    public function set($model, string $key, $value, array $attributes): ?string
    {
        if (is_null($value)) {
            return null;
        }

        return json_encode($value->toArray(self::ONLY_STORE_Y));
    }

}
