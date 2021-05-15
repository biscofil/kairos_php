<?php


namespace App\P2P\Messages\Freeze\Freeze3CommitFail;


use App\Models\Election;
use App\Models\PeerServer;
use App\Models\Trustee;
use App\P2P\Messages\Freeze\Freeze2IAmReadyForFreeze\Freeze2IAmReadyForFreezeResponse;
use App\P2P\Messages\P2PMessageRequest;
use App\P2P\Messages\P2PMessageResponse;
use Illuminate\Support\Facades\Validator;

/**
 * Describes the third message of the three-phase-commit procedure for election freeze
 * Class FreezeCommitFail
 * @package App\P2P\Messages\Freeze\Freeze3CommitFail
 * @property Election $election
 * @property bool $commit
 */
class Freeze3CommitFailRequest extends P2PMessageRequest
{

    public bool $commit;
    public Election $election;

    /**
     * Freeze3CommitFailRequest constructor.
     * @param \App\Models\PeerServer $requestSender
     * @param \App\Models\PeerServer $requestDestinations
     * @param \App\Models\Election $election
     * @param bool $commit
     * @throws \Exception
     */
    public function __construct(PeerServer $requestSender, PeerServer $requestDestinations, Election $election, bool $commit)
    {
        parent::__construct($requestSender, [$requestDestinations]);
        $this->commit = $commit;
        $this->election = $election;
    }

    /**
     * @return string
     */
    public static function getRequestMessageName(): string
    {
        return 'freeze3_commit_fail_request';
    }

    /**
     * @return string
     */
    public static function getResponseClass(): string
    {
        return Freeze3CommitFailResponse::class;
    }

    // ###################################################################

    /**
     * @param \App\Models\PeerServer $to
     * @return bool[]
     */
    public function serialize(PeerServer $to): array
    {
        return [
            'election' => [
                'uuid' => $this->election->uuid
            ],
            'commit' => $this->commit
        ];
    }

    /**
     * @param \App\Models\PeerServer $sender
     * @param array $messageData
     * @return \App\P2P\Messages\P2PMessageRequest
     * @throws \Exception
     */
    public static function unserialize(PeerServer $sender, array $messageData): P2PMessageRequest
    {
        $data = Validator::make($messageData, [
            'election' => ['required', 'array'],
            'election.uuid' => ['required', 'uuid'],
            'commit' => ['required', 'bool'],
        ])->validate();

        $election = Election::findFromUuid($data['election']['uuid']);

        $commit = boolval($data['commit']);

        return new static($sender, PeerServer::me(), $election, $commit);
    }

    // ###################################################################

    /**
     * @return \App\P2P\Messages\P2PMessageResponse
     */
    public function onRequestReceived(): P2PMessageResponse
    {
        if ($this->commit) {
            // do actual freeze
            $this->election->actualFreeze();
        } else {
            // undo freezing stuff
            $this->election->trustees()->each(function (Trustee $trustee) {
                $trustee->polynomial = null;
                $trustee->broadcast = null;
                $trustee->share_sent = null;
                $trustee->share_received = null;
                $trustee->save();
            });
        }

        return new Freeze2IAmReadyForFreezeResponse(PeerServer::me(), $this->requestSender);
    }
}
