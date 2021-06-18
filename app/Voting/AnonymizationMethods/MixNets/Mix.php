<?php


namespace App\Voting\AnonymizationMethods\MixNets;


use App\Voting\AnonymizationMethods\BelongsToAnonymizationMethod;
use App\Voting\CryptoSystems\CipherText;
use App\Voting\CryptoSystems\ElGamal\EGDLogProof;
use Illuminate\Support\Facades\Storage;

/**
 * Class Mix
 * @package App\Voting\MixNets
 * @property Ciphertext[] ciphertexts
 * @property MixNodeParameterSet|null $parameterSet Either for the left of fr the right equivalence proof
 * @property EGDLogProof[]|null $proofs Either for the left of fr the right equivalence proof
 * @property null|string $hash
 */
abstract class Mix implements BelongsToAnonymizationMethod
{

    public array $ciphertexts;
    /** @var \App\Voting\AnonymizationMethods\MixNets\MixNodeParameterSet|null $parameterSet */
    public $parameterSet;
    public $proofs;
    public ?string $hash;

    /**
     * @param Ciphertext[] $ciphertexts
     * @param MixNodeParameterSet|null $parameterSet
     * @param null $proofs
     * @noinspection PhpMissingParamTypeInspection
     */
    public function __construct(array $ciphertexts, $parameterSet = null, $proofs = null, ?string $hash = null)
    {
        $this->ciphertexts = $ciphertexts;
        $this->parameterSet = $parameterSet;
        $this->proofs = $proofs;
        $this->hash = $hash;
    }

    // ################################################################

    /**
     * @param \App\Models\Mix $mixModel
     * @param string $filename
     * @return static
     */
    public static function load(\App\Models\Mix $mixModel, string $filename): self
    {
        $data = json_decode(Storage::get($filename), true);
        return static::fromArray($mixModel, $data);
    }

    /**
     * @param string $filename
     * @return bool
     */
    public function deleteFile(string $filename): bool
    {
        return Storage::delete($filename);
    }

    /**
     * @param string $filename
     * @return void
     */
    public function store(string $filename): void
    {
        if (is_null($this->hash)) {
            $this->getHash();
        }
        $data = $this->toArray();
        Storage::put($filename, json_encode($data, JSON_PRETTY_PRINT));
    }

    // ################################################################

    /**
     * Hash is not enough to declare to mixes equal
     * @return string
     */
    public function getHash(): string
    {
        if (is_null($this->hash)) {
            $this->hash = array_reduce($this->ciphertexts, function (string $carry, CipherText $ciphertext) {
                return sha1($carry . $ciphertext->getFingerprint());
            }, '');
        }
        return $this->hash;
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
     * @param \App\Models\Mix $mixModel
     * @param array $data
     * @return Mix
     */
    public static function fromArray(\App\Models\Mix $mixModel, array $data): Mix
    {
        $election = $mixModel->trustee->election;

        $ctClass = $election->cryptosystem->getClass()::getCipherTextClass();

        $pk = $mixModel->computePublicKeyOfNextTrustees();
        $ciphertexts = array_map(function (array $cipher) use ($ctClass, $pk) {
            return $ctClass::fromArray($cipher, $pk);
        }, $data['ciphertexts']);

        /** @var \App\Voting\AnonymizationMethods\MixNets\MixNode $mixNodeClass */
        $mixNodeClass = static::getAnonimizationMethod();

        /** @var \App\Voting\AnonymizationMethods\MixNets\MixNodeParameterSet $psClass */
        $psClass = $mixNodeClass::getParameterSetClass();

        $parameterSet = is_null($data['parameter_set']) ? null : $psClass::fromArray($data['parameter_set']);

        $proofs = is_null($data['proofs']) ? null : array_map(function (?array $proofArray) {
            return is_null($proofArray) ? null : EGDLogProof::fromArray($proofArray); // TODO generalize
        }, $data['proofs']);

        $hash = $data['hash'];

        return new static($ciphertexts, $parameterSet, $proofs, $hash);
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'ciphertexts' => array_map(function (CipherText $cipherText) {
                return $cipherText->toArray(false);
            }, $this->ciphertexts),
            'parameter_set' => is_null($this->parameterSet) ? null : $this->parameterSet->toArray(),
            'proofs' => is_null($this->proofs) ? null : array_map(function (?EGDLogProof $proof) { // TODO generalize
                return is_null($proof) ? null : $proof->toArray();
            }, $this->proofs),
            'hash' => $this->hash
        ];
    }

}
