<?php


namespace App\P2P\Messages;


use App\Models\CastVote;
use App\Models\PeerServer;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Class IReceivedTheseVotes
 * @package App\P2P\Messages
 * @property \App\Models\CastVote[] $ballots
 */
class IReceivedTheseVotes extends P2PMessage
{

    public const name = 'i_received_these_votes';

    public array $ballots;

    // #######################################################################################
    // ##################################### REQUEST #########################################
    // #######################################################################################

    /**
     * ThisIsMyMixSet constructor.
     * @param PeerServer $from
     * @param PeerServer[] $to
     * @param \App\Models\CastVote[] $ballots
     * @throws Exception
     */
    public function __construct(PeerServer $from, array $to, array $ballots)
    {
        parent::__construct($from, $to);
        $this->ballots = $ballots;
    }

    /**
     * @param \App\Models\PeerServer $to
     * @return array
     */
    public function getRequestData(PeerServer $to): array
    {
        return [
            'ballots' => array_map(function (CastVote $vote) {
                return $vote->toArray();
            }, $this->ballots),
        ];
    }

    /**
     * @param PeerServer $sender
     * @param array $messageData
     * @return static
     * @throws Exception
     */
    public static function fromRequest(PeerServer $sender, array $messageData): P2PMessage
    {
        $data = Validator::make($messageData, [
            'ballots' => ['required', 'array']
        ])->validate();

        return new static(
            $sender,
            [self::me()],
            $data['ballots']
        );
    }

    // #######################################################################################
    // ##################################### RESPONSE ########################################
    // #######################################################################################

    /**
     * This code is for the server to which we are sending the request to
     * @return
     */
    public function getRequestResponse()
    {
        // TODO queue job
        $n = count($this->ballots);
        Log::debug("{$this->from->name} has sent me $n ballots");
        return new JsonResponse(['status' => 'ok, job queued']);
    }

    /**
     * @param \App\Models\PeerServer $destPeerServer
     * @param \GuzzleHttp\Promise\PromiseInterface|\Illuminate\Http\Client\Response $response
     * @throws Exception
     */
    protected function onResponseReceived(PeerServer $destPeerServer, $response): void
    {

        Log::debug($response->json());

    }

}
