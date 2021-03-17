<?php


namespace App\P2P\Messages;


use App\Models\Election;
use App\Models\ElectionPeerServer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Class AddMeToYourElectionPeers
 * @package App\P2P\Messages
 * @property Election $election
 */
class AddMeToYourElectionPeers extends P2PMessage
{

    const NAME = 'add_me_to_your_election_peers';

    private $election;

    public function __construct(Election $election, string $from, string $to)
    {
        $this->election = $election;
        $this->from = $from;
        $this->to = $to;
    }

    public static function fromRequest(Request $request)
    {
        $data = $request->validate([
            'sender' => ['required', 'url'],
            'election_id' => ['required']
        ]);
        $election = Election::findOrFail($data['election_id']);
        return new AddMeToYourElectionPeers($election, $data['sender'], config('app.url'));
    }

    /**
     *
     */
    public function getRequestData(): array
    {
        return [
            'message' => AddMeToYourElectionPeers::NAME,
            'election_id' => 1,
        ];
    }

    public function onMessageReceived()
    {
        $peer = new ElectionPeerServer();
        $peer->ip = $this->from;
        $peer->name = "server " . $this->from;
        //$peer->election()->associate($this->election)
        $peer->save();
        Log::debug("Hello message received");

        return true;

    }


}
