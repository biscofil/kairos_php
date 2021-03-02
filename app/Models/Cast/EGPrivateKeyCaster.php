<?php


namespace App\Models\Cast;


use App\Crypto\EGPrivateKey;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Class EGPrivateKey
 * @package App\Models\Cast
 */
class EGPrivateKeyCaster implements CastsAttributes
{

    /**
     * @param Model $model
     * @param string $key
     * @param mixed $value
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
        return EGPrivateKey::fromArray($data);
    }

    /**
     * @param Model $model
     * @param string $key
     * @param EGPrivateKey $value
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

}
