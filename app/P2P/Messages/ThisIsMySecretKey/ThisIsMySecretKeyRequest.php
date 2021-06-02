<?php


namespace App\P2P\Messages\ThisIsMySecretKey;


use App\Models\Election;
use App\Models\PeerServer;
use App\P2P\Messages\P2PMessageRequest;
use App\P2P\Messages\P2PMessageResponse;
use App\Voting\AnonymizationMethods\MixNets\ReEncryption\ReEncryptingMixNode;
use App\Voting\CryptoSystems\SecretKey;
use Illuminate\Support\Facades\Validator;

/**
 * Class ThisIsMySecretKeyRequest
 * @package App\P2P\Messages\ThisIsMySecretKey
 * Used by Re-Encryption mixnets
 * @property \App\Voting\CryptoSystems\SecretKey secretKey
 * @property \App\Models\Election election
 */
class ThisIsMySecretKeyRequest extends P2PMessageRequest
{
    public SecretKey $secretKey;
    public Election $election;

    /**
     * ThisIsMySecretKeyRequest constructor.
     * @param \App\Models\PeerServer $requestSender
     * @param \App\Models\PeerServer $requestDestination
     * @param \App\Models\Election $election
     * @param \App\Voting\CryptoSystems\SecretKey $secretKey
     * @throws \Exception
     */
    public function __construct(PeerServer $requestSender, PeerServer $requestDestination, Election $election, SecretKey $secretKey)
    {
        parent::__construct($requestSender, [$requestDestination]);
        $this->election = $election;
        $this->secretKey = $secretKey;
    }

    /**
     * @return string
     */
    public static function getRequestMessageName(): string
    {
        return 'this_is_my_secret_key_request';
    }

    /**
     * @param \App\Models\PeerServer $to
     * @return array
     */
    public function serialize(PeerServer $to): array
    {
        return [
            'uuid' => $this->election->uuid,
            'secret_key' => $this->secretKey->toArray()
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
            'uuid' => ['required', 'uuid'],
            'secret_key' => ['required', 'array'],
        ])->validate();

        $election = Election::findFromUuid($data['uuid']);

        $SecretKey = $data['secret_key'];
        $secretKeyClass = $election->cryptosystem->getClass()::getSecretKeyClass();
        $SecretKey = $secretKeyClass::fromArray($SecretKey); // RSA, ELGAMAL

        return new static(
            $sender,
            getCurrentServer(),
            $election,
            $SecretKey
        );
    }

    /**
     * @return \App\P2P\Messages\P2PMessageResponse
     */
    public function onRequestReceived(): P2PMessageResponse
    {

        // save received secret key
        /** @var \App\Models\Trustee $trustee */
        $trustee = $this->election->getTrusteeFromPeerServer($this->requestSender, true);
        $trustee->private_key = $this->secretKey;
        $trustee->save();

        ReEncryptingMixNode::onSecretKeyReceived($this->election, $trustee);

        return new ThisIsMySecretKeyResponse(getCurrentServer(), $this->requestSender);
    }

    /**
     * @return string
     */
    public static function getResponseClass(): string
    {
        return ThisIsMySecretKeyResponse::class;
    }
}
