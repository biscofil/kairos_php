<?php


namespace App\P2P\Messages\ThisIsMyMixSet;


use App\Models\Election;
use App\Models\Mix;
use App\Models\PeerServer;
use App\P2P\Messages\P2PMessageRequest;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * Class ThisIsMyMixSetRequest
 * @package App\P2P\Messages
 * @property Mix $mixModel
 */
class ThisIsMyMixSetRequest extends P2PMessageRequest
{

    public Mix $mixModel;

    /**
     * @return string
     */
    public static function getRequestMessageName(): string
    {
        return 'this_is_my_mix_set_request';
    }

    /**
     * @return string
     */
    public static function getResponseClass(): string
    {
        return ThisIsMyMixSetResponse::class;
    }

    /**
     * ThisIsMyMixSet constructor.
     * @param PeerServer $requestSender
     * @param PeerServer $requestDestination
     * @param \App\Models\Mix $mix
     * @throws \Exception
     */
    public function __construct(PeerServer $requestSender, PeerServer $requestDestination, Mix $mix)
    {
        parent::__construct($requestSender, [$requestDestination]);
        $this->mixModel = $mix;
    }

    // #######################################################################################

    /**
     * @param \App\Models\PeerServer $to
     * @return array
     */
    public function serialize(PeerServer $to): array
    {
        $file = Storage::get($this->mixModel->getFilename() . '.json'); // TODO remove
        return [
            'election_uuid' => $this->mixModel->trustee->election->uuid,
            'mix_set' => $this->mixModel,
            'file' => $file // TODO remove
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
            'election_uuid' => ['required', 'uuid'],
            'mix_set' => ['required', 'array'],
            'file' => ['required', 'array']
        ])->validate();

        $election = Election::findFromUuid($data['election_uuid']);

        $mixModel = new Mix();
        $mixModel->trustee_id = $election->getTrusteeFromPeerServer($sender, true)->id;
        $mixModel->fillFromSharedArray($data['mix_set']);

        // TODO dispatch verification process and mix if this is the next node

        return new static(
            $sender,
            PeerServer::me(),
            $mixModel
        );
    }

    // #######################################################################################

    /**
     * This code is for the server to which we are sending the request to
     * he has to respond with its public key
     * @return ThisIsMyMixSetResponse
     * @noinspection PhpIncompatibleReturnTypeInspection
     */
    public function onRequestReceived(): ThisIsMyMixSetResponse
    {
        Log::debug('ThisIsMyMixSet message received');

        $this->mixModel->save();

        // DISPATCH DOWNLOAD

        return new ThisIsMyMixSetResponse(PeerServer::me(), $this->requestSender);
    }

}
