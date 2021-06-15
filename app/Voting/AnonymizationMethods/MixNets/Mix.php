<?php


namespace App\Voting\AnonymizationMethods\MixNets;


use App\Models\Election;
use App\Voting\AnonymizationMethods\BelongsToAnonymizationMethod;
use App\Voting\CryptoSystems\CipherText;
use App\Voting\CryptoSystems\ElGamal\EGDLogProof;
use App\Voting\CryptoSystems\PublicKey;

/**
 * Class Mix
 * @package App\Voting\MixNets
 * @property Election $election
 * @property Ciphertext[] ciphertexts
 * @property MixNodeParameterSet|null $parameterSet Either for the left of fr the right equivalence proof
 * @property EGDLogProof[]|null $proofs Either for the left of fr the right equivalence proof
 * @property PublicKey $publicKey
 */
abstract class Mix implements BelongsToAnonymizationMethod
{

    public Election $election;
    public array $ciphertexts;
    /** @var \App\Voting\AnonymizationMethods\MixNets\MixNodeParameterSet|null $parameterSet */
    public $parameterSet;
    public $proofs;
    protected PublicKey $publicKey;

    /**
     * @param Election $election
     * @param Ciphertext[] $ciphertexts
     * @param MixNodeParameterSet|null $parameterSet
     * @param null $proofs
     * @param \App\Voting\CryptoSystems\PublicKey|null $publicKey
     * @noinspection PhpMissingParamTypeInspection
     */
    public function __construct(Election $election, array $ciphertexts, $parameterSet = null, $proofs = null, ?PublicKey $publicKey = null)
    {
        $this->election = $election;
        $this->ciphertexts = $ciphertexts;
        $this->parameterSet = $parameterSet;
        $this->proofs = $proofs;
        $this->publicKey = $publicKey ?? $election->public_key;
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

        $pkClass = $election->cryptosystem->getClass()::getPublicKeyClass();
        $ctClass = $election->cryptosystem->getClass()::getCipherTextClass();

        $publicKey = $pkClass::fromArray($data['public_key']);

        $ciphertexts = array_map(function (array $cipher) use ($ctClass, $publicKey) {
            return $ctClass::fromArray($cipher, $publicKey);
        }, $data['ciphertexts']);

        /** @var \App\Voting\AnonymizationMethods\MixNets\MixNode $mixNodeClass */
        $mixNodeClass = static::getAnonimizationMethod();

        /** @var \App\Voting\AnonymizationMethods\MixNets\MixNodeParameterSet $psClass */
        $psClass = $mixNodeClass::getParameterSetClass();

        $parameterSet = is_null($data['parameter_set']) ? null : $psClass::fromArray($data['parameter_set']);

        $proofs = is_null($data['proofs']) ? null : array_map(function (?array $proofArray) {
            return is_null($proofArray) ? null : EGDLogProof::fromArray($proofArray); // TODO generalize
        }, $data['proofs']);

        return new static($election, $ciphertexts, $parameterSet, $proofs, $publicKey);
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'election_uuid' => $this->election->uuid,
            'public_key' => $this->publicKey->toArray(),
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
