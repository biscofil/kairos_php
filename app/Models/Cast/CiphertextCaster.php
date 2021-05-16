<?php


namespace App\Models\Cast;


use App\Models\Election;
use App\Voting\CryptoSystems\CryptoSystem;

/**
 * Class CiphertextCaster
 * @package App\Models\Cast
 */
class CiphertextCaster extends DynamicCryptosystemClassCaster
{

    /**
     * Specify the name of the constant of a cryptosystem class (RSA/Elgamal) that contains the name of the class
     * we want to cast the value to
     * @param string|CryptoSystem $cs
     * @return string
     */
    public function getTargetClassConstantName(string $cs): string
    {
        return $cs::getCipherTextClass();
    }

    /**
     * From DB record to Model
     * @param ModelWithFieldsWithParameterSets $model
     * @param string $key
     * @param string|null $value
     * @param array $attributes
     * @return \App\Models\Cast\Castable|\App\Voting\CryptoSystems\CipherText|null
     * @noinspection PhpMissingParamTypeInspection
     * @noinspection PhpMissingParentCallCommonInspection
     */
    public function get($model, string $key, $value, array $attributes)
    {
        if (is_null($value)) {
            return null;
        }
        $data = json_decode($value, true);

        if (is_null($data)) {
            return null;
        }

        // use the attribute to get the public key y of the election
        // $pk =  Voter::findOrFail($attributes['voter_id'])->election->public_key
        $pk = Election::findOrFail($attributes['election_id'])->public_key; // TODO cache

        $cryptoSystemClass = $this->getCryptosystemFromDBRecord($data);

        $cipherTextClass = $cryptoSystemClass::getCipherTextClass();

        $data['pk'] = $pk;

        return $cipherTextClass::fromArray($data, true);
    }

}
