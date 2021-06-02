<?php


namespace App\P2P\Messages\AddMeToYourPeers;


use App\Models\PeerServer;
use App\P2P\Messages\P2PMessageRequest;
use App\Rules\SenderDomainMatchesRequestIP;
use App\Voting\CryptoSystems\RSA\RSAPublicKey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Class AddMeToYourPeers
 * @package App\P2P\Messages
 * @property RSAPublicKey $senderJwtPk
 * @property string $token token that the peer we are sending the message to should use to communicate with us
 */
class AddMeToYourPeersRequest extends P2PMessageRequest
{

    public RSAPublicKey $senderJwtPk;
    public string $token;

    /**
     * @return string
     */
    public static function getRequestMessageName(): string
    {
        return 'add_me_to_your_peers_request';
    }

    /**
     * @return string
     */
    public static function getResponseClass(): string
    {
        return AddMeToYourPeersResponse::class;
    }

    /**
     * AddMeToYourPeers constructor.
     * @param \App\Models\PeerServer $requestSender
     * @param PeerServer $requestDestinations Single destination server
     * @param \App\Voting\CryptoSystems\RSA\RSAPublicKey $pk
     * @param string $token token that the peer we are sending the message to should use to communicate with us
     * @throws \Exception
     */
    public function __construct(PeerServer $requestSender, PeerServer $requestDestinations, RSAPublicKey $pk, string $token)
    {
        parent::__construct($requestSender, [$requestDestinations]);
        $this->senderJwtPk = $pk;
        $this->token = $token;
    }

    // ###########################################################################################

    /**
     * This is the mesage sent to the new peer
     * @param \App\Models\PeerServer $to
     * @return array
     */
    public function serialize(PeerServer $to): array
    {
        /** @noinspection PhpVoidFunctionResultUsedInspection */
        return [
            'my_jwt_public_key' => getCurrentServer()->jwt_public_key->toArray(),
            'token' => $this->token,
            /**
             * sender_domain is used by @see AddMeToYourPeersRequest::getAuthPeer()
             */
            'sender_domain' => $this->requestSender->domain // the domain of the current server, sending the request
        ];
    }

    /**
     * @param PeerServer $sender
     * @param array $messageData
     * @return P2PMessageRequest
     * @throws ValidationException
     * @throws \Exception
     */
    public static function unserialize(PeerServer $sender, array $messageData): P2PMessageRequest
    {
        $data = Validator::make($messageData, [
            'my_jwt_public_key' => ['required', 'array'],
            'token' => ['required'],
        ])->validate();

        // check if the claimed domain has in fact the used IP

        $pk = RSAPublicKey::fromArray($data['my_jwt_public_key']);

        return new static(
            $sender,
            getCurrentServer(),
            $pk,
            $data['token'] // token the receiving server should use to communicate with the sender
        );
    }

    // ###########################################################################################

    /**
     * @throws \Exception
     */
    public function onRequestReceived(): AddMeToYourPeersResponse
    {

        Log::debug('AddMeToYourPeers request received');

        // store values sent by the request sender
        $this->requestSender->jwt_public_key = $this->senderJwtPk;
        $this->requestSender->token = $this->token; // set the token the current server will use to communicate with the request sender
        $this->requestSender->save();

        Log::debug("Host {$this->requestSender->domain} added as peer");

        // prepare values to send back
        $tokenForRequestSender = $this->requestSender->getNewToken();

        return new AddMeToYourPeersResponse(
            getCurrentServer(),
            $this->requestSender,
            getCurrentServer()->jwt_public_key,
            $tokenForRequestSender
        );
    }

    /**
     * Handles authentication for AddMeToYourPeers: create a new
     * @param \Illuminate\Http\Request $request
     * @return \App\Models\PeerServer
     * @noinspection PhpMissingParentCallCommonInspection
     */
    public static function getAuthPeer(Request $request): PeerServer
    {

        $data = $request->validate([
            // check if the claimed domain has in fact the used IP
            'sender_domain' => ['required', new SenderDomainMatchesRequestIP($request->ip())]  // TODO validate active domain
        ]);

        $domain = $data['sender_domain'];
        if ($peer = PeerServer::withDomain($domain)->first()) {
            return $peer;
        }

        /**
         * @see \App\P2P\Messages\AddMeToYourPeers::fromRequest()
         */
        return PeerServer::newPeerServer($domain);

    }

}
