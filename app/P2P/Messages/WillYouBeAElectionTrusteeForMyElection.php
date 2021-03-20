<?php


namespace App\P2P\Messages;


use App\Crypto\EGKeyPair;
use App\Crypto\EGPublicKey;
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

    public const name = 'will_you_be_a_election_trustee_for_my_election';

    public $electionData;

    // #######################################################################################
    // ##################################### REQUEST #########################################
    // #######################################################################################

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
        ])->validate();

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

    // #######################################################################################
    // ##################################### RESPONSE ########################################
    // #######################################################################################

    /**
     * This code is for the server to which we are sending the request to
     * he has to respond with its public key
     * @return array
     */
    public function onRequestReceived(): array
    {

        Log::debug("WillYouBeAElectionTrusteeForMyElection message received");

        $host = $this->from;

        $election = Election::make($this->electionData);
        $election->id = null;
        $election->admin_id = null;
        $election->peerServerAuthor()->associate(PeerServer::withDomain($host)->firstOrFail());
        $election->save();

        $keyPair = EGKeyPair::generate();
        $keyPair->storeToFile('election_' . $election->id);

        Log::info("I now have a copy of the election of $host");

        return [
            'public_key' => json_encode($keyPair->pk->toArray())
        ];

    }

    /**
     * We parse the public key and we assign it to the trustee
     * @param array $data
     */
    public function onResponseReceived(array $data): void
    {

        $server = PeerServer::withDomain($this->to)->firstOrFail();

        $election = Election::findOrFail($this->electionData['id']);

        $trustee = $election->trustees()
            ->where('peer_server_id', '=', $server->id)
            ->first();

        if ($trustee) {

            $public_key = EGPublicKey::fromArray(json_decode($data['public_key'], true));
            $trustee->setPublicKey($public_key);
            $trustee->save();

            Log::info("Server $this->to added as trustee");

        } else {

            Log::error("Server $this->to NOT added as trustee");

        }

    }

}
