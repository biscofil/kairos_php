<?php


namespace App\Voting\AnonymizationMethods\MixNets;

use App\Models\CastVote;
use App\Models\Election;
use App\Models\Mix as MixModel;
use App\Models\Trustee;
use App\Voting\AnonymizationMethods\BelongsToAnonymizationMethod;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Class MixWithShadowMixed
 * @package App\Voting\AnonymizationMethods\MixNets
 * @property MixModel $mixModel
 * @property null|array $shadowMixHashes
 */
abstract class MixWithShadowMixes implements BelongsToAnonymizationMethod
{

    public MixModel $mixModel;
    private ?array $shadowMixHashes = null; // used to perform hashing in an optimized way

    /**
     * MixWithShadowMixed constructor.
     * @param MixModel $mixModel
     */
    public function __construct(MixModel &$mixModel)
    {
        $this->mixModel = $mixModel;
    }

    // ########################################################################

    /**
     * Once the challenge bits have been generated, this method egnerates a proof for each bit
     * @param \App\Models\Trustee $claimer
     * @throws \Exception
     */
    public function generateProofs(Trustee $claimer): void
    {
        $this->mixModel = $this->mixModel->fresh(); // TODO check!!!!!

        if (strlen($this->mixModel->challenge_bits) === 0) {
            throw new Exception("Challenge bit string can't be empty");
        }

        $challengeBitArray = str_split($this->mixModel->challenge_bits);
        $count = count($challengeBitArray);

//        if (array_keys($challengeBitArray) !== array_keys($this->shadowMixes)) {
//            throw new Exception($count . ' challenge bits and ' . count($this->shadowMixes) . ' shadow mixes');
//        }

        /** @var Mix $mixClass */
        $mixClass = static::getAnonimizationMethod()::getMixClass();

        $inputMix = $this->mixModel->getInputCipherTextsMix();
        $primaryMix = $this->getPrimaryMix();

        array_map(function (string $bit, int $i) use ($inputMix, $primaryMix, $mixClass, $claimer, $count) {

            Log::debug('Generating proof ' . ($i + 1) . ' / ' . $count);

            $shadowMixFilename = $this->mixModel->getShadowMixFilename($i);
            $shadowMix = $mixClass::load($this->mixModel, $shadowMixFilename);

            if ($bit === '0') { // left

                $shadowMix->parameterSet = $this->getLeftEquivalenceParameterSet($shadowMix);
                $shadowMix->proofs = $this->getLeftProofs($shadowMix, $claimer, $shadowMix->parameterSet);

                if (!$this->checkLeftProof($inputMix, $shadowMix, $claimer)) {
                    throw new \Exception(" > invalid left proof #$i generated");
                }

            } elseif ($bit === '1') { // right

                $shadowMix->parameterSet = $this->getRightEquivalenceParameterSet($shadowMix, $primaryMix);
                $shadowMix->proofs = $this->getRightProofs($shadowMix, $claimer, $shadowMix->parameterSet);

                if (!$this->checkRightProof($shadowMix, $primaryMix, $claimer)) {
                    throw new \Exception(" > invalid right proof #$i generated");
                }

            } else {
                throw new Exception("Bit must be either 1 or 0, '$bit' given");
            }

            $shadowMix->store($shadowMixFilename);

        }, $challengeBitArray, range(0, $count - 1));

        $primaryMix->parameterSet = null; // forget parameter set of primary mix
        $primaryMix->proofs = null;
        $primaryMix->store($this->mixModel->getPrimaryMixFilename());

    }

    /**
     * Checks the correctenss of each shadow mix given their proofs
     * @return bool
     * @throws \Exception
     */
    public function isProofValid(): bool
    {

        $this->mixModel = $this->mixModel->fresh(); // TODO check

        $claimer = $this->mixModel->trustee;

        /** @var \App\Voting\AnonymizationMethods\MixNets\Mix $mixClass */
        $mixClass = static::getAnonimizationMethod()::getMixClass();

        $inputMix = $this->mixModel->getInputCipherTextsMix();
        $primaryMix = $this->getPrimaryMix();

        $this->shadowMixHashes = []; // keep track of hash for global hash

        foreach (range(0, $this->mixModel->shadow_mix_count - 1) as $idx) {

            $shadowMix = $mixClass::load($this->mixModel, $this->mixModel->getShadowMixFilename($idx));

            Log::debug('Checking shadow mix ' . ($idx + 1) . ' / ' . $this->mixModel->shadow_mix_count);

            $bit = $this->mixModel->challenge_bits[$idx];

            if ($bit === '0') { // left
                if (!$this->checkLeftProof($inputMix, $shadowMix, $claimer)) {
                    Log::error(" > invalid left proof #$idx");
                    return false;
                }
            } elseif ($bit === '1') { // right
                if (!$this->checkRightProof($shadowMix, $primaryMix, $claimer)) {
                    Log::error(" > invalid right proof #$idx");
                    return false;
                }
            } else {
                throw new Exception("Bit must be either 1 or 0, '$bit' given");
            }

            $this->shadowMixHashes[] = $shadowMix->getHash();

        }

        // check after cycling trough all the mixes in order to only load them once
        // the hash of every mix is stored in $this->shadowMixHashes
        // and will be used by getFiatShamirChallengeBits()
        if ($this->mixModel->challenge_bits !== $this->getFiatShamirChallengeBits()) {
            return false;
        }

        return true;

    }

    // ########################################################################

    /**
     * @return \App\Voting\AnonymizationMethods\MixNets\Mix
     */
    public function getPrimaryMix(): Mix
    {
        $mixClass = static::getAnonimizationMethod()::getMixClass();
        return $mixClass::load($this->mixModel, $this->mixModel->getPrimaryMixFilename());
    }

    /**
     * Creates a mix containing the ciphertexts extracted from the bulletin board
     * @param \App\Models\Election $election
     * @return \App\Voting\AnonymizationMethods\MixNets\Mix
     */
    public static function extractVotesFromBulletinBoard(Election $election): Mix
    {
        /** @var Mix $mixClass */
        $mixClass = static::getAnonimizationMethod()::getMixClass();

        /** @var Mix $out */
        return new $mixClass(
            $election->votes()->onlyLastOfVoters()->get()->map(function (CastVote $castVote) {
                return $castVote->vote;
            })->toArray()
        );
    }

    /**
     * @return string
     */
    public function getHash(): string
    {

        /** @var Mix $mixClass */
        $mixClass = static::getAnonimizationMethod()::getMixClass();

        if (is_null($this->shadowMixHashes)) {
            // only if not performed yet
            $this->shadowMixHashes = array_map(function (int $i) use ($mixClass): string {
                $mix = $mixClass::load($this->mixModel, $this->mixModel->getShadowMixFilename($i));
                return $mix->getHash();
            }, range(0, $this->mixModel->shadow_mix_count - 1));
        }

        return sha1(implode('', $this->shadowMixHashes));
    }

    // ########################################################################

    /**
     * Generate challenge bits
     * @return string
     */
    public function getFiatShamirChallengeBits(): string
    {
        $hash = $this->getHash();
        $fullLen = (BI($hash, 16))->toBits();
        $out = substr($fullLen, 0, $this->mixModel->shadow_mix_count);

//        // make sure enough zeros and ones
//        if (substr_count($out, '0') == 0) {
//            // add zeros
//        } elseif (substr_count($out, '1') == 0) {
//            // add ones
//        }

        return $out;
    }

    // ########################################################################

    /**
     * @param Mix $shadow
     * @return \App\Voting\AnonymizationMethods\MixNets\MixNodeParameterSet
     */
    abstract public function getLeftEquivalenceParameterSet(Mix $shadow): MixNodeParameterSet;

    /**
     * @param Mix $shadowMix
     * @param Mix $primaryMix
     * @return \App\Voting\AnonymizationMethods\MixNets\MixNodeParameterSet
     */
    abstract public function getRightEquivalenceParameterSet(Mix $shadowMix, Mix $primaryMix): MixNodeParameterSet;

    // ########################################################################

    /**
     * @param Mix $shadow
     * @param \App\Models\Trustee $claimer
     * @param \App\Voting\AnonymizationMethods\MixNets\MixNodeParameterSet $parameterSet
     */
    abstract public function getLeftProofs(Mix $shadow, Trustee $claimer, MixNodeParameterSet $parameterSet): ?array;

    /**
     * @param \App\Voting\AnonymizationMethods\MixNets\Mix $shadow
     * @param \App\Models\Trustee $claimer
     * @param \App\Voting\AnonymizationMethods\MixNets\MixNodeParameterSet $parameterSet
     */
    abstract public function getRightProofs(Mix $shadow, Trustee $claimer, MixNodeParameterSet $parameterSet): ?array;

    // ########################################################################

    /**
     * @param \App\Voting\AnonymizationMethods\MixNets\Mix $inputCipherTexts
     * @param \App\Voting\AnonymizationMethods\MixNets\Mix $shadowMix
     * @param \App\Models\Trustee $claimer
     * @return bool
     */
    abstract public function checkLeftProof(Mix $inputCipherTexts, Mix $shadowMix, Trustee $claimer): bool;

    /**
     * @param \App\Voting\AnonymizationMethods\MixNets\Mix $shadowMix
     * @param \App\Voting\AnonymizationMethods\MixNets\Mix $primaryMix
     * @param \App\Models\Trustee $claimer
     * @return bool
     */
    abstract public function checkRightProof(Mix $shadowMix, Mix $primaryMix, Trustee $claimer): bool;

    // ########################################################################

    /**
     * @param string $fileName
     * @return bool
     * @deprecated
     */
    public function deleteFile(string $fileName): bool
    {
        return Storage::delete($fileName);
    }

}
