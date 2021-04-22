<?php


namespace App\Models\Cast;


/**
 * Class FieldWithParameterSet
 * @package App\Models\Cast
 */
abstract class FieldWithParameterSet extends DynamicCryptosystemClassCaster
{

    /**
     * @param \App\Models\Cast\ModelWithFieldsWithParameterSets $model
     * @param string $key
     * @param string|null $value
     * @param array $attributes
     * @return \App\Voting\CryptoSystems\PublicKey|\App\Voting\CryptoSystems\SecretKey|null
     * @throws \ReflectionException
     */
    public function get($model, string $key, $value, array $attributes)
    {
        return parent::get($model, $key, $value, $attributes);
    }

    /**
     * @param \App\Models\Cast\ModelWithFieldsWithParameterSets $model
     * @param string $key
     * @param \App\Voting\CryptoSystems\SecretKey|null $value
     * @param array $attributes
     * @return string|null
     */
    public function set($model, string $key, $value, array $attributes): ?string
    {
        return parent::set($model, $key, $value, $attributes);
    }

}
