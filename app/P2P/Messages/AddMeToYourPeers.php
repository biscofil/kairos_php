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
    private RSAPublicKey $pk;

    public function __construct(string $from, $to, RSAPublicKey $pk)
    {
        parent::__construct($from, $to);
        $this->senderJwtPk = $pk;
    }

    /**
     * This is the mesage sent to the new peer
     * @param string $to
     * @return array
     */
    public function getRequestData(string $to): array
    {
        $myKeyPair = getJwtRSAKeyPair();
        return [
            'my_jwt_public_key' => $myKeyPair->pk->toArray()
        ];
    }

    /**
     * @param string $sender
     * @param array $messageData
     * @return P2PMessage
     * @throws ValidationException
     * @throws \Exception
     */
    public static function fromRequest(string $sender, array $messageData): P2PMessage
    {
        $data = Validator::make($messageData, [
            'my_jwt_public_key' => ['required', 'array']
        ])->validate();

        $pk = RSAPublicKey::fromArray($data['my_jwt_public_key']);

        return new static(
            $sender,
            config('app.url'),
            $pk
        );
    }

    /**
     * The new peer registers sender and replies with its jwt public key
     * @return JsonResponse
     */
    public function onRequestReceived(): JsonResponse
    {

        $host = $this->from;

        Log::debug("AddMeToYourPeers message received");

        if (PeerServer::query()->where('ip', '=', $host)->count() == 0) {

            $peer = new PeerServer();
            $peer->ip = $host; // TODO resolve domain, store both IP and host
            $peer->name = "server " . $host;
            $peer->jwt_public_key = $this->senderJwtPk;
            $peer->fetchServerInfo();
            $peer->save();

            Log::debug("Host $host added as peer");
        }

        $myKeyPair = getJwtRSAKeyPair();
        return new JsonResponse([
            'jwt_public_key' => $myKeyPair->pk->toArray()
        ]);

    }

    /**
     * Once the peer has replied, we store its public key
     * @param string $destPeerServer
     * @param array $data
     */
    public function onResponseReceived(string $destPeerServer, array $data): void
    {
        // get the public key sent back from the peer
        $pk = RSAPublicKey::fromArray($data['jwt_public_key']);

        // update peer
        $server = PeerServer::withDomain($destPeerServer)->firstOrFail();
        $server->jwt_public_key = $pk;
        $server->save();

    }

}
