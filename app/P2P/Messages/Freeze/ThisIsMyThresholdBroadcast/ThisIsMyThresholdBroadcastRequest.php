<?php


namespace App\P2P\Messages\Freeze\ThisIsMyThresholdBroadcast;


use App\Models\Election;
use App\Models\PeerServer;
use App\P2P\Messages\P2PMessageRequest;
use App\Voting\CryptoSystems\ElGamal\EGThresholdBroadcast;
use App\Voting\CryptoSystems\PublicKey;
use App\Voting\CryptoSystems\ThresholdBroadcast;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use phpseclib3\Math\BigInteger;

/**
 * Class ThisIsMyThresholdBroadcastRequest
 * @package App\P2P\Messages\Freeze\ThisIsMyThresholdBroadcast
 * @property ThresholdBroadcast $broadcast
 * @property Election $election
 * @property BigInteger $share
 * @property PublicKey $publicKey
 */
class ThisIsMyThresholdBroadcastRequest extends P2PMessageRequest
{

    public BigInteger $share;
    public Election $election;
    public ThresholdBroadcast $broadcast;
    public PublicKey $publicKey;

    /**
     * @return string
     */
    public static function getRequestMessageName(): string
    {
        return 'this_is_my_threshold_broadcast_request';
    }

    public static function getResponseClass(): string
    {
        return ThisIsMyThresholdBroadcastResponse::class;
    }

    /**
     * ThisIsMyThresholdBroadcast constructor.
     * @param PeerServer $requestSender
     * @param PeerServer $requestDestination
     * @param Election $election
     * @param \App\Voting\CryptoSystems\PublicKey $publicKey
     * @param EGThresholdBroadcast $broadcast
     * @param BigInteger $share
     * @throws \Exception
     */
    public function __construct(PeerServer $requestSender, PeerServer $requestDestination, Election $election, PublicKey $publicKey, ThresholdBroadcast $broadcast, BigInteger $share)
    {
        parent::__construct($requestSender, [$requestDestination]);
        $this->election = $election;
        $this->broadcast = $broadcast;
        $this->share = $share;
        $this->publicKey = $publicKey;
    }

    /**
     * Executed by the server sending the ThisIsMyThresholdBroadcast request
     * @param \App\Models\PeerServer $to
     * @return array
     */
    public function serialize(PeerServer $to): array
    {
        return [
            'election_uuid' => $this->election->uuid,
            'public_key' => $this->publicKey->toArray(),
            'broadcast' => $this->broadcast->toArray(),
            'share' => $this->share->toHex()
        ];
    }

    /**
     * Executed by the server seceiving the ThisIsMyThresholdBroadcast request
     * @param PeerServer $sender
     * @param array $messageData
     * @return static
     * @throws \Exception
     */
    public static function unserialize(PeerServer $sender, array $messageData): P2PMessageRequest
    {
        $data = Validator::make($messageData, [
            'election_uuid' => ['required', 'uuid', 'exists:elections,uuid'],
            'public_key' => ['required', 'array'],
            'broadcast' => ['required', 'array'],
            'share' => ['required', 'string']
        ])->validate();

        Log::debug('received ThisIsMyThresholdBroadcast request');

        $election = Election::findFromUuid($data['election_uuid']);

        /** @var PublicKey $publicKeyClass */
        $publicKeyClass = $election->cryptosystem->getClass()::getPublicKeyClass();
        $publicKey = $publicKeyClass::fromArray($data['public_key']); // RSA, ELGAMAL

        /** @var ThresholdBroadcast $thresholdBroadcastClass */
        $thresholdBroadcastClass = $election->cryptosystem->getClass()::getThresholdBroadcastClass();
        $broadcast = $thresholdBroadcastClass::fromArray($data['broadcast']); // RSA, ELGAMAL

        $receivedShare = new BigInteger($data['share'], 16);

        return new static(
            $sender,
            PeerServer::me(),
            $election,
            $publicKey,
            $broadcast,
            $receivedShare
        );
    }

    // #######################################################################################
    // ##################################### RESPONSE ########################################
    // #######################################################################################

    /**
     * Code executed by server J when broadcast of server I arrives
     * @return ThisIsMyThresholdBroadcastResponse
     * @throws \Exception
     * @noinspection PhpIncompatibleReturnTypeInspection
     */
    public function onRequestReceived(): ThisIsMyThresholdBroadcastResponse
    {

        Log::debug('responding to ThisIsMyThresholdBroadcast request');

        // find sending trustee by peer server
        $trusteeI = $this->election->getTrusteeFromPeerServer($this->requestSender);
        if (!$trusteeI) {
            return new JsonResponse(['error' => 'trustee not found', 400]);
        }

        $meTrustee = $this->election->getTrusteeFromPeerServer(PeerServer::me(), true);
        // TODO what if same server is not a trustee?

        // TODO set polynomial
        Log::debug('Received broadcast : ' . $this->broadcast->toString());
//        Log::debug($this->broadcast->toArray());

        $j = $trusteeI->getPeerServerIndex();
        $trusteeI->broadcast = $this->broadcast;
        $trusteeI->share_received = $this->share;
        $trusteeI->public_key = $this->publicKey;
        $trusteeI->share_sent = $meTrustee->polynomial->getShare($j + 1); // TODO check +1
        $trusteeI->save();

        if (ThisIsMyThresholdBroadcast::areAllSharesReceived($this->election)) {
            ThisIsMyThresholdBroadcast::onAllSharesReceived($this->election);
        }

        return new ThisIsMyThresholdBroadcastResponse(
            PeerServer::me(),
            $this->requestSender,
            $meTrustee->broadcast,
            $meTrustee->public_key,
            $trusteeI->share_sent
        );

    }

}
