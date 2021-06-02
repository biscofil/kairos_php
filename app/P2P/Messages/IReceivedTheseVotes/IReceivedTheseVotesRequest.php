<?php


namespace App\P2P\Messages\IReceivedTheseVotes;


use App\Models\CastVote;
use App\Models\PeerServer;
use App\P2P\Messages\P2PMessageRequest;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Class IReceivedTheseVotesRequest
 * @package App\P2P\Messages\IReceivedTheseVotes
 * @property \App\Models\CastVote[] $ballots
 */
class IReceivedTheseVotesRequest extends P2PMessageRequest
{

    public array $ballots;

    /**
     * @return string
     */
    public static function getRequestMessageName(): string
    {
        return 'i_received_these_votes_request';
    }

    public static function getResponseClass(): string
    {
        return IReceivedTheseVotesResponse::class;
    }

    /**
     * ThisIsMyMixSet constructor.
     * @param PeerServer $requestSender
     * @param PeerServer[] $requestDestinations
     * @param \App\Models\CastVote[] $ballots
     * @throws Exception
     */
    public function __construct(PeerServer $requestSender, array $requestDestinations, array $ballots)
    {
        parent::__construct($requestSender, $requestDestinations);
        $this->ballots = $ballots;
    }

    // #######################################################################################

    /**
     * @param \App\Models\PeerServer $to
     * @return array
     */
    public function serialize(PeerServer $to): array
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
    public static function unserialize(PeerServer $sender, array $messageData): P2PMessageRequest
    {
        $data = Validator::make($messageData, [
            'ballots' => ['required', 'array']
        ])->validate();

        return new static(
            $sender,
            [getCurrentServer()],
            $data['ballots']
        );
    }

    /**
     * This code is for the server to which we are sending the request to
     * @return IReceivedTheseVotesResponse
     */
    public function onRequestReceived() : IReceivedTheseVotesResponse
    {
        // TODO queue job
        $n = count($this->ballots);
        Log::debug("{$this->requestSender->name} has sent me $n ballots");
        return new IReceivedTheseVotesResponse(getCurrentServer(), $this->requestSender);
    }


}
