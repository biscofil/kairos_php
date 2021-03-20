<?php


namespace App\P2P\Messages;


use App\Models\Election;
use App\Models\PeerServer;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Class WillYouBeAElectionTrusteeForMyElection
 * @package App\P2P\Messages
 * @property array $electionData
 */
class WillYouBeAElectionTrusteeForMyElection extends P2PMessage
{

    protected $name = 'will_you_be_a_election_trustee_for_my_election';

    public $electionData;

    /**
     * WillYouBeAElectionTrusteeForMyElection constructor.
     * @param array $electionData
     * @param string $from
     * @param string $to
     * @throws \Exception
     */
    public function __construct(array $electionData, string $from, string $to)
    {
        parent::__construct($from, $to);
        $this->electionData = $electionData;
    }

    /**
     * @param array $messageData
     * @return static
     * @throws \Exception
     */
    public static function fromRequest(array $messageData): P2PMessage
    {
        $data = Validator::make($messageData, [
            'election' => ['required', 'array']
        ])->validated();

        return new WillYouBeAElectionTrusteeForMyElection(
            $data['election'],
            $messageData['sender'],
            config('app.url')
        );
    }

    /**
     * @return array
     */
    public function getRequestData(): array
    {
        return [
            'election' => $this->electionData,
        ];
    }

    /**
     * @return P2PMessage|null
     */
    public function onMessageReceived(): ?P2PMessage
    {

        Log::debug("WillYouBeAElectionTrusteeForMyElection message received");

        $host = $this->from;

        $election = Election::make($this->electionData);
        $election->id = null;
        $election->admin_id = null;
        $election->peerServerAuthor()->associate(PeerServer::withDomain($host)->firstOrFail());

        try {
            $election->save();
            Log::info("I now have a copy of the election of $host");
            return new OkIWillBeAnElectionTrustee($election, $this->to, $this->from);
        } catch (\Exception $E) {
            Log::error("Error copying the election of $host");
            return null;
        }

    }


}
