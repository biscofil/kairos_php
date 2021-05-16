<?php


namespace App\P2P\Messages\Freeze\Freeze1IAmFreezingElection;


use App\Exceptions\NotYourElectionException;
use App\Jobs\RunP2PTask;
use App\Models\Election;
use App\Models\PeerServer;
use App\Models\Trustee;
use App\Models\User;
use App\P2P\Messages\Freeze\ThisIsMyThresholdBroadcast\ThisIsMyThresholdBroadcast;
use App\P2P\Messages\P2PMessageRequest;
use App\P2P\Tasks\GenerateAndSendShares;
use App\P2P\Tasks\SendAddMeToYourPeersMessageToUnknownPeers;
use App\Voting\CryptoSystems\ThresholdBroadcast;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use phpseclib3\Math\BigInteger;

/**
 * Describes the first message of the three-phase-commit procedure for election freeze
 * Class Freeze1IAmFreezingElectionRequest
 * @package App\P2P\Messages
 * @property Election $election
 * @property Trustee[] $trustees
 * @property null|ThresholdBroadcast $senderThresholdBroadcast
 * @property null|BigInteger $senderShare
 */
class Freeze1IAmFreezingElectionRequest extends P2PMessageRequest
{

    public Election $election;
    public array $trustees;
    public ?ThresholdBroadcast $senderThresholdBroadcast;
    public ?BigInteger $senderShare;

    /**
     * @return string
     */
    public static function getRequestMessageName(): string
    {
        return 'are_you_ready_for_election_freeze_request';
    }

    /**
     * IFrozeMyElection constructor.
     * @param \App\Models\PeerServer $requestSender
     * @param \App\Models\PeerServer $requestDestination
     * @param \App\Models\Election $election
     * @param \App\Models\Trustee[] $trustees
     * @param \App\Voting\CryptoSystems\ThresholdBroadcast|null $senderThresholdBroadcast
     * @param \phpseclib3\Math\BigInteger|null $senderShare
     * @throws \Exception
     */
    public function __construct(PeerServer $requestSender,
                                PeerServer $requestDestination,
                                Election $election,
                                array $trustees,
                                ?ThresholdBroadcast $senderThresholdBroadcast = null,
                                ?BigInteger $senderShare = null
    )
    {
        parent::__construct($requestSender, [$requestDestination]);
        $this->election = $election;
        $this->trustees = $trustees;
        $this->senderThresholdBroadcast = $senderThresholdBroadcast;
        $this->senderShare = $senderShare;
    }

    // #####################################################################

    /**
     * @param \App\Models\PeerServer $to
     * @return array
     * @throws \Exception
     */
    public function serialize(PeerServer $to): array
    {

        $electionData = $this->election->withoutRelations()->toShareableArray();
        $trusteeData = $this->trustees; // TODO

        /** @var \App\Models\Trustee $meTrustee */
        $meTrustee = $this->election->getTrusteeFromPeerServer(PeerServer::me());

        $broadcast = null;
        $share = null;

        if ($meTrustee) {
            // broadcast
            $broadcast = $meTrustee->broadcast;
            // share
            $senderTrustee = $this->election->getTrusteeFromPeerServer($to);
            $share = $senderTrustee->share_sent;
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
     * @return self
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Exception
     */
    public static function unserialize(PeerServer $sender, array $messageData): Freeze1IAmFreezingElectionRequest
    {
        // TODO validation

        $data = Validator::make($messageData, [
            'election' => ['required'],
            'election.uuid' => ['required', 'uuid', 'exists:elections,uuid'],
            'election.min_peer_count_t' => ['required', 'int'],

            'trustees' => ['required', 'array'],

            'broadcast' => [
                'nullable',
                Rule::requiredIf(function () use ($sender, $messageData) {
                    return $messageData['election']['min_peer_count_t'] > 0
                        && in_array($sender->domain, array_column($messageData['trustees'], 'domain'));
                })
            ],
            'share' => [
                'nullable',
                Rule::requiredIf(function () use ($sender, $messageData) {
                    return $messageData['election']['min_peer_count_t'] > 0
                        && in_array($sender->domain, array_column($messageData['trustees'], 'domain'));
                })
            ]
        ])->validate();

        // election
        $electionData = $data['election'];
        $election = Election::findFromUuid($electionData['uuid']);
        $election->fillFromSharedArray($electionData);

        // trustee data
        $trusteeData = $data['trustees'];

        // broadcast
        $broadcast = $data['broadcast'];
        if ($broadcast) {
            $thresholdBroadcastClass = $election->cryptosystem->getCryptoSystemClass()::getThresholdBroadcastClass();
            $broadcast = $thresholdBroadcastClass::fromArray($data['broadcast']); // RSA, ELGAMAL
        }

        // share
        $share = $data['share'];
        if ($share) {
            $share = new BigInteger($share, 16);
        }

        return new static(
            $sender,
            PeerServer::me(),
            $election,
            $trusteeData,
            $broadcast,
            $share);
    }

    // #####################################################################

    /**
     * @return \App\P2P\Messages\Freeze\Freeze1IAmFreezingElection\Freeze1IAmFreezingElectionResponse
     * @throws \Exception
     * @noinspection PhpIncompatibleReturnTypeInspection
     */
    public function onRequestReceived(): Freeze1IAmFreezingElectionResponse
    {
        Log::debug('Freezing election of another peer');

        if ($this->requestSender->id !== $this->election->peer_server_id) {
            throw new NotYourElectionException();
        }

        //
        $this->election->save();
        $trustees = self::getOrCreateTrustees($this->election, $this->trustees); // TODO check $trustees

        if ($senderTrustee = $this->election->getTrusteeFromPeerServer($this->requestSender)) {
            $senderTrustee->broadcast = $this->senderThresholdBroadcast;
            $senderTrustee->share_received = $this->senderShare;
            $senderTrustee->save();
        }


        $jobs = [
            // send a AddMeToYourPeers message to each unknown peer
            new RunP2PTask(new SendAddMeToYourPeersMessageToUnknownPeers($this->election)),
        ];

        $meTrustee = $this->election->getTrusteeFromPeerServer(PeerServer::me(), true);

        Log::debug('Generating my own keypair');
        $keyPair = $this->election->cryptosystem->getCryptoSystemClass()::generateKeypair();
        $meTrustee->private_key = $keyPair->sk;
        $meTrustee->public_key = $keyPair->pk;
        $publicKey = $keyPair->pk;

        if ($this->election->hasLLThresholdScheme()) {

            Log::debug('Freeze1IAmFreezingElection > no threshold');
            // no slack : l-l theshold

        } else {

            Log::debug('Freeze1IAmFreezingElection > min_peer_count_t is not 0 --> GenerateAndSendShares');

            Log::debug('Generating my own polynomial to send back');
            $meTrustee->polynomial = $meTrustee->private_key->getThresholdPolynomial($this->election->min_peer_count_t);

            Log::debug('Generating my own broadcast to send back');
            $meTrustee->broadcast = $meTrustee->polynomial->getBroadcast();

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

//        $broadcastToSendBack = null;
        $shareToSendBack = null;
        $freezeReady = false;

        if ($senderTrustee = $this->election->getTrusteeFromPeerServer($this->requestSender)) {
            // sender (coordinator) is a peer

            $senderIdx = $senderTrustee->getPeerServerIndex();
//            $broadcastToSendBack = $meTrustee->broadcast->toArray();
            $shareToSendBack = $meTrustee->polynomial->getShare($senderIdx);
            $senderTrustee->share_sent = $shareToSendBack;
            $senderTrustee->save();

            if (ThisIsMyThresholdBroadcast::areAllSharesReceived($this->election)) {
                /**
                 * Skip the second phase message, reuse the current one
                 */
                $freezeReady = true;
            }
        }

        // execute jobs in sequence
        Log::debug('Bus chain dispatch in 5 seconds');
        // wait for 5 seconds to allow everyone to generate its polynomial
        Bus::chain($jobs)->delay(5)->dispatch();

        return new Freeze1IAmFreezingElectionResponse(
            PeerServer::me(),
            $this->requestSender,
            $this->election,
            $publicKey,
            $meTrustee->broadcast,
            $shareToSendBack,
            $freezeReady
        );
    }

    /**
     * @return string
     */
    public static function getResponseClass(): string
    {
        return Freeze1IAmFreezingElectionResponse::class;
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
            if ($ex = Trustee::findUUID($t->uuid)) {
                //if exists, do not create it
                Log::debug("Freeze1IAmFreezingElectionResponse > Trustee with uuid $t->uuid exists, skipping creation");
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

}
