<?php


namespace App\P2P\Messages\Freeze\Freeze1IAmFreezingElection;


use App\Models\Election;
use App\Models\PeerServer;
use App\P2P\Messages\Freeze\Freeze2IAmReadyForFreeze\Freeze2IAmReadyForFreeze;
use App\P2P\Messages\P2PMessageResponse;
use App\Voting\CryptoSystems\PublicKey;
use App\Voting\CryptoSystems\ThresholdBroadcast;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use phpseclib3\Math\BigInteger;

/**
 * Describes the response of the first message of the three-phase-commit procedure for election freeze
 * Class Freeze1IAmFreezingElectionResponse
 * @package App\P2P\Messages
 * @property Election $election
 * @property \App\Voting\CryptoSystems\PublicKey|null $publicKey
 * @property \App\Voting\CryptoSystems\ThresholdBroadcast|null $broadcast
 * @property \phpseclib3\Math\BigInteger|null $share
 * @property bool $freezeReady
 */
class Freeze1IAmFreezingElectionResponse extends P2PMessageResponse
{

    public Election $election;
    public ?PublicKey $publicKey;
    public ?ThresholdBroadcast $broadcast;
    public ?BigInteger $share;
    public bool $freezeReady;

    /**
     * @param \App\Models\PeerServer $requestDestination
     * @param \App\Models\PeerServer $requestSender
     * @param \App\Models\Election $election
     * @param \App\Voting\CryptoSystems\PublicKey|null $publicKey
     * @param \App\Voting\CryptoSystems\ThresholdBroadcast|null $broadcast
     * @param \phpseclib3\Math\BigInteger|null $share
     * @param bool $freezeReady
     */
    public function __construct(PeerServer $requestDestination, PeerServer $requestSender,
                                Election $election,
                                ?PublicKey $publicKey,
                                ?ThresholdBroadcast $broadcast,
                                ?BigInteger $share,
                                bool $freezeReady)
    {
        parent::__construct($requestDestination, $requestSender);
        $this->election = $election;
        $this->publicKey = $publicKey;
        $this->broadcast = $broadcast;
        $this->share = $share;
        $this->freezeReady = $freezeReady;
    }

    // #############################################################

    /**
     * @return array
     * @throws \Exception
     * @noinspection PhpIncompatibleReturnTypeInspection
     */
    public function serialize(): array
    {
        return [
            'status' => 'freezing, I will send ready later on',
            'public_key' => $this->publicKey ? $this->publicKey->toArray() : null,
            'my_broadcast' => $this->broadcast ? $this->broadcast->toArray() : null,
            'my_share' => $this->share ? $this->share->toHex() : null,
            'freeze_ready' => $this->freezeReady
        ];
    }

    /**
     * @param \App\Models\PeerServer $requestDestination
     * @param array $messageData
     * @param \App\P2P\Messages\Freeze\Freeze1IAmFreezingElection\Freeze1IAmFreezingElectionRequest $requestMessage
     * @return self
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Exception
     */
    public static function unserialize(PeerServer $requestDestination, array $messageData, $requestMessage): Freeze1IAmFreezingElectionResponse
    {
        $expectingBroadcastShare = $requestMessage->election->min_peer_count_t > 0 // TODO check
            && $requestMessage->election->getTrusteeFromPeerServer($requestMessage->requestSender);

        $data = Validator::make($messageData, [
            'public_key' => ['nullable'], //  TODO required when
            'my_broadcast' => ['nullable', Rule::requiredIf($expectingBroadcastShare)],
            'my_share' => ['nullable', Rule::requiredIf($expectingBroadcastShare)],
            'freeze_ready' => ['required', 'bool']
        ])->validate();

        // get election from request
        $election = $requestMessage->election;

        // public key without threshold
        $publicKey = null;
        if ($data['public_key']) {
            $pkClass = $election->cryptosystem->getClass()::getPublicKeyClass();
            $publicKey = $pkClass::fromArray($data['public_key']);
        }

        // broadcast
        $broadcast = null;
        if ($data['my_broadcast']) {
            $thresholdBroadcastClass = $election->cryptosystem->getClass()::getThresholdBroadcastClass();
            $broadcast = $thresholdBroadcastClass::fromArray($data['my_broadcast']); // RSA, ELGAMAL
        }

        // share
        $receivedShare = null;
        if ($data['my_share']) {
            $receivedShare = new BigInteger($data['my_share'], 16);
        }

        $freeze_ready = boolval($data['freeze_ready']);

        return new static(
            $requestDestination,
            PeerServer::me(),
            $election,
            $publicKey,
            $broadcast,
            $receivedShare,
            $freeze_ready
        );
    }

    // #############################################################

    /**
     * @param \App\Models\PeerServer $destPeerServer
     * @param \App\P2P\Messages\Freeze\Freeze1IAmFreezingElection\Freeze1IAmFreezingElectionRequest $request
     * @throws \Exception
     */
    public function onResponseReceived(PeerServer $destPeerServer, $request): void
    {
        $meTrustee = $this->election->getTrusteeFromPeerServer(PeerServer::me());
        $trustee = $this->election->getTrusteeFromPeerServer($destPeerServer, true);

        $trustee->setPublicKey($this->publicKey);

        if ($this->election->hasLLThresholdScheme()) {
            Log::debug('Freeze1IAmFreezingElectionResponse::onResponseReceived > no threshold');
        } else {
            Log::debug('Freeze1IAmFreezingElectionResponse::onResponseReceived > threshold');
            $trustee->broadcast = $this->broadcast;
            if ($meTrustee) { // only if the current server (creator, coordinator) is also peer TODO check
                // store broadcast and share
                $trustee->share_received = $this->share;
            }
            $trustee->freeze_ready = $this->freezeReady;
        }

        $trustee->save();

        Log::debug('Freeze1IAmFreezingElection > checking if all peers are ready');
        if (Freeze2IAmReadyForFreeze::areAllPeersReady($this->election)) {
            // if all are ready
            Log::debug('Freeze1IAmFreezingElection > all peers are ready. Calling ThisIsMyThresholdBroadcast::onAllPeersReady');
            Freeze2IAmReadyForFreeze::onAllPeersReady($this->election);
        }

    }

    // #############################################################


}
