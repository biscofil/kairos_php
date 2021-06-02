<?php


namespace App\P2P\Messages\Freeze\ThisIsMyThresholdBroadcast;


use App\Models\PeerServer;
use App\P2P\Messages\P2PMessageResponse;
use App\Voting\CryptoSystems\PublicKey;
use App\Voting\CryptoSystems\ThresholdBroadcast;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use phpseclib3\Math\BigInteger;

/**
 * Class ThisIsMyThresholdBroadcastResponse
 * @package App\P2P\Messages\Freeze\ThisIsMyThresholdBroadcast
 * @property ThresholdBroadcast $broadcast
 * @property BigInteger $share
 * @property \App\Voting\CryptoSystems\PublicKey $publicKey
 */
class ThisIsMyThresholdBroadcastResponse extends P2PMessageResponse
{

    public ThresholdBroadcast $broadcast;
    public BigInteger $share;
    public PublicKey $publicKey;

    /**
     * ThisIsMyThresholdBroadcastResponse constructor.
     * @param \App\Models\PeerServer $requestDestination
     * @param \App\Models\PeerServer $requestSender
     * @param \App\Voting\CryptoSystems\ThresholdBroadcast $broadcast
     * @param \App\Voting\CryptoSystems\PublicKey $publicKey
     * @param \phpseclib3\Math\BigInteger $share
     */
    public function __construct(PeerServer $requestDestination, PeerServer $requestSender,
                                ThresholdBroadcast $broadcast, PublicKey $publicKey,
                                BigInteger $share)
    {
        parent::__construct($requestDestination, $requestSender);
        $this->broadcast = $broadcast;
        $this->share = $share;
        $this->publicKey = $publicKey;
    }

    /**
     * @return array
     */
    public function serialize(): array
    {
        return [
            'my_broadcast' => $this->broadcast->toArray(),
            'my_public_key' => $this->publicKey->toArray(),
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
            'my_public_key' => ['required', 'array'],
            'my_share' => ['required'],
        ])->validate();


        /** @var PublicKey $publicKeyClass */
        $publicKeyClass = $requestMessage->election->cryptosystem->getClass()::getPublicKeyClass();
        $publicKey = $publicKeyClass::fromArray($data['my_public_key']); // RSA, ELGAMAL

        // broadcast
        /** @var ThresholdBroadcast $thresholdBroadcastClass */
        $thresholdBroadcastClass = $requestMessage->election->cryptosystem->getClass()::getThresholdBroadcastClass();
        $broadcast = $thresholdBroadcastClass::fromArray($data['my_broadcast']); // RSA, ELGAMAL

        // share
        $receivedShare = new BigInteger($data['my_share'], 16);

        return new static(
            $requestDestination,
            getCurrentServer(),
            $broadcast,
            $publicKey,
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
        $trustee->public_key = $this->publicKey;
        $trustee->save();

        if (ThisIsMyThresholdBroadcast::areAllSharesReceived($request->election)) {
            ThisIsMyThresholdBroadcast::onAllSharesReceived($request->election);
        }

    }
}
