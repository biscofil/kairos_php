<?php


namespace App\P2P\Messages\AddMeToYourPeers;


use App\Models\PeerServer;
use App\P2P\Messages\P2PMessageResponse;
use App\Voting\CryptoSystems\RSA\RSAPublicKey;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Class AddMeToYourPeers
 * @package App\P2P\Messages
 * @property RSAPublicKey $requestDestinationJWTRSAPublicKey PK used for JWT by the current server
 * @property string $token token the peer we are sending the response to should use to communicate with us
 */
class AddMeToYourPeersResponse extends P2PMessageResponse
{

    public RSAPublicKey $requestDestinationJWTRSAPublicKey;
    public string $token;

    /**
     * AddMeToYourPeers constructor.
     * @param \App\Models\PeerServer $requestDestination (current server)
     * @param PeerServer $requestSender Server that sent the request we are responding to
     * @param RSAPublicKey $jwtRSApk PK used for JWT by the current server
     * @param string $token token the peer we are sending the response to should use to communicate with us
     * @throws \Exception
     */
    public function __construct(PeerServer $requestDestination, PeerServer $requestSender, RSAPublicKey $jwtRSApk, string $token)
    {
        parent::__construct($requestDestination, $requestSender);
        $this->requestDestinationJWTRSAPublicKey = $jwtRSApk;
        $this->token = $token;
    }

    // #########################################################################

    /**
     * The new peer registers sender and replies with its jwt public key
     * @return array
     */
    public function serialize(): array
    {
        return [
            'token' => $this->token,
            'jwt_public_key' => $this->requestDestinationJWTRSAPublicKey->toArray()
        ];
    }

    /**
     * @param PeerServer $requestDestination
     * @param array $messageData
     * @param \App\P2P\Messages\AddMeToYourPeers\AddMeToYourPeersRequest $requestMessage
     * @return self
     * @throws ValidationException
     * @throws \Exception
     */
    public static function unserialize(PeerServer $requestDestination, array $messageData, $requestMessage): self
    {
        $data = Validator::make($messageData, [
            'jwt_public_key' => ['required', 'array'],
            'token' => ['required'],
        ])->validate();

        // check if the claimed domain has in fact the used IP

        $pk = RSAPublicKey::fromArray($data['jwt_public_key']);

        return new static(
            $requestDestination,
            PeerServer::me(),
            $pk,
            $data['token'] // token the receiving server should use to communicate with the sender
        );
    }

    // #########################################################################

    /**
     * Once the peer has replied, we store its public key
     * @param \App\P2P\Messages\AddMeToYourPeers\AddMeToYourPeersRequest $request
     */
    public function onResponseReceived($request): void
    {
        // update peer
        $destPeerServer = $this->requestDestination;
        $destPeerServer->fetchServerInfo();
        $destPeerServer->token = $this->token;
        // get the public key sent back from the peer
        $destPeerServer->jwt_public_key = $this->requestDestinationJWTRSAPublicKey;
        $destPeerServer->save();
    }

}
