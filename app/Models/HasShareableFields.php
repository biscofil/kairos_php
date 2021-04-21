<?php


namespace App\Models;


/**
 * Trait HasShareableFields
 * @package App\Models
 * @property array $shareableFields
 */
trait HasShareableFields
{

    /**
     */
    public function toShareableArray(): array
    {
        /** @var \Illuminate\Database\Eloquent\Model $this */

        $out = $this->toArray();

        if (!property_exists($this, 'shareableFields')) {
            return $out;
        }

        return array_intersect_key($out, array_flip($this->shareableFields));
    }

    /**
     * @param array $sharedData
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function fillFromSharedArray(array $sharedData)
    {

        /** @var \Illuminate\Database\Eloquent\Model $this */

        if (property_exists($this, 'shareableFields')) {

            $sharedData = array_intersect_key($sharedData, array_flip($this->shareableFields));
        }
        return $this->fill($sharedData);

    }


}
