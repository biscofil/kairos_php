<?php


namespace App\P2P\Messages;


use App\Jobs\OnElectionFreezeTimeout;
use App\Jobs\RunP2PTask;
use App\Models\Election;
use App\Models\PeerServer;
use App\Models\Trustee;
use App\Models\User;
use App\P2P\Tasks\PostFreezeHandshakes;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Class IFrozeMyElection
 * @package App\P2P\Messages
 * @property Election $election
 * @property Trustee[] $trustees
 */
class IFrozeMyElection extends P2PMessage
{

    public const TIMEOUT = 10; //seconds

    public const name = 'i_froze_my_election';

    /**
     * @var \App\Models\Election
     */
    public Election $election;

    /**
     * @var \App\Models\Trustee[]
     */
    public array $trustees;

    /**
     * IFrozeMyElection constructor.
     * @param \App\Models\PeerServer $from
     * @param array $to
     * @param \App\Models\Election $election
     * @param \App\Models\Trustee[] $trustees
     * @throws \Exception
     */
    public function __construct(PeerServer $from, array $to, Election $election, array $trustees)
    {
        parent::__construct($from, $to);
        $this->election = $election;
        $this->trustees = $trustees;
    }

    /**
     * @param \App\Models\PeerServer $to
     * @return array
     */
    public function getRequestData(PeerServer $to): array
    {
        $electionData = $this->election->withoutRelations()->toShareableArray();

        $trusteeData = $this->trustees; // TODO

        return [
            'election' => $electionData,
            'trustees' => $trusteeData
        ];
    }

    /**
     * @param \App\Models\PeerServer $sender
     * @param array $messageData
     * @return \App\P2P\Messages\P2PMessage
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Exception
     */
    public static function fromRequest(PeerServer $sender, array $messageData): P2PMessage
    {

        $data = Validator::make($messageData, [
            'election' => ['required'],
            'election.uuid' => ['required', 'uuid', 'exists:elections,uuid'],
            'trustees' => ['required', 'array'],
        ])->validate();

        $electionData = $messageData['election'];

//        Log::debug($electionData);

        $election = Election::findFromUuid($electionData['uuid']);
        $election->fillFromSharedArray($electionData);

        $trustees = array_map(function (array $trusteeData) {

            // store
            $t = new Trustee();
            $t->fillFromSharedArray($trusteeData);
            $t->save();
            if (!is_null($trusteeData['peer_server'])) {
                $p = new PeerServer();
                $p->fillFromSharedArray($trusteeData['peer_server']);
                if ($knownPeer = PeerServer::withDomain($p->domain)->first()) {
                    $p = $knownPeer; // use existing if domain is known
                }
                $t->peerServer()->associate($p)->save();
            } elseif (!is_null($trusteeData['user'])) {
                $u = new User();
                $u->fillFromSharedArray($trusteeData['user']);
                $t->user()->associate($u)->save();
            } else {
                throw new \Exception("peer_server_id and user_id can't both be null");
            }
            $t->save();
            return $t;
        }, $data['trustees']);

        return new static(
            $sender,
            [PeerServer::me()],
            $election,
            $trustees
        );
    }

    /**
     * @return
     * @throws \Exception
     * @noinspection PhpIncompatibleReturnTypeInspection
     */
    public function getRequestResponse()
    {
        Log::debug('Freezing election of another peer');

        if ($this->from->id !== $this->election->peer_server_id) {
            return new JsonResponse(['error' => 'not your election'], 400);
        }

        $this->election->freeze();

        $publicKey = null;

        if ($this->election->min_peer_count_t === 0) {
            // no slack : l-l theshold

            $keyPair = $this->election->cryptosystem->getCryptoSystemClass()->generateKeypair();
            $keyPair->storeToFile('election_' . $this->election->id . '.keypair.json');
            $publicKey = json_encode($keyPair->pk->toArray());

        } else {

            // t-l threshold
            // TODO

        }

        RunP2PTask::dispatch(new PostFreezeHandshakes(
            $this->from,
            $this->to,
            $this->election
        ));

        return new JsonResponse([
            'status' => 'election frozen',
            'public_key' => $publicKey
        ]);
    }

    /**
     * @param \App\Models\PeerServer $destPeerServer
     * @param \GuzzleHttp\Promise\PromiseInterface|\Illuminate\Http\Client\Response $response
     * @throws \Exception
     */
    protected function onResponseReceived(PeerServer $destPeerServer, $response): void
    {

        if (!$response->ok()) {
            Log::error(self::name . ' > error');
            Log::debug($response->json());
            return;
        }

        Log::info(self::name . ' > OK ');

        if ($this->election->min_peer_count_t === 0) {

            $trustee = $this->election->getTrusteeFromPeerServer($destPeerServer);
            if (!$trustee) {
                Log::error('Received positive confirmation from peer which is not a trustee for this election.');
                return;
            }

            $pkClass = $this->election->cryptosystem->getCryptoSystemClass()::PublicKeyClass;
            $public_key = $pkClass::fromArray(json_decode($response->json('public_key'), true));
            $trustee->setPublicKey($public_key);
            $trustee->save();

        }

    }

    /**
     *
     */
    protected function afterMessagesSent()
    {
        // wait for TIMEOUT seconds for a confirmation
        OnElectionFreezeTimeout::dispatch($this->election)
            ->delay(now()->addSeconds(self::TIMEOUT));
    }
}
