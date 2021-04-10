<?php


namespace App\P2P\Messages;


use App\Http\Requests\EditCreateElectionRequest;
use App\Models\Election;
use App\Models\PeerServer;
use App\Voting\CryptoSystems\PublicKey;
use Exception;
use Illuminate\Http\JsonResponse;
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

    public array $electionData;

    // #######################################################################################
    // ##################################### REQUEST #########################################
    // #######################################################################################

    /**
     * WillYouBeAElectionTrusteeForMyElection constructor.
     * @param array $mixSet
     * @param string $from
     * @param string $to
     * @throws Exception
     */
    public function __construct(array $mixSet, string $from, string $to)
    {
        parent::__construct($from, $to);
        $this->electionData = $mixSet;
    }

    /**
     * @param string $sender
     * @param array $messageData
     * @return static
     * @throws Exception
     */
    public static function fromRequest(string $sender, array $messageData): P2PMessage
    {
        $data = Validator::make($messageData, [
            'election' => ['required', 'array']
        ])->validate();

        $electionData = $data['election'];

        return new WillYouBeAElectionTrusteeForMyElection(
            $electionData,
            $sender,
            config('app.url')
        );
    }

    /**
     * @param string $to
     * @return array
     */
    public function getRequestData(string $to): array
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
     * @return JsonResponse
     */
    public function onRequestReceived(): JsonResponse
    {

        Log::debug("WillYouBeAElectionTrusteeForMyElection message received");

        // validate the sent data
        $errors = Validator::make($this->electionData,
            (new EditCreateElectionRequest())->rules()
        )->errors()->toArray();

        if (count($errors)) {
            return new JsonResponse([
                'errors' => $errors
            ], 402);
        }

        $host = $this->from;

        $election = Election::make($this->electionData);
        $election->id = null;
        $election->admin_id = null;
        $election->peerServerAuthor()->associate(PeerServer::withDomain($host)->firstOrFail());
        $election->save();

        if($election->delta_t_l === 0){
            // no slack : l-l theshold
        }else{
            // t-l threshold
        }

        $keyPair = $election->cryptosystem->getCryptoSystemClass()->generateKeypair(); // TODO remove for threshold
        $keyPair->storeToFile('election_' . $election->id . '.keypair.json'); // TODO remove for threshold

        Log::info("I now have a copy of the election of $host");

        return new JsonResponse([
            'public_key' => json_encode($keyPair->pk->toArray()) // TODO remove for threshold
        ]);

    }

    /**
     * We parse the public key and we assign it to the trustee
     * @param string $destPeerServer
     * @param array $data
     * @throws Exception
     */
    public function onResponseReceived(string $destPeerServer, array $data): void
    {

        $server = PeerServer::withDomain($destPeerServer)->firstOrFail();

        $election = Election::findOrFail($this->electionData['id']);

        $trustee = $election->getTrusteeFromPeerServer($server);

        if ($trustee) {

            /** @var PublicKey $pkClass */
            $pkClass = $election->cryptosystem->getCryptoSystemClass()::PublicKeyClass;
            $public_key = $pkClass::fromArray(json_decode($data['public_key'], true));
            $trustee->setPublicKey($public_key);
            $trustee->save();

            Log::info("Server $destPeerServer added as trustee");

        } else {

            Log::error("Server $destPeerServer NOT added as trustee");

        }

    }

}
