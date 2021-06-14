<?php


namespace App\Voting\AnonymizationMethods\MixNets;


use App\Models\Election;
use App\Voting\AnonymizationMethods\BelongsToAnonymizationMethod;
use App\Voting\CryptoSystems\CipherText;
use App\Voting\CryptoSystems\ElGamal\EGDLogProof;

/**
 * Class Mix
 * @package App\Voting\MixNets
 * @property Election $election
 * @property Ciphertext[] ciphertexts
 * @property MixNodeParameterSet|null $parameterSet
 * @property null $proofs
 */
abstract class Mix implements BelongsToAnonymizationMethod
{

    public Election $election;
    public array $ciphertexts;
    /** @var \App\Voting\AnonymizationMethods\MixNets\MixNodeParameterSet|null $parameterSet */
    public $parameterSet;
    public $proofs;

    /**
     * @param Election $election
     * @param Ciphertext[] $ciphertexts
     * @param MixNodeParameterSet|null $parameterSet
     * @param null $proofs
     * @noinspection PhpMissingParamTypeInspection
     */
    public function __construct(Election $election, array $ciphertexts, $parameterSet = null, $proofs = null)
    {
        $this->election = $election;
        $this->ciphertexts = $ciphertexts;
        $this->parameterSet = $parameterSet;
        $this->proofs = $proofs;
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
     * @param Mix $b
     * @return bool
     * @throws \Exception
     */
    public function equals(Mix $b): bool
    {
        for ($i = 0; $i < count($this->ciphertexts); $i++) {
            if (!$this->ciphertexts[$i]->equals($b->ciphertexts[$i])) {
                return false;
            }
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

        $parameterSet = is_null($data['parameter_set']) ? null : $psClass::fromArray($data['parameter_set']);

        $proofs = is_null($data['proofs']) ? null : array_map(function (?array $proofArray) { // TODO generalize
            return is_null($proofArray) ? null : EGDLogProof::fromArray($proofArray);
        }, $data['proofs']);

        return new static($election, $ciphertexts, $parameterSet, $proofs);
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'election_uuid' => $this->election->uuid,
            'ciphertexts' => array_map(function (CipherText $cipherText) {
                return $cipherText->toArray(false);
            }, $this->ciphertexts),
            'parameter_set' => is_null($this->parameterSet) ? null : $this->parameterSet->toArray(),
            'proofs' => is_null($this->proofs) ? null : array_map(function (?EGDLogProof $proof) { // TODO generalize
                return is_null($proof) ? null : $proof->toArray();
            }, $this->proofs)
        ];
    }

}
