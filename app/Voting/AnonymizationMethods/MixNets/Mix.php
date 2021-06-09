<?php


namespace App\Voting\AnonymizationMethods\MixNets;


use App\Models\Election;
use App\Voting\AnonymizationMethods\BelongsToAnonymizationMethod;
use App\Voting\CryptoSystems\CipherText;

/**
 * Class Mix
 * @package App\Voting\MixNets
 * @property Election $election
 * @property Ciphertext[] ciphertexts
 * @property MixNodeParameterSet|null $parameterSet
 */
abstract class Mix implements BelongsToAnonymizationMethod
{

    public Election $election;
    public array $ciphertexts;
    /** @var \App\Voting\AnonymizationMethods\MixNets\MixNodeParameterSet|null */
    public $parameterSet;

    /**
     * @param Election $election
     * @param Ciphertext[] $ciphertexts
     * @param MixNodeParameterSet|null $parameterSet
     * @throws \Exception
     * @noinspection PhpMissingParamTypeInspection
     */
    public function __construct(Election $election, array $ciphertexts, $parameterSet = null)
    {
        $this->election = $election;
        $this->ciphertexts = $ciphertexts;
        $this->parameterSet = $parameterSet;
    }

    /**
     * Hash is not enough to declare to mixes equal
     * @return string
     */
    public function getHash(): string
    {
        return array_reduce($this->ciphertexts, function (string $carry, CipherText $ciphertext) {
            return sha1($carry . $ciphertext->getFingerprint());
        }, '');
    }

    /**
     * TODO proof should not use the private key!!!!!
     *   use dlog proof
     * @param Mix $b
     * @return bool
     * @throws \Exception
     * @deprecated
     */
    public function equals(Mix $b): bool
    {
        for ($i = 0; $i < count($this->ciphertexts); $i++) {

            if (!$this->ciphertexts[$i]->equals($b->ciphertexts[$i])) {
//                dump($this->ciphertexts[$i]->toArray());
//                dump($b->ciphertexts[$i]->toArray());
                return false;
            }

            // private key check
//            if (!$sk->decrypt($this->ciphertexts[$i])->m->equals($sk->decrypt($b->ciphertexts[$i])->m)) {
//                return false;
//            }

            // todo dlog

        }
        return true;
    }

    // ########################################################################

    /**
     * @param array $data
     * @return Mix
     * @throws \Exception
     */
    public static function fromArray(array $data): Mix
    {
        $election = Election::findFromUuid($data['election_uuid']);

        $ctClass = $election->cryptosystem->getClass()::getCipherTextClass();
        $ciphertexts = array_map(function (array $cipher) use ($ctClass, $election) {
            return $ctClass::fromArray($cipher, $election->public_key);
        }, $data['ciphertexts']);

        /** @var \App\Voting\AnonymizationMethods\MixNets\MixNode $mixNodeClass */
        $mixNodeClass = static::getAnonimizationMethod();

        /** @var \App\Voting\AnonymizationMethods\MixNets\MixNodeParameterSet $psClass */
        $psClass = $mixNodeClass::getParameterSetClass();

        $parameterSet = is_null($data['parameter_set'])
            ? null
            : $psClass::fromArray($data['parameter_set']);

        return new static($election, $ciphertexts, $parameterSet);
    }

    /**
     * @param bool $storeParameterSet only set to TRUE for shadow mixes
     * @return array
     */
    public function toArray(bool $storeParameterSet = false): array
    {
        return [
            'election_uuid' => $this->election->uuid,
            'ciphertexts' => array_map(function (CipherText $cipherText) {
                return $cipherText->toArray(false);
            }, $this->ciphertexts),
            'parameter_set' => ($storeParameterSet && $this->parameterSet) ? $this->parameterSet->toArray() : null
        ];
    }

}
