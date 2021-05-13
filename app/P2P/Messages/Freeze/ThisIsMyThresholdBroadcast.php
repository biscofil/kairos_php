<?php


namespace App\P2P\Messages\Freeze;


use App\Models\Election;
use App\Models\PeerServer;
use App\P2P\Messages\P2PMessage;
use App\Voting\CryptoSystems\ElGamal\EGThresholdBroadcast;
use App\Voting\CryptoSystems\ThresholdBroadcast;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use phpseclib3\Math\BigInteger;

/**
 * Class ThisIsMyThresholdBroadcast
 * @package App\P2P\Messages
 * @property ThresholdBroadcast $broadcast
 * @property Election $election
 * @property BigInteger $share
 */
class ThisIsMyThresholdBroadcast extends P2PMessage
{

    public BigInteger $share;
    public Election $election;
    public ThresholdBroadcast $broadcast;

    /**
     * @return string
     */
    public static function getMessageName(): string
    {
        return 'this_is_my_threshold_broadcast';
    }

    /**
     * ThisIsMyThresholdBroadcast constructor.
     * @param PeerServer $from
     * @param PeerServer[] $to
     * @param Election $election
     * @param EGThresholdBroadcast $broadcast
     * @param BigInteger $share
     * @throws \Exception
     */
    public function __construct(PeerServer $from, array $to, Election $election, ThresholdBroadcast $broadcast, BigInteger $share)
    {
        parent::__construct($from, $to);
        $this->election = $election;
        $this->broadcast = $broadcast;
        $this->share = $share;
    }

    /**
     * Executed by the server sending the ThisIsMyThresholdBroadcast request
     * @param \App\Models\PeerServer $to
     * @return array
     */
    public function getRequestData(PeerServer $to): array
    {
        return [
            'election_uuid' => $this->election->uuid,
            'broadcast' => $this->broadcast->toArray(),
            'share' => $this->share->toHex()
        ];
    }

    /**
     * Executed by the server seceiving the ThisIsMyThresholdBroadcast request
     * @param PeerServer $sender
     * @param array $messageData
     * @return static
     * @throws \Exception
     */
    public static function fromRequest(PeerServer $sender, array $messageData): P2PMessage
    {
        $data = Validator::make($messageData, [
            'election_uuid' => ['required', 'uuid', 'exists:elections,uuid'],
            'broadcast' => ['required', 'array'],
            'share' => ['required', 'string']
        ])->validate();

        Log::debug('received ThisIsMyThresholdBroadcast request');

        $election = Election::findFromUuid($data['election_uuid']);

        $thresholdBroadcastClass = $election->cryptosystem->getCryptoSystemClass()::ThresholdBroadcastClass;
        $broadcast = $thresholdBroadcastClass::fromArray($data['broadcast']); // RSA, ELGAMAL

        $receivedShare = new BigInteger($data['share'], 16);

        return new static(
            $sender,
            [PeerServer::me()],
            $election,
            $broadcast,
            $receivedShare
        );
    }

    // #######################################################################################
    // ##################################### RESPONSE ########################################
    // #######################################################################################

    /**
     * Code executed by server J when broadcast of server I arrives
     * @return
     * @throws \Exception
     * @noinspection PhpIncompatibleReturnTypeInspection
     */
    public function getRequestResponse()
    {

        Log::debug('responding to ThisIsMyThresholdBroadcast request');

        // find sending trustee by peer server
        $trusteeI = $this->election->getTrusteeFromPeerServer($this->from);
        if (!$trusteeI) {
            return new JsonResponse(['error' => 'trustee not found', 400]);
        }

        $meTrustee = $this->election->trustees()
            ->where('peer_server_id', '=', PeerServer::meID)
            ->firstOrFail(); // TODO what if same server is not a trustee?

        $j = $trusteeI->getPeerServerIndex();
        $shareToSendBack = $meTrustee->polynomial->getShare($j);

        // TODO set polynomial
        Log::debug('Received broadcast : ' . $this->broadcast->toString());
//        Log::debug($this->broadcast->toArray());

        $trusteeI->broadcast = $this->broadcast;
        $trusteeI->share_received = $this->share;
        $trusteeI->share_sent = $shareToSendBack;
        $trusteeI->save();

        return new JsonResponse([
            'my_broadcast' => $meTrustee->broadcast->toArray(),
            'my_share' => $trusteeI->share_sent->toHex()
        ]);

//        if ($this->broadcast->isValid($trusteeI->share_received, $j)) { // TODO
//            return new JsonResponse(['msg' => 'Great, valid polynomial']);
//        } else {
////            RunP2PTask::dispatch(new SendBroadcastComplaint($this->broadcast));
//            return new JsonResponse(['error' => 'I am about to broadcast complaint'], 400);
//        }

        // TODO if all requests received -> reply to coordinator
        //  if all shares are valid reply "OK" to coordinator
        //  else reply "FAIL" to coordinator

    }

    /**
     * @param \App\Models\PeerServer $destPeerServer
     * @param \GuzzleHttp\Promise\PromiseInterface|\Illuminate\Http\Client\Response $response
     */
    protected function onResponseReceived(PeerServer $destPeerServer, $response): void
    {

        Log::debug('ThisIsMyThresholdBroadcast response received');

        $trustee = $this->election->trustees()
            ->where('peer_server_id', '=', $destPeerServer->id)
            ->firstOrFail();

        // save received broadcast, share
        $shareSentBack = $response->json('my_share');
        $shareSentBack = new BigInteger($shareSentBack, 16);
        Log::debug('share received: ' . $shareSentBack->toHex());

        /** @var ThresholdBroadcast $thresholdBroadcastClass */
        $thresholdBroadcastClass = $this->election->cryptosystem->getCryptoSystemClass()::ThresholdBroadcastClass;
        $broadcastSentBack = $response->json('my_broadcast');
        $broadcastSentBack = $thresholdBroadcastClass::fromArray($broadcastSentBack);
        Log::debug('broadcast received: ' . $broadcastSentBack->toString());
//        Log::debug($broadcastSentBack->toArray());

        $trustee->broadcast = $broadcastSentBack;
        $trustee->share_received = $shareSentBack;
        $trustee->save();


        // TODO if all requests received -> reply to coordinator
        //  if all shares are valid reply "OK" to coordinator
        //  else reply "FAIL" to coordinator
    }

    private function allSharesShared()
    {

        // TODO send all received broadcast to coordinator, he will check they are the same for all peers
//           TODO RunP2PTask::dispatch(new ReplyToCoordinator($this->broadcast));
    }


}
