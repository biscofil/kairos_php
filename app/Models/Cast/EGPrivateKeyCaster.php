<?php


namespace App\Models\Cast;


use App\EGPrivateKey;
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
     * @return EGPrivateKey
     */
    public function get($model, string $key, $value, array $attributes)
    {
        $data = json_decode($value);
        return EGPrivateKey::fromArray($data);
    }

    /**
     * @param Model $model
     * @param string $key
     * @param EGPrivateKey $value
     * @param array $attributes
     * @return false|mixed|string
     */
    public function set($model, string $key, $value, array $attributes)
    {
        return json_encode($value->toArray());
    }

}
