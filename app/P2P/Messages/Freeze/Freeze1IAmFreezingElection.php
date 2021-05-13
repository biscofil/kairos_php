<?php


namespace App\P2P\Messages\Freeze;


use App\Jobs\OnElectionFreezeTimeout;
use App\Jobs\RunP2PTask;
use App\Models\Election;
use App\Models\PeerServer;
use App\Models\Trustee;
use App\Models\User;
use App\P2P\Messages\P2PMessage;
use App\P2P\Tasks\GenerateAndSendShares;
use App\P2P\Tasks\SendAddMeToYourPeersMessageToUnknownPeers;
use App\Voting\CryptoSystems\ThresholdBroadcast;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use phpseclib3\Math\BigInteger;

/**
 * Describes the first message of the three-phase-commit procedure for election freeze
 * Class AreYouReadyForElectionFreeze
 * @package App\P2P\Messages
 * @property Election $election
 * @property Trustee[] $trustees
 */
class Freeze1IAmFreezingElection extends P2PMessage
{

    // TODO if coordinator server acts as peer server too
    //  then the message should also carry the broadcast + share

    public const TIMEOUT = 10; //seconds

    /**
     * @var \App\Models\Election
     */
    public Election $election;

    /**
     * @var \App\Models\Trustee[]
     */
    public array $trustees;

    /**
     * @return string
     */
    public static function getMessageName(): string
    {
        return 'are_you_ready_for_election_freeze';
    }

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

        if ($this->election->min_peer_count_t > 0
            && $this->election->trustees()->where('peer_server_id', '=', PeerServer::meID)->count()) {

            // generate polynomial

            $meTrustee = $this->election->trustees()->where('peer_server_id', '=', PeerServer::meID)
                ->firstOrFail();

            $keyPair = $this->election->cryptosystem->getCryptoSystemClass()->generateKeypair();
            $meTrustee->private_key = $keyPair->sk;
            $meTrustee->public_key = $keyPair->pk;

            $polynomial = $keyPair->sk->getThresholdPolynomial($this->election->min_peer_count_t);
            $meTrustee->polynomial = $polynomial; // save my polynomial

            $meTrustee->save();

            // TODO move outside of P2P message class

        }

    }

    /**
     * @param \App\Models\PeerServer $to
     * @return array
     * @throws \Exception
     */
    public function getRequestData(PeerServer $to): array
    {
        $electionData = $this->election->withoutRelations()->toShareableArray();

        $trusteeData = $this->trustees; // TODO

        $broadcast = null;
        $share = null;

        if ($this->election->min_peer_count_t > 0
            && $this->election->trustees()->where('peer_server_id', '=', PeerServer::meID)->count()) {
            // if threshold and coordinator is also peer, send broadcast and share

            $trusteeI = $this->election->trustees()->where('peer_server_id', '=', $to->id)
                ->firstOrFail();

            /** @var \App\Models\Trustee $meTrustee */
            $meTrustee = $this->election->trustees()->where('peer_server_id', '=', PeerServer::meID)
                ->firstOrFail();

            $j = $trusteeI->getPeerServerIndex();

            $broadcast = $meTrustee->polynomial->getBroadcast();
            $share = $meTrustee->polynomial->getShare($j);

            $trusteeI->share_sent = $share;
            $trusteeI->save();
        }

        return [
            'election' => $electionData,
            'trustees' => $trusteeData,
            //
            'broadcast' => $broadcast ? $broadcast->toArray() : null,
            'share' => $share ? $share->toHex() : null,
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
            'election.min_peer_count_t' => ['required', 'int'],
            'trustees' => ['required', 'array'],

            'broadcast' => ['nullable', Rule::requiredIf(function () use ($sender, $messageData) {
                return $messageData['election']['min_peer_count_t'] > 0
                    && in_array($sender->domain, array_column($messageData['trustees'], 'domain'));
            })],
            'share' => ['nullable', Rule::requiredIf(function () use ($sender, $messageData) {
                return $messageData['election']['min_peer_count_t'] > 0
                    && in_array($sender->domain, array_column($messageData['trustees'], 'domain'));
            })]
        ])->validate();

        $electionData = $messageData['election'];

//        Log::debug($electionData);

        $election = Election::findFromUuid($electionData['uuid']);
        $election->fillFromSharedArray($electionData);
        $election->save();

        $trustees = self::getOrCreateTrustees($election, $data['trustees']);


        if ($senderTrustee = $election->trustees()->where('peer_server_id', '=', $sender->id)->first()) {

            /** @var ThresholdBroadcast $thresholdBroadcastClass */
            $thresholdBroadcastClass = $election->cryptosystem->getCryptoSystemClass()::ThresholdBroadcastClass;
            $sentBroadcast = $data['broadcast'];
            $sentBroadcast = $thresholdBroadcastClass::fromArray($sentBroadcast);
            $senderTrustee->broadcast = $sentBroadcast;

            $sentShare = new BigInteger($data['share'], 16);
            $senderTrustee->share_received = $sentShare;

            $senderTrustee->save();
        }

        return new static(
            $sender,
            [PeerServer::me()],
            $election,
            $trustees
        );
    }

    /**
     * Creates trustees (both users and peer servers) if not existing
     * @param \App\Models\Election $election
     * @param array $trustees
     * @return \App\Models\Trustee[]|array|null[]
     * @throws \Exception
     */
    private static function getOrCreateTrustees(Election $election, array $trustees): array
    {
        return array_map(function (array $trusteeData) use ($election) {

            // store
            $t = new Trustee();
            $t->fillFromSharedArray($trusteeData);
            if ($ex = Trustee::find(['uuid' => $t->uuid])) {
                //if exists, do not create it
                return $ex;
            }

            $t->election()->associate($election)->save();
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
        }, $trustees);
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     * @noinspection PhpIncompatibleReturnTypeInspection
     */
    public function getRequestResponse(): JsonResponse
    {
        Log::debug('Freezing election of another peer');

        if ($this->from->id !== $this->election->peer_server_id) {
            return new JsonResponse(['error' => 'not your election'], 400);
        }

//        $this->election->freeze();

        $publicKey = null;

        $jobs = [
            // send a AddMeToYourPeers message to each unknown peer
            new RunP2PTask(new SendAddMeToYourPeersMessageToUnknownPeers($this->election)),
        ];

        /** @var \App\Models\Trustee $meTrustee */
        $meTrustee = $this->election->trustees()
            ->where('peer_server_id', '=', PeerServer::meID)
            ->firstOrFail(); // TODO what if same server is not a trustee?

        Log::debug('Generating keypair');
        $keyPair = $this->election->cryptosystem->getCryptoSystemClass()->generateKeypair();
        $meTrustee->private_key = $keyPair->sk;
        $meTrustee->public_key = $keyPair->pk;

        if ($this->election->min_peer_count_t === 0) {

            Log::debug('Freeze1IAmFreezingElection > min_peer_count_t is 0 --> no GenerateAndSendShares');
            // no slack : l-l theshold

            $publicKey = json_encode($keyPair->pk->toArray());

        } else {

            Log::debug('Freeze1IAmFreezingElection > min_peer_count_t is not 0 --> GenerateAndSendShares');

            Log::debug('Generating polynomial');
            $polynomial = $keyPair->sk->getThresholdPolynomial($this->election->min_peer_count_t);
            $meTrustee->polynomial = $polynomial; // save my polynomial

            Log::debug('Generating broadcast');
            $broadcast = $meTrustee->polynomial->getBroadcast();
            $meTrustee->broadcast = $broadcast; // save my broadcast

            // t-l threshold
            $jobs[] = new RunP2PTask(new GenerateAndSendShares($this->election));

        }

        $meTrustee->save();

//        $jobs[] = new SendP2PMessage(new Freeze2IAmReadyForFreeze(
//            PeerServer::me(),
//            $this->from // send back to coordinator
//            // TODO
//        ));
        // TODO after all AddMeToYourPeers messages are done
        //  > request only to peers with higher "label" and expect requests from peers with lower "label"

        // execute jobs in sequence
        Log::debug('Bus chain dispatch in 2 seconds');
        // wait for 2 seconds to allow everyone to generate its polynomial
        Bus::chain($jobs)->delay(2)->dispatch();

        Log::debug('replying');

        $broadcastToSendBack = null;
        $shareToSendBack = null;

        if ($senderTrustee = $this->election->getTrusteeFromPeerServer($this->from)) {
            $senderIdx = $senderTrustee->getPeerServerIndex();
            $broadcastToSendBack = $meTrustee->broadcast->toArray();
            $shareToSendBack = $meTrustee->polynomial->getShare($senderIdx);
            $senderTrustee->share_sent = $shareToSendBack;
            $senderTrustee->save();
        }

        return new JsonResponse([
            'status' => 'freezing, I will send ready later on',
            'public_key' => $publicKey,
            'my_broadcast' => $broadcastToSendBack,
            'my_share' => $shareToSendBack
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
            Log::error(static::getMessageName() . ' > error');
            Log::debug($response->json());
            return;
        }

        Log::info(static::getMessageName() . ' > OK ');

        $trustee = $this->election->getTrusteeFromPeerServer($destPeerServer);
        if (!$trustee) {
            Log::error('Received positive confirmation from peer which is not a trustee for this election.');
            return;
        }

        if ($this->election->min_peer_count_t === 0) {
            $pkClass = $this->election->cryptosystem->getCryptoSystemClass()::PublicKeyClass;
            $public_key = $pkClass::fromArray(json_decode($response->json('public_key'), true));
            $trustee->setPublicKey($public_key);
            $trustee->save();

        } else {

            // store broadcast and share
            $thresholdBroadcastClass = $this->election->cryptosystem->getCryptoSystemClass()::ThresholdBroadcastClass;
            $broadCastSentBack = $response->json('my_broadcast');
            $broadCastSentBack = $thresholdBroadcastClass::fromArray($broadCastSentBack);
            $trustee->broadcast = $broadCastSentBack;

            $shareSentBack = $response->json('my_share');
            $shareSentBack = new BigInteger($shareSentBack, 16);
            $trustee->share_received = $shareSentBack;

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
