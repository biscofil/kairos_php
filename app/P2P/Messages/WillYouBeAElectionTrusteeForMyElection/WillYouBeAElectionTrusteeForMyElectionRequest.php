<?php


namespace App\P2P\Messages\WillYouBeAElectionTrusteeForMyElection;


use App\Http\Requests\EditCreateElectionRequest;
use App\Models\Election;
use App\Models\PeerServer;
use App\P2P\Messages\P2PMessageRequest;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Class WillYouBeAElectionTrusteeForMyElection
 * @package App\P2P\Messages
 * @property Election $election
 */
class WillYouBeAElectionTrusteeForMyElectionRequest extends P2PMessageRequest
{

    public Election $election;

    /**
     * @return string
     */
    public static function getRequestMessageName(): string
    {
        return 'will_you_be_a_election_trustee_for_my_election_request';
    }

    public static function getResponseClass(): string
    {
        return WillYouBeAElectionTrusteeForMyElectionResponse::class;
    }

    /**
     * WillYouBeAElectionTrusteeForMyElection constructor.
     * @param PeerServer $requestSender
     * @param PeerServer[] $requestDestinations
     * @param Election $election
     * @throws Exception
     */
    public function __construct(PeerServer $requestSender, array $requestDestinations, Election $election)
    {
        parent::__construct($requestSender, $requestDestinations);
        $this->election = $election;
    }

    // #######################################################################################

    /**
     * @param \App\Models\PeerServer $to
     * @return array
     */
    public function serialize(PeerServer $to): array
    {
        Log::debug("sending WillYouBeAElectionTrusteeForMyElection message to {$to->domain}");

        return [
            'election' => $this->election->toArray(),
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

        return new static(
            $sender,
            [getCurrentServer()],
            $election
        );
    }

    // #######################################################################################

    /**
     * This code is for the server to which we are sending the request to
     * he has to respond with its public key
     * @return WillYouBeAElectionTrusteeForMyElectionResponse
     */
    public function onRequestReceived(): WillYouBeAElectionTrusteeForMyElectionResponse
    {

        Log::debug('WillYouBeAElectionTrusteeForMyElection message received');

        $this->election->id = null;
        $this->election->admin_id = null;
        $this->election->peerServerAuthor()->associate($this->requestSender); // TODO check ip / domain
        $this->election->save();

        Log::info("I now have a copy of the election of {$this->requestSender->name}");

        return new WillYouBeAElectionTrusteeForMyElectionResponse(getCurrentServer(), $this->requestSender);

    }

}
