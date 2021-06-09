<?php


namespace App\P2P\Messages\Freeze\Freeze1IAmFreezingElection;


use App\Exceptions\NotYourElectionException;
use App\Jobs\RunP2PTask;
use App\Models\Answer;
use App\Models\Election;
use App\Models\PeerServer;
use App\Models\Question;
use App\Models\Trustee;
use App\Models\User;
use App\P2P\Messages\Freeze\ThisIsMyThresholdBroadcast\ThisIsMyThresholdBroadcast;
use App\P2P\Messages\P2PMessageRequest;
use App\P2P\Tasks\GenerateAndSendShares;
use App\P2P\Tasks\SendAddMeToYourPeersMessageToUnknownPeers;
use App\Voting\CryptoSystems\PublicKey;
use App\Voting\CryptoSystems\ThresholdBroadcast;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use phpseclib3\Math\BigInteger;

/**
 * Describes the first message of the three-phase-commit procedure for election freeze
 * Class Freeze1IAmFreezingElectionRequest
 * @package App\P2P\Messages
 * @property Election election
 * @property Collection|Question[] questions
 * @property Collection|Trustee[] trustees
 * @property null|ThresholdBroadcast senderThresholdBroadcast
 * @property null|BigInteger senderShare
 * @property null|PublicKey senderPublicKey
 */
class Freeze1IAmFreezingElectionRequest extends P2PMessageRequest
{

    public Election $election;
    public Collection $trustees;
    public ?ThresholdBroadcast $senderThresholdBroadcast;
    public ?BigInteger $senderShare;
    public ?PublicKey $senderPublicKey;
    public Collection $questions;

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
     * @param Collection|Question[] $questions
     * @param Collection|Trustee[] $trustees
     * @param \App\Voting\CryptoSystems\PublicKey|null $senderPublicKey
     * @param \App\Voting\CryptoSystems\ThresholdBroadcast|null $senderThresholdBroadcast
     * @param \phpseclib3\Math\BigInteger|null $senderShare
     * @throws \Exception
     */
    public function __construct(PeerServer $requestSender,
                                PeerServer $requestDestination,
                                Election $election,
                                Collection $questions,
                                Collection $trustees,
                                ?PublicKey $senderPublicKey,
                                ?ThresholdBroadcast $senderThresholdBroadcast = null,
                                ?BigInteger $senderShare = null
    )
    {
        parent::__construct($requestSender, [$requestDestination]);
        $this->election = $election;
        $this->trustees = $trustees;
        $this->senderThresholdBroadcast = $senderThresholdBroadcast;
        $this->senderShare = $senderShare;
        $this->senderPublicKey = $senderPublicKey;
        $this->questions = $questions;
    }

    // #####################################################################

    /**
     * @param \App\Models\PeerServer $to
     * @return array
     * @throws \Exception
     */
    public function serialize(PeerServer $to): array
    {
        return [
            'election' => $this->election->withoutRelations()->toShareableArray(),
            'questions' => $this->questions->map(function (Question $question) {
                return [
                    '_question' => $question->toShareableArray(),
                    '_answers' => $question->answers->map(function (Answer $answer) {
                        return $answer->toShareableArray();
                    })->toArray()
                ];
            })->toArray(),
            'trustees' => $this->trustees->map(function (Trustee $trustee) {
                $out = $trustee->toShareableArray();
                $out['peer_server'] = $trustee->peerServer ? $trustee->peerServer->toShareableArray() : null;
                $out['user'] = $trustee->user ? $trustee->user->toShareableArray() : null;
                return $out;
            })->toArray(),
            //
            'public_key' => $this->senderPublicKey ? $this->senderPublicKey->toArray() : null,
            'broadcast' => $this->senderThresholdBroadcast ? $this->senderThresholdBroadcast->toArray() : null,
            'share' => $this->senderShare ? $this->senderShare->toHex() : null,
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
        $required = $messageData['election']['min_peer_count_t'] > 0
            && in_array($sender->domain, array_column($messageData['trustees'], 'domain')); // TODO change to < peer count!!

        // TODO store questions

        $data = Validator::make($messageData, [
            'election' => ['required'],
            'election.uuid' => ['required', 'uuid', 'exists:elections,uuid'],
            'election.min_peer_count_t' => ['required', 'int'],

            'questions' => ['required', 'array', 'min:1'],
            'questions.*._question' => ['required', 'array'],
            'questions.*._answers' => ['required', 'array'],

            'trustees' => ['required', 'array'],
//            'trustees.*.peer_server' => ['required_without_all:trustees.*.user', 'array'],
//            'trustees.*.user' => ['required_without_all:trustees.*.peer_server', 'array'],

            'public_key' => [
                'nullable',
                Rule::requiredIf($required)
            ],

            'broadcast' => [
                'nullable',
                Rule::requiredIf($required)
            ],
            'share' => [
                'nullable',
                Rule::requiredIf($required)
            ]
        ])->validate();

        // election
        $electionData = $data['election'];
        $election = Election::findFromUuid($electionData['uuid']);
        $election->fillFromSharedArray($electionData);

        // questions
        /** @var Collection|Question[] $questions */
        $questions = collect(array_map(function ($questionData) use ($election) {
            $answers = collect(array_map(function ($answerData) use ($election) {
                $a = new Answer();
                $a->fillFromSharedArray($answerData);
                return $a;
            }, $questionData['_answers']));
            $q = new Question();
            $q->fillFromSharedArray($questionData['_question']);
            $q->setAttribute('_answers', $answers);  // temporary, not a fillable field stored in DB
            return $q;
        }, $data['questions']));

        // trustee data
        $trustees = collect(array_map(function ($trusteeData) use ($election) {
            $t = new Trustee();
            $t->fillFromSharedArray($trusteeData);

            if (!is_null($trusteeData['peer_server'])) {
                $p = new PeerServer();
                $p->fillFromSharedArray($trusteeData['peer_server']);
                if ($knownPeer = PeerServer::withDomain($p->domain)->first()) {
                    $p = $knownPeer; // use existing if domain is known
                }
                $t->setAttribute('_peer_server', $p); // temporary, not a fillable field stored in DB
            } elseif (!is_null($trusteeData['user'])) {
                $u = new User();
                $u->fillFromSharedArray($trusteeData['user']);
                $t->setAttribute('_user', $u); // temporary, not a fillable field stored in DB
            } else {
                throw new Exception("peer_server_id and user_id can't both be null");
            }

            return $t;
        }, $data['trustees']));

        // public key
        $publicKey = $data['public_key'];
        if ($publicKey) {
            $publicKeyClass = $election->cryptosystem->getClass()::getPublicKeyClass();
            $publicKey = $publicKeyClass::fromArray($publicKey); // RSA, ELGAMAL
        }

        // broadcast
        $broadcast = $data['broadcast'];
        if ($broadcast) {
            $thresholdBroadcastClass = $election->cryptosystem->getClass()::getThresholdBroadcastClass();
            $broadcast = $thresholdBroadcastClass::fromArray($broadcast); // RSA, ELGAMAL
        }

        // share
        $share = $data['share'];
        if ($share) {
            $share = new BigInteger($share, 16);
        }

        return new self(
            $sender,
            getCurrentServer(),
            $election,
            $questions,
            $trustees,
            $publicKey,
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
        Log::debug('Freezing election of peer ' . $this->requestSender->name);

        if ($this->requestSender->id !== $this->election->peer_server_id) {
            throw new NotYourElectionException();
        }

        // election
        $this->election->save();

        // questions and answers
        $this->questions->each(function (Question $question) {
            /** @var Collection|Answer[] $answers */
            $answers = $question->getAttributes()['_answers'];
            $question->offsetUnset('_answers');
            $question->election_id = $this->election->id;
            $question->save();
            $answers->each(function (Answer $answer) use ($question) {
                $answer->question_id = $question->id;
                $answer->save();
            });
        });

        // trustees
        self::storeNewTrustees($this->election, $this->trustees);

        if ($senderTrustee = $this->election->getTrusteeFromPeerServer($this->requestSender)) {
            $senderTrustee->setPublicKey($this->senderPublicKey);
            $senderTrustee->broadcast = $this->senderThresholdBroadcast;
            $senderTrustee->share_received = $this->senderShare;
            $senderTrustee->save();
        }

        $jobs = [
            // send a AddMeToYourPeers message to each unknown peer
            new RunP2PTask(new SendAddMeToYourPeersMessageToUnknownPeers($this->election)),
        ];

        $meTrustee = $this->election->getTrusteeFromPeerServer(getCurrentServer(), true);

        //Generating my own keypair
        $meTrustee->generateKeyPair();

        if ($this->election->hasTLThresholdScheme()) {

            Log::debug('Freeze1IAmFreezingElection > hasTLThresholdScheme --> GenerateAndSendShares');

            // Generating my own polynomial to send back
            $meTrustee->polynomial = $meTrustee->private_key->getThresholdPolynomial($this->election->min_peer_count_t);

            // Generating my own broadcast to send back
            $meTrustee->broadcast = $meTrustee->polynomial->getBroadcast();

            // store the share of my own secret key
            $meIdx = $meTrustee->getPeerServerIndex();
            $meTrustee->share_received = $meTrustee->polynomial->getShare($meIdx + 1);

            // t-l threshold
            $jobs[] = new RunP2PTask(new GenerateAndSendShares($this->election));

        }

        $meTrustee->save();
//        $jobs[] = new SendP2PMessage(new Freeze2IAmReadyForFreeze(
//            getCurrentServer(),
//            $this->from // send back to coordinator
//            // TODO
//        ));
        // TODO after all AddMeToYourPeers messages are done
        //  > request only to peers with higher "label" and expect requests from peers with lower "label"

//        $broadcastToSendBack = null;
        $shareToSendBack = null;
        $freezeReady = true;

        $senderTrustee = $this->election->getTrusteeFromPeerServer($this->requestSender);
        if ($senderTrustee) { // sender (coordinator) is a peer

            if ($this->election->hasTLThresholdScheme()) {

                $senderIdx = $senderTrustee->getPeerServerIndex();
                // $broadcastToSendBack = $meTrustee->broadcast->toArray();
                $shareToSendBack = $meTrustee->polynomial->getShare($senderIdx + 1); // TODO check +1
                $senderTrustee->share_sent = $shareToSendBack;
                $senderTrustee->save();

                $freezeReady = ThisIsMyThresholdBroadcast::areAllSharesReceived($this->election);
            }

        }

        // execute jobs in sequence
        Log::debug('Bus chain dispatch in 5 seconds');

        // wait for 5 seconds to allow everyone to receive the first phase message and generate its polynomial
        Bus::chain($jobs)->delay(5)->dispatch();

        return new Freeze1IAmFreezingElectionResponse(
            getCurrentServer(),
            $this->requestSender,
            $this->election,
            $meTrustee->public_key,
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
     * @param \Illuminate\Support\Collection|Trustee[] $trustees
     * @return void
     * @throws \Exception
     */
    private static function storeNewTrustees(Election $election, Collection $trustees): void
    {
        foreach ($trustees as $trustee) {

            // store
            if ($ex = Trustee::findUUID($trustee->uuid)) {
                //if exists, do not create it
                Log::debug("Freeze1IAmFreezingElectionResponse > Trustee with uuid $trustee->uuid exists, skipping creation");
//                return $ex;
                continue;
            }

            $_temp_peer_server = null;
            if (array_key_exists('_peer_server', $trustee->getAttributes())) {
                $_temp_peer_server = $trustee->getAttributes()['_peer_server'];
                $trustee->offsetUnset('_peer_server');
            }

            $_temp_user = null;
            if (array_key_exists('_user', $trustee->getAttributes())) {
                $_temp_user = $trustee->getAttributes()['_user'];
                $trustee->offsetUnset('_user');
            }

            $trustee->election()->associate($election)->save();
            $trustee->save();

            if (!is_null($_temp_peer_server)) {
                $trustee->peerServer()->associate($_temp_peer_server)->save();
            } elseif (!is_null($_temp_user)) {
                $trustee->user()->associate($_temp_user)->save();
            } else {
                throw new Exception("peer_server_id and user_id can't both be null");
            }

        };
    }

}
