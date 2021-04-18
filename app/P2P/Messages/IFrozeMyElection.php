<?php


namespace App\P2P\Messages;


use App\Models\Election;
use App\Models\PeerServer;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Class IFrozeMyElection
 * @package App\P2P\Messages
 * @property Election $election
 */
class IFrozeMyElection extends P2PMessage
{

    public const name = 'i_froze_my_election';

    /**
     * @var \App\Models\Election
     */
    public Election $election;

    public function __construct(PeerServer $from, array $to, Election $election)
    {
        parent::__construct($from, $to);
        $this->election = $election;
    }

    public function getRequestData(PeerServer $to): array
    {
        return [
            'election_uuid' => $this->election->uuid
        ];
    }

    public static function fromRequest(PeerServer $sender, array $messageData): P2PMessage
    {

        $data = Validator::make($messageData, [
            'election_uuid' => ['required', 'uuid'],
        ])->validate();

        $election = Election::findFromUuid($data['election_uuid']);
//        unset($messageData['id']);
//        $election->fill($messageData);

        return new static(
            $sender,
            [PeerServer::me()],
            $election
        );
    }

    public function onRequestReceived(): JsonResponse
    {
        Log::debug("Freezing election of another peer");

        if ($this->from->id !== $this->election->peer_server_id) {
            return new JsonResponse(["error" => "not your election"], 400);
        }

        $this->election->freeze();
        return new JsonResponse(["status" => "election frozen"]);
    }
}
