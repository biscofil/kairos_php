<?php


namespace App\P2P\Tasks;


use App\Models\Mix;
use Illuminate\Support\Facades\Log;

/**
 * Class VerifyReceivedMix
 * @package App\P2P\Tasks
 * @property Mix mixModel
 */
class VerifyReceivedMix extends Task
{

    public Mix $mixModel;

    /**
     * WaitAndRespond constructor.
     * @param \App\Models\Mix $mixModel
     */
    public function __construct(Mix $mixModel)
    {
        $this->mixModel = $mixModel;
    }

    /**
     *
     */
    public function run()
    {
        Log::debug('Running VerifyReceivedMix on MIX # ' . $this->mixModel->id);

        /** @var \App\Voting\AnonymizationMethods\MixNets\MixNode $amClass */
        $amClass = $this->mixModel->trustee->election->anonymization_method->getClass();

        /** @var \App\Voting\AnonymizationMethods\MixNets\MixWithShadowMixes $primaryShadowMixesClass */
        $primaryShadowMixesClass = $amClass::getMixWithShadowMixesClass();

        $primaryShadowMixes = $primaryShadowMixesClass::load($this->mixModel->getFilename());

        try {
            if ($primaryShadowMixes->isProofValid()) {
                Log::info('Mix proof is valid!');
            } else {
                Log::warning('Mix proof is invalid!');
            }
        } catch (\Exception $e) {
            Log::error('Mix proof failed!');
        }

    }
}
