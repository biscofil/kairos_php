<?php


namespace App\P2P\Messages;


use App\Models\PeerServer;
use App\Rules\SenderDomainMatchesRequestIP;
use App\Voting\CryptoSystems\RSA\RSAPublicKey;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Class AddMeToYourPeers
 * @package App\P2P\Messages
 * @property RSAPublicKey $senderJwtPk
 * @property string $token
 */
class AddMeToYourPeers extends P2PMessage
{

    public const name = 'add_me_to_your_peers';
    public RSAPublicKey $senderJwtPk;
    public string $token;

    /**
     * AddMeToYourPeers constructor.
     * @param \App\Models\PeerServer $from
     * @param PeerServer[] $to
     * @param \App\Voting\CryptoSystems\RSA\RSAPublicKey $pk
     * @param string $token
     * @throws \Exception
     */
    public function __construct(PeerServer $from, array $to, RSAPublicKey $pk, string $token)
    {
        parent::__construct($from, $to);
        $this->senderJwtPk = $pk;
        $this->token = $token;
    }

    /**
     * This is the mesage sent to the new peer
     * @param \App\Models\PeerServer $to
     * @return array
     */
    public function getRequestData(PeerServer $to): array
    {
        $myKeyPair = getJwtRSAKeyPair();
        /** @noinspection PhpVoidFunctionResultUsedInspection */
        return [
            'my_jwt_public_key' => $myKeyPair->pk->toArray(),
            'token' => $this->token,
            'sender_domain' => $this->from->domain // the domain of the current server, sending the request
        ];
    }

    /**
     * Handles authentication for AddMeToYourPeers: create a new
     * @param \Illuminate\Http\Request $request
     * @return \App\Models\PeerServer
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

    /**
     * @param PeerServer $sender
     * @param array $messageData
     * @return P2PMessage
     * @throws ValidationException
     * @throws \Exception
     */
    public static function fromRequest(PeerServer $sender, array $messageData): P2PMessage
    {
        $data = Validator::make($messageData, [
            'my_jwt_public_key' => ['required', 'array'],
            'token' => ['required'],
        ])->validate();

        // check if the claimed domain has in fact the used IP

        $pk = RSAPublicKey::fromArray($data['my_jwt_public_key']);

        return new static(
            $sender,
            [PeerServer::me()],
            $pk,
            $data['token'] // token the receiving server should use to communicate with the sender
        );
    }

    /**
     * The new peer registers sender and replies with its jwt public key
     * @return
     */
    public function getRequestResponse()
    {

        Log::debug('AddMeToYourPeers message received');

        $this->from->jwt_public_key = $this->senderJwtPk;
        $this->from->token = $this->token; // set the token the current server will use to communicate with {$this->from}
        $this->from->save();

        Log::debug("Host {$this->from->domain} added as peer");

        $myKeyPair = getJwtRSAKeyPair();

        $out = [
            'token' => $this->from->getNewToken(), // Get the token
            'jwt_public_key' => $myKeyPair->pk->toArray()
        ];

        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return new JsonResponse($out);

    }

    /**
     * Once the peer has replied, we store its public key
     * @param \App\Models\PeerServer $destPeerServer
     * @param \GuzzleHttp\Promise\PromiseInterface|\Illuminate\Http\Client\Response $response
     */
    protected function onResponseReceived(PeerServer $destPeerServer, $response): void
    {

        if (!$response->ok()) {
            Log::error($response->json());
            return;
        }

        $responseJson = $response->json();

        // get the public key sent back from the peer
        $pk = RSAPublicKey::fromArray($responseJson['jwt_public_key']);

        // update peer
        $destPeerServer->fetchServerInfo();
        $destPeerServer->token = $responseJson['token'];
        $destPeerServer->jwt_public_key = $pk;
        $destPeerServer->save();

    }

}
