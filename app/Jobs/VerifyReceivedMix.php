<?php


namespace App\Jobs;


use App\Models\Mix;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Class VerifyReceivedMix
 * @package App\Jobs
 * @property Mix mixModel
 */
class VerifyReceivedMix implements ShouldQueue
{

    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

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
     * Execute the job.
     *
     * @return void
     * @throws \Exception
     */
    public function handle()
    {
        // TODO has to work with both encryption, decryption, re-encryption

        $election = $this->mixModel->trustee->election;
        $meTrustee = $election->getTrusteeFromPeerServer(getCurrentServer(), true);

        // if fully decrypted, stop
        $completeMixChain = $this->mixModel->getChainLenght() === $election->min_peer_count_t;

        if ($this->mixModel->verify()) {

            if ($completeMixChain) {

                Log::info('Chain lenght limit reached');

                /** @var \App\Voting\AnonymizationMethods\MixNets\MixNode $amClass */
                $amClass = $election->anonymization_method->getClass();
                $amClass::afterSuccessfulMixProcess($election);
                return;
            }

            if ($meTrustee->comesAfterTrustee($this->mixModel->trustee)) {
                Log::info('Running GenerateMix from previous mix');

                // todo filter qualified peer trustees and combine keys
                $firstValidTrustee = ($this->mixModel->getMixNodeChain()[0])->trustee;

                if ($election->hasTLThresholdScheme()) {
                    $this->mixModel->generateSecretKeyFromShares($election, $firstValidTrustee);
                }

                // if the current peer server is the next in line TODO check
                GenerateMix::dispatchSync($election, $this->mixModel);

                // TODO here we should execute code, not executed because of the same peer issue
            }

        } else {

            if ($completeMixChain) {
                // TODO check
                Log::info('Chain lenght limit reached');
                return;
            }

            if ($meTrustee->comesAfterTrustee($this->mixModel->trustee)) {
                Log::info('Running GenerateMix from bulletin board');

                // TODO if t-l-encryption use share of current server and (t-1) keys of the next peers

                // start from scratch with curent server as first valid mix node
                if ($election->hasTLThresholdScheme()) {
                    $this->mixModel->generateSecretKeyFromShares($election, $meTrustee);
                }

                // if the current peer server is the next in line TODO check
                GenerateMix::dispatchSync($election);
            }
        }


    }
}
