<?php


namespace App\Models\Cast;


use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Database\Eloquent\SerializesCastableAttributes;
use Illuminate\Database\Eloquent\Model;
use phpseclib3\Math\BigInteger;

/**
 * Class BigIntCaster
 * @package App\Models\Cast
 */
class BigIntCaster implements CastsAttributes, SerializesCastableAttributes
{

    /**
     * @param Model $model
     * @param string $key
     * @param mixed $value
     * @param array $attributes
     * @return null|BigInteger
     * @noinspection PhpMissingParamTypeInspection
     */
    public function get($model, string $key, $value, array $attributes): ?BigInteger
    {
        if (is_null($value)) {
            return null;
        }
        return new BigInteger($value, 16);
    }

    /**
     * @param Model $model
     * @param string $key
     * @param null|BigInteger $value
     * @param array $attributes
     * @return null|string
     * @noinspection PhpMissingParamTypeInspection
     */
    public function set($model, string $key, $value, array $attributes): ?string
    {
        if (is_null($value)) {
            return null;
        }
        return $value->toHex();
    }

    /**
     * @param Model $model
     * @param string $key
     * @param BigInteger|null $value
     * @param array $attributes
     * @return null|string
     * @noinspection PhpMissingParamTypeInspection
     * @noinspection PhpMissingReturnTypeInspection
     */
    public function serialize($model, string $key, $value, array $attributes)
    {
        if (is_null($value)) {
            return null;
        }
        return $value->toHex();
    }

}
