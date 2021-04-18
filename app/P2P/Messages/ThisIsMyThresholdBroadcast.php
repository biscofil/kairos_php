<?php


namespace App\P2P\Messages;


use App\Jobs\RunP2PTask;
use App\Models\Election;
use App\Models\PeerServer;
use App\P2P\Tasks\SendBroadcastComplaint;
use App\Voting\CryptoSystems\ElGamal\EGThresholdBroadcast;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use phpseclib3\Math\BigInteger;

/**
 * Class ThisIsMyThresholdBroadcast
 * @package App\P2P\Messages
 * @property EGThresholdBroadcast $broadcast
 * @property Election $election
 * @property BigInteger $share
 */
class ThisIsMyThresholdBroadcast extends P2PMessage
{

    private BigInteger $share;
    private Election $election;
    private EGThresholdBroadcast $broadcast;

    /**
     * ThisIsMyThresholdBroadcast constructor.
     * @param PeerServer $from
     * @param PeerServer[] $to
     * @param Election $election
     * @param EGThresholdBroadcast $broadcast
     * @param BigInteger $share
     * @throws \Exception
     */
    public function __construct(PeerServer $from,array $to, Election $election, EGThresholdBroadcast $broadcast, BigInteger $share)
    {
        parent::__construct($from, $to);
        $this->election = $election;
        $this->broadcast = $broadcast;
        $this->share = $share;
    }

    /**
     * @param string $sender
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

        $broadcast = EGThresholdBroadcast::fromArray($data['broadcast']);

        return new static(
            $sender,
            [self::me()],
            Election::findFromUuid($data['election_uuid']),
            $broadcast,
            new BigInteger($data['share'], 16)
        );
    }

    /**
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

    // #######################################################################################
    // ##################################### RESPONSE ########################################
    // #######################################################################################

    /**
     * Code executed by server J when broadcast of server I arrives
     * @return JsonResponse
     * @throws \Exception
     */
    public function onRequestReceived(): JsonResponse
    {

        // find sending trustee by peer server
        $trusteeI = $this->election->getTrusteeFromPeerServer($this->from);
        if (!$trusteeI) {
            return new JsonResponse(["error" => "trustee not found", 400]);
        }

        // set polynomial
        $trusteeI->broadcast = $this->broadcast;
        $trusteeI->share = $this->share; // TODO
        $trusteeI->save();

        $j = 1; // TODO compute index!!!!

        if ($this->broadcast->isValid($trusteeI->share, $j)) { // TODO
            return new JsonResponse(["msg" => "Great, valid polynomial"]);
        } else {
            RunP2PTask::dispatch(new SendBroadcastComplaint(
                $this->broadcast,
                $this->from,
                $this->to
            ));
            return new JsonResponse(["error" => "I am about to broadcast complaint"], 400);
        }

    }


}
