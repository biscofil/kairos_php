<?php


namespace App\P2P\Messages\Freeze\Freeze2IAmReadyForFreeze;


use App\Models\Election;
use App\Models\PeerServer;
use App\Models\Trustee;
use App\P2P\Messages\P2PMessageRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Describes the second message of the three-phase-commit procedure for election freeze
 * Class IAmReadyForElectionFreeze
 * @package App\P2P\Messages
 * @property Election $election
 * @property Trustee[] $trustees
 */
class Freeze2IAmReadyForFreezeRequest extends P2PMessageRequest
{

    public const TIMEOUT = 10; //seconds

    /**
     * @var \App\Models\Election
     */
    public Election $election;

    /**
     * @var \App\Models\Trustee[]
     */
    public array $trustees;

    /**
     * @return string
     */
    public static function getRequestMessageName(): string
    {
        return 'i_am_ready_for_election_freeze';
    }

    /**
     * IFrozeMyElection constructor.
     * @param \App\Models\PeerServer $requestSender
     * @param \App\Models\PeerServer $requestDestinations
     * @param \App\Models\Election $election
     * @param \App\Models\Trustee[] $trustees
     * @throws \Exception
     */
    public function __construct(PeerServer $requestSender, PeerServer $requestDestinations, Election $election, array $trustees)
    {
        parent::__construct($requestSender, [$requestDestinations]);
        $this->election = $election;
        $this->trustees = $trustees;
    }

    // ########################################################################

    /**
     * @param \App\Models\PeerServer $to
     * @return array
     */
    public function serialize(PeerServer $to): array
    {
        $electionData = $this->election->withoutRelations()->toShareableArray();

        $trusteeData = $this->trustees;

        return [
            'election' => $electionData, // TODO only uuid
            'trustees' => $trusteeData
        ];
    }

    /**
     * @param \App\Models\PeerServer $sender
     * @param array $messageData
     * @return \App\P2P\Messages\P2PMessageRequest
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Exception
     */
    public static function unserialize(PeerServer $sender, array $messageData): P2PMessageRequest
    {

        $data = Validator::make($messageData, [
            'election' => ['required'],
            'election.uuid' => ['required', 'uuid', 'exists:elections,uuid'],
            'trustees' => ['required', 'array'],
        ])->validate();

        $electionData = $messageData['election'];

        $election = Election::findFromUuid($electionData['uuid']);

        $trustees = $data['trustees'];

        return new static(
            $sender,
            getCurrentServer(),
            $election,
            $trustees
        );
    }

    // ########################################################################

    /**
     * @return \App\P2P\Messages\Freeze\Freeze2IAmReadyForFreeze\Freeze2IAmReadyForFreezeResponse
     * @noinspection PhpIncompatibleReturnTypeInspection
     * @throws \Exception
     */
    public function onRequestReceived(): Freeze2IAmReadyForFreezeResponse
    {

        // we are receiving a message from peer A containing the brodcast he received from B
        // if broadcast of A is invalid, add one warning to A
        // if broadcast of B is invalid, add one to B
        // TODO make B sign its broadcast
        // TODO store broadcast received by all and check
        // TODO if br
//        Log::debug($this->trustees);

        $senderTrustee = $this->election->getTrusteeFromPeerServer($this->requestSender, true);

        /** @var Trustee[]|\Illuminate\Support\Collection $_trustees */
        $_trustees = $this->election->trustees()->peerServers()->get()->keyBy('uuid');

        // TODO check broadcast
        $senderTrustee->freeze_ready = true;
        $senderTrustee->save();

        if ($this->election->hasLLThresholdScheme()) {

        } else {

            foreach ($this->trustees as $requestJsonTrustee) {

                if ($senderTrustee->uuid === $requestJsonTrustee['uuid']) {
                    // skip sender
                    continue;
                }

                Log::debug(" ####### checking the broadcast peer {$senderTrustee->uuid} received from peer " . $requestJsonTrustee['uuid']);

//                Log::debug($requestJsonTrustee);

                /** @var Trustee $_trustee */
                $_trustee = $_trustees->get($requestJsonTrustee['uuid']);

                $thresholdBroadcastClass = $this->election->cryptosystem->getClass()::getThresholdBroadcastClass();
                $broadcast = $thresholdBroadcastClass::fromArray($requestJsonTrustee['broadcast']); // RSA, ELGAMAL

                if ($_trustee->broadcast->equals($broadcast)) {
                    Log::debug(' > the broadcast just received and the store one do equal');
                } else {
                    Log::warning(' > the broadcast just received and the store one do NOT equal');

                    # received does not match!!!
                    Log::debug(' > just received broadcast of trustee ' . $requestJsonTrustee['uuid']);
                    Log::debug($broadcast->toArray());

                    Log::debug(' > stored broadcast ' . $requestJsonTrustee['uuid']);
                    Log::debug($_trustee->broadcast->toArray());
                }
            }

        }

        Log::debug('Freeze2IAmReadyForFreeze > checking if all peers are ready');
        if (Freeze2IAmReadyForFreeze::areAllPeersReady($this->election)) {
            // if all are ready
            Log::debug('Freeze2IAmReadyForFreeze > all peers are ready. Calling ThisIsMyThresholdBroadcast::onAllPeersReady');
            Freeze2IAmReadyForFreeze::onAllPeersReady($this->election);
        }

        return new Freeze2IAmReadyForFreezeResponse(getCurrentServer(), $this->requestSender);
    }

    /**
     * @return string
     */
    public static function getResponseClass(): string
    {
        return Freeze2IAmReadyForFreezeResponse::class;
    }

}
