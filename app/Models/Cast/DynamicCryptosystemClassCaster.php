<?php


namespace App\Models\Cast;


use App\Enums\CryptoSystemEnum;
use App\Voting\CryptoSystems\BelongsToCryptoSystem;
use App\Voting\CryptoSystems\CryptoSystem;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Database\Eloquent\SerializesCastableAttributes;

/**
 * Class DynamicCryptosystemClassCaster
 * @package App\Models\Cast
 */
abstract class DynamicCryptosystemClassCaster implements CastsAttributes, SerializesCastableAttributes
{

    // ########################################################################
    // ############################ DB TO MODEL ###############################
    // ########################################################################

    /**
     * @param ModelWithFieldsWithParameterSets $model
     * @param string $key
     * @param string|null $value
     * @param array $attributes
     * @return null|BelongsToCryptoSystem|\App\Models\Cast\Castable
     * @noinspection PhpMissingParamTypeInspection
     */
    public function get($model, string $key, $value, array $attributes)
    {
        if (is_null($value)) {
            return null;
        }

        $data = json_decode($value, true);

        $cryptoSystemClassName = $this->getCryptosystemFromDBRecord($data);

        $className = $this->getTargetClassConstantName($cryptoSystemClassName);
//        $className = (new ReflectionClass($cryptoSystemClassName))->getConstant($this->getTargetClassConstantName());

        return $className::fromArray($data, $model->ignoreParameterSet($key));
//
//        $reflectionMethod = new ReflectionMethod($className, 'fromArray');
//        return $reflectionMethod->invokeArgs(NULL, [$data, $model->ignoreParameterSet($key)]);
    }

    /**
     * @param array $data
     * @return string|\App\Voting\CryptoSystems\CryptoSystem
     */
    protected function getCryptosystemFromDBRecord(array &$data): string
    {
        $cryptoSystemIdentifier = $data['_cs'];
        /** @var CryptoSystem $cryptoSystemClassName */
        $cryptoSystemClassName = CryptoSystemEnum::getByIdentifier($cryptoSystemIdentifier);
        unset($data['_cs']);
        return $cryptoSystemClassName;
    }

    /**
     * Specify the name of the constant of a cryptosystem class (RSA/Elgamal) that contains the name of the class
     * we want to cast the value to
     * @param string $cs
     * @return string|\App\Models\Cast\Castable
     */
    abstract public function getTargetClassConstantName(string $cs): string;

    // ########################################################################
    // ############################ MODEL TO DB ###############################
    // ########################################################################

    /**
     * @param ModelWithFieldsWithParameterSets $model
     * @param string $key
     * @param null|BelongsToCryptoSystem|\App\Models\Cast\Castable $value
     * @param array $attributes
     * @return null|BelongsToCryptoSystem|\App\Models\Cast\Castable
     * @noinspection PhpMissingParamTypeInspection
     */
    public function set($model, string $key, $value, array $attributes): ?string
    {
        if (is_null($value)) {
            return null;
        }
        $out = $value->toArray($model->ignoreParameterSet($key));
        $out['_cs'] = CryptoSystemEnum::getIdentifier($value);
        return json_encode($out);
    }

    // ########################################################################

    /**
     * @param ModelWithFieldsWithParameterSets $model
     * @param string $key
     * @param BelongsToCryptoSystem|null $value
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
        $out = $value->toArray();
        $out['_cs'] = CryptoSystemEnum::getIdentifier($value);
        return $out;
    }

}
