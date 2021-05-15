<?php


namespace App\P2P\Messages\Freeze\ThisIsMyThresholdBroadcast;


use App\Models\PeerServer;
use App\P2P\Messages\P2PMessageRequest;
use App\P2P\Messages\P2PMessageResponse;
use App\Voting\CryptoSystems\ThresholdBroadcast;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use phpseclib3\Math\BigInteger;

/**
 * Class ThisIsMyThresholdBroadcastResponse
 * @package App\P2P\Messages\Freeze\ThisIsMyThresholdBroadcast
 * @property ThresholdBroadcast $broadcast
 * @property  BigInteger $share
 */
class ThisIsMyThresholdBroadcastResponse extends P2PMessageResponse
{

    public ThresholdBroadcast $broadcast;
    public BigInteger $share;

    /**
     * ThisIsMyThresholdBroadcastResponse constructor.
     * @param \App\Models\PeerServer $requestDestination
     * @param \App\Models\PeerServer $requestSender
     * @param \App\Voting\CryptoSystems\ThresholdBroadcast $broadcast
     * @param \phpseclib3\Math\BigInteger $share
     */
    public function __construct(PeerServer $requestDestination, PeerServer $requestSender,
                                ThresholdBroadcast $broadcast, BigInteger $share)
    {
        parent::__construct($requestDestination, $requestSender);
        $this->broadcast = $broadcast;
        $this->share = $share;
    }

    /**
     * @return array
     */
    public function serialize(): array
    {
        return [
            'my_broadcast' => $this->broadcast->toArray(),
            'my_share' => $this->share->toHex()
        ];
    }

    /**
     * @param \App\Models\PeerServer $requestDestination
     * @param array $messageData
     * @param \App\P2P\Messages\Freeze\ThisIsMyThresholdBroadcast\ThisIsMyThresholdBroadcastRequest $requestMessage
     * @return \App\P2P\Messages\P2PMessageResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public static function unserialize(PeerServer $requestDestination, array $messageData, $requestMessage): P2PMessageResponse
    {

        $data = Validator::make($messageData, [
            'my_broadcast' => ['required'],
            'my_share' =>  ['required'],
        ])->validate();

        // broadcast
        $thresholdBroadcastClass = $requestMessage->election->cryptosystem->getCryptoSystemClass()::ThresholdBroadcastClass;
        $broadcast = $thresholdBroadcastClass::fromArray($data['my_broadcast']); // RSA, ELGAMAL

        // share
        $receivedShare = new BigInteger($data['my_share'], 16);

        return new static(
            $requestDestination,
            PeerServer::me(),
            $broadcast,
            $receivedShare
        );
    }

    /**
     * @param \App\Models\PeerServer $destPeerServer
     * @param \App\P2P\Messages\Freeze\ThisIsMyThresholdBroadcast\ThisIsMyThresholdBroadcastRequest $request
     * @throws \Exception
     */
    public function onResponseReceived(PeerServer $destPeerServer, $request): void
    {

        Log::debug('ThisIsMyThresholdBroadcast response received');

        $trustee = $request->election->getTrusteeFromPeerServer($this->requestDestination, true);

        // save received broadcast, share
        Log::debug('share received: ' . $this->share->toHex());

        /** @var ThresholdBroadcast $thresholdBroadcastClass */
        Log::debug('broadcast received: ' . $this->broadcast->toString());

        $trustee->broadcast = $this->broadcast;
        $trustee->share_received = $this->share;
        $trustee->save();

        if (ThisIsMyThresholdBroadcast::areAllSharesReceived($request->election)) {
            ThisIsMyThresholdBroadcast::onAllSharesReceived($request->election);
        }

    }
}
