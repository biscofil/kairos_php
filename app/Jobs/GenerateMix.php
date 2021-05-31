<?php

namespace App\Jobs;

use App\Models\CastVote;
use App\Models\Election;
use App\Models\Mix;
use App\Models\PeerServer;
use App\P2P\Messages\ThisIsMyMixSet\ThisIsMyMixSetRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Class GenerateMix
 * @package App\Jobs
 * @property Election election
 * @property Collection|\App\Models\CastVote[]|null votes
 */
class GenerateMix implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public Election $election;
    public ?Collection $votes;

    /**
     * GenerateMix constructor.
     * @param \App\Models\Election $election
     * @param Collection|\App\Models\CastVote[]|null $votes
     */
    public function __construct(Election $election, ?Collection $votes = null)
    {
        $this->election = $election;
        $this->votes = $votes;
    }

    /**
     * Execute the job.
     * @return void
     * @throws \Exception
     * @see \App\Voting\AnonymizationMethods\MixNets\MixNode::afterVotingPhaseEnds()
     */
    public function handle()
    {
        $meTrustee = $this->election->getTrusteeFromPeerServer(PeerServer::me());

        if ($meTrustee) {
            // current server is a peer

            if ($meTrustee->getPeerServerIndex() === 1) {
                // if this is first peer of the sequence

                if (is_null($this->votes)) {
                    // first step, take from bulletin board
                    $this->votes = $this->election->votes()->get();
                }
                Log::debug('Running mix on ' . count($this->votes) . ' votes');

                /** @var \App\Voting\AnonymizationMethods\MixNets\MixNode|string $mixClass */
                $mixClass = $this->election->anonymization_method->getClass();

                /** @var Collection|\App\Voting\CryptoSystems\CipherText[] $cipherTexts */
                $cipherTexts = $this->votes->map(function (CastVote $castVote) {
                    return $castVote->vote;
                });

                // generate shadow mixes
                $primaryShadowMixes = $mixClass::generate($this->election, $cipherTexts->toArray(), 80);

                // generate challenge bits & proofs
                $primaryShadowMixes->challengeBits = $primaryShadowMixes->getFiatShamirChallengeBits();
                $primaryShadowMixes->generateProofs();

                $mixModel = new Mix();
                $mixModel->round = 1;//is_null($previousMix) ? 1 : $previousMix->round + 1;
                $mixModel->trustee_id = $this->election->getTrusteeFromPeerServer(PeerServer::me(), true)->id;
                $mixModel->previous_mix_id = null; //$previousMix ? $previousMix->id : null;
                $mixModel->hash = $primaryShadowMixes->getHash();
                $mixModel->save();

                $primaryShadowMixes->store($mixModel->getFilename());


                // send mix to all
                $messagesToSend = $this->election->peerServers()->get()
                    ->map(function (PeerServer $trusteePeerServer) use ($mixModel, $meTrustee) {
                        return new ThisIsMyMixSetRequest(
                            PeerServer::me(),
                            $trusteePeerServer,
                            $mixModel
                        );
                    });

                if ($messagesToSend->count()) {
                    SendP2PMessage::dispatch($messagesToSend->toArray());
                }

            }
        }


    }
}
