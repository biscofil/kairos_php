<?php


namespace App\P2P\Messages;


use App\Http\Requests\EditCreateElectionRequest;
use App\Models\Election;
use App\Models\PeerServer;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Class WillYouBeAElectionTrusteeForMyElection
 * @package App\P2P\Messages
 * @property Election $election
 */
class WillYouBeAElectionTrusteeForMyElection extends P2PMessage
{

    public Election $election;

    /**
     * @return string
     */
    public static function getMessageName(): string
    {
        return 'will_you_be_a_election_trustee_for_my_election';
    }

    // #######################################################################################
    // ##################################### REQUEST #########################################
    // #######################################################################################

    /**
     * WillYouBeAElectionTrusteeForMyElection constructor.
     * @param PeerServer $from
     * @param PeerServer[] $to
     * @param Election $election
     * @throws Exception
     */
    public function __construct(PeerServer $from, array $to, Election $election)
    {
        parent::__construct($from, $to);
        $this->election = $election;
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
            'election' => ['required', 'array']
        ])->validate();

        $electionData = $data['election'];

        $validator = Validator::make($data['election'],
            (new EditCreateElectionRequest())->rules()
        );
        if (count($validator->errors())) {
            throw new ValidationException($validator);
        }

        $election = Election::make($electionData);

        if (!$sender->exists) {
            throw new Exception('Peer server is unknown');
        }

        return new WillYouBeAElectionTrusteeForMyElection(
            $sender,
            [PeerServer::me()],
            $election
        );
    }

    /**
     * @param \App\Models\PeerServer $to
     * @return array
     */
    public function getRequestData(PeerServer $to): array
    {
        Log::debug("sending WillYouBeAElectionTrusteeForMyElection message to {$to->domain}");

        return [
            'election' => $this->election->toArray(),
        ];
    }

    // #######################################################################################
    // ##################################### RESPONSE ########################################
    // #######################################################################################

    /**
     * This code is for the server to which we are sending the request to
     * he has to respond with its public key
     * @return
     */
    public function getRequestResponse()
    {

        Log::debug('WillYouBeAElectionTrusteeForMyElection message received');

        $this->election->id = null;
        $this->election->admin_id = null;
        $this->election->peerServerAuthor()->associate($this->from); // TODO check ip / domain
        $this->election->save();

        Log::info("I now have a copy of the election of {$this->from->name}");

        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return new JsonResponse([]);

    }

    /**
     * We parse the public key and we assign it to the trustee
     * @param \App\Models\PeerServer $destPeerServer
     * @param \GuzzleHttp\Promise\PromiseInterface|\Illuminate\Http\Client\Response $response
     * @throws Exception
     */
    protected function onResponseReceived(PeerServer $destPeerServer, $response): void
    {

        Log::debug($response->json());

        $trustee = $this->election->getTrusteeFromPeerServer($destPeerServer);

        if ($trustee) {

            if (!$response->ok()) {
                $trustee->delete();
                Log::info("Server {$destPeerServer->name} deleted as trustee");
                return;
            }

            Log::info("Server {$destPeerServer->name} added as trustee");

        } else {

            Log::error("Server {$destPeerServer->name} NOT added as trustee");

        }

    }

}
