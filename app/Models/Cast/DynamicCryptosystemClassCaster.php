<?php


namespace App\Models\Cast;


use App\Voting\CryptoSystems\CryptoSystem;
use App\Voting\CryptoSystems\ElGamal\EGPublicKey;
use App\Voting\CryptoSystems\PublicKey;
use App\Voting\CryptoSystems\SecretKey;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Database\Eloquent\SerializesCastableAttributes;
use ReflectionClass;
use ReflectionMethod;

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
     * @return PublicKey|SecretKey
     * @noinspection PhpMissingParamTypeInspection
     * @throws \ReflectionException
     */
    public function get($model, string $key, $value, array $attributes)
    {
        if (is_null($value)) {
            return null;
        }

        $data = json_decode($value, true);

        $cryptoSystemIdentifier = $data['_cs'];
        unset($data['_cs']);

        $cryptoSystemClassName = CryptoSystem::getByIdentifier($cryptoSystemIdentifier);

        $className = (new ReflectionClass($cryptoSystemClassName))->getConstant($this->getTargetClassConstantName());

        $reflectionMethod = new ReflectionMethod($className, 'fromArray');
        return $reflectionMethod->invokeArgs(NULL, [$data, $model->onlyStoreXY($key)]);
    }

    /**
     * Specify the name of the constant of a cryptosystem class (RSA/Elgamal) that contains the name of the class
     * we want to cast the value to
     * @return string
     */
    abstract function getTargetClassConstantName(): string;

    // ########################################################################
    // ############################ MODEL TO DB ###############################
    // ########################################################################

    /**
     * @param ModelWithFieldsWithParameterSets $model
     * @param string $key
     * @param null|SecretKey $value
     * @param array $attributes
     * @return null|string
     * @noinspection PhpMissingParamTypeInspection
     */
    public function set($model, string $key, $value, array $attributes): ?string
    {
        if (is_null($value)) {
            return null;
        }
        $out = $value->toArray($model->onlyStoreXY($key));
        $out['_cs'] = CryptoSystem::getIdentifier($value);
        return json_encode($out);
    }

    // ########################################################################

    /**
     * @param ModelWithFieldsWithParameterSets $model
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
        $out = $value->toArray();
        $out['_cs'] = CryptoSystem::getIdentifier($value);
        return $out;
    }

}
