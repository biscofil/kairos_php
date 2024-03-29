<?php


namespace App\Models\Cast;


/**
 * Trait ModelWithFieldsWithParameterSets
 * @package App\Models\Cast
 * @property string[] cryptoFields
 */
trait ModelWithFieldsWithParameterSets
{

    /**
     * Returns a bool that indicates if the model wants a crypto field
     * to be stored in its entirety (cryptosystem params too)
     * @param string $fieldName
     * @param bool $default
     * @return bool
     */
    public function ignoreParameterSet(string $fieldName, bool $default = true): bool
    {
        if (property_exists($this, 'cryptoFields')
            && array_key_exists($fieldName, $this->cryptoFields)) {
            return $this->cryptoFields[$fieldName];
        }
        //is not specified, only store X,Y
        return $default;
    }

//    public function getFieldParameterSet(string $fieldName){
//        if (method_exists($this, 'getParameterSet')) {
//            return $this->getParameterSet($fieldName);
//        }
//    }

}
