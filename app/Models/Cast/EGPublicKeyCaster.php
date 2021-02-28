<?php


namespace App\Models\Cast;

use App\EGPublicKey;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use PHP\Math\BigInteger\BigInteger;

/**
 * Class EGPublicKeyCaster
 * @package App\Models\Cast
 */
class EGPublicKeyCaster implements CastsAttributes
{

    /**
     * @param Model $model
     * @param string $key
     * @param mixed $value
     * @param array $attributes
     * @return EGPublicKey
     */
    public function get($model, string $key, $value, array $attributes): EGPublicKey
    {
        $data = json_decode($value);
        return EGPublicKey::fromArray($data);
    }

    /**
     * @param Model $model
     * @param string $key
     * @param EGPublicKey $value
     * @param array $attributes
     * @return string
     */
    public function set($model, string $key, $value, array $attributes): string
    {
        return json_encode($value->toArray());
    }

}
