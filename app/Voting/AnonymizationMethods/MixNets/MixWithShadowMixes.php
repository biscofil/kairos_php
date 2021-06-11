<?php


namespace App\Voting\AnonymizationMethods\MixNets;

use App\Models\Election;
use App\Voting\AnonymizationMethods\BelongsToAnonymizationMethod;
use App\Voting\CryptoSystems\CipherText;
use Illuminate\Support\Facades\Storage;

/**
 * Class MixWithShadowMixed
 * @package App\Voting\AnonymizationMethods\MixNets
 * @property \App\Voting\AnonymizationMethods\MixNets\Mix $primaryMix
 * @property \App\Voting\AnonymizationMethods\MixNets\Mix[] $shadowMixes
 * @property Ciphertext[] originalCiphertexts
 * @property string $challengeBits
 * @property array $proofs
 * @property MixNodeParameterSet[] $parameterSets
 * @property \App\Models\Election election
 */
abstract class MixWithShadowMixes implements BelongsToAnonymizationMethod
{

    public Mix $primaryMix;
    public array $shadowMixes;
    public array $originalCiphertexts;
    public string $challengeBits = '';
    public Election $election;
    //
    public array $proofs = [];
    public array $parameterSets = [];

    /**
     * MixWithShadowMixed constructor.
     * @param array $originalCipherTexts
     * @param \App\Voting\AnonymizationMethods\MixNets\Mix $primaryMix
     * @param array $shadowMixes
     * @param \App\Models\Election $election
     */
    public function __construct(array $originalCipherTexts, Mix $primaryMix, array $shadowMixes, Election $election)
    {
        $this->originalCiphertexts = $originalCipherTexts;
        $this->primaryMix = $primaryMix;
        $this->shadowMixes = $shadowMixes;
        $this->election = $election;
    }

    // ########################################################################

    /**
     * @return string
     */
    public function getHash(): string
    {
        return sha1(json_encode($this->toArray()));
    }

    // ########################################################################

    /**
     * Generate challenge bits
     * @return string
     */
    public function getFiatShamirChallengeBits(): string
    {
        $hex = sha1(implode('', array_map(function (Mix $mix) {
            return $mix->getHash();
        }, $this->shadowMixes)));
        $fullLen = (BI($hex, 16))->toBits();
        return substr($fullLen, 0, count($this->shadowMixes));
    }

    /**
     * @param string $bits
     */
    public function setChallengeBits(string $bits): void
    {
        $this->challengeBits = $bits;
    }

    /**
     * @throws \Exception
     */
    public function generateProofs(): void
    {
        $parameterSets = [];
        $proofs = [];

        // TODO clean parameter sets of opposite side

        for ($i = 0; $i < strlen($this->challengeBits); $i++) {

            $bit = $this->challengeBits[$i];
            $mix = $this->shadowMixes[$i];

            if ($bit === '0') { // left
                $parameterSets[] = $this->getLeftEquivalenceParameterSet($mix);
                $proofs[] = $this->getLeftProof($mix);
            } elseif ($bit === '1') { // right
                $parameterSets[] = $this->getRightEquivalenceParameterSet($mix);
                $proofs[] = $this->getRightProof($mix);
            } else {
                throw new \Exception("Bit must be either 1 or 0, '$bit' given");
            }

            $this->shadowMixes[$i]->parameterSet = null; // forget parameter set of shadow mix
        }

        $this->primaryMix->parameterSet = null; // forget parameter set of primary mix

        $this->parameterSets = $parameterSets;
        $this->proofs = $proofs;
    }

    /**
     * @param \App\Voting\AnonymizationMethods\MixNets\Mix $shadow
     * @return \App\Voting\AnonymizationMethods\MixNets\MixNodeParameterSet
     */
    abstract public function getLeftEquivalenceParameterSet(Mix $shadow): MixNodeParameterSet;

    /**
     * @param \App\Voting\AnonymizationMethods\MixNets\Mix $shadow
     * @return \App\Voting\AnonymizationMethods\MixNets\MixNodeParameterSet
     */
    abstract public function getRightEquivalenceParameterSet(Mix $shadow): MixNodeParameterSet;

    /**
     * @param \App\Voting\AnonymizationMethods\MixNets\Mix $shadow
     */
    abstract public function getLeftProof(Mix $shadow);

    /**
     * @param \App\Voting\AnonymizationMethods\MixNets\Mix $shadow
     */
    abstract public function getRightProof(Mix $shadow);

    // ########################################################################

    /**
     * @return bool
     * @throws \Exception
     */
    public function isProofValid(): bool
    {

        if ($this->challengeBits !== $this->getFiatShamirChallengeBits()) {
            return false;
        }

        foreach ($this->shadowMixes as $idx => $shadowMix) {

            $bit = $this->challengeBits[$idx];
            $parameterSet = $this->parameterSets[$idx];
            $proof = $this->proofs[$idx];

            if ($bit === '0') { // left
                if (!$this->checkLeftProof($shadowMix, $parameterSet, $proof)) {
                    return false;
                }
            } elseif ($bit === '1') { // right
                if (!$this->checkRightProof($shadowMix, $parameterSet, $proof)) {
                    return false;
                }
            } else {
                throw new \Exception("Bit must be either 1 or 0, '$bit' given");
            }

        }

        return true;

    }

    /**
     * @param \App\Voting\AnonymizationMethods\MixNets\Mix $shadowMix
     * @param \App\Voting\AnonymizationMethods\MixNets\MixNodeParameterSet $parameterSet
     * @param $proof
     * @return bool
     */
    abstract public function checkLeftProof(Mix $shadowMix, MixNodeParameterSet $parameterSet, $proof): bool;

    /**
     * @param \App\Voting\AnonymizationMethods\MixNets\Mix $shadowMix
     * @param \App\Voting\AnonymizationMethods\MixNets\MixNodeParameterSet $parameterSet
     * @param $proof
     * @return bool
     */
    abstract public function checkRightProof(Mix $shadowMix, MixNodeParameterSet $parameterSet, $proof): bool;

    // ########################################################################

    /**
     * @param string $fileName
     * @param bool $storePrivateValues
     * @return mixed
     */
    public function store(string $fileName, bool $storePrivateValues = false): void
    {
        $data = $this->toArray($storePrivateValues);
        Storage::put($fileName, json_encode($data, JSON_PRETTY_PRINT));
    }

    /**
     * @param string $fileName
     * @return bool
     */
    public function deleteFile(string $fileName): bool
    {
        return Storage::delete($fileName);
    }

    /**
     * @param string $fileName
     * @return \App\Voting\AnonymizationMethods\MixNets\MixWithShadowMixes
     * @throws \Exception
     */
    public static function load(string $fileName): self
    {
        $data = json_decode(Storage::get($fileName), true);
        return self::fromArray($data);
    }

    // ########################################################################

    /**
     * @param array $data
     * @return \App\Voting\AnonymizationMethods\MixNets\MixWithShadowMixes
     * @throws \Exception
     */
    public static function fromArray(array $data): self
    {
        $election = Election::findFromUuid($data['election_uuid']);

        $csClass = $election->cryptosystem->getClass();

        // challenge bits
        $challengeBits = $data['challenge_bits'];

        // original ciphertexts
        $ctClass = $csClass::getCipherTextClass();
        $originalCiphertexts = array_map(function (array $originalCiphertext) use ($election, $ctClass) {
            return $ctClass::fromArray($originalCiphertext, $election->public_key);
        }, $data['original_ciphertexts']);

        /** @var \App\Voting\AnonymizationMethods\MixNets\MixNode|string $mixNetAnonimizationMethodClass */
        $mixNetAnonimizationMethodClass = static::getAnonimizationMethod();
        $mixClass = $mixNetAnonimizationMethodClass::getMixClass();
        $parameterSetClass = $mixNetAnonimizationMethodClass::getParameterSetClass();

        $primaryMix = $mixClass::fromArray($data['primary_mix']);

        $shadowMixes = array_map(function (array $shadowMixArray) use ($mixClass) {
            return $mixClass::fromArray($shadowMixArray);
        }, $data['shadow_mixes']);

        $parameterSets = array_map(function (array $originalCiphertext) use ($parameterSetClass) {
            return $parameterSetClass::fromArray($originalCiphertext); // TODO check
        }, $data['parameter_sets']);

        $proofs = $data['proofs']; // TODO check, null for now, no custom type

        $out = new static($originalCiphertexts, $primaryMix, $shadowMixes, $election);
        $out->challengeBits = $challengeBits;
        $out->parameterSets = $parameterSets;
        $out->proofs = $proofs;
        return $out;
    }

    /**
     * @param bool $storePrivateValues
     * @return array
     */
    public function toArray(bool $storePrivateValues = false): array
    {
        return [
            'election_uuid' => $this->election->uuid,
            'challenge_bits' => $this->challengeBits,
            //
            'original_ciphertexts' => array_map(function (CipherText $cipherText) {
                return $cipherText->toArray(false);
            }, $this->originalCiphertexts),
            'primary_mix' => $this->primaryMix->toArray($storePrivateValues),
            'shadow_mixes' => array_map(function (Mix $shadowMix) use ($storePrivateValues) {
                return $shadowMix->toArray($storePrivateValues);
            }, $this->shadowMixes),
            //
            'parameter_sets' => array_map(function (MixNodeParameterSet $parameterSet) use ($storePrivateValues) {
                return $parameterSet->toArray();
            }, $this->parameterSets),
            'proofs' => $this->proofs
        ];
    }

}
