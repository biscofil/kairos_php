<?php


namespace App\P2P\Messages;


use App\Models\PeerServer;
use App\Voting\CryptoSystems\RSA\RSAPublicKey;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Class AddMeToYourPeers
 * @package App\P2P\Messages
 * @property RSAPublicKey $senderJwtPk
 */
class AddMeToYourPeers extends P2PMessage
{

    public const name = 'add_me_to_your_peers';
    public RSAPublicKey $senderJwtPk;

    /**
     * AddMeToYourPeers constructor.
     * @param \App\Models\PeerServer $from
     * @param PeerServer[] $to
     * @param \App\Voting\CryptoSystems\RSA\RSAPublicKey $pk
     * @throws \Exception
     */
    public function __construct(PeerServer $from, array $to, RSAPublicKey $pk)
    {
        parent::__construct($from, $to);
        $this->senderJwtPk = $pk;
    }

    /**
     * This is the mesage sent to the new peer
     * @param \App\Models\PeerServer $to
     * @return array
     */
    public function getRequestData(PeerServer $to): array
    {
        $myKeyPair = getJwtRSAKeyPair();
        return [
            'my_jwt_public_key' => $myKeyPair->pk->toArray()
        ];
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
            'my_jwt_public_key' => ['required', 'array']
        ])->validate();

        $pk = RSAPublicKey::fromArray($data['my_jwt_public_key']);

        return new static(
            $sender,
            [self::me()],
            $pk
        );
    }

    /**
     * The new peer registers sender and replies with its jwt public key
     * @return JsonResponse
     */
    public function onRequestReceived(): JsonResponse
    {

        Log::debug("AddMeToYourPeers message received");

        $this->from->jwt_public_key = $this->senderJwtPk;
        $this->from->fetchServerInfo();
        $this->from->save();

        Log::debug("Host {$this->from->ip} added as peer");

        $myKeyPair = getJwtRSAKeyPair();
        return new JsonResponse([
            'jwt_public_key' => $myKeyPair->pk->toArray()
        ]);

    }

    /**
     * Once the peer has replied, we store its public key
     * @param \App\Models\PeerServer $destPeerServer
     * @param \Illuminate\Http\Client\Response $response
     */
    public function onResponseReceived(PeerServer $destPeerServer, \Illuminate\Http\Client\Response $response): void
    {

        if ($response->status() >= 300) {
            return;
        }

        // get the public key sent back from the peer
        $pk = RSAPublicKey::fromArray($response->json('jwt_public_key'));

        // update peer
        $destPeerServer->fetchServerInfo();
        $destPeerServer->jwt_public_key = $pk;
        $destPeerServer->save();

    }

}
