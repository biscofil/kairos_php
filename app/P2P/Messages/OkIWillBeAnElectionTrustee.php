<?php


namespace App\P2P\Messages;


use App\Models\Election;
use App\Models\PeerServer;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Class OkIWillBeAnElectionTrustee
 * @package App\P2P\Messages
 * @property Election $election
 */
class OkIWillBeAnElectionTrustee extends P2PMessage
{

    protected $name = 'ok_i_will_be_an_election_trustee';

    public $election;

    /**
     * OkIWillBeAnElectionTrustee constructor.
     * @param Election $election
     * @param string $from
     * @param string $to
     * @throws \Exception
     */
    public function __construct(Election $election, string $from, string $to)
    {
        parent::__construct($from, $to);
        $this->election = $election;
    }

    /**
     * @param array $messageData
     * @return static
     * @throws \Exception
     */
    public static function fromRequest(array $messageData): P2PMessage
    {
        Log::debug("OkIWillBeAnElectionTrustee > fromRequest");

        $data = Validator::make($messageData, [
            'election_slug' => ['required', 'exists:elections,slug']
        ])->validated();

        /** @var Election $election */
        $election = Election::query()
            ->where('slug', '=', $data['election_slug'])
            ->firstOrFail();

        return new OkIWillBeAnElectionTrustee($election, $messageData['sender'], config('app.url'));
    }

    /**
     * @return array
     */
    public function getRequestData(): array
    {
        Log::debug("OkIWillBeAnElectionTrustee > getRequestData");

        return [
            'election_slug' => $this->election->slug,
        ];
    }

    /**
     * @return P2PMessage|null
     */
    public function onMessageReceived(): ?P2PMessage
    {

        Log::debug("OkIWillBeAnElectionTrustee > onMessageReceived");

        $host = $this->from;

        $server = PeerServer::withDomain($host)->firstOrFail();

        $trustee = $this->election->trustees()
            ->where('peer_server_id', '=', $server->id)
            ->first();

        if ($trustee) {

            $trustee->peer_confirmation = true;
            $trustee->save();

            Log::info("Server $host added as trustee");

        } else {

            Log::warning("Server $host NOT added as trustee");

        }

        return null;

    }

}
