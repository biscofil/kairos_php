<?php


namespace App\Models\Cast;


/**
 * Trait ModelWithCryptoFields
 * @package App\Models\Cast
 * @property array cryptoFieldsXYOnly
 */
trait ModelWithCryptoFields
{

    /**
     * Returns a bool that indicates if the model wants a crypto field
     * to be stored in its entirety (cryptosystem params too)
     * @param string $fieldName
     * @param bool $default
     * @return bool
     */
    public function onlyStoreXY(string $fieldName, bool $default = true): bool
    {
        if (property_exists($this, 'cryptoFieldsXYOnly')
            && array_key_exists($fieldName, $this->cryptoFields)) {
            return $this->cryptoFields[$fieldName];
        }
        //is not specified, only store X,Y
        return $default;
    }

}
